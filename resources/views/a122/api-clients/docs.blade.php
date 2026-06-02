@php
  $baseUrl = url('/api/v1/client');
  $sections = [
    ['id' => 'authentication', 'label' => 'Authentication'],
    ['id' => 'rate-limit', 'label' => 'Rate limit'],
    ['id' => 'cache', 'label' => 'Response cache'],
    ['id' => 'endpoints', 'label' => 'Endpoints'],
    ['id' => 'examples', 'label' => 'Examples'],
    ['id' => 'errors', 'label' => 'Errors'],
  ];
  $groups = collect($endpoints)->groupBy(fn ($endpoint) => str_contains($endpoint[1], '/search') ? 'Search API' : 'Products API');
@endphp

<!DOCTYPE html>
<html lang="uz" class="kc-docs-root">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kitobchi Client API Docs</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root {
    color-scheme: light dark;
  }
  body {
    margin: 0;
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background: #ffffff;
  }
  @media (prefers-color-scheme: dark) {
    body { background: #0f172a; }
  }
  .kc-docs-shell {
    --doc-bg: #ffffff;
    --doc-soft: #f6f7fb;
    --doc-border: #e5e7eb;
    --doc-text: #111827;
    --doc-muted: #6b7280;
    --doc-code: #0f172a;
    --doc-accent: #2563eb;
    display: grid;
    grid-template-columns: 256px minmax(0, 1fr) 220px;
    gap: 32px;
    align-items: start;
    max-width: 1320px;
    margin: 0 auto;
    padding: 26px 24px 64px;
  }
  @media (prefers-color-scheme: dark) {
  .kc-docs-shell {
    --doc-bg: #0f172a;
    --doc-soft: #111c33;
    --doc-border: #26344f;
    --doc-text: #e5e7eb;
    --doc-muted: #94a3b8;
    --doc-code: #020617;
    --doc-accent: #60a5fa;
  }
  }
  .kc-docs-side,
  .kc-docs-toc {
    position: sticky;
    top: 92px;
    max-height: calc(100vh - 112px);
    overflow: auto;
    padding-right: 4px;
  }
  .kc-docs-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 0 18px;
    color: var(--doc-text);
    text-decoration: none;
  }
  .kc-docs-brand img { width: 34px; height: 34px; object-fit: contain; }
  .kc-docs-nav-title {
    font-size: 11px;
    font-weight: 800;
    color: var(--doc-muted);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin: 18px 0 8px;
  }
  .kc-docs-nav a,
  .kc-docs-toc a {
    display: block;
    padding: 7px 10px;
    border-radius: 8px;
    color: var(--doc-muted);
    text-decoration: none;
    font-size: 13px;
    line-height: 1.35;
  }
  .kc-docs-nav a:hover,
  .kc-docs-toc a:hover {
    color: var(--doc-accent);
    background: var(--doc-soft);
  }
  .kc-docs-main {
    min-width: 0;
    color: var(--doc-text);
  }
  .kc-docs-hero {
    border-bottom: 1px solid var(--doc-border);
    padding: 20px 0 28px;
    margin-bottom: 28px;
  }
  .kc-docs-eyebrow {
    color: var(--doc-accent);
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 10px;
  }
  .kc-docs-main h1 {
    font-size: clamp(32px, 4vw, 46px);
    font-weight: 800;
    letter-spacing: 0;
    margin: 0 0 12px;
    color: var(--doc-text);
  }
  .kc-docs-lead {
    max-width: 760px;
    color: var(--doc-muted);
    font-size: 17px;
    line-height: 1.7;
    margin: 0;
  }
  .kc-docs-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
  }
  .kc-docs-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 8px 13px;
    border: 1px solid var(--doc-border);
    border-radius: 8px;
    color: var(--doc-text);
    background: var(--doc-bg);
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
  }
  .kc-docs-btn.primary {
    border-color: var(--doc-accent);
    background: var(--doc-accent);
    color: #fff;
  }
  .kc-docs-btn:hover { filter: brightness(.98); }
  .kc-docs-main section {
    border-bottom: 1px solid var(--doc-border);
    padding: 26px 0;
    scroll-margin-top: 96px;
  }
  .kc-docs-main h2 {
    font-size: 25px;
    font-weight: 800;
    color: var(--doc-text);
    margin: 0 0 14px;
  }
  .kc-docs-main h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--doc-text);
    margin: 24px 0 10px;
  }
  .kc-docs-main p,
  .kc-docs-main li {
    color: var(--doc-muted);
    line-height: 1.7;
  }
  .kc-docs-callout {
    border: 1px solid var(--doc-border);
    background: var(--doc-soft);
    border-radius: 10px;
    padding: 14px 16px;
    color: var(--doc-muted);
    margin: 14px 0;
  }
  .kc-docs-code {
    background: var(--doc-code);
    color: #dbeafe;
    border-radius: 10px;
    padding: 16px;
    overflow-x: auto;
    font-size: 13px;
    line-height: 1.7;
    margin: 14px 0;
  }
  .kc-docs-code code { color: inherit; }
  .kc-docs-inline {
    color: var(--doc-text);
    background: var(--doc-soft);
    border: 1px solid var(--doc-border);
    border-radius: 6px;
    padding: 2px 6px;
    font-size: .9em;
  }
  .kc-docs-endpoint {
    border: 1px solid var(--doc-border);
    border-radius: 10px;
    background: var(--doc-bg);
    padding: 14px;
    margin: 10px 0;
  }
  .kc-docs-endpoint-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
  }
  .kc-docs-method {
    background: #dcfce7;
    color: #166534;
    border-radius: 999px;
    padding: 3px 9px;
    font-size: 12px;
    font-weight: 800;
  }
  .dark .kc-docs-method { background: rgba(34, 197, 94, .16); color: #86efac; }
  .kc-docs-path {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 13px;
    color: var(--doc-text);
    overflow-wrap: anywhere;
  }
  .kc-docs-pill {
    background: var(--doc-soft);
    border: 1px solid var(--doc-border);
    color: var(--doc-muted);
    border-radius: 999px;
    padding: 3px 9px;
    font-size: 12px;
    font-weight: 700;
  }
  .kc-docs-table {
    width: 100%;
    border-collapse: collapse;
    margin: 14px 0;
    font-size: 14px;
  }
  .kc-docs-table th,
  .kc-docs-table td {
    border-bottom: 1px solid var(--doc-border);
    padding: 10px 8px;
    vertical-align: top;
  }
  .kc-docs-table th {
    color: var(--doc-text);
    font-weight: 800;
  }
  .kc-docs-table td { color: var(--doc-muted); }
  @media (max-width: 1180px) {
    .kc-docs-shell { grid-template-columns: 220px minmax(0, 1fr); }
    .kc-docs-toc { display: none; }
  }
  @media (max-width: 860px) {
    .kc-docs-shell { display: block; }
    .kc-docs-side { position: static; max-height: none; margin-bottom: 18px; }
  }
</style>
</head>
<body>

<div class="kc-docs-shell">
  <aside class="kc-docs-side">
    <a class="kc-docs-brand" href="{{ url('/developers/api') }}">
      <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi">
      <div>
        <strong>Kitobchi API</strong>
        <div class="text-secondary small">Client docs</div>
      </div>
    </a>

    <div class="kc-docs-nav">
      <div class="kc-docs-nav-title">Guide</div>
      @foreach($sections as $section)
        <a href="#{{ $section['id'] }}">{{ $section['label'] }}</a>
      @endforeach

      <div class="kc-docs-nav-title">API</div>
      @foreach($groups as $group => $items)
        <a href="#{{ \Illuminate\Support\Str::slug($group) }}">{{ $group }}</a>
      @endforeach

      <div class="kc-docs-nav-title">Base URL</div>
      <a href="#authentication">{{ $baseUrl }}</a>
    </div>
  </aside>

  <main class="kc-docs-main">
    <header class="kc-docs-hero">
      <div class="kc-docs-eyebrow">Kitobchi Client API</div>
      <h1>Client API Integration Guide</h1>
      <p class="kc-docs-lead">
        Tashqi marketplace, hamkor servis va katalog integratsiyalari uchun mahsulot, seller va qidiruv API hujjatlari.
        Har bir request alohida client credential orqali tekshiriladi.
      </p>
      <div class="kc-docs-actions">
        <a class="kc-docs-btn primary" href="#authentication">Boshlash</a>
        <a class="kc-docs-btn" href="#endpoints">Endpointlar</a>
        <a class="kc-docs-btn" href="#examples">Namuna requestlar</a>
      </div>
    </header>

    <section id="authentication">
      <h2>Authentication</h2>
      <p>
        Har bir so'rovda <span class="kc-docs-inline">X-App-ID</span> va <span class="kc-docs-inline">X-App-Secret</span>
        headerlari yuboriladi. Client nofaol bo'lsa yoki secret mos kelmasa API javob bermaydi.
      </p>
      <pre class="kc-docs-code"><code>X-App-ID: app_xxxxxxxxxxxx
X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json</code></pre>
      <div class="kc-docs-callout">
        Base URL: <span class="kc-docs-inline">{{ $baseUrl }}</span>
      </div>
    </section>

    <section id="rate-limit">
      <h2>Rate limit</h2>
      <p>
        Har bir API client uchun alohida sekundlik va daqiqalik limit ishlaydi.
        Default limit: <span class="kc-docs-inline">{{ $defaultLimits['per_second'] }}/soniya</span>
        va <span class="kc-docs-inline">{{ $defaultLimits['per_minute'] }}/daqiqa</span>.
      </p>
      <pre class="kc-docs-code"><code>X-RateLimit-Limit-Second: {{ $defaultLimits['per_second'] }}
X-RateLimit-Limit-Minute: {{ $defaultLimits['per_minute'] }}
X-RateLimit-Remaining-Second: 7
X-RateLimit-Remaining-Minute: 239
Retry-After: 1</code></pre>
    </section>

    <section id="cache">
      <h2>Response cache</h2>
      <p>
        GET so'rovlar qisqa muddat cache qilinadi. Bir xil client, URL, query va locale bilan takroriy so'rov kelsa,
        API controller qayta ishlamasdan saqlangan javobni qaytarishi mumkin.
      </p>
      <table class="kc-docs-table">
        <thead>
          <tr><th>Header</th><th>Ma'nosi</th></tr>
        </thead>
        <tbody>
          <tr><td><span class="kc-docs-inline">X-API-Cache: MISS</span></td><td>Javob yangidan hisoblandi va cachega yozildi.</td></tr>
          <tr><td><span class="kc-docs-inline">X-API-Cache: HIT</span></td><td>Javob cachedan qaytdi.</td></tr>
          <tr><td><span class="kc-docs-inline">ETag</span></td><td>Client keyingi so'rovda <span class="kc-docs-inline">If-None-Match</span> yuborsa, o'zgarmagan javob uchun 304 qaytadi.</td></tr>
        </tbody>
      </table>
      <pre class="kc-docs-code"><code>Cache-Control: private, max-age=120
ETag: "e19f4..."
X-API-Cache: HIT</code></pre>
    </section>

    <section id="endpoints">
      <h2>Endpoints</h2>
      @foreach($groups as $group => $items)
        <h3 id="{{ \Illuminate\Support\Str::slug($group) }}">{{ $group }}</h3>
        @foreach($items as [$method, $path, $desc, $ability])
          <div class="kc-docs-endpoint">
            <div class="kc-docs-endpoint-top">
              <span class="kc-docs-method">{{ $method }}</span>
              <span class="kc-docs-path">{{ $path }}</span>
              <span class="kc-docs-pill">{{ $ability }}</span>
            </div>
            <p class="mb-0 mt-2">{{ $desc }}</p>
          </div>
        @endforeach
      @endforeach
    </section>

    <section id="examples">
      <h2>Examples</h2>
      <h3>Products list</h3>
      <pre class="kc-docs-code"><code>curl --request GET \
  --url '{{ url('/api/v1/client/products/books') }}' \
  --header 'Accept: application/json' \
  --header 'X-App-ID: app_xxxxxxxxxxxx' \
  --header 'X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'</code></pre>

      <h3>Search</h3>
      <pre class="kc-docs-code"><code>curl --request GET \
  --url '{{ url('/api/v1/client/search?q=python') }}' \
  --header 'Accept: application/json' \
  --header 'X-App-ID: app_xxxxxxxxxxxx' \
  --header 'X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'</code></pre>

      <h3>Expected response</h3>
      <pre class="kc-docs-code"><code>{
  "status": "success",
  "data": []
}</code></pre>
    </section>

    <section id="errors">
      <h2>Errors</h2>
      <table class="kc-docs-table">
        <thead>
          <tr><th>Status</th><th>Qachon qaytadi</th></tr>
        </thead>
        <tbody>
          <tr><td><span class="kc-docs-inline">401</span></td><td>Credential headerlari yuborilmagan.</td></tr>
          <tr><td><span class="kc-docs-inline">403</span></td><td>Client topilmadi, nofaol, secret noto'g'ri yoki ability yetarli emas.</td></tr>
          <tr><td><span class="kc-docs-inline">429</span></td><td>Rate limit oshib ketgan.</td></tr>
          <tr><td><span class="kc-docs-inline">500</span></td><td>Ichki server xatosi. Hamkor integratsiya mas'uli bilan bog'laning.</td></tr>
        </tbody>
      </table>
      <pre class="kc-docs-code"><code>{
  "status": "error",
  "message": "Invalid or inactive API credentials"
}</code></pre>
    </section>
  </main>

  <aside class="kc-docs-toc">
    <div class="kc-docs-nav-title">On This Page</div>
    @foreach($sections as $section)
      <a href="#{{ $section['id'] }}">{{ $section['label'] }}</a>
    @endforeach
  </aside>
</div>
</body>
</html>
