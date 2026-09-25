import { useMemo, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import PaginationControls from '../components/PaginationControls';
import {
  Seller, Counts, fmt, badgeClass, sellerLabel,
} from '../components/SellerCommon';
import { StatWidget } from '../components/Axelit';
import { tiIcon } from '../utils/icons';
import { Avatar as PAvatar } from '../components/Profile';

const sellerTabs = [
  { key: 'pending', label: 'Kutilmoqda', icon: 'ti-hourglass' },
  { key: 'approved', label: 'Faol', icon: 'ti-building-store' },
  { key: 'rejected', label: 'Bekor qilingan', icon: 'ti-octagon-off' },
  { key: 'blocked', label: 'Bloklangan', icon: 'ti-shield-lock' },
  { key: 'all', label: 'Barchasi', icon: 'ti-layout-grid' },
];

export default function Sellers() {
  const {
    sellers = [],
    sellerCounts = {},
    partnerRoleCounts = { all: 0, seller: 0, author: 0 },
    sellerPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    sellerFilters = {},
  } = usePage<{
    sellers?: (Seller & { businessRole?: string; businessRoleLabel?: string; isAuthor?: boolean })[];
    sellerCounts?: Counts;
    partnerRoleCounts?: { all: number; seller: number; author: number };
    sellerPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    sellerFilters?: { tab?: string; search?: string; role?: string };
  }>().props;

  const [sellerTab, setSellerTab] = useState(sellerFilters.tab || 'pending');
  const [roleFilter, setRoleFilter] = useState(sellerFilters.role || 'all');
  const [sellerSearch, setSellerSearch] = useState(sellerFilters.search || '');

  const totalBalance = useMemo(
    () => sellers.reduce((sum, seller) => sum + (seller.balance || 0), 0),
    [sellers]
  );

  const load = (extra: Record<string, string | number> = {}) =>
    router.get(
      '/boshqaruv/sellers',
      {
        sellers_page: sellerPagination.page,
        sellers_tab: sellerTab,
        sellers_role: roleFilter,
        sellers_search: sellerSearch,
        ...extra,
      },
      { preserveState: true, preserveScroll: true, replace: true }
    );

  const runPatch = (url?: string, message?: string, payload: Record<string, string> = {}) => {
    if (!url || (message && !confirm(message))) return;
    router.patch(url, payload, { preserveScroll: true });
  };

  const warnSeller = (seller: Seller) => {
    const title = prompt('Ogohlantirish sarlavhasi', 'Admin ogohlantirishi');
    if (!title) return;
    const message = prompt('Ogohlantirish matni', 'Iltimos, marketplace qoidalariga amal qiling.');
    if (!message) return;
    router.post(seller.actions?.warnUrl || '', { title, message }, { preserveScroll: true });
  };

  const resetPassword = (seller: Seller) => {
    if (!seller.actions?.resetPasswordUrl || !confirm(`${seller.name} uchun yangi parol SMS orqali yuborilsinmi?`)) return;
    router.post(seller.actions.resetPasswordUrl, {}, { preserveScroll: true });
  };

  return (
    <div className="container-fluid py-3">
      {/* ── Sarlavha & Breadcrumbs ── */}
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-3">
        <div>
          <h4 className="main-title mb-0">Sotuvchilar va Mualliflar</h4>
          <PageCrumbs />
          <p className="mb-0 text-secondary">
            Marketpleys biznes hamkorlari: kitob do'konlari, kantselyariya yetkazib beruvchilar va muallif akkauntlari
          </p>
        </div>
        <div className="d-flex align-items-center gap-2">
          <button
            className="btn btn-sm btn-outline-secondary"
            onClick={() => router.reload({ preserveScroll: true })}
          >
            <i className="ti ti-rotate me-1"></i>Yangilash
          </button>
        </div>
      </div>

      {/* ── KPI Widgets ── */}
      <div className="row g-3 mb-4">
        {[
          { label: 'Kutilayotgan arizalar', value: sellerCounts.pending || 0, icon: 'ti-hourglass' },
          { label: 'Faol hamkorlar', value: sellerCounts.approved || 0, icon: 'ti-building-store' },
          { label: "Do'konlar soni", value: partnerRoleCounts.seller || 0, icon: 'ti-building' },
          { label: 'Muallif akkauntlari', value: partnerRoleCounts.author || 0, icon: 'ti-pencil' },
          { label: "To'lanadigan balans", value: `${fmt(totalBalance)} so'm`, icon: 'ti-wallet' },
        ].map((item, kpiIndex) => (
          <div className="col-xl col-md-4 col-sm-6" key={item.label}>
            <StatWidget index={kpiIndex} label={item.label} value={item.value} />
          </div>
        ))}
      </div>

      {/* ── Jadval Card ── */}
      <div className="card border-0 shadow-sm">
        <div className="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between gap-3 flex-wrap py-3">
          <div>
            <h5 className="f-w-600 mb-0">Hamkorlar ro'yxati</h5>
            <p className="mb-0 text-secondary f-s-13">Jami {sellerPagination.total} ta hamkor topildi</p>
          </div>

          <div className="d-flex flex-wrap align-items-center gap-2">
            {/* Hamkor turi filtri (Do'kon vs Muallif) */}
            <div className="btn-group btn-group-sm" role="group">
              <button
                type="button"
                className={`btn ${roleFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary'}`}
                onClick={() => {
                  setRoleFilter('all');
                  load({ sellers_page: 1, sellers_role: 'all' });
                }}
              >
                Barchasi ({partnerRoleCounts.all})
              </button>
              <button
                type="button"
                className={`btn ${roleFilter === 'seller' ? 'btn-primary' : 'btn-outline-secondary'}`}
                onClick={() => {
                  setRoleFilter('seller');
                  load({ sellers_page: 1, sellers_role: 'seller' });
                }}
              >
                <i className="ti ti-building-store me-1"></i>Do'konlar ({partnerRoleCounts.seller})
              </button>
              <button
                type="button"
                className={`btn ${roleFilter === 'author' ? 'btn-primary' : 'btn-outline-secondary'}`}
                onClick={() => {
                  setRoleFilter('author');
                  load({ sellers_page: 1, sellers_role: 'author' });
                }}
              >
                <i className="ti ti-pencil me-1"></i>Mualliflar ({partnerRoleCounts.author})
              </button>
            </div>

            {/* Qidiruv */}
            <input
              className="form-control form-control-sm"
              style={{ maxWidth: 260 }}
              value={sellerSearch}
              onChange={(e) => setSellerSearch(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && load({ sellers_page: 1 })}
              placeholder="Do'kon/muallif, tel yoki hudud..."
            />
          </div>
        </div>

        <div className="card-body">
          {/* Status Tabs */}
          <div className="nav kc-segment mb-3">
            {sellerTabs.map((item) => (
              <div key={item.key} className="nav-item">
                <button
                  className={`nav-link ${sellerTab === item.key ? 'active' : ''}`}
                  onClick={() => {
                    setSellerTab(item.key);
                    load({ sellers_page: 1, sellers_tab: item.key });
                  }}
                >
                  <i className={`${tiIcon(item.icon)} me-1`}></i>
                  {item.label}
                  <span className="badge text-light-secondary ms-2">{fmt(sellerCounts[item.key] || 0)}</span>
                </button>
              </div>
            ))}
          </div>

          {/* Table */}
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0">
              <thead className="bg-light">
                <tr>
                  <th className="ps-3">ID</th>
                  <th>Hamkor (Do'kon / Muallif)</th>
                  <th>Turi</th>
                  <th>Telefon</th>
                  <th>Viloyat</th>
                  <th className="text-end">Balans</th>
                  <th className="text-end">Mahsulotlar</th>
                  <th className="text-end">Buyurtmalar</th>
                  <th className="text-end">Karma / Reyting</th>
                  <th>Holat</th>
                  <th className="pe-3 text-end">Amallar</th>
                </tr>
              </thead>
              <tbody>
                {sellers.length === 0 ? (
                  <tr>
                    <td colSpan={11} className="text-center py-5 text-muted">
                      Hamkorlar topilmadi.
                    </td>
                  </tr>
                ) : (
                  sellers.map((seller) => (
                    <tr key={seller.id}>
                      <td className="ps-3 f-w-600 text-nowrap">#{seller.id}</td>
                      <td>
                        <Link
                          href={seller.actions?.detailUrl || `/boshqaruv/sellers/${seller.id}`}
                          className="d-flex align-items-center gap-2 text-reset text-decoration-none"
                        >
                          <PAvatar src={seller.photo} name={seller.name} size="md" />
                          <div className="min-w-0">
                            <div className="f-w-600 text-truncate text-primary">{seller.name}</div>
                            <small className="text-muted text-truncate d-block">
                              {seller.ownerName || seller.legalName || '—'}
                            </small>
                          </div>
                        </Link>
                      </td>
                      <td>
                        {seller.businessRole === 'author' || seller.isAuthor ? (
                          <span className="badge text-light-info border-0 px-2 py-1">
                            <i className="ti ti-pencil me-1"></i>Muallif
                          </span>
                        ) : (
                          <span className="badge text-light-primary border-0 px-2 py-1">
                            <i className="ti ti-building-store me-1"></i>Do'kon
                          </span>
                        )}
                      </td>
                      <td>{seller.phone || '—'}</td>
                      <td>{seller.region || '—'}</td>
                      <td className="text-end f-w-600 text-dark">
                        {fmt(seller.balance || 0)} so'm
                      </td>
                      <td className="text-end">{fmt(seller.products || 0)} ta</td>
                      <td className="text-end">{fmt(seller.orders || 0)} ta</td>
                      <td className="text-end">
                        <span className="badge text-light-warning border-0">
                          ★ {(seller.rating || 0).toFixed(1)}
                        </span>
                      </td>
                      <td>
                        <span className={`badge border-0 ${badgeClass(seller.status)}`}>
                          {sellerLabel(seller.status)}
                        </span>
                      </td>
                      <td className="pe-3 text-end">
                        <div className="d-inline-flex gap-1">
                          <Link
                            href={seller.actions?.detailUrl || `/boshqaruv/sellers/${seller.id}`}
                            className="btn btn-xs btn-outline-primary"
                            title="Batafsil"
                          >
                            <i className="ti ti-eye"></i>
                          </Link>
                          <Link
                            href={seller.actions?.editUrl || `/boshqaruv/sellers/${seller.id}/edit`}
                            className="btn btn-xs btn-outline-secondary"
                            title="Tahrirlash"
                          >
                            <i className="ti ti-edit"></i>
                          </Link>

                          {seller.status === 'pending' && (
                            <button
                              className="btn btn-xs btn-success"
                              onClick={() => runPatch(seller.actions?.approveUrl, `${seller.name} tasdiqlansinmi?`)}
                              title="Tasdiqlash"
                            >
                              <i className="ti ti-check"></i>
                            </button>
                          )}

                          {seller.status === 'blocked' && (
                            <button
                              className="btn btn-xs btn-info"
                              onClick={() => runPatch(seller.actions?.unblockUrl, `${seller.name} blokdan chiqarilsinmi?`)}
                              title="Blokdan chiqarish"
                            >
                              <i className="ti ti-lock-open"></i>
                            </button>
                          )}

                          {seller.status !== 'blocked' && (
                            <button
                              className="btn btn-xs btn-outline-danger"
                              onClick={() => runPatch(seller.actions?.rejectUrl, `${seller.name} bloklansinmi?`)}
                              title="Bloklash / Rad etish"
                            >
                              <i className="ti ti-lock"></i>
                            </button>
                          )}

                          <button
                            className="btn btn-xs btn-outline-warning"
                            onClick={() => warnSeller(seller)}
                            title="Ogohlantirish"
                          >
                            <i className="ti ti-alert-triangle"></i>
                          </button>

                          <button
                            className="btn btn-xs btn-outline-dark"
                            onClick={() => resetPassword(seller)}
                            title="Parolni tiklash"
                          >
                            <i className="ti ti-key"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {sellerPagination.totalPages > 1 && (
          <div className="card-footer bg-transparent border-top py-3">
            <PaginationControls
              page={sellerPagination.page}
              totalPages={sellerPagination.totalPages}
              total={sellerPagination.total}
              from={sellerPagination.from}
              to={sellerPagination.to}
              onPageChange={(p) => load({ sellers_page: p })}
            />
          </div>
        )}
      </div>
    </div>
  );
}
