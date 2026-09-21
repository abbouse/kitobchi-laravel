import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';

// `perm` — App\Models\Admin::MODULES dagi modul kaliti bilan bir xil bo'lishi
// SHART (backendda ham xuddi shu kalit bilan panel.permission:<kalit>
// middleware qo'llangan). `perm: null` — hech qanday ruxsat talab qilinmaydi,
// tizimga kirgan har bir admin ko'radi (faqat Dashboard/Live Dashboard).
const nav = [
  { group: 'Asosiy', icon: 'bi-grid', items: [
    { to: '/boshqaruv', match: '/boshqaruv', label: 'Dashboard', icon: 'bi-speedometer2', perm: null as string | null },
    { to: '/boshqaruv/live', match: '/boshqaruv/live', label: 'Live Dashboard', icon: 'bi-broadcast', badge: 'LIVE', perm: null as string | null },
  ]},
  { group: 'Katalog', icon: 'bi-book', items: [
    { to: '/boshqaruv/books', match: '/boshqaruv/books', label: 'Kitoblar', icon: 'bi-book', perm: 'catalog' },
    { to: '/boshqaruv/book-categories', match: '/boshqaruv/book-categories', label: 'Kitob kategoriyalari', icon: 'bi-bookmarks', perm: 'catalog' },
    { to: '/boshqaruv/stationeries', match: '/boshqaruv/stationeries', label: 'Kanselyariya', icon: 'bi-pencil-square', perm: 'catalog' },
    { to: '/boshqaruv/stationery-categories', match: '/boshqaruv/stationery-categories', label: 'Kanstovar kategoriyalari', icon: 'bi-tags', perm: 'catalog' },
    { to: '/boshqaruv/authors', match: '/boshqaruv/authors', label: 'Mualliflar', icon: 'bi-person-vcard', perm: 'catalog' },
    { to: '/boshqaruv/publishers', match: '/boshqaruv/publishers', label: 'Nashriyotlar', icon: 'bi-building', perm: 'catalog' },
  ]},
  { group: 'Buyurtmalar', icon: 'bi-receipt', items: [
    { to: '/boshqaruv/orders', match: '/boshqaruv/orders', label: 'Buyurtmalar', icon: 'bi-receipt', perm: 'orders' },
    { to: '/boshqaruv/users', match: '/boshqaruv/users', label: 'Foydalanuvchilar', icon: 'bi-people', perm: 'users' },
    { to: '/boshqaruv/split', match: '/boshqaruv/split', label: 'Split nazorati', icon: 'bi-wallet2', perm: 'split' },
    { to: '/boshqaruv/search-history', match: '/boshqaruv/search-history', label: 'Qidiruv tarixi', icon: 'bi-clock-history', perm: 'search-history' },
  ]},
  { group: 'Savdo va logistika', icon: 'bi-truck', items: [
    { to: '/boshqaruv/sellers', match: '/boshqaruv/sellers', label: 'Sotuvchilar', icon: 'bi-shop-window', perm: 'sellers' },
    { to: '/boshqaruv/seller-orders', match: '/boshqaruv/seller-orders', label: 'Seller buyurtmalari', icon: 'bi-shop', perm: 'sellers' },
    { to: '/boshqaruv/couriers', match: '/boshqaruv/couriers', label: 'Kuryerlar', icon: 'bi-bicycle', perm: 'couriers' },
    { to: '/boshqaruv/courier-orders', match: '/boshqaruv/courier-orders', label: 'Kuryer buyurtmalari', icon: 'bi-truck', perm: 'couriers' },
    { to: '/boshqaruv/hubs', match: '/boshqaruv/hubs', label: 'Hub fulfillment', icon: 'bi-building', perm: 'hubs' },
    { to: '/boshqaruv/transactions', match: '/boshqaruv/transactions', label: 'Tranzaksiyalar', icon: 'bi-cash-coin', perm: 'finance' },
    { to: '/boshqaruv/fiscalization', match: '/boshqaruv/fiscalization', label: 'Fiskalizatsiya', icon: 'bi-qr-code', perm: 'finance' },
    { to: '/boshqaruv/commission-audit', match: '/boshqaruv/commission-audit', label: 'Komissiya audit', icon: 'bi-percent', perm: 'finance' },
    { to: '/boshqaruv/audit-logs', match: '/boshqaruv/audit-logs', label: 'Audit log', icon: 'bi-clipboard-data', perm: 'audit-logs' },
    { to: '/boshqaruv/seller-ai-actions', match: '/boshqaruv/seller-ai-actions', label: 'Seller AI audit', icon: 'bi-robot', perm: 'seller-ai' },
    { to: '/boshqaruv/expenses', match: '/boshqaruv/expenses', label: 'Chiqimlar', icon: 'bi-wallet2', perm: 'finance' },
    { to: '/boshqaruv/logistika', match: '/boshqaruv/logistika', label: 'Logistika', icon: 'bi-geo-alt', perm: 'logistika' },
  ]},
  { group: 'Marketing', icon: 'bi-megaphone', items: [
    { to: '/boshqaruv/reklamalar', match: '/boshqaruv/reklamalar', label: 'Reklamalar', icon: 'bi-megaphone', perm: 'marketing' },
    { to: '/boshqaruv/promokodlar', match: '/boshqaruv/promokodlar', label: 'Promokodlar', icon: 'bi-ticket-perforated', perm: 'marketing' },
    { to: '/boshqaruv/blogerlar', match: '/boshqaruv/blogerlar', label: 'Blogerlar', icon: 'bi-people', perm: 'marketing' },
    { to: '/boshqaruv/gift-sertifikatlar', match: '/boshqaruv/gift-sertifikatlar', label: 'Gift sertifikatlar', icon: 'bi-gift', perm: 'marketing' },
    { to: '/boshqaruv/market-news', match: '/boshqaruv/market-news', label: 'Market yangiliklari', icon: 'bi-newspaper', perm: 'marketing' },
    { to: '/boshqaruv/collections', match: '/boshqaruv/collections', label: "To'plamlar", icon: 'bi-collection', perm: 'marketing' },
    { to: '/boshqaruv/reels', match: '/boshqaruv/reels', label: 'Reels / Shorts', icon: 'bi-camera-reels', perm: 'marketing' },
    { to: '/boshqaruv/book-club', match: '/boshqaruv/book-club', label: 'Book Club', icon: 'bi-journal-bookmark', perm: 'book-club' },
  ]},
  { group: 'Mijozlarga xizmat', icon: 'bi-headset', items: [
    { to: '/boshqaruv/tickets', match: '/boshqaruv/tickets', label: 'Support', icon: 'bi-headset', perm: 'support' },
    { to: '/boshqaruv/tickets?tickets_source=seller', match: '/boshqaruv/tickets?tickets_source=seller', label: 'Seller tiketlari', icon: 'bi-chat-left-text', perm: 'support' },
    { to: '/boshqaruv/shikoyatlar', match: '/boshqaruv/shikoyatlar', label: 'Shikoyatlar', icon: 'bi-exclamation-triangle', perm: 'support' },
    { to: '/boshqaruv/chat', match: '/boshqaruv/chat', label: 'Chat kuzatuv', icon: 'bi-chat-dots', perm: 'support' },
    { to: '/boshqaruv/push', match: '/boshqaruv/push', label: 'Push bildirishnomalar', icon: 'bi-bell', perm: 'push' },
  ]},
  { group: 'HR va tashkilot', icon: 'bi-people', items: [
    { to: '/boshqaruv/vakansiyalar', match: '/boshqaruv/vakansiyalar', label: 'Vakansiyalar', icon: 'bi-person-badge', perm: 'hr' },
    { to: '/boshqaruv/karyera-arizalari', match: '/boshqaruv/karyera-arizalari', label: 'Karyera arizalari', icon: 'bi-file-earmark-person', perm: 'hr' },
    { to: '/boshqaruv/hub-arizalari', match: '/boshqaruv/hub-arizalari', label: 'Hub arizalari', icon: 'bi-building-add', perm: 'hubs' },
    { to: '/boshqaruv/adminlar', match: '/boshqaruv/adminlar', label: 'Adminlar', icon: 'bi-shield-lock', perm: 'admins' },
  ]},
  { group: 'Premium', icon: 'bi-gem', items: [
    { to: '/boshqaruv/mystery-box', match: '/boshqaruv/mystery-box', label: 'Mystery Box', icon: 'bi-box-seam', perm: 'premium' },
    { to: '/boshqaruv/sovgalar', match: '/boshqaruv/sovgalar', label: "Sovg'alar", icon: 'bi-gift-fill', perm: 'premium' },
  ]},
  { group: 'Tizim', icon: 'bi-gear', items: [
    { to: '/boshqaruv/siyosatlar', match: '/boshqaruv/siyosatlar', label: 'Siyosatlar', icon: 'bi-file-earmark-text', perm: 'settings' },
    { to: '/boshqaruv/api-clients', match: '/boshqaruv/api-clients', label: 'API mijozlar', icon: 'bi-code-slash', perm: 'settings' },
    { to: '/boshqaruv/settings', match: '/boshqaruv/settings', label: 'Sozlamalar', icon: 'bi-gear', perm: 'settings' },
  ]},
];

type PanelAdmin = { name?: string; email?: string; role?: string; roleKey?: string; isSuperAdmin?: boolean; isReadOnly?: boolean; permissions?: string[] };

// Adminning ruxsatlariga qarab sidebar'ni filtrlaydi: superadmin — hammasini
// ko'radi; boshqalar — faqat `permissions` massivida bor modullarni. Bo'sh
// qolgan guruhlar butunlay yashiriladi (masalan, hech qaysi marketing
// ruxsati yo'q admin uchun "Marketing va Hamjamiyat" guruhi umuman ko'rinmaydi).
function filterNavByPermissions(admin?: PanelAdmin) {
  const isSuper = !!admin?.isSuperAdmin;
  const perms = new Set(admin?.permissions || []);
  const allowed = (perm: string | null) => perm === null || isSuper || perms.has(perm);

  return nav
    .map((g) => ({ ...g, items: g.items.filter((it) => allowed(it.perm)) }))
    .filter((g) => g.items.length > 0);
}

// ── Global qidiruv: bo'lim nomi bo'yicha sakrash + asosiy ro'yxatlarda qidirish ──
const searchTargets = [
  { label: 'Buyurtmalardan qidirish', path: '/boshqaruv/orders', icon: 'bi-receipt' },
  { label: 'Foydalanuvchilardan qidirish', path: '/boshqaruv/users', icon: 'bi-people' },
  { label: 'Kitoblardan qidirish', path: '/boshqaruv/books', icon: 'bi-book' },
  { label: 'Kanselyariyadan qidirish', path: '/boshqaruv/stationeries', icon: 'bi-pencil-square' },
  { label: 'Tranzaksiyalardan qidirish', path: '/boshqaruv/transactions', icon: 'bi-cash-coin' },
];

type QuickResult = { key: string; label: string; icon: string; hint?: string; go: () => void };

function QuickSearch({ visibleNav }: { visibleNav: typeof nav }) {
  const [query, setQuery] = useState('');
  const [active, setActive] = useState(0);
  const [focused, setFocused] = useState(false);
  const wrapRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  // Faqat adminga ko'rinadigan (ruxsat berilgan) bo'limlar tezkor qidiruvda chiqadi.
  const navItems = useMemo(() => visibleNav.flatMap((g) => g.items.map((it) => ({ ...it, group: g.group }))), [visibleNav]);

  const results = useMemo<QuickResult[]>(() => {
    const q = query.trim().toLowerCase();
    if (!q) return [];
    const sections: QuickResult[] = navItems
      .filter((it) => it.label.toLowerCase().includes(q))
      .slice(0, 5)
      .map((it) => ({
        key: `nav:${it.to}`,
        label: it.label,
        icon: it.icon,
        hint: it.group,
        go: () => router.visit(it.to),
      }));
    const jumps: QuickResult[] = searchTargets.map((t) => ({
      key: `jump:${t.path}`,
      label: `${t.label}: "${query.trim()}"`,
      icon: t.icon,
      hint: 'Qidiruv',
      go: () => router.get(t.path, { search: query.trim() }),
    }));
    return [...sections, ...jumps];
  }, [query, navItems]);

  const open = focused && results.length > 0;

  useEffect(() => { setActive(0); }, [query]);

  useEffect(() => {
    const onKey = (event: KeyboardEvent) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        inputRef.current?.focus();
      }
    };
    const onClick = (event: MouseEvent) => {
      if (wrapRef.current && !wrapRef.current.contains(event.target as Node)) setFocused(false);
    };
    window.addEventListener('keydown', onKey);
    window.addEventListener('mousedown', onClick);
    return () => { window.removeEventListener('keydown', onKey); window.removeEventListener('mousedown', onClick); };
  }, []);

  const pick = (result: QuickResult) => {
    setFocused(false);
    setQuery('');
    inputRef.current?.blur();
    result.go();
  };

  return (
    <div className="search quick-search d-none d-md-block" ref={wrapRef}>
      <i className="bi bi-search"></i>
      <input
        ref={inputRef}
        value={query}
        placeholder="Bo'lim, buyurtma, foydalanuvchi qidirish..."
        onChange={(e) => setQuery(e.target.value)}
        onFocus={() => setFocused(true)}
        onKeyDown={(e) => {
          if (e.key === 'Escape') { setFocused(false); inputRef.current?.blur(); }
          if (!open) return;
          if (e.key === 'ArrowDown') { e.preventDefault(); setActive((v) => Math.min(v + 1, results.length - 1)); }
          if (e.key === 'ArrowUp') { e.preventDefault(); setActive((v) => Math.max(v - 1, 0)); }
          if (e.key === 'Enter') { e.preventDefault(); const r = results[active] || results[0]; if (r) pick(r); }
        }}
      />
      <span className="quick-search-kbd">Ctrl K</span>
      {open && (
        <div className="quick-search-menu">
          {results.map((r, index) => (
            <button
              key={r.key}
              type="button"
              className={`quick-search-item ${index === active ? 'active' : ''}`}
              onMouseEnter={() => setActive(index)}
              onClick={() => pick(r)}
            >
              <i className={`bi ${r.icon}`}></i>
              <span className="quick-search-label">{r.label}</span>
              {r.hint ? <span className="quick-search-hint">{r.hint}</span> : null}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

// ── Global flash/xato toastlari: hamma sahifadagi amallar uchun yagona feedback ──
type ToastItem = { id: number; type: 'success' | 'error'; text: string };
let toastSeq = 0;

function useFlashToasts() {
  const pageProps = usePage<{
    flash?: { success?: string | null; error?: string | null };
    errors?: Record<string, string>;
  }>().props;
  const [toasts, setToasts] = useState<ToastItem[]>([]);

  useEffect(() => {
    const items: Array<{ type: 'success' | 'error'; text: string }> = [];
    if (pageProps.flash?.success) items.push({ type: 'success', text: String(pageProps.flash.success) });
    if (pageProps.flash?.error) items.push({ type: 'error', text: String(pageProps.flash.error) });
    const errorValues = pageProps.errors ? Object.values(pageProps.errors) : [];
    if (!pageProps.flash?.error && errorValues.length) {
      items.push({ type: 'error', text: String(errorValues[0]) });
    }
    if (!items.length) return;

    const stamped = items.map((item) => ({ ...item, id: ++toastSeq }));
    setToasts((current) => [...current, ...stamped].slice(-4));
    // Timerlar cleanup qilinmaydi — sahifa almashsa ham toast o'z vaqtida yopiladi
    stamped.forEach((toast) =>
      window.setTimeout(() => {
        setToasts((current) => current.filter((t) => t.id !== toast.id));
      }, toast.type === 'error' ? 7000 : 4500),
    );
  }, [pageProps]);

  const dismiss = (id: number) => setToasts((current) => current.filter((t) => t.id !== id));

  return { toasts, dismiss };
}

function initialsOf(name?: string): string {
  const parts = (name || '').trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return 'A';
  return parts.slice(0, 2).map((word) => word[0]!.toUpperCase()).join('');
}

export default function Layout({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useState(false);
  const [semi, setSemi] = useState(() => {
    if (typeof window === 'undefined') return false;
    try { return localStorage.getItem('boshqaruv-sidebar') === 'semi'; } catch { return false; }
  });
  const [darkMode, setDarkMode] = useState(() => {
    if (typeof window === 'undefined') return false;
    return localStorage.getItem('boshqaruv-theme') === 'dark';
  });
  const [profileOpen, setProfileOpen] = useState(false);
  const profileRef = useRef<HTMLDivElement>(null);
  const { url, props } = usePage<{
    auth?: { admin?: PanelAdmin };
  }>();
  const admin = props.auth?.admin;
  const initials = initialsOf(admin?.name);
  const { toasts, dismiss } = useFlashToasts();
  const visibleNav = useMemo(() => filterNavByPermissions(admin), [admin]);

  useEffect(() => { setOpen(false); setProfileOpen(false); }, [url]);
  useEffect(() => {
    document.body.classList.toggle('boshqaruv-dark', darkMode);
    document.documentElement.setAttribute('data-bs-theme', darkMode ? 'dark' : 'light');
    localStorage.setItem('boshqaruv-theme', darkMode ? 'dark' : 'light');
  }, [darkMode]);
  useEffect(() => {
    try { localStorage.setItem('boshqaruv-sidebar', semi ? 'semi' : 'full'); } catch { /* storage yopiq bo'lishi mumkin */ }
  }, [semi]);
  useEffect(() => {
    if (!profileOpen) return;
    const onClick = (event: MouseEvent) => {
      if (profileRef.current && !profileRef.current.contains(event.target as Node)) setProfileOpen(false);
    };
    const onKey = (event: KeyboardEvent) => { if (event.key === 'Escape') setProfileOpen(false); };
    window.addEventListener('mousedown', onClick);
    window.addEventListener('keydown', onKey);
    return () => { window.removeEventListener('mousedown', onClick); window.removeEventListener('keydown', onKey); };
  }, [profileOpen]);

  const isActive = (match: string) => {
    if (match === '/boshqaruv') return url === '/boshqaruv' || url === '/boshqaruv/';
    if (match.includes('?')) return url.startsWith(match);
    if (match === '/boshqaruv/tickets' && url.includes('tickets_source=seller')) return false;
    return url.startsWith(match);
  };

  // Joriy faol menyu bandi (breadcrumb va sarlavha uchun)
  const currentNav = useMemo(() => {
    return nav
      .flatMap((g) => g.items.map((it) => ({ ...it, group: g.group })))
      .filter((it) => isActive(it.match))
      .sort((a, b) => b.match.length - a.match.length)[0];
  }, [url]);

  // Sidebar: "Asosiy" bandlari to'g'ridan-to'g'ri, qolgan guruhlar yig'iladi.
  // Faol sahifa joylashgan guruh avtomatik ochiladi (Axelit xatti-harakati).
  const flatGroup = visibleNav.find((g) => g.group === 'Asosiy');
  const groups = visibleNav.filter((g) => g.group !== 'Asosiy');
  const activeGroup = currentNav && currentNav.group !== 'Asosiy' ? currentNav.group : null;
  const [openGroup, setOpenGroup] = useState<string | null>(activeGroup);
  useEffect(() => { if (activeGroup) setOpenGroup(activeGroup); }, [activeGroup]);

  // Brauzer tab sarlavhasi joriy bo'limga mos bo'ladi
  useEffect(() => {
    document.title = currentNav && currentNav.match !== '/boshqaruv'
      ? `${currentNav.label} — Kitobchi Boshqaruv`
      : 'Kitobchi Boshqaruv';
  }, [currentNav]);

  const toggleSidebar = () => {
    if (window.matchMedia('(max-width: 991.98px)').matches) setOpen((v) => !v);
    else setSemi((v) => !v);
  };

  const renderBadge = (badge?: string) => (badge
    ? <span className={`badge ${badge === 'LIVE' ? 'is-live' : ''}`}>{badge}</span>
    : null);

  return (
    <div className={`app-shell ${semi ? 'is-semi' : ''} ${open ? 'is-open' : ''}`}>
      <aside className="sidebar" aria-label="Asosiy menyu">
        <div className="side-brand">
          <Link href="/boshqaruv" aria-label="Kitobchi Boshqaruv">
            {/* Sidebar yorug' rejimda oq — qora logotip; qorong'ida oq logotip */}
            <img src={darkMode ? '/images/logo/logo_white.png' : '/images/logo/logo_black.png'} alt="Kitobchi" />
            <span className="side-brand-mark" aria-hidden="true">K</span>
          </Link>
        </div>

        <nav className="side-nav">
          {flatGroup ? (
            <>
              <div className="side-section"><span>Asosiy</span></div>
              <ul className="side-list">
                {flatGroup.items.map((it) => (
                  <li className="side-item" key={it.to}>
                    <Link href={it.to} className={`side-link ${isActive(it.match) ? 'active' : ''}`} title={it.label}>
                      <i className={`bi ${it.icon}`}></i>
                      <span className="side-label">{it.label}</span>
                      {renderBadge(it.badge)}
                    </Link>
                  </li>
                ))}
              </ul>
            </>
          ) : null}

          {groups.length ? <div className="side-section"><span>Bo'limlar</span></div> : null}
          <ul className="side-list">
            {groups.map((g) => {
              const isOpen = openGroup === g.group;
              const hasActive = g.items.some((it) => isActive(it.match));
              const subId = `side-sub-${g.group.replace(/\s+/g, '-').toLowerCase()}`;
              return (
                <li key={g.group} className={`side-item side-group ${isOpen ? 'open' : ''} ${hasActive ? 'has-active' : ''}`}>
                  <button
                    type="button"
                    className="side-link"
                    aria-expanded={isOpen}
                    aria-controls={subId}
                    title={g.group}
                    onClick={() => setOpenGroup((cur) => (cur === g.group ? null : g.group))}
                  >
                    <i className={`bi ${(g as { icon?: string }).icon || 'bi-folder'}`}></i>
                    <span className="side-label">{g.group}</span>
                    <i className="bi bi-chevron-right side-chevron" aria-hidden="true"></i>
                  </button>
                  {isOpen ? (
                    <ul className="side-sub" id={subId}>
                      {g.items.map((it) => (
                        <li key={it.to}>
                          <Link href={it.to} className={isActive(it.match) ? 'active' : ''}>
                            <span className="side-label">{it.label}</span>
                            {renderBadge(it.badge)}
                          </Link>
                        </li>
                      ))}
                    </ul>
                  ) : null}
                </li>
              );
            })}
          </ul>
        </nav>
      </aside>

      <div className="main-wrap">
        <header className="topbar">
          <button type="button" className="header-toggle" onClick={toggleSidebar} aria-label="Menyuni yig'ish / ochish" title="Menyu">
            <i className="bi bi-grid"></i>
          </button>

          <div className="topbar-crumb d-none d-lg-flex">
            <i className="bi bi-house-door"></i>
            <span>Boshqaruv</span>
            {currentNav && currentNav.match !== '/boshqaruv' ? (
              <>
                <i className="bi bi-chevron-right sep"></i>
                <span className="current">{currentNav.label}</span>
              </>
            ) : null}
          </div>

          <QuickSearch visibleNav={visibleNav} />

          <div style={{ flex: 1 }}></div>

          <span className="topbar-status d-none d-xl-inline-flex">Tizim barqaror</span>

          <button type="button" className="head-icon" onClick={() => setDarkMode((value) => !value)} title={darkMode ? 'Yorug\' rejim' : 'Qorong\'i rejim'} aria-label="Mavzuni almashtirish">
            <i className={`bi ${darkMode ? 'bi-sun' : 'bi-moon-stars'}`}></i>
          </button>

          <div className="topbar-profile" ref={profileRef}>
            <button type="button" className="topbar-profile-btn" onClick={() => setProfileOpen((v) => !v)} aria-haspopup="menu" aria-expanded={profileOpen}>
              <span className="avatar">{initials}</span>
              <span className="who d-none d-md-block">
                <b>{admin?.name || 'Admin'}</b>
                <small>{admin?.role || 'Administrator'}</small>
              </span>
              <i className="bi bi-chevron-down d-none d-md-inline"></i>
            </button>
            {profileOpen ? (
              <div className="profile-menu" role="menu">
                <div className="profile-menu-head">
                  <span className="avatar">{initials}</span>
                  <div style={{ minWidth: 0 }}>
                    <b>{admin?.name || 'Admin'}</b>
                    <small>{admin?.email || ''}</small>
                    <span className="role-badge">{admin?.role || 'Administrator'}{admin?.isReadOnly ? " · faqat ko'rish" : ''}</span>
                  </div>
                </div>
                {visibleNav.some((g) => g.items.some((it) => it.to === '/boshqaruv/settings')) ? (
                  <Link href="/boshqaruv/settings" className="profile-menu-item" role="menuitem">
                    <i className="bi bi-gear"></i><span>Sozlamalar</span>
                  </Link>
                ) : null}
                <button type="button" className="profile-menu-item" role="menuitem" onClick={() => setDarkMode((value) => !value)}>
                  <i className={`bi ${darkMode ? 'bi-sun' : 'bi-moon-stars'}`}></i>
                  <span>{darkMode ? "Yorug' rejim" : "Qorong'i rejim"}</span>
                </button>
                <button type="button" className="profile-menu-item is-danger" role="menuitem" onClick={() => router.post('/boshqaruv/logout')}>
                  <i className="bi bi-box-arrow-right"></i><span>Chiqish</span>
                </button>
              </div>
            ) : null}
          </div>
        </header>

        <main className="content">{children}</main>

        <footer className="app-footer">
          <span>© {new Date().getFullYear()} Kitobchi. Barcha huquqlar himoyalangan.</span>
          <span>Boshqaruv paneli <span className="ver">v2.0</span></span>
        </footer>
      </div>

      <div className="toast-stack">
        {toasts.map((toast) => (
          <div key={toast.id} className={`app-toast ${toast.type === 'success' ? 'app-toast-success' : 'app-toast-error'}`}>
            <i className={`bi ${toast.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}`}></i>
            <span className="app-toast-text">{toast.text}</span>
            <button type="button" className="app-toast-close" onClick={() => dismiss(toast.id)} aria-label="Yopish">
              <i className="bi bi-x-lg"></i>
            </button>
          </div>
        ))}
      </div>

      {open ? <div className="sidebar-backdrop d-lg-none" onClick={() => setOpen(false)}></div> : null}
    </div>
  );
}
