@php
  $routeFor = fn (string $slug) => $slug === 'getting-started'
    ? route('developers.api-docs')
    : route('developers.api-docs', ['page' => $slug]);

  $allPages = collect($pages)->map(fn ($page, $slug) => [
    'slug'        => $slug,
    'title'       => $page['title'],
    'description' => $page['description'],
    'group'       => $page['group'] ?? 'API',
  ])->values();

  // Method badge'ni sidebar uchun endpoint guruhlari
  $epGroups = ['products', 'search', 'seller', 'deeplink'];
@endphp
<!DOCTYPE html>
<html lang="uz" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $currentPage['title'] }} — Kitobchi API Reference</title>
  <meta name="description" content="{{ $currentPage['description'] }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300;0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;1,14..32,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    /* ── Tokens ─────────────────────────────────────────────────── */
    :root {
      --white:       #ffffff;
      --bg:          #f9fafb;
      --bg-hover:    #f3f4f6;
      --panel:       #ffffff;
      --border:      rgba(0,0,0,0.07);
      --border-med:  rgba(0,0,0,0.10);
      --text:        #111827;
      --text-2:      #374151;
      --muted:       #6b7280;
      --faint:       #9ca3af;
      --accent:      #18181b;
      --link:        #2563eb;
      --link-hover:  #1d4ed8;
      --code-bg:     #18181b;
      --code-text:   #e5e7eb;
      --sidebar-w:   252px;
      --toc-w:       200px;
      --header-h:    54px;
      color-scheme: light;
    }
    html[data-theme="dark"] {
      --bg:          #0a0b0d;
      --bg-hover:    #111318;
      --panel:       #111318;
      --border:      rgba(255,255,255,0.07);
      --border-med:  rgba(255,255,255,0.10);
      --text:        #f4f4f5;
      --text-2:      #d1d5db;
      --muted:       #9ca3af;
      --faint:       #6b7280;
      --accent:      #f4f4f5;
      --link:        #60a5fa;
      --link-hover:  #93c5fd;
      --code-bg:     #0d0d0f;
      --code-text:   #d4d4d8;
      color-scheme: dark;
    }

    /* ── Reset ──────────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      font-size: 14px;
      line-height: 1.6;
      background: var(--bg);
      color: var(--text);
      -webkit-font-smoothing: antialiased;
    }
    a { color: inherit; text-decoration: none; }
    button { font-family: inherit; cursor: pointer; }

    /* ── Scrollbar ──────────────────────────────────────────────── */
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-med); border-radius: 99px; }

    /* ── Header ─────────────────────────────────────────────────── */
    .hd {
      position: sticky; top: 0; z-index: 50;
      height: var(--header-h);
      display: flex; align-items: center;
      background: color-mix(in srgb, var(--panel) 90%, transparent);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
    }
    .hd-brand {
      width: var(--sidebar-w);
      display: flex; align-items: center; gap: 9px;
      padding: 0 20px; flex-shrink: 0;
      border-right: 1px solid var(--border); height: 100%;
    }
    .hd-brand img { height: 26px; width: 26px; border-radius: 8px; display: block; }
    .hd-ver {
      font-size: 11px; font-weight: 600; color: var(--faint);
      padding: 2px 6px; border-radius: 4px;
      border: 1px solid var(--border); background: var(--bg);
    }
    .hd-center { flex: 1; padding: 0 20px; }
    .hd-search {
      display: flex; align-items: center; gap: 8px;
      height: 34px; max-width: 380px;
      border: 1px solid var(--border-med); border-radius: 8px;
      background: var(--bg); color: var(--muted);
      padding: 0 12px; font-size: 13px; font-family: inherit;
      transition: border-color 0.15s;
    }
    .hd-search:hover { border-color: var(--faint); }
    .hd-search svg { flex-shrink: 0; }
    .hd-search .kbds { margin-left: auto; display: flex; gap: 3px; }
    .hd-search kbd {
      font-family: 'JetBrains Mono', monospace; font-size: 10px;
      padding: 1px 5px; border-radius: 4px;
      border: 1px solid var(--border-med); background: var(--panel);
      color: var(--faint);
    }
    .hd-right {
      display: flex; align-items: center; gap: 6px; padding: 0 20px;
    }
    .hd-btn {
      display: inline-flex; align-items: center; gap: 5px;
      height: 32px; padding: 0 11px; border-radius: 7px;
      border: 1px solid var(--border-med); background: var(--panel);
      color: var(--text-2); font-size: 12.5px; font-weight: 500;
      transition: all 0.12s;
    }
    .hd-btn:hover { border-color: var(--faint); color: var(--text); }
    .hd-btn-icon { width: 32px; padding: 0; justify-content: center; }
    .hd-btn-primary {
      background: var(--accent); color: var(--white);
      border-color: transparent;
    }
    html[data-theme="dark"] .hd-btn-primary { color: var(--bg); }
    .hd-btn-primary:hover { opacity: 0.88; color: var(--white); }
    html[data-theme="dark"] .hd-btn-primary:hover { color: var(--bg); }

    /* ── Layout ─────────────────────────────────────────────────── */
    .layout {
      display: grid;
      grid-template-columns: var(--sidebar-w) minmax(0,1fr) var(--toc-w);
      min-height: calc(100vh - var(--header-h));
    }

    /* ── Sidebar ─────────────────────────────────────────────────── */
    .sidebar {
      position: sticky; top: var(--header-h);
      height: calc(100vh - var(--header-h));
      overflow-y: auto; padding: 16px 0 40px;
      border-right: 1px solid var(--border);
    }
    .nav-group { margin-bottom: 4px; }
    .nav-group-title {
      display: block;
      font-size: 11px; font-weight: 600;
      letter-spacing: 0.04em; text-transform: uppercase;
      color: var(--faint); padding: 10px 16px 4px;
    }
    .nav-item {
      display: flex; align-items: center; gap: 8px;
      padding: 5px 16px; color: var(--muted); font-size: 13.5px;
      transition: color 0.12s, background 0.12s; border-radius: 0;
    }
    .nav-item:hover { color: var(--text); background: var(--bg-hover); }
    .nav-item.active {
      color: var(--text); font-weight: 500;
      background: var(--bg-hover);
    }
    .nav-item.active::before {
      content: ''; position: absolute; left: 0;
      width: 2px; height: 20px; background: var(--accent);
      border-radius: 0 1px 1px 0;
    }
    .nav-item { position: relative; }
    .nav-badge {
      margin-left: auto; font-size: 10px; font-weight: 600;
      padding: 1px 6px; border-radius: 3px;
    }
    .nav-badge-soon { background: var(--bg-hover); color: var(--faint); border: 1px solid var(--border); }
    .nav-badge-write { background: rgba(245,158,11,0.08); color: #92400e; border: 1px solid rgba(245,158,11,0.2); }
    html[data-theme="dark"] .nav-badge-write { color: #fbbf24; border-color: rgba(245,158,11,0.2); }

    /* ── Main ────────────────────────────────────────────────────── */
    .main {
      padding: 36px 48px 80px;
      max-width: 780px; width: 100%; margin: 0 auto; min-width: 0;
    }

    /* Breadcrumb */
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--faint); margin-bottom: 22px;
    }
    .breadcrumb a:hover { color: var(--link); }
    .breadcrumb-sep { color: var(--border-med); }

    /* Headings */
    .page-h1 {
      font-size: 28px; font-weight: 600; letter-spacing: -0.018em;
      line-height: 1.2; color: var(--text); margin-bottom: 12px;
    }
    .page-lead {
      font-size: 15px; color: var(--muted); line-height: 1.7;
      margin-bottom: 32px; max-width: 620px;
    }
    hr.divider {
      border: none; border-top: 1px solid var(--border);
      margin: 28px 0;
    }
    .doc-h2 {
      font-size: 17px; font-weight: 600; letter-spacing: -0.01em;
      color: var(--text); margin: 28px 0 10px;
      scroll-margin-top: calc(var(--header-h) + 16px);
    }
    .doc-h2:first-child { margin-top: 0; }
    .doc-h3 {
      font-size: 14px; font-weight: 600; color: var(--text);
      margin: 20px 0 8px;
      scroll-margin-top: calc(var(--header-h) + 16px);
    }
    .doc-p {
      font-size: 14px; line-height: 1.7; color: var(--muted);
      margin-bottom: 12px;
    }
    .doc-p:last-child { margin-bottom: 0; }
    .doc-p strong { color: var(--text-2); }
    .doc-p a { color: var(--link); }
    .doc-p a:hover { color: var(--link-hover); text-decoration: underline; }

    /* Inline code */
    .ic {
      font-family: 'JetBrains Mono', monospace;
      font-size: 12px; padding: 1.5px 5px;
      background: var(--bg-hover);
      border: 1px solid var(--border-med);
      border-radius: 4px; color: var(--text-2);
      white-space: nowrap;
    }

    /* Note / callout — minimal, no color */
    .note {
      font-size: 13.5px; line-height: 1.65;
      padding: 12px 16px; margin: 16px 0;
      border-radius: 8px;
      border: 1px solid var(--border-med);
      background: var(--bg);
      color: var(--muted);
    }
    .note strong { color: var(--text-2); }
    .note a { color: var(--link); }

    /* Code block */
    .codeblock {
      position: relative; margin: 14px 0;
      background: var(--code-bg);
      border-radius: 10px; overflow: hidden;
      border: 1px solid rgba(255,255,255,0.06);
    }
    .codeblock-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 8px 14px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .codeblock-title {
      font-size: 11.5px; font-weight: 500;
      color: rgba(255,255,255,0.35);
      font-family: 'JetBrains Mono', monospace;
    }
    .codeblock pre {
      margin: 0; padding: 16px;
      font: 13px/1.7 'JetBrains Mono', monospace;
      color: var(--code-text); overflow-x: auto; white-space: pre;
    }
    .copy-btn {
      font-size: 11.5px; font-weight: 500; font-family: inherit;
      background: rgba(255,255,255,0.07);
      color: rgba(255,255,255,0.35);
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: 5px; padding: 3px 9px;
      transition: all 0.12s;
    }
    .copy-btn:hover { color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.11); }
    .copy-btn.ok { color: #34d399; border-color: rgba(52,211,153,0.3); }

    /* Table */
    .doc-table {
      width: 100%; border-collapse: collapse;
      font-size: 13px; margin: 14px 0;
      border: 1px solid var(--border-med);
      border-radius: 9px; overflow: hidden;
      background: var(--panel);
    }
    .doc-table th {
      padding: 9px 14px;
      background: var(--bg); color: var(--text-2);
      font-weight: 600; font-size: 11.5px;
      text-transform: uppercase; letter-spacing: 0.04em;
      border-bottom: 1px solid var(--border-med);
      text-align: left;
    }
    .doc-table td {
      padding: 10px 14px;
      border-bottom: 1px solid var(--border);
      color: var(--muted); vertical-align: top; line-height: 1.5;
    }
    .doc-table tr:last-child td { border-bottom: none; }
    .doc-table code { font-family: 'JetBrains Mono', monospace; font-size: 11.5px; }

    /* Type / required chips */
    .chip {
      display: inline-flex; align-items: center;
      font-family: 'JetBrains Mono', monospace;
      font-size: 11px; font-weight: 500;
      padding: 1.5px 6px; border-radius: 4px; white-space: nowrap;
    }
    .chip-type { background: var(--bg-hover); color: var(--muted); border: 1px solid var(--border); }
    .chip-req  { background: rgba(239,68,68,0.07); color: #dc2626; border: 1px solid rgba(239,68,68,0.15); }
    .chip-opt  { background: var(--bg-hover); color: var(--faint); border: 1px solid var(--border); }
    html[data-theme="dark"] .chip-req { color: #fca5a5; border-color: rgba(239,68,68,0.2); }

    /* Method badges */
    .method {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 50px; height: 22px; border-radius: 4px;
      font-size: 10.5px; font-weight: 700; letter-spacing: 0.04em;
      font-family: 'JetBrains Mono', monospace;
    }
    .m-GET    { background: rgba(16,185,129,0.08); color: #059669; }
    .m-POST   { background: rgba(59,130,246,0.08); color: #2563eb; }
    .m-PUT    { background: rgba(245,158,11,0.08); color: #b45309; }
    .m-PATCH  { background: rgba(245,158,11,0.08); color: #b45309; }
    .m-DELETE { background: rgba(239,68,68,0.08);  color: #dc2626; }
    html[data-theme="dark"] .m-GET    { color: #34d399; background: rgba(16,185,129,0.1); }
    html[data-theme="dark"] .m-POST   { color: #93c5fd; background: rgba(59,130,246,0.1); }
    html[data-theme="dark"] .m-PUT,
    html[data-theme="dark"] .m-PATCH  { color: #fbbf24; background: rgba(245,158,11,0.1); }
    html[data-theme="dark"] .m-DELETE { color: #fca5a5; background: rgba(239,68,68,0.1); }

    /* Checklist (plain, no emoji) */
    .checklist { list-style: none; padding: 0; margin: 12px 0; }
    .checklist li {
      display: grid; grid-template-columns: 18px 1fr;
      gap: 10px; padding: 8px 0;
      border-bottom: 1px solid var(--border);
      font-size: 13.5px; color: var(--muted); line-height: 1.5;
      align-items: start;
    }
    .checklist li:last-child { border-bottom: none; }
    .checklist li svg { margin-top: 2px; color: var(--faint); flex-shrink: 0; }

    /* Simple numbered steps */
    .steps { display: flex; flex-direction: column; gap: 0; margin: 14px 0; }
    .step {
      display: grid; grid-template-columns: 28px 1fr;
      gap: 14px; padding: 16px 0;
      border-bottom: 1px solid var(--border); align-items: start;
    }
    .step:last-child { border-bottom: none; }
    .step-num {
      width: 22px; height: 22px; border-radius: 50%;
      border: 1.5px solid var(--border-med);
      display: flex; align-items: center; justify-content: center;
      font-size: 11.5px; font-weight: 700; color: var(--muted);
      flex-shrink: 0; margin-top: 1px;
    }
    .step h3 { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
    .step p  { font-size: 13.5px; color: var(--muted); line-height: 1.6; }

    /* Error code table */
    .status-table { width: 100%; margin: 14px 0; }
    .status-row {
      display: grid; grid-template-columns: 60px 140px 1fr;
      gap: 16px; padding: 10px 0;
      border-bottom: 1px solid var(--border);
      font-size: 13.5px; align-items: baseline;
    }
    .status-row:last-child { border-bottom: none; }
    .status-code {
      font-family: 'JetBrains Mono', monospace; font-size: 13px;
      font-weight: 600; color: var(--text);
    }
    .status-label { color: var(--text-2); font-weight: 500; }
    .status-desc  { color: var(--muted); }

    /* Changelog */
    .cl-entry { margin-bottom: 28px; }
    .cl-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .cl-version {
      font-family: 'JetBrains Mono', monospace;
      font-size: 14px; font-weight: 600; color: var(--text);
    }
    .cl-date { font-size: 12.5px; color: var(--faint); }
    .cl-tag {
      font-size: 10.5px; font-weight: 600; padding: 1px 7px; border-radius: 3px;
    }
    .cl-tag-latest { background: rgba(16,185,129,0.08); color: #059669; border: 1px solid rgba(16,185,129,0.2); }
    html[data-theme="dark"] .cl-tag-latest { color: #34d399; }
    .cl-entry ul { padding-left: 16px; }
    .cl-entry li { font-size: 13.5px; color: var(--muted); margin-bottom: 4px; line-height: 1.6; }
    .cl-entry li strong { color: var(--text-2); }

    /* TOC */
    .toc {
      position: sticky; top: var(--header-h);
      height: calc(100vh - var(--header-h));
      overflow-y: auto; padding: 24px 16px 40px;
      border-left: 1px solid var(--border);
    }
    .toc-title {
      font-size: 11px; font-weight: 600; letter-spacing: 0.04em;
      text-transform: uppercase; color: var(--faint);
      margin-bottom: 8px;
    }
    .toc-link {
      display: block; padding: 4px 8px; border-radius: 5px;
      font-size: 12.5px; color: var(--faint); transition: all 0.12s;
    }
    .toc-link:hover { color: var(--text); background: var(--bg-hover); }
    .toc-link.active { color: var(--text); }

    /* Prev/Next */
    .page-nav {
      display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
      margin-top: 40px; padding-top: 24px;
      border-top: 1px solid var(--border);
    }
    .pn-card {
      padding: 14px 16px; border-radius: 8px;
      border: 1px solid var(--border-med); background: var(--panel);
      transition: border-color 0.12s;
    }
    .pn-card:hover { border-color: var(--faint); }
    .pn-dir { font-size: 11.5px; color: var(--faint); margin-bottom: 3px; }
    .pn-title { font-size: 14px; font-weight: 500; color: var(--text); }
    .pn-card.next { text-align: right; }

    /* Search modal */
    .search-wrap {
      position: fixed; inset: 0; z-index: 200;
      background: rgba(0,0,0,0.3);
      display: none; align-items: flex-start; justify-content: center;
      padding-top: 80px;
    }
    .search-wrap.open { display: flex; }
    .search-box {
      width: min(520px, calc(100vw - 32px));
      background: var(--panel); border-radius: 12px;
      border: 1px solid var(--border-med);
      box-shadow: 0 8px 30px rgba(0,0,0,0.15); overflow: hidden;
    }
    .search-bar {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; border-bottom: 1px solid var(--border);
    }
    .search-bar svg { color: var(--faint); flex-shrink: 0; }
    .search-inp {
      flex: 1; border: none; background: transparent; outline: none;
      font: 14.5px 'Inter', sans-serif; color: var(--text);
    }
    .search-inp::placeholder { color: var(--faint); }
    .search-list { max-height: 380px; overflow-y: auto; padding: 6px; }
    .search-item {
      display: flex; align-items: center; gap: 12px;
      padding: 9px 12px; border-radius: 7px; transition: background 0.1s;
    }
    .search-item:hover { background: var(--bg-hover); }
    .search-item-icon {
      width: 28px; height: 28px; border-radius: 6px;
      background: var(--bg); border: 1px solid var(--border-med);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; color: var(--faint);
    }
    .search-item strong { display: block; font-size: 13.5px; color: var(--text); font-weight: 500; }
    .search-item span   { display: block; font-size: 12px; color: var(--faint); margin-top: 1px; }
    .search-empty { padding: 20px; text-align: center; font-size: 13.5px; color: var(--faint); }

    /* Code tabs */
    .code-tabs { margin: 14px 0; border-radius: 10px; overflow: hidden; background: var(--code-bg); border: 1px solid rgba(255,255,255,0.06); }
    .ctabs-bar { display: flex; align-items: center; padding: 6px 10px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .ctabs-langs { display: flex; gap: 0; flex: 1; }
    .ctab {
      font: 500 12px 'Inter', sans-serif; color: rgba(255,255,255,0.35);
      background: transparent; border: none;
      padding: 6px 12px 7px; border-bottom: 2px solid transparent;
      margin-bottom: -1px; transition: all 0.1s; cursor: pointer;
    }
    .ctab:hover { color: rgba(255,255,255,0.6); }
    .ctab.active { color: rgba(255,255,255,0.85); border-bottom-color: rgba(255,255,255,0.4); }
    .ctab-copy {
      font: 500 11.5px 'Inter', sans-serif;
      background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.35);
      border: 1px solid rgba(255,255,255,0.1); border-radius: 5px;
      padding: 3px 9px; margin: 4px 0;
      transition: all 0.12s;
    }
    .ctab-copy:hover { color: rgba(255,255,255,0.6); }
    .ctab-copy.ok { color: #34d399; border-color: rgba(52,211,153,0.3); }
    .code-pane { display: none; }
    .code-pane.active { display: block; }
    .code-pane pre { margin: 0; padding: 16px; font: 13px/1.7 'JetBrains Mono', monospace; color: var(--code-text); overflow-x: auto; white-space: pre; }

    /* Try It */
    .try-it {
      margin-top: 16px; border-radius: 8px;
      border: 1px solid var(--border-med); overflow: hidden;
    }
    .try-summary {
      display: flex; align-items: center; gap: 8px;
      padding: 10px 14px; font-size: 13px; font-weight: 500;
      color: var(--muted); cursor: pointer; user-select: none; list-style: none;
    }
    .try-summary::-webkit-details-marker { display: none; }
    details.try-it[open] .try-summary { border-bottom: 1px solid var(--border); }
    .try-summary svg { transition: transform 0.15s; }
    details.try-it[open] .try-summary svg { transform: rotate(90deg); }
    .try-body { padding: 14px; }
    .try-fields { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; margin-bottom: 12px; }
    .try-field { display: flex; flex-direction: column; gap: 4px; }
    .try-lbl { font-size: 11.5px; font-weight: 600; color: var(--muted); }
    .try-inp {
      height: 34px; padding: 0 10px;
      border: 1px solid var(--border-med); border-radius: 6px;
      background: var(--panel); color: var(--text);
      font: 12.5px 'JetBrains Mono', monospace; outline: none;
      transition: border-color 0.12s;
    }
    .try-inp:focus { border-color: var(--faint); }
    .try-actions { display: flex; align-items: center; gap: 10px; }
    .try-send {
      height: 32px; padding: 0 14px; border: none; border-radius: 6px;
      background: var(--accent); color: var(--white);
      font: 600 12.5px 'Inter', sans-serif; transition: opacity 0.12s;
    }
    html[data-theme="dark"] .try-send { color: var(--bg); }
    .try-send:hover { opacity: 0.85; }
    .try-send:disabled { opacity: 0.4; }
    .try-status { font-size: 12.5px; font-weight: 600; }
    .try-result {
      margin-top: 10px; max-height: 280px; overflow: auto;
      background: var(--code-bg); color: var(--code-text);
      border-radius: 7px; padding: 12px;
      font: 12px/1.65 'JetBrains Mono', monospace;
    }

    /* Responsive */
    @media (max-width: 1100px) {
      .layout { grid-template-columns: var(--sidebar-w) 1fr; }
      .toc { display: none; }
    }
    @media (max-width: 820px) {
      .layout { display: block; }
      .sidebar { display: none; }
      .main { padding: 24px 20px 60px; }
      .page-h1 { font-size: 22px; }
      .page-nav { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

{{-- Header --}}
<header class="hd">
  <div class="hd-brand">
    <a href="{{ route('developers.api-docs') }}" style="display:flex;align-items:center;gap:9px;">
      <img src="{{ asset('apple-touch-icon.png') }}" alt="Kitobchi" width="26" height="26">
    </a>
    <span class="hd-ver">API v1</span>
  </div>
  <div class="hd-center">
    <button class="hd-search" id="searchBtn" type="button">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <span>Qidirish...</span>
      <span class="kbds"><kbd>⌘</kbd><kbd>K</kbd></span>
    </button>
  </div>
  <div class="hd-right">
    <a class="hd-btn" href="{{ $openapiUrl }}" target="_blank" rel="noopener">OpenAPI</a>
    <a class="hd-btn" href="{{ $postmanUrl }}" target="_blank" rel="noopener">Postman</a>
    <button class="hd-btn hd-btn-icon" id="themeBtn" type="button" aria-label="Mavzu">
      <svg class="ico-sun" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
      <svg class="ico-moon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
    </button>
    <a class="hd-btn hd-btn-primary" href="{{ url('/') }}">Kitobchi.com</a>
  </div>
</header>

{{-- Search modal --}}
<div class="search-wrap" id="searchWrap">
  <div class="search-box">
    <div class="search-bar">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input class="search-inp" id="searchInp" type="search" placeholder="Sahifa, mavzu yoki endpoint..." autocomplete="off">
    </div>
    <div class="search-list" id="searchList"></div>
  </div>
</div>

{{-- Main layout --}}
<div class="layout">

  {{-- Sidebar --}}
  <aside class="sidebar">
    <nav aria-label="API navigatsiyasi">
      @foreach($groups as $group => $slugs)
        <div class="nav-group">
          <span class="nav-group-title">{{ $group }}</span>
          @foreach($slugs as $slug)
            @php $p = $pages[$slug]; @endphp
            <a href="{{ $routeFor($slug) }}"
               class="nav-item {{ $currentSlug === $slug ? 'active' : '' }}">
              <span>{{ $p['title'] }}</span>
              @if(!empty($p['badge']))
                <span class="nav-badge {{ str_contains($p['badge'], 'kunda') ? 'nav-badge-soon' : 'nav-badge-write' }}">
                  {{ $p['badge'] }}
                </span>
              @endif
            </a>
          @endforeach
        </div>
      @endforeach
    </nav>
  </aside>

  {{-- Content --}}
  <main class="main">
    <div class="breadcrumb">
      <a href="{{ route('developers.api-docs') }}">Kitobchi API</a>
      <span class="breadcrumb-sep">/</span>
      <span>{{ $currentPage['group'] }}</span>
      <span class="breadcrumb-sep">/</span>
      <span>{{ $currentPage['title'] }}</span>
    </div>

    <h1 class="page-h1">{{ $currentPage['title'] }}</h1>
    <p class="page-lead">{{ $currentPage['description'] }}</p>

    @switch($currentSlug)

      {{-- GETTING STARTED --}}
      @case('getting-started')
        <h2 class="doc-h2" id="overview">Nima bu?</h2>
        <p class="doc-p">Kitobchi Client API — do'konlar, katalog platformalari va uchinchi tomon servislar uchun mo'ljallangan REST API. API orqali siz Kitobchi'ning kitob va kanselyariya katalogiga, qidiruv tizimiga va seller ma'lumotlariga ulana olasiz.</p>
        <p class="doc-p">Barcha so'rovlar <strong>HTTPS</strong> orqali, quyidagi base URL ga yuboriladi:</p>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">Base URL</span>
          </div>
          <pre>{{ $baseUrl }}</pre>
        </div>

        <hr class="divider">
        <h2 class="doc-h2" id="quickstart">Boshlash</h2>
        <div class="steps">
          <div class="step">
            <div class="step-num">1</div>
            <div>
              <h3>API kalitini oling</h3>
              <p>Do'kon va Kitobchi xodimlari o'rtasida ochilgan maxsus Telegram guruhiga murojaat qiling — sizga <span class="ic">X-App-ID</span> va <span class="ic">X-App-Secret</span> juftligi beriladi. Kalit siz boshqaradigan do'kon hisobiga bog'langan.</p>
            </div>
          </div>
          <div class="step">
            <div class="step-num">2</div>
            <div>
              <h3>Birinchi so'rovni yuboring</h3>
              <p>Quyidagi cURL misolini ishga tushirib natijani ko'ring. Barcha so'rovlarda ikkala header ham bo'lishi shart.</p>
            </div>
          </div>
          <div class="step">
            <div class="step-num">3</div>
            <div>
              <h3>Integratsiyani quring</h3>
              <p>Catalog, search va seller endpoint'larini o'z tizimingizga ulang. So'rovlarni faqat serveringiz tomonidan yuboring — API kalitini hech qachon frontend kodiga kiritmang.</p>
            </div>
          </div>
        </div>

        <hr class="divider">
        <h2 class="doc-h2" id="first-request">Birinchi so'rov</h2>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">bash</span>
            <button class="copy-btn" type="button">Ko'chirish</button>
          </div>
          <pre>curl --request GET \
  --url '{{ $baseUrl }}/products/books?page=1' \
  --header 'Accept: application/json' \
  --header 'X-App-ID: app_xxxxxxxxxxxx' \
  --header 'X-App-Secret: your-secret-here'</pre>
        </div>

        <hr class="divider">
        <h2 class="doc-h2" id="response-format">Javob formati</h2>
        <p class="doc-p">Barcha javoblar JSON formatida qaytariladi. Muvaffaqiyatli javob:</p>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">JSON — 200 OK</span>
          </div>
          <pre>{
  "status": "success",
  "data": [ ... ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 342,
    "last_page": 18
  }
}</pre>
        </div>
        <p class="doc-p">Xato bo'lganda:</p>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">JSON — Xato javob</span>
          </div>
          <pre>{
  "status": "error",
  "message": "Invalid or inactive API credentials"
}</pre>
        </div>

        <hr class="divider">
        <h2 class="doc-h2" id="best-practices">Muhim qoidalar</h2>
        <ul class="checklist">
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <div><strong>Secret serverda bo'lsin</strong> — <span class="ic">X-App-Secret</span> ni faqat server muhitiy o'zgaruvchilarida saqlang. Frontend yoki mobil ilovaga bermang.</div>
          </li>
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <div><strong>POST'larda Idempotency-Key</strong> — zaxira yangilash so'rovlarida <span class="ic">Idempotency-Key</span> headerini yuboring, internet uzilsa ham ikki marta bajarilmaydi.</div>
          </li>
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <div><strong>Rate limit kuzatuvi</strong> — <span class="ic">X-RateLimit-Remaining-Minute</span> headerini kuzating; limit yaqinlashganda so'rovlar orasidagi intervalini oshiring.</div>
          </li>
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <div><strong>ETag bilan kesh optimallashtirish</strong> — GET javobidagi <span class="ic">ETag</span> ni saqlang, keyingi so'rovda <span class="ic">If-None-Match</span> orqali 304 javob oling.</div>
          </li>
        </ul>
        @break

      {{-- AUTHENTICATION --}}
      @case('authentication')
        <h2 class="doc-h2" id="headers">Headerlar</h2>
        <p class="doc-p">Har bir so'rovda quyidagi headerlar bo'lishi shart:</p>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">HTTP</span>
            <button class="copy-btn" type="button">Ko'chirish</button>
          </div>
          <pre>X-App-ID: app_xxxxxxxxxxxx
X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json</pre>
        </div>
        <table class="doc-table">
          <thead><tr><th>Header</th><th>Tavsifi</th><th>Talab</th></tr></thead>
          <tbody>
            <tr><td><span class="ic">X-App-ID</span></td><td>Unikal ilova identifikatori — <code>app_</code> prefiksi bilan boshlanadi</td><td><span class="chip chip-req">majburiy</span></td></tr>
            <tr><td><span class="ic">X-App-Secret</span></td><td>Maxfiy kalit — serverda muhit o'zgaruvchisi sifatida saqlang</td><td><span class="chip chip-req">majburiy</span></td></tr>
            <tr><td><span class="ic">Accept</span></td><td>JSON javob kutilayotganini bildiradi</td><td><span class="chip chip-opt">tavsiya</span></td></tr>
            <tr><td><span class="ic">Idempotency-Key</span></td><td>POST so'rovlarda takroriy bajarishning oldini oladi (UUID yuboring)</td><td><span class="chip chip-opt">POST'da tavsiya</span></td></tr>
          </tbody>
        </table>

        <hr class="divider">
        <h2 class="doc-h2" id="credentials">Kalit olish</h2>
        <p class="doc-p">API kalitlari Kitobchi jamoasi tomonidan beriladi. Kalit to'plami ikkita elementdan iborat:</p>
        <table class="doc-table">
          <thead><tr><th>Element</th><th>Ko'rinishi</th><th>Maqsadi</th></tr></thead>
          <tbody>
            <tr><td><span class="ic">X-App-ID</span></td><td><code>app_a1b2c3d4e5f6g7h8</code></td><td>Ochiq identifikator — loglar va debuggingda ko'rinadi</td></tr>
            <tr><td><span class="ic">X-App-Secret</span></td><td><code>sk_prod_xxxxxxxxxxxx...</code></td><td>Maxfiy kalit — faqat serverda saqlang, hech kimga bermang</td></tr>
          </tbody>
        </table>
        <p class="doc-p">Kalit olish yoki yangilash bo'yicha: <strong>Do'kon va Kitobchi xodimlari o'rtasida ochilgan maxsus Telegram guruhiga murojaat qiling.</strong></p>

        <hr class="divider">
        <h2 class="doc-h2" id="secrets">Xavfsizlik</h2>
        <div class="note">
          <strong>Secret oshkor bo'lsa:</strong> darhol bizga xabar bering, eski kalit o'chiriladi va yangi juftlik beriladi. Barcha so'rovlar yangi kalit bilan yuborilishi kerak bo'ladi.
        </div>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">.env</span>
          </div>
          <pre>KITOBCHI_APP_ID=app_xxxxxxxxxxxx
KITOBCHI_APP_SECRET=sk_prod_xxxxxxxxxxxx</pre>
        </div>

        <hr class="divider">
        <h2 class="doc-h2" id="abilities">Ruxsatlar (Abilities)</h2>
        <p class="doc-p">Har bir kalit yaratilayotganda foydalanish doirasi belgilanadi:</p>
        <table class="doc-table">
          <thead><tr><th>Ability</th><th>Qamrovi</th></tr></thead>
          <tbody>
            <tr><td><span class="ic">read</span></td><td>Katalog, qidiruv, seller ma'lumotlari (faqat o'qish)</td></tr>
            <tr><td><span class="ic">stock:write</span></td><td>Do'kon zaxirasini ISBN / shtrix-kod bo'yicha yangilash</td></tr>
          </tbody>
        </table>
        @break

      {{-- RATE LIMITS --}}
      @case('rate-limits')
        <h2 class="doc-h2" id="limits">Limitlar</h2>
        <p class="doc-p">Har bir API kalit uchun standart limitlar:</p>
        <table class="doc-table">
          <thead><tr><th>Davr</th><th>Limit</th></tr></thead>
          <tbody>
            <tr><td>1 soniya</td><td><span class="ic">{{ $defaultLimits['per_second'] }}</span> so'rov</td></tr>
            <tr><td>1 daqiqa</td><td><span class="ic">{{ $defaultLimits['per_minute'] }}</span> so'rov</td></tr>
          </tbody>
        </table>
        <p class="doc-p">Limit oshib ketsa HTTP <span class="ic">429 Too Many Requests</span> qaytariladi. <span class="ic">Retry-After</span> headeridagi soniya qadar kuting.</p>

        <hr class="divider">
        <h2 class="doc-h2" id="headers-rl">Rate-limit headerlar</h2>
        <table class="doc-table">
          <thead><tr><th>Header</th><th>Ma'nosi</th></tr></thead>
          <tbody>
            <tr><td><span class="ic">X-RateLimit-Limit-Second</span></td><td>1 soniyadagi ruxsat etilgan so'rovlar soni</td></tr>
            <tr><td><span class="ic">X-RateLimit-Limit-Minute</span></td><td>1 daqiqadagi ruxsat etilgan so'rovlar soni</td></tr>
            <tr><td><span class="ic">X-RateLimit-Remaining-Second</span></td><td>Joriy soniyadagi qolgan kvota</td></tr>
            <tr><td><span class="ic">X-RateLimit-Remaining-Minute</span></td><td>Joriy daqiqadagi qolgan kvota</td></tr>
            <tr><td><span class="ic">Retry-After</span></td><td>429 holati — kutish vaqti (soniya)</td></tr>
          </tbody>
        </table>

        <hr class="divider">
        <h2 class="doc-h2" id="cache">Response keshi</h2>
        <p class="doc-p">GET endpointlar avtomatik ravishda <strong>120 soniya</strong> keshlanadi. Har bir javobda <span class="ic">X-API-Cache</span> headeri bo'ladi:</p>
        <table class="doc-table">
          <thead><tr><th>Qiymat</th><th>Ma'nosi</th></tr></thead>
          <tbody>
            <tr><td><span class="ic">MISS</span></td><td>Keshda yo'q edi — yangi javob yaratildi va saqlandi</td></tr>
            <tr><td><span class="ic">HIT</span></td><td>Keshdan qaytarildi</td></tr>
            <tr><td><span class="ic">BYPASS</span></td><td>Kesh o'tkazib yuborildi (POST yoki <span class="ic">Cache-Control: no-cache</span>)</td></tr>
          </tbody>
        </table>

        <hr class="divider">
        <h2 class="doc-h2" id="etag">ETag va 304</h2>
        <p class="doc-p">Bandwidth tejash uchun conditional request'lardan foydalaning:</p>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">1-so'rov — ETag saqlang</span>
          </div>
          <pre>HTTP/1.1 200 OK
ETag: "a3f5c8e9b1d2..."
Content-Type: application/json</pre>
        </div>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">2-so'rov — If-None-Match yuboring</span>
          </div>
          <pre>GET /products/books HTTP/1.1
If-None-Match: "a3f5c8e9b1d2..."

→ HTTP/1.1 304 Not Modified  (body yo'q, trafik tejaldi)</pre>
        </div>
        @break

      {{-- PAGINATION --}}
      @case('pagination')
        <h2 class="doc-h2" id="params">Parametrlar</h2>
        <table class="doc-table">
          <thead><tr><th>Parametr</th><th>Turi</th><th>Default</th><th>Tavsifi</th></tr></thead>
          <tbody>
            <tr><td><span class="ic">page</span></td><td><span class="chip chip-type">integer</span></td><td>1</td><td>Sahifa raqami (1 dan boshlanadi)</td></tr>
            <tr><td><span class="ic">per_page</span></td><td><span class="chip chip-type">integer</span></td><td>20</td><td>Sahifadagi elementlar soni (maks. 100)</td></tr>
            <tr><td><span class="ic">q</span></td><td><span class="chip chip-type">string</span></td><td>—</td><td>Nom yoki tavsif bo'yicha qidiruv</td></tr>
            <tr><td><span class="ic">sort</span></td><td><span class="chip chip-type">string</span></td><td>popular</td><td><span class="ic">popular</span>, <span class="ic">new</span>, <span class="ic">price_asc</span>, <span class="ic">price_desc</span></td></tr>
            <tr><td><span class="ic">seller_id</span></td><td><span class="chip chip-type">integer</span></td><td>—</td><td>Faqat shu do'kon mahsulotlarini ko'rsatish</td></tr>
          </tbody>
        </table>

        <hr class="divider">
        <h2 class="doc-h2" id="meta">Meta bloki</h2>
        <p class="doc-p">Sahifalangan barcha javoblarda <span class="ic">meta</span> bloki bo'ladi:</p>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">JSON</span>
          </div>
          <pre>{
  "status": "success",
  "data": [ ... ],
  "meta": {
    "page": 2,
    "per_page": 20,
    "total": 342,
    "last_page": 18
  }
}</pre>
        </div>
        <table class="doc-table">
          <thead><tr><th>Maydon</th><th>Turi</th><th>Tavsifi</th></tr></thead>
          <tbody>
            <tr><td><span class="ic">meta.page</span></td><td><span class="chip chip-type">integer</span></td><td>Joriy sahifa</td></tr>
            <tr><td><span class="ic">meta.per_page</span></td><td><span class="chip chip-type">integer</span></td><td>Sahifadagi elementlar soni</td></tr>
            <tr><td><span class="ic">meta.total</span></td><td><span class="chip chip-type">integer</span></td><td>Jami elementlar soni</td></tr>
            <tr><td><span class="ic">meta.last_page</span></td><td><span class="chip chip-type">integer</span></td><td>Oxirgi sahifa raqami</td></tr>
          </tbody>
        </table>

        <hr class="divider">
        <h2 class="doc-h2" id="filtering">Filtr va tartiblash</h2>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">bash</span>
          </div>
          <pre># Nom bo'yicha qidiruv
GET /products/books?q=python&page=1

# Narx bo'yicha o'sish
GET /products/books?sort=price_asc&per_page=50

# Bitta do'kon, yangilaridan boshlab
GET /products/books?seller_id=12&sort=new</pre>
        </div>

        <hr class="divider">
        <h2 class="doc-h2" id="empty-results">Bo'sh natijalar</h2>
        <p class="doc-p">Hech narsa topilmasa ham 200 OK qaytariladi — xato emas:</p>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">JSON</span>
          </div>
          <pre>{
  "status": "success",
  "data": [],
  "meta": { "page": 1, "per_page": 20, "total": 0, "last_page": 1 }
}</pre>
        </div>
        @break

      {{-- ENDPOINT PAGES --}}
      @case('products')
        <p class="doc-p" style="margin-bottom:24px;">Katalogdagi kitoblar, kanselyariyalar, seller ma'lumotlari va tavsiyalar. Barcha GET endpointlar <span class="ic">read</span> ability talab qiladi.</p>
        @foreach($pageEndpoints as $ep)
          <x-api-docs-endpoint :endpoint="$ep" />
        @endforeach
        @break

      @case('search')
        <p class="doc-p" style="margin-bottom:24px;">Global qidiruv, autocomplete va kategorial filtr. Autocomplete'ni UI'da debounce (300 ms) bilan chaqiring.</p>
        @foreach($pageEndpoints as $ep)
          <x-api-docs-endpoint :endpoint="$ep" />
        @endforeach
        @break

      @case('seller')
        <div class="note" style="margin-bottom:20px;">
          Bu endpointlar faqat <span class="ic">stock:write</span> ability va do'koningizga bog'langan kalit bilan ishlaydi. POST so'rovlarda <span class="ic">Idempotency-Key</span> headerini yuboring.
        </div>
        @foreach($pageEndpoints as $ep)
          <x-api-docs-endpoint :endpoint="$ep" />
        @endforeach
        @break

      @case('deeplink')
        <p class="doc-p" style="margin-bottom:24px;">Mobil ilova URL schemalari, web, Play Market va App Store havolalarini bitta so'rovda oling.</p>
        @foreach($pageEndpoints as $ep)
          <x-api-docs-endpoint :endpoint="$ep" />
        @endforeach
        @break

      {{-- WEBHOOKS --}}
      @case('webhooks')
        <div class="note" style="margin-bottom:20px;">
          Webhook funksionalligi hozirda ishlab chiqilmoqda. Tayyor bo'lganda bu sahifa yangilanadi. Murojaat: <strong>Maxsus Telegram guruhiga yozing.</strong>
        </div>

        <h2 class="doc-h2" id="events">Rejalashtirilgan hodisalar</h2>
        <table class="doc-table">
          <thead><tr><th>Hodisa</th><th>Qachon yuborilar</th></tr></thead>
          <tbody>
            <tr><td><span class="ic">product.stock_changed</span></td><td>Mahsulot zaxirasi o'zgarganda</td></tr>
            <tr><td><span class="ic">product.price_changed</span></td><td>Mahsulot narxi yangilanganda</td></tr>
            <tr><td><span class="ic">product.status_changed</span></td><td>Mahsulot faollik holati o'zgarganda</td></tr>
            <tr><td><span class="ic">seller.status_changed</span></td><td>Do'kon holati o'zgarganda</td></tr>
          </tbody>
        </table>

        <hr class="divider">
        <h2 class="doc-h2" id="signature">HMAC imzo tekshiruvi</h2>
        <p class="doc-p">Webhook so'rovining haqiqiyligini <span class="ic">X-Kitobchi-Signature</span> headeridagi HMAC-SHA256 imzo orqali tekshiring:</p>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">PHP</span>
            <button class="copy-btn" type="button">Ko'chirish</button>
          </div>
          <pre>$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_KITOBCHI_SIGNATURE'] ?? '';
$expected  = hash_hmac('sha256', $payload, getenv('KITOBCHI_WEBHOOK_SECRET'));

if (! hash_equals($expected, $signature)) {
    http_response_code(401);
    exit;
}</pre>
        </div>

        <hr class="divider">
        <h2 class="doc-h2" id="retries">Qayta yuborish</h2>
        <p class="doc-p">Endpoint 200 qaytarmasa, webhook 3 marta qayta yuboriladi: 1 daqiqa, 5 daqiqa va 30 daqiqadan keyin. Barcha urinish muvaffaqiyatsiz tugasa webhook to'xtatiladi.</p>
        @break

      {{-- ERRORS --}}
      @case('errors')
        <h2 class="doc-h2" id="statuses">HTTP status kodlar</h2>
        <div class="status-table">
          <div class="status-row">
            <span class="status-code">200</span><span class="status-label">OK</span><span class="status-desc">So'rov muvaffaqiyatli bajarildi</span>
          </div>
          <div class="status-row">
            <span class="status-code">304</span><span class="status-label">Not Modified</span><span class="status-desc">Ma'lumot o'zgarmagan (ETag + <span class="ic">If-None-Match</span>)</span>
          </div>
          <div class="status-row">
            <span class="status-code">401</span><span class="status-label">Unauthorized</span><span class="status-desc">Credential headerlar yo'q yoki noto'g'ri</span>
          </div>
          <div class="status-row">
            <span class="status-code">403</span><span class="status-label">Forbidden</span><span class="status-desc">Kalit nofaol, ability yetarli emas, yoki IP ruxsatsiz</span>
          </div>
          <div class="status-row">
            <span class="status-code">404</span><span class="status-label">Not Found</span><span class="status-desc">Endpoint yo'q yoki ma'lumot topilmadi</span>
          </div>
          <div class="status-row">
            <span class="status-code">422</span><span class="status-label">Unprocessable</span><span class="status-desc">Majburiy parametr yo'q yoki noto'g'ri qiymat</span>
          </div>
          <div class="status-row">
            <span class="status-code">429</span><span class="status-label">Too Many Requests</span><span class="status-desc">Rate limit oshdi — <span class="ic">Retry-After</span> soniya kuting</span>
          </div>
          <div class="status-row">
            <span class="status-code">5xx</span><span class="status-label">Server Error</span><span class="status-desc">Server ichki xatosi — exponential backoff bilan qayta urinib ko'ring</span>
          </div>
        </div>

        <hr class="divider">
        <h2 class="doc-h2" id="format">Xato formati</h2>
        <div class="codeblock">
          <div class="codeblock-header">
            <span class="codeblock-title">JSON</span>
          </div>
          <pre>{
  "status": "error",
  "message": "Invalid or inactive API credentials"
}</pre>
        </div>

        <hr class="divider">
        <h2 class="doc-h2" id="common-errors">Tez-tez uchraydigan xatolar</h2>
        <table class="doc-table">
          <thead><tr><th>Xato xabari</th><th>Sabab va yechim</th></tr></thead>
          <tbody>
            <tr><td><span class="ic">API credentials missing</span></td><td>Headerlar umuman yuborilmagan — ikkala headerni ham qo'shing</td></tr>
            <tr><td><span class="ic">Invalid or inactive API credentials</span></td><td>Secret noto'g'ri yoki kalit o'chirilgan — Telegram guruhga murojaat qiling</td></tr>
            <tr><td><span class="ic">IP address not allowed for this API key</span></td><td>Server IP allowlistda yo'q — kerakli IP ni bildiring</td></tr>
            <tr><td><span class="ic">Missing ability: stock:write</span></td><td>Kalit faqat <span class="ic">read</span> — <span class="ic">stock:write</span> kalit so'rang</td></tr>
            <tr><td><span class="ic">This API key is not scoped to a seller</span></td><td>Seller API uchun do'koningizga bog'langan alohida kalit kerak</td></tr>
            <tr><td><span class="ic">ISBN not found in your store</span></td><td>Bu ISBN siz boshqaradigan do'konda ro'yxatdan o'tmagan</td></tr>
          </tbody>
        </table>

        <hr class="divider">
        <h2 class="doc-h2" id="checklist">Integratsiya checklisti</h2>
        <ul class="checklist">
          <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div>App ID va Secret faqat server muhit o'zgaruvchilarida saqlangan</div></li>
          <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div>Barcha API so'rovlar server tomonidan yuborilmoqda (frontend'dan emas)</div></li>
          <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div>POST so'rovlarda <span class="ic">Idempotency-Key</span> header yuborilmoqda</div></li>
          <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div>429 holati uchun <span class="ic">Retry-After</span> headeriga asoslangan kutish mexanizmi bor</div></li>
          <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div>ETag saqlash va <span class="ic">If-None-Match</span> bilan 304 optimizatsiya qilingan</div></li>
        </ul>
        @break

      {{-- CHANGELOG --}}
      @case('changelog')
        <h2 class="doc-h2" id="versioning">Versiyalash siyosati</h2>
        <p class="doc-p">API versiyasi URL'da ko'rsatiladi: <span class="ic">/api/v1/client/</span>. Breaking change bo'lganda yangi versiya e'lon qilinadi va eski versiya kamida 6 oy parallel ishlashda qoladi. Non-breaking yangiliklar (yangi endpointlar, yangi response maydonlar) joriy versiyaga qo'shiladi.</p>

        <hr class="divider">
        <h2 class="doc-h2" id="v1-2">v1.2 — Sentyabr 2026</h2>
        <div class="cl-entry">
          <div class="cl-header">
            <span class="cl-version">v1.2.0</span>
            <span class="cl-tag cl-tag-latest">Latest</span>
            <span class="cl-date">19 Sentyabr 2026</span>
          </div>
          <ul>
            <li><strong>GET /products/mine</strong> — seller o'z do'konining mahsulotlari va zaxirasini ko'rishi uchun yangi endpoint</li>
            <li><strong>GET /deeplink</strong> — mobil va web uchun smart URL generator</li>
            <li>ETag + 304 kesh mexanizmi barcha GET endpointlarga qo'shildi</li>
            <li><span class="ic">Idempotency-Key</span> header POST so'rovlarda 24 soat keshlanadi</li>
            <li><span class="ic">by-publisher</span> endpointidagi pagination muammosi tuzatildi</li>
          </ul>
        </div>

        <h2 class="doc-h2" id="v1-1">v1.1 — Iyun 2026</h2>
        <div class="cl-entry">
          <div class="cl-header">
            <span class="cl-version">v1.1.0</span>
            <span class="cl-date">14 Iyun 2026</span>
          </div>
          <ul>
            <li><strong>POST /products/stock/by-code</strong> — ISBN va shtrix-kod bo'yicha zaxira yangilash (<span class="ic">stock:write</span>)</li>
            <li>IP allowlist — kalitlarga server IP biriktirish imkoniyati</li>
            <li><span class="ic">X-API-Cache</span> header: MISS / HIT / BYPASS holatlari</li>
            <li>Rate limit headerlar yangilandi</li>
            <li><span class="ic">seller_id</span> filter parametridagi xato tuzatildi</li>
          </ul>
        </div>

        <h2 class="doc-h2" id="v1-0">v1.0 — Yanvar 2026</h2>
        <div class="cl-entry">
          <div class="cl-header">
            <span class="cl-version">v1.0.0</span>
            <span class="cl-date">5 Yanvar 2026</span>
          </div>
          <ul>
            <li>Birinchi rasmiy reliz</li>
            <li>Products endpointlari: books, stationery, recommendations, authors, publishers, sellers</li>
            <li>Search: global, suggestions, trending, categories</li>
            <li><span class="ic">X-App-ID</span> + <span class="ic">X-App-Secret</span> autentifikatsiya tizimi</li>
            <li>OpenAPI 3.0 va Postman Collection eksport</li>
          </ul>
        </div>
        @break

      {{-- DEFAULT --}}
      @default
        <p class="doc-p">Base URL: <span class="ic">{{ $baseUrl }}</span></p>
    @endswitch

    {{-- Prev / Next --}}
    @php
      $slugs = array_keys($pages);
      $idx   = array_search($currentSlug, $slugs, true);
      $prev  = $idx > 0 ? $slugs[$idx - 1] : null;
      $next  = $idx !== false && $idx < count($slugs) - 1 ? $slugs[$idx + 1] : null;
    @endphp
    <nav class="page-nav" aria-label="Sahifalar navigatsiyasi">
      @if($prev)
        <a class="pn-card" href="{{ $routeFor($prev) }}">
          <div class="pn-dir">← Oldingi</div>
          <div class="pn-title">{{ $pages[$prev]['title'] }}</div>
        </a>
      @else<div></div>@endif
      @if($next)
        <a class="pn-card next" href="{{ $routeFor($next) }}">
          <div class="pn-dir">Keyingi →</div>
          <div class="pn-title">{{ $pages[$next]['title'] }}</div>
        </a>
      @endif
    </nav>
  </main>

  {{-- TOC --}}
  <aside class="toc">
    <div class="toc-title">Shu sahifada</div>
    @foreach($toc as $item)
      <a href="#{{ $item['id'] }}" class="toc-link">{{ $item['label'] }}</a>
    @endforeach
  </aside>

</div>

<script>
  // ── Theme ─────────────────────────────────────────────────────────
  const root = document.documentElement;
  const saved = localStorage.getItem('kb_theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  if (saved === 'dark' || (!saved && prefersDark)) root.dataset.theme = 'dark';

  function syncThemeIcons() {
    const dark = root.dataset.theme === 'dark';
    document.querySelector('.ico-sun').style.display  = dark ? 'none' : '';
    document.querySelector('.ico-moon').style.display = dark ? '' : 'none';
  }
  syncThemeIcons();

  document.getElementById('themeBtn')?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem('kb_theme', root.dataset.theme);
    syncThemeIcons();
  });

  // ── Search ────────────────────────────────────────────────────────
  const pages    = @json($allPages);
  const baseRoute = @json(route('developers.api-docs'));
  const wrap     = document.getElementById('searchWrap');
  const btn      = document.getElementById('searchBtn');
  const inp      = document.getElementById('searchInp');
  const list     = document.getElementById('searchList');

  function pageUrl(slug) {
    return slug === 'getting-started' ? baseRoute : `${baseRoute}/${slug}`;
  }

  const searchIcon = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>`;

  function renderSearch(q = '') {
    const lq = q.trim().toLowerCase();
    const hits = pages.filter(p => !lq || `${p.title} ${p.description} ${p.group}`.toLowerCase().includes(lq));
    list.innerHTML = hits.length
      ? hits.map(p => `<a class="search-item" href="${pageUrl(p.slug)}"><div class="search-item-icon">${searchIcon}</div><div><strong>${p.title}</strong><span>${p.description}</span></div></a>`).join('')
      : '<div class="search-empty">Hech narsa topilmadi.</div>';
  }

  function openSearch() { renderSearch(''); wrap.classList.add('open'); setTimeout(() => inp?.focus(), 20); }
  function closeSearch() { wrap.classList.remove('open'); }

  btn?.addEventListener('click', openSearch);
  inp?.addEventListener('input', () => renderSearch(inp.value));
  wrap?.addEventListener('click', e => { if (e.target === wrap) closeSearch(); });
  document.addEventListener('keydown', e => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openSearch(); }
    if (e.key === 'Escape') closeSearch();
  });

  // ── TOC Intersection Observer ──────────────────────────────────────
  const tocLinks = document.querySelectorAll('.toc-link');
  if (tocLinks.length && 'IntersectionObserver' in window) {
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting)
          tocLinks.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + e.target.id));
      });
    }, { rootMargin: '-56px 0px -70% 0px' });
    tocLinks.forEach(l => {
      const el = document.querySelector(l.getAttribute('href'));
      if (el) obs.observe(el);
    });
  }

  // ── Code Tabs ──────────────────────────────────────────────────────
  document.addEventListener('click', e => {
    const tab = e.target.closest('.ctab');
    if (!tab) return;
    const wrap = tab.closest('.code-tabs');
    const key  = tab.dataset.tab;
    wrap.querySelectorAll('.ctab').forEach(t => t.classList.toggle('active', t === tab));
    wrap.querySelectorAll('.code-pane').forEach(p => p.classList.toggle('active', p.dataset.pane === key));
  });

  // ── Copy ──────────────────────────────────────────────────────────
  async function doCopy(text, btn) {
    try {
      await navigator.clipboard.writeText(text);
      const orig = btn.textContent;
      btn.classList.add('ok'); btn.textContent = 'Nusxalandi';
      setTimeout(() => { btn.classList.remove('ok'); btn.textContent = orig; }, 1500);
    } catch (_) {}
  }

  document.addEventListener('click', async e => {
    const b = e.target.closest('.copy-btn, .ctab-copy');
    if (!b) return;
    const tabs  = b.closest('.code-tabs');
    const block = b.closest('.codeblock');
    if (tabs) {
      const pane = tabs.querySelector('.code-pane.active') || tabs.querySelector('.code-pane');
      await doCopy(pane?.innerText || '', b);
    } else if (block) {
      await doCopy(block.querySelector('pre')?.innerText || '', b);
    }
  });

  // Standalone response copy
  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-resp-copy]');
    if (!b) return;
    const block = b.closest('.ep-resp-head')?.nextElementSibling;
    await doCopy(block?.querySelector('code')?.innerText || '', b);
  });

  // ── Try It ────────────────────────────────────────────────────────
  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-try-send]');
    if (!b) return;
    const root   = b.closest('[data-tryit]');
    const appid  = root.querySelector('[data-try="appid"]')?.value?.trim() || '';
    const secret = root.querySelector('[data-try="secret"]')?.value?.trim() || '';
    let url = root.dataset.urlTemplate || '';
    root.querySelectorAll('[data-try-path]').forEach(i => url = url.replace('{' + i.dataset.tryPath + '}', encodeURIComponent(i.value.trim())));
    const qs = [];
    root.querySelectorAll('[data-try-query]').forEach(i => { const v = i.value.trim(); if (v) qs.push(encodeURIComponent(i.dataset.tryQuery) + '=' + encodeURIComponent(v)); });
    if (qs.length) url += (url.includes('?') ? '&' : '?') + qs.join('&');
    const st  = root.querySelector('[data-try-status]');
    const res = root.querySelector('[data-try-result]');
    st.textContent = 'Yuborilmoqda…'; st.style.color = 'var(--muted)'; b.disabled = true;
    const t0 = performance.now();
    try {
      const r   = await fetch(url, { headers: { Accept: 'application/json', 'X-App-ID': appid, 'X-App-Secret': secret } });
      const ms  = Math.round(performance.now() - t0);
      const txt = await r.text();
      let body  = txt; try { body = JSON.stringify(JSON.parse(txt), null, 2); } catch (_) {}
      st.textContent  = `${r.status} ${r.statusText} · ${ms}ms`;
      st.style.color  = r.ok ? '#10b981' : '#ef4444';
      res.textContent = body; res.hidden = false;
    } catch (err) {
      st.textContent = 'Xato: ' + err.message; st.style.color = '#ef4444'; res.hidden = true;
    } finally { b.disabled = false; }
  });
</script>
</body>
</html>
