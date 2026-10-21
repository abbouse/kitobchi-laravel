<?php

namespace App\Support\Contracts;

/**
 * Yuklab olinadigan shartnoma shablonlari (resources/contracts/*.docx) va ularning maydonlari.
 *
 * Maydon guruhlari:
 *  - company — barcha shartnomalar uchun umumiy Kitobchi rekvizitlari (boshqaruvda bir marta kiritiladi);
 *  - terms   — shu shartnomaning kompaniya belgilaydigan shartlari (summa, foiz, muddatlar);
 *  - party   — kontragent ma'lumotlari: saytdan yuklanganda bo'sh chiziq qoladi, boshqaruvda
 *              aniq shaxs uchun hujjat yaratilganda to'ldirilishi mumkin (bazada saqlanmaydi).
 * Sana va jadval sanalari yuklab olingan (yoki boshqaruvda tanlangan) kundan avtomatik hisoblanadi.
 */
final class ContractTemplates
{
    public const COMPANY_SCOPE = 'company';

    /** @return array<string, array{label:string, default:string, hint?:string}> */
    public static function companyFields(): array
    {
        return [
            'kompaniya_nomi' => ['label' => "Kompaniya nomi (o'zbekcha)", 'default' => 'YaTT «TURDALIYEV ABBOS ERKIN O‘G‘LI»'],
            'kompaniya_nomi_ru' => ['label' => 'Kompaniya nomi (ruscha)', 'default' => 'ИП «TURDALIYEV ABBOS ERKIN O‘G‘LI»'],
            'kompaniya_rahbari' => ['label' => 'Tadbirkor / rahbar F.I.Sh. (lotin)', 'default' => 'Turdaliyev Abbos Erkin o‘g‘li'],
            'kompaniya_rahbari_qisqa' => ['label' => 'Rahbar qisqa (lotin)', 'default' => 'Turdaliyev A. E.'],
            'kompaniya_rahbari_kiril' => ['label' => 'Rahbar F.I.Sh. (kirill)', 'default' => 'Турдалиев Аббос Эркин ўғли'],
            'kompaniya_rahbari_ru' => ['label' => 'Rahbar F.I.O. (ruscha)', 'default' => 'Турдалиев Аббос Эркин угли'],
            'kompaniya_rahbari_qisqa_ru' => ['label' => 'Rahbar qisqa (ruscha)', 'default' => 'Турдалиев А. Э.'],
            'kompaniya_stir' => ['label' => 'STIR (INN)', 'default' => '51503025990027'],
            'kompaniya_manzil' => ['label' => 'Yuridik manzil', 'default' => 'Namangan vil., Mingbuloq, Navoiy 36'],
            'kompaniya_hisob' => ['label' => 'Hisob raqami (h/r)', 'default' => '20218000407192649001'],
            'kompaniya_bank' => ['label' => 'Bank', 'default' => '«Trastbank» XAB bosh ofisi, Toshkent sh.'],
            'kompaniya_mfo' => ['label' => 'MFO', 'default' => '00491'],
            'kompaniya_telefon' => ['label' => 'Telefon', 'default' => '+998 (50) 030-30-33'],
            'kompaniya_email' => ['label' => 'E-mail', 'default' => 'toordaliev@gmail.com'],
            'kompaniya_karta' => ['label' => "To'lov kartasi raqami", 'default' => '5614 6815 1954 3114'],
            'kompaniya_sayt' => ['label' => 'Sayt', 'default' => 'kitobchi.com'],
            'shahar' => ['label' => "Shartnoma tuziladigan shahar (lotin)", 'default' => 'Toshkent'],
            'shahar_kiril' => ['label' => 'Shahar (kirill)', 'default' => 'Тошкент'],
            'shahar_ru' => ['label' => 'Shahar (ruscha)', 'default' => 'Ташкент'],
        ];
    }

    /**
     * @return array<string, array{title:string, title_ru:string, description:string, file:string, filename:string,
     *   audience:string, terms: array<string, array>, party: array<string, array>}>
     */
    public static function all(): array
    {
        $b = fn (int $n) => str_repeat('_', $n);

        return [
            'dokon-shartnomasi' => [
                'title' => "Do'konlar bilan komissiya shartnomasi",
                'title_ru' => 'Договор комиссии с продавцами',
                'description' => "Kitob va kanselyariya do'konlari hamda o'zini o'zi band qilgan sotuvchilar uchun — Kitobchi platformasi orqali tovarlarni sotish (rus/o'zbek tillarida).",
                'file' => 'dokon-shartnomasi.docx',
                'filename' => 'Kitobchi_dokon_shartnomasi',
                'audience' => 'partner',
                'terms' => [
                    'komissiya_foiz' => ['label' => 'Komissiya, %', 'default' => $b(6), 'hint' => 'Masalan: 15'],
                    'tolov_muddati_kun' => ['label' => "Sotuvchiga to'lov muddati (bank kuni)", 'default' => '3–7'],
                    'tayyorlash_soat' => ['label' => 'Buyurtmani kuryerga topshirish muddati (soat)', 'default' => '24'],
                    'bekor_kun' => ['label' => 'Bekor qilish haqida ogohlantirish (kun)', 'default' => '15'],
                ],
                'party' => [
                    'shartnoma_raqami' => ['label' => 'Shartnoma raqami', 'default' => $b(8)],
                    'sotuvchi_nomi' => ['label' => "Sotuvchi nomi (tashkilot/YaTT)", 'default' => $b(36)],
                    'sotuvchi_rahbari' => ['label' => 'Rahbar F.I.Sh.', 'default' => $b(28)],
                    'sotuvchi_asos' => ['label' => 'Faoliyat asosi (Ustav, guvohnoma...)', 'default' => $b(18)],
                    'sotuvchi_manzil' => ['label' => 'Manzil', 'default' => $b(36)],
                    'sotuvchi_hisob' => ['label' => 'Hisob raqami', 'default' => $b(24)],
                    'sotuvchi_bank' => ['label' => 'Bank', 'default' => $b(28)],
                    'sotuvchi_mfo' => ['label' => 'MFO', 'default' => $b(10)],
                    'sotuvchi_stir' => ['label' => 'STIR', 'default' => $b(14)],
                    'sotuvchi_telefon' => ['label' => 'Telefon', 'default' => $b(18)],
                    'sotuvchi_email' => ['label' => 'E-mail', 'default' => $b(22)],
                    'sotuvchi_fish' => ['label' => "O'zini o'zi band qilgan: F.I.Sh.", 'default' => $b(30)],
                    'sotuvchi_pasport' => ['label' => 'Pasport seriya va raqami', 'default' => $b(16)],
                    'sotuvchi_pasport_kim' => ['label' => 'Pasport kim tomonidan berilgan', 'default' => $b(26)],
                    'sotuvchi_jshshir' => ['label' => 'JSHSHIR (PINFL)', 'default' => $b(18)],
                ],
            ],
            'skuter-ijarasi' => [
                'title' => 'Skuter ijarasi shartnomasi',
                'title_ru' => 'Договор аренды скутера',
                'description' => "Kitobchi kuryerlariga mototransport (skuter) ijaraga berish — haftalik to'lov jadvali shartnoma sanasidan avtomatik tuziladi.",
                'file' => 'skuter-ijarasi.docx',
                'filename' => 'Kitobchi_skuter_ijarasi_shartnomasi',
                'audience' => 'courier',
                'terms' => [
                    'haftalik_tolov' => ['label' => "Haftalik ijara haqi (so'm)", 'default' => $b(20), 'hint' => 'Masalan: 350 000'],
                    'haftalik_tolov_sozda' => ['label' => "Haftalik ijara haqi so'z bilan", 'default' => $b(34), 'hint' => "uch yuz ellik ming"],
                    'garov' => ['label' => "Garov puli (so'm)", 'default' => $b(20)],
                    'garov_sozda' => ['label' => "Garov puli so'z bilan", 'default' => $b(28)],
                    'skuter_qiymati' => ['label' => "Skuterning bozor qiymati (so'm)", 'default' => $b(20)],
                    'penya_foiz' => ['label' => 'Penya, kuniga %', 'default' => $b(4)],
                    'penya_max_foiz' => ['label' => 'Penyaning eng ko‘p miqdori, %', 'default' => $b(4)],
                    'garov_qaytarish_kun' => ['label' => 'Garovni qaytarish (ish kuni)', 'default' => $b(4)],
                    'xabar_kun' => ['label' => 'Uzaytirmaslik haqida xabar (kun)', 'default' => $b(4)],
                    'uzaytirish_oy' => ['label' => "Avtomatik uzaytirish (oy)", 'default' => $b(4)],
                    'qaytarish_muddati' => ['label' => 'Shartnoma tugagach qaytarish muddati', 'default' => $b(4).' soat/kun', 'hint' => '24 soat'],
                    'forsmajor_kun' => ['label' => 'Fors-major haqida xabar (kun)', 'default' => $b(4)],
                    'tolamaslik_hafta' => ['label' => "Ketma-ket to'lamaslik (hafta) — bekor qilish asosi", 'default' => $b(4)],
                    'ogohlantirish_kun' => ['label' => 'Bir tomonlama bekor qilish ogohlantirishi (kun)', 'default' => $b(4)],
                    'topshirish_soat' => ['label' => 'Bekor qilinganda topshirish (soat)', 'default' => $b(4)],
                    'qoshimcha_majburiyat' => ['label' => "Ijaraga beruvchining qo'shimcha majburiyati", 'default' => "Skuterni qonunchilik talab qilgan hollarda davlat ro‘yxatidan o‘tkazish va majburiy sug‘urta bilan ta’minlash"],
                ],
                'party' => [
                    'shartnoma_raqami' => ['label' => 'Shartnoma raqami', 'default' => $b(8)],
                    'beruvchi_vakili' => ['label' => 'Skuterni topshiruvchi vakil', 'default' => $b(30)],
                    'ijarachi_fish' => ['label' => 'Ijarachi F.I.Sh.', 'default' => $b(32)],
                    'ijarachi_pasport' => ['label' => 'Pasport seriya va raqami', 'default' => '______ №__________'],
                    'ijarachi_pasport_kim' => ['label' => 'Pasport kim tomonidan berilgan', 'default' => $b(22)],
                    'ijarachi_pasport_sana' => ['label' => 'Pasport berilgan sana', 'default' => '«___»_________20__ yil'],
                    'ijarachi_jshshir' => ['label' => 'JSHSHIR (PINFL)', 'default' => $b(22)],
                    'ijarachi_manzil' => ['label' => 'Doimiy yashash manzili', 'default' => $b(40)],
                    'ijarachi_telefon' => ['label' => 'Telefon', 'default' => '+998 (__) ___-__-__'],
                    'skuter_modeli' => ['label' => 'Skuter turi / modeli', 'default' => $b(27)],
                    'skuter_vin' => ['label' => 'Zavod raqami (VIN / rama)', 'default' => $b(27)],
                    'skuter_quvvati' => ['label' => 'Dvigatel quvvati', 'default' => '______ kVt   /   ______ sm³'],
                    'skuter_tezligi' => ['label' => 'Maksimal tezligi', 'default' => '______ km/soat'],
                    'skuter_davlat_raqami' => ['label' => "Davlat raqami", 'default' => $b(27)],
                    'skuter_rangi' => ['label' => 'Rangi', 'default' => $b(27)],
                    'skuter_yili' => ['label' => 'Ishlab chiqarilgan yili', 'default' => $b(27)],
                    'skuter_kilometraji' => ['label' => 'Kilometraji', 'default' => '______ km'],
                ],
            ],
        ];
    }

    public static function get(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    public static function path(string $slug): string
    {
        return resource_path('contracts/'.self::all()[$slug]['file']);
    }
}
