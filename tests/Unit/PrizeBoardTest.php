<?php

namespace Tests\Unit;

use App\Services\PrizeBoard;
use PHPUnit\Framework\TestCase;

/** "Omadli maydon": 8 katak qanday to'ldirilishi va yutuq qayerda to'xtashi. */
class PrizeBoardTest extends TestCase
{
    private function prizes(array $spec): array
    {
        $out = [];
        $id = 1;
        foreach ($spec as $type => $n) {
            for ($i = 0; $i < $n; $i++) {
                $out[] = ['id' => $id++, 'type' => $type];
            }
        }

        return $out;
    }

    private function types(array $prizes): array
    {
        return array_column($prizes, 'type', 'id');
    }

    private function countType(array $board, array $types, ?string $type): int
    {
        return count(array_filter($board, fn ($id) => $type === null ? $id === null : ($id !== null && $types[$id] === $type)));
    }

    public function test_empty_prizes_give_empty_board(): void
    {
        $this->assertSame([], PrizeBoard::build([], [], 's'));
    }

    public function test_many_books_take_at_most_two_cells_and_board_is_full(): void
    {
        $p = $this->prizes(['fragments' => 6, 'promocode' => 5, 'coins' => 4]);
        $board = PrizeBoard::build($p, [], '7|2026-10-20');
        $t = $this->types($p);

        $this->assertCount(8, $board);
        $this->assertSame(2, $this->countType($board, $t, 'fragments'));
        $this->assertSame(3, $this->countType($board, $t, 'promocode'));
        $this->assertSame(2, $this->countType($board, $t, 'coins'));
        $this->assertSame(1, $this->countType($board, $t, null));
        // Kitoblar qarama-qarshi burchaklarda
        $this->assertSame('fragments', $t[$board[0]]);
        $this->assertSame('fragments', $t[$board[4]]);
        // Takror yo'q
        $ids = array_filter($board, fn ($v) => $v !== null);
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_collecting_book_always_shown_others_rotate_by_day(): void
    {
        $p = $this->prizes(['fragments' => 6, 'coins' => 2]);
        $seen = [];
        foreach (range(1, 20) as $day) {
            $board = PrizeBoard::build($p, [5 => 0.5], "7|2026-10-$day", $day);
            $this->assertContains(5, $board, 'Yig\'ilayotgan kitob doim ko\'rinishi kerak');
            foreach ($board as $id) {
                $seen[$id] = true;
            }
        }
        // 20 kun ichida qolgan 5 ta kitobning hammasi navbat bilan chiqadi
        foreach ([1, 2, 3, 4, 6] as $id) {
            $this->assertTrue(isset($seen[$id]), "Kitob #$id hech chiqmadi");
        }
    }

    public function test_same_seed_same_board(): void
    {
        $p = $this->prizes(['fragments' => 4, 'promocode' => 4, 'coins' => 3]);
        $this->assertSame(PrizeBoard::build($p, [], 'a'), PrizeBoard::build($p, [], 'a'));
    }

    public function test_few_prizes_do_not_repeat(): void
    {
        $p = $this->prizes(['fragments' => 1, 'promocode' => 1, 'coins' => 1]);
        $board = PrizeBoard::build($p, [], 'x');
        $this->assertCount(8, $board);
        $this->assertSame(5, $this->countType($board, $this->types($p), null));
        $ids = array_values(array_filter($board, fn ($v) => $v !== null));
        sort($ids);
        $this->assertSame([1, 2, 3], $ids, 'Har sovg\'a bir marta');
    }

    public function test_single_prize_once_rest_nothing(): void
    {
        $p = $this->prizes(['coins' => 1]);
        $board = PrizeBoard::build($p, [], 'x');
        $this->assertSame(1, $this->countType($board, $this->types($p), 'coins'));
        $this->assertSame(7, $this->countType($board, $this->types($p), null));
    }

    public function test_surprise_box_takes_one_cell(): void
    {
        $p = $this->prizes(['fragments' => 2, 'promocode' => 3, 'coins' => 2, 'surprise' => 2]);
        $board = PrizeBoard::build($p, [], 'x');
        $t = $this->types($p);
        $this->assertSame(1, $this->countType($board, $t, 'surprise'));
        $this->assertSame(1, $this->countType($board, $t, null), 'Kamida bitta "Keyingi safar"');
    }

    public function test_mixed_board_has_no_same_type_neighbours(): void
    {
        foreach (['a', 'b', 'c', 'd', 'e'] as $seed) {
            $p = $this->prizes(['fragments' => 3, 'promocode' => 3, 'coins' => 2]);
            $this->assertNoSameNeighbours(PrizeBoard::build($p, [], $seed), $this->types($p));
        }
    }

    public function test_place_on_existing_cell(): void
    {
        $p = $this->prizes(['fragments' => 2, 'promocode' => 3, 'coins' => 2]);
        $board = PrizeBoard::build($p, [], 's');
        $r = PrizeBoard::place($board, 4, 'promocode', $this->types($p), 's');
        $this->assertSame($board, $r['board']);
        $this->assertSame(4, $board[$r['cell']]);

        $r = PrizeBoard::place($board, null, null, $this->types($p), 's');
        $this->assertNull($board[$r['cell']]);
    }

    public function test_place_swaps_hidden_book_into_book_cell(): void
    {
        $p = $this->prizes(['fragments' => 5, 'coins' => 2]);
        $t = $this->types($p);
        $board = PrizeBoard::build($p, [], 's');
        $hidden = current(array_diff([1, 2, 3, 4, 5], $board));
        $r = PrizeBoard::place($board, $hidden, 'fragments', $t, 's');

        $this->assertSame($hidden, $r['board'][$r['cell']]);
        $this->assertSame('fragments', $t[$board[$r['cell']]], 'Kitob katagi almashishi kerak');
        $this->assertSame(1, count(array_diff_assoc($r['board'], $board)));
    }

    public function test_place_nothing_when_board_has_no_nothing_cell(): void
    {
        $board = [1, 2, 1, 2, 1, 2, 1, 2];
        $r = PrizeBoard::place($board, null, null, [1 => 'coins', 2 => 'promocode'], 's');
        $this->assertNull($r['board'][$r['cell']]);
    }

    private function assertNoSameNeighbours(array $board, array $types): void
    {
        $kind = fn ($id) => $id === null ? 'nothing' : $types[$id];
        for ($i = 0; $i < 8; $i++) {
            $this->assertNotSame($kind($board[$i]), $kind($board[($i + 1) % 8]), 'Yonma-yon: '.json_encode($board));
        }
    }
}
