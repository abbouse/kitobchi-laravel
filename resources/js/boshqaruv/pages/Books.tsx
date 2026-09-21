import { useEffect, useState } from 'react';
import { PageCrumbs } from '../Layout';
import type { FormEvent, ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';
import ImageGalleryEditor from '../components/ImageGalleryEditor';
import ModerationRejectModal from '../components/ModerationRejectModal';
import { MiniStat, StatWidget } from '../components/Axelit';
import { tiIcon } from '../utils/icons';
import { MediaCard, PersonRow } from '../components/Profile';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface MiniOrder {
  id: number;
  customer: string;
  phone?: string;
  amount: number;
  status: string;
  payment?: string;
  date?: string;
  url?: string;
  seller?: string;
}

interface Book {
  id: number;
  title: string;
  author: string;
  translator?: string;
  isbn?: string;
  price: number;
  discountPrice?: number | null;
  discountExpiresAt?: string | null;
  stock: number;
  sold: number;
  totalClients?: number;
  totalRevenue?: number;
  totalSalesWeek?: number;
  views?: number;
  cover: string | null;
  images?: string[];
  rawImages?: string[];
  category?: string;
  categoryId?: number | null;
  publisher?: string | null;
  publisherId?: number | null;
  sellerId?: number | null;
  status?: number;
  statusLabel?: string;
  active?: boolean;
  hidden?: boolean;
  recommended?: boolean;
  recommendedExpiresAt?: string | null;
  seller?: {
    name?: string;
    phone?: string;
    status?: string;
    verified?: boolean;
    hidden?: boolean;
    url?: string;
  } | null;
  lang?: string;
  langType?: string;
  coverType?: string;
  pages?: number;
  year?: number;
  description?: string | null;
  mediaCount?: number;
  recentOrders?: MiniOrder[];
  sellerOrders?: MiniOrder[];
  createdAt?: string;
  updatedAt?: string;
  aiModerationStatus?: string | null;
  aiModerationNote?: string | null;
  aiModerationModel?: string | null;
  aiModerationCheckedAt?: string | null;
  showUrl?: string;
  editUrl?: string;
  moderateUrl?: string;
}

interface OptionItem { id: number; name: string }

const badge = (ok: boolean | undefined, yes: string, no: string) => (
  <span className={`badge ${ok ? 'text-light-success' : 'text-light-secondary'}`}>{ok ? yes : no}</span>
);

const Detail = ({ label, value }: { label: string; value?: ReactNode }) => (
  <div className="col-md-6">
    <p className="mb-1 f-s-13 text-secondary">{label}</p>
    <h6 className="mb-0 f-w-600 f-s-14 text-dark text-break">{value || '—'}</h6>
  </div>
);

const statusChip = (status?: number): [string, string] =>
  status === 1 ? ['Faol', 'text-light-success'] : status === 2 ? ['Rad etilgan', 'text-light-danger'] : ['Moderatsiya', 'text-light-warning'];

export default function Books() {
  const { books = [], bookPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, bookCounts = {}, bookFilters = {}, bookFormOptions = { categories: [], publishers: [], sellers: [] } } = usePage<{ books?: Book[]; bookPagination?: { page: number; totalPages: number; from: number; to: number; total: number }; bookCounts?: Record<string, number>; bookFilters?: { search?: string; tab?: string }; bookFormOptions?: { categories: OptionItem[]; publishers: OptionItem[]; sellers: OptionItem[] } }>().props;
  const [showView, setShowView] = useState(false);
  const [selectedBook, setSelectedBook] = useState<Book | null>(null);
  const [search, setSearch] = useState(bookFilters.search || '');
  const [activeTab, setActiveTab] = useState(bookFilters.tab || 'active');
  const [autoOpenedSearch, setAutoOpenedSearch] = useState('');
  const [rejectTarget, setRejectTarget] = useState<Book | null>(null);

  const handleOpenView = (book: Book) => {
    setSelectedBook(book);
    setShowView(true);
  };

  useEffect(() => {
    if (bookFilters.search && bookFilters.search !== autoOpenedSearch && books.length === 1 && !showView) {
      setAutoOpenedSearch(bookFilters.search);
      handleOpenView(books[0]);
    }
  }, [bookFilters.search, autoOpenedSearch, books, showView]);

  const loadBooks = (page = 1, tab = activeTab, term = search) => {
    router.get('/boshqaruv/books', { books_page: page, books_tab: tab, books_search: term }, { preserveState: true, preserveScroll: true, replace: true });
  };

  const handleModerate = (book: Book, status: 0 | 1 | 2, note?: string) => {
    if (!book.moderateUrl) return;
    router.patch(book.moderateUrl, { is_approved: status, note: note ?? '' }, { preserveScroll: true });
  };

  const confirmReject = (reason: string) => {
    if (rejectTarget) handleModerate(rejectTarget, 2, reason);
    setRejectTarget(null);
  };

  const submitEdit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!selectedBook?.editUrl) return;
    const data = new FormData(event.currentTarget);
    data.append('_method', 'PUT');
    router.post(selectedBook.editUrl, data, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => setShowView(false),
    });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Kitoblar katalogi</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Moderatsiya, faol va rad etilgan kitoblarni boshqarish</p>
        </div>
        <form className="d-flex gap-2" onSubmit={(event) => { event.preventDefault(); loadBooks(1); }}>
          <input className="form-control form-control-sm" style={{ minWidth: 280 }} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="ID, nom, ISBN, muallif yoki seller" />
          <button className="btn btn-sm btn-outline-secondary"><i className="ti ti-search"></i></button>
        </form>
      </div>

      <div className="row">
        {[
          { key: 'pending', label: 'Moderatsiyada', val: bookCounts.pending || 0, hint: 'Avval ko‘rib chiqilishi kerak', color: 'rgba(var(--warning-dark), 1)' },
          { key: 'active', label: 'Faol kitoblar', val: bookCounts.active || 0, hint: 'Xaridorga ko‘rinayotganlar', color: 'rgba(var(--success), 1)' },
          { key: 'rejected', label: 'Rad etilgan', val: bookCounts.rejected || 0, hint: 'Qayta ko‘rib chiqilishi mumkin', color: 'rgba(var(--danger), 1)' },
          { key: 'all', label: 'Jami katalog', val: bookCounts.all || 0, hint: 'Barcha yozuvlar', color: 'rgba(var(--primary), 1)' },
        ].map((item, kpiIndex) => (
          <div className="col-xl-3 col-md-6" key={item.key}>
            <StatWidget
              index={kpiIndex}
              label={item.label}
              value={item.val}
              sub={item.hint}
              selected={activeTab === item.key}
              onClick={() => { setActiveTab(item.key); loadBooks(1, item.key); }}
            />
          </div>
        ))}
      </div>

      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600">Kitoblar</h5>
            <p className="mb-0 text-secondary">{bookPagination.total} ta kitob topildi</p>
          </div>
          <div className="nav kc-segment">
            {[
              ['pending', 'Moderatsiya'],
              ['active', 'Faol'],
              ['rejected', 'Rad etilgan'],
              ['all', 'Barchasi'],
            ].map(([key, label]) => (
              <div key={key} className="nav-item"><button
                  type="button"
                  className={`nav-link ${activeTab === key ? 'active' : ''}`}
                  onClick={() => { setActiveTab(key); loadBooks(1, key); }}>
                  {label} <span className="badge text-light-secondary ms-2">{bookCounts[key] || 0}</span>
                </button></div>
            ))}
          </div>
        </div>
        <div className="card-body">


          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead>
                <tr>
                  <th></th>
                  <th>Kitob</th>
                  <th>Muallif</th>
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
                {books.map((book) => {
                  const [label, chip] = statusChip(book.status);
                  return (
                    <tr key={book.id}>
                      <td>
                        <div className="w-40 h-55 b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0">
                          {book.cover && String(book.cover).startsWith('http') ? <img className="w-100 h-100 object-fit-cover" src={book.cover} alt="" /> : <i className="ti ti-book"></i>}
                        </div>
                      </td>
                      <td>
                        <strong className="d-block text-truncate" style={{ maxWidth: 260 }}>{book.title}</strong>
                        <small className="d-block text-muted">#{book.id} · {book.category || 'Kitob'}{book.hidden ? ' · yashirilgan' : ''}</small>
                      </td>
                      <td className="text-muted">{book.author}</td>
                      <td>{book.seller?.name || 'Ichki katalog'}</td>
                      <td>
                        <strong>{fmt(book.discountPrice || book.price)} so'm</strong>
                        {book.discountPrice ? <small className="d-block text-muted text-decoration-line-through">{fmt(book.price)}</small> : null}
                      </td>
                      <td>{fmt(book.stock)}</td>
                      <td>{fmt(book.sold)}</td>
                      <td>{fmt(book.views || 0)}</td>
                      <td><span className={`badge ${chip}`}>{label}</span></td>
                      <td>
                        <div className="d-flex gap-1">
                          <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => handleOpenView(book)} title="Ko'rish / tahrirlash">
                            <i className="ti ti-eye"></i>
                          </button>
                          {book.moderateUrl && book.status !== 1 ? (
                            <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => handleModerate(book, 1)} title="Tasdiqlash">
                              <i className="ti ti-check"></i>
                            </button>
                          ) : null}
                          {book.moderateUrl && book.status !== 2 ? (
                            <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => setRejectTarget(book)} title="Rad etish">
                              <i className="ti ti-x"></i>
                            </button>
                          ) : null}
                        </div>
                      </td>
                    </tr>
                  );
                })}
                {bookPagination.total === 0 ? (
                  <tr><td className="text-center py-5 text-secondary" colSpan={10}><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Bu bo'limda kitob topilmadi</td></tr>
                ) : null}
              </tbody>
            </table>
          </div>
          <PaginationControls {...bookPagination} onPageChange={(page) => loadBooks(page)} />
        </div>
      </div>

      <ModerationRejectModal
        show={!!rejectTarget}
        itemLabel={rejectTarget?.title}
        onCancel={() => setRejectTarget(null)}
        onConfirm={confirmReject}
      />

      <Modal show={showView} onHide={() => setShowView(false)} centered size="xl" scrollable>
        <Modal.Header closeButton>
          <Modal.Title className="f-s-20 f-w-600">Kitob: {selectedBook?.title}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedBook ? (
            <div className="row">
              <div className="col-lg-4 col-xxl-3">
                <MediaCard
                  image={selectedBook.cover || null}
                  title={selectedBook.title}
                  subtitle={selectedBook.author}
                  badges={<>
                    {badge(Boolean(selectedBook.active), 'Aktiv', 'Nofaol')}
                    {badge(Boolean(selectedBook.status), 'Tasdiqlangan', 'Moderatsiya')}
                    {badge(!selectedBook.hidden, "Ko'rinadi", 'Yashirilgan')}
                    {selectedBook.recommended ? <span className="badge text-light-info">Tavsiya</span> : null}
                  </>}
                  stats={[{ label: 'Sotilgan', value: fmt(selectedBook.sold) }, { label: 'Ombor', value: fmt(selectedBook.stock) }, { label: "Ko'rish", value: fmt(selectedBook.views || 0) }]}
                />

                <div className="card"><div className="card-header"><h5 className="mb-0">Sotuvchi</h5></div><div className="card-body">
                    {selectedBook.seller ? (
                      <>
                        <PersonRow icon="ti ti-building-store" name={selectedBook.seller.name} meta={selectedBook.seller.phone || "Telefon yo'q"} />
                        <div className="d-flex gap-2 mt-3 flex-wrap">
                          {badge(selectedBook.seller.verified, 'Verified', 'Tekshirilmagan')}
                          {badge(!selectedBook.seller.hidden, 'Aktiv shop', 'Shop yashirin')}
                        </div>
                      </>
                    ) : (
                      <div className="text-muted">Ichki katalog</div>
                    )}
                  </div></div>
              </div>

              <div className="col-lg-8 col-xxl-9">
                <div className="row g-3 mb-3">
                  {[
                    { label: 'Narx', value: `${fmt(selectedBook.price)} so'm`, icon: 'ti-cash' },
                    { label: 'Ombor', value: `${fmt(selectedBook.stock)} dona`, icon: 'ti-package' },
                    { label: 'Sotilgan', value: `${fmt(selectedBook.sold)} marta`, icon: 'ti-shopping-bag' },
                    { label: 'Daromad', value: `${fmt(selectedBook.totalRevenue || 0)} so'm`, icon: 'ti-trending-up' },
                  ].map((item) => (
                    <div className="col-sm-6" key={item.label}>
                      <MiniStat icon={tiIcon(item.icon)} label={item.label} value={item.value} />
                    </div>
                  ))}
                </div>

                <div className="card"><div className="card-header"><h5 className="mb-0">Asosiy ma'lumotlar</h5></div><div className="card-body">
                    <div className="row g-3">
                      <Detail label="Kategoriya" value={selectedBook.category} />
                      <Detail label="Nashriyot" value={selectedBook.publisher} />
                      <Detail label="Tarjimon" value={selectedBook.translator} />
                      <Detail label="ISBN" value={selectedBook.isbn} />
                      <Detail label="Til / yozuv" value={[selectedBook.lang, selectedBook.langType].filter(Boolean).join(' / ')} />
                      <Detail label="Muqova / sahifa" value={[selectedBook.coverType, selectedBook.pages ? `${selectedBook.pages} bet` : null].filter(Boolean).join(' / ')} />
                      <Detail label="Yil" value={selectedBook.year} />
                      <Detail label="Ko'rishlar" value={fmt(selectedBook.views || 0)} />
                      <Detail label="Chegirma" value={selectedBook.discountPrice ? `${fmt(selectedBook.discountPrice)} so'm` : null} />
                      <Detail label="Chegirma muddati" value={selectedBook.discountExpiresAt} />
                      <Detail label="Tavsiya muddati" value={selectedBook.recommendedExpiresAt} />
                      <Detail label="Media" value={`${selectedBook.mediaCount || 0} ta rasm`} />
                    </div>
                    {selectedBook.description ? (
                      <div className="mt-3">
                        <div className="text-muted f-s-13">Tavsif</div>
                        <div className="text-break" style={{ whiteSpace: 'pre-wrap' }}>{selectedBook.description}</div>
                      </div>
                    ) : null}
                  </div></div>

                <div className="card"><div className="card-header"><h5 className="mb-0">AI moderatsiya auditi</h5></div><div className="card-body">
                    <div className="row g-3">
                      <Detail label="AI holati" value={aiStatusLabel(selectedBook.aiModerationStatus)} />
                      <Detail label="Tekshirgan model" value={selectedBook.aiModerationModel} />
                      <Detail label="Tekshiruv vaqti" value={selectedBook.aiModerationCheckedAt} />
                      <Detail label="Qaror" value={selectedBook.statusLabel} />
                    </div>
                    {selectedBook.aiModerationNote ? <div className="alert alert-border-secondary mt-3 mb-0 f-s-13">{selectedBook.aiModerationNote}</div> : null}
                  </div></div>

                <div className="card"><div className="card-header"><h5 className="mb-0">Savdo analitikasi</h5></div><div className="card-body">
                    <div className="row g-3">
                      <Detail label="Jami sotuv" value={`${fmt(selectedBook.sold)} dona`} />
                      <Detail label="Jami mijoz" value={fmt(selectedBook.totalClients || 0)} />
                      <Detail label="Haftalik sotuv" value={`${fmt(selectedBook.totalSalesWeek || 0)} dona`} />
                      <Detail label="Jami tushum" value={`${fmt(selectedBook.totalRevenue || 0)} so'm`} />
                    </div>
                  </div></div>

                <div className="card"><div className="card-header"><h5 className="mb-0">Oxirgi buyurtmalar</h5></div><div className="card-body">
                    <MiniOrdersTable rows={selectedBook.recentOrders || []} empty="Bu kitob bo'yicha buyurtma topilmadi" />
                  </div></div>

                <div className="card"><div className="card-header"><h5 className="mb-0">Seller orderlar</h5></div><div className="card-body">
                    <MiniOrdersTable rows={selectedBook.sellerOrders || []} empty="Seller order topilmadi" />
                  </div></div>

                <div className="card"><div className="card-header"><h5 className="mb-0">Admin tahriri</h5></div><div className="card-body">
                    <form className="row g-3" onSubmit={submitEdit}>
                      <div className="col-md-6"><label className="form-label f-s-13 text-muted">Nomi</label><input name="name" className="form-control" defaultValue={selectedBook.title} required /></div>
                      <div className="col-md-6"><label className="form-label f-s-13 text-muted">Muallif</label><input name="author" className="form-control" defaultValue={selectedBook.author} required /></div>
                      <div className="col-md-4"><label className="form-label f-s-13 text-muted">Tarjimon</label><input name="translator" className="form-control" defaultValue={selectedBook.translator || ''} /></div>
                      <div className="col-md-4"><label className="form-label f-s-13 text-muted">ISBN</label><input name="isbn" className="form-control" defaultValue={selectedBook.isbn || ''} /></div>
                      <div className="col-md-4"><label className="form-label f-s-13 text-muted">Yil</label><input name="year" type="number" className="form-control" defaultValue={selectedBook.year || ''} /></div>
                      <div className="col-md-4"><label className="form-label f-s-13 text-muted">Kategoriya</label><select name="category_id" className="form-select" defaultValue={selectedBook.categoryId || ''} required>{bookFormOptions.categories.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></div>
                      <div className="col-md-4"><label className="form-label f-s-13 text-muted">Nashriyot</label><select name="publisher_id" className="form-select" defaultValue={selectedBook.publisherId || ''}><option value="">Tanlanmagan</option>{bookFormOptions.publishers.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></div>
                      <div className="col-md-4"><label className="form-label f-s-13 text-muted">Seller</label><select name="seller_id" className="form-select" defaultValue={selectedBook.sellerId || ''}><option value="">Ichki katalog</option>{bookFormOptions.sellers.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></div>
                      <div className="col-md-3"><label className="form-label f-s-13 text-muted">Narx</label><input name="price" type="number" min={0} className="form-control" defaultValue={selectedBook.price} required /></div>
                      <div className="col-md-3"><label className="form-label f-s-13 text-muted">Chegirma narxi</label><input name="discountPrice" type="number" min={0} className="form-control" defaultValue={selectedBook.discountPrice || ''} /></div>
                      <div className="col-md-3"><label className="form-label f-s-13 text-muted">Chegirma muddati</label><input name="discountExpiresAt" type="datetime-local" className="form-control" defaultValue={toInputDate(selectedBook.discountExpiresAt)} /></div>
                      <div className="col-md-3"><label className="form-label f-s-13 text-muted">Ombor</label><input name="count" type="number" min={0} className="form-control" defaultValue={selectedBook.stock} required /></div>
                      <div className="col-md-3"><label className="form-label f-s-13 text-muted">Til</label><input name="lang" className="form-control" defaultValue={selectedBook.lang || ''} /></div>
                      <div className="col-md-3"><label className="form-label f-s-13 text-muted">Yozuv</label><input name="langType" className="form-control" defaultValue={selectedBook.langType || ''} /></div>
                      <div className="col-md-3"><label className="form-label f-s-13 text-muted">Muqova</label><input name="coverType" className="form-control" defaultValue={selectedBook.coverType || ''} /></div>
                      <div className="col-md-3"><label className="form-label f-s-13 text-muted">Sahifa</label><input name="pages" type="number" min={0} className="form-control" defaultValue={selectedBook.pages || ''} /></div>
                      <div className="col-md-4"><label className="form-label f-s-13 text-muted">Moderatsiya</label><select name="is_approved" className="form-select" defaultValue={selectedBook.status ?? 0}><option value="0">Moderatsiya</option><option value="1">Tasdiqlangan</option><option value="2">Rad etilgan</option></select></div>
                      <div className="col-md-8 d-flex align-items-end gap-3 flex-wrap">
                        <label className="form-check"><input name="status" value="1" className="form-check-input" type="checkbox" defaultChecked={selectedBook.active} /> <span className="form-check-label">Faol</span></label>
                        <label className="form-check"><input name="is_hidden" value="1" className="form-check-input" type="checkbox" defaultChecked={selectedBook.hidden} /> <span className="form-check-label">Yashirish</span></label>
                        <label className="form-check"><input name="recommended" value="1" className="form-check-input" type="checkbox" defaultChecked={selectedBook.recommended} /> <span className="form-check-label">Tavsiya</span></label>
                      </div>
                      <div className="col-md-6"><label className="form-label f-s-13 text-muted">Tavsiya muddati</label><input name="recommendedExpiresAt" type="datetime-local" className="form-control" defaultValue={toInputDate(selectedBook.recommendedExpiresAt)} /></div>
                      <div className="col-12">
                        <label className="form-label f-s-13 text-muted">Rasmlar</label>
                        <ImageGalleryEditor key={selectedBook.id} images={selectedBook.rawImages || selectedBook.images || []} />
                      </div>
                      <div className="col-12"><label className="form-label f-s-13 text-muted">Tavsif</label><textarea name="description" className="form-control" rows={4} defaultValue={selectedBook.description || ''} /></div>
                      <div className="col-12"><button className="btn btn-primary">Saqlash</button></div>
                    </form>
                  </div></div>
              </div>
            </div>
          ) : null}
        </Modal.Body>
        <Modal.Footer>
          {selectedBook?.moderateUrl ? (
            <>
              <Button variant="outline-danger" onClick={() => setRejectTarget(selectedBook)}>Rad etish</Button>
              <Button variant="outline-secondary" onClick={() => handleModerate(selectedBook, 0)}>Moderatsiyaga</Button>
              <Button variant="primary" className="btn-primary" onClick={() => handleModerate(selectedBook, 1)}>Tasdiqlash</Button>
            </>
          ) : null}
          <Button variant="light-secondary" onClick={() => setShowView(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
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

function MiniOrdersTable({ rows, empty }: { rows: MiniOrder[]; empty: string }) {
  if (!rows.length) {
    return <div className="text-muted f-s-13">{empty}</div>;
  }

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
              <td>
                <div className="f-w-600">{row.customer}</div>
                <div className="text-muted f-s-13">{row.phone || row.seller || ''}</div>
              </td>
              <td className="f-w-600">{fmt(row.amount)} so'm</td>
              <td><span className="badge text-light-secondary">{row.status || '—'}</span></td>
              <td className="text-muted">{row.date || '—'}</td>
              <td className="text-end">
                {row.url ? <a className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" href={row.url} title="Buyurtmani ochish"><i className="ti ti-eye"></i></a> : null}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
