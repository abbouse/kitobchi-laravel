import { Fragment, useEffect, useMemo, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { tiIcon } from './utils/icons';

/*
 * Boshqaruv karkasi (2026-10 yangi dizayn):
 *   .kb-shell > aside.kb-side (menyu) + .kb-main > header.kb-top + main.kb-content.
 *   Uslublar: resources/css/boshqaruv-theme.css (`.kb-*`). Sahifalar o'zgarmaydi —
 *   ular avvalgidek main > .container-fluid > .kc-page-body ichida chiziladi.
 */

// `perm` — App\Models\Admin::MODULES dagi modul kaliti bilan bir xil bo'lishi
// SHART (backendda ham xuddi shu kalit bilan panel.permission:<kalit>
// middleware qo'llangan). `perm: null` — hech qanday ruxsat talab qilinmaydi,
// tizimga kirgan har bir admin ko'radi (faqat Dashboard/Live Dashboard).
//
// Menyu 12 ta mayda guruhdan ma'no bo'yicha qayta yig'ilgan; hech bir sahifa
// olib tashlanmagan — faqat joyi o'zgargan.
const nav = [
  { group: 'Asosiy', section: '', icon: 'ti-layout-dashboard', ax: 'ti ti-layout-dashboard', items: [
    { to: '/boshqaruv', match: '/boshqaruv', label: 'Bugun', icon: 'ti-layout-dashboard', perm: null as string | null },
    { to: '/boshqaruv/live', match: '/boshqaruv/live', label: 'Live Dashboard', icon: 'ti-broadcast', badge: 'LIVE', perm: null as string | null },
  ] },
  { group: 'Buyurtmalar', section: 'Savdo', icon: 'ti-receipt', ax: 'ti ti-receipt', items: [
    { to: '/boshqaruv/orders', match: '/boshqaruv/orders', label: 'Barcha buyurtmalar', icon: 'ti-receipt', perm: 'orders' },
    { to: '/boshqaruv/seller-orders', match: '/boshqaruv/seller-orders', label: 'Do‘kon buyurtmalari', icon: 'ti-shopping-bag', perm: 'sellers' },
    { to: '/boshqaruv/courier-orders', match: '/boshqaruv/courier-orders', label: 'Kuryer topshiriqlari', icon: 'ti-truck-delivery', perm: 'couriers' },
    { to: '/boshqaruv/split', match: '/boshqaruv/split', label: 'Split nazorati', icon: 'ti-wallet', perm: 'split' },
    { to: '/boshqaruv/demand-analytics', match: '/boshqaruv/demand-analytics', label: 'Talab va savat analitikasi', icon: 'ti-chart-bar', perm: 'orders' },
  ] },
  { group: 'Mijozlar', section: 'Savdo', icon: 'ti-users', ax: 'ti ti-users', items: [
    { to: '/boshqaruv/users', match: '/boshqaruv/users', label: 'Foydalanuvchilar', icon: 'ti-users', perm: 'users' },
    { to: '/boshqaruv/support/inbox', match: '/boshqaruv/support/inbox', label: 'Support inbox', icon: 'ti-messages', badge: 'LIVE', perm: 'support' },
    { to: '/boshqaruv/support/kpi', match: '/boshqaruv/support/kpi', label: 'Support KPI', icon: 'ti-chart-histogram', perm: 'support' },
    { to: '/boshqaruv/shikoyatlar', match: '/boshqaruv/shikoyatlar', label: 'Shikoyatlar', icon: 'ti-alert-triangle', perm: 'support' },
    { to: '/boshqaruv/search-history', match: '/boshqaruv/search-history', label: 'Qidiruv tarixi', icon: 'ti-history', perm: 'search-history' },
  ] },
  { group: 'Katalog', section: 'Savdo', icon: 'ti-book', ax: 'ti ti-book', items: [
    { to: '/boshqaruv/books', match: '/boshqaruv/books', label: 'Kitoblar', icon: 'ti-book', perm: 'catalog' },
    { to: '/boshqaruv/catalog', match: '/boshqaruv/catalog', label: 'Global katalog', icon: 'ti-stack-2', perm: 'catalog' },
    { to: '/boshqaruv/catalog/submissions', match: '/boshqaruv/catalog/submissions', label: 'Kitob arizalari', icon: 'ti-inbox', perm: 'catalog' },
    { to: '/boshqaruv/catalog-slots', match: '/boshqaruv/catalog-slots', label: 'Katalog joylari', icon: 'ti-crown', perm: 'catalog' },
    { to: '/boshqaruv/book-categories', match: '/boshqaruv/book-categories', label: 'Kitob kategoriyalari', icon: 'ti-bookmarks', perm: 'catalog' },
    { to: '/boshqaruv/stationeries', match: '/boshqaruv/stationeries', label: 'Kanselyariya', icon: 'ti-edit', perm: 'catalog' },
    { to: '/boshqaruv/stationery-categories', match: '/boshqaruv/stationery-categories', label: 'Kanstovar kategoriyalari', icon: 'ti-tags', perm: 'catalog' },
    { to: '/boshqaruv/authors', match: '/boshqaruv/authors', label: 'Mualliflar', icon: 'ti-id', perm: 'catalog' },
    { to: '/boshqaruv/publishers', match: '/boshqaruv/publishers', label: 'Nashriyotlar', icon: 'ti-building', perm: 'catalog' },
  ] },
  { group: 'Hamkorlar', section: 'Hamkorlar va logistika', icon: 'ti-building-store', ax: 'ti ti-building-store', items: [
    { to: '/boshqaruv/sellers', match: '/boshqaruv/sellers', label: 'Sotuvchilar va mualliflar', icon: 'ti-building-store', perm: 'sellers' },
    { to: '/boshqaruv/seller-ai-actions', match: '/boshqaruv/seller-ai-actions', label: 'Seller AI audit', icon: 'ti-robot', perm: 'seller-ai' },
  ] },
  { group: 'Logistika', section: 'Hamkorlar va logistika', icon: 'ti-truck', ax: 'ti ti-truck', items: [
    { to: '/boshqaruv/couriers', match: '/boshqaruv/couriers', label: 'Kuryerlar floti', icon: 'ti-bike', perm: 'couriers' },
    { to: '/boshqaruv/hubs', match: '/boshqaruv/hubs', label: 'Hub fulfillment', icon: 'ti-building-warehouse', perm: 'hubs' },
    { to: '/boshqaruv/hub-arizalari', match: '/boshqaruv/hub-arizalari', label: 'Hub arizalari', icon: 'ti-home-plus', perm: 'hubs' },
    { to: '/boshqaruv/logistika', match: '/boshqaruv/logistika', label: 'Logistika zonalari', icon: 'ti-map-pin', perm: 'logistika' },
  ] },
  { group: 'Moliya', section: 'Moliya', icon: 'ti-wallet', ax: 'ti ti-wallet', items: [
    { to: '/boshqaruv/transactions', match: '/boshqaruv/transactions', label: 'Tranzaksiyalar', icon: 'ti-coins', perm: 'finance' },
    { to: '/boshqaruv/fiscalization', match: '/boshqaruv/fiscalization', label: 'Fiskalizatsiya', icon: 'ti-qrcode', perm: 'finance' },
    { to: '/boshqaruv/commission-audit', match: '/boshqaruv/commission-audit', label: 'Komissiya audit', icon: 'ti-percentage', perm: 'finance' },
    { to: '/boshqaruv/expenses', match: '/boshqaruv/expenses', label: 'Chiqimlar', icon: 'ti-wallet', perm: 'finance' },
  ] },
  { group: 'Marketing', section: "O'sish", icon: 'ti-speakerphone', ax: 'ti ti-speakerphone', items: [
    { to: '/boshqaruv/home-sections', match: '/boshqaruv/home-sections', label: 'Ilova bosh sahifasi', icon: 'ti-layout-grid', perm: 'marketing' },
    { to: '/boshqaruv/reklamalar', match: '/boshqaruv/reklamalar', label: 'Reklamalar', icon: 'ti-speakerphone', perm: 'marketing' },
    { to: '/boshqaruv/promokodlar', match: '/boshqaruv/promokodlar', label: 'Promokodlar', icon: 'ti-ticket', perm: 'marketing' },
    { to: '/boshqaruv/push', match: '/boshqaruv/push', label: 'Push bildirishnomalar', icon: 'ti-bell', perm: 'push' },
    { to: '/boshqaruv/gift-sertifikatlar', match: '/boshqaruv/gift-sertifikatlar', label: 'Gift sertifikatlar', icon: 'ti-gift', perm: 'marketing' },
    { to: '/boshqaruv/blogerlar', match: '/boshqaruv/blogerlar', label: 'Blogerlar', icon: 'ti-users', perm: 'marketing' },
  ] },
  { group: 'Kontent', section: "O'sish", icon: 'ti-movie', ax: 'ti ti-movie', items: [
    { to: '/boshqaruv/book-club', match: '/boshqaruv/book-club', label: 'Book Club', icon: 'ti-bookmark', perm: 'book-club' },
    { to: '/boshqaruv/collections', match: '/boshqaruv/collections', label: 'To‘plamlar', icon: 'ti-stack-2', perm: 'marketing' },
    { to: '/boshqaruv/market-news', match: '/boshqaruv/market-news', label: 'Market yangiliklari', icon: 'ti-news', perm: 'marketing' },
    { to: '/boshqaruv/reels', match: '/boshqaruv/reels', label: 'Reels / Shorts', icon: 'ti-movie', perm: 'marketing' },
    { to: '/boshqaruv/book-videos', match: '/boshqaruv/book-videos', label: 'Kitob videolari', icon: 'ti-video', perm: 'marketing' },
  ] },
  { group: 'Sodiqlik', section: "O'sish", icon: 'ti-gift', ax: 'ti ti-gift', items: [
    { to: '/boshqaruv/prize-game', match: '/boshqaruv/prize-game', label: 'Omadli maydon', icon: 'ti-rotate-clockwise', perm: 'premium' },
    { to: '/boshqaruv/darajalar', match: '/boshqaruv/darajalar', label: 'Darajalar va nishonlar', icon: 'ti-trophy', perm: 'premium' },
    { to: '/boshqaruv/mystery-box', match: '/boshqaruv/mystery-box', label: 'Mystery Box', icon: 'ti-package', perm: 'premium' },
    { to: '/boshqaruv/sovgalar', match: '/boshqaruv/sovgalar', label: 'Sovg‘alar', icon: 'ti-gift', perm: 'premium' },
  ] },
  { group: 'Jamoa', section: 'Tizim', icon: 'ti-users-group', ax: 'ti ti-users-group', items: [
    { to: '/boshqaruv/team', match: '/boshqaruv/team', label: 'Jamoa KPI', icon: 'ti-users-group', perm: 'audit-logs|admins' },
    { to: '/boshqaruv/adminlar', match: '/boshqaruv/adminlar', label: 'Adminlar', icon: 'ti-shield-lock', perm: 'admins' },
    { to: '/boshqaruv/audit-logs', match: '/boshqaruv/audit-logs', label: 'Audit log', icon: 'ti-clipboard-data', perm: 'audit-logs' },
    { to: '/boshqaruv/vakansiyalar', match: '/boshqaruv/vakansiyalar', label: 'Vakansiyalar', icon: 'ti-id-badge', perm: 'hr' },
    { to: '/boshqaruv/karyera-arizalari', match: '/boshqaruv/karyera-arizalari', label: 'Karyera arizalari', icon: 'ti-file-certificate', perm: 'hr' },
  ] },
  { group: 'Tizim', section: 'Tizim', icon: 'ti-settings', ax: 'ti ti-settings', items: [
    { to: '/boshqaruv/security', match: '/boshqaruv/security', label: 'Kiberxavfsizlik va server', icon: 'ti-shield-check', perm: 'settings' },
    { to: '/boshqaruv/siyosatlar', match: '/boshqaruv/siyosatlar', label: 'Siyosatlar', icon: 'ti-file-text', perm: 'settings' },
    { to: '/boshqaruv/shartnomalar', match: '/boshqaruv/shartnomalar', label: 'Shartnomalar', icon: 'ti-file-certificate', perm: 'settings' },
    { to: '/boshqaruv/api-clients', match: '/boshqaruv/api-clients', label: 'API mijozlar', icon: 'ti-code', perm: 'settings' },
    { to: '/boshqaruv/settings', match: '/boshqaruv/settings', label: 'Sozlamalar', icon: 'ti-settings', perm: 'settings' },
  ] },
];

type WorkQueue = {
  items: Array<{ key: string; label: string; count: number; tone: string; icon: string; url: string }>;
  nav: Record<string, number> | Record<string, never>;
  total: number;
};

type NavItem = { to: string; match: string; label: string; icon: string; badge?: string; perm: string | null };
type NavGroup = { group: string; section: string; icon: string; ax: string; items: NavItem[] };

type PanelAdmin = { name?: string; email?: string; role?: string; roleKey?: string; isSuperAdmin?: boolean; isReadOnly?: boolean; permissions?: string[] };

// Adminning ruxsatlariga qarab menyuni filtrlaydi: superadmin — hammasini
// ko'radi; boshqalar — faqat `permissions` massivida bor modullarni. Bo'sh
// qolgan guruhlar butunlay yashiriladi.
function filterNavByPermissions(admin?: PanelAdmin): NavGroup[] {
  const isSuper = !!admin?.isSuperAdmin;
  const perms = new Set(admin?.permissions || []);
  // "a|b" — kamida bittasi bo'lsa kifoya
  const allowed = (perm: string | null) => perm === null || isSuper || perm.split('|').some((p) => perms.has(p));

  return (nav as NavGroup[])
    .map((g) => ({ ...g, items: g.items.filter((it) => allowed(it.perm)) }))
    .filter((g) => g.items.length > 0);
}

// URL bo'yicha faol menyu bandini topish (menyu, yuqori panel va show-sahifa yo'li uchun)
function navMatches(url: string, match: string) {
  const path = url.split('#')[0];
  if (match === '/boshqaruv') return path === '/boshqaruv' || path === '/boshqaruv/' || path.startsWith('/boshqaruv?');
  if (match.includes('?')) return path.startsWith(match);
  if (match === '/boshqaruv/tickets' && path.includes('tickets_source=seller')) return false;
  if (match === '/boshqaruv/catalog' && (path.startsWith('/boshqaruv/catalog/submissions') || path.startsWith('/boshqaruv/catalog-slots'))) return false;
  if (match === '/boshqaruv/sellers' && path.startsWith('/boshqaruv/seller-orders')) return false;
  if (match === '/boshqaruv/couriers' && path.startsWith('/boshqaruv/courier-orders')) return false;
  return path === match || path.startsWith(`${match}/`) || path.startsWith(`${match}?`);
}

export function findCurrentNav(url: string) {
  return (nav as NavGroup[])
    .flatMap((g) => g.items.map((it) => ({ ...it, group: g.group, groupAx: g.ax, groupFirst: g.items[0]?.to || '/boshqaruv' })))
    .filter((it) => navMatches(url, it.match))
    .sort((a, b) => b.match.length - a.match.length)[0];
}

/**
 * Sahifa sarlavhasi ostidagi yo'l endi yuqori panelda ko'rsatiladi.
 * Komponent saqlab qolindi — sahifalar uni chaqirishda davom etadi.
 */
export function PageCrumbs() {
  return null;
}

// Menyu bo'limlari — tartib shu yerda
const SECTIONS = ['Savdo', 'Hamkorlar va logistika', 'Moliya', "O'sish", 'Tizim'];

// ── Ikonkalar (karkas uchun, chiziqli SVG) ──
const Svg = ({ d, size = 18 }: { d: React.ReactNode; size?: number }) => (
  <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{d}</svg>
);
const IcSearch = () => <Svg d={<><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></>} />;
const IcBell = () => <Svg d={<><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9" /><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0" /></>} />;
const IcShield = () => <Svg d={<><path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6l8-3z" /><path d="m9 12 2 2 4-4" /></>} />;
const IcMoon = () => <Svg d={<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z" />} />;
const IcSun = () => <Svg d={<><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" /></>} />;
const IcSidebar = () => <Svg d={<><rect x="3" y="4" width="18" height="16" rx="2.5" /><path d="M9 4v16" /></>} />;
const IcMenu = () => <Svg d={<path d="M4 7h16M4 12h16M4 17h16" />} />;
const IcSettings = () => <Svg d={<><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z" /></>} />;
const IcLive = () => <Svg d={<><circle cx="12" cy="12" r="2" /><path d="M16.2 7.8a6 6 0 0 1 0 8.4M7.8 16.2a6 6 0 0 1 0-8.4M19 5a10 10 0 0 1 0 14M5 19A10 10 0 0 1 5 5" /></>} />;
const IcLogout = () => <Svg d={<><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3" /><path d="M10 17l-5-5 5-5" /><path d="M5 12h11" /></>} />;
const IcUp = () => <Svg d={<path d="m6 15 6-6 6 6" />} />;
const IcChevUpDown = () => <Svg size={16} d={<path d="m8 9 4-4 4 4M8 15l4 4 4-4" />} />;

// ── ⌘K: bo'limga sakrash + asosiy ro'yxatlarda qidirish ──
const searchTargets = [
  { label: 'Buyurtmalardan qidirish', path: '/boshqaruv/orders', param: 'orders_search', icon: 'ti-receipt' },
  { label: 'Foydalanuvchilardan qidirish', path: '/boshqaruv/users', param: 'users_search', icon: 'ti-users' },
  { label: 'Kitoblardan qidirish', path: '/boshqaruv/books', param: 'books_search', icon: 'ti-book' },
  { label: 'Sotuvchi va mualliflardan qidirish', path: '/boshqaruv/sellers', param: 'sellers_search', icon: 'ti-building-store' },
  { label: 'Tranzaksiyalardan qidirish', path: '/boshqaruv/transactions', param: 'transactions_search', icon: 'ti-coins' },
  { label: 'Talab analitikasidan qidirish', path: '/boshqaruv/demand-analytics', param: 'search', icon: 'ti-chart-bar' },
];

type PaletteResult = { key: string; label: string; icon: string; hint?: string; group: string; go: () => void };

const normalize = (value: string) => value.toLowerCase().replace(/[‘’ʻʼ`']/g, "'").trim();

function CommandPalette({ visibleNav, open, onClose }: { visibleNav: NavGroup[]; open: boolean; onClose: () => void }) {
  const [query, setQuery] = useState('');
  const [active, setActive] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);
  const listRef = useRef<HTMLDivElement>(null);

  const navItems = useMemo(() => visibleNav.flatMap((g) => g.items.map((it) => ({ ...it, group: g.group }))), [visibleNav]);

  const results = useMemo<PaletteResult[]>(() => {
    const q = normalize(query);
    const pages: PaletteResult[] = navItems
      .filter((it) => !q || normalize(it.label).includes(q) || normalize(it.group).includes(q))
      .slice(0, q ? 8 : 7)
      .map((it) => ({
        key: `nav:${it.to}`,
        label: it.label,
        icon: it.icon,
        hint: it.group === 'Asosiy' ? 'Boshqaruv' : it.group,
        group: q ? 'Sahifalar' : "Tezkor o'tish",
        go: () => router.visit(it.to),
      }));
    if (!q) return pages;
    const allowedPaths = new Set(navItems.map((it) => it.to));
    const jumps: PaletteResult[] = searchTargets.filter((t) => allowedPaths.has(t.path)).map((t) => ({
      key: `jump:${t.path}`,
      label: `${t.label}: «${query.trim()}»`,
      icon: t.icon,
      hint: "Ro'yxat ichida qidirish",
      group: 'Qidirish',
      go: () => router.get(t.path, { [t.param]: query.trim() }),
    }));
    return [...pages, ...jumps];
  }, [query, navItems]);

  useEffect(() => { setActive(0); }, [query]);
  useEffect(() => {
    if (open) window.setTimeout(() => inputRef.current?.focus(), 30);
    else setQuery('');
  }, [open]);
  useEffect(() => {
    const el = listRef.current?.querySelector<HTMLElement>(`[data-index="${active}"]`);
    el?.scrollIntoView({ block: 'nearest' });
  }, [active]);

  if (!open) return null;

  const pick = (result: PaletteResult) => {
    onClose();
    result.go();
  };

  let lastGroup = '';
  return (
    <div className="kb-palette-backdrop" onMouseDown={onClose}>
      <div className="kb-palette" role="dialog" aria-modal="true" aria-label="Qidiruv va buyruqlar" onMouseDown={(e) => e.stopPropagation()}>
        <form
          className="kb-palette-input"
          onSubmit={(e) => { e.preventDefault(); const r = results[active] || results[0]; if (r) pick(r); }}
        >
          <IcSearch />
          <input
            ref={inputRef}
            type="text"
            autoComplete="off"
            spellCheck={false}
            placeholder="Sahifa, buyurtma ID, telefon yoki kitob nomi…"
            aria-label="Qidiruv"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Escape') { e.preventDefault(); onClose(); }
              if (e.key === 'ArrowDown') { e.preventDefault(); setActive((v) => Math.min(v + 1, results.length - 1)); }
              if (e.key === 'ArrowUp') { e.preventDefault(); setActive((v) => Math.max(v - 1, 0)); }
            }}
          />
          <span className="kb-kbd">Esc</span>
        </form>
        <div className="kb-palette-list" ref={listRef}>
          {results.map((r, index) => {
            const header = r.group !== lastGroup ? r.group : null;
            lastGroup = r.group;
            return (
              <Fragment key={r.key}>
                {header ? <div className="kb-palette-group">{header}</div> : null}
                <button
                  type="button"
                  data-index={index}
                  className={`kb-palette-item ${index === active ? 'is-active' : ''}`}
                  onMouseMove={() => setActive(index)}
                  onClick={() => pick(r)}
                >
                  <span className="kb-pal-ico"><i className={tiIcon(r.icon)}></i></span>
                  <span className="kb-pal-text"><b>{r.label}</b>{r.hint ? <span>{r.hint}</span> : null}</span>
                  <span className="kb-pal-enter">↵</span>
                </button>
              </Fragment>
            );
          })}
          {results.length === 0 ? <div className="kb-palette-empty">Hech narsa topilmadi — boshqa so‘z bilan urinib ko‘ring</div> : null}
        </div>
        <div className="kb-palette-foot">
          <span><span className="kb-kbd">↑</span><span className="kb-kbd">↓</span> tanlash</span>
          <span><span className="kb-kbd">↵</span> ochish</span>
          <span><span className="kb-kbd">Ctrl</span><span className="kb-kbd">K</span> istalgan joydan</span>
        </div>
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

// ≥1200px — to'liq menyu (tanlov eslab qolinadi), 768–1199px — ixcham (ikonka),
// <768px — yashirin, tugma bilan chiqib keladi.
const viewportMode = () => {
  if (typeof window === 'undefined') return 'desktop';
  const w = window.innerWidth;
  if (w < 768) return 'mobile';
  if (w < 1200) return 'tablet';
  return 'desktop';
};
const storedRail = () => {
  try { return localStorage.getItem('boshqaruv-sidebar') === 'semi'; } catch { return false; }
};
const railFor = (mode: string) => (mode === 'mobile' ? false : mode === 'tablet' ? true : storedRail());

export function applyTheme(dark: boolean) {
  document.body.classList.add('ltr');
  document.body.classList.toggle('dark', dark);
  try { localStorage.setItem('boshqaruv-theme', dark ? 'dark' : 'light'); } catch { /* storage yopiq */ }
}

const countText = (n: number) => (n > 99 ? '99+' : String(n));

export default function Layout({ children }: { children: React.ReactNode }) {
  const [mode, setMode] = useState(viewportMode);
  const [rail, setRail] = useState(() => railFor(viewportMode()));
  const [mobileOpen, setMobileOpen] = useState(false);
  const [darkMode, setDarkMode] = useState(() => {
    if (typeof window === 'undefined') return false;
    try { return localStorage.getItem('boshqaruv-theme') === 'dark'; } catch { return false; }
  });
  const [profileOpen, setProfileOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [bellOpen, setBellOpen] = useState(false);
  const [showTop, setShowTop] = useState(false);
  const { url, props } = usePage<{
    auth?: { admin?: PanelAdmin };
    workQueue?: WorkQueue | null;
  }>();
  const queue = props.workQueue || null;
  const navCount = (to: string) => (queue?.nav && (queue.nav as Record<string, number>)[to]) || 0;
  const admin = props.auth?.admin;
  const initials = initialsOf(admin?.name);
  const { toasts, dismiss } = useFlashToasts();
  const visibleNav = useMemo(() => filterNavByPermissions(admin), [admin]);
  const bellRef = useRef<HTMLDivElement>(null);
  const profileRef = useRef<HTMLDivElement>(null);

  const closeOverlays = () => { setProfileOpen(false); setSearchOpen(false); setBellOpen(false); };

  useEffect(() => {
    closeOverlays();
    setMobileOpen(false);
  }, [url]);
  useEffect(() => { applyTheme(darkMode); }, [darkMode]);
  useEffect(() => {
    const onResize = () => {
      const next = viewportMode();
      setMode((prev) => {
        if (prev !== next) {
          setRail(railFor(next));
          if (next !== 'mobile') setMobileOpen(false);
        }
        return next;
      });
    };
    const onScroll = () => setShowTop(document.documentElement.scrollTop > 400);
    const onKey = (event: KeyboardEvent) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        setProfileOpen(false);
        setBellOpen(false);
        setSearchOpen((v) => !v);
      }
      if (event.key === 'Escape') { setProfileOpen(false); setSearchOpen(false); setBellOpen(false); setMobileOpen(false); }
    };
    const onDown = (event: MouseEvent) => {
      const target = event.target as Node;
      if (bellRef.current && !bellRef.current.contains(target)) setBellOpen(false);
      if (profileRef.current && !profileRef.current.contains(target)) setProfileOpen(false);
    };
    window.addEventListener('resize', onResize);
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('keydown', onKey);
    document.addEventListener('mousedown', onDown);
    return () => {
      window.removeEventListener('resize', onResize);
      window.removeEventListener('scroll', onScroll);
      window.removeEventListener('keydown', onKey);
      document.removeEventListener('mousedown', onDown);
    };
  }, []);

  const isActive = (match: string) => navMatches(url, match);
  const currentNav = useMemo(() => findCurrentNav(url), [url]);

  const flatGroup = visibleNav.find((g) => g.group === 'Asosiy');
  const groups = visibleNav.filter((g) => g.group !== 'Asosiy');
  const activeGroup = currentNav && currentNav.group !== 'Asosiy' ? currentNav.group : null;
  const [openGroups, setOpenGroups] = useState<string[]>(activeGroup ? [activeGroup] : []);
  useEffect(() => {
    if (activeGroup) setOpenGroups((cur) => (cur.includes(activeGroup) ? cur : [...cur, activeGroup]));
  }, [activeGroup]);

  // Brauzer tab sarlavhasi joriy bo'limga mos bo'ladi
  useEffect(() => {
    document.title = currentNav && currentNav.match !== '/boshqaruv'
      ? `${currentNav.label} — Kitobchi Boshqaruv`
      : 'Kitobchi Boshqaruv';
  }, [currentNav]);

  const isRail = rail && mode !== 'mobile';
  const toggleRail = () => {
    if (mode === 'mobile') { setMobileOpen((v) => !v); return; }
    setRail((value) => {
      const next = !value;
      if (mode === 'desktop') {
        try { localStorage.setItem('boshqaruv-sidebar', next ? 'semi' : 'full'); } catch { /* storage yopiq */ }
      }
      return next;
    });
  };
  const toggleGroup = (group: string) => {
    if (isRail) {
      // Ixcham menyuda guruh bosilsa — menyu ochiladi va shu guruh ko'rinadi
      setRail(false);
      if (mode === 'desktop') { try { localStorage.setItem('boshqaruv-sidebar', 'full'); } catch { /* */ } }
      setOpenGroups((cur) => (cur.includes(group) ? cur : [...cur, group]));
      return;
    }
    setOpenGroups((cur) => (cur.includes(group) ? cur.filter((g) => g !== group) : [...cur, group]));
  };

  // Hisoblagich bo'lsa — son (qizil), bo'lmasa statik "LIVE" yorlig'i
  const itemBadge = (it: NavItem) => {
    const count = navCount(it.to);
    if (count > 0) return <span className="kb-count" title="Diqqat talab qiladi">{countText(count)}</span>;
    if (it.badge) return <span className={`kb-tag ${it.badge === 'LIVE' ? 'is-live' : ''}`}>{it.badge}</span>;
    return null;
  };

  const canSettings = visibleNav.some((g) => g.items.some((it) => it.to === '/boshqaruv/settings'));
  const canSecurity = visibleNav.some((g) => g.items.some((it) => it.to === '/boshqaruv/security'));

  const crumbGroup = currentNav && currentNav.group !== 'Asosiy' ? currentNav.group : null;

  return (
    <div className={`kb-shell ${isRail ? 'is-rail' : ''} ${mobileOpen ? 'is-open' : ''}`}>
      <aside className="kb-side" aria-label="Asosiy menyu">
        <Link className="kb-brand" href="/boshqaruv" aria-label="Kitobchi Boshqaruv">
          <img src="/favicon.svg" alt="" width={32} height={32} />
          <span className="kb-brand-text"><b>Kitobchi</b><span>Boshqaruv</span></span>
        </Link>

        <div className="kb-side-search">
          <button type="button" className="kb-search-btn" onClick={() => { setMobileOpen(false); setSearchOpen(true); }}>
            <IcSearch />
            <span>Qidirish yoki o‘tish…</span>
            <span className="kb-kbd">Ctrl K</span>
          </button>
        </div>

        <div className="kb-nav" role="navigation" aria-label="Bo‘limlar">
          {flatGroup?.items.map((it) => (
            <Link
              key={it.to}
              href={it.to}
              className={`kb-nav-link ${isActive(it.match) ? 'is-active' : ''}`}
              title={it.label}
              aria-current={isActive(it.match) ? 'page' : undefined}
            >
              <i className={tiIcon(it.icon)}></i>
              <span className="kb-label">{it.label}</span>
              {itemBadge(it)}
            </Link>
          ))}

          {SECTIONS.map((section) => {
            const inSection = groups.filter((g) => g.section === section);
            if (!inSection.length) return null;
            return (
              <Fragment key={section}>
                <div className="kb-nav-section">{section}</div>
                <div className="kb-nav-sep" aria-hidden="true"></div>
                {inSection.map((g) => {
                  const isOpen = openGroups.includes(g.group);
                  const hasCurrent = activeGroup === g.group;
                  const groupCount = g.items.reduce((sum, it) => sum + navCount(it.to), 0);
                  const subId = `kb-nav-${g.group.replace(/[^a-z0-9]+/gi, '-').toLowerCase()}`;
                  return (
                    <Fragment key={g.group}>
                      <button
                        type="button"
                        className={`kb-nav-link ${hasCurrent ? 'is-current' : ''}`}
                        aria-expanded={!isRail && isOpen}
                        aria-controls={subId}
                        title={g.group}
                        onClick={() => toggleGroup(g.group)}
                      >
                        <i className={tiIcon(g.icon)}></i>
                        <span className="kb-label">{g.group}</span>
                        {(!isOpen || isRail) && groupCount > 0 ? <span className="kb-count">{countText(groupCount)}</span> : null}
                        <i className="ti ti-chevron-right kb-chev" aria-hidden="true"></i>
                      </button>
                      {isOpen && !isRail ? (
                        <div className="kb-sub" id={subId}>
                          {g.items.map((it) => (
                            <Link
                              key={it.to}
                              href={it.to}
                              className={`kb-sub-link ${isActive(it.match) ? 'is-active' : ''}`}
                              aria-current={isActive(it.match) ? 'page' : undefined}
                            >
                              <span className="kb-label">{it.label}</span>
                              {itemBadge(it)}
                            </Link>
                          ))}
                        </div>
                      ) : null}
                    </Fragment>
                  );
                })}
              </Fragment>
            );
          })}
        </div>

        <div className="kb-side-foot position-relative" ref={profileRef}>
          <button
            type="button"
            className="kb-me"
            aria-haspopup="menu"
            aria-expanded={profileOpen}
            onClick={() => { setBellOpen(false); setProfileOpen((v) => !v); }}
            title={admin?.name || 'Profil'}
          >
            <span className="kb-avatar">{initials}</span>
            <span className="kb-me-text">
              <b>{admin?.name || 'Admin'}</b>
              <span>{admin?.role || 'Administrator'}{admin?.isReadOnly ? " · faqat ko'rish" : ''}</span>
            </span>
            <span className="kb-me-text" style={{ flex: '0 0 auto', color: 'var(--kb-muted)' }}><IcChevUpDown /></span>
          </button>
          {profileOpen ? (
            <div className="kb-pop" role="menu" style={{ top: 'auto', bottom: 'calc(100% + 6px)', left: 10, right: 'auto', width: 260 }}>
              <div className="d-flex align-items-center gap-2 p-3" style={{ borderBottom: '1px solid var(--kb-line-soft)' }}>
                <span className="kb-avatar" style={{ width: 38, height: 38, flexBasis: 38, fontSize: 13 }}>{initials}</span>
                <span className="min-w-0 d-flex flex-column" style={{ lineHeight: 1.3 }}>
                  <b className="text-truncate" style={{ fontSize: 13.5 }}>{admin?.name || 'Admin'}</b>
                  <span className="text-truncate text-secondary" style={{ fontSize: 12 }}>{admin?.email || ''}</span>
                </span>
              </div>
              <div className="p-1">
                <Link className="kb-menu-item" href="/boshqaruv/live" role="menuitem"><IcLive /> Live Dashboard</Link>
                {canSecurity ? <Link className="kb-menu-item" href="/boshqaruv/security" role="menuitem"><IcShield /> Kiberxavfsizlik va server</Link> : null}
                {canSettings ? <Link className="kb-menu-item" href="/boshqaruv/settings" role="menuitem"><IcSettings /> Sozlamalar</Link> : null}
                <button type="button" className="kb-menu-item" role="menuitemcheckbox" aria-checked={darkMode} onClick={() => setDarkMode((v) => !v)}>
                  <IcMoon /> Qorong‘i rejim <span className={`kb-switch ${darkMode ? 'is-on' : ''}`} aria-hidden="true"></span>
                </button>
                <div className="kb-menu-sep"></div>
                <button type="button" className="kb-menu-item is-danger" role="menuitem" onClick={() => router.post('/boshqaruv/logout')}>
                  <IcLogout /> Chiqish
                </button>
              </div>
            </div>
          ) : null}
        </div>
      </aside>

      {mobileOpen ? <div className="kb-overlay" onClick={() => setMobileOpen(false)} aria-hidden="true"></div> : null}

      <div className="kb-main">
        <header className="kb-top">
          <button
            type="button"
            className="kb-icon-btn"
            onClick={toggleRail}
            aria-label={mode === 'mobile' ? 'Menyuni ochish' : isRail ? 'Menyuni kengaytirish' : "Menyuni yig'ish"}
            title={mode === 'mobile' ? 'Menyu' : isRail ? 'Menyuni kengaytirish' : "Menyuni yig'ish"}
          >
            {mode === 'mobile' ? <IcMenu /> : <IcSidebar />}
          </button>

          <div className="kb-crumbs" role="navigation" aria-label="Yo'l">
            {currentNav ? (
              <>
                {crumbGroup ? (
                  <>
                    <Link href={currentNav.groupFirst} className="kb-hide-mobile">{crumbGroup}</Link>
                    <span className="kb-crumb-sep kb-hide-mobile" aria-hidden="true">/</span>
                  </>
                ) : null}
                <span className="kb-crumb-cur">{currentNav.label}</span>
              </>
            ) : (
              <span className="kb-crumb-cur">Boshqaruv</span>
            )}
          </div>

          <div className="kb-top-search kb-hide-mobile">
            <button type="button" className="kb-search-btn" onClick={() => { closeOverlays(); setSearchOpen(true); }}>
              <IcSearch />
              <span>Qidirish…</span>
              <span className="kb-kbd">Ctrl K</span>
            </button>
          </div>

          <div className="kb-top-actions">
            <button type="button" className="kb-icon-btn kb-only-mobile" aria-label="Qidirish" onClick={() => { closeOverlays(); setSearchOpen(true); }}>
              <IcSearch />
            </button>

            <div className="position-relative" ref={bellRef}>
              <button
                type="button"
                className="kb-icon-btn"
                aria-label={queue && queue.total > 0 ? `Navbatdagi ishlar: ${queue.total}` : 'Navbatdagi ishlar'}
                title="Navbatdagi ishlar"
                aria-expanded={bellOpen}
                onClick={() => { setProfileOpen(false); setBellOpen((v) => !v); }}
              >
                <IcBell />
                {queue && queue.total > 0 ? <span className="kb-dot-count">{countText(queue.total)}</span> : null}
              </button>
              {bellOpen ? (
                <div className="kb-pop" role="dialog" aria-label="Navbatdagi ishlar">
                  <div className="kb-pop-head">
                    <span>Navbatdagi ishlar</span>
                    <Link href="/boshqaruv">Bugun sahifasi</Link>
                  </div>
                  {queue && queue.items.length ? (
                    <div className="kb-pop-list">
                      {queue.items.map((item) => (
                        <Link key={item.key} href={item.url} className="kb-pop-row">
                          <span className={`kb-pop-ico text-light-${item.tone}`}><i className={tiIcon(item.icon)}></i></span>
                          <span className="kb-pop-text">{item.label}</span>
                          <span className={`badge text-light-${item.tone}`}>{item.count}</span>
                        </Link>
                      ))}
                    </div>
                  ) : (
                    <div className="kb-pop-empty"><i className="ti ti-circle-check"></i>Navbatda ish yo‘q — hammasi joyida</div>
                  )}
                </div>
              ) : null}
            </div>

            {canSecurity ? (
              <Link className="kb-icon-btn kb-hide-mobile" href="/boshqaruv/security" title="Kiberxavfsizlik va server" aria-label="Kiberxavfsizlik va server">
                <IcShield />
              </Link>
            ) : null}

            <button
              type="button"
              className="kb-icon-btn"
              onClick={() => setDarkMode((v) => !v)}
              aria-label={darkMode ? "Yorug' rejimga o'tish" : "Qorong'i rejimga o'tish"}
              title={darkMode ? "Yorug' rejim" : "Qorong'i rejim"}
            >
              {darkMode ? <IcSun /> : <IcMoon />}
            </button>
          </div>
        </header>

        <main className="kb-content">
          <div className="container-fluid">
            <div className="kc-page-body">{children}</div>
            {/* Ko'rish/tahrirlash oynalari shu yerda alohida sahifa sifatida ochiladi (components/AppModal) */}
            <div id="kc-show-root"></div>
          </div>
        </main>

        <div className="kb-foot">
          <span>© {new Date().getFullYear()} Kitobchi · Boshqaruv v2.1</span>
          <Link href="/boshqaruv">Bugun sahifasi</Link>
        </div>
      </div>

      <CommandPalette visibleNav={visibleNav} open={searchOpen} onClose={() => setSearchOpen(false)} />

      {showTop ? (
        <button type="button" className="kb-gotop" aria-label="Yuqoriga" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}>
          <IcUp />
        </button>
      ) : null}

      <div className="toast-container position-fixed bottom-0 end-0 p-3" style={{ zIndex: 1080 }}>
        {toasts.map((toast) => (
          <div
            key={toast.id}
            className={`toast d-block kc-toast mb-2 ${toast.type === 'success' ? 'b-1-success' : 'b-1-danger'}`}
            role={toast.type === 'success' ? 'status' : 'alert'}
            aria-live="assertive"
            aria-atomic="true"
          >
            <div className="d-flex align-items-center">
              <div className="toast-body d-flex align-items-center gap-2 f-w-500">
                <i className={`${toast.type === 'success' ? 'ti ti-circle-check text-success' : 'ti ti-alert-circle text-danger'} f-s-20`}></i>
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
