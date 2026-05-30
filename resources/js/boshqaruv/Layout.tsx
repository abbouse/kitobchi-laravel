import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';

const nav = [
  { group: 'Asosiy', items: [
    { to: '/boshqaruv', match: '/boshqaruv', label: 'Dashboard', icon: 'bi-speedometer2' },
    { to: '/boshqaruv/live', match: '/boshqaruv/live', label: 'Live Dashboard', icon: 'bi-broadcast', badge: 'LIVE' },
  ]},
  { group: 'Katalog', items: [
    { to: '/boshqaruv/products', match: '/boshqaruv/products', label: 'Mahsulotlar', icon: 'bi-box-seam' },
    { to: '/boshqaruv/books', match: '/boshqaruv/books', label: 'Kitoblar', icon: 'bi-book' },
    { to: '/boshqaruv/book-categories', match: '/boshqaruv/book-categories', label: 'Kitob kategoriyalari', icon: 'bi-bookmarks' },
    { to: '/boshqaruv/stationeries', match: '/boshqaruv/stationeries', label: 'Kanselyariya', icon: 'bi-pencil-square' },
    { to: '/boshqaruv/stationery-categories', match: '/boshqaruv/stationery-categories', label: 'Kanstovar kategoriyalari', icon: 'bi-tags' },
    { to: '/boshqaruv/authors', match: '/boshqaruv/authors', label: 'Mualliflar', icon: 'bi-person-vcard' },
    { to: '/boshqaruv/publishers', match: '/boshqaruv/publishers', label: 'Nashriyotlar', icon: 'bi-building' },
    { to: '/boshqaruv/parser', match: '/boshqaruv/parser', label: 'Parser / Import', icon: 'bi-cloud-download' },
  ]},
  { group: 'Buyurtmalar va Foydalanuvchilar', items: [
    { to: '/boshqaruv/orders', match: '/boshqaruv/orders', label: 'Buyurtmalar', icon: 'bi-receipt' },
    { to: '/boshqaruv/users', match: '/boshqaruv/users', label: 'Foydalanuvchilar', icon: 'bi-people' },
    { to: '/boshqaruv/search-history', match: '/boshqaruv/search-history', label: 'Qidiruv tarixi', icon: 'bi-clock-history' },
  ]},
  { group: 'Savdo va Logistika', items: [
    { to: '/boshqaruv/sellers', match: '/boshqaruv/sellers', label: 'Sotuvchilar', icon: 'bi-shop-window' },
    { to: '/boshqaruv/seller-orders', match: '/boshqaruv/seller-orders', label: 'Seller buyurtmalari', icon: 'bi-shop' },
    { to: '/boshqaruv/couriers', match: '/boshqaruv/couriers', label: 'Kuryerlar', icon: 'bi-bicycle' },
    { to: '/boshqaruv/courier-orders', match: '/boshqaruv/courier-orders', label: 'Kuryer buyurtmalari', icon: 'bi-truck' },
    { to: '/boshqaruv/hubs', match: '/boshqaruv/hubs', label: 'Hub fulfillment', icon: 'bi-building' },
    { to: '/boshqaruv/transactions', match: '/boshqaruv/transactions', label: 'Tranzaksiyalar', icon: 'bi-cash-coin' },
    { to: '/boshqaruv/logistika', match: '/boshqaruv/logistika', label: 'Logistika', icon: 'bi-geo-alt' },
  ]},
  { group: 'Marketing va Hamjamiyat', items: [
    { to: '/boshqaruv/reklamalar', match: '/boshqaruv/reklamalar', label: 'Reklamalar', icon: 'bi-megaphone' },
    { to: '/boshqaruv/promokodlar', match: '/boshqaruv/promokodlar', label: 'Promokodlar', icon: 'bi-ticket-perforated' },
    { to: '/boshqaruv/blogerlar', match: '/boshqaruv/blogerlar', label: 'Blogerlar', icon: 'bi-people' },
    { to: '/boshqaruv/gift-sertifikatlar', match: '/boshqaruv/gift-sertifikatlar', label: 'Gift sertifikatlar', icon: 'bi-gift' },
    { to: '/boshqaruv/market-news', match: '/boshqaruv/market-news', label: 'Market yangiliklari', icon: 'bi-newspaper' },
    { to: '/boshqaruv/reels', match: '/boshqaruv/reels', label: 'Reels / Shorts', icon: 'bi-camera-reels' },
    { to: '/boshqaruv/book-club', match: '/boshqaruv/book-club', label: 'Book Club', icon: 'bi-journal-bookmark' },
  ]},
  { group: 'Mijozlarga xizmat', items: [
    { to: '/boshqaruv/tickets', match: '/boshqaruv/tickets', label: 'Support', icon: 'bi-headset' },
    { to: '/boshqaruv/shikoyatlar', match: '/boshqaruv/shikoyatlar', label: 'Shikoyatlar', icon: 'bi-exclamation-triangle' },
    { to: '/boshqaruv/chat', match: '/boshqaruv/chat', label: 'Chat kuzatuv', icon: 'bi-chat-dots' },
    { to: '/boshqaruv/push', match: '/boshqaruv/push', label: 'Push bildirishnomalar', icon: 'bi-bell' },
  ]},
  { group: 'HR va Tashkilot', items: [
    { to: '/boshqaruv/vakansiyalar', match: '/boshqaruv/vakansiyalar', label: 'Vakansiyalar', icon: 'bi-person-badge' },
    { to: '/boshqaruv/karyera-arizalari', match: '/boshqaruv/karyera-arizalari', label: 'Karyera arizalari', icon: 'bi-file-earmark-person' },
    { to: '/boshqaruv/adminlar', match: '/boshqaruv/adminlar', label: 'Adminlar', icon: 'bi-shield-lock' },
  ]},
  { group: 'Premium', items: [
    { to: '/boshqaruv/mystery-box', match: '/boshqaruv/mystery-box', label: 'Mystery Box', icon: 'bi-box-seam' },
    { to: '/boshqaruv/sovgalar', match: '/boshqaruv/sovgalar', label: "Sovg'alar", icon: 'bi-gift-fill' },
  ]},
  { group: 'Tizim', items: [
    { to: '/boshqaruv/siyosatlar', match: '/boshqaruv/siyosatlar', label: 'Siyosatlar', icon: 'bi-file-earmark-text' },
    { to: '/boshqaruv/api-clients', match: '/boshqaruv/api-clients', label: 'API mijozlar', icon: 'bi-code-slash' },
    { to: '/boshqaruv/settings', match: '/boshqaruv/settings', label: 'Sozlamalar', icon: 'bi-gear' },
  ]},
];

export default function Layout({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useState(false);
  const { url, props } = usePage<{
    auth?: { admin?: { name?: string; email?: string; role?: string } };
    legacy?: { a122?: string | null };
  }>();
  const admin = props.auth?.admin;
  const legacyUrl = props.legacy?.a122;

  useEffect(() => { setOpen(false); }, [url]);

  const isActive = (match: string) => {
    if (match === '/boshqaruv') return url === '/boshqaruv' || url === '/boshqaruv/';
    return url.startsWith(match);
  };

  return (
    <div className="app-shell">
      <aside className={`sidebar ${open ? 'open' : ''}`}>
        <div className="brand">
          <div className="brand-logo"><i className="bi bi-book-half"></i></div>
          <div>
            <div className="brand-name">Kitobchi</div>
            <div className="brand-sub">Admin Panel</div>
          </div>
        </div>

        <nav className="nav-group">
          {nav.map((g) => (
            <div key={g.group}>
              <div className="nav-title">{g.group}</div>
              {g.items.map((it) => (
                <Link
                  key={it.to}
                  href={it.to}
                  className={'nav-item ' + (isActive(it.match) ? 'active' : '')}
                >
                  <i className={`bi ${it.icon}`}></i>
                  <span>{it.label}</span>
                  {it.badge && (
                    <span className={`badge rounded-pill ${it.badge === 'LIVE' ? 'bg-danger' : 'bg-light text-dark'}`}>
                      {it.badge}
                    </span>
                  )}
                </Link>
              ))}
            </div>
          ))}
        </nav>

        <div className="sidebar-footer">
          <div className="avatar">AS</div>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ color: 'white', fontWeight: 600, fontSize: 14 }}>{admin?.name || 'Admin'}</div>
            <div style={{ color: '#a5b4fc', fontSize: 12 }}>{admin?.role || 'Administrator'}</div>
          </div>
          <button className="btn btn-sm" style={{ color: '#c7d2fe' }} onClick={() => router.post('/boshqaruv/logout')}>
            <i className="bi bi-box-arrow-right" style={{ fontSize: 18 }}></i>
          </button>
        </div>
      </aside>

      <div className="main-wrap">
        <header className="topbar">
          <button className="icon-btn sidebar-toggle" onClick={() => setOpen(!open)}>
            <i className="bi bi-list" style={{ fontSize: 20 }}></i>
          </button>
          <div className="search d-none d-md-block">
            <i className="bi bi-search"></i>
            <input placeholder="Sahifa, buyurtma, foydalanuvchi qidirish..." />
          </div>
          <div style={{ flex: 1 }}></div>
          <button className="icon-btn"><i className="bi bi-moon"></i></button>
          <button className="icon-btn"><i className="bi bi-envelope"></i><span className="dot"></span></button>
          <button className="icon-btn"><i className="bi bi-bell"></i><span className="dot"></span></button>
          {legacyUrl && (
            <a href={legacyUrl} className="btn btn-primary-gradient btn-sm d-inline-flex align-items-center gap-1">
              <i className="bi bi-tools"></i>
              To'liq funksiyalar
            </a>
          )}
          <div className="d-flex align-items-center gap-2 ps-2 border-start">
            <div className="avatar" style={{ width: 36, height: 36, borderRadius: '50%', background: 'linear-gradient(135deg,#f472b6,#8b5cf6)', color: 'white', fontWeight: 700, display: 'grid', placeItems: 'center', fontSize: 13 }}>AS</div>
            <div className="d-none d-md-block">
              <div style={{ fontSize: 13, fontWeight: 600 }}>{admin?.name || 'Admin'}</div>
              <div style={{ fontSize: 11, color: '#6b7280' }}>{admin?.email || 'admin@kitobchi.uz'}</div>
            </div>
          </div>
        </header>
        <main className="content">{children}</main>
      </div>

      {open && <div className="d-block d-md-none" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,.4)', zIndex: 1029 }} onClick={() => setOpen(false)}></div>}
    </div>
  );
}
