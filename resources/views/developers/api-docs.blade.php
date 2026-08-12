@php
  $routeFor = fn (string $slug) => $slug === 'getting-started'
    ? route('developers.api-docs')
    : route('developers.api-docs', ['page' => $slug]);
  $allPages = collect($pages)->map(fn ($page, $slug) => [
    'slug' => $slug,
    'title' => $page['title'],
    'description' => $page['description'],
    'group' => $page['group'] ?? 'API',
  ])->values();
@endphp
<!DOCTYPE html>
<html lang="uz" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $currentPage['title'] }} — Kitobchi Developer API</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f8fafc;
      --panel: #ffffff;
      --soft: #f1f5f9;
      --text: #0f172a;
      --muted: #475569;
      --faint: #94a3b8;
      --border: #e2e8f0;
      --accent: #4f46e5;
      --accent-hover: #4338ca;
      --accent-soft: rgba(79, 70, 229, 0.08);
      --code-bg: #0b1021;
      --code-text: #e2e8f0;
      --header-bg: rgba(255, 255, 255, 0.85);
      --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.02);
      color-scheme: light;
    }
    html[data-theme="dark"] {
      --bg: #070a13;
      --panel: #0f172a;
      --soft: #1e293b;
      --text: #f8fafc;
      --muted: #94a3b8;
      --faint: #64748b;
      --border: #1e293b;
      --accent: #6366f1;
      --accent-hover: #818cf8;
      --accent-soft: rgba(99, 102, 241, 0.15);
      --code-bg: #030712;
      --code-text: #e2e8f0;
      --header-bg: rgba(7, 10, 19, 0.85);
      --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
      color-scheme: dark;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }
    a { color: inherit; }

    /* Topbar Header */
    .docs-top {
      position: sticky;
      top: 0;
      z-index: 40;
      display: grid;
      grid-template-columns: 280px minmax(0, 1fr) auto;
      align-items: center;
      height: 64px;
      border-bottom: 1px solid var(--border);
      background: var(--header-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
    }
    .docs-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0 24px;
      text-decoration: none;
      border-right: 1px solid var(--border);
      height: 100%;
    }
    .docs-brand img { width: 110px; height: auto; display: block; }
    html[data-theme="dark"] .docs-brand .logo-light { display: none; }
    html[data-theme="light"] .docs-brand .logo-dark { display: none; }
    .docs-brand-badge {
      background: var(--accent-soft);
      color: var(--accent);
      font-size: 11px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 100px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .docs-search-wrap { padding: 0 24px; }
    .docs-search {
      display: flex;
      align-items: center;
      gap: 10px;
      width: min(480px, 100%);
      height: 40px;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: var(--soft);
      color: var(--muted);
      padding: 0 14px;
      font-size: 13.5px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .docs-search:hover {
      border-color: var(--accent);
      background: var(--panel);
      color: var(--text);
    }
    .docs-search kbd {
      margin-left: auto;
      border: 1px solid var(--border);
      border-radius: 6px;
      color: var(--faint);
      background: var(--panel);
      padding: 2px 7px;
      font: 11px 'JetBrains Mono', monospace;
    }

    .docs-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0 24px;
      height: 100%;
    }
    .docs-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      height: 36px;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--panel);
      color: var(--text);
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      padding: 0 12px;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .docs-btn:hover {
      border-color: var(--accent);
      color: var(--accent);
      transform: translateY(-1px);
    }
    .docs-btn.primary {
      background: var(--accent);
      color: #ffffff;
      border-color: var(--accent);
    }
    .docs-btn.primary:hover {
      background: var(--accent-hover);
      color: #ffffff;
    }

    /* Layout */
    .docs-layout {
      display: grid;
      grid-template-columns: 280px minmax(0, 1fr) 240px;
      align-items: start;
      min-height: calc(100vh - 64px);
    }
    .docs-sidebar, .docs-toc {
      position: sticky;
      top: 64px;
      height: calc(100vh - 64px);
      overflow-y: auto;
      padding: 24px 18px 40px;
    }
    .docs-sidebar { border-right: 1px solid var(--border); background: var(--panel); }
    .docs-toc { border-left: 1px solid var(--border); }

    .docs-nav-group-title {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--faint);
      margin: 20px 10px 8px;
    }
    .docs-nav-group-title:first-child { margin-top: 0; }
    .docs-nav a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border-radius: 8px;
      text-decoration: none;
      color: var(--muted);
      font-size: 13.5px;
      font-weight: 500;
      transition: all 0.15s ease;
    }
    .docs-nav a:hover {
      color: var(--text);
      background: var(--soft);
    }
    .docs-nav a.active {
      color: var(--accent);
      background: var(--accent-soft);
      font-weight: 700;
      border-left: 3px solid var(--accent);
      border-top-left-radius: 2px;
      border-bottom-left-radius: 2px;
    }

    .docs-toc a {
      display: block;
      padding: 6px 10px;
      border-radius: 6px;
      text-decoration: none;
      color: var(--muted);
      font-size: 13px;
      transition: all 0.15s ease;
    }
    .docs-toc a:hover {
      color: var(--text);
      background: var(--soft);
    }

    /* Main Content */
    .docs-main {
      width: min(860px, calc(100vw - 520px));
      min-width: 0;
      padding: 40px 48px 80px;
      margin: 0 auto;
    }
    .docs-breadcrumb {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--muted);
      font-size: 13px;
      margin-bottom: 20px;
    }
    .docs-breadcrumb a { text-decoration: none; font-weight: 500; }
    .docs-breadcrumb a:hover { color: var(--accent); }

    h1 {
      margin: 0 0 14px;
      font-size: 38px;
      font-weight: 800;
      letter-spacing: -0.02em;
      line-height: 1.15;
    }
    .docs-lead {
      color: var(--muted);
      font-size: 17px;
      line-height: 1.7;
      margin: 0 0 36px;
    }

    .docs-main section {
      padding: 28px 0;
      border-top: 1px solid var(--border);
      scroll-margin-top: 84px;
    }
    .docs-main h2 {
      margin: 0 0 14px;
      font-size: 24px;
      font-weight: 800;
      letter-spacing: -0.01em;
    }
    .docs-main h3 {
      margin: 24px 0 10px;
      font-size: 17px;
      font-weight: 700;
    }
    .docs-main p, .docs-main li {
      color: var(--muted);
      font-size: 15px;
      line-height: 1.7;
    }
    .docs-main ul { padding-left: 20px; }

    .docs-inline {
      padding: 2px 7px;
      border: 1px solid var(--border);
      border-radius: 6px;
      background: var(--soft);
      color: var(--text);
      font: 12.5px 'JetBrains Mono', monospace;
    }

    .docs-callout {
      border: 1px solid var(--border);
      border-left: 4px solid var(--accent);
      border-radius: 10px;
      background: var(--panel);
      box-shadow: var(--card-shadow);
      padding: 16px 20px;
      color: var(--muted);
      font-size: 14.5px;
      line-height: 1.65;
      margin: 20px 0;
    }

    .docs-code {
      position: relative;
      margin: 16px 0;
      border-radius: 10px;
      background: var(--code-bg);
      color: var(--code-text);
      overflow: hidden;
      box-shadow: var(--card-shadow);
    }
    .docs-code pre {
      margin: 0;
      padding: 18px 20px;
      font: 13px/1.7 'JetBrains Mono', monospace;
      white-space: pre;
      overflow-x: auto;
    }

    .docs-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
      font-size: 13.5px;
      background: var(--panel);
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid var(--border);
      box-shadow: var(--card-shadow);
    }
    .docs-table th, .docs-table td {
      padding: 12px 16px;
      border-bottom: 1px solid var(--border);
      text-align: left;
      vertical-align: top;
    }
    .docs-table th {
      background: var(--soft);
      color: var(--text);
      font-weight: 700;
    }
    .docs-table td { color: var(--muted); line-height: 1.6; }
    .docs-table tr:last-child td { border-bottom: none; }

    .type-chip {
      font-family: 'JetBrains Mono', monospace;
      font-size: 11.5px;
      padding: 2px 6px;
      border-radius: 4px;
      background: rgba(79, 70, 229, 0.1);
      color: var(--accent);
      font-weight: 600;
    }

    /* Endpoint Card Modern Styling */
    .endpoint {
      border: 1px solid var(--border);
      border-radius: 14px;
      background: var(--panel);
      margin: 24px 0;
      overflow: hidden;
      box-shadow: var(--card-shadow);
      scroll-margin-top: 84px;
    }
    .endpoint-top {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 18px;
      border-bottom: 1px solid var(--border);
      background: var(--soft);
    }
    .method {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 58px;
      height: 28px;
      border-radius: 7px;
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.04em;
    }
    .method--GET { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .method--POST { background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
    .method--PUT, .method--PATCH { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .method--DELETE { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    html[data-theme="dark"] .method--GET { background: rgba(34, 197, 94, 0.16); color: #4ade80; border-color: rgba(34, 197, 94, 0.3); }
    html[data-theme="dark"] .method--POST { background: rgba(99, 102, 241, 0.18); color: #a5b4fc; border-color: rgba(99, 102, 241, 0.3); }
    html[data-theme="dark"] .method--PUT, html[data-theme="dark"] .method--PATCH { background: rgba(245, 158, 11, 0.16); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3); }
    html[data-theme="dark"] .method--DELETE { background: rgba(239, 68, 68, 0.16); color: #f87171; border-color: rgba(239, 68, 68, 0.3); }

    .path {
      font-family: 'JetBrains Mono', monospace;
      font-size: 13.5px;
      font-weight: 600;
      color: var(--text);
      word-break: break-all;
    }
    .ability {
      margin-left: auto;
      border: 1px solid var(--border);
      border-radius: 100px;
      padding: 3px 10px;
      color: var(--muted);
      font-size: 11.5px;
      font-weight: 700;
      background: var(--panel);
    }
    .ability--stock-write {
      background: #fffbeb;
      color: #b45309;
      border-color: #fde68a;
    }
    html[data-theme="dark"] .ability--stock-write {
      background: rgba(245, 158, 11, 0.15);
      color: #fbbf24;
      border-color: rgba(245, 158, 11, 0.3);
    }

    .endpoint-body { padding: 20px; }
    .endpoint-title { margin: 0 0 6px; font-size: 18px; font-weight: 800; }
    .endpoint-summary { margin: 0 0 16px; color: var(--muted); font-size: 14.5px; line-height: 1.6; }

    /* Code Tabs */
    .code-tabs {
      margin: 18px 0;
      border: 1px solid var(--border);
      border-radius: 10px;
      overflow: hidden;
      background: var(--code-bg);
    }
    .code-tabs-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 8px 12px;
      background: rgba(255, 255, 255, 0.05);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .code-tabs-langs { display: flex; gap: 4px; }
    .code-tab {
      border: none;
      background: transparent;
      color: #94a3b8;
      font: 600 12.5px 'Inter', sans-serif;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .code-tab:hover { color: #f8fafc; }
    .code-tab.active {
      background: rgba(255, 255, 255, 0.15);
      color: #ffffff;
    }
    .code-copy {
      border: 1px solid rgba(255, 255, 255, 0.15);
      background: rgba(255, 255, 255, 0.08);
      color: #cbd5e1;
      border-radius: 6px;
      padding: 5px 12px;
      font: 600 12px 'Inter', sans-serif;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .code-copy:hover { color: #ffffff; background: rgba(255, 255, 255, 0.2); }
    .code-copy.copied { color: #4ade80; border-color: #4ade80; }

    .code-pane { display: none; }
    .code-pane.active { display: block; }
    .code-pane pre {
      margin: 0;
      padding: 18px 20px;
      color: var(--code-text);
      font: 13px/1.7 'JetBrains Mono', monospace;
      overflow-x: auto;
    }

    .endpoint-response-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 18px 0 8px;
    }
    .endpoint-response-head span { font-size: 13.5px; font-weight: 700; color: var(--text); }
    .endpoint-response-head em {
      font-style: normal;
      background: rgba(34, 197, 94, 0.12);
      color: #16a34a;
      font-weight: 700;
      font-size: 11.5px;
      padding: 2px 8px;
      border-radius: 100px;
      margin-left: 8px;
    }

    /* Try It Out Sandbox */
    .try-it {
      margin-top: 20px;
      border: 1px dashed var(--border);
      border-radius: 10px;
      background: var(--soft);
      overflow: hidden;
    }
    .try-it > summary {
      padding: 12px 18px;
      font-weight: 700;
      font-size: 13.5px;
      color: var(--accent);
      cursor: pointer;
      user-select: none;
    }
    .try-it-body { padding: 0 18px 18px; }
    .try-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
      margin-bottom: 14px;
    }
    .try-grid label {
      display: flex;
      flex-direction: column;
      gap: 5px;
      font-size: 12px;
      font-weight: 700;
      color: var(--muted);
    }
    .try-grid input {
      height: 38px;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--panel);
      color: var(--text);
      padding: 0 12px;
      font: 13px 'JetBrains Mono', monospace;
      outline: none;
    }
    .try-send {
      border: none;
      background: var(--accent);
      color: #ffffff;
      border-radius: 8px;
      padding: 10px 18px;
      font: 700 13px 'Inter', sans-serif;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .try-send:hover { background: var(--accent-hover); }
    .try-status { margin-top: 10px; font-size: 13px; font-weight: 700; }
    .try-result {
      margin-top: 10px;
      max-height: 360px;
      overflow: auto;
      background: var(--code-bg);
      color: var(--code-text);
      border-radius: 8px;
      padding: 16px;
      font: 12.5px/1.6 'JetBrains Mono', monospace;
    }

    /* Next / Prev Navigation */
    .next-prev {
      display: flex;
      gap: 16px;
      margin-top: 40px;
      padding-top: 24px;
      border-top: 1px solid var(--border);
    }
    .next-prev a {
      flex: 1;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 16px;
      text-decoration: none;
      background: var(--panel);
      box-shadow: var(--card-shadow);
      transition: all 0.15s ease;
    }
    .next-prev a:hover {
      border-color: var(--accent);
      transform: translateY(-2px);
    }
    .next-prev span {
      display: block;
      color: var(--muted);
      font-size: 12px;
      margin-bottom: 4px;
      font-weight: 500;
    }
    .next-prev strong {
      display: block;
      color: var(--text);
      font-size: 15px;
      font-weight: 700;
    }

    /* Search Popover Dialog */
    .search-popover {
      position: fixed;
      inset: 80px auto auto 50%;
      transform: translateX(-50%);
      width: min(560px, calc(100vw - 32px));
      max-height: min(500px, calc(100vh - 120px));
      overflow-y: auto;
      border: 1px solid var(--border);
      border-radius: 14px;
      background: var(--panel);
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
      padding: 12px;
      display: none;
      z-index: 60;
    }
    .search-popover.open { display: block; }
    .search-input {
      width: 100%;
      height: 44px;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: var(--soft);
      color: var(--text);
      outline: none;
      padding: 0 14px;
      font: 14px 'Inter', sans-serif;
      margin-bottom: 10px;
    }
    .search-popover a {
      display: block;
      padding: 12px;
      border-radius: 8px;
      text-decoration: none;
      transition: background 0.15s;
    }
    .search-popover a:hover { background: var(--soft); }
    .search-popover strong { display: block; font-size: 14.5px; color: var(--text); }
    .search-popover span { display: block; margin-top: 4px; color: var(--muted); font-size: 12.5px; }

    @media (max-width: 1180px) {
      .docs-top, .docs-layout { grid-template-columns: 250px minmax(0, 1fr); }
      .docs-toc { display: none; }
      .docs-main { width: min(860px, calc(100vw - 250px)); }
    }
    @media (max-width: 820px) {
      .docs-top { grid-template-columns: 1fr auto; }
      .docs-brand { border-right: none; }
      .docs-search-wrap { grid-column: 1 / -1; padding-bottom: 12px; }
      .docs-layout { display: block; }
      .docs-sidebar { position: static; height: auto; border-right: none; border-bottom: 1px solid var(--border); }
      .docs-nav { display: flex; gap: 6px; overflow-x: auto; }
      .docs-nav-group-title { display: none; }
      .docs-main { width: auto; padding: 24px 18px 60px; }
      h1 { font-size: 30px; }
      .next-prev { flex-direction: column; }
    }
  </style>
</head>
<body>
  <!-- Top Navigation Header -->
  <header class="docs-top">
    <a class="docs-brand" href="{{ route('developers.api-docs') }}" aria-label="Kitobchi Developer API">
      <img class="logo-light" src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi">
      <img class="logo-dark" src="{{ asset('images/logo/logo_white.png') }}" alt="Kitobchi">
      <span class="docs-brand-badge">Developer API</span>
    </a>

    <div class="docs-search-wrap">
      <button class="docs-search" type="button" id="searchButton">
        <span>Hujjatlardan qidirish...</span>
        <kbd>⌘K</kbd>
      </button>
    </div>

    <div class="docs-actions">
      <a class="docs-btn" href="{{ $openapiUrl }}" target="_blank" rel="noopener" title="OpenAPI (JSON) Spec">
        <span>OpenAPI</span>
      </a>
      <button class="docs-btn" id="copyMdButton" type="button" title="Sahifani LLM uchun Markdown ko'rinishida nusxalash">
        <span>Markdown</span>
      </button>
      <button class="docs-btn" id="themeButton" type="button" aria-label="Mavzuni o'zgartirish">
        <span>◐</span>
      </button>
      <a class="docs-btn primary" href="{{ url('/') }}">
        <span>Kitobchi.uz &rarr;</span>
      </a>
    </div>
  </header>

  <!-- Spotlight Search Popover -->
  <div class="search-popover" id="searchPopover">
    <input class="search-input" id="searchInput" type="search" placeholder="Masalan: authentication, products, xatolar...">
    <div id="searchResults"></div>
  </div>

  <!-- Main Layout Grid -->
  <div class="docs-layout">
    <!-- Left Navigation Sidebar -->
    <aside class="docs-sidebar">
      <nav class="docs-nav" aria-label="API Navigatsiyasi">
        @foreach($groups as $group => $slugs)
          <div class="docs-nav-group-title">{{ $group }}</div>
          @foreach($slugs as $slug)
            <a href="{{ $routeFor($slug) }}" class="{{ $currentSlug === $slug ? 'active' : '' }}">
              <span>{{ $pages[$slug]['title'] }}</span>
            </a>
          @endforeach
        @endforeach
      </nav>
    </aside>

    <!-- Center Main Article -->
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
            <p>Client API har bir so‘rovni ikki credential header orqali autentifikatsiya qiladi: <span class="docs-inline">X-App-ID</span> va <span class="docs-inline">X-App-Secret</span>.</p>
            <div class="docs-code">
              <pre><code>X-App-ID: app_xxxxxxxxxxxx
X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json</code></pre>
            </div>
          </section>
          <section id="secrets">
            <h2>Secret xavfsizligi</h2>
            <p><span class="docs-inline">X-App-Secret</span> kalitini frontend JavaScript kodingizda yoki ommaviy omborda saqlamang. Barcha so‘rovlar serveringiz orqali yuborilishi kerak.</p>
            <div class="docs-callout">
              <strong>Xavfsizlik maslahati:</strong> Secret oshkor bo'lsa, Boshqaruv paneli orqali yangisiga almashtiring. Eski secret darhol bekor qilinadi.
            </div>
          </section>
          <section id="abilities">
            <h2>Ruxsatlar (abilities)</h2>
            <p>Ommaviy katalog uchun <span class="docs-inline">read</span> ruxsati, Seller API (zaxira yozish) uchun esa <span class="docs-inline">stock:write</span> ruxsati talab qilinadi.</p>
            <table class="docs-table">
              <thead><tr><th>Ability</th><th>Qamrovi</th></tr></thead>
              <tbody>
                <tr><td><span class="type-chip">read</span></td><td>Ommaviy katalog, mahsulotlar va qidiruv (read-only).</td></tr>
                <tr><td><span class="type-chip">stock:write</span></td><td>Seller o'z do'konidagi zaxirani ISBN/shtrix-kod bo'yicha yangilash.</td></tr>
              </tbody>
            </table>
          </section>
          @break

        @case('rate-limits')
          <section id="limits">
            <h2>So'rovlar Limiti</h2>
            <p>Har bir client uchun default limit: <span class="docs-inline">{{ $defaultLimits['per_second'] }}/sec</span> va <span class="docs-inline">{{ $defaultLimits['per_minute'] }}/min</span>.</p>
            <table class="docs-table">
              <thead><tr><th>Header</th><th>Ma'nosi</th></tr></thead>
              <tbody>
                <tr><td><span class="docs-inline">X-RateLimit-Limit-Second</span></td><td>Bir soniyadagi maksimal so'rovlar soni.</td></tr>
                <tr><td><span class="docs-inline">X-RateLimit-Remaining-Minute</span></td><td>Daqiqadagi qolgan so'rovlar kvotasi.</td></tr>
                <tr><td><span class="docs-inline">Retry-After</span></td><td>Limit oshganda kutish soniyalari (HTTP 429).</td></tr>
              </tbody>
            </table>
          </section>
          <section id="cache">
            <h2>Kesh va ETag</h2>
            <p>GET so'rovlari avtomatik ravishda 120 soniya keshlanadi. Javoblardagi <span class="docs-inline">ETag</span> orqali 304 Not Modified statusini olish mumkin.</p>
          </section>
          @break

        @case('products')
          <section>
            <h2>Mahsulotlar API</h2>
            <p>Katalogdagi barcha kitoblar, kanselyariyalar va sellerlar ro'yxatini olish uchun endpointlar.</p>
          </section>
          @foreach($pageEndpoints as $endpoint)
            <x-api-docs-endpoint :endpoint="$endpoint" />
          @endforeach
          @break

        @case('search')
          <section>
            <h2>Qidiruv API</h2>
            <p>Global qidiruv, avtomatik to'ldirish (autocomplete) va trenddagi so'rovlar.</p>
          </section>
          @foreach($pageEndpoints as $endpoint)
            <x-api-docs-endpoint :endpoint="$endpoint" />
          @endforeach
          @break

        @case('seller')
          <section>
            <h2>Seller API (Zaxira Yozish)</h2>
            <p>Do'kon zaxirangizni ISBN va shtrix-kodlar bo'yicha real vaqt rejimida avtomatik yangilang.</p>
          </section>
          @foreach($pageEndpoints as $endpoint)
            <x-api-docs-endpoint :endpoint="$endpoint" />
          @endforeach
          @break

        @case('pagination')
          <section id="params">
            <h2>Sahifalash Parametrlari</h2>
            <table class="docs-table">
              <thead><tr><th>Parametr</th><th>Turi</th><th>Izoh</th></tr></thead>
              <tbody>
                <tr><td><span class="docs-inline">page</span></td><td><span class="type-chip">integer</span></td><td>Sahifa raqami (default: 1)</td></tr>
                <tr><td><span class="docs-inline">q</span></td><td><span class="type-chip">string</span></td><td>Qidiruv matni bo'yicha filtr</td></tr>
                <tr><td><span class="docs-inline">sort</span></td><td><span class="type-chip">string</span></td><td>popular, new, price_asc, price_desc</td></tr>
              </tbody>
            </table>
          </section>
          @break

        @case('webhooks')
          <section id="events">
            <h2>Webhook Hodisalari</h2>
            <p>Zaxira yoki narx o'zgarganda serveringizga yuboriladigan avtomatik bildirishnomalar.</p>
            <table class="docs-table">
              <thead><tr><th>Hodisa</th><th>Tavsifi</th></tr></thead>
              <tbody>
                <tr><td><span class="docs-inline">product.updated</span></td><td>Mahsulot ma'lumoti yoki narxi o'zgarganda.</td></tr>
                <tr><td><span class="docs-inline">product.stock_changed</span></td><td>Zaxira miqdori o'zgarganda.</td></tr>
              </tbody>
            </table>
          </section>
          @break

        @case('errors')
          <section id="statuses">
            <h2>Status Kodlar va Xatolar</h2>
            <table class="docs-table">
              <thead><tr><th>Code</th><th>Ma'nosi</th></tr></thead>
              <tbody>
                <tr><td><span class="type-chip">401</span></td><td>Headerlar yoki API Secret mavjud emas</td></tr>
                <tr><td><span class="type-chip">403</span></td><td>Ruxsat berilmagan yoki IP allowlistdan tashqari so'rov</td></tr>
                <tr><td><span class="type-chip">429</span></td><td>Rate limit oshib ketdi</td></tr>
              </tbody>
            </table>
          </section>
          @break

        @default
          <section id="overview">
            <h2>Umumiy tushuncha</h2>
            <p>Kitobchi Client API — bu hamkor va do'kon integratsiyalari uchun mo'ljallangan zamonaviy REST API ekotizimi.</p>
            <div class="docs-callout">
              Base URL: <span class="docs-inline">{{ $baseUrl }}</span>
            </div>
          </section>
          <section id="first-request">
            <h2>Birinchi so'rov yuborish</h2>
            <div class="docs-code">
              <pre><code>curl --request GET \
  --url '{{ url('/api/v1/client/products/books') }}' \
  --header 'Accept: application/json' \
  --header 'X-App-ID: app_xxxxxxxxxxxx' \
  --header 'X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'</code></pre>
            </div>
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
          <a href="{{ $routeFor($prev) }}">
            <span>&larr; Oldingi sahifa</span>
            <strong>{{ $pages[$prev]['title'] }}</strong>
          </a>
        @else
          <div></div>
        @endif
        @if($next)
          <a href="{{ $routeFor($next) }}" style="text-align: right">
            <span>Keyingi sahifa &rarr;</span>
            <strong>{{ $pages[$next]['title'] }}</strong>
          </a>
        @endif
      </nav>
    </main>

    <!-- Right Side Page Table of Contents -->
    <aside class="docs-toc">
      <div class="docs-nav-group-title">Shu sahifada</div>
      @foreach($toc as $item)
        <a href="#{{ $item['id'] }}">{{ $item['label'] }}</a>
      @endforeach
    </aside>
  </div>

  <script>
    window.__docsPages = @json($allPages);
    window.__docsBase = @json(route('developers.api-docs'));

    // Dark/Light Theme Switching
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('kitobchi_docs_theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      html.dataset.theme = 'dark';
    }

    document.getElementById('themeButton')?.addEventListener('click', () => {
      html.dataset.theme = html.dataset.theme === 'dark' ? 'light' : 'dark';
      localStorage.setItem('kitobchi_docs_theme', html.dataset.theme);
    });

    // Spotlight Search Modal
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
      const matches = pages.filter((page) => !q || `${page.title} ${page.description} ${page.group}`.toLowerCase().includes(q));
      results.innerHTML = matches.length
        ? matches.map((page) => `<a href="${pageUrl(page.slug)}"><strong>${page.title}</strong><span>${page.description}</span></a>`).join('')
        : '<div class="search-empty" style="color:var(--muted);padding:12px;">Mos sahifa topilmadi.</div>';
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

    // Code Language Tabs Switching
    document.addEventListener('click', (event) => {
      const tab = event.target.closest('.code-tab');
      if (!tab) return;
      const wrap = tab.closest('.code-tabs');
      const target = tab.dataset.tab;
      wrap.querySelectorAll('.code-tab').forEach((t) => t.classList.toggle('active', t === tab));
      wrap.querySelectorAll('.code-pane').forEach((p) => p.classList.toggle('active', p.dataset.pane === target));
    });

    // Clipboard Copy Action
    document.addEventListener('click', async (event) => {
      const btn = event.target.closest('.code-copy');
      if (!btn) return;
      let text = '';
      const tabs = btn.closest('.code-tabs');
      const resp = btn.closest('.endpoint-response');
      if (tabs) {
        const pane = tabs.querySelector('.code-pane.active') || tabs.querySelector('.code-pane');
        text = pane ? pane.innerText : '';
      } else if (resp) {
        const code = resp.querySelector('code');
        text = code ? code.innerText : '';
      }
      try {
        await navigator.clipboard.writeText(text);
        const original = btn.textContent;
        btn.classList.add('copied');
        btn.textContent = '✓ Nusxalandi';
        setTimeout(() => { btn.classList.remove('copied'); btn.textContent = original; }, 1200);
      } catch (_) {}
    });

    // Try It Out API Executor
    document.addEventListener('click', async (event) => {
      const send = event.target.closest('.try-send');
      if (!send) return;
      const root = send.closest('[data-tryit]');
      let url = root.dataset.urlTemplate || '';
      const appid = (root.querySelector('[data-try="appid"]').value || '').trim();
      const secret = (root.querySelector('[data-try="secret"]').value || '').trim();
      root.querySelectorAll('[data-try-path]').forEach((inp) => {
        url = url.replace('{' + inp.dataset.tryPath + '}', encodeURIComponent((inp.value || '').trim()));
      });
      const qs = [];
      root.querySelectorAll('[data-try-query]').forEach((inp) => {
        const v = (inp.value || '').trim();
        if (v !== '') qs.push(encodeURIComponent(inp.dataset.tryQuery) + '=' + encodeURIComponent(v));
      });
      if (qs.length) url += (url.includes('?') ? '&' : '?') + qs.join('&');

      const statusEl = root.querySelector('[data-try-status]');
      const resultEl = root.querySelector('[data-try-result]');
      statusEl.textContent = 'Yuborilmoqda…';
      statusEl.style.color = 'var(--muted)';
      send.disabled = true;
      const startTime = performance.now();
      try {
        const res = await fetch(url, {
          headers: { 'Accept': 'application/json', 'X-App-ID': appid, 'X-App-Secret': secret },
        });
        const duration = Math.round(performance.now() - startTime);
        const txt = await res.text();
        let body = txt;
        try { body = JSON.stringify(JSON.parse(txt), null, 2); } catch (_) {}
        statusEl.textContent = `${res.status} ${res.statusText} • ${duration}ms`;
        statusEl.style.color = res.ok ? '#16a34a' : '#dc2626';
        resultEl.textContent = body;
        resultEl.hidden = false;
      } catch (err) {
        statusEl.textContent = 'Xato: ' + err.message;
        statusEl.style.color = '#dc2626';
        resultEl.hidden = true;
      } finally {
        send.disabled = false;
      }
    });

    // LLM Markdown Exporter
    document.getElementById('copyMdButton')?.addEventListener('click', async () => {
      const h1 = document.querySelector('.docs-main h1')?.innerText || 'Kitobchi API';
      const lead = document.querySelector('.docs-lead')?.innerText || '';
      let md = `# ${h1}\n\n${lead}\n`;
      document.querySelectorAll('.docs-main .endpoint').forEach((ep) => {
        const method = ep.querySelector('.method')?.innerText || '';
        const path = ep.querySelector('.path')?.innerText || '';
        const summary = ep.querySelector('.endpoint-summary')?.innerText || '';
        md += `\n## ${method} ${path}\n${summary}\n`;
        const active = ep.querySelector('.code-pane.active code');
        if (active) md += '\n```\n' + active.innerText + '\n```\n';
        const resp = ep.querySelector('.response-code code');
        if (resp) md += '\nJavob:\n```json\n' + resp.innerText + '\n```\n';
      });
      try {
        await navigator.clipboard.writeText(md);
        const b = document.getElementById('copyMdButton');
        const original = b.textContent;
        b.textContent = '✓ Nusxalandi';
        setTimeout(() => { b.textContent = original; }, 1200);
      } catch (_) {}
    });
  </script>
</body>
</html>
