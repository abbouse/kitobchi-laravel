import { useEffect, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

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
  <span className={`chip ${ok ? 'chip-success' : 'chip-gray'}`}>{ok ? yes : no}</span>
);

const Detail = ({ label, value }: { label: string; value?: ReactNode }) => (
  <div className="col-md-6">
    <div className="text-muted small">{label}</div>
    <div className="fw-semibold">{value || '—'}</div>
  </div>
);

export default function Books() {
  const { books = [], bookPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, bookCounts = {}, bookFilters = {}, bookFormOptions = { categories: [], publishers: [], sellers: [] } } = usePage<{ books?: Book[]; bookPagination?: { page: number; totalPages: number; from: number; to: number; total: number }; bookCounts?: Record<string, number>; bookFilters?: { search?: string; tab?: string }; bookFormOptions?: { categories: OptionItem[]; publishers: OptionItem[]; sellers: OptionItem[] } }>().props;
  const [showView, setShowView] = useState(false);
  const [selectedBook, setSelectedBook] = useState<Book | null>(null);
  const [search, setSearch] = useState(bookFilters.search || '');
  const [activeTab, setActiveTab] = useState(bookFilters.tab || 'pending');
  const [autoOpenedSearch, setAutoOpenedSearch] = useState('');

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

  const handleReject = (book: Book) => {
    if (!book.moderateUrl) return;
    const reason = window.prompt("Rad etish sababi (sellerga ko'rinadi):", '');
    if (reason === null) return; // admin bekor qildi
    handleModerate(book, 2, reason.trim());
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
      <div className="page-head">
        <div>
          <h1 className="page-title">Kitoblar katalogi</h1>
          <p className="page-subtitle">Moderatsiya, faol va rad etilgan kitoblarni boshqarish</p>
        </div>
        <form className="d-flex gap-2" onSubmit={(event) => { event.preventDefault(); loadBooks(1); }}>
          <input className="form-control form-control-sm" style={{ minWidth: 280 }} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="ID, nom, ISBN, muallif yoki seller" />
          <button className="btn btn-sm btn-outline-secondary"><i className="bi bi-search"></i></button>
        </form>
      </div>

      <div className="row g-3 mb-4">
        {[
          { key: 'pending', label: 'Moderatsiyada', val: bookCounts.pending || 0, hint: 'Avval ko‘rib chiqilishi kerak', color: '#f59e0b' },
          { key: 'active', label: 'Faol kitoblar', val: bookCounts.active || 0, hint: 'Xaridorga ko‘rinayotganlar', color: '#10b981' },
          { key: 'rejected', label: 'Rad etilgan', val: bookCounts.rejected || 0, hint: 'Qayta ko‘rib chiqilishi mumkin', color: '#ef4444' },
          { key: 'all', label: 'Jami katalog', val: bookCounts.all || 0, hint: 'Barcha yozuvlar', color: '#4f46e5' },
        ].map((item) => (
          <div className="col-xl-3 col-md-6" key={item.key}>
            <button
              type="button"
              className="stat-card text-start w-100 border-0"
              onClick={() => { setActiveTab(item.key); loadBooks(1, item.key); }}
              style={{ outline: activeTab === item.key ? `2px solid ${item.color}` : undefined }}
            >
              <div className="stat-label">{item.label}</div>
              <div className="stat-value">{item.val}</div>
              <div className="text-muted small mt-1">{item.hint}</div>
            </button>
          </div>
        ))}
      </div>

      <div className="card-panel mb-4">
        <div className="d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <div>
            <div className="fw-semibold">Holat bo‘yicha filter</div>
            <div className="text-muted small">Birinchi kirganda moderatsiyadagi kitoblar chiqadi. Shu blokdan boshqa holatlarga tez o‘tasiz.</div>
          </div>
          <div className="d-flex gap-2 flex-wrap">
            {[
              ['pending', 'Moderatsiya'],
              ['active', 'Faol'],
              ['rejected', 'Rad etilgan'],
              ['all', 'Barchasi'],
            ].map(([key, label]) => (
              <button
                key={key}
                type="button"
                className={`btn btn-sm ${activeTab === key ? 'btn-primary-gradient' : 'btn-outline-secondary'}`}
                onClick={() => { setActiveTab(key); loadBooks(1, key); }}
              >
                {label} <span className="ms-1 opacity-75">{bookCounts[key] || 0}</span>
              </button>
            ))}
          </div>
        </div>
      </div>

      <div className="row g-3">
        {books.map((book) => (
          <div className="col-xl-3 col-md-6" key={book.id}>
            <div className="card-panel h-100 d-flex flex-column justify-content-between">
              <div>
                <div className="d-flex gap-3">
                  <div className="book-cover-sm">
                    {book.cover && String(book.cover).startsWith('http') ? (
                      <img src={book.cover} alt={book.title} />
                    ) : (book.cover || '📕')}
                  </div>
                  <div style={{ minWidth: 0, flex: 1 }}>
                    <div className="fw-bold text-truncate" style={{ fontSize: 15 }}>{book.title}</div>
                    <div className="text-muted small mb-2 text-truncate">{book.author}</div>
                    <div className="fw-bold" style={{ color: '#4f46e5' }}>{fmt(book.price)} so'm</div>
                    <div className="d-flex gap-1 mt-2 flex-wrap">
                      <span className="chip chip-success">{book.stock} dona</span>
                      <span className="chip chip-gray">{book.category || 'Kitob'}</span>
                      <span className={`chip ${book.status === 1 ? 'chip-success' : (book.status === 2 ? 'chip-danger' : 'chip-warning')}`}>{book.statusLabel || 'Moderatsiya'}</span>
                      {book.hidden ? <span className="chip chip-danger">Yashirilgan</span> : null}
                    </div>
                  </div>
                </div>
              </div>
              <div className="d-flex gap-2 mt-3 pt-3 border-top">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => handleOpenView(book)} title="Ko'rish">
                  <i className="bi bi-eye"></i>
                </button>
                {book.moderateUrl ? (
                  <>
                    <button className="btn btn-sm btn-light flex-fill text-success" onClick={() => handleModerate(book, 1)} title="Tasdiqlash">
                      <i className="bi bi-check-lg"></i>
                    </button>
                    <button className="btn btn-sm btn-light flex-fill text-danger" onClick={() => handleReject(book)} title="Rad etish">
                      <i className="bi bi-x-lg"></i>
                    </button>
                  </>
                ) : null}
              </div>
            </div>
          </div>
        ))}
      </div>
      <PaginationControls {...bookPagination} onPageChange={(page) => loadBooks(page)} />

      <Modal show={showView} onHide={() => setShowView(false)} centered size="xl" scrollable>
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold">Kitob: {selectedBook?.title}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedBook ? (
            <div className="row g-4">
              <div className="col-lg-4">
                <div className="detail-panel text-center">
                  <div className="book-cover-lg mx-auto mb-3">
                    {selectedBook.cover ? <img src={selectedBook.cover} alt={selectedBook.title} /> : '📕'}
                  </div>
                  <h4 className="fw-bold mb-1">{selectedBook.title}</h4>
                  <div className="text-muted mb-3">{selectedBook.author}</div>
                  <div className="d-flex gap-2 justify-content-center flex-wrap">
                    {badge(Boolean(selectedBook.active), 'Aktiv', 'Nofaol')}
                    {badge(Boolean(selectedBook.status), 'Tasdiqlangan', 'Moderatsiya')}
                    {badge(!selectedBook.hidden, "Ko'rinadi", 'Yashirilgan')}
                    {selectedBook.recommended ? <span className="chip chip-info">Tavsiya</span> : null}
                  </div>
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Sotuvchi</h6>
                  {selectedBook.seller ? (
                    <>
                      <div className="fw-semibold">{selectedBook.seller.name}</div>
                      <div className="text-muted small">{selectedBook.seller.phone || 'Telefon yoq'}</div>
                      <div className="d-flex gap-2 mt-2 flex-wrap">
                        {badge(selectedBook.seller.verified, 'Verified', 'Tekshirilmagan')}
                        {badge(!selectedBook.seller.hidden, 'Aktiv shop', 'Shop yashirin')}
                      </div>
                    </>
                  ) : (
                    <div className="text-muted">Ichki katalog</div>
                  )}
                </div>
              </div>

              <div className="col-lg-8">
                <div className="row g-3 mb-3">
                  {[
                    { label: 'Narx', value: `${fmt(selectedBook.price)} so'm`, icon: 'bi-cash-stack' },
                    { label: 'Ombor', value: `${fmt(selectedBook.stock)} dona`, icon: 'bi-box-seam' },
                    { label: 'Sotilgan', value: `${fmt(selectedBook.sold)} marta`, icon: 'bi-bag-check' },
                    { label: 'Daromad', value: `${fmt(selectedBook.totalRevenue || 0)} so'm`, icon: 'bi-graph-up-arrow' },
                  ].map((item) => (
                    <div className="col-md-3 col-6" key={item.label}>
                      <div className="mini-stat">
                        <i className={`bi ${item.icon}`}></i>
                        <span>{item.label}</span>
                        <strong>{item.value}</strong>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="detail-panel">
                  <h6 className="fw-bold mb-3">Asosiy ma'lumotlar</h6>
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
                      <div className="text-muted small">Tavsif</div>
                      <div className="detail-text">{selectedBook.description}</div>
                    </div>
                  ) : null}
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">AI moderatsiya auditi</h6>
                  <div className="row g-3">
                    <Detail label="AI holati" value={aiStatusLabel(selectedBook.aiModerationStatus)} />
                    <Detail label="Tekshirgan model" value={selectedBook.aiModerationModel} />
                    <Detail label="Tekshiruv vaqti" value={selectedBook.aiModerationCheckedAt} />
                    <Detail label="Qaror" value={selectedBook.statusLabel} />
                  </div>
                  {selectedBook.aiModerationNote ? <div className="alert alert-light border mt-3 mb-0 small">{selectedBook.aiModerationNote}</div> : null}
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Savdo analitikasi</h6>
                  <div className="row g-3">
                    <Detail label="Jami sotuv" value={`${fmt(selectedBook.sold)} dona`} />
                    <Detail label="Jami mijoz" value={fmt(selectedBook.totalClients || 0)} />
                    <Detail label="Haftalik sotuv" value={`${fmt(selectedBook.totalSalesWeek || 0)} dona`} />
                    <Detail label="Jami tushum" value={`${fmt(selectedBook.totalRevenue || 0)} so'm`} />
                  </div>
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Oxirgi buyurtmalar</h6>
                  <MiniOrdersTable rows={selectedBook.recentOrders || []} empty="Bu kitob bo'yicha buyurtma topilmadi" />
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Seller orderlar</h6>
                  <MiniOrdersTable rows={selectedBook.sellerOrders || []} empty="Seller order topilmadi" />
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">Admin tahriri</h6>
                  <form className="row g-3" onSubmit={submitEdit}>
                    <div className="col-md-6"><label className="form-label small text-muted">Nomi</label><input name="name" className="form-control" defaultValue={selectedBook.title} required /></div>
                    <div className="col-md-6"><label className="form-label small text-muted">Muallif</label><input name="author" className="form-control" defaultValue={selectedBook.author} required /></div>
                    <div className="col-md-4"><label className="form-label small text-muted">Tarjimon</label><input name="translator" className="form-control" defaultValue={selectedBook.translator || ''} /></div>
                    <div className="col-md-4"><label className="form-label small text-muted">ISBN</label><input name="isbn" className="form-control" defaultValue={selectedBook.isbn || ''} /></div>
                    <div className="col-md-4"><label className="form-label small text-muted">Yil</label><input name="year" type="number" className="form-control" defaultValue={selectedBook.year || ''} /></div>
                    <div className="col-md-4"><label className="form-label small text-muted">Kategoriya</label><select name="category_id" className="form-select" defaultValue={selectedBook.categoryId || ''} required>{bookFormOptions.categories.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></div>
                    <div className="col-md-4"><label className="form-label small text-muted">Nashriyot</label><select name="publisher_id" className="form-select" defaultValue={selectedBook.publisherId || ''}><option value="">Tanlanmagan</option>{bookFormOptions.publishers.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></div>
                    <div className="col-md-4"><label className="form-label small text-muted">Seller</label><select name="seller_id" className="form-select" defaultValue={selectedBook.sellerId || ''}><option value="">Ichki katalog</option>{bookFormOptions.sellers.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></div>
                    <div className="col-md-3"><label className="form-label small text-muted">Narx</label><input name="price" type="number" min={0} className="form-control" defaultValue={selectedBook.price} required /></div>
                    <div className="col-md-3"><label className="form-label small text-muted">Chegirma narxi</label><input name="discountPrice" type="number" min={0} className="form-control" defaultValue={selectedBook.discountPrice || ''} /></div>
                    <div className="col-md-3"><label className="form-label small text-muted">Chegirma muddati</label><input name="discountExpiresAt" type="datetime-local" className="form-control" defaultValue={toInputDate(selectedBook.discountExpiresAt)} /></div>
                    <div className="col-md-3"><label className="form-label small text-muted">Ombor</label><input name="count" type="number" min={0} className="form-control" defaultValue={selectedBook.stock} required /></div>
                    <div className="col-md-3"><label className="form-label small text-muted">Til</label><input name="lang" className="form-control" defaultValue={selectedBook.lang || ''} /></div>
                    <div className="col-md-3"><label className="form-label small text-muted">Yozuv</label><input name="langType" className="form-control" defaultValue={selectedBook.langType || ''} /></div>
                    <div className="col-md-3"><label className="form-label small text-muted">Muqova</label><input name="coverType" className="form-control" defaultValue={selectedBook.coverType || ''} /></div>
                    <div className="col-md-3"><label className="form-label small text-muted">Sahifa</label><input name="pages" type="number" min={0} className="form-control" defaultValue={selectedBook.pages || ''} /></div>
                    <div className="col-md-4"><label className="form-label small text-muted">Moderatsiya</label><select name="is_approved" className="form-select" defaultValue={selectedBook.status ?? 0}><option value="0">Moderatsiya</option><option value="1">Tasdiqlangan</option><option value="2">Rad etilgan</option></select></div>
                    <div className="col-md-8 d-flex align-items-end gap-3 flex-wrap">
                      <label className="form-check"><input name="status" value="1" className="form-check-input" type="checkbox" defaultChecked={selectedBook.active} /> <span className="form-check-label">Faol</span></label>
                      <label className="form-check"><input name="is_hidden" value="1" className="form-check-input" type="checkbox" defaultChecked={selectedBook.hidden} /> <span className="form-check-label">Yashirish</span></label>
                      <label className="form-check"><input name="recommended" value="1" className="form-check-input" type="checkbox" defaultChecked={selectedBook.recommended} /> <span className="form-check-label">Tavsiya</span></label>
                    </div>
                    <div className="col-md-6"><label className="form-label small text-muted">Tavsiya muddati</label><input name="recommendedExpiresAt" type="datetime-local" className="form-control" defaultValue={toInputDate(selectedBook.recommendedExpiresAt)} /></div>
                    <div className="col-md-6"><label className="form-label small text-muted">Yangi rasmlar</label><input name="images[]" type="file" multiple accept="image/*" className="form-control" /></div>
                    <div className="col-12"><label className="form-label small text-muted">Rasmlar ro'yxati</label><textarea name="images_text" className="form-control" rows={3} defaultValue={(selectedBook.rawImages || selectedBook.images || []).join('\n')} /></div>
                    <div className="col-12"><label className="form-label small text-muted">Tavsif</label><textarea name="description" className="form-control" rows={4} defaultValue={selectedBook.description || ''} /></div>
                    <div className="col-12"><button className="btn btn-primary-gradient">Saqlash</button></div>
                  </form>
                </div>
              </div>
            </div>
          ) : null}
        </Modal.Body>
        <Modal.Footer>
          {selectedBook?.moderateUrl ? (
            <>
              <Button variant="outline-danger" onClick={() => handleReject(selectedBook)}>Rad etish</Button>
              <Button variant="outline-secondary" onClick={() => handleModerate(selectedBook, 0)}>Moderatsiyaga</Button>
              <Button variant="primary" className="btn-primary-gradient" onClick={() => handleModerate(selectedBook, 1)}>Tasdiqlash</Button>
            </>
          ) : null}
          <Button variant="light" onClick={() => setShowView(false)}>Yopish</Button>
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
  return ({ pending: 'Navbatda', processing: 'Tekshirilmoqda', approved: 'AI tasdiqladi', rejected: 'AI rad etdi', human_review: 'Admin ko‘rigi kerak', failed: 'Vaqtincha xato', manual_approved: 'Admin tasdiqladi', manual_rejected: 'Admin rad etdi' } as Record<string, string>)[value || ''] || value || 'Hali tekshirilmagan';
}

function MiniOrdersTable({ rows, empty }: { rows: MiniOrder[]; empty: string }) {
  if (!rows.length) {
    return <div className="text-muted small">{empty}</div>;
  }

  return (
    <div className="table-responsive">
      <table className="data-table compact-table">
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
                <div className="fw-semibold">{row.customer}</div>
                <div className="text-muted small">{row.phone || row.seller || ''}</div>
              </td>
              <td className="fw-semibold">{fmt(row.amount)} so'm</td>
              <td><span className="chip chip-gray">{row.status || '—'}</span></td>
              <td className="text-muted">{row.date || '—'}</td>
              <td className="text-end">
                {row.url ? <a className="btn btn-sm btn-light" href={row.url} title="Buyurtmani ochish"><i className="bi bi-eye"></i></a> : null}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
