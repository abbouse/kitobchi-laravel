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
- Kitob qo'shish: ISBN skan → karta topilsa faqat narx/qoldiq; topilmasa old+orqa muqova bilan ariza.
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
