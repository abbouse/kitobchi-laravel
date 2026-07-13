# Kitobchi (Laravel)

**Kitobchi** — kitob va kanstovar marketplace uchun Laravel 11 backend: mobil ilova API (foydalanuvchi, sotuvchi, kuryer), admin panel, to‘lovlar (Payme), push (FCM), Telegram-bot, AI (Google Gemini va OpenAI), avtomatik mahsulot va Book Club moderatsiyasi, savat, buyurtmalar, kontent va boshqalar.

Bu hujjat repoda **o‘rnatish**, **sozlash**, **yo‘llar**, **jadval (scheduler)**, **integratsiyalar** va **papka tuzilmasi** bo‘yicha yo‘riqnoma.

Admin paneldagi statuslar, kodli holatlar va moderatsiya mappinglari uchun alohida reference:

- [docs/admin-status-reference.md](docs/admin-status-reference.md)

Order lifecycle uchun yangi kanonik string enum qatlamlari:

- `solds.status_code`
- `solds.payment_status_code`
- `seller_orders.status_code`
- `courier_orders.status_code`

Eski `status` / `paymentStatus` ustunlari hali compatibility uchun saqlanadi, lekin yangi development shu `*_code` ustunlariga qarashi kerak.

---

## Texnologiyalar

| Qatlam | Texnologiya |
|--------|-------------|
| Backend | **PHP 8.2+**, **Laravel 11** |
| API auth | **Laravel Sanctum** |
| Frontend (panel + assetlar) | **Vite 7**, **Tailwind CSS 4**, **Alpine.js**, ApexCharts, FullCalendar, Flatpickr |
| Real-time | **Laravel Reverb** (ixtiyoriy; `BROADCAST_CONNECTION=reverb`) |
| Navbat | `database` yoki `redis` — `.env` bo‘yicha |
| Kesh / sessiya | odatda **database** driver (migratsiyalar bilan) |
| Push | **Firebase** + **FCM** (`kreait/laravel-firebase`, `laravel-notification-channels/fcm`) |
| AI | **Google Gemini** (`google-gemini-php/laravel`) va **OpenAI** (`openai-php/client`) |
| Chatbot | **OpenAI** client (`openai-php/client`) |
| Telegram | **Nutgram** (`nutgram/laravel`) |
| SMS | **Android SMS Gateway** (`capcom6/android-sms-gateway`) |
| Video | **php-ffmpeg** (serverda `ffmpeg` binary kerak) |
| Redis | **Predis** (ixtiyoriy) |

---

## Talablar

- **PHP 8.2 yoki 8.3** (`ext-curl`, `ext-json`, `ext-mbstring`, `ext-openssl`, `ext-pdo`, `ext-tokenizer`, `ext-xml`, `ext-zip` va Laravel talab qilgan boshqa kengaytmalar)
- **Composer 2**
- **Node.js 20+** (Vite uchun)
- **MySQL 8** (ishlab chiqarishda tavsiya) yoki lokal uchun `.env.example` dagi **SQLite**
- **Redis** — Reverb masshtablash yoki `REDIS_*` bilan kesh/navbat uchun
- **FFmpeg** — videolar bilan ishlash funksiyalari yoqilgan bo‘lsa
- **OpenAI API key** — chatbot, UGC baholash va avtomatik mahsulot moderatsiyasi uchun

---

## Tez boshlash

```bash
cd kitobchi
cp .env.example .env
composer install
php artisan key:generate
```

**Ma’lumotlar bazasi**

- SQLite (standart `.env.example`): `touch database/database.sqlite` keyin `php artisan migrate`
- MySQL: `.env` da `DB_CONNECTION=mysql`, host, nom, foydalanuvchi, parol, keyin `php artisan migrate`

```bash
php artisan storage:link
npm install
npm run build          # yoki rivojlantirish: npm run dev
php artisan serve      # yoki nginx/php-fpm
```

**Navbat** (`.env` da `QUEUE_CONNECTION=database` bo‘lsa):

```bash
php artisan queue:work
```

**Bir vaqtning o‘zida dev** (server + navbat + log + Vite):

```bash
composer run dev
```

---

## Loyiha tuzilmasi

```
kitobchi/
├── app/
│   ├── Console/Commands/       # Artisan buyruqlar (AI, jadval, buyurtma eslatmalari, …)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/            # Mobil va tashqi API (User, Seller, Courier, Payme, Webhook, …)
│   │   │   └── A122/           # Admin panel (dashboard, kitoblar, buyurtmalar, moderatsiya, …)
│   │   └── Middleware/         # locale, Payme, panel auth, API client, ruxsatlar
│   ├── Models/                 # Eloquent modellar (Books, Orders, Seller, BookClub, …)
│   ├── Notifications/          # FCM va boshqa bildirishnomalar
│   └── Services/               # To‘lov, AI, logistika va domen servis qatlamlari
├── bootstrap/app.php           # middleware aliaslari, Laravel scheduler
├── config/                     # services, firebase, fcm, gemini, nutgram, reverb, payme, seo, …
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── schema/
│       └── kitobchi_structure.sql   # to‘liq sxema referensi (migratsiyalar bilan bir vaqtda ishlatiladi)
├── lang/                       # ko‘p tilli matnlar (xatolar, UI)
├── public/                     # kirish nuqtasi, `images/`, build chiqishi
├── resources/
│   ├── css/                    # jumladan kitobchi-popcorn.css (landing / xato sahifalar)
│   ├── js/
│   └── views/                  # Blade: welcome, panel, share, layouts, errors
├── routes/
│   ├── web.php                 # landing, share, to‘lov, legal, careers, panel include
│   ├── a122.php                # /a122/* admin
│   ├── api.php                 # asosiy API guruhlari + v1 prefiksi
│   ├── api_user.php            # /api/v1/kitobchi/* foydalanuvchi ilovasi
│   ├── api_seller.php          # /api/v1/seller/*
│   ├── api_courier.php         # /api/v1/courier/*
│   ├── api_client.php          # /api/v1/client/* (masalan mehmon sinxron)
│   ├── channels.php            # broadcast kanallari
│   └── telegram.php            # Nutgram marshrutlari (agar ishlatilsa)
├── storage/                    # loglar, cache, yuklamalar; `app/firebase_credentials.json` (gitga emas)
├── tests/
├── composer.json
├── package.json
└── vite.config.js
```

---

## Yo‘llar (routing)

| Fayl | Vazifa |
|------|--------|
| `routes/web.php` | Bosh sahifa (`/`), til almashtirish, `/share/*`, Payme redirectlari, `/payment/*`, `/telegram/webhook`, karyera, huquqiy hujjatlar, `a122.php` ni `require` |
| `routes/a122.php` | Admin: login, dashboard, foydalanuvchilar, kitob/kanstovar, buyurtmalar, sotuvchilar, kuryerlar, chat, promokodlar, Book Club, sozlamalar, … |
| `routes/api.php` | Sanctum, SMS, auth, push, loyiha versiyasi, qisqa user endpointlar, so‘ng `Route::prefix('v1')` ostida quyidagilar |
| `routes/api_user.php` | `POST/GET …` — `/api/v1/kitobchi/...` mobil foydalanuvchi |
| `routes/api_seller.php` | `/api/v1/seller/...` partner kabineti API |
| `routes/api_courier.php` | `/api/v1/courier/...` kuryer |
| `routes/api_client.php` | `/api/v1/client/...` (klient identifikatsiyasi middleware bilan) |
| `routes/channels.php` | `Broadcast::channel` qoidalari |
| `routes/telegram.php` | Telegram bot marshrutlari |

**Sog‘liq tekshiruvi:** `GET /up` (Laravel default).

---

## Muhit o‘zgaruvchilari (`.env`)

Asosiy namuna: **`.env.example`**. Quyida Kitobchi uchun muhim guruhlar (to‘liq Laravel ro‘yxati uchun [Laravel docs](https://laravel.com/docs/11.x/configuration) ga qarang).

### Ilova

| O‘zgaruvchi | Tavsif |
|-------------|--------|
| `APP_NAME`, `APP_URL`, `APP_KEY`, `APP_ENV`, `APP_DEBUG` | Standart |
| `APP_TIMEZONE` | Jadval `Asia/Tashkent` bilan mos (scheduler `bootstrap/app.php` da) |
| `SEO_OG_IMAGE` | Ijtimoiy tarmoq preview rasmi (to‘liq HTTPS URL) |

### Ma’lumotlar bazasi, sessiya, kesh, navbat

| O‘zgaruvchi | Tavsif |
|-------------|--------|
| `DB_*` | MySQL yoki SQLite |
| `SESSION_DRIVER` | Ko‘pincha `database` |
| `CACHE_STORE` | Ko‘pincha `database` |
| `QUEUE_CONNECTION` | `database` yoki `redis` |

### Pochta

| O‘zgaruvchi | Tavsif |
|-------------|--------|
| `MAIL_*` | SMTP yoki `log` |
| `MAIL_REPLY_TO_*` | Karyera javoblari uchun Reply-To |

### To‘lov (Payme)

`config/services.php` → `payme`:

- `PAYME_MERCHANT_ID`
- `PAYME_SUBSCRIBE_PASSWORD`
- `PAYME_ENDPOINT`

### Firebase va FCM

- `FIREBASE_PROJECT` (default konfig nomi)
- `FIREBASE_CREDENTIALS` yoki `GOOGLE_APPLICATION_CREDENTIALS` — JSON kalit fayl yo‘li (odatda `storage/app/firebase_credentials.json`)
- `FIREBASE_PROJECT_ID` — `config/fcm.php` da push uchun

### AI (Gemini)

- `GEMINI_API_KEY`
- Ixtiyoriy: `GEMINI_BASE_URL`, `GEMINI_REQUEST_TIMEOUT`, `GEMINI_TIMEOUT`, `GEMINI_CACHE_TTL` (`config/gemini.php`)

### OpenAI (chatbot / Book Club / mahsulot moderatsiyasi)

- `OPENAI_API_KEY`
- `PRODUCT_AI_MODERATION_*` — model, batch, limit, rasm sifati, confidence va retry sozlamalari

- `OPENAI_API_KEY` — `App\Services\OpenAIService` (`config('openai.api_key')` bo‘lsa, u ustunlik qiladi)

---

## Book Club AI baholash

Book Club uchun eski qo‘lda `UGC navbati` oqimi olib tashlangan. Endi post va izohlar batch usulida **OpenAI** orqali baholanadi, kommentlar esa haftalik moderatsiyadan ham o‘tadi.

### Qanday ishlaydi

- Yangi post yozilganda: `book_club.ai_post_status = pending`
- Yangi izoh yoki reply yozilganda: `book_club_comments.ai_status = pending`
- Scheduler kuniga 2 marta post va izohlarni OpenAI orqali baholaydi
- Scheduler haftasiga 1 marta Book Club kommentlarini so'kinish, spam va reklama bo'yicha moderatsiya qiladi
- Mahsulotga bog‘langan **Book Club postlari** haftasiga bir marta yig‘ilib, mahsulotning `ugc_aggregate_score` qiymati qayta hisoblanadi

### Maydonlar

`book_club`
- `ai_post_score`
- `ai_post_checked_at`
- `ai_post_status`
- `ai_post_note`
- `ai_post_model`

`book_club_comments`
- `ai_score`
- `ai_checked_at`
- `ai_status`
- `ai_note`
- `ai_model`
- `is_hidden_by_ai`
- `ai_moderation_status`
- `ai_moderated_at`
- `ai_moderation_note`
- `ai_moderation_model`

`books` / `stationeries`
- `ugc_aggregate_score`
- `ugc_reviews_count`
- `ugc_last_scored_at`

### Baholash mezoni

AI 1–5 oralig‘ida baho beradi:

- `1` — spam, haqorat, zararli yoki butunlay befoyda
- `2` — juda sust yoki mavzuga deyarli yordam bermaydi
- `3` — oddiy, qabul qilsa bo‘ladi
- `4` — foydali va mavzuga hissa qo‘shadi
- `5` — juda foydali, aniq va ishonchli

Mahsulot UGC reytingi esa AI baholagan productga bog‘langan Book Club postlaridan **Bayesian weighted average** usuli bilan hisoblanadi. Yaqin 30–90 kundagi sharhlarga biroz yuqoriroq og‘irlik beriladi.

### Reklama va nomaqbul kommentlar siyosati

Kommentlarda quyidagilar yashiriladi:

- so'kinish, haqorat, kamsitish, tahdid
- pornografik yoki ochiq jinsiy mazmun
- spam va takroriy flood
- tashqi savdo yoki trafik yig'ish: telefon, Telegram, Instagram, WhatsApp, link, promo-kod, narx bilan sotuvga chaqirish
- firibgarlik, noqonuniy xizmat yoki xavfli takliflar

Oddiy tajriba, shaxsiy tavsiya yoki product haqida tabiiy fikr esa yashirilmaydi.

### Artisan buyruqlar

```bash
php artisan openai:score-book-club-content
php artisan openai:score-book-club-content --all=1
php artisan openai:moderate-book-club-comments
php artisan openai:moderate-book-club-comments --all=1
php artisan products:refresh-ugc-ratings
```

### Scheduler

`bootstrap/app.php` ichida:

- `openai:score-book-club-content` — kuniga 2 marta
- `products:refresh-ugc-ratings` — haftasiga 1 marta
- `products:moderate-ai --type=all` — har 30 daqiqada

### SMS (Eskiz)

`SendSmsController` orqali **notify.eskiz.uz** API ishlatiladi. Kod bazasida autentifikatsiya hozircha koddagi konstantalar bilan berilgan bo‘lishi mumkin — **ishlab chiqarishda** login/parol/tokenni faqat `.env` orqali berish va koddan olib tashlash tavsiya etiladi.

### Telegram (Nutgram)

- `TELEGRAM_TOKEN`
- `BOT_ADMINS` — vergul bilan Telegram user ID lar

### Real-time (Reverb)

`config/reverb.php` va `config/broadcasting.php`: `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_APP_ID`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`, server uchun `REVERB_SERVER_*`, masshtablashda Redis.

### Boshqa (ixtiyoriy)

- **AWS** — `AWS_*` (S3, SQS)
- **Sanctum** — `SANCTUM_STATEFUL_DOMAINS`
- **Vite** — `VITE_APP_NAME`

---

## Artisan buyruqlar (loyiha ichidagi)

| Buyruq | Tavsif |
|--------|--------|
| `php artisan products:moderate-ai --type=all` | Kitob va kanstovar listinglarini metadata hamda rasmlari bilan AI moderatsiyadan o‘tkazish |
| `php artisan ai:daily-reset` | AI limitlari va chat tozalash (kunlik) |
| `php artisan orders:remind-unpaid` | To‘lanmagan buyurtma eslatmasi |
| `php artisan orders:cancel-unpaid` | Muddati o‘tgan to‘lanmagan buyurtmalarni bekor qilish |
| `php artisan cart:remind --time=morning\|afternoon\|evening` | Tashlab ketilgan savat |
| `php artisan users:book-remind` | Kitob eslatmasi (haftalik jadvalda 3 marta) |
| `php artisan vectors:rebuild` | Qidiruv vektorlari (`--type`, `--force`) |
| `php artisan gifts:expire` | Muddati o‘tgan sovg‘a sertifikatlari |
| `php artisan mystery-box:check-deliveries` | Mystery Box yetkazib berish |
| `php artisan bot:webhook set\|delete\|info` | Telegram webhook |

**Eslatma:** `bootstrap/app.php` jadvalida `backup:clean` va `backup:run` chaqiruvlari bor. Hozirgi `composer.json` da **spatie/laravel-backup** ko‘rinmaydi — agar paket o‘rnatilmagan bo‘lsa, bu buyruqlar xato beradi; ishlab chiqarishda paketni qo‘shing yoki schedulerdan olib tashlang.

---

## Scheduler (cron)

Vaqt zonasi: **`Asia/Tashkent`**. Serverda har daqiqa:

```bash
* * * * * cd /path/to/kitobchi && php artisan schedule:run >> /dev/null 2>&1
```

Rejalashtirilgan vazifalar (`bootstrap/app.php`):

| Vazifa | Chastota |
|--------|----------|
| `ai:daily-reset` | Har kuni 00:05 |
| `orders:remind-unpaid` | Har 10 daqiqa |
| `orders:cancel-unpaid` | Har 5 daqiqa |
| `cart:remind` | 08:00, 13:00, 19:00 |
| `users:book-remind` | Se, Pa, Ju (hafta kunlari 2,4,6) |
| `backup:clean` | Har kuni 01:15 |
| `backup:run` | Har kuni 02:10 |
| `vectors:rebuild --force` | Yakshanba 02:30 |
| `queue:prune-batches` | Har kuni 03:00 |
| `gifts:expire` | Har kuni 02:00 |
| `mystery-box:check-deliveries` | Har kuni 08:30 |
| `products:moderate-ai --type=all` | Har 30 daqiqa |

---

## Order Status Codes

Marketplace order oqimida endi 4 ta kanonik status tili bor:

### Main order: `solds.status_code`

| Code | Ma’nosi | Legacy |
|------|---------|--------|
| `pending` | Buyurtma yaratildi, navbatda | `A` |
| `packing` | Seller qabul qildi / tayyorlanyapti | `P` |
| `in_delivery` | Yo‘lda | `B` |
| `delivered` | Yetkazildi | `C` |
| `cancelled` | Bekor qilindi | `F` |
| `returned` | Pochta qaytarib yuborgan | `F` legacy bilan birga |

### Payment: `solds.payment_status_code`

| Code | Ma’nosi | Legacy |
|------|---------|--------|
| `cash_pending` | Naqd, hali yopilmagan | `0` |
| `card_pending` | Karta/Payme, hali tasdiqlanmagan | `1` |
| `paid` | To‘langan | `2` |
| `cancelled` | To‘lov bekor / rad | `3` |

### Seller order: `seller_orders.status_code`

| Code | Ma’nosi | Legacy |
|------|---------|--------|
| `payment_pending` | To‘lov kutilmoqda | `0` |
| `new` | Yangi buyurtma | `1` |
| `accepted` | Do‘kon qabul qildi | `2` |
| `handed_to_courier` | Kuryerga berildi | `3` |
| `cancelled` | Bekor qilindi | `4` |

### Courier order: `courier_orders.status_code`

| Code | Ma’nosi | Legacy |
|------|---------|--------|
| `payment_pending` | To‘lov tasdiq kutmoqda | `pay_process` |
| `pending` | Kuryerga chiqishi mumkin | `pending` |
| `in_delivery` | Kuryerda yo‘lda | `in_delivery` |
| `delivered` | Yetkazildi | `delivered` |
| `cancelled` | Bekor qilindi | `rejected` |
| `returned` | Pochta qaytimi / markazga qaytgan | `returned` |

### Canonical qoida

- Yangi backend logika `*_code` ustunlarini `source of truth` deb oladi.
- Legacy ustunlar (`status`, `paymentStatus`) rollout davrida eski app buildlar sinmasligi uchun saqlanadi.
- Yangi API payloadlarda iloji boricha `status_code` va `payment_status_code` ham qaytariladi.

## Status Backfill

Yangi `*_code` ustunlarini eski yozuvlar bilan to‘ldirish uchun:

```bash
php artisan migrate
php artisan orders:migrate-status-codes --dry-run
php artisan orders:migrate-status-codes
```

`--dry-run` preview uchun, real yozmaydi.

## Postal Return / Resend Flow

Pochta orqali yuborilgan buyurtma qaytib kelsa:

1. Admin order detail ichida `Pochta qaytgan deb belgilash` formi orqali qayta yuborish narxini (`postal_return_fee`) va izohni kiritadi.
2. Original order:
   - `status_code = returned`
   - `postal_return_status = returned_to_sender`
   - `postal_return_fee` saqlanadi
3. Customer purchase detail sahifasida `Buyurtmani qayta yuborish` CTA ko‘rinadi.
4. User bosganda penalty/to‘lov uchun alohida resend order yaratiladi:
   - `order_kind = postal_resend`
   - `resend_source_order_id = original_order_id`
   - `amount = postal_return_fee`
   - `payment_status_code = card_pending`
5. Payme muvaffaqiyatli tugagach resend child order avtomatik:
   - seller tarafda `handed_to_courier`
   - courier tarafda `pending`
   - main order tarafda `in_delivery`
   holatiga o‘tadi.
6. Seller settlement resend child orderlar uchun qayta ishlamaydi; bu oqim faqat logistika re-dispatch uchun.

Bu yondashuv bilan:

- sellerga ikkinchi marta daromad yozilmaydi
- courier uchun yangi logistika vazifa yaratiladi
- customer uchun qayta yuborish fee alohida va tushunarli bo‘ladi

---

## Middleware (asosiy)

`bootstrap/app.php`:

- **Web:** `SetLandingLocale` — landing til prefiksi / cookie
- **Alias:** `api.client` (`VerifyApiClient`), `payme` (`PaymeMiddleware`), `auth.panel`, `panel.permission`
- **API guruhiga:** `UpdateLastSeen` — oxirgi faollik

---

## Xavfsizlik va fayllar

- `.env`, `storage/app/firebase_credentials.json` va boshqa maxfiy kalitlarni **Gitga qo‘shmang**
- `php artisan storage:link` — `public/storage` → yuklamalar
- Productionda `APP_DEBUG=false`, HTTPS, `SESSION_SECURE_COOKIE` mos sozlash

---

## Test va sifat

```bash
./vendor/bin/phpunit
# yoki
php artisan test
```

Kod uslubi: `./vendor/bin/pint` (Laravel Pint).

---

## Mobil ilovalar

Mobil ilovalar bu repodagi **`/api/v1/...`** endpointlardan foydalanadi.

---

## Foydali buyruqlar qisqacha

```bash
php artisan migrate
php artisan db:seed
php artisan optimize:clear
php artisan route:list
php artisan config:cache   # faqat production
```

Savollar yoki yangi ishlab chiqarish serveri uchun alohida **deploy** skriptlari bo‘lsa, ularni shu repoga qo‘shish mumkin.
