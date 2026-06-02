@php
  $routeFor = fn (string $slug) => $slug === 'getting-started'
    ? route('developers.api-docs')
    : route('developers.api-docs', ['page' => $slug]);
  $allPages = collect($pages)->map(fn ($page, $slug) => [
    'slug' => $slug,
    'title' => $page['title'],
    'description' => $page['description'],
  ])->values();
@endphp
<!DOCTYPE html>
<html lang="uz" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $currentPage['title'] }} | Kitobchi API</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #fff;
      --panel: #fff;
      --soft: #f6f7f9;
      --text: #111827;
      --muted: #6b7280;
      --faint: #9ca3af;
      --border: #e5e7eb;
      --accent: #2563eb;
      --accent-soft: #eff6ff;
      --code-bg: #0f172a;
      --code-text: #dbeafe;
      color-scheme: light;
    }
    html[data-theme="dark"] {
      --bg: #0b1020;
      --panel: #0f172a;
      --soft: #111827;
      --text: #e5e7eb;
      --muted: #9ca3af;
      --faint: #64748b;
      --border: #1f2a44;
      --accent: #60a5fa;
      --accent-soft: rgba(96, 165, 250, .12);
      --code-bg: #020617;
      --code-text: #dbeafe;
      color-scheme: dark;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: var(--bg);
      color: var(--text);
      letter-spacing: 0;
    }
    a { color: inherit; }
    .docs-top {
      position: sticky;
      top: 0;
      z-index: 30;
      display: grid;
      grid-template-columns: 272px minmax(0, 1fr) 224px;
      align-items: center;
      min-height: 64px;
      border-bottom: 1px solid var(--border);
      background: color-mix(in srgb, var(--bg) 92%, transparent);
      backdrop-filter: blur(14px);
    }
    .docs-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
      padding: 0 24px;
      text-decoration: none;
      border-right: 1px solid var(--border);
      height: 64px;
    }
    .docs-brand img { width: 120px; height: auto; display: block; }
    html[data-theme="dark"] .docs-brand .logo-light { display: none; }
    html[data-theme="light"] .docs-brand .logo-dark { display: none; }
    .docs-brand span {
      display: block;
      color: var(--muted);
      font-size: 12px;
      font-weight: 600;
      margin-top: 2px;
    }
    .docs-search-wrap { padding: 0 24px; }
    .docs-search {
      display: flex;
      align-items: center;
      gap: 10px;
      width: min(520px, 100%);
      height: 38px;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--soft);
      color: var(--muted);
      padding: 0 12px;
      font-size: 14px;
    }
    .docs-search kbd {
      margin-left: auto;
      border: 1px solid var(--border);
      border-radius: 6px;
      color: var(--faint);
      background: var(--panel);
      padding: 2px 6px;
      font: 12px/1.2 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .docs-actions {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 8px;
      padding: 0 24px;
      border-left: 1px solid var(--border);
      height: 64px;
    }
    .docs-lang,
    .docs-icon-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 34px;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--panel);
      color: var(--text);
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
      padding: 0 10px;
      cursor: pointer;
    }
    .docs-icon-btn { width: 34px; padding: 0; }
    .docs-layout {
      display: grid;
      grid-template-columns: 272px minmax(0, 1fr) 224px;
      align-items: start;
      min-height: calc(100vh - 64px);
    }
    .docs-sidebar,
    .docs-toc {
      position: sticky;
      top: 64px;
      height: calc(100vh - 64px);
      overflow: auto;
      padding: 22px 18px 32px;
    }
    .docs-sidebar { border-right: 1px solid var(--border); }
    .docs-toc { border-left: 1px solid var(--border); }
    .docs-nav-title {
      margin: 18px 8px 7px;
      color: var(--faint);
      font-size: 12px;
      font-weight: 800;
    }
    .docs-nav-title:first-child { margin-top: 0; }
    .docs-nav a,
    .docs-toc a {
      display: block;
      padding: 7px 9px;
      border-radius: 7px;
      text-decoration: none;
      color: var(--muted);
      font-size: 14px;
      line-height: 1.35;
    }
    .docs-nav a:hover,
    .docs-toc a:hover {
      color: var(--text);
      background: var(--soft);
    }
    .docs-nav a.active {
      color: var(--accent);
      background: var(--accent-soft);
      font-weight: 700;
    }
    .docs-main {
      width: min(820px, calc(100vw - 496px));
      min-width: 0;
      padding: 44px 40px 80px;
      margin: 0 auto;
    }
    .docs-breadcrumb {
      display: flex;
      gap: 8px;
      align-items: center;
      color: var(--muted);
      font-size: 13px;
      margin-bottom: 18px;
    }
    .docs-breadcrumb a { text-decoration: none; }
    h1 {
      margin: 0 0 14px;
      font-size: 40px;
      line-height: 1.12;
      font-weight: 800;
      letter-spacing: 0;
    }
    .docs-lead {
      max-width: 720px;
      color: var(--muted);
      font-size: 17px;
      line-height: 1.75;
      margin: 0 0 34px;
    }
    .docs-main section {
      padding: 24px 0;
      border-top: 1px solid var(--border);
      scroll-margin-top: 88px;
    }
    .docs-main h2 {
      margin: 0 0 12px;
      font-size: 24px;
      line-height: 1.25;
      font-weight: 800;
    }
    .docs-main h3 {
      margin: 24px 0 10px;
      font-size: 17px;
      line-height: 1.35;
      font-weight: 800;
    }
    .docs-main p,
    .docs-main li {
      color: var(--muted);
      font-size: 15px;
      line-height: 1.75;
    }
    .docs-main ul { padding-left: 22px; }
    .docs-inline {
      display: inline-block;
      padding: 1px 6px;
      border: 1px solid var(--border);
      border-radius: 6px;
      background: var(--soft);
      color: var(--text);
      font: 13px/1.45 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      vertical-align: baseline;
    }
    .docs-callout {
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--soft);
      padding: 14px 16px;
      color: var(--muted);
      font-size: 14px;
      line-height: 1.7;
      margin: 16px 0;
    }
    .docs-code {
      position: relative;
      margin: 16px 0;
      border-radius: 8px;
      background: var(--code-bg);
      color: var(--code-text);
      overflow: auto;
    }
    .docs-code pre {
      margin: 0;
      padding: 18px;
      font: 13px/1.75 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      white-space: pre;
    }
    .docs-table {
      width: 100%;
      border-collapse: collapse;
      margin: 16px 0;
      font-size: 14px;
    }
    .docs-table th,
    .docs-table td {
      border-bottom: 1px solid var(--border);
      padding: 11px 8px;
      text-align: left;
      vertical-align: top;
    }
    .docs-table th { color: var(--text); font-weight: 800; }
    .docs-table td { color: var(--muted); line-height: 1.65; }
    .endpoint {
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--panel);
      margin: 12px 0;
      overflow: hidden;
    }
    .endpoint-top {
      display: flex;
      gap: 10px;
      align-items: center;
      padding: 13px 14px;
      border-bottom: 1px solid var(--border);
      background: var(--soft);
    }
    .method {
      flex: 0 0 auto;
      border-radius: 6px;
      background: #dcfce7;
      color: #166534;
      padding: 3px 8px;
      font-size: 12px;
      font-weight: 800;
    }
    html[data-theme="dark"] .method {
      background: rgba(34, 197, 94, .15);
      color: #86efac;
    }
    .path {
      min-width: 0;
      color: var(--text);
      overflow-wrap: anywhere;
      font: 13px/1.5 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .ability {
      flex: 0 0 auto;
      margin-left: auto;
      border: 1px solid var(--border);
      border-radius: 999px;
      padding: 3px 8px;
      color: var(--muted);
      font-size: 12px;
      font-weight: 700;
      background: var(--panel);
    }
    .endpoint p { margin: 0; padding: 13px 14px; }
    .next-prev {
      display: flex;
      justify-content: space-between;
      gap: 14px;
      margin-top: 34px;
      border-top: 1px solid var(--border);
      padding-top: 22px;
    }
    .next-prev a {
      min-width: 0;
      width: 50%;
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 12px 14px;
      text-decoration: none;
      color: var(--text);
      background: var(--panel);
    }
    .next-prev span {
      display: block;
      color: var(--muted);
      font-size: 12px;
      margin-bottom: 4px;
    }
    .search-popover {
      position: fixed;
      inset: 74px auto auto 296px;
      width: min(520px, calc(100vw - 40px));
      max-height: min(480px, calc(100vh - 110px));
      overflow: auto;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: var(--panel);
      box-shadow: 0 18px 50px rgba(15, 23, 42, .18);
      padding: 8px;
      display: none;
      z-index: 50;
    }
    .search-popover.open { display: block; }
    .search-input {
      width: 100%;
      height: 40px;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--soft);
      color: var(--text);
      outline: none;
      padding: 0 11px;
      font: 14px/1.2 Inter, system-ui, sans-serif;
      margin-bottom: 8px;
    }
    .search-popover a {
      display: block;
      padding: 10px 11px;
      border-radius: 8px;
      text-decoration: none;
    }
    .search-popover strong { display: block; font-size: 14px; }
    .search-popover span { display: block; margin-top: 3px; color: var(--muted); font-size: 12px; line-height: 1.45; }
    .search-empty { color: var(--muted); padding: 14px; font-size: 14px; }
    @media (max-width: 1160px) {
      .docs-top,
      .docs-layout { grid-template-columns: 248px minmax(0, 1fr); }
      .docs-actions { border-left: 0; }
      .docs-toc { display: none; }
      .docs-main { width: min(820px, calc(100vw - 248px)); }
    }
    @media (max-width: 820px) {
      .docs-top {
        grid-template-columns: 1fr auto;
        min-height: auto;
      }
      .docs-brand {
        height: 58px;
        border-right: 0;
        padding: 0 16px;
      }
      .docs-brand img { width: 108px; }
      .docs-search-wrap {
        grid-column: 1 / -1;
        padding: 0 16px 12px;
      }
      .docs-search { width: 100%; }
      .docs-actions {
        height: 58px;
        padding: 0 16px 0 0;
      }
      .docs-layout { display: block; }
      .docs-sidebar {
        position: static;
        height: auto;
        border-right: 0;
        border-bottom: 1px solid var(--border);
        padding: 14px 16px;
      }
      .docs-nav {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 4px;
      }
      .docs-nav-title { display: none; }
      .docs-nav a { white-space: nowrap; }
      .docs-main {
        width: auto;
        padding: 30px 18px 60px;
      }
      h1 { font-size: 32px; }
      .next-prev { display: block; }
      .next-prev a { display: block; width: auto; margin-bottom: 10px; }
      .search-popover {
        inset: 118px 16px auto 16px;
        width: auto;
      }
    }
  </style>
</head>
<body>
  <header class="docs-top">
    <a class="docs-brand" href="{{ route('developers.api-docs') }}" aria-label="Kitobchi API">
      <div>
        <img class="logo-light" src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi">
        <img class="logo-dark" src="{{ asset('images/logo/logo_white.png') }}" alt="Kitobchi">
        <span>Developer API</span>
      </div>
    </a>
    <div class="docs-search-wrap">
      <button class="docs-search" type="button" id="searchButton">
        <span>Hujjatlardan qidirish</span>
        <kbd>⌘K</kbd>
      </button>
    </div>
    <div class="docs-actions">
      <span class="docs-lang">O‘zbekcha</span>
      <button class="docs-icon-btn" id="themeButton" type="button" aria-label="Ko‘rinishni almashtirish">◐</button>
    </div>
  </header>

  <div class="search-popover" id="searchPopover">
    <input class="search-input" id="searchInput" type="search" placeholder="Masalan: authentication, products, xatolar">
    <div id="searchResults"></div>
  </div>

  <div class="docs-layout">
    <aside class="docs-sidebar">
      <nav class="docs-nav" aria-label="API hujjatlari">
        @foreach($groups as $group => $slugs)
          <div class="docs-nav-title">{{ $group }}</div>
          @foreach($slugs as $slug)
            <a href="{{ $routeFor($slug) }}" class="{{ $currentSlug === $slug ? 'active' : '' }}">{{ $pages[$slug]['title'] }}</a>
          @endforeach
        @endforeach
      </nav>
    </aside>

    <main class="docs-main">
      <div class="docs-breadcrumb">
        <a href="{{ route('developers.api-docs') }}">Kitobchi API</a>
        <span>/</span>
        <span>{{ $currentPage['title'] }}</span>
      </div>
      <h1>{{ $currentPage['title'] }}</h1>
      <p class="docs-lead">{{ $currentPage['description'] }}</p>

      @switch($currentSlug)
        @case('authentication')
          <section id="headers">
            <h2>Headerlar</h2>
            <p>Client API har bir so‘rovni ikki credential orqali tekshiradi: <span class="docs-inline">X-App-ID</span> va <span class="docs-inline">X-App-Secret</span>. Bu qiymatlar admin paneldagi API mijozlar bo‘limida beriladi.</p>
            <div class="docs-code"><pre><code>X-App-ID: app_xxxxxxxxxxxx
X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json</code></pre></div>
          </section>
          <section id="secrets">
            <h2>Secret saqlash</h2>
            <p><span class="docs-inline">X-App-Secret</span> frontend ilova, mobil app ichidagi ochiq config yoki Git repositoryga yozilmasligi kerak. Uni faqat server tomonda saqlang va so‘rovlarni serveringiz orqali yuboring.</p>
            <div class="docs-callout">Agar secret oshkor bo‘lsa, admin paneldan client secretni yangilang. Eski secret darhol ishlamay qoladi.</div>
          </section>
          <section id="abilities">
            <h2>Ruxsatlar</h2>
            <p>Hozirgi client endpointlar <span class="docs-inline">read</span> ruxsati bilan ishlaydi. Keyinchalik yozish yoki order bilan bog‘liq endpointlar qo‘shilsa, ular alohida ability talab qiladi.</p>
          </section>
          @break

        @case('rate-limits')
          <section id="limits">
            <h2>Limitlar</h2>
            <p>Har bir client uchun so‘rovlar alohida hisoblanadi. Default limit: <span class="docs-inline">{{ $defaultLimits['per_second'] }}/soniya</span> va <span class="docs-inline">{{ $defaultLimits['per_minute'] }}/daqiqa</span>.</p>
            <table class="docs-table">
              <thead><tr><th>Header</th><th>Maʼnosi</th></tr></thead>
              <tbody>
                <tr><td><span class="docs-inline">X-RateLimit-Limit-Second</span></td><td>Bir soniyada yuborish mumkin bo‘lgan maksimal so‘rovlar soni.</td></tr>
                <tr><td><span class="docs-inline">X-RateLimit-Remaining-Minute</span></td><td>Joriy daqiqada qolgan so‘rovlar soni.</td></tr>
                <tr><td><span class="docs-inline">Retry-After</span></td><td>Limit oshsa, qayta urinib ko‘rishdan oldin kutiladigan vaqt.</td></tr>
              </tbody>
            </table>
          </section>
          <section id="cache">
            <h2>Cache</h2>
            <p>GET so‘rovlar qisqa muddat cache qilinadi. Bir xil client, URL, query va til bilan takroriy so‘rov kelsa, javob cache’dan qaytishi mumkin.</p>
            <div class="docs-code"><pre><code>Cache-Control: private, max-age=120
X-API-Cache: HIT</code></pre></div>
          </section>
          <section id="etag">
            <h2>ETag</h2>
            <p>Client oxirgi javobdagi <span class="docs-inline">ETag</span> qiymatini keyingi so‘rovda <span class="docs-inline">If-None-Match</span> sifatida yuborsa, maʼlumot o‘zgarmagan holatda server <span class="docs-inline">304 Not Modified</span> qaytaradi.</p>
          </section>
          @break

        @case('products')
          <section id="list">
            <h2>Ro‘yxat</h2>
            <p>Mahsulotlar endpointlari katalogni tashqi servisga chiqarish uchun ishlatiladi. Javob ichida mahsulot nomi, narx, rasm, kategoriya, seller va zaxira maʼlumotlari keladi.</p>
            @foreach($endpoints['products'] as $endpoint)
              <x-api-docs-endpoint :endpoint="$endpoint" />
            @endforeach
          </section>
          <section id="recommendation">
            <h2>Tavsiyalar</h2>
            <p><span class="docs-inline">/products/recommendation/{col}</span> endpointi tavsiya qilingan mahsulotlarni qaytaradi. Uni vitrinalar, hamkor kataloglar va “sizga mos” bloklar uchun ishlatish mumkin.</p>
          </section>
          <section id="sellers">
            <h2>Sellerlar</h2>
            <p>Seller endpointlari do‘kon maʼlumotlari, QR orqali seller topish va seller mahsulotlarini sahifalab olish uchun ishlatiladi.</p>
          </section>
          @break

        @case('search')
          <section id="global">
            <h2>Global qidiruv</h2>
            <p>Global qidiruv nom, kategoriya va mahsulot turiga qarab natija qaytaradi. Asosiy query parametri: <span class="docs-inline">q</span>.</p>
            <div class="docs-code"><pre><code>curl --request GET \
  --url '{{ url('/api/v1/client/search?q=python') }}' \
  --header 'Accept: application/json' \
  --header 'X-App-ID: app_xxxxxxxxxxxx' \
  --header 'X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'</code></pre></div>
          </section>
          <section id="categories">
            <h2>Kategoriyalar</h2>
            <x-api-docs-endpoint :endpoint="$endpoints['search'][1]" />
            <p>Bu endpoint katalog menyusi yoki filter panelini qurish uchun ishlatiladi.</p>
          </section>
          <section id="category-products">
            <h2>Kategoriya mahsulotlari</h2>
            <x-api-docs-endpoint :endpoint="$endpoints['search'][2]" />
            <p><span class="docs-inline">type</span> qiymati mahsulot turini bildiradi. Kategoriya ichidagi natijalar sahifalangan bo‘lishi mumkin.</p>
          </section>
          @break

        @case('errors')
          <section id="statuses">
            <h2>Status kodlar</h2>
            <table class="docs-table">
              <thead><tr><th>Status</th><th>Qachon qaytadi</th></tr></thead>
              <tbody>
                <tr><td><span class="docs-inline">401</span></td><td>Credential headerlari yuborilmagan.</td></tr>
                <tr><td><span class="docs-inline">403</span></td><td>Client topilmadi, nofaol, secret noto‘g‘ri yoki ruxsat yetarli emas.</td></tr>
                <tr><td><span class="docs-inline">429</span></td><td>Rate limit oshib ketgan.</td></tr>
                <tr><td><span class="docs-inline">500</span></td><td>Ichki server xatosi. Log orqali tekshiriladi.</td></tr>
              </tbody>
            </table>
          </section>
          <section id="format">
            <h2>Xato formati</h2>
            <div class="docs-code"><pre><code>{
  "status": "error",
  "message": "Invalid or inactive API credentials"
}</code></pre></div>
          </section>
          <section id="checklist">
            <h2>Tekshiruv ro‘yxati</h2>
            <ul>
              <li><span class="docs-inline">X-App-ID</span> va <span class="docs-inline">X-App-Secret</span> to‘g‘ri yuborilganini tekshiring.</li>
              <li>Client admin panelda faol ekanini tekshiring.</li>
              <li>Endpoint uchun kerakli ability clientga berilganini tekshiring.</li>
              <li>So‘rovlar soni limitdan oshmaganini loglardan tekshiring.</li>
            </ul>
          </section>
          @break

        @case('changelog')
          <section id="versioning">
            <h2>Versioning</h2>
            <p>Client API versiyasi URL ichida turadi: <span class="docs-inline">/api/v1/client</span>. Breaking change bo‘lsa, yangi versiya alohida chiqariladi.</p>
          </section>
          <section id="current">
            <h2>Joriy versiya</h2>
            <p><span class="docs-inline">v1</span> hozirgi barqaror versiya. Mahsulotlar, sellerlar, qidiruv va kategoriyalar shu versiyada mavjud.</p>
          </section>
          @break

        @default
          <section id="overview">
            <h2>Umumiy tushuncha</h2>
            <p>Kitobchi Client API hamkor servislar, tashqi kataloglar va marketplace integratsiyalari uchun mo‘ljallangan. API orqali mahsulotlar, sellerlar, kategoriyalar va qidiruv natijalarini olish mumkin.</p>
            <div class="docs-callout">Base URL: <span class="docs-inline">{{ $baseUrl }}</span></div>
          </section>
          <section id="first-request">
            <h2>Birinchi so‘rov</h2>
            <p>Quyidagi misolda kitoblar ro‘yxati olinadi. Real credential qiymatlari admin tomonidan beriladi.</p>
            <div class="docs-code"><pre><code>curl --request GET \
  --url '{{ url('/api/v1/client/products/books') }}' \
  --header 'Accept: application/json' \
  --header 'X-App-ID: app_xxxxxxxxxxxx' \
  --header 'X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'</code></pre></div>
          </section>
          <section id="response">
            <h2>Javob formati</h2>
            <p>Endpointga qarab <span class="docs-inline">data</span>, sahifalash uchun <span class="docs-inline">meta</span> yoki qo‘shimcha bloklar kelishi mumkin. Integratsiyada status code va JSON javobni birga tekshirish tavsiya qilinadi.</p>
            <div class="docs-code"><pre><code>{
  "status": "success",
  "data": []
}</code></pre></div>
          </section>
      @endswitch

      @php
        $orderedSlugs = array_keys($pages);
        $index = array_search($currentSlug, $orderedSlugs, true);
        $prev = $index > 0 ? $orderedSlugs[$index - 1] : null;
        $next = $index !== false && $index < count($orderedSlugs) - 1 ? $orderedSlugs[$index + 1] : null;
      @endphp
      <nav class="next-prev" aria-label="Keyingi va oldingi sahifalar">
        @if($prev)
          <a href="{{ $routeFor($prev) }}"><span>Oldingi</span>{{ $pages[$prev]['title'] }}</a>
        @else
          <span></span>
        @endif
        @if($next)
          <a href="{{ $routeFor($next) }}" style="text-align:right"><span>Keyingi</span>{{ $pages[$next]['title'] }}</a>
        @endif
      </nav>
    </main>

    <aside class="docs-toc">
      <div class="docs-nav-title">Shu sahifada</div>
      @foreach($toc as $item)
        <a href="#{{ $item['id'] }}">{{ $item['label'] }}</a>
      @endforeach
    </aside>
  </div>

  <script>
    window.__docsPages = @json($allPages);
    window.__docsBase = @json(route('developers.api-docs'));
  </script>
  <script>
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('kitobchi_docs_theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      html.dataset.theme = 'dark';
    }

    document.getElementById('themeButton')?.addEventListener('click', () => {
      html.dataset.theme = html.dataset.theme === 'dark' ? 'light' : 'dark';
      localStorage.setItem('kitobchi_docs_theme', html.dataset.theme);
    });

    const button = document.getElementById('searchButton');
    const popover = document.getElementById('searchPopover');
    const input = document.getElementById('searchInput');
    const results = document.getElementById('searchResults');
    const pages = window.__docsPages || [];
    const base = window.__docsBase || '/developers/api';

    function pageUrl(slug) {
      return slug === 'getting-started' ? base : `${base}/${slug}`;
    }

    function renderSearch(query = '') {
      const q = query.trim().toLowerCase();
      const matches = pages.filter((page) => !q || `${page.title} ${page.description}`.toLowerCase().includes(q));
      results.innerHTML = matches.length
        ? matches.map((page) => `<a href="${pageUrl(page.slug)}"><strong>${page.title}</strong><span>${page.description}</span></a>`).join('')
        : '<div class="search-empty">Mos sahifa topilmadi.</div>';
    }

    function openSearch() {
      renderSearch('');
      popover.classList.add('open');
      setTimeout(() => input?.focus(), 20);
    }

    button?.addEventListener('click', openSearch);
    input?.addEventListener('input', () => renderSearch(input.value));
    document.addEventListener('keydown', (event) => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        openSearch();
      }
      if (event.key === 'Escape') {
        popover.classList.remove('open');
      }
    });
    document.addEventListener('click', (event) => {
      if (!popover.contains(event.target) && !button.contains(event.target)) {
        popover.classList.remove('open');
      }
    });
  </script>
</body>
</html>
