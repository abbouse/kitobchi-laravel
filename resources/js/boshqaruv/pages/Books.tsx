import { useEffect, useState } from 'react';
import { PageCrumbs } from '../Layout';
import type { FormEvent, ReactNode } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';
import ImageGalleryEditor from '../components/ImageGalleryEditor';
import ModerationRejectModal from '../components/ModerationRejectModal';
import { EmptyState, MiniStat, StatWidget } from '../components/Axelit';
import { tiIcon } from '../utils/icons';
import FormAction from '../components/FormAction';
import { CoverInputs, EditionFields, EditionPicker, type PickedEdition } from '../components/CatalogFields';

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
    id?: number;
    name?: string;
    phone?: string;
    status?: string;
    verified?: boolean;
    hidden?: boolean;
    url?: string;
    booksUrl?: string;
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
  editionId?: number | null;
  catalogUrl?: string | null;
  editionStatus?: string | null;
  editionVerified?: boolean;
  editionOffers?: number;
  submissionsUrl?: string;
  featured?: boolean;
  archived?: boolean;
  archivedAt?: string | null;
  archiveUrl?: string;
  restoreUrl?: string;
}

interface OptionItem { id: number; name: string }

const SpecItem = ({ label, value }: { label: string; value?: ReactNode }) => (
  <div className="col-sm-6 col-md-4">
    <p className="mb-1 f-s-12 text-secondary">{label}</p>
    <div className="f-w-600 f-s-14 text-dark text-break">{value || '—'}</div>
  </div>
);

const statusBadge = (status?: number, archived?: boolean): [string, string, string] => {
  if (archived) return ["O'chirilgan (Arxiv)", 'bg-light-danger text-danger', 'ti-trash'];
  if (status === 1) return ['Faol', 'bg-light-success text-success', 'ti-circle-check'];
  if (status === 2) return ['Rad etilgan', 'bg-light-danger text-danger', 'ti-circle-x'];
  return ['Moderatsiya', 'bg-light-warning text-warning', 'ti-clock'];
};

export default function Books() {
  const {
    books = [],
    bookPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 },
    bookCounts = {},
    bookFilters = {},
    bookFormOptions = { categories: [], publishers: [], sellers: [] },
    bookUrls
  } = usePage<{
    books?: Book[];
    bookPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    bookCounts?: Record<string, number>;
    bookFilters?: { search?: string; tab?: string; sellerId?: number | null; sellerName?: string | null };
    bookFormOptions?: { categories: OptionItem[]; publishers: OptionItem[]; sellers: OptionItem[] };
    bookUrls?: { store: string; catalogSearch: string; catalog: string };
  }>().props;

  const [picked, setPicked] = useState<PickedEdition | null>(null);
  const [showView, setShowView] = useState(false);
  const [selectedBook, setSelectedBook] = useState<Book | null>(null);
  const [search, setSearch] = useState(bookFilters.search || '');
  const [activeTab, setActiveTab] = useState(bookFilters.tab || 'active');
  const [autoOpenedSearch, setAutoOpenedSearch] = useState('');
  const [rejectTarget, setRejectTarget] = useState<Book | null>(null);
  const [copiedIsbn, setCopiedIsbn] = useState<string | null>(null);
  const [inspectorTab, setInspectorTab] = useState<'info' | 'orders' | 'ai'>('info');

  const handleOpenView = (book: Book) => {
    setSelectedBook(book);
    setInspectorTab('info');
    setShowView(true);
  };

  const copyToClipboard = (isbn: string) => {
    navigator.clipboard.writeText(isbn);
    setCopiedIsbn(isbn);
    setTimeout(() => setCopiedIsbn(null), 2000);
  };

  useEffect(() => {
    if (bookFilters.search && bookFilters.search !== autoOpenedSearch && books.length === 1 && !showView) {
      setAutoOpenedSearch(bookFilters.search);
      handleOpenView(books[0]);
    }
  }, [bookFilters.search, autoOpenedSearch, books, showView]);

  const loadBooks = (page = 1, tab = activeTab, term = search, sellerId: number | null = bookFilters.sellerId ?? null) => {
    router.get('/boshqaruv/books', { books_page: page, books_tab: tab, books_search: term, books_seller: sellerId || undefined }, { preserveState: true, preserveScroll: true, replace: true });
  };

  const handleModerate = (book: Book, status: 0 | 1 | 2, note?: string) => {
    if (!book.moderateUrl) return;
    router.patch(book.moderateUrl, { is_approved: status, note: note ?? '' }, { preserveScroll: true });
  };

  const confirmReject = (reason: string) => {
    if (rejectTarget) handleModerate(rejectTarget, 2, reason);
    setRejectTarget(null);
  };

  const submitCreate = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!bookUrls?.store) return;
    router.post(bookUrls.store, new FormData(event.currentTarget), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => setPicked(null),
    });
  };

  const archiveBook = (book: Book) => {
    if (!book.archiveUrl) return;
    if (!window.confirm(`"${book.title}" sotuvdan olinsinmi?\n\nKitob do'kondan yashiriladi, xaridorlarning savati va sevimlilaridan o'chiriladi. Oldingi to'langan buyurtmalar va moliyaviy yozuvlar saqlanadi.`)) return;
    router.delete(book.archiveUrl, { preserveScroll: true, onSuccess: () => setShowView(false) });
  };

  const restoreBook = (book: Book) => {
    if (book.restoreUrl) router.patch(book.restoreUrl, {}, { preserveScroll: true, onSuccess: () => setShowView(false) });
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
      {/* ── Page Header ── */}
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h4 className="main-title mb-0">Do'kon takliflari va mahsulotlar</h4>
            <span className="badge bg-light-primary text-primary f-s-12 b-r-8">Offers & Products</span>
          </div>
          <PageCrumbs />
          <p className="mb-0 text-secondary f-s-13">Do'konlar katalogi, narxlar, qoldiqlar va moderatsiya nazorati</p>
          {bookFilters.sellerId ? (
            <span className="badge bg-light-primary text-primary mt-2 d-inline-flex align-items-center gap-2 f-s-12">
              <i className="ti ti-building-store"></i>
              Do'kon: <b>{bookFilters.sellerName}</b>
              <button
                type="button"
                className="btn-close f-s-10"
                aria-label="Filtrni olib tashlash"
                onClick={() => loadBooks(1, activeTab, search, null)}
              ></button>
            </span>
          ) : null}
        </div>

        <div className="d-flex gap-2 flex-wrap align-items-center">
          <form className="d-flex gap-2" onSubmit={(event) => { event.preventDefault(); loadBooks(1); }}>
            <div className="position-relative" style={{ minWidth: 290 }}>
              <input
                className="form-control form-control-sm pe-4"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="ID, Kitob nomi, ISBN, Muallif yoki Seller..."
              />
              {search ? (
                <button
                  type="button"
                  className="btn btn-link btn-sm position-absolute top-50 end-0 translate-middle-y text-secondary text-decoration-none p-1 me-1"
                  onClick={() => { setSearch(''); loadBooks(1, activeTab, '', bookFilters.sellerId ?? null); }}
                >
                  <i className="ti ti-x"></i>
                </button>
              ) : null}
            </div>
            <button className="btn btn-sm btn-outline-secondary" type="submit" title="Qidirish">
              <i className="ti ti-search"></i>
            </button>
          </form>

          {bookUrls?.store ? (
            <FormAction
              label="Kitob qo'shish"
              icon="ti ti-plus"
              variant="primary"
              modalSize="lg"
              title="Do'konga kitob qo'shish"
              description="Avval katalogdan qidiring — topilsa faqat narx va qoldiq kiritiladi. Topilmasa, yangi kitob kartasi ochiladi."
              submitLabel="Do'konga qo'shish"
              onSubmit={submitCreate}
            >
              <div className="row g-3">
                <div className="col-12">
                  <label className="form-label f-w-600">Global katalogdagi kitob</label>
                  <EditionPicker searchUrl={bookUrls.catalogSearch} value={picked} onPick={setPicked} />
                </div>
                <div className="col-md-6">
                  <label className="form-label f-w-600">Do'kon (Seller)</label>
                  <select name="seller_id" className="form-select" required defaultValue="">
                    <option value="" disabled>Do'konni tanlang</option>
                    {bookFormOptions.sellers.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}
                  </select>
                </div>
                <div className="col-md-3">
                  <label className="form-label f-w-600">Narx (so'm)</label>
                  <input name="price" type="number" min={1} className="form-control" placeholder="Masalan: 45000" required />
                </div>
                <div className="col-md-3">
                  <label className="form-label f-w-600">Chegirma narxi</label>
                  <input name="discountPrice" type="number" min={0} className="form-control" placeholder="Ixtiyoriy" />
                </div>
                <div className="col-md-4">
                  <label className="form-label f-w-600">Ombor zaxirasi</label>
                  <input name="count" type="number" min={0} className="form-control" defaultValue={1} required />
                </div>
              </div>
              {!picked ? (
                <>
                  <hr />
                  <div className="alert alert-light-primary f-s-13">
                    <i className="ti ti-info-circle me-1"></i>
                    Agar kitob global katalogda mavjud bo'lmasa, quyidagi maydonlarni to'ldiring:
                  </div>
                  <EditionFields options={bookFormOptions} />
                  <div className="mt-3"><CoverInputs requireFront /></div>
                </>
              ) : null}
            </FormAction>
          ) : null}
        </div>
      </div>

      {/* ── Top KPI StatWidgets ── */}
      <div className="row">
        {[
          { key: 'pending', label: 'Moderatsiyada', val: bookCounts.pending || 0, hint: 'Ko‘rib chiqilishi kerak', color: 'rgba(var(--warning-dark), 1)' },
          { key: 'active', label: 'Faol kitoblar', val: bookCounts.active || 0, hint: 'Xaridorga ko‘rinayotganlar', color: 'rgba(var(--success), 1)' },
          { key: 'rejected', label: 'Rad etilgan', val: bookCounts.rejected || 0, hint: 'Xatolik yoki qoidalarga zid', color: 'rgba(var(--danger), 1)' },
          { key: 'all', label: 'Jami takliflar', val: bookCounts.all || 0, hint: 'Barcha seller takliflari', color: 'rgba(var(--primary), 1)' },
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

      {/* ── Main Products / Offers Table Card ── */}
      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600 mb-0">Do'kon takliflari</h5>
            <p className="mb-0 text-secondary f-s-13">{fmt(bookPagination.total)} ta kitob taklifi</p>
          </div>
          <div className="nav kc-segment">
            {[
              ['pending', 'Moderatsiya'],
              ['active', 'Faol'],
              ['rejected', 'Rad etilgan'],
              ['all', 'Barchasi'],
              ['archived', "O'chirilgan"],
            ].map(([key, label]) => (
              <div key={key} className="nav-item">
                <button
                  type="button"
                  className={`nav-link ${activeTab === key ? 'active' : ''}`}
                  onClick={() => { setActiveTab(key); loadBooks(1, key); }}
                >
                  {label}
                  <span className="badge text-light-secondary ms-2">{bookCounts[key] || 0}</span>
                </button>
              </div>
            ))}
          </div>
        </div>

        <div className="card-body">
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead>
                <tr>
                  <th style={{ width: 60 }}>Muqova</th>
                  <th>Mahsulot va Karta</th>
                  <th>Muallif va ISBN</th>
                  <th>Do'kon (Seller)</th>
                  <th>Narx va Chegirma</th>
                  <th>Ombor</th>
                  <th>Sotilgan</th>
                  <th>Holat</th>
                  <th className="text-end" style={{ width: 120 }}>Amallar</th>
                </tr>
              </thead>
              <tbody>
                {books.map((book) => {
                  const [statusText, statusChip, statusIcon] = statusBadge(book.status, book.archived);
                  const effectivePrice = book.discountPrice && book.discountPrice < book.price ? book.discountPrice : book.price;
                  const discountPercent = book.discountPrice && book.discountPrice < book.price
                    ? Math.round(((book.price - book.discountPrice) / book.price) * 100)
                    : null;

                  return (
                    <tr key={book.id}>
                      {/* Muqova */}
                      <td>
                        <div
                          className="b-r-8 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 cursor-pointer shadow-sm"
                          style={{ width: 48, height: 66, border: '1px solid rgba(0,0,0,0.06)' }}
                          onClick={() => handleOpenView(book)}
                          title="Tafsilotlarni ko'rish"
                        >
                          {book.cover && String(book.cover).startsWith('http') ? (
                            <img className="w-100 h-100 object-fit-cover" src={book.cover} alt={book.title} loading="lazy" />
                          ) : (
                            <i className="ti ti-book text-primary f-s-20"></i>
                          )}
                        </div>
                      </td>

                      {/* Mahsulot va Karta */}
                      <td>
                        <div
                          className="f-w-600 text-dark d-block text-truncate cursor-pointer hover-primary"
                          style={{ maxWidth: 280 }}
                          onClick={() => handleOpenView(book)}
                          title={book.title}
                        >
                          {book.title}
                        </div>
                        <div className="d-flex align-items-center gap-1 mt-1 flex-wrap">
                          <span className="badge bg-light-secondary text-dark f-s-11 b-r-6 font-monospace">#{book.id}</span>
                          {book.editionId ? (
                            <a
                              href={book.catalogUrl || `/boshqaruv/catalog/${book.editionId}`}
                              className="badge bg-light-primary text-primary f-s-11 b-r-6 text-decoration-none"
                              title="Global katalog kartasi"
                            >
                              <i className="ti ti-bookmarks me-1"></i>Karta #{book.editionId}
                            </a>
                          ) : null}
                          {book.featured ? (
                            <span className="badge bg-warning-subtle text-warning-emphasis f-s-11 b-r-6">
                              <i className="ti ti-crown me-1"></i>BuyBox
                            </span>
                          ) : null}
                          {book.hidden ? (
                            <span className="badge bg-light-secondary text-secondary f-s-11 b-r-6">Yashirin</span>
                          ) : null}
                          {book.category ? (
                            <span className="badge bg-light-secondary text-secondary f-s-11 b-r-6">{book.category}</span>
                          ) : null}
                        </div>
                      </td>

                      {/* Muallif va ISBN */}
                      <td>
                        <div className="text-dark f-s-13 f-w-500">{book.author || '—'}</div>
                        {book.isbn ? (
                          <div className="d-flex align-items-center gap-1 mt-1">
                            <span
                              className="badge bg-light-secondary text-dark font-monospace cursor-pointer d-inline-flex align-items-center gap-1 f-s-11"
                              onClick={() => copyToClipboard(book.isbn!)}
                              title="Nusxalash uchun bosing"
                            >
                              <i className={`ti ${copiedIsbn === book.isbn ? 'ti-check text-success' : 'ti-copy'} f-s-10`}></i>
                              {book.isbn}
                            </span>
                          </div>
                        ) : null}
                      </td>

                      {/* Do'kon (Seller) */}
                      <td>
                        <div className="d-flex align-items-center gap-2">
                          <div
                            className="b-r-22 d-flex-center bg-light-primary text-primary f-w-600 f-s-12 flex-shrink-0"
                            style={{ width: 32, height: 32 }}
                          >
                            {(book.seller?.name || 'S').charAt(0).toUpperCase()}
                          </div>
                          <div className="min-w-0">
                            {book.seller?.booksUrl ? (
                              <a
                                href={book.seller.booksUrl}
                                className="text-dark f-w-600 f-s-13 d-block text-truncate text-decoration-none hover-primary"
                                title="Do'konning barcha kitoblari"
                                style={{ maxWidth: 140 }}
                              >
                                {book.seller.name}
                              </a>
                            ) : (
                              <span className="text-dark f-w-600 f-s-13 d-block text-truncate" style={{ maxWidth: 140 }}>
                                {book.seller?.name || 'Ichki katalog'}
                              </span>
                            )}
                            {book.seller?.verified ? (
                              <small className="text-success d-inline-flex align-items-center gap-1 f-s-11">
                                <i className="ti ti-discount-check-filled"></i>Verified
                              </small>
                            ) : null}
                          </div>
                        </div>
                      </td>

                      {/* Narx va Chegirma */}
                      <td>
                        <div>
                          <strong className="text-dark f-s-14">{fmt(effectivePrice)} so'm</strong>
                          {discountPercent ? (
                            <div className="d-flex align-items-center gap-1 mt-1">
                              <span className="text-muted text-decoration-line-through f-s-12">{fmt(book.price)}</span>
                              <span className="badge bg-light-danger text-danger f-s-10 b-r-6">-{discountPercent}%</span>
                            </div>
                          ) : null}
                        </div>
                      </td>

                      {/* Ombor */}
                      <td>
                        {book.stock > 15 ? (
                          <span className="badge bg-light-success text-success f-s-12 b-r-6">
                            <i className="ti ti-check me-1"></i>{fmt(book.stock)} dona
                          </span>
                        ) : book.stock > 0 ? (
                          <span className="badge bg-light-warning text-warning f-s-12 b-r-6">
                            <i className="ti ti-alert-triangle me-1"></i>{fmt(book.stock)} dona
                          </span>
                        ) : (
                          <span className="badge bg-light-danger text-danger f-s-12 b-r-6">
                            <i className="ti ti-x me-1"></i>Tugagan
                          </span>
                        )}
                      </td>

                      {/* Sotilgan */}
                      <td>
                        <span className="f-w-600 text-dark f-s-13">{fmt(book.sold)}</span>
                        <small className="d-block text-secondary f-s-11">{fmt(book.views || 0)} ko'rish</small>
                      </td>

                      {/* Holat */}
                      <td>
                        <span className={`badge ${statusChip} d-inline-flex align-items-center gap-1 f-s-12 b-r-6`}>
                          <i className={`ti ${statusIcon} f-s-11`}></i>
                          {statusText}
                        </span>
                        {book.editionStatus === 'pending' ? (
                          <a href={book.submissionsUrl} className="badge bg-light-warning text-warning d-block mt-1 text-decoration-none f-s-11" title="Karta tekshiruvda">
                            <i className="ti ti-inbox me-1"></i>Ariza
                          </a>
                        ) : null}
                      </td>

                      {/* Amallar */}
                      <td className="text-end">
                        <div className="d-flex gap-1 justify-content-end">
                          <button
                            className="btn btn-light-primary icon-btn w-32 h-32 b-r-22"
                            onClick={() => handleOpenView(book)}
                            title="Tafsilotlar va tahrirlash"
                          >
                            <i className="ti ti-eye"></i>
                          </button>
                          {book.moderateUrl && book.status !== 1 && !book.archived ? (
                            <button
                              className="btn btn-light-success icon-btn w-32 h-32 b-r-22"
                              onClick={() => handleModerate(book, 1)}
                              title="Tasdiqlash"
                            >
                              <i className="ti ti-check"></i>
                            </button>
                          ) : null}
                          {book.moderateUrl && book.status !== 2 && !book.archived ? (
                            <button
                              className="btn btn-light-danger icon-btn w-32 h-32 b-r-22"
                              onClick={() => setRejectTarget(book)}
                              title="Rad etish"
                            >
                              <i className="ti ti-x"></i>
                            </button>
                          ) : null}
                          {book.archived ? (
                            <button
                              className="btn btn-light-success icon-btn w-32 h-32 b-r-22"
                              onClick={() => restoreBook(book)}
                              title="Arxivdan qaytarish"
                            >
                              <i className="ti ti-rotate"></i>
                            </button>
                          ) : (
                            <button
                              className="btn btn-light-secondary icon-btn w-32 h-32 b-r-22"
                              onClick={() => archiveBook(book)}
                              title="Sotuvdan olish (arxivlash)"
                            >
                              <i className="ti ti-trash"></i>
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}

                {bookPagination.total === 0 ? (
                  <tr>
                    <td className="py-5" colSpan={9}>
                      <EmptyState text="Ushbu bo'limda birorta mahsulot taklifi topilmadi" />
                    </td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>

          <PaginationControls {...bookPagination} onPageChange={(page) => loadBooks(page)} />
        </div>
      </div>

      {/* Moderation Reject Modal */}
      <ModerationRejectModal
        show={!!rejectTarget}
        itemLabel={rejectTarget?.title}
        onCancel={() => setRejectTarget(null)}
        onConfirm={confirmReject}
      />

      {/* ── Product Inspector Modal ── */}
      <Modal show={showView} onHide={() => setShowView(false)} centered size="xl" scrollable>
        <Modal.Header closeButton className="pb-2">
          <div className="d-flex align-items-center gap-2 flex-wrap min-w-0">
            <Modal.Title className="f-s-18 f-w-700 text-truncate mb-0">
              <span className="d-inline-block text-truncate" style={{ maxWidth: 650 }}>
                {selectedBook?.title}
              </span>
            </Modal.Title>
            <span className="badge bg-light-secondary text-dark font-monospace">#{selectedBook?.id}</span>
            {selectedBook?.featured ? (
              <span className="badge bg-warning-subtle text-warning-emphasis"><i className="ti ti-crown me-1"></i>BuyBox g'olibi</span>
            ) : null}
          </div>
        </Modal.Header>


        <Modal.Body className="p-4">
          {selectedBook ? (
            <div>
              {/* Hero Banner: Cover + Core Info + Connection */}
              <div className="card bg-light-primary border-0 p-3 mb-4 b-r-12">
                <div className="d-flex gap-4 flex-wrap align-items-center">
                  <div
                    className="b-r-10 overflow-hidden bg-white shadow-sm flex-shrink-0 d-flex-center position-relative"
                    style={{ width: 90, height: 125, border: '1px solid rgba(0,0,0,0.08)' }}
                  >
                    {selectedBook.cover && String(selectedBook.cover).startsWith('http') ? (
                      <img className="w-100 h-100 object-fit-cover" src={selectedBook.cover} alt={selectedBook.title} />
                    ) : (
                      <i className="ti ti-book text-primary f-s-30"></i>
                    )}
                  </div>

                  <div className="flex-grow-1 min-w-0">
                    <h5 className="f-w-700 text-dark mb-1">{selectedBook.title}</h5>
                    <p className="text-secondary mb-2 f-s-14">
                      Muallif: <b className="text-dark">{selectedBook.author || 'Noma\'lum'}</b>
                      {selectedBook.category ? ` · Kategoriya: ${selectedBook.category}` : ''}
                      {selectedBook.publisher ? ` · Nashriyot: ${selectedBook.publisher}` : ''}
                    </p>

                    <div className="d-flex gap-2 flex-wrap align-items-center">
                      {selectedBook.catalogUrl ? (
                        <a
                          href={selectedBook.catalogUrl}
                          className="badge bg-primary text-white text-decoration-none d-inline-flex align-items-center gap-1 p-2 b-r-6"
                        >
                          <i className="ti ti-bookmarks"></i>
                          Katalog kartasi #{selectedBook.editionId}
                          <i className="ti ti-arrow-right ms-1"></i>
                        </a>
                      ) : (
                        <span className="badge bg-warning-subtle text-warning-emphasis p-2 b-r-6">
                          <i className="ti ti-alert-triangle me-1"></i>Katalogga ulanmagan
                        </span>
                      )}

                      <span className={`badge ${selectedBook.active ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary'} p-2 b-r-6`}>
                        {selectedBook.active ? 'Do\'konda faol' : 'Nofaol'}
                      </span>

                      {selectedBook.hidden ? (
                        <span className="badge bg-light-secondary text-secondary p-2 b-r-6">Yashirilgan</span>
                      ) : null}
                    </div>
                  </div>

                  {/* Seller Mini Cardlet */}
                  <div className="bg-white p-3 b-r-10 shadow-sm" style={{ minWidth: 220 }}>
                    <div className="text-secondary f-s-12 mb-1">Sotuvchi do'kon</div>
                    <div className="d-flex align-items-center gap-2">
                      <div className="b-r-22 d-flex-center bg-light-primary text-primary f-w-600 f-s-14" style={{ width: 36, height: 36 }}>
                        {(selectedBook.seller?.name || 'S').charAt(0).toUpperCase()}
                      </div>
                      <div>
                        <div className="f-w-600 f-s-14 text-dark">{selectedBook.seller?.name || 'Ichki katalog'}</div>
                        <small className="text-secondary">{selectedBook.seller?.phone || 'Telefon yo\'q'}</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* 4 Core Metrics */}
              <div className="row g-3 mb-4">
                <div className="col-sm-6 col-xl-3">
                  <MiniStat
                    icon={tiIcon('ti-cash')}
                    label="Amaldagi narx"
                    value={`${fmt(selectedBook.discountPrice || selectedBook.price)} so'm`}
                  />
                </div>
                <div className="col-sm-6 col-xl-3">
                  <MiniStat
                    icon={tiIcon('ti-package')}
                    label="Ombor zaxirasi"
                    value={`${fmt(selectedBook.stock)} dona`}
                  />
                </div>
                <div className="col-sm-6 col-xl-3">
                  <MiniStat
                    icon={tiIcon('ti-shopping-bag')}
                    label="Jami sotilgan"
                    value={`${fmt(selectedBook.sold)} dona`}
                  />
                </div>
                <div className="col-sm-6 col-xl-3">
                  <MiniStat
                    icon={tiIcon('ti-trending-up')}
                    label="Jami tushum"
                    value={`${fmt(selectedBook.totalRevenue || 0)} so'm`}
                  />
                </div>
              </div>

              {/* Inspector Tabs */}
              <div className="nav kc-segment mb-3">
                <div className="nav-item">
                  <button
                    type="button"
                    className={`nav-link ${inspectorTab === 'info' ? 'active' : ''}`}
                    onClick={() => setInspectorTab('info')}
                  >
                    <i className="ti ti-list-details me-1"></i>Xususiyatlar va Tavsif
                  </button>
                </div>
                <div className="nav-item">
                  <button
                    type="button"
                    className={`nav-link ${inspectorTab === 'orders' ? 'active' : ''}`}
                    onClick={() => setInspectorTab('orders')}
                  >
                    <i className="ti ti-receipt me-1"></i>Buyurtmalar tarixi ({selectedBook.recentOrders?.length || 0})
                  </button>
                </div>
                <div className="nav-item">
                  <button
                    type="button"
                    className={`nav-link ${inspectorTab === 'ai' ? 'active' : ''}`}
                    onClick={() => setInspectorTab('ai')}
                  >
                    <i className="ti ti-sparkles me-1"></i>AI moderatsiya auditi
                  </button>
                </div>
              </div>

              {/* Tab 1: Info & Specifications */}
              {inspectorTab === 'info' ? (
                <div className="card border">
                  <div className="card-body">
                    <h6 className="f-w-600 mb-3 text-dark">Kitob xususiyatlari</h6>
                    <div className="row g-3">
                      <SpecItem label="ISBN-13" value={selectedBook.isbn} />
                      <SpecItem label="Kategoriya" value={selectedBook.category} />
                      <SpecItem label="Nashriyot" value={selectedBook.publisher} />
                      <SpecItem label="Tarjimon" value={selectedBook.translator} />
                      <SpecItem label="Til va yozuv" value={[selectedBook.lang, selectedBook.langType].filter(Boolean).join(' / ')} />
                      <SpecItem label="Muqova turi" value={selectedBook.coverType} />
                      <SpecItem label="Sahifalar soni" value={selectedBook.pages ? `${selectedBook.pages} bet` : undefined} />
                      <SpecItem label="Chop etilgan yili" value={selectedBook.year} />
                      <SpecItem label="Asl narxi" value={`${fmt(selectedBook.price)} so'm`} />
                      <SpecItem label="Chegirma narxi" value={selectedBook.discountPrice ? `${fmt(selectedBook.discountPrice)} so'm` : 'Yo\'q'} />
                      <SpecItem label="Chegirma muddati" value={selectedBook.discountExpiresAt} />
                      <SpecItem label="Ko'rishlar soni" value={fmt(selectedBook.views || 0)} />
                    </div>

                    {selectedBook.description ? (
                      <div className="mt-4 pt-3 border-top">
                        <h6 className="f-w-600 mb-2 text-dark">Kitob annotatsiyasi (Tavsifi)</h6>
                        <div className="bg-light p-3 b-r-8 text-secondary f-s-13 text-break" style={{ whiteSpace: 'pre-wrap', lineHeight: 1.6 }}>
                          {selectedBook.description}
                        </div>
                      </div>
                    ) : null}
                  </div>
                </div>
              ) : null}

              {/* Tab 2: Orders */}
              {inspectorTab === 'orders' ? (
                <div className="card border">
                  <div className="card-body">
                    <h6 className="f-w-600 mb-3 text-dark">Oxirgi buyurtmalar</h6>
                    <MiniOrdersTable rows={selectedBook.recentOrders || []} empty="Ushbu mahsulot bo'yicha buyurtmalar topilmadi" />
                    {selectedBook.sellerOrders && selectedBook.sellerOrders.length ? (
                      <div className="mt-4 pt-3 border-top">
                        <h6 className="f-w-600 mb-3 text-dark">Do'kon buyurtmalari (Seller orders)</h6>
                        <MiniOrdersTable rows={selectedBook.sellerOrders} empty="Seller order topilmadi" />
                      </div>
                    ) : null}
                  </div>
                </div>
              ) : null}

              {/* Tab 3: AI Audit */}
              {inspectorTab === 'ai' ? (
                <div className="card border">
                  <div className="card-body">
                    <h6 className="f-w-600 mb-3 text-dark">Sun'iy intellekt (AI) moderatsiya tekshiruvi</h6>
                    <div className="row g-3">
                      <SpecItem label="AI xulosasi" value={aiStatusLabel(selectedBook.aiModerationStatus)} />
                      <SpecItem label="Tekshirgan model" value={selectedBook.aiModerationModel} />
                      <SpecItem label="Tekshirilgan vaqti" value={selectedBook.aiModerationCheckedAt} />
                      <SpecItem label="Moderatsiya qarori" value={selectedBook.statusLabel} />
                    </div>
                    {selectedBook.aiModerationNote ? (
                      <div className="alert alert-light-primary mt-3 mb-0 f-s-13">
                        <p className="f-w-600 mb-1"><i className="ti ti-notes me-1"></i>AI izohi:</p>
                        <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{selectedBook.aiModerationNote}</p>
                      </div>
                    ) : null}
                  </div>
                </div>
              ) : null}
            </div>
          ) : null}
        </Modal.Body>

        <Modal.Footer className="justify-content-between flex-wrap gap-2">
          <div>
            {selectedBook?.archived ? (
              <Button variant="light-success" onClick={() => restoreBook(selectedBook)}>
                <i className="ti ti-rotate me-1"></i>Arxivdan qaytarish
              </Button>
            ) : (
              <Button variant="light-danger" onClick={() => selectedBook && archiveBook(selectedBook)}>
                <i className="ti ti-trash me-1"></i>Sotuvdan olish
              </Button>
            )}
          </div>

          <div className="d-flex gap-2 flex-wrap">
            {selectedBook?.editUrl ? (
              <FormAction
                label="Tahrirlash"
                icon="ti ti-edit"
                variant="light-primary"
                size="md"
                modalSize="lg"
                title={`Mahsulotni tahrirlash: ${selectedBook.title}`}
                onSubmit={submitEdit}
              >
                <div className="row g-3">
                  {selectedBook.editionId ? (
                    <div className="col-12">
                      <div className="alert alert-light-primary mb-0 d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div className="min-w-0">
                          <p className="f-w-600 mb-1"><i className="ti ti-lock me-1"></i>Kitob ma'lumotlari master-kartada</p>
                          <p className="mb-0 f-s-13 text-secondary">
                            Nom, muallif, muqova va tavsif barcha do'konlar uchun umumiy bo'lib, ular faqat global kartada tahrirlanadi.
                          </p>
                        </div>
                        {selectedBook.catalogUrl ? (
                          <a href={selectedBook.catalogUrl} className="btn btn-light-primary btn-sm flex-shrink-0">
                            <i className="ti ti-external-link me-1"></i>Kartaga o'tish
                          </a>
                        ) : null}
                      </div>
                    </div>
                  ) : (
                    <>
                      <div className="col-md-6"><label className="form-label f-w-600">Nomi</label><input name="name" className="form-control" defaultValue={selectedBook.title} required /></div>
                      <div className="col-md-6"><label className="form-label f-w-600">Muallif</label><input name="author" className="form-control" defaultValue={selectedBook.author} required /></div>
                      <div className="col-md-4"><label className="form-label f-w-600">Tarjimon</label><input name="translator" className="form-control" defaultValue={selectedBook.translator || ''} /></div>
                      <div className="col-md-4"><label className="form-label f-w-600">ISBN</label><input name="isbn" className="form-control" defaultValue={selectedBook.isbn || ''} /></div>
                      <div className="col-md-4"><label className="form-label f-w-600">Yil</label><input name="year" type="number" className="form-control" defaultValue={selectedBook.year || ''} /></div>
                      <div className="col-md-4"><label className="form-label f-w-600">Kategoriya</label><select name="category_id" className="form-select" defaultValue={selectedBook.categoryId || ''} required>{bookFormOptions.categories.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></div>
                      <div className="col-md-4"><label className="form-label f-w-600">Nashriyot</label><select name="publisher_id" className="form-select" defaultValue={selectedBook.publisherId || ''}><option value="">Tanlanmagan</option>{bookFormOptions.publishers.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></div>
                    </>
                  )}

                  <div className="col-md-4">
                    <label className="form-label f-w-600">Seller</label>
                    <select name="seller_id" className="form-select" defaultValue={selectedBook.sellerId || ''}>
                      <option value="">Ichki katalog</option>
                      {bookFormOptions.sellers.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}
                    </select>
                  </div>
                  <div className="col-md-4"><label className="form-label f-w-600">Narx (so'm)</label><input name="price" type="number" min={0} className="form-control" defaultValue={selectedBook.price} required /></div>
                  <div className="col-md-4"><label className="form-label f-w-600">Chegirma narxi</label><input name="discountPrice" type="number" min={0} className="form-control" defaultValue={selectedBook.discountPrice || ''} /></div>
                  <div className="col-md-4"><label className="form-label f-w-600">Chegirma muddati</label><input name="discountExpiresAt" type="datetime-local" className="form-control" defaultValue={toInputDate(selectedBook.discountExpiresAt)} /></div>
                  <div className="col-md-4"><label className="form-label f-w-600">Ombor zaxirasi</label><input name="count" type="number" min={0} className="form-control" defaultValue={selectedBook.stock} required /></div>

                  {selectedBook.editionId ? null : (
                    <>
                      <div className="col-md-3"><label className="form-label f-w-600">Til</label><input name="lang" className="form-control" defaultValue={selectedBook.lang || ''} /></div>
                      <div className="col-md-3"><label className="form-label f-w-600">Yozuv</label><input name="langType" className="form-control" defaultValue={selectedBook.langType || ''} /></div>
                      <div className="col-md-3"><label className="form-label f-w-600">Muqova</label><input name="coverType" className="form-control" defaultValue={selectedBook.coverType || ''} /></div>
                      <div className="col-md-3"><label className="form-label f-w-600">Sahifa</label><input name="pages" type="number" min={0} className="form-control" defaultValue={selectedBook.pages || ''} /></div>
                    </>
                  )}

                  <div className="col-md-4">
                    <label className="form-label f-w-600">Moderatsiya</label>
                    <select name="is_approved" className="form-select" defaultValue={selectedBook.status ?? 0}>
                      <option value="0">Moderatsiya</option>
                      <option value="1">Tasdiqlangan</option>
                      <option value="2">Rad etilgan</option>
                    </select>
                  </div>

                  <div className="col-md-8 d-flex align-items-end gap-3 flex-wrap">
                    <label className="form-check"><input name="status" value="1" className="form-check-input" type="checkbox" defaultChecked={selectedBook.active} /> <span className="form-check-label f-w-500">Faol</span></label>
                    <label className="form-check"><input name="is_hidden" value="1" className="form-check-input" type="checkbox" defaultChecked={selectedBook.hidden} /> <span className="form-check-label f-w-500">Yashirish</span></label>
                    <label className="form-check"><input name="recommended" value="1" className="form-check-input" type="checkbox" defaultChecked={selectedBook.recommended} /> <span className="form-check-label f-w-500">Tavsiya</span></label>
                  </div>

                  {selectedBook.editionId ? null : (
                    <>
                      <div className="col-12">
                        <label className="form-label f-w-600">Rasmlar galereyasi</label>
                        <ImageGalleryEditor key={selectedBook.id} images={selectedBook.rawImages || selectedBook.images || []} />
                      </div>
                      <div className="col-12">
                        <label className="form-label f-w-600">Tavsif</label>
                        <textarea name="description" className="form-control" rows={4} defaultValue={selectedBook.description || ''} />
                      </div>
                    </>
                  )}
                </div>
              </FormAction>
            ) : null}

            {selectedBook?.moderateUrl && !selectedBook.archived ? (
              <>
                <Button variant="outline-danger" onClick={() => setRejectTarget(selectedBook)}>
                  Rad etish
                </Button>
                {selectedBook.status !== 1 ? (
                  <Button variant="primary" className="btn-primary" onClick={() => handleModerate(selectedBook, 1)}>
                    Tasdiqlash
                  </Button>
                ) : null}
              </>
            ) : null}

            <Button variant="light-secondary" onClick={() => setShowView(false)}>
              Yopish
            </Button>
          </div>
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
  return ({
    pending: 'Navbatda',
    processing: 'Tekshirilmoqda',
    approved: 'AI tasdiqladi',
    rejected: 'AI rad etdi',
    human_review: 'Admin ko‘rigi kerak',
    failed: 'Vaqtincha xato',
    manual_approved: 'Admin tasdiqladi',
    manual_rejected: 'Admin rad etdi',
    legacy_exempt: 'Eski (moderatsiyadan chetlashtirilgan)',
  } as Record<string, string>)[value || ''] || value || 'Hali tekshirilmagan';
}

function MiniOrdersTable({ rows, empty }: { rows: MiniOrder[]; empty: string }) {
  if (!rows.length) {
    return <div className="text-muted f-s-13 py-3 text-center">{empty}</div>;
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
            <th className="text-end">Amal</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={row.id}>
              <td><span className="badge bg-light-secondary text-dark font-monospace">#{row.id}</span></td>
              <td>
                <div className="f-w-600 f-s-13">{row.customer}</div>
                <div className="text-muted f-s-12">{row.phone || row.seller || ''}</div>
              </td>
              <td className="f-w-600">{fmt(row.amount)} so'm</td>
              <td><span className="badge bg-light-secondary text-dark f-s-11">{row.status || '—'}</span></td>
              <td className="text-muted f-s-12">{row.date || '—'}</td>
              <td className="text-end">
                {row.url ? (
                  <a className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" href={row.url} title="Buyurtmani ochish">
                    <i className="ti ti-arrow-right"></i>
                  </a>
                ) : null}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

