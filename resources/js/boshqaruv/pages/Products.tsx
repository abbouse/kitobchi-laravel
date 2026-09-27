import { useMemo, useState } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';
import { EmptyState, MiniStat, StatWidget } from '../components/Axelit';
import { tiIcon } from '../utils/icons';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Product {
  id: string;
  rawId: number;
  title: string;
  type: string;
  category?: string;
  seller?: string;
  price: number;
  stock: number;
  sold: number;
  revenue?: number;
  status: string;
  approved?: boolean;
  image?: string | null;
  showUrl?: string;
  editUrl?: string;
  moderateUrl?: string;
}

export default function Products() {
  const { products = [] } = usePage<{ products?: Product[] }>().props;
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState('Barchasi');
  const [stockFilter, setStockFilter] = useState('Barchasi');
  const [selected, setSelected] = useState<Product | null>(null);

  const filtered = products.filter((product) => {
    const haystack = `${product.title} ${product.category || ''} ${product.seller || ''}`.toLowerCase();
    const matchSearch = haystack.includes(search.toLowerCase());
    const matchType = typeFilter === 'Barchasi' || product.type === typeFilter;
    const matchStock = stockFilter === 'Barchasi'
      || (stockFilter === 'Omborda' && product.stock > 0)
      || (stockFilter === 'Kam qolgan' && product.stock > 0 && product.stock < 15)
      || (stockFilter === 'Tugagan' && product.stock === 0);
    return matchSearch && matchType && matchStock;
  });

  const types = useMemo(() => ['Barchasi', ...Array.from(new Set(products.map((product) => product.type)))], [products]);
  const pagination = useClientPagination(filtered, 30);
  const totalStock = products.reduce((sum, product) => sum + product.stock, 0);
  const lowStock = products.filter((product) => product.stock > 0 && product.stock < 15).length;
  const pendingModeration = products.filter((product) => !product.approved).length;

  const moderate = (product: Product, approved: boolean) => {
    if (!product.moderateUrl) return;
    router.patch(product.moderateUrl, { is_approved: approved ? 1 : 0 }, { preserveScroll: true });
  };

  return (
    <div>
      {/* ── Page Header ── */}
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h4 className="main-title mb-0">Barcha mahsulotlar</h4>
            <span className="badge bg-light-primary text-primary f-s-12 b-r-8">All Inventory</span>
          </div>
          <PageCrumbs />
          <p className="mb-0 text-secondary f-s-13">Kitob, kanselyariya va sovg'alar umumiy tovar katalogi</p>
        </div>
        <div className="d-flex gap-2 align-items-center flex-wrap">
          <a className="btn btn-sm btn-outline-secondary" href="/boshqaruv/books">
            <i className="ti ti-book me-1"></i>Kitoblar bo'limi
          </a>
          <a className="btn btn-sm btn-primary" href="/boshqaruv/stationeries">
            <i className="ti ti-plus me-1"></i>Kanselyariya qo'shish
          </a>
        </div>
      </div>

      {/* ── Top KPI StatWidgets ── */}
      <div className="row">
        {[
          { label: 'Jami tovarlar', value: products.length, sub: 'Barcha turlar bo\'yicha' },
          { label: 'Jami ombor qoldig\'i', value: fmt(totalStock), sub: 'Dona tovar mavjud' },
          { label: 'Kam qolgan tovarlar', value: lowStock, sub: '15 donadan kam' },
          { label: 'Moderatsiyada', value: pendingModeration, sub: 'Tasdiqlash kutilmoqda' },
        ].map((item, kpiIndex) => (
          <div className="col-xl-3 col-md-6" key={item.label}>
            <StatWidget index={kpiIndex} label={item.label} value={item.value} sub={item.sub} />
          </div>
        ))}
      </div>

      {/* ── Control Bar and Table Card ── */}
      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
          <div>
            <h5 className="f-w-600 mb-0">Mahsulotlar katalogi</h5>
            <p className="mb-0 text-secondary f-s-13">{filtered.length} ta tovar filtrlangan</p>
          </div>

          <div className="d-flex align-items-center gap-2 flex-wrap">
            {/* Search Input */}
            <div className="position-relative" style={{ minWidth: 260 }}>
              <input
                type="search"
                className="form-control form-control-sm pe-4"
                placeholder="Nomi, kategoriya, seller..."
                value={search}
                onChange={e => setSearch(e.target.value)}
              />
              <i className="ti ti-search position-absolute top-50 end-0 translate-middle-y me-2 text-secondary f-s-12"></i>
            </div>

            {/* Type Filter */}
            <select
              className="form-select form-select-sm"
              style={{ width: 'auto' }}
              value={typeFilter}
              onChange={e => setTypeFilter(e.target.value)}
            >
              {types.map((type) => <option key={type} value={type}>{type}</option>)}
            </select>

            {/* Stock Filter */}
            <select
              className="form-select form-select-sm"
              style={{ width: 'auto' }}
              value={stockFilter}
              onChange={e => setStockFilter(e.target.value)}
            >
              <option value="Barchasi">Barcha qoldiqlar</option>
              <option value="Omborda">Omborda mavjud</option>
              <option value="Kam qolgan">Kam qolgan (&lt;15)</option>
              <option value="Tugagan">Tugagan (0)</option>
            </select>
          </div>
        </div>

        <div className="card-body">
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead>
                <tr>
                  <th style={{ width: 60 }}>Rasm</th>
                  <th>Mahsulot</th>
                  <th>Turi va Kategoriya</th>
                  <th>Sotuvchi (Seller)</th>
                  <th>Narxi</th>
                  <th>Ombor</th>
                  <th>Sotilgan</th>
                  <th>Holat</th>
                  <th className="text-end" style={{ width: 100 }}>Amallar</th>
                </tr>
              </thead>
              <tbody>
                {pagination.paginated.map((product) => (
                  <tr key={product.id}>
                    <td>
                      <div
                        className="b-r-8 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 cursor-pointer shadow-sm"
                        style={{ width: 48, height: 60, border: '1px solid rgba(0,0,0,0.06)' }}
                        onClick={() => setSelected(product)}
                      >
                        {product.image ? (
                          <img className="w-100 h-100 object-fit-cover" src={product.image} alt={product.title} loading="lazy" />
                        ) : (
                          <i className="ti ti-package text-primary f-s-20"></i>
                        )}
                      </div>
                    </td>

                    <td>
                      <div
                        className="f-w-600 text-dark d-block text-truncate cursor-pointer hover-primary"
                        style={{ maxWidth: 280 }}
                        onClick={() => setSelected(product)}
                        title={product.title}
                      >
                        {product.title}
                      </div>
                      <small className="text-secondary font-monospace d-block mt-1">#{product.rawId}</small>
                    </td>

                    <td>
                      <span className={`badge ${
                        product.type === 'Kitob'
                          ? 'bg-light-primary text-primary'
                          : product.type === 'Kanselyariya'
                          ? 'bg-light-info text-info'
                          : 'bg-light-warning text-warning'
                      } b-r-6 f-s-11`}>
                        {product.type}
                      </span>
                      {product.category ? (
                        <small className="d-block text-secondary f-s-12 mt-1">{product.category}</small>
                      ) : null}
                    </td>

                    <td>
                      <div className="d-flex align-items-center gap-2">
                        <div
                          className="b-r-22 d-flex-center bg-light-secondary text-dark f-w-600 f-s-11 flex-shrink-0"
                          style={{ width: 28, height: 28 }}
                        >
                          {(product.seller || 'S').charAt(0).toUpperCase()}
                        </div>
                        <span className="text-dark f-s-13 f-w-500 text-truncate" style={{ maxWidth: 140 }}>
                          {product.seller || 'Ichki katalog'}
                        </span>
                      </div>
                    </td>

                    <td>
                      <strong className="text-dark f-s-14">{fmt(product.price)} so'm</strong>
                    </td>

                    <td>
                      {product.stock > 15 ? (
                        <span className="badge bg-light-success text-success b-r-6 f-s-11">
                          <i className="ti ti-check me-1"></i>{product.stock} dona
                        </span>
                      ) : product.stock > 0 ? (
                        <span className="badge bg-light-warning text-warning b-r-6 f-s-11">
                          <i className="ti ti-alert-triangle me-1"></i>{product.stock} dona
                        </span>
                      ) : (
                        <span className="badge bg-light-danger text-danger b-r-6 f-s-11">
                          <i className="ti ti-x me-1"></i>Tugagan
                        </span>
                      )}
                    </td>

                    <td>
                      <span className="f-w-600 text-dark f-s-13">{fmt(product.sold)}</span>
                    </td>

                    <td>
                      <span className={`badge ${product.approved ? 'bg-light-success text-success' : 'bg-light-warning text-warning'} b-r-6 f-s-11`}>
                        {product.approved ? (product.status || 'Tasdiqlangan') : 'Moderatsiya'}
                      </span>
                    </td>

                    <td className="text-end">
                      <div className="d-flex gap-1 justify-content-end">
                        <button
                          className="btn btn-light-primary icon-btn w-32 h-32 b-r-22"
                          onClick={() => setSelected(product)}
                          title="Tafsilotlar"
                        >
                          <i className="ti ti-eye"></i>
                        </button>
                        {product.moderateUrl ? (
                          <button
                            className={`btn ${product.approved ? 'btn-light-secondary' : 'btn-light-success'} icon-btn w-32 h-32 b-r-22`}
                            onClick={() => moderate(product, !product.approved)}
                            title={product.approved ? "Moderatsiyaga qaytarish" : "Tasdiqlash"}
                          >
                            <i className={`ti ${product.approved ? 'ti-rotate' : 'ti-check'}`}></i>
                          </button>
                        ) : null}
                      </div>
                    </td>
                  </tr>
                ))}

                {!pagination.paginated.length ? (
                  <tr>
                    <td colSpan={9} className="py-5">
                      <EmptyState text="Filtrga mos mahsulot topilmadi" />
                    </td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>

          <PaginationControls {...pagination} onPageChange={pagination.setPage} />
        </div>
      </div>

      {/* ── Product Quick Modal ── */}
      <Modal show={!!selected} onHide={() => setSelected(null)} centered size="lg">
        <Modal.Header closeButton>
          <div className="d-flex align-items-center gap-2">
            <Modal.Title className="f-s-18 f-w-700 mb-0">{selected?.title}</Modal.Title>
            <span className="badge bg-light-secondary text-dark font-monospace">#{selected?.rawId}</span>
          </div>
        </Modal.Header>
        <Modal.Body className="p-4">
          {selected ? (
            <div>
              <div className="d-flex gap-3 align-items-center mb-4 bg-light-primary p-3 b-r-12">
                <div
                  className="b-r-8 overflow-hidden bg-white shadow-sm flex-shrink-0 d-flex-center"
                  style={{ width: 80, height: 100, border: '1px solid rgba(0,0,0,0.08)' }}
                >
                  {selected.image ? (
                    <img className="w-100 h-100 object-fit-cover" src={selected.image} alt={selected.title} />
                  ) : (
                    <i className="ti ti-package text-primary f-s-30"></i>
                  )}
                </div>
                <div>
                  <h6 className="f-w-700 text-dark mb-1">{selected.title}</h6>
                  <p className="text-secondary f-s-13 mb-2">
                    Turi: <b className="text-dark">{selected.type}</b> · Kategoriya: <b className="text-dark">{selected.category || '—'}</b>
                  </p>
                  <span className={`badge ${selected.approved ? 'bg-light-success text-success' : 'bg-light-warning text-warning'} b-r-6`}>
                    {selected.approved ? (selected.status || 'Tasdiqlangan') : 'Moderatsiyada'}
                  </span>
                </div>
              </div>

              <div className="row g-3">
                <div className="col-sm-6">
                  <MiniStat icon={tiIcon('ti-cash')} label="Narx" value={`${fmt(selected.price)} so'm`} />
                </div>
                <div className="col-sm-6">
                  <MiniStat icon={tiIcon('ti-package')} label="Ombor qoldig'i" value={`${selected.stock} dona`} />
                </div>
                <div className="col-sm-6">
                  <MiniStat icon={tiIcon('ti-shopping-bag')} label="Sotilgan soni" value={`${selected.sold} dona`} />
                </div>
                <div className="col-sm-6">
                  <MiniStat icon={tiIcon('ti-trending-up')} label="Taxminiy daromad" value={`${fmt(selected.revenue || 0)} so'm`} />
                </div>
              </div>
            </div>
          ) : null}
        </Modal.Body>
        <Modal.Footer className="justify-content-between">
          <div>
            {selected?.moderateUrl ? (
              <Button
                variant={selected.approved ? 'outline-warning' : 'primary'}
                className={selected.approved ? '' : 'btn-primary'}
                onClick={() => selected && moderate(selected, !selected.approved)}
              >
                {selected.approved ? 'Moderatsiyaga qaytarish' : 'Tasdiqlash'}
              </Button>
            ) : null}
          </div>
          <Button variant="light-secondary" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

