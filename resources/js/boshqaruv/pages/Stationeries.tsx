import { router, usePage } from '@inertiajs/react';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface StatItem {
  id: number;
  name: string;
  category: string;
  seller?: string | null;
  price: number;
  discountPrice?: number | null;
  stock: number;
  sold: number;
  revenue?: number;
  views?: number;
  status?: number;
  active?: boolean;
  hidden?: boolean;
  recommended?: boolean;
  icon?: string | null;
  createUrl?: string;
  showUrl?: string;
  editUrl?: string;
  moderateUrl?: string;
}

const statusLabel = (status?: number) => {
  if (status === 1) return ['Tasdiqlangan', 'chip-success'];
  if (status === 2) return ['Rad etilgan', 'chip-danger'];
  return ['Moderatsiya', 'chip-warning'];
};

export default function Stationeries() {
  const { stationeries = [] } = usePage<{ stationeries?: StatItem[] }>().props;
  const createUrl = '/boshqaruv/stationeries';
  const moderate = (item: StatItem, status: 0 | 1 | 2) => {
    if (!item.moderateUrl) return;
    router.patch(item.moderateUrl, { is_approved: status }, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Kanselyariya mahsulotlari</h1>
          <p className="page-subtitle">Jami {stationeries.length} ta mahsulot</p>
        </div>
      </div>

      <div className="row g-3">
        {stationeries.map((item) => {
          const [label, chip] = statusLabel(item.status);
          return (
            <div className="col-xl-4 col-md-6" key={item.id}>
              <div className="card-panel h-100 d-flex flex-column justify-content-between">
                <div>
                  <div className="d-flex align-items-center gap-3 mb-3">
                    <div className="book-cover-sm" style={{ width: 72, height: 72 }}>
                      {item.icon ? <img src={item.icon} alt={item.name} /> : <i className="bi bi-pencil-square"></i>}
                    </div>
                    <div style={{ flex: 1, minWidth: 0 }}>
                      <div className="fw-bold text-truncate">{item.name}</div>
                      <div className="d-flex gap-1 mt-1 flex-wrap">
                        <span className="chip chip-info">{item.category}</span>
                        <span className={`chip ${chip}`}>{label}</span>
                        {item.hidden ? <span className="chip chip-danger">Yashirin</span> : null}
                      </div>
                    </div>
                    <div className="text-end">
                      <div className="fw-bold" style={{ color: '#10b981' }}>{fmt(item.price)} so'm</div>
                      {item.discountPrice ? <small className="text-danger">{fmt(item.discountPrice)} so'm</small> : <small className="text-muted">Narx</small>}
                    </div>
                  </div>
                  <div className="row text-center g-2 pt-3 border-top">
                    <div className="col"><div className="fw-bold">{item.stock}</div><small className="text-muted">Omborda</small></div>
                    <div className="col"><div className="fw-bold text-success">{item.sold}</div><small className="text-muted">Sotilgan</small></div>
                    <div className="col"><div className="fw-bold">{fmt(item.revenue || 0)}</div><small className="text-muted">Daromad</small></div>
                  </div>
                  <div className="text-muted small mt-2">{item.seller || 'Ichki katalog'} · {fmt(item.views || 0)} ko'rish</div>
                </div>
                <div className="d-flex gap-2 mt-3 pt-2">
                  {item.moderateUrl ? <button className="btn btn-sm btn-light flex-fill" onClick={() => moderate(item, item.status === 1 ? 0 : 1)}><i className="bi bi-shield-check"></i></button> : null}
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
