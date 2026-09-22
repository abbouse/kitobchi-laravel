import { useEffect, useState } from 'react';
import { PageCrumbs } from '../Layout';
import type { FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';
import ImageGalleryEditor from '../components/ImageGalleryEditor';
import ModerationRejectModal from '../components/ModerationRejectModal';
import { MediaCard } from '../components/Profile';
import FormAction from '../components/FormAction';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Variant { id: number; name: string; stock: number; price?: number; image?: string | null }
interface MiniOrder { id: number; customer: string; phone?: string; seller?: string; amount: number; status: string; date?: string; url?: string }
interface OptionItem { id: number; name: string }
interface StatItem {
  id: number; name: string; categoryId?: number | null; sellerId?: number | null; category: string; seller?: string | null; price: number; discountPrice?: number | null;
  discountPercent?: number; discountExpiresAt?: string; stock: number; variantStock?: number; sold: number; clients?: number;
  revenue?: number; views?: number; status?: number; active?: boolean; hidden?: boolean; recommended?: boolean;
  recommendedExpiresAt?: string; icon?: string | null; images?: string[]; rawImages?: string[]; barcode?: string; material?: string;
  description?: string; createdAt?: string; updatedAt?: string; aiModerationStatus?: string | null; aiModerationNote?: string | null;
  aiModerationModel?: string | null; aiModerationCheckedAt?: string | null; variants?: Variant[]; recentOrders?: MiniOrder[]; sellerOrders?: MiniOrder[]; editUrl?: string; moderateUrl?: string;
}

const statusLabel = (status?: number): [string, string] =>
  status === 1 ? ['Tasdiqlangan', 'text-light-success'] : status === 2 ? ['Rad etilgan', 'text-light-danger'] : ['Moderatsiya', 'text-light-warning'];

let variantRowSeq = -1;
const nextVariantRowKey = () => variantRowSeq--;

export default function Stationeries() {
  const {
    stationeries = [],
    stationeryCounts = {},
    stationeryPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    stationeryFilters = {},
    stationeryFormOptions = { categories: [], sellers: [] },
  } = usePage<{
    stationeries?: StatItem[];
    stationeryCounts?: Record<string, number>;
    stationeryPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    stationeryFilters?: { tab?: string; search?: string };
    stationeryFormOptions?: { categories: OptionItem[]; sellers: OptionItem[] };
  }>().props;

  const [tab, setTab] = useState(stationeryFilters.tab || 'active');
  const [search, setSearch] = useState(stationeryFilters.search || '');
  const [selected, setSelected] = useState<StatItem | null>(null);
  const [autoOpenedSearch, setAutoOpenedSearch] = useState('');
  const [rejectTarget, setRejectTarget] = useState<StatItem | null>(null);
  const [variantRows, setVariantRows] = useState<Array<{ key: number; id: number; name: string; stock: number; image: string | null }>>([]);

  const loadItems = (page = 1, activeTab = tab, term = search) =>
    router.get('/boshqaruv/stationeries', { stationeries_page: page, stationeries_tab: activeTab, stationeries_search: term }, { preserveState: true, preserveScroll: true, replace: true });

  const moderate = (item: StatItem, status: 0 | 1 | 2, note?: string) =>
    item.moderateUrl && router.patch(item.moderateUrl, { is_approved: status, note: note ?? '' }, { preserveScroll: true });

  const confirmReject = (reason: string) => {
    if (rejectTarget) moderate(rejectTarget, 2, reason);
    setRejectTarget(null);
  };

  const openDetail = (item: StatItem) => {
    setSelected(item);
    setVariantRows((item.variants || []).map((variant) => ({ key: nextVariantRowKey(), id: variant.id, name: variant.name, stock: variant.stock, image: variant.image || null })));
  };

  useEffect(() => {
    if (stationeryFilters.search && stationeryFilters.search !== autoOpenedSearch && stationeries.length === 1 && !selected) {
      setAutoOpenedSearch(stationeryFilters.search);
      openDetail(stationeries[0]);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [stationeryFilters.search, autoOpenedSearch, stationeries, selected]);

  const addVariantRow = () => setVariantRows((current) => [...current, { key: nextVariantRowKey(), id: 0, name: '', stock: 0, image: null }]);
  const removeVariantRow = (key: number) => setVariantRows((current) => current.filter((row) => row.key !== key));
  const updateVariantRow = (key: number, patch: Partial<{ name: string; stock: number }>) =>
    setVariantRows((current) => current.map((row) => (row.key === key ? { ...row, ...patch } : row)));

  const submitEdit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!selected?.editUrl) return;
    const data = new FormData(event.currentTarget);
    data.append('_method', 'PUT');
    router.post(selected.editUrl, data, { forceFormData: true, preserveScroll: true, onSuccess: () => setSelected(null) });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Kanselyariya mahsulotlari</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Moderatsiya, ombor, variantlar va katalog nazorati</p>
        </div>
      </div>

      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600">Mahsulotlar</h5>
            <p className="mb-0 text-secondary">{stationeryPagination.total} ta mahsulot topildi</p>
          </div>
          <form className="d-flex gap-2" onSubmit={(event) => { event.preventDefault(); loadItems(); }}>
            <input className="form-control form-control-sm" style={{ maxWidth: 300 }} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Nomi, kategoriya, barcode yoki seller" />
            <button className="btn btn-sm btn-outline-secondary"><i className="ti ti-search"></i></button>
          </form>
        </div>
        <div className="card-body">


          <div className="nav kc-segment mb-3">
            {[
              ['pending', 'Moderatsiya'],
              ['active', 'Tasdiqlangan'],
              ['rejected', 'Rad etilgan'],
              ['all', 'Barchasi'],
            ].map(([key, label]) => (
              <div key={key} className="nav-item"><button
                  className={`nav-link ${tab === key ? 'active' : ''}`}
                  onClick={() => { setTab(key); loadItems(1, key); }}>
                  {label}<span className="badge text-light-secondary ms-2">{stationeryCounts[key] || 0}</span>
                </button></div>
            ))}
          </div>

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead>
                <tr>
                  <th></th>
                  <th>Mahsulot</th>
                  <th>Seller</th>
                  <th>Narx</th>
                  <th>Ombor</th>
                  <th>Sotilgan</th>
                  <th>Ko'rish</th>
                  <th>Status</th>
                  <th>Amallar</th>
                </tr>
              </thead>
              <tbody>
                {stationeries.map((item) => {
                  const [label, chip] = statusLabel(item.status);
                  return (
                    <tr key={item.id}>
                      <td><div className="w-40 h-55 b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0">{item.icon ? <img className="w-100 h-100 object-fit-cover" src={item.icon} alt="" /> : <i className="ti ti-edit"></i>}</div></td>
                      <td><strong>{item.name}</strong><small className="d-block text-muted">#{item.id} · {item.category}</small></td>
                      <td>{item.seller || 'Ichki katalog'}</td>
                      <td>
                        <strong>{fmt(item.discountPrice || item.price)} so'm</strong>
                        {item.discountPrice ? <small className="d-block text-muted text-decoration-line-through">{fmt(item.price)}</small> : null}
                      </td>
                      <td>{item.stock} + {item.variantStock || 0}</td>
                      <td>{fmt(item.sold)}</td>
                      <td>{fmt(item.views || 0)}</td>
                      <td><span className={`badge ${chip}`}>{label}</span></td>
                      <td>
                        <div className="d-flex gap-1">
                          <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => openDetail(item)} title="Ko'rish / tahrirlash"><i className="ti ti-eye"></i></button>
                          {item.moderateUrl && item.status !== 1 ? (
                            <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => moderate(item, 1)} title="Tasdiqlash"><i className="ti ti-check"></i></button>
                          ) : null}
                          {item.moderateUrl && item.status !== 2 ? (
                            <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => setRejectTarget(item)} title="Rad etish"><i className="ti ti-x"></i></button>
                          ) : null}
                        </div>
                      </td>
                    </tr>
                  );
                })}
                {stationeryPagination.total === 0 ? <tr><td className="text-center py-5 text-secondary" colSpan={9}><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Bu bo'limda mahsulot topilmadi</td></tr> : null}
              </tbody>
            </table>
          </div>
          <PaginationControls {...stationeryPagination} onPageChange={(page) => loadItems(page)} />
        </div>
      </div>

      <ModerationRejectModal
        show={!!rejectTarget}
        itemLabel={rejectTarget?.name}
        onCancel={() => setRejectTarget(null)}
        onConfirm={confirmReject}
      />

      <Modal show={!!selected} onHide={() => setSelected(null)} centered size="xl" scrollable>
        <Modal.Header closeButton>
          <Modal.Title className="f-s-20 f-w-600">{selected?.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {!selected ? null : (
            <div className="row">
              <div className="col-lg-4 col-xxl-3">
                <MediaCard
                  icon="ti ti-pencil"
                  image={selected.icon || null}
                  title={selected.name}
                  subtitle={[selected.category, selected.seller || 'Ichki katalog'].filter(Boolean).join(' · ')}
                  badges={<><span className={`badge ${selected.active ? 'text-light-success' : 'text-light-secondary'}`}>{selected.active ? 'Faol' : 'Nofaol'}</span><span className={`badge ${selected.hidden ? 'text-light-warning' : 'text-light-info'}`}>{selected.hidden ? 'Yashirin' : 'Ochiq'}</span></>}
                  stats={[{ label: 'Narx', value: fmt(selected.price) }, { label: 'Ombor', value: selected.stock }, { label: 'Sotilgan', value: selected.sold }]}
                />
                {(selected.images || []).length > 1 ? (
                  <div className="card"><div className="card-header"><h5 className="mb-0">Galereya</h5></div><div className="card-body">
                    <div className="row g-2">
                      {(selected.images || []).slice(0, 9).map((image) => <div className="col-4" key={image}><a href={image} target="_blank" rel="noreferrer" className="d-block b-r-10 overflow-hidden h-80"><img src={image} alt="" className="w-100 h-100 object-fit-cover" /></a></div>)}
                    </div>
                  </div></div>
                ) : null}
              </div>
              <div className="col-lg-8 col-xxl-9"><div className="row">
              <Info title="Asosiy ma'lumotlar" rows={[
                ['Kategoriya', selected.category],
                ['Seller', selected.seller || 'Ichki katalog'],
                ['Barcode', selected.barcode || '—'],
                ['Material', selected.material || '—'],
                ['Marketplace', selected.active ? 'Faol' : 'Nofaol'],
                ['Visibility', selected.hidden ? 'Yashirin' : 'Ochiq'],
              ]} />

              <Info title="KPI va narx" rows={[
                ['Narx', `${fmt(selected.price)} so'm`],
                ['Chegirma', selected.discountPrice ? `${fmt(selected.discountPrice)} so'm (${selected.discountPercent || 0}%)` : '—'],
                ['Ombor', `${selected.stock} dona`],
                ['Variant stock', `${selected.variantStock || 0} dona`],
                ['Sotilgan', `${selected.sold} dona`],
                ['Daromad', `${fmt(selected.revenue || 0)} so'm`],
                ['Mijozlar', String(selected.clients || 0)],
                ["Ko'rishlar", String(selected.views || 0)],
              ]} />

              <div className="col-xl-6">
                <div className="card h-100"><div className="card-header"><h5 className="mb-0">Variantlar</h5></div><div className="card-body">
                    {(selected.variants || []).length === 0 ? <div className="text-muted mb-2">Variant mavjud emas</div> : null}
                    {(selected.variants || []).map((variant) => (
                      <div className="d-flex justify-content-between b-b-1-light py-2" key={variant.id}>
                        <span>{variant.name}</span>
                        <strong>{variant.stock} dona</strong>
                      </div>
                    ))}
                    <div className="text-muted f-s-13 mt-2">Variantlarni qo'shish/o'chirish uchun pastdagi "Tahrirlash" tugmasidan foydalaning.</div>
                  </div></div>
              </div>

              <Info title="Admin nazorati" rows={[
                ['Moderatsiya', statusLabel(selected.status)[0]],
                ['AI holati', aiStatusLabel(selected.aiModerationStatus)],
                ['AI modeli', selected.aiModerationModel || '—'],
                ['AI tekshiruv vaqti', selected.aiModerationCheckedAt || '—'],
                ['Recommended', selected.recommended ? 'Ha' : "Yo'q"],
                ['Chegirma muddati', selected.discountExpiresAt || '—'],
                ['Recommendation muddati', selected.recommendedExpiresAt || '—'],
                ['Yaratilgan', selected.createdAt || '—'],
                ['Yangilangan', selected.updatedAt || '—'],
              ]} />

              {selected.aiModerationNote ? (
                <div className="col-12"><div className="card"><div className="card-header"><h5 className="mb-0">AI moderatsiya sababi</h5></div><div className="card-body"><div className="text-muted">{selected.aiModerationNote}</div></div></div></div>
              ) : null}

              <div className="col-12"><div className="card"><div className="card-header"><h5 className="mb-0">Tavsif</h5></div><div className="card-body"><div className="text-muted">{selected.description || 'Tavsif kiritilmagan'}</div></div></div></div>

              <div className="col-xl-6"><div className="card h-100"><div className="card-header"><h5 className="mb-0">Shu mahsulot buyurtmalari</h5></div><div className="card-body"><MiniOrdersTable rows={selected.recentOrders || []} empty="Bu kanselyariya bo'yicha buyurtma topilmadi" /></div></div></div>
              <div className="col-xl-6"><div className="card h-100"><div className="card-header"><h5 className="mb-0">Seller orderlar</h5></div><div className="card-body"><MiniOrdersTable rows={selected.sellerOrders || []} empty="Seller order topilmadi" /></div></div></div>

              </div></div>
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>
          {selected?.editUrl ? (
            <FormAction label="Tahrirlash" icon="ti ti-edit" variant="light-primary" size="md" modalSize="lg" title={`Tahrirlash: ${selected.name}`} onSubmit={submitEdit}>
              <div className="row g-3">
                      <div className="col-md-6"><label className="form-label">Nomi</label><input name="name" className="form-control" defaultValue={selected.name} required /></div>
                      <div className="col-md-3"><label className="form-label">Kategoriya</label><select name="category_id" className="form-select" defaultValue={selected.categoryId || ''} required>{stationeryFormOptions.categories.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></div>
                      <div className="col-md-3"><label className="form-label">Seller</label><select name="seller_id" className="form-select" defaultValue={selected.sellerId || ''}><option value="">Ichki katalog</option>{stationeryFormOptions.sellers.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></div>
                      <div className="col-md-3"><label className="form-label">Barcode</label><input name="barcode" className="form-control" defaultValue={selected.barcode || ''} /></div>
                      <div className="col-md-3"><label className="form-label">Material</label><input name="material" className="form-control" defaultValue={selected.material || ''} /></div>
                      <div className="col-md-3"><label className="form-label">Narx</label><input name="price" type="number" min={0} className="form-control" defaultValue={selected.price} required /></div>
                      <div className="col-md-3"><label className="form-label">Chegirma narxi</label><input name="discount_price" type="number" min={0} className="form-control" defaultValue={selected.discountPrice || ''} /></div>
                      <div className="col-md-3"><label className="form-label">Chegirma muddati</label><input name="discountExpiresAt" type="datetime-local" className="form-control" defaultValue={toInputDate(selected.discountExpiresAt)} /></div>
                      <div className="col-md-3"><label className="form-label">Ombor</label><input name="stock" type="number" min={0} className="form-control" defaultValue={selected.stock} required /></div>
                      <div className="col-md-3"><label className="form-label">Moderatsiya</label><select name="is_approved" className="form-select" defaultValue={selected.status ?? 0}><option value="0">Moderatsiya</option><option value="1">Tasdiqlangan</option><option value="2">Rad etilgan</option></select></div>
                      <div className="col-md-6 d-flex align-items-end gap-3 flex-wrap">
                        <label className="form-check"><input name="status" value="1" className="form-check-input" type="checkbox" defaultChecked={selected.active} /> <span className="form-check-label">Faol</span></label>
                        <label className="form-check"><input name="is_hidden" value="1" className="form-check-input" type="checkbox" defaultChecked={selected.hidden} /> <span className="form-check-label">Yashirish</span></label>
                        <label className="form-check"><input name="recommended" value="1" className="form-check-input" type="checkbox" defaultChecked={selected.recommended} /> <span className="form-check-label">Tavsiya</span></label>
                      </div>
                      <div className="col-md-3"><label className="form-label">Tavsiya muddati</label><input name="recommendedExpiresAt" type="datetime-local" className="form-control" defaultValue={toInputDate(selected.recommendedExpiresAt)} /></div>

                      <div className="col-12">
                        <label className="form-label">Rasmlar</label>
                        <ImageGalleryEditor key={selected.id} images={selected.rawImages || selected.images || []} />
                      </div>

                      <div className="col-12"><label className="form-label">Tavsif</label><textarea name="description" className="form-control" rows={4} defaultValue={selected.description || ''} /></div>

                      <div className="col-12">
                        <div className="d-flex align-items-center justify-content-between mb-2">
                          <h6 className="f-w-600 mb-0">Variantlar</h6>
                          <button type="button" className="btn btn-sm btn-outline-secondary" onClick={addVariantRow}>
                            <i className="ti ti-plus me-1"></i>Variant qo'shish
                          </button>
                        </div>
                        {variantRows.length === 0 ? <div className="text-muted f-s-13 mb-2">Variant yo'q — kerak bo'lsa yuqoridagi tugma bilan qo'shing.</div> : null}
                        {variantRows.map((row) => (
                          <div className="row g-2 mb-2 align-items-center" key={row.key}>
                            <input type="hidden" name="variant_id[]" value={row.id || ''} />
                            <input type="hidden" name="variant_image_existing[]" value={row.image || ''} />
                            <div className="col-md-1">
                              {row.image ? <img src={row.image} alt="" className="b-r-10 object-fit-cover flex-shrink-0 w-35 h-35" /> : <div className="b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 w-35 h-35"><i className="ti ti-photo text-muted"></i></div>}
                            </div>
                            <div className="col-md-3">
                              <input name="variant_color_name[]" className="form-control" placeholder="Rang/variant" value={row.name} onChange={(event) => updateVariantRow(row.key, { name: event.target.value })} />
                            </div>
                            <div className="col-md-2">
                              <input name="variant_stock[]" type="number" min={0} className="form-control" placeholder="Stock" value={row.stock} onChange={(event) => updateVariantRow(row.key, { stock: Number(event.target.value) })} />
                            </div>
                            <div className="col-md-5">
                              <input name="variant_image[]" type="file" accept="image/*" className="form-control" />
                            </div>
                            <div className="col-md-1 text-end">
                              <button type="button" className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" title="Variantni o'chirish" onClick={() => removeVariantRow(row.key)}>
                                <i className="ti ti-trash"></i>
                              </button>
                            </div>
                          </div>
                        ))}
                        <div className="form-text">O'chirilgan variant saqlashda butunlay o'chib ketadi (ombordagi qoldig'i bilan birga).</div>
                      </div>

              </div>
            </FormAction>
          ) : null}
          {selected?.moderateUrl ? (
            <>
              <Button variant="outline-danger" onClick={() => setRejectTarget(selected)}>Rad etish</Button>
              <Button variant="outline-secondary" onClick={() => moderate(selected, 0)}>Moderatsiyaga</Button>
              <Button variant="primary" className="btn-primary" onClick={() => moderate(selected, 1)}>Tasdiqlash</Button>
            </>
          ) : null}
          <Button variant="light-secondary" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

function Info({ title, rows }: { title: string; rows: Array<[string, string]> }) {
  return (
    <div className="col-xl-4">
      <div className="card h-100"><div className="card-header"><h5 className="mb-0">{title}</h5></div><div className="card-body">
          {rows.map(([label, value]) => (
            <div className="b-b-1-light py-2" key={label}>
              <small className="text-muted d-block">{label}</small>
              <strong>{value}</strong>
            </div>
          ))}
        </div></div>
    </div>
  );
}

function MiniOrdersTable({ rows, empty }: { rows: MiniOrder[]; empty: string }) {
  if (!rows.length) return <div className="text-muted f-s-13">{empty}</div>;
  return (
    <div className="table-responsive app-scroll">
      <table className="table table-bottom-border align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Mijoz</th>
            <th>Summa</th>
            <th>Status</th>
            <th>Sana</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={row.id}>
              <td>#{row.id}</td>
              <td><strong>{row.customer}</strong><small className="d-block text-muted">{row.phone || row.seller || ''}</small></td>
              <td>{fmt(row.amount)} so'm</td>
              <td><span className="badge text-light-secondary">{row.status || '—'}</span></td>
              <td>{row.date || '—'}</td>
              <td className="text-end">{row.url ? <a className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" href={row.url} title="Buyurtmani ochish"><i className="ti ti-eye"></i></a> : null}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function toInputDate(value?: string | null) {
  if (!value) return '';
  return String(value).replace(' ', 'T').slice(0, 16);
}

function aiStatusLabel(value?: string | null) {
  return ({ pending: 'Navbatda', processing: 'Tekshirilmoqda', approved: 'AI tasdiqladi', rejected: 'AI rad etdi', human_review: 'Admin ko‘rigi kerak', failed: 'Vaqtincha xato', manual_approved: 'Admin tasdiqladi', manual_rejected: 'Admin rad etdi', legacy_exempt: 'Eski (moderatsiyadan chetlashtirilgan)' } as Record<string, string>)[value || ''] || value || 'Hali tekshirilmagan';
}
