# Kitob vektorlari kartada + kitob darajasidagi sotuv — deploy runbook

## Nima o'zgardi

**Vektorlar.** Kitob embeddingi endi do'kon taklifida (`books.vectorData`) emas, kitob kartasida — `book_edition_vectors` jadvalida (1 karta = 1 vektor). Vektor faqat kitob MAZMUNIDAN yasaladi: nom, muallif, kategoriya, teglar, til, yil, muqova, nashriyot, tavsif. Narx, sotuv soni va do'kon nomi vektorga kirmaydi — ular SQL'da saralash/filtr sifatida qo'llanadi.

- Qayta embed faqat kartaning matni o'zgarganda (`BookEdition::VECTOR_FIELDS`), matn hash'i bir xil bo'lsa OpenAI'ga borilmaydi. Narx/qoldiq/sotuv o'zgarishi, yangi do'kon qo'shilishi — hech qanday OpenAI chaqiruvi yo'q.
- Ommaviy yangilanishlar (muallif, nashriyot, kategoriya nomi) `BookEditionVector::markStale()` bilan belgilanadi, `vectors:rebuild` ularni qayta yasaydi.
- Semantik qidiruv indeksi karta id bo'yicha (`vector-search-index:book:v2`). Bozor qidiruvida kartaning tanlangan (buy box) taklifi, do'kon sahifasida esa o'sha do'konning o'z taklifi chiqadi.
- `books.vectorData`, `books.vector_text_hash`, `books.has_vector` ustunlari olib tashlandi. Kanselyariya o'zgarmadi (o'z jadvalida qoladi).

**Sotuv statistikasi.**

- `totalSalesWeek` ilgari hech qachon nolga qaytmasdi (aslida "butun davr" edi). Yangi `products:sales-stats` buyrug'i uni har soatda buyurtmalardan oxirgi 7 kun bo'yicha qayta hisoblaydi (kitob va kanselyariya).
- Kartaga `book_editions.sales_week` va `sales_total` qo'shildi — kitobning BARCHA do'konlardagi sotuvi. BuyBox har qayta hisoblashda, buyruq esa har soatda yangilaydi.
- Bozor ro'yxatlari (trend, top, tavsiyalar, o'xshashlar, qidiruv "ommabop" tartibi, chatbot, video top kitoblar, Reading Intelligence reytingi) `Books::orderByBookSales()` orqali karta soni bo'yicha saralaydi. Do'kon sahifasi va do'kon hisobotlari o'z taklifi sonini ishlatadi — `books` jadvalidagi har do'kon statistikasi (`totalSales`, `totalSalesWeek`, `totalRevenue*`, `totalClients*`, `views`) joyida qoladi.

## Deploy tartibi

1. Kodni yangilang, keyin:
   ```bash
   php artisan migrate --force
   php artisan queue:restart
   ```
   Migratsiya mavjud vektorlarni kartaga ko'chiradi (har kartadan tanlangan taklifniki) va `sales_total` ni boshlang'ich to'ldiradi. Ko'chirilgan vektorlar `text_hash = NULL` bilan keladi — ya'ni ular ishlayveradi, lekin rejalashtirilgan `vectors:rebuild` ularni asta-sekin yangi (faqat mazmun) matn bilan qayta yasaydi (har 10 daqiqada 120 ta).
2. Haftalik sonni darhol to'g'rilash:
   ```bash
   php artisan products:sales-stats
   ```
   Keyin u har soat :17 da o'zi ishlaydi.
3. Ixtiyoriy — qayta embedni tezlashtirish: `php artisan vectors:rebuild --type=book --limit=0` (cheklovsiz; OpenAI xarajati = kartalar soni × bitta embedding).

## Tekshirish

- `SELECT COUNT(*) FROM book_edition_vectors;` — kartalar soniga yaqin bo'lishi kerak.
- `SELECT COUNT(*) FROM book_edition_vectors WHERE text_hash IS NULL;` — vaqt o'tishi bilan 0 ga tushadi.
- Ilovada semantik qidiruv va "o'xshash kitoblar" ishlashini, trend ro'yxatida bir kitob bir marta chiqishini tekshiring.

## Orqaga qaytarish

`php artisan migrate:rollback --step=2` — ustunlar qaytadi va vektorlar kartadan har bir taklifga qayta yoziladi.

## Testlar

`tests/Feature/Catalog/EditionVectorAndSalesTest.php` (MySQL kerak): bitta kartaga bitta vektor, taklif o'zgarishi qayta embed qilmasligi, markStale + rebuild, qidiruvda tanlangan/do'kon taklifi, karta soni bo'yicha saralash, 7 kunlik oyna.
