import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';

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
  category?: string;
  publisher?: string | null;
  status?: number;
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
  showUrl?: string;
  editUrl?: string;
  moderateUrl?: string;
}

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
  const { books = [] } = usePage<{ books?: Book[] }>().props;
  const list = useMemo<Book[]>(() => books, [books]);
  const pagination = useClientPagination(list, 24);
  const [showView, setShowView] = useState(false);
  const [selectedBook, setSelectedBook] = useState<Book | null>(null);

  const handleOpenView = (book: Book) => {
    setSelectedBook(book);
    setShowView(true);
  };

  const handleModerate = (book: Book, status: 0 | 1 | 2) => {
    if (!book.moderateUrl) return;
    router.patch(book.moderateUrl, { is_approved: status }, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Kitoblar katalogi</h1>
          <p className="page-subtitle">{list.length} ta kitob</p>
        </div>
        <div className="d-flex gap-2">
          <a className="btn btn-outline-secondary" href="/boshqaruv/products">
            <i className="bi bi-funnel me-1"></i>Filtr
          </a>
        </div>
      </div>

      <div className="row g-3">
        {pagination.paginated.map((book) => (
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
                  <button className="btn btn-sm btn-light flex-fill" onClick={() => handleModerate(book, book.status ? 0 : 1)} title="Moderatsiya">
                    <i className="bi bi-shield-check"></i>
                  </button>
                ) : null}
              </div>
            </div>
          </div>
        ))}
      </div>
      <PaginationControls {...pagination} onPageChange={pagination.setPage} />

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
              </div>
            </div>
          ) : null}
        </Modal.Body>
        <Modal.Footer>
          {selectedBook?.moderateUrl ? (
            <>
              <Button variant="outline-secondary" onClick={() => handleModerate(selectedBook, 0)}>Moderatsiya</Button>
              <Button variant="primary" className="btn-primary-gradient" onClick={() => handleModerate(selectedBook, 1)}>Tasdiqlash</Button>
            </>
          ) : null}
          <Button variant="light" onClick={() => setShowView(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
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
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
