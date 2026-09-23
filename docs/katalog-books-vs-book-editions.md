# `books` va `book_editions`: nega ikkalasi kerak, qaysi ustunlar ortiqcha

Savol: *"bizga books table endi kerak emasmi? ikkita bir xil table kerak emas."*

Qisqa javob: **ikkita jadval bir xil emas.** `book_editions` — kitobning o'zi
(karta). `books` — do'konning shu kitob bo'yicha **taklifi**: narx, chegirma,
qoldiq, artikul, filial zaxirasi, moderatsiya holati, sotuv statistikasi. Bitta
kartaga 20 ta do'kon taklifi bog'lanadi. Ya'ni `books` jadvalining o'zi ortiqcha
emas.

Ortiqchasi — `books` ichidagi **14 ta ustun** kartadagi ma'lumotni takrorlashi:
`name, author, author_id, translator, isbn, publisher_id, category_id, lang,
langType, coverType, year, pages, description, images`.

Quyida shu 14 ta ustunni bugun o'chirib bo'lmasligining aniq sabablari va
o'chirishga olib boradigan bosqichli yo'l yozilgan.

---

## 1. Bugun o'chirib bo'lmaydigan sabablar

### 1.1 FULLTEXT qidiruv faqat `books` ustida
`books_fulltext_search(name, author, description)` indeksi migratsiyalarda emas,
`database/schema/kitobchi_structure.sql:245` da yaratilgan.
`app/Http/Controllers/Api/SearchController.php:1082, 1739, 1743, 1748, 1752` —
`MATCH(name, author, description) AGAINST(...)`.

`book_editions` da bunga teng keladigan FULLTEXT indeks **yo'q**. Ustunlar
o'chirilsa qidiruv xato bermaydi — `SearchController.php:1070` indeks bor-yo'qligini
runtime'da tekshiradi va jimgina `LIKE '%...%'` ga tushadi. Ya'ni ishlaydi, lekin
butun jadvalni skanerlab. Bu eng xavflisi: xatolik ko'rinmaydi, faqat sekinlashadi.

### 1.2 Kompozit indekslar buziladi
MySQL indeksni ikkita jadval bo'ylab qura olmaydi. O'chirilsa quyidagilar yo'qoladi:
`books_category_idx`, `books_category_sales_idx`, `books_isbn_index`,
`books_author_id_index`, `books_publisher_id_index`, `books_fulltext_search`.
`book_editions` da `title`, `lang`, `langType`, `coverType` uchun indeks yo'q.

### 1.3 Kartaga ulanmagan takliflar hali ham bor
`edition_id IS NULL` — hujjatdagi kamchilik emas, **ishlaydigan holat**:

1. `config('catalog.auto_link')` o'chirilgan bo'lsa — `CatalogOfferObserver.php:42`
   darhol qaytadi.
2. `linkOffer` xato bersa — `CatalogOfferObserver.php:48-52` xatoni yutadi va log
   yozadi, qator ulanmagan holda qoladi.
3. `catalog:backfill` da qator xato bersa — `CatalogBackfill.php:218-224` uni
   `$failedIds` ga qo'shib, keyingi chunklardan butunlay chiqarib tashlaydi.
4. Backfill hali to'liq yurgizilmagan bo'lsa.
5. Admin qo'lda uzsa — `CatalogOfferObserver::updated:58-62` buni qo'llab-quvvatlaydi.

Bunday qatorlar uchun `books` — ma'lumotning **yagona nusxasi**. Ustunlarni
o'chirish ularni tiklab bo'lmaydigan qilib yo'q qiladi: `ProductPayloadFormatter`
`name: null`, `images: []` qaytaradi.

Admin panelda soni ko'rinadi: `Boshqaruv/CatalogController.php:84` —
`Books::whereNull('edition_id')->count()`.

### 1.4 Hamkor API jimgina siniydi
`Api/Client/SellerApiController.php` — `books.isbn` va `books.name` ni o'qiydi.
`GET products/mine` `name: null, code: null` ni `status: "success"` bilan
qaytaradi; hamkor integratsiyasi xato signalisiz buziladi.

### 1.5 Yumshoq o'chirilgan kartalar
`BookEdition` da `SoftDeletes` bor, `Books::edition()` esa oddiy `belongsTo` —
arxivlangan takliflarning kartasi o'chirilgan bo'lsa, buyurtma tarixi bo'sh
ko'rinadi. Hozir zarar yo'q, chunki ma'lumot `books` da turibdi.

### 1.6 Qulf tizimi shu takrorlanish uchun yozilgan
`Books::CATALOG_MANAGED`, `writingFromCatalog`, `performUpdate`/`performInsert`,
`BooksBuilder`, `CatalogService::offerAttributes`/`syncOffers` — hammasi shu 14 ta
ustunni qo'riqlaydi. Ustunlar ketsa, bu qism ham keraksiz bo'ladi. Bu — o'chirish
foydasiga eng kuchli dalil, lekin faqat yuqoridagi 5 ta shart bajarilgandan keyin.

---

## 2. O'chirishga olib boradigan bosqichli yo'l

1. **Karta-birinchi yozish.** Taklif yaratadigan 3 ta yo'l kartasiz qator
   yaratmasin: `Api/Seller/ProductController::createProduct` (**yopildi**),
   `A122/BookController.php:167`, `CatalogParsers/BookUzParserService.php:214`.
   `CatalogOfferObserver` xatoni yutmasin — tranzaksiya qaytsin.
2. **Gate.** `SELECT COUNT(*) FROM books WHERE edition_id IS NULL` = 0 bo'lguncha
   migratsiya ishga tushmasin (migratsiyaning o'zida tekshiruv).
3. **Indekslar.** `book_editions` ga `FULLTEXT(title, author, description)` va
   `title`, `coverType`, `langType` indekslarini qo'shish.
4. **O'qish joylarini ko'chirish.** `scopeWhereIsbn`, `toSearchableArray`,
   `ProductPayloadFormatter`, `SearchController` filtrlari va fasetlari,
   `Web/ProductCatalogController`, `AdminController` Inertia payload'i,
   `Seller/ProductController::lastProducts` (hozir xom modelni qaytaradi),
   `google-merchant.blade.php`, `sitemap.blade.php`, `share/redirect.blade.php`.
5. **Scout.** `makeAllSearchableUsing` ga `->with('edition')` qo'shib, to'liq
   `scout:import`.
6. **Shundan keyin** ustunlarni o'chirish migratsiyasi + qulf tizimini olib tashlash.

Bu 1–2 kunlik alohida ish. Buyruq berilsa, 1–3-bosqichdan boshlanadi.

---

## 3. Hozir o'chirilgani

`2026_09_23_140000_drop_unused_book_offer_columns.php` — `books.condition`.

Biz faqat yangi kitob sotamiz. Ustun hech qayerda mantiqqa ta'sir qilmasdi:
savat, buyurtma, narx, qidiruv, filtr, fiskalizatsiya va hamkor API uni umuman
o'qimasdi (`google-merchant.blade.php:23,54` esa `new` ni qattiq yozib qo'ygan).
Faqat taklif qo'shish formasi, admin ro'yxati va ko'rsatish uchun ishlatilardi.

Ta'siri: takroriy taklif kaliti `(do'kon, karta, holat)` dan `(do'kon, karta)` ga
qisqardi — bitta do'kon bitta kartaga endi bitta taklif qo'yadi.
`ProductPayloadFormatter` API shartnomasi buzilmasligi uchun `condition` maydonini
doimiy `'new'` qilib qaytaradi.

## 4. Yozilib, hech qayerda o'qilmaydigan ustunlar (hozircha tegilmadi)

| ustun | yoziladi | o'qiladi |
|---|---|---|
| `totalClientsWeek` | `OrderService.php:135` | hech qayerda |
| `totalRevenueWeek` | `OrderService.php:126`, `SoldObserver.php:193` | faqat o'z-o'zini `max(0, ...)` qilish uchun |
| `archived_by` | `Boshqaruv/CatalogController.php:600,627` | hech qayerda (audit uchun qoldirildi) |
| `ai_moderation_content_hash` | `ProductAiModerationService.php:623,655` | solishtirish xotirada bo'ladi, DB'dan o'qilmaydi |

Birinchi ikkitasi `stationeries` va `gifts` jadvallarida ham bor — o'chirish
`OrderService` va `SoldObserver` ni uchala mahsulot turi bo'yicha o'zgartirishni
talab qiladi. Bu katalog ishiga aloqasi yo'q, shuning uchun alohida qoldirildi.
