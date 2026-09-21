import { Fragment, useEffect, useMemo, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { tiIcon } from './utils/icons';

/*
 * Boshqaruv karkasi — Axelit shablonining aynan o'zi (litsenziyalangan):
 *   .app-wrapper > nav (sidebar) + .app-content > header.header-main + main,
 *   footer, go-top. Klasslar va tuzilma Axelit bilan 1:1, shuning uchun
 *   uslublar resources/css/axelit/axelit.css dan to'g'ridan-to'g'ri keladi.
 */

// `perm` — App\Models\Admin::MODULES dagi modul kaliti bilan bir xil bo'lishi
// SHART (backendda ham xuddi shu kalit bilan panel.permission:<kalit>
// middleware qo'llangan). `perm: null` — hech qanday ruxsat talab qilinmaydi,
// tizimga kirgan har bir admin ko'radi (faqat Dashboard/Live Dashboard).
const nav = [
  { group: 'Asosiy', section: 'Asosiy', icon: 'ti-layout-grid', ax: 'iconoir-home-alt', items: [
    { to: '/boshqaruv', match: '/boshqaruv', label: 'Dashboard', icon: 'ti-gauge', ax: 'iconoir-home-alt', perm: null as string | null },
    { to: '/boshqaruv/live', match: '/boshqaruv/live', label: 'Live Dashboard', icon: 'ti-broadcast', ax: 'iconoir-antenna-signal', badge: 'LIVE', perm: null as string | null },
  ]},
  { group: 'Katalog', section: 'Savdo', icon: 'ti-book', ax: 'iconoir-book-stack', items: [
    { to: '/boshqaruv/books', match: '/boshqaruv/books', label: 'Kitoblar', icon: 'ti-book', perm: 'catalog' },
    { to: '/boshqaruv/book-categories', match: '/boshqaruv/book-categories', label: 'Kitob kategoriyalari', icon: 'ti-bookmarks', perm: 'catalog' },
    { to: '/boshqaruv/stationeries', match: '/boshqaruv/stationeries', label: 'Kanselyariya', icon: 'ti-edit', perm: 'catalog' },
    { to: '/boshqaruv/stationery-categories', match: '/boshqaruv/stationery-categories', label: 'Kanstovar kategoriyalari', icon: 'ti-tags', perm: 'catalog' },
    { to: '/boshqaruv/authors', match: '/boshqaruv/authors', label: 'Mualliflar', icon: 'ti-id', perm: 'catalog' },
    { to: '/boshqaruv/publishers', match: '/boshqaruv/publishers', label: 'Nashriyotlar', icon: 'ti-building', perm: 'catalog' },
  ]},
  { group: 'Buyurtmalar', section: 'Savdo', icon: 'ti-receipt', ax: 'iconoir-shopping-bag', items: [
    { to: '/boshqaruv/orders', match: '/boshqaruv/orders', label: 'Buyurtmalar', icon: 'ti-receipt', perm: 'orders' },
    { to: '/boshqaruv/users', match: '/boshqaruv/users', label: 'Foydalanuvchilar', icon: 'ti-users', perm: 'users' },
    { to: '/boshqaruv/split', match: '/boshqaruv/split', label: 'Split nazorati', icon: 'ti-wallet', perm: 'split' },
    { to: '/boshqaruv/search-history', match: '/boshqaruv/search-history', label: 'Qidiruv tarixi', icon: 'ti-history', perm: 'search-history' },
  ]},
  { group: 'Savdo va logistika', section: 'Savdo', icon: 'ti-truck', ax: 'iconoir-delivery-truck', items: [
    { to: '/boshqaruv/sellers', match: '/boshqaruv/sellers', label: 'Sotuvchilar', icon: 'ti-building-store', perm: 'sellers' },
    { to: '/boshqaruv/seller-orders', match: '/boshqaruv/seller-orders', label: 'Seller buyurtmalari', icon: 'ti-building-store', perm: 'sellers' },
    { to: '/boshqaruv/couriers', match: '/boshqaruv/couriers', label: 'Kuryerlar', icon: 'ti-bike', perm: 'couriers' },
    { to: '/boshqaruv/courier-orders', match: '/boshqaruv/courier-orders', label: 'Kuryer buyurtmalari', icon: 'ti-truck', perm: 'couriers' },
    { to: '/boshqaruv/hubs', match: '/boshqaruv/hubs', label: 'Hub fulfillment', icon: 'ti-building', perm: 'hubs' },
    { to: '/boshqaruv/transactions', match: '/boshqaruv/transactions', label: 'Tranzaksiyalar', icon: 'ti-coins', perm: 'finance' },
    { to: '/boshqaruv/fiscalization', match: '/boshqaruv/fiscalization', label: 'Fiskalizatsiya', icon: 'ti-qrcode', perm: 'finance' },
    { to: '/boshqaruv/commission-audit', match: '/boshqaruv/commission-audit', label: 'Komissiya audit', icon: 'ti-percentage', perm: 'finance' },
    { to: '/boshqaruv/audit-logs', match: '/boshqaruv/audit-logs', label: 'Audit log', icon: 'ti-clipboard-data', perm: 'audit-logs' },
    { to: '/boshqaruv/seller-ai-actions', match: '/boshqaruv/seller-ai-actions', label: 'Seller AI audit', icon: 'ti-robot', perm: 'seller-ai' },
    { to: '/boshqaruv/expenses', match: '/boshqaruv/expenses', label: 'Chiqimlar', icon: 'ti-wallet', perm: 'finance' },
    { to: '/boshqaruv/logistika', match: '/boshqaruv/logistika', label: 'Logistika', icon: 'ti-map-pin', perm: 'logistika' },
  ]},
  { group: 'Marketing', section: "O'sish", icon: 'ti-speakerphone', ax: 'iconoir-megaphone', items: [
    { to: '/boshqaruv/reklamalar', match: '/boshqaruv/reklamalar', label: 'Reklamalar', icon: 'ti-speakerphone', perm: 'marketing' },
    { to: '/boshqaruv/promokodlar', match: '/boshqaruv/promokodlar', label: 'Promokodlar', icon: 'ti-ticket', perm: 'marketing' },
    { to: '/boshqaruv/blogerlar', match: '/boshqaruv/blogerlar', label: 'Blogerlar', icon: 'ti-users', perm: 'marketing' },
    { to: '/boshqaruv/gift-sertifikatlar', match: '/boshqaruv/gift-sertifikatlar', label: 'Gift sertifikatlar', icon: 'ti-gift', perm: 'marketing' },
    { to: '/boshqaruv/market-news', match: '/boshqaruv/market-news', label: 'Market yangiliklari', icon: 'ti-news', perm: 'marketing' },
    { to: '/boshqaruv/collections', match: '/boshqaruv/collections', label: "To'plamlar", icon: 'ti-stack-2', perm: 'marketing' },
    { to: '/boshqaruv/reels', match: '/boshqaruv/reels', label: 'Reels / Shorts', icon: 'ti-movie', perm: 'marketing' },
    { to: '/boshqaruv/book-club', match: '/boshqaruv/book-club', label: 'Book Club', icon: 'ti-bookmark', perm: 'book-club' },
  ]},
  { group: 'Mijozlarga xizmat', section: 'Jamoa va tizim', icon: 'ti-headset', ax: 'iconoir-headset-help', items: [
    { to: '/boshqaruv/tickets', match: '/boshqaruv/tickets', label: 'Support', icon: 'ti-headset', perm: 'support' },
    { to: '/boshqaruv/tickets?tickets_source=seller', match: '/boshqaruv/tickets?tickets_source=seller', label: 'Seller tiketlari', icon: 'ti-message', perm: 'support' },
    { to: '/boshqaruv/shikoyatlar', match: '/boshqaruv/shikoyatlar', label: 'Shikoyatlar', icon: 'ti-alert-triangle', perm: 'support' },
    { to: '/boshqaruv/chat', match: '/boshqaruv/chat', label: 'Chat kuzatuv', icon: 'ti-message-dots', perm: 'support' },
    { to: '/boshqaruv/push', match: '/boshqaruv/push', label: 'Push bildirishnomalar', icon: 'ti-bell', perm: 'push' },
  ]},
  { group: 'HR va tashkilot', section: 'Jamoa va tizim', icon: 'ti-users', ax: 'iconoir-community', items: [
    { to: '/boshqaruv/vakansiyalar', match: '/boshqaruv/vakansiyalar', label: 'Vakansiyalar', icon: 'ti-id-badge', perm: 'hr' },
    { to: '/boshqaruv/karyera-arizalari', match: '/boshqaruv/karyera-arizalari', label: 'Karyera arizalari', icon: 'ti-file-certificate', perm: 'hr' },
    { to: '/boshqaruv/hub-arizalari', match: '/boshqaruv/hub-arizalari', label: 'Hub arizalari', icon: 'ti-home-plus', perm: 'hubs' },
    { to: '/boshqaruv/adminlar', match: '/boshqaruv/adminlar', label: 'Adminlar', icon: 'ti-shield-lock', perm: 'admins' },
  ]},
  { group: 'Premium', section: "O'sish", icon: 'ti-diamond', ax: 'iconoir-crown', items: [
    { to: '/boshqaruv/mystery-box', match: '/boshqaruv/mystery-box', label: 'Mystery Box', icon: 'ti-package', perm: 'premium' },
    { to: '/boshqaruv/sovgalar', match: '/boshqaruv/sovgalar', label: "Sovg'alar", icon: 'ti-gift', perm: 'premium' },
  ]},
  { group: 'Tizim', section: 'Jamoa va tizim', icon: 'ti-settings', ax: 'iconoir-settings', items: [
    { to: '/boshqaruv/siyosatlar', match: '/boshqaruv/siyosatlar', label: 'Siyosatlar', icon: 'ti-file-text', perm: 'settings' },
    { to: '/boshqaruv/api-clients', match: '/boshqaruv/api-clients', label: 'API mijozlar', icon: 'ti-code', perm: 'settings' },
    { to: '/boshqaruv/settings', match: '/boshqaruv/settings', label: 'Sozlamalar', icon: 'ti-settings', perm: 'settings' },
  ]},
];

type NavItem = { to: string; match: string; label: string; icon: string; ax?: string; badge?: string; perm: string | null };
type NavGroup = { group: string; section: string; icon: string; ax: string; items: NavItem[] };

type PanelAdmin = { name?: string; email?: string; role?: string; roleKey?: string; isSuperAdmin?: boolean; isReadOnly?: boolean; permissions?: string[] };

// Adminning ruxsatlariga qarab sidebar'ni filtrlaydi: superadmin — hammasini
// ko'radi; boshqalar — faqat `permissions` massivida bor modullarni. Bo'sh
// qolgan guruhlar butunlay yashiriladi.
function filterNavByPermissions(admin?: PanelAdmin): NavGroup[] {
  const isSuper = !!admin?.isSuperAdmin;
  const perms = new Set(admin?.permissions || []);
  const allowed = (perm: string | null) => perm === null || isSuper || perms.has(perm);

  return (nav as NavGroup[])
    .map((g) => ({ ...g, items: g.items.filter((it) => allowed(it.perm)) }))
    .filter((g) => g.items.length > 0);
}

// URL bo'yicha faol menyu bandini topish (sidebar, sarlavha va breadcrumb uchun)
function navMatches(url: string, match: string) {
  if (match === '/boshqaruv') return url === '/boshqaruv' || url === '/boshqaruv/';
  if (match.includes('?')) return url.startsWith(match);
  if (match === '/boshqaruv/tickets' && url.includes('tickets_source=seller')) return false;
  return url.startsWith(match);
}

export function findCurrentNav(url: string) {
  return (nav as NavGroup[])
    .flatMap((g) => g.items.map((it) => ({ ...it, group: g.group, groupAx: g.ax, groupFirst: g.items[0]?.to || '/boshqaruv' })))
    .filter((it) => navMatches(url, it.match))
    .sort((a, b) => b.match.length - a.match.length)[0];
}

/**
 * Axelit sahifa sarlavhasi ostidagi "app-line-breadcrumbs" qatori:
 * [ikonka] Bo'lim / Joriy sahifa (faol — primary rangda).
 */
export function PageCrumbs() {
  const { url } = usePage();
  const cur = findCurrentNav(url);
  if (!cur) return null;
  const isHome = cur.match === '/boshqaruv';
  return (
    <ul className="app-line-breadcrumbs mt-1 mb-2">
      <li>
        <Link href={isHome ? '/boshqaruv' : cur.groupFirst} className="f-s-14 f-w-500">
          <span><i className={`${cur.groupAx} f-s-16 align-text-top`}></i> {cur.group === 'Asosiy' ? 'Boshqaruv' : cur.group}</span>
        </Link>
      </li>
      <li className="active">
        <Link href={cur.to} className="f-s-14 f-w-500">{cur.label}</Link>
      </li>
    </ul>
  );
}

// Sidebar bo'limlari (Axelit'dagi "menu-title" sarlavhalari) — tartib shu yerda
const SECTIONS = ['Savdo', "O'sish", 'Jamoa va tizim'];

// ── Global qidiruv: bo'lim nomi bo'yicha sakrash + asosiy ro'yxatlarda qidirish ──
const searchTargets = [
  { label: 'Buyurtmalardan qidirish', path: '/boshqaruv/orders', icon: 'ti-receipt', tone: 'primary' },
  { label: 'Foydalanuvchilardan qidirish', path: '/boshqaruv/users', icon: 'ti-users', tone: 'success' },
  { label: 'Kitoblardan qidirish', path: '/boshqaruv/books', icon: 'ti-book', tone: 'warning' },
  { label: 'Kanselyariyadan qidirish', path: '/boshqaruv/stationeries', icon: 'ti-edit', tone: 'info' },
  { label: 'Tranzaksiyalardan qidirish', path: '/boshqaruv/transactions', icon: 'ti-coins', tone: 'danger' },
];

const TONES = ['primary', 'success', 'warning', 'info', 'danger', 'secondary'];

type QuickResult = { key: string; label: string; icon: string; hint?: string; tone: string; go: () => void };

/**
 * Axelit "header-searchbar" oynasi: yuqorida qidiruv maydoni, pastda natijalar
 * ro'yxati (35×35 pastel ikonka + sarlavha + izoh). Ctrl/⌘ + K bilan ochiladi.
 */
function SearchCanvas({ visibleNav, open, onClose }: { visibleNav: NavGroup[]; open: boolean; onClose: () => void }) {
  const [query, setQuery] = useState('');
  const [active, setActive] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);

  const navItems = useMemo(() => visibleNav.flatMap((g) => g.items.map((it) => ({ ...it, group: g.group }))), [visibleNav]);

  const results = useMemo<QuickResult[]>(() => {
    const q = query.trim().toLowerCase();
    const sections: QuickResult[] = navItems
      .filter((it) => !q || it.label.toLowerCase().includes(q) || it.group.toLowerCase().includes(q))
      .slice(0, q ? 6 : 5)
      .map((it, index) => ({
        key: `nav:${it.to}`,
        label: it.label,
        icon: it.icon,
        hint: it.group,
        tone: TONES[index % TONES.length],
        go: () => router.visit(it.to),
      }));
    if (!q) return sections;
    const jumps: QuickResult[] = searchTargets.map((t) => ({
      key: `jump:${t.path}`,
      label: `${t.label}: "${query.trim()}"`,
      icon: t.icon,
      hint: 'Ro\'yxat ichida qidirish',
      tone: t.tone,
      go: () => router.get(t.path, { search: query.trim() }),
    }));
    return [...sections, ...jumps];
  }, [query, navItems]);

  useEffect(() => { setActive(0); }, [query]);
  useEffect(() => {
    if (open) window.setTimeout(() => inputRef.current?.focus(), 60);
    else setQuery('');
  }, [open]);

  const pick = (result: QuickResult) => {
    onClose();
    result.go();
  };

  return (
    <div className={`offcanvas offcanvas-end header-searchbar-canvas ${open ? 'show' : ''}`} tabIndex={-1} aria-label="Qidiruv">
      <div className="header-searchbar-header">
        <div className="d-flex justify-content-between mb-3">
          <form className="app-form app-icon-form w-100" onSubmit={(e) => { e.preventDefault(); const r = results[active] || results[0]; if (r) pick(r); }}>
            <div className="position-relative">
              <input
                ref={inputRef}
                type="search"
                className="form-control search-filter"
                placeholder="Bo'lim, buyurtma, foydalanuvchi..."
                aria-label="Qidiruv"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Escape') onClose();
                  if (e.key === 'ArrowDown') { e.preventDefault(); setActive((v) => Math.min(v + 1, results.length - 1)); }
                  if (e.key === 'ArrowUp') { e.preventDefault(); setActive((v) => Math.max(v - 1, 0)); }
                }}
              />
              <i className="ti ti-search text-dark"></i>
            </div>
          </form>
          <button type="button" className="h-35 w-35 d-flex-center b-r-15 overflow-hidden bg-light-secondary search-list-avtar ms-2 border-0 flex-shrink-0" onClick={onClose} aria-label="Yopish">
            <i className="iconoir-xmark f-s-20"></i>
          </button>
        </div>
        <p className="mb-0 text-secondary f-s-15 mt-2">{query.trim() ? 'Natijalar:' : "Tezkor o'tish:"}</p>
      </div>
      <div className="offcanvas-body app-scroll p-0">
        <ul className="search-list">
          {results.map((r, index) => (
            <li
              key={r.key}
              className={`search-list-item ${index === active ? 'is-active' : ''}`}
              onMouseEnter={() => setActive(index)}
              onClick={() => pick(r)}
              role="button"
            >
              <div className={`h-35 w-35 d-flex-center b-r-15 overflow-hidden bg-light-${r.tone} search-list-avtar`}>
                <i className={`${tiIcon(r.icon)} f-s-18`}></i>
              </div>
              <div className="search-list-content">
                <h6 className="mb-0 text-dark txt-ellipsis-1">{r.label}</h6>
                {r.hint ? <p className="f-s-13 mb-0 text-secondary">{r.hint}</p> : null}
              </div>
            </li>
          ))}
          {results.length === 0 ? (
            <li className="search-list-item">
              <div className="h-35 w-35 d-flex-center b-r-15 overflow-hidden bg-light-secondary search-list-avtar">
                <i className="ti ti-search f-s-18"></i>
              </div>
              <div className="search-list-content">
                <h6 className="mb-0 text-dark">Hech narsa topilmadi</h6>
                <p className="f-s-13 mb-0 text-secondary">Boshqa so'z bilan urinib ko'ring</p>
              </div>
            </li>
          ) : null}
        </ul>
      </div>
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

// Axelit (script.js) xatti-harakati: ≥1200px — to'liq menyu (tanlov eslab
// qolinadi), 768–1199px — ikonka qatori, <768px — yashirin, tugma bilan ochiladi.
const viewportMode = () => {
  if (typeof window === 'undefined') return 'desktop';
  const w = window.innerWidth;
  if (w < 768) return 'mobile';
  if (w < 1200) return 'tablet';
  return 'desktop';
};
const storedSemi = () => {
  try { return localStorage.getItem('boshqaruv-sidebar') === 'semi'; } catch { return false; }
};
const semiFor = (mode: string) => (mode === 'mobile' ? false : mode === 'tablet' ? true : storedSemi());

export function applyTheme(dark: boolean) {
  document.body.classList.add('ltr');
  document.body.classList.toggle('dark', dark);
  try { localStorage.setItem('boshqaruv-theme', dark ? 'dark' : 'light'); } catch { /* storage yopiq */ }
}

export default function Layout({ children }: { children: React.ReactNode }) {
  const [semi, setSemi] = useState(() => semiFor(viewportMode()));
  const [darkMode, setDarkMode] = useState(() => {
    if (typeof window === 'undefined') return false;
    try { return localStorage.getItem('boshqaruv-theme') === 'dark'; } catch { return false; }
  });
  const [profileOpen, setProfileOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [scroll, setScroll] = useState(0);
  const { url, props } = usePage<{
    auth?: { admin?: PanelAdmin };
  }>();
  const admin = props.auth?.admin;
  const initials = initialsOf(admin?.name);
  const { toasts, dismiss } = useFlashToasts();
  const visibleNav = useMemo(() => filterNavByPermissions(admin), [admin]);

  useEffect(() => {
    setProfileOpen(false);
    setSearchOpen(false);
    if (viewportMode() === 'mobile') setSemi(false);
  }, [url]);
  useEffect(() => { applyTheme(darkMode); }, [darkMode]);
  useEffect(() => {
    const onResize = () => setSemi(semiFor(viewportMode()));
    const onScroll = () => {
      const el = document.documentElement;
      const max = el.scrollHeight - el.clientHeight;
      setScroll(el.scrollTop > 100 && max > 0 ? Math.round((el.scrollTop * 100) / max) : 0);
    };
    const onKey = (event: KeyboardEvent) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        setProfileOpen(false);
        setSearchOpen(true);
      }
      if (event.key === 'Escape') { setProfileOpen(false); setSearchOpen(false); }
    };
    window.addEventListener('resize', onResize);
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('keydown', onKey);
    return () => {
      window.removeEventListener('resize', onResize);
      window.removeEventListener('scroll', onScroll);
      window.removeEventListener('keydown', onKey);
    };
  }, []);

  const isActive = (match: string) => navMatches(url, match);

  // Joriy faol menyu bandi (sarlavha uchun)
  const currentNav = useMemo(() => findCurrentNav(url), [url]);

  // "Asosiy" bandlari to'g'ridan-to'g'ri havola, qolgan guruhlar yig'iladi.
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
    setSemi((value) => {
      const next = !value;
      if (viewportMode() === 'desktop') {
        try { localStorage.setItem('boshqaruv-sidebar', next ? 'semi' : 'full'); } catch { /* storage yopiq */ }
      }
      return next;
    });
  };
  const expandSidebar = () => {
    setSemi(false);
    if (viewportMode() === 'desktop') {
      try { localStorage.setItem('boshqaruv-sidebar', 'full'); } catch { /* storage yopiq */ }
    }
  };

  const renderBadge = (badge?: string) => (badge
    ? <span className={`badge ${badge === 'LIVE' ? 'text-danger-dark bg-danger-300' : 'text-primary-dark bg-primary-300'} badge-notification ms-2`}>{badge}</span>
    : null);

  const canSettings = visibleNav.some((g) => g.items.some((it) => it.to === '/boshqaruv/settings'));
  const overlayOpen = profileOpen || searchOpen;

  return (
    <div className="app-wrapper">
      <nav className={semi ? 'semi-nav' : ''} aria-label="Asosiy menyu">
        <div className="app-logo">
          <Link className="logo d-inline-block" href="/boshqaruv" aria-label="Kitobchi Boshqaruv">
            {/* Logotip o'rnida ilova ikonkasi (favicon) */}
            <img className="kc-logo-icon" src="/favicon.svg" alt="" width={40} height={40} />
            <span className="kc-logo-text">Kitobchi</span>
          </Link>
          <span className="bg-light-primary toggle-semi-nav" onClick={expandSidebar} role="button" aria-label="Menyuni ochish">
            <i className="ti ti-chevrons-right f-s-20"></i>
          </span>
        </div>

        <div className="app-nav" id="app-simple-bar">
          <ul className="main-nav p-0 mt-2">
            {flatGroup ? (
              <>
                <li className="menu-title"><span>Asosiy</span></li>
                {flatGroup.items.map((it) => (
                  <li className={`no-sub ${isActive(it.match) ? 'active' : ''}`} key={it.to}>
                    <Link href={it.to} className={isActive(it.match) ? 'active' : ''} title={it.label}>
                      <i className={it.ax || 'iconoir-page'}></i>
                      {it.label}
                      {renderBadge(it.badge)}
                    </Link>
                  </li>
                ))}
              </>
            ) : null}

            {SECTIONS.map((section) => {
              const inSection = groups.filter((g) => g.section === section);
              if (!inSection.length) return null;
              return (
                <Fragment key={section}>
                  <li className="menu-title"><span>{section}</span></li>
                  {inSection.map((g) => {
                    const isOpen = openGroup === g.group;
                    const subId = `nav-${g.group.replace(/[^a-z0-9]+/gi, '-').toLowerCase()}`;
                    return (
                      <li key={g.group}>
                        <a
                          href={`#${subId}`}
                          aria-expanded={isOpen}
                          aria-controls={subId}
                          title={g.group}
                          onClick={(e) => { e.preventDefault(); setOpenGroup((cur) => (cur === g.group ? null : g.group)); }}
                        >
                          <i className={g.ax}></i>
                          {g.group}
                        </a>
                        <ul className={`collapse ${isOpen ? 'show' : ''}`} id={subId}>
                          {g.items.map((it) => (
                            <li key={it.to} className={isActive(it.match) ? 'active' : ''}>
                              <Link href={it.to}>{it.label}</Link>
                              {renderBadge(it.badge)}
                            </li>
                          ))}
                        </ul>
                      </li>
                    );
                  })}
                </Fragment>
              );
            })}
          </ul>
        </div>
      </nav>

      <div className="app-content" onClick={() => { if (semi && viewportMode() === 'mobile') setSemi(false); }}>
        <div>
          <header className="header-main">
            <div className="container-fluid">
              <div className="row">
                <div className="col-6 col-sm-4 d-flex align-items-center header-left p-0">
                  <span
                    className="header-toggle me-3"
                    role="button"
                    tabIndex={0}
                    aria-label="Menyuni yig'ish / ochish"
                    onClick={(e) => { e.stopPropagation(); toggleSidebar(); }}
                    onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleSidebar(); } }}
                  >
                    <i className="iconoir-view-grid"></i>
                  </span>
                </div>

                <div className="col-6 col-sm-8 d-flex align-items-center justify-content-end header-right p-0">
                  <ul className="d-flex align-items-center">

                    <li className="header-searchbar">
                      <a
                        className="d-block head-icon"
                        href="#"
                        role="button"
                        aria-label="Qidiruv (Ctrl + K)"
                        title="Qidiruv (Ctrl + K)"
                        onClick={(e) => { e.preventDefault(); e.stopPropagation(); setProfileOpen(false); setSearchOpen((v) => !v); }}
                      >
                        <i className="iconoir-search"></i>
                      </a>
                      <SearchCanvas visibleNav={visibleNav} open={searchOpen} onClose={() => setSearchOpen(false)} />
                    </li>

                    <li
                      className="header-dark"
                      role="button"
                      tabIndex={0}
                      title={darkMode ? "Yorug' rejim" : "Qorong'i rejim"}
                      aria-label="Mavzuni almashtirish"
                      onClick={(e) => { e.stopPropagation(); setDarkMode((value) => !value); }}
                      onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setDarkMode((value) => !value); } }}
                    >
                      <div className={`sun-logo head-icon ${darkMode ? 'sun' : ''}`}>
                        <i className="iconoir-sun-light"></i>
                      </div>
                      <div className={`moon-logo head-icon ${darkMode ? 'moon' : ''}`}>
                        <i className="iconoir-half-moon"></i>
                      </div>
                    </li>

                    <li className="header-profile">
                      <a
                        className="d-block head-icon"
                        href="#"
                        role="button"
                        aria-label="Profil"
                        aria-expanded={profileOpen}
                        onClick={(e) => { e.preventDefault(); e.stopPropagation(); setSearchOpen(false); setProfileOpen((v) => !v); }}
                      >
                        <span className="b-r-50 h-35 w-35 d-flex-center bg-light-primary f-s-14 f-w-600">{initials}</span>
                      </a>
                      <div className={`offcanvas offcanvas-end header-profile-canvas ${profileOpen ? 'show' : ''}`} tabIndex={-1} aria-label="Profil">
                        <div className="offcanvas-body app-scroll">
                          <ul>
                            <li className="d-flex gap-3 mb-3">
                              <div className="d-flex-center">
                                <span className="h-45 w-45 d-flex-center b-r-10 position-relative bg-light-primary f-s-16 f-w-700">{initials}</span>
                              </div>
                              <div className="mt-1 min-w-0">
                                <h6 className="mb-0 txt-ellipsis-1">{admin?.name || 'Admin'}</h6>
                                <p className="f-s-12 mb-0 text-secondary txt-ellipsis-1">{admin?.email || ''}</p>
                                <span className="badge text-light-primary mt-1">{admin?.role || 'Administrator'}{admin?.isReadOnly ? " · faqat ko'rish" : ''}</span>
                              </div>
                            </li>
                            {canSettings ? (
                              <li>
                                <Link className="f-w-500" href="/boshqaruv/settings">
                                  <i className="iconoir-settings pe-1 f-s-20"></i> Sozlamalar
                                </Link>
                              </li>
                            ) : null}
                            <li>
                              <Link className="f-w-500" href="/boshqaruv/live">
                                <i className="iconoir-antenna-signal pe-1 f-s-20"></i> Live Dashboard
                              </Link>
                            </li>
                            <li className="app-divider-v dotted py-1"></li>
                            <li>
                              <div className="d-flex align-items-center justify-content-between">
                                <a className="f-w-500" href="#" onClick={(e) => { e.preventDefault(); setDarkMode((value) => !value); }}>
                                  <i className="iconoir-half-moon pe-1 f-s-20"></i> Qorong'i rejim
                                </a>
                                <div className="flex-shrink-0">
                                  <div className="form-check form-switch">
                                    <input
                                      className="form-check-input form-check-primary"
                                      type="checkbox"
                                      aria-label="Qorong'i rejim"
                                      checked={darkMode}
                                      onChange={(e) => setDarkMode(e.target.checked)}
                                    />
                                  </div>
                                </div>
                              </div>
                            </li>
                            <li className="app-divider-v dotted py-1"></li>
                            <li>
                              <button type="button" className="mb-0 btn btn-light-danger btn-sm justify-content-center w-100" onClick={() => router.post('/boshqaruv/logout')}>
                                <i className="iconoir-log-out pe-1 f-s-20"></i> Chiqish
                              </button>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            {overlayOpen ? (
              <div className="offcanvas-backdrop fade show" onClick={() => { setProfileOpen(false); setSearchOpen(false); }}></div>
            ) : null}
          </header>

          <main>
            <div className="container-fluid">
              <div className="kc-page-body">{children}</div>
              {/* Ko'rish/tahrirlash oynalari shu yerda alohida sahifa sifatida ochiladi (components/AppModal) */}
              <div id="kc-show-root"></div>
            </div>
          </main>
        </div>
      </div>

      <div
        className="go-top"
        role="button"
        aria-label="Yuqoriga"
        style={{ display: scroll > 0 ? 'grid' : 'none',
          background: `conic-gradient(rgba(var(--info),1), rgba(var(--primary),1), rgba(var(--danger),1), rgba(var(--info-dark),1), rgba(var(--primary-dark),1), rgba(var(--danger-dark),1) ${scroll}%, rgba(var(--primary),.3) ${scroll}%)`,
        }}
        onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
      >
        <span className="progress-value"><i className="ti ti-chevron-up"></i></span>
      </div>

      <footer>
        <div className="container-fluid">
          <div className="row">
            <div className="col-md-9 col-12">
              <ul className="footer-text">
                <li><p className="mb-0">Copyright © {new Date().getFullYear()} Kitobchi. Barcha huquqlar himoyalangan.</p></li>
                <li><a href="#" onClick={(e) => e.preventDefault()}> v2.0 </a></li>
              </ul>
            </div>
            <div className="col-md-3 d-none d-md-block">
              <ul className="footer-text text-end">
                <li><Link href="/boshqaruv/tickets">Yordam <i className="ti ti-help"></i></Link></li>
              </ul>
            </div>
          </div>
        </div>
      </footer>

      <div className="toast-container position-fixed bottom-0 end-0 p-3 mb-5">
        {toasts.map((toast) => (
          <div
            key={toast.id}
            className={`toast d-block bg-white ${toast.type === 'success' ? 'b-1-success' : 'b-1-danger'}`}
            role={toast.type === 'success' ? 'status' : 'alert'}
            aria-live="assertive"
            aria-atomic="true"
          >
            <div className="d-flex align-items-center">
              <div className={`toast-body d-flex align-items-center gap-2 f-w-500 ${toast.type === 'success' ? 'text-success' : 'text-danger'}`}>
                <i className={toast.type === 'success' ? 'iconoir-check-circle f-s-20' : 'iconoir-warning-circle f-s-20'}></i>
                <span className="text-dark">{toast.text}</span>
              </div>
              <button type="button" className="btn-close me-2 m-auto" onClick={() => dismiss(toast.id)} aria-label="Yopish"></button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
