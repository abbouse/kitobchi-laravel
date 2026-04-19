# Kitobchi (Laravel)

**Kitobchi** — kitob va kanstovar marketplace uchun Laravel 11 backend: mobil ilova API (foydalanuvchi, sotuvchi, kuryer), admin panel, to‘lovlar (Payme), push (FCM), Telegram-bot, AI (Google Gemini), kontent moderatsiyasi (tashqi **Kangaroo** xizmati), Book Club, savat, buyurtmalar, kontent va boshqalar.

Bu hujjat repoda **o‘rnatish**, **sozlash**, **yo‘llar**, **jadval (scheduler)**, **integratsiyalar** va **papka tuzilmasi** bo‘yicha yo‘riqnoma.

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
| AI | **Google Gemini** (`google-gemini-php/laravel`) |
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
- **Kangaroo** — alohida Python xizmati (moderatsiya va bozor tahlili); batafsil: monorepo ichida `../kangaroo/README.md` yoki alohida Kangaroo reposi

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
│   ├── Console/Commands/       # Artisan buyruqlar (jadval, Kangaroo, buyurtma eslatmalari, …)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/            # Mobil va tashqi API (User, Seller, Courier, Payme, Webhook, …)
│   │   │   └── Panel/          # Admin panel (dashboard, kitoblar, buyurtmalar, moderatsiya, …)
│   │   └── Middleware/         # locale, Payme, panel auth, API client, ruxsatlar
│   ├── Models/                 # Eloquent modellar (Books, Orders, Seller, BookClub, …)
│   ├── Notifications/          # FCM va boshqa bildirishnomalar
│   └── Services/               # Masalan Kangaroo HTTP klienti
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
│   ├── panel.php               # /panel/* admin
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
| `routes/web.php` | Bosh sahifa (`/`), til almashtirish, `/share/*`, Payme redirectlari, `/payment/*`, `/telegram/webhook`, karyera, huquqiy hujjatlar, `panel.php` ni `require` |
| `routes/panel.php` | Admin: `/panel/login`, dashboard, foydalanuvchilar, kitob/kanstovar, buyurtmalar, sotuvchilar, kuryerlar, chat, promokodlar, **Book Club moderatsiya navbati**, sozlamalar, … |
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

### Kangaroo (tashqi API)

| O‘zgaruvchi | Tavsif |
|-------------|--------|
| `KANGAROO_API_URL` | Masalan `https://kangaroo.example.com` |
| `KANGAROO_API_KEY` | Kangaroo serveridagi `API_SECRET_KEY` bilan **bir xil** (`X-Kangaroo-Key`) |
| `KANGAROO_LISTING_AUTO_APPLY` | `true` bo‘lsa Kangaroo qarorlari to‘g‘ridan-to‘g‘ri `is_approved` ga yoziladi (**ehtiyot bilan**) |
| `KANGAROO_HTTP_TIMEOUT`, `KANGAROO_HTTP_RETRIES`, `KANGAROO_HTTP_RETRY_DELAY_MS` | HTTP qayta urinish |

### Firebase va FCM

- `FIREBASE_PROJECT` (default konfig nomi)
- `FIREBASE_CREDENTIALS` yoki `GOOGLE_APPLICATION_CREDENTIALS` — JSON kalit fayl yo‘li (odatda `storage/app/firebase_credentials.json`)
- `FIREBASE_PROJECT_ID` — `config/fcm.php` da push uchun

### AI (Gemini)

- `GEMINI_API_KEY`
- Ixtiyoriy: `GEMINI_BASE_URL`, `GEMINI_REQUEST_TIMEOUT`, `GEMINI_TIMEOUT`, `GEMINI_CACHE_TTL` (`config/gemini.php`)

### OpenAI (chatbot / boshqa servislar)

- `OPENAI_API_KEY` — `App\Services\OpenAIService` (`config('openai.api_key')` bo‘lsa, u ustunlik qiladi)

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
| `php artisan kangaroo:sync-content-moderation` | Kitob/kanstovar listing + Book Club UGC ni Kangaroo orqali sinxron moderatsiya (`--listings=0\|1`, `--ugc=0\|1`) |
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
| `kangaroo:sync-content-moderation` | Har 30 daqiqa (25 daqiqa `withoutOverlapping`) |

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

## Tashqi loyihalar

- **Kangaroo** (Python FastAPI) — `KANGAROO_API_URL` / `KANGAROO_API_KEY` orqali ulanadi; moderatsiya va bozor insightlari.
- Mobil ilovalar bu repodagi **`/api/v1/...`** endpointlardan foydalanadi.

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
