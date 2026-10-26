<?php

namespace App\Services;

/**
 * "Omadli maydon" — 3×3 maydonning 8 ta chekka katagi.
 *
 * Toza mantiq (DB'siz): qaysi sovg'a qaysi katakda turishini hal qiladi.
 * Katak tartibi soat mili bo'yicha: 0 — chap yuqori burchak, 2, 4, 6 — boshqa burchaklar.
 * Katak qiymati — sovg'a ID si yoki null ("Keyingi safar").
 *
 * Qoidalar:
 *  - kitob (fragments) ko'pi bilan 2 katak, promokod 3, tanga 2, sovg'a qutisi 1,
 *    kamida 1 ta "Keyingi safar";
 *  - kitob — eng oliy sovg'a: maydonda ko'pi bilan 2 ta;
 *  - yig'ilayotgan kitob (bo'laklari bor) doim maydonda — to'liq yig'ilguncha yoki
 *    sovg'a o'chirilguncha / zaxirasi tugaguncha (bir vaqtda ko'pi bilan 2 ta kitob
 *    yig'iladi — buni PrizeGameService::candidates ta'minlaydi);
 *  - qolgan kitoblar kuniga bir marta almashadi: har kuni bo'sh kitob kataklari to'liq
 *    yangilanadi, har bir user o'z navbatidan boshlaydi (hamma bir xil kitobni ko'rmaydi);
 *  - HAR KATAKDA BOSHQA SOVG'A — takror yo'q. Sovg'a kam bo'lsa, qolgan kataklar
 *    "Keyingi safar". (Katak soni tushish ehtimoliga ta'sir qilmaydi — ehtimolni
 *    boshqaruvdagi foiz belgilaydi; takrorlar userga noto'g'ri tasavvur berardi.)
 *  - kitoblar qarama-qarshi burchaklarda, bir xil turdagilar yonma-yon turmaydi;
 *  - yutilgan sovg'a maydonda bo'lmasa, o'sha turdagi katak almashtiriladi.
 */
class PrizeBoard
{
    public const SIZE = 8;

    public const QUOTA = ['fragments' => 2, 'promocode' => 3, 'coins' => 2, 'surprise' => 1];

    /**
     * @param  array<int, array{id:int, type:string}>  $prizes  yutish mumkin bo'lgan sovg'alar (tartib — admin tartibi)
     * @param  array<int, float>  $progress  prize_id => yig'ilgan bo'laklar ulushi (0..1)
     * @param  string  $seed  masalan "userId|2026-10-20" — kun davomida maydon o'zgarmaydi
     * @param  int  $rotation  kun raqami — kvotadan ortiq sovg'alar navbat bilan almashadi
     * @param  int  $offset  user bo'yicha siljish (masalan user ID) — navbat har userda boshqa joydan
     * @return array<int, int|null> 8 ta katak; sovg'a bo'lmasa — bo'sh massiv
     */
    public static function build(array $prizes, array $progress, string $seed, int $rotation = 0, int $offset = 0): array
    {
        $prizes = array_values(array_filter($prizes, fn ($p) => isset($p['id'], $p['type'])));
        if (! $prizes) {
            return [];
        }

        $groups = ['fragments' => [], 'promocode' => [], 'coins' => [], 'surprise' => []];
        foreach ($prizes as $p) {
            $type = isset($groups[$p['type']]) ? $p['type'] : 'coins';
            $groups[$type][] = (int) $p['id'];
        }

        // Yig'ilayotgan kitoblar (bo'laklari bor) — doim maydonda, eng ko'p yig'ilgani oldinda
        $collecting = array_values(array_filter($groups['fragments'], fn ($id) => ($progress[$id] ?? 0) > 0));
        usort($collecting, fn ($a, $b) => ($progress[$b] <=> $progress[$a]) ?: $a <=> $b);
        $quota = self::QUOTA;
        $collecting = array_slice($collecting, 0, $quota['fragments']);

        // Kunlik aylanma: qolgan sovg'alar kuniga bir marta almashadi. Har kuni bo'sh
        // kataklar soniga teng qadam tashlanadi — kecha ko'ringanlar o'rniga yangilari chiqadi.
        foreach ($groups as $t => $ids) {
            $fixed = $t === 'fragments' ? $collecting : [];
            $free = array_values(array_diff($ids, $fixed));
            sort($free);
            $slots = max(1, $quota[$t] - count($fixed));
            if (count($free) > $slots) {
                $shift = (($rotation * $slots) + $offset) % count($free);
                $free = array_merge(array_slice($free, $shift), array_slice($free, 0, $shift));
            }
            $groups[$t] = array_merge($fixed, $free);
        }

        $type = [];
        $picked = [];
        foreach ($quota as $t => $max) {
            $picked[$t] = array_slice($groups[$t], 0, $max);
        }
        // Kamida bitta "Keyingi safar" uchun joy: ortiqchasi eng ko'p turdan (avval promokod) olinadi.
        // Yig'ilayotgan kitoblarga tegilmaydi.
        $protected = ['fragments' => count($collecting)];
        while (array_sum(array_map('count', $picked)) > self::SIZE - 1) {
            $drop = null;
            foreach (['promocode', 'coins', 'fragments', 'surprise'] as $t) {
                if (count($picked[$t]) <= ($protected[$t] ?? 0)) {
                    continue;
                }
                if ($drop === null || count($picked[$t]) > count($picked[$drop])) {
                    $drop = $t;
                }
            }
            if ($drop === null) {
                break;
            }
            $picked[$drop] = array_slice($picked[$drop], 0, -1);
        }
        $unique = [];
        foreach ($picked as $t => $ids) {
            foreach ($ids as $id) {
                $unique[] = $id;
                $type[$id] = $t;
            }
        }

        // Noyob sovg'alar (ko'pi bilan 7) + qolgani "Keyingi safar"
        $items = $unique;
        while (count($items) < self::SIZE) {
            $items[] = null;
        }

        return self::arrange($items, $type, $seed);
    }

    /**
     * Yutuqni maydonga joylaydi.
     *
     * @param  array<int, int|null>  $board
     * @param  string|null  $type  yutilgan sovg'a turi (null — hech narsa)
     * @return array{board: array<int, int|null>, cell: int}
     */
    public static function place(array $board, ?int $prizeId, ?string $type, array $types, string $seed): array
    {
        if (! $board) {
            return ['board' => $board, 'cell' => -1];
        }

        $match = array_keys(array_filter($board, fn ($v) => $v === $prizeId, ARRAY_FILTER_USE_BOTH));
        if ($match) {
            return ['board' => $board, 'cell' => $match[self::hash($seed, count($board)) % count($match)]];
        }

        // Maydonda yo'q: avval o'sha turdagi, keyin takrorlangan, keyin istalgan katak almashadi
        $count = array_count_values(array_map(fn ($v) => (string) $v, $board));
        $candidates = [];
        foreach ($board as $cell => $id) {
            $score = 0;
            if ($prizeId === null) {
                $score = ($count[(string) $id] ?? 1) > 1 ? 2 : 1;
            } else {
                if ($id !== null && ($types[$id] ?? null) === $type) {
                    $score += 4;
                }
                if (($count[(string) $id] ?? 1) > 1) {
                    $score += 2;
                }
                if ($id !== null) {
                    $score += 1; // "Keyingi safar" katagini oxirgi navbatda olamiz
                }
            }
            if ($id !== null && ($types[$id] ?? null) === 'collecting') {
                $score = -10; // yig'ilayotgan kitob katagiga tegilmaydi
            }
            $candidates[$cell] = $score;
        }
        arsort($candidates);
        $best = max($candidates);
        $cells = array_keys(array_filter($candidates, fn ($s) => $s === $best));
        sort($cells);
        $cell = $cells[self::hash($seed, (int) $prizeId) % count($cells)];
        $board[$cell] = $prizeId;

        return ['board' => $board, 'cell' => $cell];
    }

    /** Kitoblar qarama-qarshi burchaklarda, bir xil tur yonma-yon emas. */
    private static function arrange(array $items, array $type, string $seed): array
    {
        $kind = fn ($id) => $id === null ? 'nothing' : ($type[$id] ?? 'coins');
        $board = array_fill(0, self::SIZE, false);

        $books = array_values(array_filter($items, fn ($id) => $kind($id) === 'fragments'));
        $rest = array_values(array_filter($items, fn ($id) => $kind($id) !== 'fragments'));
        $corners = [0, 4, 2, 6];
        foreach ($books as $n => $id) {
            if ($n < 4) {
                $board[$corners[$n]] = $id;
            } else {
                $rest[] = $id;
            }
        }

        // Qolganlar: bo'sh kataklarga eng kam "yonma-yon bir xil" bo'ladigan tartib (ko'pi bilan 8! variant).
        usort($rest, fn ($a, $b) => self::hash($seed, (int) $a + 7) <=> self::hash($seed, (int) $b + 7));
        $free = array_keys(array_filter($board, fn ($v) => $v === false));
        $best = null;
        $bestScore = PHP_INT_MAX;
        self::permute($rest, function (array $perm) use ($board, $free, $kind, &$best, &$bestScore) {
            foreach ($free as $n => $cell) {
                $board[$cell] = $perm[$n];
            }
            $score = 0;
            for ($i = 0; $i < self::SIZE; $i++) {
                $a = $board[$i];
                $b = $board[($i + 1) % self::SIZE];
                if ($kind($a) === $kind($b)) {
                    $score += $a === $b ? 3 : 2;
                }
            }
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $board;
            }

            return $score === 0;
        });

        return $best;
    }

    /** Takrorsiz o'rin almashtirishlar; callback true qaytarsa — to'xtaydi. */
    private static function permute(array $items, callable $visit, array $prefix = []): bool
    {
        if (! $items) {
            return $visit($prefix);
        }
        $tried = [];
        foreach ($items as $k => $item) {
            $key = var_export($item, true);
            if (isset($tried[$key])) {
                continue;
            }
            $tried[$key] = true;
            $rest = $items;
            unset($rest[$k]);
            if (self::permute(array_values($rest), $visit, [...$prefix, $item])) {
                return true;
            }
        }

        return false;
    }

    private static function hash(string $seed, int $n): int
    {
        return crc32($seed.'#'.$n);
    }
}
