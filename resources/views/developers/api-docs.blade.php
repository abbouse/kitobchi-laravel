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

  $iconSvgs = [
    'rocket'  => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
    'key'     => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>',
    'gauge'   => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>',
    'layers'  => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
    'book'    => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    'search'  => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    'store'   => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/></svg>',
    'zap'     => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    'alert'   => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    'history' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><path d="M12 7v5l4 2"/></svg>',
  ];
@endphp
<!DOCTYPE html>
<html lang="uz" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $currentPage['title'] }} — Kitobchi Developer API</title>
  <meta name="description" content="{{ $currentPage['description'] }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    /* ── Design Tokens ─────────────────────────────────────────────────── */
    :root {
      --bg:           #f9fafb;
      --bg2:          #f3f4f6;
      --panel:        #ffffff;
      --panel2:       #f9fafb;
      --border:       #e5e7eb;
      --border2:      #f3f4f6;
      --text:         #111827;
      --text2:        #374151;
      --muted:        #6b7280;
      --faint:        #9ca3af;
      --accent:       #2563eb;
      --accent2:      #1d4ed8;
      --accent-soft:  rgba(37,99,235,0.08);
      --accent-muted: rgba(37,99,235,0.15);
      --code-bg:      #0d1117;
      --code-text:    #e6edf3;
      --code-border:  rgba(255,255,255,0.08);
      --shadow-sm:    0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);
      --shadow-md:    0 4px 16px rgba(0,0,0,.07),0 2px 4px rgba(0,0,0,.04);
      --shadow-lg:    0 10px 40px rgba(0,0,0,.12),0 4px 8px rgba(0,0,0,.06);
      --radius:       10px;
      --sidebar-w:    260px;
      --toc-w:        220px;
      --header-h:     56px;
      color-scheme:   light;
    }
    html[data-theme="dark"] {
      --bg:           #0a0c10;
      --bg2:          #111318;
      --panel:        #131720;
      --panel2:       #1a1f2e;
      --border:       #1e2535;
      --border2:      #252d3d;
      --text:         #f0f4ff;
      --text2:        #cbd5e1;
      --muted:        #8892a4;
      --faint:        #556070;
      --accent:       #4f83f7;
      --accent2:      #6b97ff;
      --accent-soft:  rgba(79,131,247,0.1);
      --accent-muted: rgba(79,131,247,0.2);
      --code-bg:      #060912;
      --code-text:    #d1dced;
      --code-border:  rgba(255,255,255,0.06);
      --shadow-sm:    0 1px 3px rgba(0,0,0,.3);
      --shadow-md:    0 4px 16px rgba(0,0,0,.4);
      --shadow-lg:    0 10px 40px rgba(0,0,0,.5);
      color-scheme:   dark;
    }

    /* ── Reset & Base ─────────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; font-size: 16px; }
    body {
      font-family: 'Geist', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }
    a { color: inherit; text-decoration: none; }
    svg { display: block; flex-shrink: 0; }

    /* ── Scrollbar ─────────────────────────────────────────────────────── */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 99px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--faint); }

    /* ── Top Header ────────────────────────────────────────────────────── */
    .site-header {
      position: sticky; top: 0; z-index: 50;
      height: var(--header-h);
      display: flex; align-items: center; gap: 0;
      background: color-mix(in srgb, var(--panel) 85%, transparent);
      backdrop-filter: blur(20px) saturate(180%);
      -webkit-backdrop-filter: blur(20px) saturate(180%);
      border-bottom: 1px solid var(--border);
    }
    .header-brand {
      display: flex; align-items: center; gap: 10px;
      padding: 0 20px; height: 100%;
      border-right: 1px solid var(--border);
      flex-shrink: 0; width: var(--sidebar-w);
    }
    .header-brand img { height: 22px; width: auto; display: block; }
    html[data-theme="dark"] .logo-light { display: none; }
    html[data-theme="light"] .logo-dark  { display: none; }
    .api-badge {
      font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em;
      text-transform: uppercase;
      padding: 2px 7px; border-radius: 99px;
      background: var(--accent-soft); color: var(--accent);
      border: 1px solid var(--accent-muted);
      flex-shrink: 0;
    }

    .header-center {
      flex: 1; display: flex; align-items: center; padding: 0 20px;
    }
    .search-trigger {
      display: flex; align-items: center; gap: 9px;
      height: 36px; width: 100%; max-width: 420px;
      border: 1px solid var(--border); border-radius: 8px;
      background: var(--panel2); color: var(--muted);
      padding: 0 12px; font-size: 13px;
      cursor: pointer; transition: all 0.15s;
      font-family: inherit;
    }
    .search-trigger:hover { border-color: var(--accent); background: var(--panel); }
    .search-trigger svg { color: var(--faint); }
    .search-trigger .search-hint { margin-left: auto; }
    .search-hint {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 11.5px; color: var(--faint);
    }
    .search-hint kbd {
      font-family: 'Geist Mono', monospace;
      font-size: 10px; padding: 1px 5px;
      border: 1px solid var(--border);
      border-radius: 4px; background: var(--panel);
    }

    .header-actions {
      display: flex; align-items: center; gap: 8px;
      padding: 0 16px; flex-shrink: 0;
    }
    .hbtn {
      display: inline-flex; align-items: center; gap: 6px;
      height: 32px; padding: 0 11px; border-radius: 7px;
      font-size: 12.5px; font-weight: 600; font-family: inherit;
      border: 1px solid var(--border); background: var(--panel);
      color: var(--text2); cursor: pointer; transition: all 0.15s;
      text-decoration: none;
    }
    .hbtn:hover { border-color: var(--accent); color: var(--accent); }
    .hbtn.primary {
      background: var(--accent); color: #fff; border-color: transparent;
    }
    .hbtn.primary:hover { background: var(--accent2); color: #fff; }
    .hbtn-icon {
      width: 32px; height: 32px; padding: 0;
      justify-content: center;
    }

    /* ── Page Layout ───────────────────────────────────────────────────── */
    .page-layout {
      display: grid;
      grid-template-columns: var(--sidebar-w) minmax(0,1fr) var(--toc-w);
      min-height: calc(100vh - var(--header-h));
    }

    /* ── Left Sidebar ──────────────────────────────────────────────────── */
    .sidebar {
      position: sticky; top: var(--header-h);
      height: calc(100vh - var(--header-h));
      overflow-y: auto; border-right: 1px solid var(--border);
      background: var(--panel); padding: 20px 12px 40px;
    }
    .nav-section { margin-bottom: 8px; }
    .nav-group-label {
      font-size: 10.5px; font-weight: 700; letter-spacing: 0.07em;
      text-transform: uppercase; color: var(--faint);
      padding: 10px 10px 6px; display: block;
    }
    .nav-group-label:first-child { padding-top: 2px; }
    .nav-link {
      display: flex; align-items: center; gap: 9px;
      padding: 7px 10px; border-radius: 7px;
      color: var(--muted); font-size: 13.5px; font-weight: 500;
      transition: all 0.12s; position: relative;
    }
    .nav-link:hover { color: var(--text); background: var(--bg2); }
    .nav-link.active {
      color: var(--accent); background: var(--accent-soft); font-weight: 600;
    }
    .nav-link.active::before {
      content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
      width: 2.5px; height: 18px; background: var(--accent); border-radius: 0 2px 2px 0;
    }
    .nav-link-icon { color: var(--faint); transition: color 0.12s; }
    .nav-link:hover .nav-link-icon,
    .nav-link.active .nav-link-icon { color: var(--accent); }
    .nav-link-badge {
      margin-left: auto; font-size: 10px; font-weight: 700;
      padding: 1px 6px; border-radius: 99px;
      background: var(--accent-soft); color: var(--accent);
    }
    .nav-link-badge.soon {
      background: var(--bg2); color: var(--faint);
    }

    /* ── Main Content ──────────────────────────────────────────────────── */
    .main-content {
      padding: 40px 52px 80px;
      max-width: 820px; width: 100%; margin: 0 auto;
      min-width: 0;
    }

    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12.5px; color: var(--faint); margin-bottom: 24px;
    }
    .breadcrumb a { color: var(--muted); transition: color 0.12s; }
    .breadcrumb a:hover { color: var(--accent); }
    .breadcrumb-sep { color: var(--border); }

    .page-title {
      font-size: 34px; font-weight: 800; letter-spacing: -0.025em;
      line-height: 1.15; margin-bottom: 12px; color: var(--text);
    }
    .page-lead {
      font-size: 16px; line-height: 1.75; color: var(--muted);
      margin-bottom: 36px; max-width: 640px;
    }

    /* Content sections */
    .doc-section {
      padding: 32px 0; border-top: 1px solid var(--border);
      scroll-margin-top: calc(var(--header-h) + 20px);
    }
    .doc-section:first-of-type { border-top: none; padding-top: 0; }

    .doc-h2 {
      font-size: 22px; font-weight: 700; letter-spacing: -0.015em;
      margin-bottom: 14px; color: var(--text);
    }
    .doc-h3 {
      font-size: 16px; font-weight: 700;
      margin: 22px 0 10px; color: var(--text);
    }
    .doc-p {
      font-size: 14.5px; line-height: 1.75; color: var(--muted);
      margin-bottom: 14px;
    }
    .doc-p:last-child { margin-bottom: 0; }
    .doc-ul {
      list-style: none; padding: 0;
      display: flex; flex-direction: column; gap: 6px;
      margin: 12px 0;
    }
    .doc-ul li {
      display: flex; gap: 9px; align-items: flex-start;
      font-size: 14px; color: var(--muted); line-height: 1.6;
    }
    .doc-ul li::before {
      content: '—'; color: var(--faint); flex-shrink: 0; margin-top: 1px;
    }

    /* Inline code */
    .ic {
      font-family: 'Geist Mono', monospace;
      font-size: 12.5px; padding: 1px 6px;
      background: var(--bg2); border: 1px solid var(--border);
      border-radius: 5px; color: var(--text2);
      white-space: nowrap;
    }

    /* Callouts */
    .callout {
      display: flex; gap: 12px; align-items: flex-start;
      padding: 14px 18px; border-radius: 9px;
      border: 1px solid; font-size: 13.5px; line-height: 1.6;
      margin: 18px 0;
    }
    .callout-info  { border-color: #dbeafe; background: #eff6ff; color: #1e40af; }
    .callout-warn  { border-color: #fde68a; background: #fffbeb; color: #92400e; }
    .callout-tip   { border-color: #d1fae5; background: #ecfdf5; color: #065f46; }
    .callout-error { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
    html[data-theme="dark"] .callout-info  { border-color: #1e3a5f; background: #0f2040; color: #93c5fd; }
    html[data-theme="dark"] .callout-warn  { border-color: #78350f; background: #451a03; color: #fcd34d; }
    html[data-theme="dark"] .callout-tip   { border-color: #064e3b; background: #022c22; color: #6ee7b7; }
    html[data-theme="dark"] .callout-error { border-color: #7f1d1d; background: #450a0a; color: #fca5a5; }
    .callout-icon { font-size: 15px; flex-shrink: 0; margin-top: 1px; }

    /* Code block */
    .code-block {
      position: relative; border-radius: 10px;
      background: var(--code-bg); border: 1px solid var(--code-border);
      overflow: hidden; margin: 16px 0;
      box-shadow: var(--shadow-md);
    }
    .code-block-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 9px 14px;
      background: rgba(255,255,255,0.04);
      border-bottom: 1px solid var(--code-border);
    }
    .code-block-lang {
      font-size: 11.5px; font-weight: 600; color: #6b7a96;
      font-family: 'Geist Mono', monospace;
    }
    .code-block pre {
      padding: 16px 18px; margin: 0;
      font: 13px/1.75 'Geist Mono', monospace;
      color: var(--code-text); overflow-x: auto;
      white-space: pre;
    }
    .code-copy-btn {
      font-family: inherit; font-size: 11.5px; font-weight: 600;
      background: rgba(255,255,255,0.07); color: #6b7a96;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 6px; padding: 4px 10px;
      cursor: pointer; transition: all 0.15s;
    }
    .code-copy-btn:hover { background: rgba(255,255,255,0.12); color: #e6edf3; }
    .code-copy-btn.copied { color: #56d364; border-color: #56d364; }

    /* Table */
    .doc-table {
      width: 100%; border-collapse: collapse;
      font-size: 13.5px; margin: 16px 0;
      border: 1px solid var(--border); border-radius: 10px; overflow: hidden;
      background: var(--panel);
      box-shadow: var(--shadow-sm);
    }
    .doc-table th {
      padding: 10px 14px; background: var(--bg2);
      color: var(--text2); font-weight: 700; font-size: 12px;
      text-transform: uppercase; letter-spacing: 0.04em;
      border-bottom: 1px solid var(--border); text-align: left;
    }
    .doc-table td {
      padding: 11px 14px; border-bottom: 1px solid var(--border2);
      color: var(--muted); vertical-align: top; line-height: 1.6;
    }
    .doc-table tr:last-child td { border-bottom: none; }
    .doc-table td code { font-family: 'Geist Mono', monospace; font-size: 12px; }

    /* Type chip */
    .type-chip {
      display: inline-flex; align-items: center;
      font-family: 'Geist Mono', monospace; font-size: 11.5px; font-weight: 600;
      padding: 2px 7px; border-radius: 5px;
      background: var(--accent-soft); color: var(--accent);
      white-space: nowrap;
    }
    .type-chip.green  { background: rgba(34,197,94,.1);  color: #16a34a; }
    .type-chip.yellow { background: rgba(234,179,8,.1);  color: #a16207; }
    .type-chip.red    { background: rgba(239,68,68,.1);  color: #dc2626; }
    .type-chip.gray   { background: var(--bg2); color: var(--faint); }
    html[data-theme="dark"] .type-chip.green  { background: rgba(34,197,94,.12);  color: #4ade80; }
    html[data-theme="dark"] .type-chip.yellow { background: rgba(234,179,8,.12);  color: #fbbf24; }
    html[data-theme="dark"] .type-chip.red    { background: rgba(239,68,68,.12);  color: #f87171; }

    /* Status badge */
    .status-badge {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 12.5px; font-weight: 700;
      padding: 4px 10px; border-radius: 99px;
    }
    .status-badge::before {
      content: ''; width: 6px; height: 6px; border-radius: 50%;
    }
    .status-badge.ok    { background: rgba(34,197,94,.1); color: #15803d; }
    .status-badge.ok::before { background: #22c55e; }
    .status-badge.err   { background: rgba(239,68,68,.1); color: #b91c1c; }
    .status-badge.err::before { background: #ef4444; }
    html[data-theme="dark"] .status-badge.ok  { background: rgba(34,197,94,.12); color: #4ade80; }
    html[data-theme="dark"] .status-badge.err { background: rgba(239,68,68,.12); color: #f87171; }

    /* ── Endpoint Card ─────────────────────────────────────────────────── */
    .ep-card {
      border: 1px solid var(--border); border-radius: 12px;
      background: var(--panel); margin: 20px 0;
      overflow: hidden; scroll-margin-top: calc(var(--header-h) + 20px);
      box-shadow: var(--shadow-sm);
      transition: box-shadow 0.2s;
    }
    .ep-card:hover { box-shadow: var(--shadow-md); }

    .ep-header {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px;
      background: var(--panel2);
      border-bottom: 1px solid var(--border);
    }
    .method-badge {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 54px; height: 25px; border-radius: 6px;
      font-size: 11.5px; font-weight: 800; letter-spacing: 0.04em;
      flex-shrink: 0;
    }
    .method-GET    { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .method-POST   { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
    .method-PUT    { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .method-PATCH  { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .method-DELETE { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    html[data-theme="dark"] .method-GET    { background:rgba(34,197,94,.14);  color:#4ade80; border-color:rgba(34,197,94,.25); }
    html[data-theme="dark"] .method-POST   { background:rgba(99,102,241,.16); color:#a5b4fc; border-color:rgba(99,102,241,.3); }
    html[data-theme="dark"] .method-PUT,
    html[data-theme="dark"] .method-PATCH  { background:rgba(245,158,11,.14); color:#fbbf24; border-color:rgba(245,158,11,.25); }
    html[data-theme="dark"] .method-DELETE { background:rgba(239,68,68,.14);  color:#f87171; border-color:rgba(239,68,68,.25); }

    .ep-path {
      font-family: 'Geist Mono', monospace; font-size: 13px; font-weight: 600;
      color: var(--text); word-break: break-all; flex: 1;
    }
    .ep-meta { margin-left: auto; display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
    .ep-tag {
      font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px;
      background: var(--bg2); color: var(--faint); border: 1px solid var(--border);
    }
    .ep-tag.write { background: rgba(245,158,11,.1); color: #b45309; border-color: rgba(245,158,11,.25); }
    html[data-theme="dark"] .ep-tag.write { background:rgba(245,158,11,.14); color:#fbbf24; border-color:rgba(245,158,11,.25); }
    .ep-tag.cached { background: rgba(34,197,94,.1); color: #15803d; border-color: rgba(34,197,94,.25); }
    html[data-theme="dark"] .ep-tag.cached { background:rgba(34,197,94,.12); color:#4ade80; border-color:rgba(34,197,94,.25); }

    .ep-body { padding: 18px 20px; }
    .ep-title { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
    .ep-summary { font-size: 13.5px; color: var(--muted); line-height: 1.65; margin-bottom: 18px; }
    .ep-summary a { color: var(--accent); }

    /* ── Code Tabs ─────────────────────────────────────────────────────── */
    .code-tabs {
      border-radius: 10px; overflow: hidden;
      border: 1px solid var(--code-border);
      background: var(--code-bg); margin: 14px 0;
      box-shadow: var(--shadow-md);
    }
    .code-tabs-bar {
      display: flex; align-items: center;
      padding: 6px 10px; gap: 2px;
      background: rgba(255,255,255,0.04);
      border-bottom: 1px solid var(--code-border);
    }
    .code-tabs-langs { display: flex; gap: 2px; flex: 1; }
    .ctab {
      font: 600 12px 'Geist', sans-serif;
      color: #6b7a96; background: transparent; border: none;
      padding: 5px 11px; border-radius: 6px; cursor: pointer;
      transition: all 0.12s;
    }
    .ctab:hover { color: #c8d3e8; }
    .ctab.active { background: rgba(255,255,255,0.12); color: #e6edf3; }
    .ctab-copy {
      font: 600 11.5px 'Geist', sans-serif;
      background: rgba(255,255,255,0.07); color: #6b7a96;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 6px; padding: 4px 10px;
      cursor: pointer; transition: all 0.15s; flex-shrink: 0;
    }
    .ctab-copy:hover { color: #c8d3e8; background: rgba(255,255,255,0.12); }
    .ctab-copy.copied { color: #56d364; border-color: rgba(86,211,100,0.4); }
    .code-pane { display: none; }
    .code-pane.active { display: block; }
    .code-pane pre {
      margin: 0; padding: 16px 18px;
      font: 13px/1.75 'Geist Mono', monospace;
      color: var(--code-text); overflow-x: auto; white-space: pre;
    }

    /* ── Response Section ──────────────────────────────────────────────── */
    .ep-response-head {
      display: flex; align-items: center; justify-content: space-between;
      margin: 18px 0 8px;
    }
    .ep-response-label {
      font-size: 12.5px; font-weight: 700; color: var(--text2);
      display: flex; align-items: center; gap: 8px;
    }
    .response-200 {
      font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 99px;
      background: rgba(34,197,94,.1); color: #15803d;
    }
    html[data-theme="dark"] .response-200 { background:rgba(34,197,94,.12); color:#4ade80; }

    /* ── Try It Console ────────────────────────────────────────────────── */
    .try-console {
      margin-top: 18px; border-radius: 10px;
      border: 1.5px dashed var(--border); background: var(--panel2);
      overflow: hidden;
    }
    .try-toggle {
      display: flex; align-items: center; gap: 8px;
      padding: 11px 16px; font-size: 13px; font-weight: 600;
      color: var(--accent); cursor: pointer; user-select: none;
      list-style: none;
    }
    .try-toggle::-webkit-details-marker { display: none; }
    .try-toggle svg { transition: transform 0.2s; }
    details.try-console[open] .try-toggle svg { transform: rotate(90deg); }
    .try-body { padding: 0 16px 16px; }
    .try-fields {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
      gap: 10px; margin-bottom: 12px;
    }
    .try-field { display: flex; flex-direction: column; gap: 4px; }
    .try-label {
      font-size: 11.5px; font-weight: 700; color: var(--muted);
      display: flex; align-items: center; gap: 4px;
    }
    .try-label .req { color: #ef4444; }
    .try-input {
      height: 36px; padding: 0 11px;
      border: 1px solid var(--border); border-radius: 7px;
      background: var(--panel); color: var(--text);
      font: 13px 'Geist Mono', monospace; outline: none;
      transition: border-color 0.15s;
    }
    .try-input:focus { border-color: var(--accent); }
    .try-actions { display: flex; align-items: center; gap: 10px; }
    .try-send-btn {
      height: 36px; padding: 0 16px; border-radius: 7px; border: none;
      background: var(--accent); color: #fff;
      font: 700 13px 'Geist', sans-serif;
      cursor: pointer; transition: background 0.15s;
    }
    .try-send-btn:hover { background: var(--accent2); }
    .try-send-btn:disabled { opacity: 0.6; cursor: default; }
    .try-status { font-size: 13px; font-weight: 700; }
    .try-result {
      margin-top: 10px; max-height: 320px; overflow: auto;
      background: var(--code-bg); color: var(--code-text);
      border-radius: 8px; padding: 14px 16px;
      font: 12.5px/1.7 'Geist Mono', monospace;
      border: 1px solid var(--code-border);
    }

    /* ── Right TOC ─────────────────────────────────────────────────────── */
    .toc-panel {
      position: sticky; top: var(--header-h);
      height: calc(100vh - var(--header-h));
      overflow-y: auto; padding: 28px 16px 40px;
      border-left: 1px solid var(--border);
    }
    .toc-title {
      font-size: 10.5px; font-weight: 700; letter-spacing: 0.07em;
      text-transform: uppercase; color: var(--faint);
      margin-bottom: 10px; padding: 0 6px;
    }
    .toc-link {
      display: flex; align-items: center; gap: 6px;
      padding: 5px 8px; border-radius: 6px;
      font-size: 12.5px; color: var(--muted);
      transition: all 0.12s;
    }
    .toc-link:hover { color: var(--text); background: var(--bg2); }
    .toc-link.active { color: var(--accent); font-weight: 600; }

    /* ── Prev / Next Navigation ────────────────────────────────────────── */
    .page-nav {
      display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
      margin-top: 48px; padding-top: 28px; border-top: 1px solid var(--border);
    }
    .page-nav-card {
      display: flex; flex-direction: column; gap: 3px;
      padding: 16px; border-radius: 10px;
      border: 1px solid var(--border); background: var(--panel);
      transition: all 0.15s; box-shadow: var(--shadow-sm);
    }
    .page-nav-card:hover { border-color: var(--accent); transform: translateY(-1px); box-shadow: var(--shadow-md); }
    .page-nav-dir { font-size: 12px; color: var(--faint); font-weight: 500; }
    .page-nav-title { font-size: 14.5px; font-weight: 700; color: var(--text); }
    .page-nav-card.next { align-items: flex-end; text-align: right; }

    /* ── Search Modal ──────────────────────────────────────────────────── */
    .search-backdrop {
      position: fixed; inset: 0; background: rgba(0,0,0,0.4);
      backdrop-filter: blur(4px); z-index: 200;
      display: none; align-items: flex-start; justify-content: center;
      padding-top: 80px;
    }
    .search-backdrop.open { display: flex; }
    .search-modal {
      width: min(560px, calc(100vw - 32px));
      max-height: calc(100vh - 160px);
      background: var(--panel); border-radius: 14px;
      border: 1px solid var(--border);
      box-shadow: var(--shadow-lg);
      overflow: hidden; display: flex; flex-direction: column;
    }
    .search-input-wrap {
      display: flex; align-items: center; gap: 10px;
      padding: 14px 16px; border-bottom: 1px solid var(--border);
    }
    .search-input-wrap svg { color: var(--faint); flex-shrink: 0; }
    .search-input {
      flex: 1; border: none; background: transparent;
      font: 15px 'Geist', sans-serif; color: var(--text); outline: none;
    }
    .search-input::placeholder { color: var(--faint); }
    .search-results { overflow-y: auto; max-height: 400px; padding: 8px; }
    .search-result-item {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 12px; border-radius: 8px; cursor: pointer;
      transition: background 0.12s;
    }
    .search-result-item:hover { background: var(--bg2); }
    .search-result-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: var(--accent-soft); color: var(--accent);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .search-result-text strong { display: block; font-size: 14px; color: var(--text); }
    .search-result-text span  { display: block; font-size: 12.5px; color: var(--muted); margin-top: 2px; }
    .search-empty { padding: 24px; text-align: center; color: var(--faint); font-size: 14px; }

    /* ── Getting Started Quickstart ────────────────────────────────────── */
    .quickstart-steps {
      display: flex; flex-direction: column; gap: 0;
      margin: 20px 0;
    }
    .qs-step {
      display: flex; gap: 16px; align-items: flex-start;
      padding: 18px 0; border-bottom: 1px solid var(--border);
    }
    .qs-step:last-child { border-bottom: none; }
    .qs-num {
      width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
      background: var(--accent); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 800; margin-top: 1px;
    }
    .qs-text h3 { font-size: 15px; font-weight: 700; margin-bottom: 6px; }
    .qs-text p  { font-size: 13.5px; color: var(--muted); line-height: 1.65; }

    /* Architecture diagram */
    .arch-flow {
      display: flex; align-items: center; gap: 0;
      margin: 20px 0; overflow-x: auto; padding: 4px 0;
    }
    .arch-node {
      display: flex; flex-direction: column; align-items: center; gap: 6px;
      padding: 14px 18px; border-radius: 10px; border: 1px solid var(--border);
      background: var(--panel); min-width: 110px; text-align: center;
      font-size: 12.5px; font-weight: 600; color: var(--text2);
      box-shadow: var(--shadow-sm);
    }
    .arch-node-icon { font-size: 20px; }
    .arch-arrow {
      color: var(--faint); font-size: 18px; padding: 0 8px; flex-shrink: 0;
    }

    /* Changelog entries */
    .changelog-entry {
      border: 1px solid var(--border); border-radius: 10px;
      background: var(--panel); margin-bottom: 16px;
      overflow: hidden; box-shadow: var(--shadow-sm);
    }
    .changelog-header {
      display: flex; align-items: center; gap: 12px;
      padding: 14px 18px; border-bottom: 1px solid var(--border);
      background: var(--panel2);
    }
    .changelog-version {
      font-family: 'Geist Mono', monospace;
      font-size: 14px; font-weight: 700; color: var(--text);
    }
    .changelog-date { font-size: 12.5px; color: var(--faint); margin-left: auto; }
    .changelog-tag {
      font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 99px;
      text-transform: uppercase; letter-spacing: 0.05em;
    }
    .changelog-tag.stable { background: rgba(34,197,94,.1); color: #15803d; }
    .changelog-tag.latest { background: var(--accent-soft); color: var(--accent); }
    html[data-theme="dark"] .changelog-tag.stable { background:rgba(34,197,94,.12); color:#4ade80; }
    .changelog-body { padding: 16px 18px; }
    .changelog-body ul { padding-left: 18px; }
    .changelog-body li { font-size: 13.5px; color: var(--muted); margin-bottom: 5px; line-height: 1.6; }

    /* Error codes grid */
    .error-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 10px; margin: 16px 0;
    }
    .error-card {
      padding: 14px 16px; border-radius: 9px; border: 1px solid;
      text-align: center;
    }
    .error-card .code { font-family: 'Geist Mono', monospace; font-size: 22px; font-weight: 800; }
    .error-card .label { font-size: 12px; font-weight: 600; margin-top: 4px; }
    .ec-401 { border-color: #fde68a; background: #fffbeb; color: #b45309; }
    .ec-403 { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }
    .ec-404 { border-color: #dbeafe; background: #eff6ff; color: #1d4ed8; }
    .ec-422 { border-color: #e9d5ff; background: #faf5ff; color: #7c3aed; }
    .ec-429 { border-color: #fde68a; background: #fffbeb; color: #b45309; }
    .ec-5xx { border-color: #e5e7eb; background: #f9fafb; color: #374151; }
    html[data-theme="dark"] .ec-401 { border-color: #78350f; background: #451a03; color: #fbbf24; }
    html[data-theme="dark"] .ec-403 { border-color: #7f1d1d; background: #450a0a; color: #fca5a5; }
    html[data-theme="dark"] .ec-404 { border-color: #1e3a5f; background: #0f2040; color: #93c5fd; }
    html[data-theme="dark"] .ec-422 { border-color: #4c1d95; background: #2e1065; color: #c4b5fd; }
    html[data-theme="dark"] .ec-429 { border-color: #78350f; background: #451a03; color: #fbbf24; }
    html[data-theme="dark"] .ec-5xx { border-color: var(--border); background: var(--panel2); color: var(--muted); }

    /* Checklist */
    .checklist { list-style: none; padding: 0; margin: 14px 0; }
    .checklist li {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 9px 0; border-bottom: 1px solid var(--border2);
      font-size: 13.5px; color: var(--muted); line-height: 1.5;
    }
    .checklist li:last-child { border-bottom: none; }
    .cl-icon { font-size: 14px; flex-shrink: 0; margin-top: 1px; }

    /* Responsive */
    @media (max-width: 1180px) {
      .page-layout { grid-template-columns: var(--sidebar-w) 1fr; }
      .toc-panel { display: none; }
    }
    @media (max-width: 860px) {
      :root { --sidebar-w: 0px; }
      .header-brand { width: auto; }
      .sidebar { display: none; }
      .page-layout { display: block; }
      .main-content { padding: 24px 20px 60px; }
      .page-title { font-size: 26px; }
      .page-nav { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  {{-- ── Header ──────────────────────────────────────────────────────────── --}}
  <header class="site-header">
    <a class="header-brand" href="{{ route('developers.api-docs') }}" aria-label="Kitobchi Developer API">
      <img class="logo-light" src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi">
      <img class="logo-dark" src="{{ asset('images/logo/logo_white.png') }}" alt="Kitobchi">
      <span class="api-badge">API</span>
    </a>

    <div class="header-center">
      <button class="search-trigger" id="searchTrigger" type="button">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span>Hujjatlardan qidirish...</span>
        <span class="search-hint"><kbd>⌘</kbd><kbd>K</kbd></span>
      </button>
    </div>

    <div class="header-actions">
      <a class="hbtn" href="{{ $openapiUrl }}" target="_blank" rel="noopener" title="OpenAPI 3.0 spec">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        OpenAPI
      </a>
      <a class="hbtn" href="{{ $postmanUrl }}" target="_blank" rel="noopener" title="Postman Collection">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Postman
      </a>
      <button class="hbtn hbtn-icon" id="themeToggle" type="button" title="Mavzuni almashtirish">
        <svg class="theme-icon-light" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg class="theme-icon-dark" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
    </div>
  </header>

  {{-- ── Search Modal ───────────────────────────────────────────────────── --}}
  <div class="search-backdrop" id="searchBackdrop">
    <div class="search-modal" role="dialog" aria-label="Hujjatlardan qidirish">
      <div class="search-input-wrap">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input class="search-input" id="searchInput" type="search" placeholder="Mavzu, endpoint yoki sahifa izlang..." autocomplete="off">
      </div>
      <div class="search-results" id="searchResults"></div>
    </div>
  </div>

  {{-- ── Page Layout ────────────────────────────────────────────────────── --}}
  <div class="page-layout">

    {{-- Left Sidebar --}}
    <aside class="sidebar">
      <nav aria-label="API navigatsiyasi">
        @foreach($groups as $group => $slugs)
          <div class="nav-section">
            <span class="nav-group-label">{{ $group }}</span>
            @foreach($slugs as $slug)
              @php $p = $pages[$slug]; @endphp
              <a href="{{ $routeFor($slug) }}" class="nav-link {{ $currentSlug === $slug ? 'active' : '' }}">
                @if(!empty($p['icon']) && isset($iconSvgs[$p['icon']]))
                  <span class="nav-link-icon">{!! $iconSvgs[$p['icon']] !!}</span>
                @endif
                <span>{{ $p['title'] }}</span>
                @if(!empty($p['badge']))
                  <span class="nav-link-badge {{ str_contains($p['badge'], 'kunda') ? 'soon' : '' }}">{{ $p['badge'] }}</span>
                @endif
              </a>
            @endforeach
          </div>
        @endforeach
      </nav>
    </aside>

    {{-- Main Content --}}
    <main class="main-content">
      <div class="breadcrumb">
        <a href="{{ route('developers.api-docs') }}">Kitobchi API</a>
        <span class="breadcrumb-sep">/</span>
        <span>{{ $currentPage['group'] ?? 'Hujjatlar' }}</span>
        <span class="breadcrumb-sep">/</span>
        <span>{{ $currentPage['title'] }}</span>
      </div>

      <h1 class="page-title">{{ $currentPage['title'] }}</h1>
      <p class="page-lead">{{ $currentPage['description'] }}</p>

      @switch($currentSlug)

        {{-- ── GETTING STARTED ──────────────────────────────────────── --}}
        @case('getting-started')
          <div class="doc-section" id="overview">
            <h2 class="doc-h2">Nima bu API?</h2>
            <p class="doc-p">Kitobchi Client API — bu do'konlar, marketpleys platformalari va uchinchi tomon servislar uchun mo'ljallangan ochiq REST API. API yordamida siz:</p>
            <ul class="doc-ul">
              <li>Kitobchi katalogidan kitoblar va kanselyariyalar ro'yxatini olishingiz</li>
              <li>Qidiruv, autocomplete va kategoriya endpointlarini integratsiya qilishingiz</li>
              <li>Seller (do'kon) sifatida zaxirangizni <span class="ic">ISBN</span> yoki shtrix-kod bo'yicha real vaqtda yangilashingiz</li>
              <li>Deep link'lar orqali foydalanuvchilarni bevosita ilovaga yo'naltirishingiz</li>
            </ul>
            <div class="callout callout-info">
              <span class="callout-icon">ℹ️</span>
              <div>
                <strong>Base URL:</strong> <span class="ic">{{ $baseUrl }}</span><br>
                Barcha so'rovlar HTTPS orqali yuborilishi shart. HTTP so'rovlar rad etiladi.
              </div>
            </div>
          </div>

          <div class="doc-section" id="architecture">
            <h2 class="doc-h2">Arxitektura va oqim</h2>
            <p class="doc-p">Har bir so'rov quyidagi zanjirdan o'tadi:</p>
            <div class="arch-flow">
              <div class="arch-node"><span class="arch-node-icon">🖥️</span>Sizning server</div>
              <span class="arch-arrow">→</span>
              <div class="arch-node"><span class="arch-node-icon">🔑</span>X-App-ID<br>X-App-Secret</div>
              <span class="arch-arrow">→</span>
              <div class="arch-node"><span class="arch-node-icon">🛡️</span>Middleware<br>tekshiruv</div>
              <span class="arch-arrow">→</span>
              <div class="arch-node"><span class="arch-node-icon">⚡</span>Cache<br>Layer</div>
              <span class="arch-arrow">→</span>
              <div class="arch-node"><span class="arch-node-icon">📦</span>JSON<br>javob</div>
            </div>
            <div class="callout callout-warn">
              <span class="callout-icon">⚠️</span>
              <div>API kalitini <strong>hech qachon frontend JavaScript</strong> kodi ichida saqlamang. Barcha so'rovlar sizning server tomonidan yuborilishi lozim.</div>
            </div>
          </div>

          <div class="doc-section" id="first-request">
            <h2 class="doc-h2">Birinchi so'rovni yuborish</h2>
            <p class="doc-p">Kalit oldingiz va hamma narsa sozlandi. Endi birinchi so'rovni yuboring:</p>
            <div class="code-block">
              <div class="code-block-head">
                <span class="code-block-lang">bash</span>
                <button class="code-copy-btn" type="button">Nusxalash</button>
              </div>
              <pre>curl --request GET \
  --url '{{ $baseUrl }}/products/books?page=1' \
  --header 'Accept: application/json' \
  --header 'X-App-ID: app_xxxxxxxxxxxx' \
  --header 'X-App-Secret: your-secret-here'</pre>
            </div>
          </div>

          <div class="doc-section" id="response-format">
            <h2 class="doc-h2">Javob formati</h2>
            <p class="doc-p">Barcha javoblar standart JSON formatida qaytariladi:</p>
            <div class="code-block">
              <div class="code-block-head">
                <span class="code-block-lang">json — Muvaffaqiyatli javob</span>
                <button class="code-copy-btn" type="button">Nusxalash</button>
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
            <div class="code-block">
              <div class="code-block-head">
                <span class="code-block-lang">json — Xato javob</span>
                <button class="code-copy-btn" type="button">Nusxalash</button>
              </div>
              <pre>{
  "status": "error",
  "message": "Invalid or inactive API credentials"
}</pre>
            </div>
          </div>

          <div class="doc-section" id="best-practices">
            <h2 class="doc-h2">Eng yaxshi amaliyotlar</h2>
            <ul class="checklist">
              <li><span class="cl-icon">🔒</span><div><strong>Secret serverda:</strong> App Secret'ni faqat server muhitidagi o'zgaruvchilarda saqlang (masalan, <span class="ic">KITOBCHI_APP_SECRET</span>).</div></li>
              <li><span class="cl-icon">🔁</span><div><strong>POST'larda Idempotency-Key:</strong> Zaxira yangilash so'rovlarida <span class="ic">Idempotency-Key</span> headerini yuboring — takroriy so'rovlar bir marta bajariladi.</div></li>
              <li><span class="cl-icon">⚡</span><div><strong>ETag'ni saqlang:</strong> GET javobidagi <span class="ic">ETag</span> headerini keyingi so'rovda <span class="ic">If-None-Match</span> sifatida yuboring — 304 javob orqali bandwidth tejang.</div></li>
              <li><span class="cl-icon">📈</span><div><strong>Limit kuzatuvi:</strong> <span class="ic">X-RateLimit-Remaining-Minute</span> headerini monitoring qiling, chegara yaqinlashganda so'rovlarni kamaytiring.</div></li>
              <li><span class="cl-icon">🛡️</span><div><strong>IP whitelist:</strong> Boshqaruv panelida kalitingizga server IP manzilini qo'shing — qo'shimcha xavfsizlik qatlami.</div></li>
            </ul>
          </div>
          @break

        {{-- ── AUTHENTICATION ───────────────────────────────────────── --}}
        @case('authentication')
          <div class="doc-section" id="headers">
            <h2 class="doc-h2">Zarur headerlar</h2>
            <p class="doc-p">Har bir so'rovda quyidagi uchta header bo'lishi shart:</p>
            <div class="code-block">
              <div class="code-block-head">
                <span class="code-block-lang">http</span>
                <button class="code-copy-btn" type="button">Nusxalash</button>
              </div>
              <pre>X-App-ID: app_xxxxxxxxxxxx
X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json</pre>
            </div>
            <table class="doc-table">
              <thead><tr><th>Header</th><th>Tavsifi</th><th>Majburiy</th></tr></thead>
              <tbody>
                <tr><td><span class="ic">X-App-ID</span></td><td>Unikal ilova identifikatori — <code>app_</code> prefiks bilan boshlanadi</td><td><strong>Ha</strong></td></tr>
                <tr><td><span class="ic">X-App-Secret</span></td><td>Maxfiy kalit — hech qachon frontend'ga uzatilmasin</td><td><strong>Ha</strong></td></tr>
                <tr><td><span class="ic">Accept</span></td><td>JSON javob kutilishini bildiradi</td><td>Tavsiya</td></tr>
                <tr><td><span class="ic">Idempotency-Key</span></td><td>POST so'rovlarda takrorlanishning oldini oladi</td><td>POST'da tavsiya</td></tr>
              </tbody>
            </table>
          </div>

          <div class="doc-section" id="credentials">
            <h2 class="doc-h2">App ID va Secret olish</h2>
            <p class="doc-p">Kalitlar Kitobchi Boshqaruv panelidan yaratiladi:</p>
            <div class="quickstart-steps">
              <div class="qs-step">
                <div class="qs-num">1</div>
                <div class="qs-text">
                  <h3>Boshqaruv panelga kiring</h3>
                  <p>Admin hisob bilan <span class="ic">kitobchi.com/boshqaruv</span> ga o'ting.</p>
                </div>
              </div>
              <div class="qs-step">
                <div class="qs-num">2</div>
                <div class="qs-text">
                  <h3>API Clients bo'limiga o'ting</h3>
                  <p>Sozlamalar → API Clients → Yangi kalit yarating.</p>
                </div>
              </div>
              <div class="qs-step">
                <div class="qs-num">3</div>
                <div class="qs-text">
                  <h3>Ability va IP ni belgilang</h3>
                  <p><span class="ic">read</span> yoki <span class="ic">stock:write</span> ni tanlang. Seller kaliti uchun do'koningizni biriktiring.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="doc-section" id="secrets">
            <h2 class="doc-h2">Secret xavfsizligi</h2>
            <div class="callout callout-error">
              <span class="callout-icon">🚨</span>
              <div><strong>Secret oshkor bo'lsa:</strong> Boshqaruv paneli orqali darhol o'chiring va yangi kalit yarating. Eski secret avtomatik bekor qilinadi va barcha so'rovlar 403 bilan rad etiladi.</div>
            </div>
            <p class="doc-p">Xavfsiz saqlash uchun tavsiyalar:</p>
            <div class="code-block">
              <div class="code-block-head">
                <span class="code-block-lang">bash — .env fayli</span>
              </div>
              <pre>KITOBCHI_APP_ID=app_xxxxxxxxxxxx
KITOBCHI_APP_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</pre>
            </div>
          </div>

          <div class="doc-section" id="abilities">
            <h2 class="doc-h2">Ruxsatlar (Abilities)</h2>
            <p class="doc-p">Har bir kalit cheklangan ruxsatlar to'plami bilan yaratiladi:</p>
            <table class="doc-table">
              <thead><tr><th>Ability</th><th>Qamrovi</th><th>Shart</th></tr></thead>
              <tbody>
                <tr>
                  <td><span class="type-chip">read</span></td>
                  <td>Katalog, qidiruv va seller ma'lumotlari (o'qish)</td>
                  <td>Ixtiyoriy kalit</td>
                </tr>
                <tr>
                  <td><span class="type-chip yellow">stock:write</span></td>
                  <td>Zaxirani ISBN/shtrix-kod bo'yicha yangilash</td>
                  <td>Seller-scoped kalit majburiy</td>
                </tr>
              </tbody>
            </table>
            <div class="callout callout-tip">
              <span class="callout-icon">💡</span>
              <div><strong>Minimal ruxsatlar:</strong> Har bir integratsiya uchun alohida kalit yarating va faqat kerakli ability'larni bering. Bu xavfsizlikni oshiradi.</div>
            </div>
          </div>
          @break

        {{-- ── RATE LIMITS ──────────────────────────────────────────── --}}
        @case('rate-limits')
          <div class="doc-section" id="limits">
            <h2 class="doc-h2">Limitlar va response headerlar</h2>
            <p class="doc-p">Har bir API client uchun standart limitlar:</p>
            <div class="error-grid" style="grid-template-columns: 1fr 1fr; margin: 16px 0;">
              <div class="error-card ec-404" style="border-color: #dbeafe; background: #eff6ff; color: #1d4ed8;">
                <div class="code">{{ $defaultLimits['per_second'] }}</div>
                <div class="label">so'rov / soniya</div>
              </div>
              <div class="error-card ec-404" style="border-color: #dbeafe; background: #eff6ff; color: #1d4ed8;">
                <div class="code">{{ $defaultLimits['per_minute'] }}</div>
                <div class="label">so'rov / daqiqa</div>
              </div>
            </div>
            <p class="doc-p">Limit oshib ketganda <strong>HTTP 429</strong> qaytariladi:</p>
            <div class="code-block">
              <div class="code-block-head"><span class="code-block-lang">json — 429 Too Many Requests</span></div>
              <pre>{
  "status": "error",
  "message": "Rate limit oshib ketdi. Keyinroq urinib ko'ring.",
  "retry_after": 1
}</pre>
            </div>
            <table class="doc-table">
              <thead><tr><th>Header</th><th>Ma'nosi</th></tr></thead>
              <tbody>
                <tr><td><span class="ic">X-RateLimit-Limit-Second</span></td><td>1 soniyadagi maksimal so'rovlar</td></tr>
                <tr><td><span class="ic">X-RateLimit-Limit-Minute</span></td><td>1 daqiqadagi maksimal so'rovlar</td></tr>
                <tr><td><span class="ic">X-RateLimit-Remaining-Second</span></td><td>Joriy soniyadagi qolgan kvota</td></tr>
                <tr><td><span class="ic">X-RateLimit-Remaining-Minute</span></td><td>Joriy daqiqadagi qolgan kvota</td></tr>
                <tr><td><span class="ic">Retry-After</span></td><td>Limit oshganda kutish vaqti (soniya)</td></tr>
              </tbody>
            </table>
          </div>

          <div class="doc-section" id="cache">
            <h2 class="doc-h2">Response keshi</h2>
            <p class="doc-p">GET endpointlari avtomatik ravishda <strong>120 soniya</strong> keshlanadi. Kesh holati har bir javobdagi <span class="ic">X-API-Cache</span> headerida ko'rsatiladi:</p>
            <table class="doc-table">
              <thead><tr><th>Qiymat</th><th>Ma'nosi</th></tr></thead>
              <tbody>
                <tr><td><span class="type-chip green">MISS</span></td><td>Keshda yo'q edi, yangi javob yaratildi va saqlandi</td></tr>
                <tr><td><span class="type-chip yellow">HIT</span></td><td>Keshdan qaytarildi — tezroq va limitni sarflamadi</td></tr>
                <tr><td><span class="type-chip gray">BYPASS</span></td><td>Kesh o'tkazib yuborildi (POST, no-cache header)</td></tr>
              </tbody>
            </table>
            <div class="callout callout-tip">
              <span class="callout-icon">💡</span>
              <div>Keshni bekor qilish uchun so'rovda <span class="ic">Cache-Control: no-cache</span> headerini yuboring.</div>
            </div>
          </div>

          <div class="doc-section" id="etag">
            <h2 class="doc-h2">ETag va 304 Not Modified</h2>
            <p class="doc-p">Bandwidth tejash uchun ETag mexanizmidan foydalaning:</p>
            <div class="code-block">
              <div class="code-block-head"><span class="code-block-lang">http — 1-so'rov: ETag saqlang</span></div>
              <pre>HTTP/1.1 200 OK
ETag: "a3f5c8e9b1d2..."
X-API-Cache: MISS</pre>
            </div>
            <div class="code-block">
              <div class="code-block-head"><span class="code-block-lang">http — 2-so'rov: ETag yuboring</span></div>
              <pre>GET /products/books HTTP/1.1
If-None-Match: "a3f5c8e9b1d2..."

HTTP/1.1 304 Not Modified  ← Ma'lumot o'zgarmagan, trafik sarf etilmadi</pre>
            </div>
          </div>
          @break

        {{-- ── PAGINATION ───────────────────────────────────────────── --}}
        @case('pagination')
          <div class="doc-section" id="params">
            <h2 class="doc-h2">Parametrlar</h2>
            <table class="doc-table">
              <thead><tr><th>Parametr</th><th>Turi</th><th>Default</th><th>Tavsifi</th></tr></thead>
              <tbody>
                <tr><td><span class="ic">page</span></td><td><span class="type-chip">integer</span></td><td>1</td><td>Sahifa raqami (1 dan boshlanadi)</td></tr>
                <tr><td><span class="ic">per_page</span></td><td><span class="type-chip">integer</span></td><td>20</td><td>Sahifadagi elementlar soni (maks. 100)</td></tr>
                <tr><td><span class="ic">q</span></td><td><span class="type-chip">string</span></td><td>—</td><td>Nom yoki tavsif bo'yicha matnli qidiruv</td></tr>
                <tr><td><span class="ic">sort</span></td><td><span class="type-chip">string</span></td><td>popular</td><td><span class="ic">popular</span>, <span class="ic">new</span>, <span class="ic">price_asc</span>, <span class="ic">price_desc</span></td></tr>
                <tr><td><span class="ic">seller_id</span></td><td><span class="type-chip">integer</span></td><td>—</td><td>Bitta do'kon mahsulotlariga cheklash</td></tr>
              </tbody>
            </table>
          </div>

          <div class="doc-section" id="meta">
            <h2 class="doc-h2">Meta bloki</h2>
            <p class="doc-p">Sahifalangan javoblar <span class="ic">meta</span> blokini o'z ichiga oladi:</p>
            <div class="code-block">
              <div class="code-block-head"><span class="code-block-lang">json — Sahifalangan javob tuzilmasi</span></div>
              <pre>{
  "status": "success",
  "data": [
    { "id": 1, "name": "Kitob nomi", "price": 89000 },
    ...
  ],
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
                <tr><td><span class="ic">meta.page</span></td><td><span class="type-chip">integer</span></td><td>Joriy sahifa raqami</td></tr>
                <tr><td><span class="ic">meta.per_page</span></td><td><span class="type-chip">integer</span></td><td>Sahifadagi elementlar soni</td></tr>
                <tr><td><span class="ic">meta.total</span></td><td><span class="type-chip">integer</span></td><td>Jami elementlar soni</td></tr>
                <tr><td><span class="ic">meta.last_page</span></td><td><span class="type-chip">integer</span></td><td>Oxirgi sahifa raqami</td></tr>
              </tbody>
            </table>
          </div>

          <div class="doc-section" id="filtering">
            <h2 class="doc-h2">Filtr va tartiblash</h2>
            <div class="code-block">
              <div class="code-block-head"><span class="code-block-lang">bash — Misollar</span></div>
              <pre># Nom bo'yicha filtr
GET /products/books?q=python&page=1

# Narx bo'yicha o'sish tartibida
GET /products/books?sort=price_asc&per_page=50

# Bitta do'kon va yangilardan boshlash
GET /products/books?seller_id=12&sort=new</pre>
            </div>
          </div>

          <div class="doc-section" id="empty-results">
            <h2 class="doc-h2">Bo'sh natijalar</h2>
            <p class="doc-p">Hech narsa topilmasa ham xato qaytarilmaydi — bo'sh massiv bilan <strong>200 OK</strong> qaytariladi:</p>
            <div class="code-block">
              <div class="code-block-head"><span class="code-block-lang">json — Bo'sh natija</span></div>
              <pre>{
  "status": "success",
  "data": [],
  "meta": { "page": 1, "per_page": 20, "total": 0, "last_page": 1 }
}</pre>
            </div>
          </div>
          @break

        {{-- ── PRODUCTS / SEARCH / SELLER — endpoint sahifalari ─────── --}}
        @case('products')
          <div class="doc-section">
            <h2 class="doc-h2">Mahsulotlar API</h2>
            <p class="doc-p">Katalogdagi barcha kitoblar, kanselyariyalar, sellerlar va tavsiyalar. Barcha GET endpointlar <span class="ic">read</span> ability bilan, zaxira yangilash esa <span class="ic">stock:write</span> talab qiladi.</p>
          </div>
          @foreach($pageEndpoints as $endpoint)
            <x-api-docs-endpoint :endpoint="$endpoint" />
          @endforeach
          @break

        @case('search')
          <div class="doc-section">
            <h2 class="doc-h2">Qidiruv API</h2>
            <p class="doc-p">Global qidiruv, avtomatik to'ldirish (autocomplete), trenddagi so'rovlar va kategoriyalar. Autocomplete endpointini UI'da debounce (≥ 300ms) bilan chaqiring.</p>
          </div>
          @foreach($pageEndpoints as $endpoint)
            <x-api-docs-endpoint :endpoint="$endpoint" />
          @endforeach
          @break

        @case('seller')
          <div class="doc-section">
            <h2 class="doc-h2">Seller API — Zaxira boshqaruvi</h2>
            <div class="callout callout-warn">
              <span class="callout-icon">⚠️</span>
              <div>
                Bu endpointlar faqat <span class="ic">stock:write</span> ability va <strong>seller-scoped</strong> kalit bilan ishlaydi. Kalit muayyan do'kongagina bog'langan — boshqa do'konlarga ta'sir qila olmaydi.
              </div>
            </div>
            <p class="doc-p">POST so'rovlarda <span class="ic">Idempotency-Key</span> headerini yuboring — internet uzilsa ham zaxira ikki marta kamaymasligi uchun.</p>
          </div>
          @foreach($pageEndpoints as $endpoint)
            <x-api-docs-endpoint :endpoint="$endpoint" />
          @endforeach
          @break

        {{-- ── WEBHOOKS ─────────────────────────────────────────────── --}}
        @case('webhooks')
          <div class="doc-section" id="events">
            <h2 class="doc-h2">Webhook hodisalari</h2>
            <div class="callout callout-info">
              <span class="callout-icon">🔔</span>
              <div><strong>Tez kunda:</strong> Webhook funksionalligi hozirda ishlab chiqilmoqda. Qo'shimcha ma'lumot uchun developers@kitobchi.com ga murojaat qiling.</div>
            </div>
            <p class="doc-p">Webhooklar tayyor bo'lgandan keyin quyidagi hodisalarga obuna bo'lish mumkin bo'ladi:</p>
            <table class="doc-table">
              <thead><tr><th>Hodisa</th><th>Qachon yuborilar</th></tr></thead>
              <tbody>
                <tr><td><span class="ic">product.stock_changed</span></td><td>Mahsulot zaxirasi o'zgarganda</td></tr>
                <tr><td><span class="ic">product.price_changed</span></td><td>Mahsulot narxi yangilanganda</td></tr>
                <tr><td><span class="ic">product.status_changed</span></td><td>Mahsulot faollik holati o'zgarganda</td></tr>
                <tr><td><span class="ic">seller.status_changed</span></td><td>Do'kon holati o'zgarganda</td></tr>
              </tbody>
            </table>
          </div>

          <div class="doc-section" id="signature">
            <h2 class="doc-h2">HMAC imzo tekshiruvi</h2>
            <p class="doc-p">Webhook so'rovining haqiqiyligini <span class="ic">X-Kitobchi-Signature</span> headeridagi HMAC-SHA256 imzo orqali tekshiring:</p>
            <div class="code-block">
              <div class="code-block-head"><span class="code-block-lang">php</span></div>
              <pre>$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_KITOBCHI_SIGNATURE'] ?? '';
$expected  = hash_hmac('sha256', $payload, env('KITOBCHI_WEBHOOK_SECRET'));

if (! hash_equals($expected, $signature)) {
    http_response_code(401);
    exit;
}</pre>
            </div>
          </div>

          <div class="doc-section" id="retries">
            <h2 class="doc-h2">Qayta yuborish siyosati</h2>
            <p class="doc-p">Endpoint 200 qaytarmasa, webhook 3 marta qayta yuboriladi: 1 daqiqa, 5 daqiqa va 30 daqiqadan keyin. 3 urinishdan so'ng webhook o'chiriladi.</p>
          </div>
          @break

        {{-- ── DEEPLINK ─────────────────────────────────────────────── --}}
        @case('deeplink')
          <div class="doc-section">
            <h2 class="doc-h2">Deep Link Generator</h2>
            <p class="doc-p">Mobil ilova URL schemalari, web havolalar, Play Market va App Store linklarini bitta so'rov bilan oling. Marketing kampaniyalari, QR kodlar va push notification uchun mos.</p>
          </div>
          @foreach($pageEndpoints as $endpoint)
            <x-api-docs-endpoint :endpoint="$endpoint" />
          @endforeach
          @break

        {{-- ── ERRORS ───────────────────────────────────────────────── --}}
        @case('errors')
          <div class="doc-section" id="statuses">
            <h2 class="doc-h2">HTTP status kodlar</h2>
            <div class="error-grid">
              <div class="error-card ec-401"><div class="code">401</div><div class="label">Unauthorized</div></div>
              <div class="error-card ec-403"><div class="code">403</div><div class="label">Forbidden</div></div>
              <div class="error-card ec-404"><div class="code">404</div><div class="label">Not Found</div></div>
              <div class="error-card ec-422"><div class="code">422</div><div class="label">Validation Error</div></div>
              <div class="error-card ec-429"><div class="code">429</div><div class="label">Rate Limited</div></div>
              <div class="error-card ec-5xx"><div class="code">5xx</div><div class="label">Server Error</div></div>
            </div>
            <table class="doc-table" style="margin-top: 20px;">
              <thead><tr><th>Kod</th><th>Sabab</th><th>Hal qilish</th></tr></thead>
              <tbody>
                <tr><td><span class="type-chip red">401</span></td><td><span class="ic">X-App-ID</span> yoki <span class="ic">X-App-Secret</span> header yo'q</td><td>Ikkala headerni ham yuboring</td></tr>
                <tr><td><span class="type-chip red">403</span></td><td>Noto'g'ri Secret, nofaol kalit yoki IP allowlistdan tashqari</td><td>Kalitni tekshiring, IP ni qo'shing</td></tr>
                <tr><td><span class="type-chip">404</span></td><td>Endpoint yo'q yoki ma'lumot topilmadi</td><td>URL va ID ni tekshiring</td></tr>
                <tr><td><span class="type-chip yellow">422</span></td><td>Majburiy parametr yo'q yoki noto'g'ri qiymat</td><td><span class="ic">message</span> maydonini o'qing</td></tr>
                <tr><td><span class="type-chip yellow">429</span></td><td>Rate limit oshib ketdi</td><td><span class="ic">Retry-After</span> soniya kutib yuboring</td></tr>
                <tr><td><span class="type-chip gray">5xx</span></td><td>Server ichki xatosi</td><td>Exponential backoff bilan qayta urinib ko'ring</td></tr>
              </tbody>
            </table>
          </div>

          <div class="doc-section" id="format">
            <h2 class="doc-h2">Xato JSON formati</h2>
            <p class="doc-p">Barcha xatolar standart formatda qaytariladi:</p>
            <div class="code-block">
              <div class="code-block-head"><span class="code-block-lang">json</span></div>
              <pre>{
  "status": "error",
  "message": "Invalid or inactive API credentials"
}</pre>
            </div>
            <p class="doc-p">Validation xatolarida qo'shimcha <span class="ic">errors</span> maydoni bo'lishi mumkin:</p>
            <div class="code-block">
              <div class="code-block-head"><span class="code-block-lang">json — 422 Validation Error</span></div>
              <pre>{
  "status": "error",
  "message": "Provide \"stock\" or \"delta\"."
}</pre>
            </div>
          </div>

          <div class="doc-section" id="common-errors">
            <h2 class="doc-h2">Tez-tez uchraydigan xatolar</h2>
            <table class="doc-table">
              <thead><tr><th>Xato xabari</th><th>Sabab</th></tr></thead>
              <tbody>
                <tr><td><span class="ic">API credentials missing</span></td><td>Header'lar umuman yuborilmagan</td></tr>
                <tr><td><span class="ic">Invalid or inactive API credentials</span></td><td>Secret noto'g'ri yoki kalit o'chirilgan</td></tr>
                <tr><td><span class="ic">IP address not allowed for this API key</span></td><td>Server IP allowlistda yo'q</td></tr>
                <tr><td><span class="ic">Missing ability: stock:write</span></td><td>Kalit <span class="ic">read</span> only, yozish ruxsati yo'q</td></tr>
                <tr><td><span class="ic">This API key is not scoped to a seller</span></td><td>Seller API uchun seller-scoped kalit kerak</td></tr>
                <tr><td><span class="ic">ISBN not found in your store</span></td><td>ISBN shu do'konda ro'yxatdan o'tmagan</td></tr>
              </tbody>
            </table>
          </div>

          <div class="doc-section" id="checklist">
            <h2 class="doc-h2">Integratsiya checklisti</h2>
            <ul class="checklist">
              <li><span class="cl-icon">✅</span>App ID va Secret environment variable'larida saqlangan (git'da yo'q)</li>
              <li><span class="cl-icon">✅</span>Barcha API so'rovlar serverdan yuborilmoqda (frontend'dan emas)</li>
              <li><span class="cl-icon">✅</span>POST so'rovlarda <span class="ic">Idempotency-Key</span> header yuborilmoqda</li>
              <li><span class="cl-icon">✅</span>429 xatosida <span class="ic">Retry-After</span> ni kuzatib kutilmoqda</li>
              <li><span class="cl-icon">✅</span>ETag saqlash va <span class="ic">If-None-Match</span> orqali 304 optimizatsiya qilingan</li>
              <li><span class="cl-icon">✅</span>IP allowlist Boshqaruv panelida sozlangan</li>
              <li><span class="cl-icon">✅</span>Seller API uchun seller-scoped kalit ishlatilmoqda</li>
            </ul>
          </div>
          @break

        {{-- ── CHANGELOG ────────────────────────────────────────────── --}}
        @case('changelog')
          <div class="doc-section" id="versioning">
            <h2 class="doc-h2">Versiyalash siyosati</h2>
            <p class="doc-p">API URL-da versiya ko'rsatiladi: <span class="ic">/api/v1/client/</span>. Breaking change bo'lganda yangi versiya (<span class="ic">/api/v2/client/</span>) e'lon qilinadi va eski versiya kamida <strong>6 oy</strong> parallel ishlaydi. Non-breaking yangiliklar (yangi endpointlar, yangi response maydonlar) joriy versiyaga qo'shiladi va hujjatlashtiriladi.</p>
          </div>

          <div class="doc-section" id="v1-2">
            <h2 class="doc-h2">Joriy versiya</h2>
            <div class="changelog-entry">
              <div class="changelog-header">
                <span class="changelog-version">v1.2.0</span>
                <span class="changelog-tag latest">Latest</span>
                <span class="changelog-date">Sentyabr 2026</span>
              </div>
              <div class="changelog-body">
                <ul>
                  <li>🆕 <strong>GET /products/mine</strong> — Seller o'z mahsulotlari va zaxirasini ko'rishi uchun yangi endpoint</li>
                  <li>🆕 <strong>GET /deeplink</strong> — Mobil va web uchun deep link generator endpoint</li>
                  <li>🆕 <strong>Branch stock tracking</strong> — Zaxira filial darajasida kuzatilmoqda</li>
                  <li>⚡ ETag + 304 kesh mexanizmi barcha GET endpointlarga qo'shildi</li>
                  <li>⚡ <span class="ic">Idempotency-Key</span> header POST so'rovlarda 24 soat keshlanmoqda</li>
                  <li>🐛 <span class="ic">by-publisher</span> endpointidagi pagination muammosi tuzatildi</li>
                </ul>
              </div>
            </div>

            <div class="changelog-entry">
              <div class="changelog-header">
                <span class="changelog-version">v1.1.0</span>
                <span class="changelog-tag stable">Stable</span>
                <span class="changelog-date">Iyun 2026</span>
              </div>
              <div class="changelog-body">
                <ul>
                  <li>🆕 <strong>POST /products/stock/by-code</strong> — ISBN va shtrix-kod bo'yicha zaxira yangilash (stock:write)</li>
                  <li>🆕 IP allowlist — kalitga server IP manzillarini biriktirish imkoniyati</li>
                  <li>🆕 <span class="ic">X-API-Cache</span> header — MISS / HIT / BYPASS holatlarini ko'rsatish</li>
                  <li>⚡ Rate limit headerlar yangilandi: <span class="ic">X-RateLimit-Remaining-Second/Minute</span></li>
                  <li>🐛 <span class="ic">seller_id</span> filter parametri products/books da ishlamayotgan muammo tuzatildi</li>
                </ul>
              </div>
            </div>

            <div class="changelog-entry">
              <div class="changelog-header">
                <span class="changelog-version">v1.0.0</span>
                <span class="changelog-tag stable">Stable</span>
                <span class="changelog-date">Yanvar 2026</span>
              </div>
              <div class="changelog-body">
                <ul>
                  <li>🚀 <strong>Birinchi rasmiy reliz</strong></li>
                  <li>🆕 Products endpointlari: books, stationery, recommendations, authors, publishers</li>
                  <li>🆕 Search: global, suggestions, trending, categories</li>
                  <li>🆕 Sellers: list, by-qr, profile, by-isbn</li>
                  <li>🆕 <span class="ic">X-App-ID</span> + <span class="ic">X-App-Secret</span> autentifikatsiya tizimi</li>
                  <li>🆕 OpenAPI 3.0 va Postman Collection eksport</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="doc-section" id="future">
            <h2 class="doc-h2">Kelajakdagi rejalar</h2>
            <p class="doc-p">Keyingi versiyalarda rejalashtirilgan:</p>
            <ul class="doc-ul">
              <li>Webhook hodisalari (product.stock_changed, product.price_changed)</li>
              <li>Batch endpoint — bir so'rovda ko'p ISBN yangilash</li>
              <li>Orders API — buyurtma holati va tracking</li>
              <li>OAuth 2.0 autentifikatsiya varianti</li>
            </ul>
            <div class="callout callout-info">
              <span class="callout-icon">📬</span>
              <div>Yangiliklar va breaking changlardan avval xabardor bo'lish uchun: <strong>developers@kitobchi.com</strong></div>
            </div>
          </div>
          @break

        {{-- ── DEFAULT: GETTING STARTED (fallback) ─────────────────── --}}
        @default
          <div class="doc-section" id="overview">
            <h2 class="doc-h2">Umumiy tushuncha</h2>
            <p class="doc-p">Kitobchi Client API — hamkor va do'kon integratsiyalari uchun zamonaviy REST API.</p>
            <div class="callout callout-info">
              <span class="callout-icon">🔗</span>
              <div>Base URL: <span class="ic">{{ $baseUrl }}</span></div>
            </div>
          </div>
      @endswitch

      {{-- Prev / Next Navigation --}}
      @php
        $orderedSlugs = array_keys($pages);
        $index = array_search($currentSlug, $orderedSlugs, true);
        $prev  = $index > 0 ? $orderedSlugs[$index - 1] : null;
        $next  = $index !== false && $index < count($orderedSlugs) - 1 ? $orderedSlugs[$index + 1] : null;
      @endphp
      <nav class="page-nav" aria-label="Sahifalar navigatsiyasi">
        @if($prev)
          <a class="page-nav-card" href="{{ $routeFor($prev) }}">
            <span class="page-nav-dir">← Oldingi</span>
            <span class="page-nav-title">{{ $pages[$prev]['title'] }}</span>
          </a>
        @else
          <div></div>
        @endif
        @if($next)
          <a class="page-nav-card next" href="{{ $routeFor($next) }}">
            <span class="page-nav-dir">Keyingi →</span>
            <span class="page-nav-title">{{ $pages[$next]['title'] }}</span>
          </a>
        @endif
      </nav>
    </main>

    {{-- Right TOC --}}
    <aside class="toc-panel">
      <div class="toc-title">Shu sahifada</div>
      @foreach($toc as $item)
        <a href="#{{ $item['id'] }}" class="toc-link">{{ $item['label'] }}</a>
      @endforeach
    </aside>
  </div>

  <script>
    window.__docsPages = @json($allPages);
    window.__docsBase  = @json(route('developers.api-docs'));

    // ── Theme ───────────────────────────────────────────────────────────
    const html = document.documentElement;
    const saved = localStorage.getItem('kb_docs_theme');
    if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      html.dataset.theme = 'dark';
    }
    function applyThemeIcons() {
      const dark = html.dataset.theme === 'dark';
      document.querySelectorAll('.theme-icon-light').forEach(el => el.style.display = dark ? 'none' : '');
      document.querySelectorAll('.theme-icon-dark').forEach(el  => el.style.display = dark ? '' : 'none');
    }
    applyThemeIcons();
    document.getElementById('themeToggle')?.addEventListener('click', () => {
      html.dataset.theme = html.dataset.theme === 'dark' ? 'light' : 'dark';
      localStorage.setItem('kb_docs_theme', html.dataset.theme);
      applyThemeIcons();
    });

    // ── Search ──────────────────────────────────────────────────────────
    const backdrop  = document.getElementById('searchBackdrop');
    const trigger   = document.getElementById('searchTrigger');
    const input     = document.getElementById('searchInput');
    const results   = document.getElementById('searchResults');
    const pages     = window.__docsPages || [];
    const baseRoute = window.__docsBase  || '/developers/api';

    function pageUrl(slug) {
      return slug === 'getting-started' ? baseRoute : `${baseRoute}/${slug}`;
    }

    const iconMap = {
      rocket:'🚀', key:'🔑', gauge:'⚡', layers:'📄', book:'📚',
      search:'🔍', store:'🏪', zap:'⚡', alert:'⚠️', history:'📋'
    };

    function renderSearch(q = '') {
      const query = q.trim().toLowerCase();
      const matches = pages.filter(p =>
        !query || `${p.title} ${p.description} ${p.group}`.toLowerCase().includes(query)
      );
      if (!matches.length) {
        results.innerHTML = '<div class="search-empty">Hech narsa topilmadi.</div>';
        return;
      }
      results.innerHTML = matches.map(p => `
        <a class="search-result-item" href="${pageUrl(p.slug)}">
          <div class="search-result-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </div>
          <div class="search-result-text">
            <strong>${p.title}</strong>
            <span>${p.description}</span>
          </div>
        </a>
      `).join('');
    }

    function openSearch() {
      renderSearch('');
      backdrop.classList.add('open');
      setTimeout(() => input?.focus(), 30);
    }
    function closeSearch() { backdrop.classList.remove('open'); }

    trigger?.addEventListener('click', openSearch);
    input?.addEventListener('input', () => renderSearch(input.value));
    backdrop?.addEventListener('click', e => { if (e.target === backdrop) closeSearch(); });
    document.addEventListener('keydown', e => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openSearch(); }
      if (e.key === 'Escape') closeSearch();
    });

    // ── TOC active highlight (Intersection Observer) ─────────────────────
    const tocLinks = document.querySelectorAll('.toc-link');
    if (tocLinks.length && 'IntersectionObserver' in window) {
      const obs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            tocLinks.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + entry.target.id));
          }
        });
      }, { rootMargin: '-60px 0px -70% 0px', threshold: 0 });
      document.querySelectorAll('[id]').forEach(el => {
        if ([...tocLinks].some(l => l.getAttribute('href') === '#' + el.id)) obs.observe(el);
      });
    }

    // ── Code Tabs ──────────────────────────────────────────────────────
    document.addEventListener('click', e => {
      const tab = e.target.closest('.ctab');
      if (!tab) return;
      const wrap   = tab.closest('.code-tabs');
      const target = tab.dataset.tab;
      wrap.querySelectorAll('.ctab').forEach(t   => t.classList.toggle('active', t === tab));
      wrap.querySelectorAll('.code-pane').forEach(p => p.classList.toggle('active', p.dataset.pane === target));
    });

    // ── Copy Buttons ───────────────────────────────────────────────────
    async function copyText(text, btn) {
      try {
        await navigator.clipboard.writeText(text);
        const orig = btn.textContent;
        btn.classList.add('copied'); btn.textContent = '✓ Nusxalandi';
        setTimeout(() => { btn.classList.remove('copied'); btn.textContent = orig; }, 1400);
      } catch (_) {}
    }

    document.addEventListener('click', async e => {
      const btn = e.target.closest('.ctab-copy, .code-copy-btn');
      if (!btn) return;
      let text = '';
      const tabs = btn.closest('.code-tabs');
      const block = btn.closest('.code-block');
      const ep = btn.closest('.ep-response, .ep-body');
      if (tabs) {
        const pane = tabs.querySelector('.code-pane.active') || tabs.querySelector('.code-pane');
        text = pane?.innerText || '';
      } else if (block) {
        text = block.querySelector('pre')?.innerText || '';
      } else if (ep) {
        text = ep.querySelector('code')?.innerText || '';
      }
      await copyText(text, btn);
    });

    // ── Try It Out ─────────────────────────────────────────────────────
    document.addEventListener('click', async e => {
      const btn = e.target.closest('[data-try-send]');
      if (!btn) return;
      const root   = btn.closest('[data-tryit]');
      const appid  = root.querySelector('[data-try="appid"]')?.value?.trim() || '';
      const secret = root.querySelector('[data-try="secret"]')?.value?.trim() || '';
      let url      = root.dataset.urlTemplate || '';

      root.querySelectorAll('[data-try-path]').forEach(inp =>
        url = url.replace('{' + inp.dataset.tryPath + '}', encodeURIComponent(inp.value.trim()))
      );
      const qs = [];
      root.querySelectorAll('[data-try-query]').forEach(inp => {
        const v = inp.value.trim();
        if (v) qs.push(encodeURIComponent(inp.dataset.tryQuery) + '=' + encodeURIComponent(v));
      });
      if (qs.length) url += (url.includes('?') ? '&' : '?') + qs.join('&');

      const statusEl = root.querySelector('[data-try-status]');
      const resultEl = root.querySelector('[data-try-result]');

      statusEl.textContent = 'Yuborilmoqda…';
      statusEl.style.color = 'var(--muted)';
      btn.disabled = true;
      const t0 = performance.now();
      try {
        const res = await fetch(url, {
          headers: { Accept: 'application/json', 'X-App-ID': appid, 'X-App-Secret': secret }
        });
        const ms  = Math.round(performance.now() - t0);
        const txt = await res.text();
        let body  = txt;
        try { body = JSON.stringify(JSON.parse(txt), null, 2); } catch (_) {}
        statusEl.textContent = `${res.status} ${res.statusText} · ${ms}ms`;
        statusEl.style.color = res.ok ? '#22c55e' : '#ef4444';
        resultEl.textContent = body;
        resultEl.hidden = false;
      } catch (err) {
        statusEl.textContent = 'Xato: ' + err.message;
        statusEl.style.color = '#ef4444';
        resultEl.hidden = true;
      } finally {
        btn.disabled = false;
      }
    });
  </script>
</body>
</html>
