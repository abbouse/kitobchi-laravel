import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button, Modal } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);
interface Variant { id: number; name: string; stock: number; price?: number; image?: string | null }
interface StatItem {
  id: number; name: string; category: string; seller?: string | null; price: number; discountPrice?: number | null;
  discountPercent?: number; discountExpiresAt?: string; stock: number; variantStock?: number; sold: number; clients?: number;
  revenue?: number; views?: number; status?: number; active?: boolean; hidden?: boolean; recommended?: boolean;
  recommendedExpiresAt?: string; icon?: string | null; images?: string[]; barcode?: string; material?: string;
  description?: string; createdAt?: string; updatedAt?: string; variants?: Variant[]; moderateUrl?: string;
}
const statusLabel = (status?: number) => status === 1 ? ['Tasdiqlangan', 'chip-success'] : status === 2 ? ['Rad etilgan', 'chip-danger'] : ['Moderatsiya', 'chip-warning'];

export default function Stationeries() {
  const { stationeries = [], stationeryCounts = {}, stationeryPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, stationeryFilters = {} } = usePage<{ stationeries?: StatItem[]; stationeryCounts?: Record<string, number>; stationeryPagination?: { page: number; totalPages: number; from: number; to: number; total: number }; stationeryFilters?: { tab?: string; search?: string } }>().props;
  const [tab, setTab] = useState(stationeryFilters.tab || 'pending');
  const [search, setSearch] = useState(stationeryFilters.search || '');
  const [selected, setSelected] = useState<StatItem | null>(null);
  const loadItems = (page = 1, activeTab = tab, term = search) => router.get('/boshqaruv/stationeries', { stationeries_page: page, stationeries_tab: activeTab, stationeries_search: term }, { preserveState: true, preserveScroll: true, replace: true });
  const moderate = (item: StatItem, status: 0 | 1 | 2) => item.moderateUrl && router.patch(item.moderateUrl, { is_approved: status }, { preserveScroll: true });

  return <div>
    <div className="page-head"><div><h1 className="page-title">Kanselyariya mahsulotlari</h1><p className="page-subtitle">Moderatsiya, ombor, variantlar va katalog nazorati</p></div></div>
    <div className="card-panel">
      <div className="panel-head"><div><div className="panel-title">Mahsulotlar</div><small className="text-muted">{stationeryPagination.total} ta mahsulot topildi</small></div><form className="d-flex gap-2" onSubmit={(e) => { e.preventDefault(); loadItems(); }}><input className="form-control form-control-sm" style={{ maxWidth: 300 }} value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nomi, kategoriya, barcode yoki seller" /><button className="btn btn-sm btn-outline-secondary"><i className="bi bi-search"></i></button></form></div>
      <div className="d-flex flex-wrap gap-2 mb-3">{[['pending', 'Moderatsiya'], ['active', 'Tasdiqlangan'], ['rejected', 'Rad etilgan'], ['all', 'Barchasi']].map(([key, label]) => <button className={`btn btn-sm ${tab === key ? 'btn-primary-gradient' : 'btn-light'}`} key={key} onClick={() => { setTab(key); loadItems(1, key); }}>{label}<span className="badge rounded-pill bg-light text-dark ms-2">{stationeryCounts[key] || 0}</span></button>)}</div>
      <div className="table-responsive"><table className="data-table"><thead><tr><th></th><th>Mahsulot</th><th>Seller</th><th>Narx</th><th>Ombor</th><th>Sotilgan</th><th>Ko‘rish</th><th>Status</th><th>Amallar</th></tr></thead><tbody>
        {stationeries.map((item) => { const [label, chip] = statusLabel(item.status); return <tr key={item.id}><td><div className="thumb">{item.icon ? <img src={item.icon} alt="" /> : <i className="bi bi-pencil-square"></i>}</div></td><td><strong>{item.name}</strong><small className="d-block text-muted">#{item.id} · {item.category}</small></td><td>{item.seller || 'Ichki katalog'}</td><td><strong>{fmt(item.discountPrice || item.price)} so'm</strong>{item.discountPrice ? <small className="d-block text-muted text-decoration-line-through">{fmt(item.price)}</small> : null}</td><td>{item.stock} + {item.variantStock || 0}</td><td>{fmt(item.sold)}</td><td>{fmt(item.views || 0)}</td><td><span className={`chip ${chip}`}>{label}</span></td><td><button className="btn btn-sm btn-light me-1" onClick={() => setSelected(item)}><i className="bi bi-eye"></i></button><button className="btn btn-sm btn-light" onClick={() => moderate(item, item.status === 1 ? 0 : 1)}><i className="bi bi-shield-check"></i></button></td></tr> })}
        {stationeryPagination.total === 0 ? <tr><td className="text-muted text-center py-5" colSpan={9}>Mahsulot topilmadi</td></tr> : null}
      </tbody></table></div><PaginationControls {...stationeryPagination} onPageChange={(page) => loadItems(page)} />
    </div>
    <Modal show={!!selected} onHide={() => setSelected(null)} centered size="xl"><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.name}</Modal.Title></Modal.Header><Modal.Body>{!selected ? null : <div className="row g-3">
      <div className="col-xl-4"><div className="detail-panel h-100"><div className="product-preview mb-3">{selected.icon ? <img src={selected.icon} alt="" style={{ width: '100%', maxHeight: 260, objectFit: 'contain' }} /> : <div className="text-muted text-center py-5">Rasm yo‘q</div>}</div><div className="d-flex flex-wrap gap-1">{selected.images?.slice(1, 5).map((image) => <img key={image} src={image} alt="" className="thumb" />)}</div></div></div>
      <Info title="Asosiy ma'lumotlar" rows={[['Kategoriya', selected.category], ['Seller', selected.seller || 'Ichki katalog'], ['Barcode', selected.barcode || '—'], ['Material', selected.material || '—'], ['Marketplace', selected.active ? 'Faol' : 'Nofaol'], ['Visibility', selected.hidden ? 'Yashirin' : 'Ochiq']]} />
      <Info title="KPI va narx" rows={[['Narx', `${fmt(selected.price)} so'm`], ['Chegirma', selected.discountPrice ? `${fmt(selected.discountPrice)} so'm (${selected.discountPercent || 0}%)` : '—'], ['Ombor', `${selected.stock} dona`], ['Variant stock', `${selected.variantStock || 0} dona`], ['Sotilgan', `${selected.sold} dona`], ['Daromad', `${fmt(selected.revenue || 0)} so'm`], ['Mijozlar', String(selected.clients || 0)], ['Ko‘rishlar', String(selected.views || 0)]]} />
      <div className="col-xl-6"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">Variantlar</h6>{(selected.variants || []).map((variant) => <div className="d-flex justify-content-between border-bottom py-2" key={variant.id}><span>{variant.name}</span><strong>{variant.stock} dona</strong></div>)}{(selected.variants || []).length === 0 ? <div className="text-muted">Variant mavjud emas</div> : null}</div></div>
      <Info title="Admin nazorati" rows={[['Moderatsiya', statusLabel(selected.status)[0]], ['Recommended', selected.recommended ? 'Ha' : "Yo'q"], ['Chegirma muddati', selected.discountExpiresAt || '—'], ['Recommendation muddati', selected.recommendedExpiresAt || '—'], ['Yaratilgan', selected.createdAt || '—'], ['Yangilangan', selected.updatedAt || '—']]} />
      <div className="col-12"><div className="detail-panel"><h6 className="fw-bold mb-2">Tavsif</h6><div className="text-muted">{selected.description || 'Tavsif kiritilmagan'}</div></div></div>
    </div>}</Modal.Body><Modal.Footer>{selected ? <Button variant="primary" className="btn-primary-gradient" onClick={() => moderate(selected, selected.status === 1 ? 0 : 1)}>{selected.status === 1 ? 'Moderatsiyaga qaytarish' : 'Tasdiqlash'}</Button> : null}<Button variant="light" onClick={() => setSelected(null)}>Yopish</Button></Modal.Footer></Modal>
  </div>;
}
function Info({ title, rows }: { title: string; rows: Array<[string, string]> }) {
  return <div className="col-xl-4"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">{title}</h6>{rows.map(([label, value]) => <div className="border-bottom py-2" key={label}><small className="text-muted d-block">{label}</small><strong>{value}</strong></div>)}</div></div>;
}
