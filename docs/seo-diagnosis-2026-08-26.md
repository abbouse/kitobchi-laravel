# Kitobchi.com — SEO diagnostikasi (2026-08-26)

## Qisqacha xulosa

Google Search Console'dagi barcha muammolar (781 ta "Discovered — not indexed", 129 ta "Alternate page with canonical", 109 ta "Crawled — not indexed", 70 ta "Duplicate, canonical not selected by user", 27+27 ta aggregateRating/review ogohlantirishi) tasodifiy emas — ularning barchasi **bitta aniq voqeaga** bog'lanadi: **17-avgustda** web frontend Laravel Blade'dan yangi **Nuxt 3 SPA/SSR ilovasi**ga (`kitobchi-laravel/web`) ko'chirildi, va bu jarayonda mahsulot sahifalarining SEO metama'lumotlari (canonical, JSON-LD, unikal title/description) **bir necha kun davomida umuman yozilmagan** holda production'ga chiqib ketgan. Google aynan shu davrda saytni qayta o'rgangan va minglab "bir xil ko'ringan" sahifani duplikat/past sifat deb belgilab, indekslashni to'xtatgan — foydalanuvchi tasvirlagan "birdan hech qanday kitob so'zi bo'yicha chiqmay qoldik" degan holat aynan shu bilan izohlanadi.

Yaxshi xabar: asosiy tuzatishlar allaqachon 22–24-avgust commitlarida qisman qilingan (`useAppSeo.ts` yaratilgan va ulangan), lekin hali **bir nechta jiddiy nuqson** qolgan — ulardan biri hozir ham davom etmoqda va yangi muammo yaratishi mumkin (soxta reyting/review, pastda §3).

---

## 1. Asosiy sabab: Nuxt migratsiyasi paytida SEO "quruq qoldi"

**Git tarixi (`kitobchi-laravel` reposi):**

| Sana | Commit | Nima bo'ldi |
|---|---|---|
| 13-avgust | `3fe9d1c` | Laravel Blade'da `products/show.blade.php`, `products/catalog.blade.php` (to'liq ishlaydigan, canonical+JSON-LD bor eski sahifalar) mavjud edi |
| **17-avgust** | `cbe65c4` "Nuxt 3 API base URL... Piyola UI parity" | Bu Blade fayllar **butunlay o'chirildi** (821 va 567 qatorlik fayllar), sayt endi to'liq Nuxt (`web/`) orqali render qilinadi. Laravel endi faqat `/api/*` backend |
| 22-avgust | `4ddf1c6` "feat(seo): implement 4-language dynamic SEO..." | `composables/useAppSeo.ts` (setProductSeo funksiyasi) yaratildi — lekin mahsulot sahifalarida **hali chaqirilmagan edi** |
| **24-avgust** | `a9c1ae1` "Bug fixes" | Kod ichidagi izohlarda ochiq yozilgan: *"bu yerda ilgari qo'lda yozilgan, canonical/hreflang'siz... oddiy useSeoMeta bor edi... `setProductSeo()` mahsulot sahifalarida HECH QACHON chaqirilmagan edi (butun loyiha bo'yicha grep qilib tekshirildi). Endi ulandi."* Shu kunda `pages/books/[id].vue`, `pages/stationery/[id].vue` va `pages/catalog/index.vue`ga canonical/JSON-LD birinchi marta ulandi |

**Natija:** 17–24-avgust oralig'ida (kamida 6–7 kun) — bu Google'ning eng faol qayta indekslash oynasi — sayt **minglab mahsulot sahifasini** bir xil umumiy title/description bilan, canonical teg umuman yo'q holda ko'rsatgan (`nuxt.config.ts`dagi global bosh sahifa metama'lumotlari meros bo'lib qolgan). Google nuqtai nazaridan bu — "minglab bir xil sahifa" signali, aynan **"Discovered — currently not indexed"** (781) va **"Crawled — currently not indexed"** (109) hosil bo'lishining klassik sababi.

*Manba: [Onely — Discovered/Crawled not indexed sabablari](https://www.onely.com/blog/how-to-fix-discovered-currently-not-indexed-in-google-search-console/) — asosiy sabablar orasida "sайт bo'ylab kontent sifati past bo'lishi", "zaif ichki linklar", "yangi/o'zgargan sayt tuzilishi" ko'rsatilgan.*

Muhim: bu tuzatilgan bo'lsa ham, **Google'ning ishonchi darhol qaytmaydi** — bir marta "past sifat" deb belgilangan katalog qayta tiklanishi odatda haftalar (ba'zan oylar) talab qiladi, hatto sabab tuzatilgandan keyin ham.

---

## 2. Canonical nomuvofiqligi — hozir ham davom etayotgan muammo

**"Вариант страницы с тегом canonical" (129 ta) va "Дубликат, канонический вариант не выбран пользователем" (70 ta)** quyidagi ikkita kod nuqtasidan kelib chiqadi:

### 2.1. Katalog (`/catalog`) filtr parametrlari
`pages/catalog/index.vue` koddagi izoh o'zi buni tan olgan: 24-avgustgacha bu sahifada **canonical umuman yo'q edi**, va `sort`/`search`/`min_price`/`publisher_ids`/`seller_ids`/`lang_types`/`cover_types` kabi filtr query-parametrlari **cheksiz URL variantlari** yaratadi (masalan `/catalog?sort=new&min_price=1000&publisher_ids=3,7`). Bular Google tomonidan alohida-alohida "kashf qilingan" (discovered) URL sifatida ko'rilib, hech biri o'ziga tegishli canonical'ga ega bo'lmagani uchun Google o'zi qaysi versiyani "asl" deb hisoblashini tanlagan — bu aynan **"Duplicate, Google chose different canonical than user"** hisobotining sababi. Hozir faqat `type` parametri saqlanib, qolganlari canonical'dan olib tashlangan — bu **to'g'ri yo'nalish**, lekin robots.txt/sitemap darajasida ham bu filtr URL'lari googlebot uchun umuman crawl qilinmasligi kerak (pastga qarang, §4).

### 2.2. Mahsulot sahifasi canonical'i — slug tekshiruvi yo'q
Eski Laravel controllerida (`ProductCatalogController::showBook`) slug mos kelmasa **301 redirect** qilinardi:
```php
$expectedSlug = Str::slug($book->name);
if ($slug !== $expectedSlug) {
    return redirect()->route('web.books.show', ['id' => $book->id, 'slug' => $expectedSlug], 301);
}
```
Bu mantiq Nuxt'ga **ko'chirilmagan**. `pages/books/[id].vue`da:
```js
const rawId = computed(() => String(route.params.id || '').split('-')[0])
...
urlPath: `/books/${route.params.id}`,   // ← route.params.id — foydalanuvchi kiritgan XOM qiymat
```
ya'ni `/books/123-notogri-slug` yoki hatto `/books/123-` kabi har qanday variant **200 OK** bilan ochiladi va o'sha (noto'g'ri) URL'ning o'zini canonical qilib ko'rsatadi — Laravel'dagi kabi to'g'ri slug'ga qaytarilmaydi. Sitemap (`resources/views/seo/sitemap.blade.php`) esa har doim `Str::slug($book->name)` orqali **to'g'ri** slug bilan URL generatsiya qiladi. Demak: agar biror joyda (eski indekslangan havola, ijtimoiy tarmoqdagi eski link, kitob nomi keyinchalik o'zgargan bo'lsa) noto'g'ri/eski slug bilan link mavjud bo'lsa — u endi hech qachon to'g'ri versiyaga yo'naltirilmaydi, faqat o'zini-o'zi canonical qiladi va sitemap'dagi asl URL bilan "raqobatlashadi".

**Tavsiya:** Nuxt sahifasida ham slug'ni `Str::slug` ekvivalenti bilan tekshirib, mos kelmasa `navigateTo(correctUrl, { redirectCode: 301 })` bilan serverga 301 qaytarish kerak (Laravel'dagi eski mantiqni Nuxt'ga ko'chirish).

---

## 3. ⚠️ Soxta (fabricated) reyting — darhol tuzatish kerak

`web/composables/useAppSeo.ts`, 153–162-qatorlar:
```js
const ratingVal = Number(options.rating || 5.0)      // reyting bo'lmasa → 5.0 yulduz
const reviewCnt = Number(options.reviewsCount || 1)  // review bo'lmasa → 1 review
if (reviewCnt > 0) {                                  // shuning uchun HAR DOIM true
  productSchema.aggregateRating = {
    '@type': 'AggregateRating', ratingValue: ratingVal.toFixed(1), reviewCount: reviewCnt, ...
  }
}
```
Bu — **haqiqiy sharh/reyting bo'lmagan mahsulotlar uchun ham** avtomatik ravishda "5.0 yulduz, 1 ta sharh" deb JSON-LD'ga yozib qo'yadi. Google'ning rasmiy siyosati bunga aniq qarshi:

> *"Reviews or ratings not by actual users may result in [manual action]."* — [Google Search Central, Structured data general guidelines](https://developers.google.com/search/docs/appearance/structured-data/sd-policies)

Bu — GSC'dagi hozirgi "aggregateRating/review yo'q" ogohlantirishidan (27 ta, — bular Google hali eski, review'siz versiyani ko'rgan sahifalar) **ancha jiddiyroq** keyingi bosqich: Google qayta crawl qilganda bu soxta 5.0/1-review ma'lumotini ko'radi, va vaqt o'tib buni spam sifatida aniqlab, **butun sayt uchun rich-result (yulduzchali snippet) huquqini olib qo'yishi** mumkin (bu asosiy ranking'ga emas, faqat qidiruvdagi "yulduzcha" ko'rinishiga ta'sir qiladi, lekin ishonch signaliga salbiy ta'sir ko'rsatadi).

**Tuzatish (minimal diff):**
```diff
- const ratingVal = Number(options.rating || 5.0)
- const reviewCnt = Number(options.reviewsCount || 1)
- if (reviewCnt > 0) {
+ const ratingVal = Number(options.rating || 0)
+ const reviewCnt = Number(options.reviewsCount || 0)
+ if (ratingVal > 0 && reviewCnt > 0) {
```
Real UGC sharhlar mavjud bo'lgan mahsulotlarda (backend'da `ugc_reviews_count`/`ugc_aggregate_score` — bu maydonlar Laravel `SeoService::buildBookSchemas()`da to'g'ri shartli ishlatilgan, xuddi shunday mantiq Nuxt tarafida ham bo'lishi kerak) aggregateRating chiqadi; qolganlarida — umuman chiqmaydi (bu GSC'da "warning" sifatida qoladi, lekin bu zararsiz — Google buni "boyitilishi mumkin bo'lgan sahifa" deb belgilaydi, indekslashga xalaqit bermaydi).

*Manbalar: [Search Engine Journal — Structured Data Mistakes That Cause Penalties](https://www.searchenginejournal.com/structured-data-mistakes/276127/), [Blue Array SEO — Spammy AggregateRating Schema](https://www.bluearray.co.uk/news/schema/spammy-structured-data-markup-review-aggregaterating-schema/)*

---

## 4. Boshqa aniqlangan nuqsonlar

**a) `/share/product/{id}` va `/art/{artikul}` sahifalari — o'z-o'zini canonical qiladi.**
`routes/web.php`dagi ushbu route'lar (`share.redirect` view'ini render qiladi) app-deep-link uchun mo'ljallangan bo'lib, mahsulot haqida deyarli bir xil matn/rasm ko'rsatadi, lekin `partials/seo-social.blade.php`dagi standart qoida bo'yicha `canonical = $canonical ?? url()->current()` — ya'ni **o'zining share-URL'ini** canonical qiladi, asl mahsulot sahifasiga (`webUrl`) emas. Bu — asl mahsulot sahifasi bilan raqobatlashadigan qo'shimcha "deyarli-duplikat" URL manzili. Agar bu sahifalar ichki linklardan (masalan "Ulashish" tugmasi orqali) yoki eski indekslangan havolalardan crawl qilinsa, ular ham "Duplicate" hisobiga qo'shiladi.
→ *Tuzatish:* bu route'larga `noindex, follow` meta robots qo'shish (chunki ular indekslanishi shart emas — faqat ijtimoiy tarmoq preview'i va ilovaga o'tish uchun), YOKI canonical'ni `webUrl`ga ko'rsatish.

**b) `Server error (5xx) — 1 ta` va `Page with redirect — 25 ta`.**
Bular hozircha kichik sonlar — ehtimol 17-avgust migratsiyasi paytidagi vaqtinchalik uzilishlar yoki `/shared-cart/{slug}` → `/share/cart/{slug}` kabi 301 zanjirlaridan qolgan iz. Kod darajasida hozircha kritik emas, lekin GSC'da "Ошибка сервера" bo'limini ochib qaysi aniq URL(lar) ekanini tekshirish tavsiya etiladi (deploy paytidagi vaqtinchalik downtime bo'lishi mumkin).

**c) `robots.txt` — Laravel darajasida to'g'ri, lekin ikkita manba bor.**
`ProductCatalogController::robots()` (Laravel) va Nuxt orasida `robots.txt` uchun alohida route yo'q — Nuxt faqat `sitemap.xml`/`google-merchant.xml`ni proxy qiladi. Demak production'da `kitobchi.com/robots.txt` aslida qaysi backend orqali xizmat qilishini (Laravel to'g'ridan-to'g'ri, yoki Nuxt fallback orqali) reverse-proxy konfiguratsiyasida tasdiqlash kerak — agar noto'g'ri routing bo'lsa (masalan Nuxt o'zining default `robots.txt`sini bersa), bu butun saytning crawl siyosatini buzishi mumkin. Bevosita brauzerda `https://kitobchi.com/robots.txt` ochib tekshirish tavsiya etiladi.

**d) Katalog filtr URL'lari `robots.txt`da bloklanmagan.**
`robots()` metodida faqat `/a122/`, `/boshqaruv/`, `/api/` disallow qilingan. `/catalog?sort=...&min_price=...` kabi parametrli URL'lar hali ham crawl qilinadi (faqat canonical orqali konsolidatsiya qilinadi, lekin crawl byudjeti sarflanadi). Google Search Console'dagi "URL Parameters" eskirgan, endi buning o'rniga `robots.txt`da `Disallow: /catalog?*sort=` yoki shunga o'xshash qoidalar, yoki canonical'ga to'liq ishonish tavsiya etiladi (hozirgi holat — canonical'ga ishonish — odatda yetarli, lekin 781 "discovered" son katta bo'lgani uchun qo'shimcha ehtiyot chorasi sifatida foydali bo'lishi mumkin).

---

## 5. Nima uchun ilgari (Laravel Blade davrida) SEO yaxshi ishlagan edi

Solishtirish uchun: eski `SeoService::buildBookSchemas()` (Laravel, hali ham kodda mavjud, lekin endi web sahifalarda ishlatilmaydi — faqat sitemap va boshqa joyларda) **to'g'ri** yozilgan edi — aggregateRating faqat haqiqiy `ugc_reviews_count`/`ugc_aggregate_score` mavjud bo'lgandagina qo'shiladi, soxta default yo'q. Bu shuni ko'rsatadiki, muammo "SEO strategiyasi noto'g'ri" emas — muammo **frontend arxitekturasini Nuxt'ga ko'chirishda eski, sinovdan o'tgan mantiqning bir qismi (slug-redirect, shartli aggregateRating) to'liq ko'chirilmay qolgani**da.

---

## 6. Ustuvorlik bo'yicha harakatlar ro'yxati

1. **(Kritik, darhol)** §3 — `useAppSeo.ts`dagi soxta `5.0/1-review` default qiymatlarni olib tashlash.
2. **(Yuqori)** §2.2 — mahsulot sahifalarida slug mos kelmasa 301 redirect mantig'ini Nuxt'ga qaytarish.
3. **(Yuqori)** §4a — `/share/product/*`, `/art/*` sahifalariga `noindex` qo'yish yoki canonical'ni asl mahsulot URL'iga ko'rsatish.
4. **(O'rta)** `https://kitobchi.com/robots.txt` va `https://kitobchi.com/sitemap.xml`ni brauzerda ochib, production'da haqiqatan to'g'ri konfiguratsiya ishlab turganini tasdiqlash (Nuxt/Laravel routing nizosi bo'lishi mumkin, §4c).
5. **(O'rta)** GSC'da barcha tuzatilgan sahifalar uchun "Validate Fix" / qayta indekslashni so'rash (Sitemaps va Inspection tool orqali), sabr bilan 2-6 hafta kutish — bu turdagi "site-wide quality" pasayishi tezkor tuzatilmaydi.
6. **(Past, lekin foydali)** Katalog filtr URL'lari uchun `robots.txt`da qo'shimcha `Disallow` qoidalari yoki `rel=nofollow` ichki linklar orqali crawl byudjetini tejash.

---

## Manbalar

- [Onely — How To Fix "Discovered/Crawled – Currently Not Indexed"](https://www.onely.com/blog/how-to-fix-discovered-currently-not-indexed-in-google-search-console/)
- [Search Engine Land — Understanding and resolving 'Discovered - currently not indexed'](https://searchengineland.com/understanding-resolving-discovered-currently-not-indexed-392659)
- [Google Search Central — Structured data general guidelines (fake reviews policy)](https://developers.google.com/search/docs/appearance/structured-data/sd-policies)
- [Rank Math — "Either offers, review, or aggregateRating should be specified"](https://rankmath.com/kb/either-offers-review-or-aggregaterating-should-be-specified/)
- [Search Engine Journal — Structured Data Mistakes That Trigger Penalties](https://www.searchenginejournal.com/structured-data-mistakes/276127/)
- [Blue Array SEO — Spammy structured data / AggregateRating review](https://www.bluearray.co.uk/news/schema/spammy-structured-data-markup-review-aggregaterating-schema/)
