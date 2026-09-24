# Katalog joyi (buy box) — do'konga sotiladigan birinchi o'rin

Bitta kitobni bir nechta do'kon sotadi. Kitob sahifasida mijoz do'konni
tanlaydi, lekin **bittasi oldindan tanlangan** turadi. Shu tanlangan o'rinni
do'konga sotamiz.

---

## 1. Kim birinchi turadi — qoida

Tartib `CatalogOffers::sort()` da, g'olib esa `BuyBoxService::recompute()` da
hal qilinadi:

1. **Mijoz qaysi do'kon orqali kirgan bo'lsa — o'sha.**
   Do'kon profilidan kitobga bosilsa, ro'yxatdagi kitob allaqachon o'sha
   do'konning `books.id` si bo'ladi (do'kon sahifasi `catalogFeatured()`
   filtridan o'tmaydi), shuning uchun qo'shimcha parametr kerak emas.
   Bu sevimlilar, ulashilgan havola, forum posti va artikul skaneri uchun ham
   shunday ishlaydi — ularning hammasi aniq bitta taklifni ochadi.
2. **Aks holda karta g'olibi (`books.catalog_featured`).** U quyidagicha
   tanlanadi:
   - **pullik joy egasi**, agar u sotuvga yaroqli va qoldig'i bo'lsa;
   - aks holda: qoldiq bor → amaldagi narx arzon → tasdiqlangan do'kon →
     reputatsiya → do'kon reytingi → sotuvlar soni → id.
3. Qolganlari: qoldiq bor → arzon narx → id.

Pullik joy `books.catalog_featured` ga **to'g'ridan-to'g'ri yozilmaydi** —
`BuyBoxService` har 30 daqiqada va har narx/qoldiq o'zgarishida butun kartani
qayta yozadi, qo'lda qo'yilgan qiymat o'chib ketardi. Shuning uchun joy
`recompute()` ichidagi eng yuqori saralash kaliti sifatida qo'shilgan
(`BuyBoxService::paidFeaturedId()`).

**Mijozga halollik:** pullik taklif "eng yaxshi taklif" deb ko'rsatilmaydi —
ilovada "Homiylik joyi" deb belgilanadi (`offers[].is_sponsored`).

---

## 2. Sotish modeli

| Savol | Qaror |
|---|---|
| Nima sotiladi | Bitta kitob kartasidagi birinchi/tanlangan o'rin |
| Nechta do'kon | **Bitta.** Joy band bo'lsa boshqa do'kon sotib ololmaydi |
| Muddat | Kunlik; narx oylik kiritiladi, `narx / 30 × kun` bo'yicha hisoblanadi |
| To'lov | Do'kon balansidan darhol yechiladi (premium obuna kabi) |
| Moderatsiya | Admin tasdiqlaydi; muddat **tasdiq paytidan** boshlanadi |
| Rad etilsa | Pul to'liq qaytadi |
| Qoldiq tugasa | Joy vaqtincha keyingi do'konga o'tadi, **muddat sarflanadi** |

"Bir nechta do'kon sotib olsa nima bo'ladi?" — degan savol eksklyuziv model
tufayli umuman tug'ilmaydi. Band kartaga urinish `409 slot_taken` qaytaradi va
joy qachon bo'shashi aytiladi.

---

## 3. Jadvallar

### `catalog_slot_purchases`
Bitta sotib olish. `status`: `pending` → `active` → `expired`; yakuniy
holatlar `rejected`, `cancelled`.

Bandlik `edition_id` + `status IN (pending, active)` + `ends_at > now()`
bo'yicha aniqlanadi. Ikki do'kon bir vaqtda sotib olmasligi uchun sotib olish
tranzaksiya ichida `lockForUpdate` bilan tekshiriladi.

### `catalog_slot_settings`
Bitta qator: `price_per_month`, `min_days`, `max_days`, `is_active`.
Boshqaruv → **Katalog joylari** → "Narx sozlamasi".
`is_active` o'chiq bo'lsa do'konlar umuman sotib ololmaydi.

### `seller_balance_entries` — do'kon balansi daftari
Ilgari premium obuna `sellers.balance` dan to'g'ridan-to'g'ri yechilardi va
hech qayerda iz qolmasdi. Endi balansga har qanday tegish shu yerga yoziladi
(`amount` manfiy — xarajat), premium obuna ham shu yo'lga o'tkazildi.

---

## 4. Oqim

**Do'kon (ilova → Reklama xizmati → "Kitobda birinchi bo'lish"):**
`GET catalog-slots/info` → narx, balans, mening joylarim
`GET catalog-slots/books` → kartaga ulangan kitoblar + har biri bo'yicha bandlik
`GET catalog-slots/quote?book_id=&days=` → narx va bandlik
`POST catalog-slots` → balansdan yechiladi, `pending` holatida
`DELETE catalog-slots/{id}` → faqat `pending` holatda, pul qaytadi

**Admin (Boshqaruv → Katalog joylari):** tasdiqlash / rad etish (sabab bilan) /
faol joyni to'xtatish (pul qaytmaydi).

**Avtomatik:** `catalog:slots-expire` har soatda muddati tugaganlarni yopadi va
kartani qayta hisoblaydi.

---

## 5. Nimaga tegilmadi

**Sharh va reyting global qoladi.** Bitta jismoniy kitob — bitta baho.
Agar ular do'kon bo'yicha ajratilsa, bitta kitobni 20 ta do'kon sotganda
19 tasi "0 ta sharh" bo'lib qolardi (eski sharhlar tasodifiy taqsimlangan) va
reyting bloki umuman ko'rinmasdi. Do'konning o'z xizmat reytingi esa
allaqachon bor (`sellers.rating`, buyurtma natijalaridan) va endi do'kon
tanlash ro'yxatida ko'rsatiladi.

---

## 6. Ishga tushirish

```bash
php artisan migrate            # catalog_slot_* va seller_balance_entries
npm run build                  # boshqaruv sahifasi
php artisan catalog:buybox     # bir marta — joylar hisobga olinsin
```

Keyin Boshqaruv → **Katalog joylari** → "Narx sozlamasi" da oylik narxni
kiriting va "Sotuvda" ni yoqing. Shu qadamsiz do'konlar sotib ololmaydi.

Rejalashtirilgan buyruq `bootstrap/app.php` da: `catalog:slots-expire` — soatda.
