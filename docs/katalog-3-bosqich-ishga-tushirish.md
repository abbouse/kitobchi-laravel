# Global katalog — 3-bosqich: eski tizimdan voz kechish

> Kitobning O'ZINIKI bo'lgan ma'lumotlari (nom, muallif, tarjimon, ISBN,
> nashriyot, bo'lim, til, yozuv, muqova, yil, sahifa, tavsif, rasmlar) faqat
> **katalog kartasida** (`book_editions`) turadi. `books` jadvalidagi qator endi
> **do'kon taklifi**: narx, chegirma, qoldiq, holat va moderatsiya.
> Kartaga ulangan taklifda (`books.edition_id` to'la) kitob maydonlarini
> **hech kim** o'zgartira olmaydi — do'kon ham, admin ham, eski ilova ham,
> import ham. Yagona yozuvchi: `CatalogService::syncOffers()`.

## 1. Deploy (tartib muhim)

```bash
php artisan down --render="errors::503"

git pull
composer install --no-dev -o
npm ci && npm run build

# YANGI: book_edition_submissions.type (new_book | correction)
php artisan migrate --force

php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart

php artisan up
```

Migratsiya bitta `ALTER TABLE` — jadval kichik (1 kun oldin yaratilgan), to'xtash sezilmaydi.
Mavjud arizalar avtomatik `new_book` qiymatini oladi.

## 2. Deploydan keyin (bir marta)

```bash
# Takliflardagi kitob ma'lumoti karta bilan bir xilmi — avval tekshiruv
php artisan catalog:sync-offers --dry-run

# Farq bo'lsa — tenglashtirish (faqat farqli qatorlar yoziladi)
php artisan catalog:sync-offers

# Buy box xavfsizlik to'ri
php artisan catalog:buybox
```

`catalog:sync-offers` endi har kecha soat 04:20 da o'zi ishlaydi (scheduler).

## 3. Nima o'zgardi

**Do'kon ilovasi (kitobchibusiness)**
- Kitob qo'shish: ISBN skan → karta topilsa faqat narx/qoldiq.
- **Do'kon endi kitob ma'lumotini umuman kiritmaydi.** To'liq forma (nom,
  muallif, tavsif, kategoriya…) olib tashlandi. Katalogda yo'q kitob uchun
  do'kon faqat **qisqa so'rov** yuboradi: ISBN + old/orqa muqova rasmi.
  Kartani admin ochadi (Boshqaruv → Katalog → Arizalar → "Karta ochish"),
  so'ng do'kon "So'rovlarim" ro'yxatidan narx va qoldiqni kiritadi.
- "ISBN'siz kitob qo'shish" yo'li butunlay olib tashlandi — ISBN majburiy.
- Eski ilova versiyalari to'liq forma yuborsa ham ishlashda davom etadi
  (server ikkala shaklni ham qabul qiladi).
- Kitobni tahrirlash: kartaga ulangan bo'lsa forma faqat **narx, chegirma, qoldiq** ko'rsatadi; kitob ma'lumoti o'qish uchun.
- Yangi: **"Tuzatish taklif qilish"** — `POST catalog/editions/{id}/correction`
  (`field`, `message`, `suggested`, 3 tagacha rasm). Bir kartaga bir do'kondan
  bitta ochiq ariza (409 `correction_pending`).
- Eski ilovalar `products/update` ga to'liq forma yuborsa ham, ulangan taklifda
  faqat narx/chegirma/qoldiq qabul qilinadi; javobda `catalog_locked: true`.

**Boshqaruv**
- Katalog → **Arizalar** endi ikki navbat: *Yangi kitob* va *Tuzatish takliflari*.
  Tuzatishni tasdiqlash kartani tahrirlamaydi — admin kartani o'zi to'g'rilab,
  keyin arizani yopadi. Rad etilganda dalil rasmlari diskdan o'chiriladi.
- Kartani tahrirlash **har doim** barcha takliflarga ko'chadi (avvalgi
  "Takliflarga ko'chirish" belgisi olib tashlandi).
- Kitoblar → ulangan taklifni tahrirlashda kitob maydonlari ko'rsatilmaydi,
  o'rniga kartaga havola. Narx/qoldiq/moderatsiya odatdagidek.
- Muallif nomini o'zgartirish yoki o'chirish: avval karta, keyin ulangan
  takliflar yangilanadi. **Ulangan takliflar qayta moderatsiyaga
  yuborilmaydi** (aks holda bitta tahrir minglab kitobni sotuvdan chiqarardi).

## 3.5. Bir xil ISBN — boshqa nashr

ISBN standarti bo'yicha qattiq/yumshoq muqova, boshqa til yoki tarjima alohida
ISBN olishi shart. O'zbekiston/MDH amaliyotida nashriyotlar ISBN'ni qayta
ishlatadi, shuning uchun bitta ISBN ostida **fizik jihatdan boshqa kitob**
chiqishi mumkin. Ular bitta kartaga qo'shilmaydi:

- Taklif kartaga ulanishi uchun **nom o'xshash VA muqova/til/yozuv mos** bo'lishi
  kerak. Kartada yoki taklifda qiymat bo'sh bo'lsa — mos deb hisoblanadi
  (eski ma'lumotning katta qismi to'ldirilmagan, ularni ajratib yuborsak
  katalog bo'linib ketardi).
- **Yil va sahifa bo'yicha AJRATILMAYDI** — qayta nashrda yil o'zgaradi, ISBN
  qoladi; yil bo'yicha ajratsak katalog portlardi.
- Do'kon ilovasida taklif oynasida "Sizdagi kitobning muqovasi" tanlanadi.
  Kartanikidan farq qilsa — "Boshqa nashr sifatida qo'shish" (ariza oqimi,
  old/orqa muqova rasmi bilan). Server ham tekshiradi: `409 variant_mismatch`.
- Mijoz kitob sahifasida **"Boshqa nashrlari"** qatori chiqadi (bir xil ISBN,
  boshqa muqova/til) — Amazon'dagi format almashtirgichga o'xshash.
- Boshqaruvda kartalarda "Qattiq muqova · O'zbek · Lotin" yorlig'i ko'rinadi;
  boshqa nashrli kartalarni birlashtirish uchun "Majburiy birlashtirish"
  belgilanishi kerak.
- `catalog:backfill` hisobotida yangi ko'rsatkich: **other_printings** — bir xil
  ISBN, bir xil nom, lekin boshqa muqova/til (normal holat). `isbn_conflicts` —
  bir xil ISBN, **boshqa nom** (admin tekshirsin).
- "Boshqa nashrlari" ro'yxatiga faqat nomi ham o'xshash kartalar tushadi —
  ISBN butunlay boshqa kitobga qayta ishlatilgan bo'lsa, mijozga ko'rsatilmaydi.

**Muqova/til qiymatlari qanday tanib olinadi.** Bazada ular erkin matn:
"Yumshoq", "soft", "Мягкая", "Твёрдый", "Қаттиқ", "Paperback"… Ularning barchasi
bitta `CatalogService::canonCover/canonLang/canonScript` orqali `hard|soft`,
`uz|ru|en|qq`, `latin|cyrillic` ga keltiriladi. **Tanib bo'lmasa — `null`**,
ya'ni "noma'lum" va hech nima bilan ziddiyatga kirmaydi (katalog bo'linmaydi).
Ilovaga chiqadigan qiymat ham aynan shu funksiyalardan olinadi — aks holda
karta o'ziga o'zi mos kelmay, do'kon 409 olardi (regressiya testi:
`test_card_value_round_trips_without_false_mismatch`).

**Sinxron kartadagi bo'sh maydonni taklifga yozmaydi.** Kartada muqova yoki
sahifa yo'q bo'lsa, `catalog:sync-offers` ularni umuman tegmaydi — ilgari
"Yumshoq"/"0 bet" kabi standart qiymat do'konning haqiqiy ma'lumotini bosib
yozib ketardi (va do'kon uni tuzata olmasdi).

## 4. Qulf qanday ishlaydi (texnik)

| Yo'l | Himoya |
|---|---|
| `$book->update([...])`, `save()`, `updateQuietly()`, `forceFill()` | `Books::performUpdate()` → `discardCatalogManagedChanges()` (qiymat qaytariladi + log) |
| `Books::create([...])` `edition_id` bilan | `Books::performInsert()` → ma'lumot kartadan to'ldiriladi |
| `Books::query()->where(...)->update([...])` | `BooksBuilder::update()` → kitob maydonlari faqat `edition_id IS NULL` qatorlarga; qolgan ustunlar hammaga |
| `Books::query()->upsert(...)` | `BooksBuilder::upsert()` → kitob ustunlari `update` ro'yxatidan kesiladi |
| `CatalogService::syncOffers()` | `Books::writingFromCatalog()` — YAGONA ruxsat etilgan yozuv |

Log'da `Katalog kitob maydoni himoyalandi` yoki `... ommaviy yangilanishdan
himoyalandi` qatori chiqsa — kimdir eski yo'l bilan yozmoqchi bo'lgan; tekshiring.

## 5. Orqaga qaytarish

- Mijoz tomonini eski holatga qaytarish (kod yozmasdan): `.env` da
  `CATALOG_DEDUPE=false` → `php artisan config:cache`. Har taklif yana alohida chiqadi.
- Avto-ulashni to'xtatish: `CATALOG_AUTO_LINK=false`.
- Qulfning o'zi kod darajasida — o'chirish kerak bo'lsa `Books::CATALOG_MANAGED`
  bo'shatiladi (tavsiya etilmaydi).

## 6. Tekshiruv ro'yxati (deploydan keyin 10 daqiqa)

- [ ] Do'kon ilovasida ISBN skan → mavjud kitob → narx/qoldiq bilan qo'shiladi.
- [ ] O'sha kitobni tahrirlash — kitob maydonlari o'qish uchun, saqlash ishlaydi.
- [ ] "Tuzatish taklif qilish" → Boshqaruv → Katalog → Arizalar → Tuzatish takliflari da ko'rinadi.
- [ ] Boshqaruvda kartani tahrirlash → barcha takliflarda nom yangilandi.
- [ ] Mijoz ilovasida bitta kitob bitta karta bo'lib chiqadi, ichida do'kon takliflari.
- [ ] `php artisan catalog:sync-offers --dry-run` → "farqli kartalar: 0".
- [ ] Qattiq muqovali kitobni yumshoq muqovali karta ustiga qo'shib ko'ring —
      "Boshqa nashr sifatida qo'shish" chiqishi kerak.
- [ ] Mijoz ilovasida shu kitob sahifasida "Boshqa nashrlari" qatori ko'rinadi.
