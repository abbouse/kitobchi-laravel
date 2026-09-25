import { useState } from 'react';
import { router, Link } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(Math.round(n || 0));

interface KpiData {
  totalWishlistItems: number;
  totalWishesProducts: number;
  uniqueWishlistUsers: number;
  totalCartItems: number;
  totalCartUnits: number;
  topCartProductsCount: number;
  uniqueCartUsers: number;
  totalStockAlerts: number;
  stockAlertProducts: number;
  cartOutOfStockProducts: number;
  cartOutOfStockUnits: number;
  lostRevenueInCarts: number;
  wishlistOutOfStockProducts: number;
  unrealizedWishlistDemand: number;
}

interface DemandItem {
  id: number;
  name: string;
  author?: string;
  productType: string;
  productTypeLabel: string;
  price: number;
  discountPrice?: number | null;
  image?: string | null;
  artikul?: string | null;
  isbn?: string | null;
  availableStock: number;
  usersCount: number;
  requestedUnits?: number;
  potentialDemand?: number;
  potentialLostRevenue?: number;
  sellerId?: number;
  sellerName?: string;
  sellerPhone?: string;
  sellerRole?: string;
  urgency: 'high' | 'medium' | 'normal';
  actionLabel?: string;
}

interface PaginationMeta {
  page: number;
  totalPages: number;
  total: number;
  from: number;
  to: number;
}

interface TabOption {
  key: string;
  label: string;
  icon: string;
  badge: number;
}

interface Props {
  kpi: KpiData;
  items: DemandItem[];
  pagination: PaginationMeta;
  filters: {
    tab: string;
    type: string;
    search: string;
  };
  tabs: TabOption[];
}

export default function DemandAnalytics({
  kpi,
  items = [],
  pagination = { page: 1, totalPages: 1, total: 0, from: 0, to: 0 },
  filters = { tab: 'all_wishes', type: 'all', search: '' },
  tabs = [],
}: Props) {
  const [search, setSearch] = useState(filters.search || '');

  const applyFilters = (overrides: Record<string, string | number>) => {
    router.get(
      '/boshqaruv/demand-analytics',
      {
        tab: filters.tab,
        type: filters.type,
        search,
        page: 1,
        ...overrides,
      },
      { preserveState: true, preserveScroll: true }
    );
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters({ search });
  };

  return (
    <div className="container-fluid py-3">
      {/* ── Sarlavha & Breadcrumbs ── */}
      <div className="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
          <h4 className="mb-0 text-dark f-w-700">Mijozlar talabi va Xohishlar analitikasi</h4>
          <PageCrumbs />
        </div>
        <div className="d-flex align-items-center gap-2">
          <span className="badge text-light-primary border-0 p-2 f-s-12">
            <i className="ti ti-chart-bar me-1"></i>Jonli talab va zaxira tahlili
          </span>
          <button
            className="btn btn-sm btn-outline-secondary"
            onClick={() => router.reload({ preserveScroll: true })}
            title="Yangilash"
          >
            <i className="ti ti-rotate me-1"></i>Yangilash
          </button>
        </div>
      </div>

      {/* ── KPI Widgets (Axelit uslubida) ── */}
      <div className="row g-3 mb-4">
        {/* Widget 1: Savatdagi tugagan kitoblar */}
        <div className="col-sm-6 col-xl-3">
          <div className="card h-100 border-0 shadow-sm bg-surface">
            <div className="card-body">
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <span className="text-secondary f-s-12 f-w-600 text-uppercase">
                    Savatda bor · Omborda tugagan
                  </span>
                  <h3 className="mb-0 text-danger f-w-700 mt-2">
                    {kpi.cartOutOfStockProducts}{' '}
                    <span className="f-s-14 f-w-500 text-muted">ta kitob</span>
                  </h3>
                </div>
                <div className="h-50 w-50 d-flex-center b-r-50 bg-light-danger text-danger flex-shrink-0">
                  <i className="ti ti-shopping-cart-x f-s-24"></i>
                </div>
              </div>
              <div className="mt-3 pt-2 border-top d-flex justify-content-between f-s-12">
                <span className="text-muted">Kutilayotgan dona:</span>
                <span className="f-w-600 text-danger">{fmt(kpi.cartOutOfStockUnits)} dona</span>
              </div>
              <div className="d-flex justify-content-between f-s-12 mt-1">
                <span className="text-muted">Yo'qotilayotgan savdo:</span>
                <span className="f-w-700 text-danger">{fmt(kpi.lostRevenueInCarts)} so'm</span>
              </div>
            </div>
          </div>
        </div>

        {/* Widget 2: Sevimlilardagi tugagan kitoblar */}
        <div className="col-sm-6 col-xl-3">
          <div className="card h-100 border-0 shadow-sm bg-surface">
            <div className="card-body">
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <span className="text-secondary f-s-12 f-w-600 text-uppercase">
                    Xohishlarda bor · Omborda 0
                  </span>
                  <h3 className="mb-0 text-warning f-w-700 mt-2">
                    {kpi.wishlistOutOfStockProducts}{' '}
                    <span className="f-s-14 f-w-500 text-muted">ta kitob</span>
                  </h3>
                </div>
                <div className="h-50 w-50 d-flex-center b-r-50 bg-light-warning text-warning flex-shrink-0">
                  <i className="ti ti-ban f-s-24"></i>
                </div>
              </div>
              <div className="mt-3 pt-2 border-top d-flex justify-content-between f-s-12">
                <span className="text-muted">Potentsial talab hajmi:</span>
                <span className="f-w-700 text-warning">{fmt(kpi.unrealizedWishlistDemand)} so'm</span>
              </div>
              <div className="d-flex justify-content-between f-s-12 mt-1">
                <span className="text-muted">Tavsiya:</span>
                <span className="badge text-light-warning border-0">Zaxirani to'ldirish</span>
              </div>
            </div>
          </div>
        </div>

        {/* Widget 3: Qayta kelishini kutayotgan so'rovlar (Alertlar) */}
        <div className="col-sm-6 col-xl-3">
          <div className="card h-100 border-0 shadow-sm bg-surface">
            <div className="card-body">
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <span className="text-secondary f-s-12 f-w-600 text-uppercase">
                    Kutish so'rovlari (Alerts)
                  </span>
                  <h3 className="mb-0 text-info f-w-700 mt-2">
                    {kpi.totalStockAlerts}{' '}
                    <span className="f-s-14 f-w-500 text-muted">ta so'rov</span>
                  </h3>
                </div>
                <div className="h-50 w-50 d-flex-center b-r-50 bg-light-info text-info flex-shrink-0">
                  <i className="ti ti-bell-filled f-s-24"></i>
                </div>
              </div>
              <div className="mt-3 pt-2 border-top d-flex justify-content-between f-s-12">
                <span className="text-muted">Kutilayotgan kitoblar:</span>
                <span className="f-w-600 text-dark">{fmt(kpi.stockAlertProducts)} xil kitob</span>
              </div>
              <div className="d-flex justify-content-between f-s-12 mt-1">
                <span className="text-muted">Bildirishnoma:</span>
                <span className="badge text-light-info border-0">Avtomatik Push / SMS</span>
              </div>
            </div>
          </div>
        </div>

        {/* Widget 4: Xohishlar va Savatdagi umumiy talab */}
        <div className="col-sm-6 col-xl-3">
          <div className="card h-100 border-0 shadow-sm bg-surface">
            <div className="card-body">
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <span className="text-secondary f-s-12 f-w-600 text-uppercase">
                    Jami xohishlar (Wishlist)
                  </span>
                  <h3 className="mb-0 text-success f-w-700 mt-2">
                    {fmt(kpi.totalWishlistItems)}{' '}
                    <span className="f-s-14 f-w-500 text-muted">saqlangan</span>
                  </h3>
                </div>
                <div className="h-50 w-50 d-flex-center b-r-50 bg-light-success text-success flex-shrink-0">
                  <i className="ti ti-heart-filled f-s-24"></i>
                </div>
              </div>
              <div className="mt-3 pt-2 border-top d-flex justify-content-between f-s-12">
                <span className="text-muted">Xohish bildirgan mijozlar:</span>
                <span className="f-w-600 text-dark">{fmt(kpi.uniqueWishlistUsers)} nafar</span>
              </div>
              <div className="d-flex justify-content-between f-s-12 mt-1">
                <span className="text-muted">Savatdagi umumiy tovar:</span>
                <span className="f-w-600 text-success">{fmt(kpi.totalCartUnits)} dona</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ── Asosiy Tahlil Doskasi ── */}
      <div className="card border-0 shadow-sm">
        <div className="card-body">
          {/* Segment tabs — kc-segment (iOS/Axelit uslubi) */}
          <div className="nav kc-segment kc-segment-wrap mb-3" role="tablist" aria-label="Tahlil bo'limlari">
            {tabs.map((tab) => (
              <div key={tab.key} className="nav-item">
                <button
                  className={`nav-link${filters.tab === tab.key ? ' active' : ''}`}
                  onClick={() => applyFilters({ tab: tab.key })}
                >
                  <i className={`${tab.icon}`}></i>
                  {tab.label}
                  <span className="badge">{tab.badge}</span>
                </button>
              </div>
            ))}
          </div>

          {/* Filter Toolbar */}
          <form className="d-flex align-items-center gap-2 flex-wrap mb-3" onSubmit={handleSearchSubmit}>
            <div className="app-form app-icon-form position-relative" style={{ width: 'min(320px, 100%)' }}>
              <i className="ti ti-search"></i>
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="Kitob nomi, muallif, ISBN yoki do'kon..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </div>
            <select
              className="form-select form-select-sm"
              style={{ width: 'min(200px, 100%)' }}
              value={filters.type}
              onChange={(e) => applyFilters({ type: e.target.value })}
            >
              <option value="all">Barcha mahsulotlar</option>
              <option value="book">Faqat kitoblar</option>
              <option value="stationery">Kanselyariya mollari</option>
            </select>
            <button type="submit" className="btn btn-sm btn-light-secondary">
              <i className="ti ti-filter me-1"></i>Qidirish
            </button>
            {search && (
              <button
                type="button"
                className="btn btn-sm btn-light-secondary"
                onClick={() => { setSearch(''); applyFilters({ search: '' }); }}
              >
                Tozalash
              </button>
            )}
          </form>

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead>
                <tr>
                  <th className="ps-3 py-3 text-secondary f-s-12 text-uppercase">Kitob / Mahsulot</th>
                  <th className="py-3 text-secondary f-s-12 text-uppercase">Narxi</th>
                  <th className="py-3 text-secondary f-s-12 text-uppercase">Mavjud qoldiq</th>
                  <th className="py-3 text-secondary f-s-12 text-uppercase">
                    {filters.tab === 'stock_alerts'
                      ? "Kutayotganlar"
                      : filters.tab.includes('wishlist') || filters.tab === 'all_wishes'
                      ? "Xohish bildirganlar"
                      : "Savatdagi talab"}
                  </th>
                  <th className="py-3 text-secondary f-s-12 text-uppercase">
                    {filters.tab.includes('out_of_stock') ? "Yo'qotilayotgan savdo" : "Potentsial talab qiymati"}
                  </th>
                  <th className="py-3 text-secondary f-s-12 text-uppercase">Hamkor (Do'kon / Muallif)</th>
                  <th className="pe-3 py-3 text-end text-secondary f-s-12 text-uppercase">Tezkor harakat</th>
                </tr>
              </thead>
              <tbody>
                {items.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="text-center py-5">
                      <div className="d-flex flex-column align-items-center justify-content-center">
                        <div className="h-60 w-60 b-r-50 bg-light-primary text-primary d-flex-center mb-3">
                          <i className="ti ti-check f-s-30"></i>
                        </div>
                        <h6 className="f-w-600 text-dark mb-1">Ushbu parametrlar bo'yicha ma'lumot topilmadi</h6>
                        <p className="text-muted f-s-13 mb-0">
                          {filters.tab.includes('out_of_stock')
                            ? "Ajoyib! Omborda tugagan va mijozlar tomonidan kutilayotgan kitoblar yo'q."
                            : "Boshqa tab yoki qidiruv so'zini sinab ko'ring."}
                        </p>
                      </div>
                    </td>
                  </tr>
                ) : (
                  items.map((item) => {
                    const price = item.discountPrice || item.price;
                    const value = item.potentialLostRevenue || item.potentialDemand || 0;

                    return (
                      <tr key={`${item.productType}-${item.id}`}>
                        {/* Mahsulot ma'lumotlari */}
                        <td className="ps-3 py-3">
                          <div className="d-flex align-items-center gap-3">
                            {item.image ? (
                              <img
                                src={item.image}
                                alt={item.name}
                                className="h-50 w-40 b-r-8 object-fit-cover border flex-shrink-0"
                              />
                            ) : (
                              <div className="h-50 w-40 b-r-8 bg-light text-secondary d-flex-center flex-shrink-0">
                                <i className="ti ti-book f-s-20"></i>
                              </div>
                            )}
                            <div className="min-w-0">
                              <div
                                className="f-w-600 text-dark text-truncate"
                                style={{ maxWidth: '280px' }}
                                title={item.name}
                              >
                                {item.name}
                              </div>
                              <div className="text-muted f-s-12 text-truncate" style={{ maxWidth: '280px' }}>
                                {item.author || "Muallif ko'rsatilmagan"}
                              </div>
                              <div className="d-flex align-items-center gap-1 mt-1">
                                {item.isbn && (
                                  <span className="badge bg-light text-muted border-0 f-s-11 font-monospace">
                                    {item.isbn}
                                  </span>
                                )}
                                {item.artikul && (
                                  <span className="badge bg-light text-muted border-0 f-s-11">
                                    #{item.artikul}
                                  </span>
                                )}
                              </div>
                            </div>
                          </div>
                        </td>

                        {/* Narx */}
                        <td>
                          <div className="f-w-600 text-dark">{fmt(price)} so'm</div>
                          {item.discountPrice && (
                            <div className="text-decoration-line-through text-muted f-s-12">
                              {fmt(item.price)} so'm
                            </div>
                          )}
                        </td>

                        {/* Ombor holati */}
                        <td>
                          {item.availableStock <= 0 ? (
                            <span className="badge text-light-danger border-0 d-inline-flex align-items-center gap-1 px-2 py-1">
                              <i className="ti ti-alert-triangle f-s-13"></i> Omborda tugagan (0)
                            </span>
                          ) : item.availableStock <= 3 ? (
                            <span className="badge text-light-warning border-0 px-2 py-1">
                              Kam qolgan ({item.availableStock} ta)
                            </span>
                          ) : (
                            <span className="badge text-light-success border-0 px-2 py-1">
                              Mavjud ({item.availableStock} ta)
                            </span>
                          )}
                        </td>

                        {/* Mijozlar soni / Talab */}
                        <td>
                          <div className="d-flex align-items-center gap-2">
                            <span className="badge text-light-primary border-0 f-s-13 px-2 py-1">
                              <i className="ti ti-users me-1"></i>
                              {item.usersCount} nafar mijoz
                            </span>
                            {item.requestedUnits && item.requestedUnits > item.usersCount && (
                              <span className="badge bg-light text-secondary border-0 f-s-12">
                                {item.requestedUnits} dona
                              </span>
                            )}
                          </div>
                        </td>

                        {/* Potentsial summa */}
                        <td>
                          <div className={`f-w-700 ${item.availableStock <= 0 ? 'text-danger' : 'text-dark'}`}>
                            {fmt(value)} so'm
                          </div>
                          {item.availableStock <= 0 && (
                            <div className="text-muted f-s-11">Yo'qotilayotgan tushum</div>
                          )}
                        </td>

                        {/* Hamkor (Do'kon / Muallif) */}
                        <td>
                          <div>
                            <div className="f-w-600 text-dark f-s-13">
                              {item.sellerName || 'Kitobchi Platform'}
                            </div>
                            <div className="d-flex align-items-center gap-1 mt-1">
                              {item.sellerRole === 'author' ? (
                                <span className="badge text-light-info border-0 f-s-10">
                                  <i className="ti ti-pencil me-1"></i>Muallif
                                </span>
                              ) : (
                                <span className="badge text-light-secondary border-0 f-s-10">
                                  <i className="ti ti-building-store me-1"></i>Do'kon
                                </span>
                              )}
                              {item.sellerPhone && (
                                <a
                                  href={`tel:${item.sellerPhone}`}
                                  className="text-muted f-s-11 ms-1 text-decoration-none"
                                  title="Qo'ng'iroq qilish"
                                >
                                  <i className="ti ti-phone me-1"></i>{item.sellerPhone}
                                </a>
                              )}
                            </div>
                          </div>
                        </td>

                        {/* Amallar */}
                        <td className="pe-3 text-end">
                          <div className="d-flex align-items-center justify-content-end gap-1">
                            <Link
                              href={`/boshqaruv/books?search=${encodeURIComponent(item.isbn || item.name)}`}
                              className="btn btn-sm btn-outline-primary"
                              title="Kitob ma'lumotlarini ko'rish va zaxirani boshqarish"
                            >
                              <i className="ti ti-package me-1"></i>
                              {item.availableStock <= 0 ? "Zaxirani to'ldirish" : "Katalogda ko'rish"}
                            </Link>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Pagination */}
        {pagination.totalPages > 1 && (
          <div className="card-footer bg-transparent border-top py-3">
            <PaginationControls
              page={pagination.page}
              totalPages={pagination.totalPages}
              total={pagination.total}
              from={pagination.from}
              to={pagination.to}
              onPageChange={(p) => applyFilters({ page: p })}
            />
          </div>
        )}
      </div>
    </div>
  );
}
