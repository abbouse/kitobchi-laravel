import { useEffect, useState } from 'react';
import { PageCrumbs } from '../Layout';
import type { FormEvent, ReactNode } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';
import ImageGalleryEditor from '../components/ImageGalleryEditor';
import ModerationRejectModal from '../components/ModerationRejectModal';
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
  <div>
    <dt>{label}</dt>
    <dd>{value || '—'}</dd>
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

  const BOOK_TABS: Array<[string, string]> = [
    ['pending', 'Moderatsiya'],
    ['active', 'Faol'],
    ['rejected', 'Rad etilgan'],
    ['archived', "O'chirilgan"],
    ['all', 'Barchasi'],
  ];

  return (
    <div>
      {/* ── Sarlavha ── */}
      <div className="kb-page-head">
        <div>
          <h4 className="main-title mb-0">Do‘kon kitoblari</h4>
          <PageCrumbs />
          <p className="mb-0 text-secondary">Do‘konlar sotayotgan kitoblar — narx, qoldiq va moderatsiya. Kitob ma’lumotlari «Global katalog»da</p>
        </div>
        <div className="kb-page-actions">
          {bookUrls?.store ? (
            <FormAction
              label="Kitob qo'shish"
              icon="ti ti-plus"
              variant="primary"
              size="md"
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
                  <label className="form-label f-w-600">Do'kon</label>
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

      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
          <div>
            <h5 className="f-w-600 mb-0">Takliflar</h5>
            <p className="mb-0 text-secondary f-s-13">{fmt(bookPagination.total)} ta topildi</p>
          </div>
          <form className="kb-toolbar" onSubmit={(event) => { event.preventDefault(); loadBooks(1); }}>
            <div className="app-form app-icon-form position-relative">
              <i className="ti ti-search"></i>
              <input
                className="form-control form-control-sm"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Nomi, ISBN, muallif, do'kon yoki ID"
                aria-label="Taklif qidirish"
              />
            </div>
            {search ? (
              <button type="button" className="btn btn-sm btn-light-secondary" onClick={() => { setSearch(''); loadBooks(1, activeTab, '', bookFilters.sellerId ?? null); }}>Tozalash</button>
            ) : null}
          </form>
        </div>

        <div className="card-body">
          <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div className="nav kc-segment">
              {BOOK_TABS.map(([key, text]) => (
                <div key={key} className="nav-item">
                  <button
                    type="button"
                    className={`nav-link ${activeTab === key ? 'active' : ''}`}
                    onClick={() => { setActiveTab(key); loadBooks(1, key); }}
                  >
                    {text}
                    <span className="kb-tab-count">{fmt(bookCounts[key] || 0)}</span>
                  </button>
                </div>
              ))}
            </div>
            {bookFilters.sellerId ? (
              <span className="kb-filter-chip">
                <i className="ti ti-building-store"></i>
                {bookFilters.sellerName}
                <button type="button" aria-label="Do'kon filtrini olib tashlash" onClick={() => loadBooks(1, activeTab, search, null)}><i className="ti ti-x"></i></button>
              </span>
            ) : null}
          </div>

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0 kb-catalog-table">
              <thead>
                <tr>
                  <th>Kitob</th>
                  <th>Do‘kon</th>
                  <th className="text-end">Narx</th>
                  <th className="text-end">Qoldiq</th>
                  <th className="text-end">Sotilgan</th>
                  <th>Holat</th>
                  <th className="text-end" style={{ width: 130 }}></th>
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
                      <td>
                        <div className="d-flex align-items-center gap-3 min-w-0">
                          <button type="button" className="kb-cover-mini p-0" onClick={() => handleOpenView(book)} title="Tafsilotlar" aria-label="Tafsilotlar">
                            {book.cover && String(book.cover).startsWith('http') ? <img src={book.cover} alt="" loading="lazy" /> : <i className="ti ti-book"></i>}
                          </button>
                          <div className="min-w-0">
                            <button type="button" className="btn btn-link p-0 f-w-600 text-dark d-block text-truncate text-start kb-catalog-title" onClick={() => handleOpenView(book)} title={book.title}>
                              {book.title}
                            </button>
                            <div className="f-s-12 text-secondary d-flex align-items-center gap-1 flex-wrap">
                              <span className="text-truncate" style={{ maxWidth: 220 }}>{book.author || 'Muallif noma’lum'}</span>
                              {book.isbn ? <>·<button type="button" className="kb-isbn f-s-12" onClick={() => copyToClipboard(book.isbn!)} title="ISBN nusxalash">{book.isbn}<i className={`ti ${copiedIsbn === book.isbn ? 'ti-check text-success' : 'ti-copy'}`}></i></button></> : null}
                            </div>
                            <div className="d-flex align-items-center gap-1 mt-1 flex-wrap">
                              {book.editionId ? (
                                <a href={book.catalogUrl || `/boshqaruv/catalog/${book.editionId}`} className="kb-mini-chip" title="Global katalog kartasi">Karta #{book.editionId}</a>
                              ) : <span className="kb-mini-chip is-warn">Kartasiz</span>}
                              {book.featured ? <span className="kb-mini-chip is-gold"><i className="ti ti-crown"></i>BuyBox</span> : null}
                              {book.hidden ? <span className="kb-mini-chip">Yashirin</span> : null}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td>
                        {book.seller?.booksUrl ? (
                          <a href={book.seller.booksUrl} className="text-dark f-w-500 d-inline-block text-truncate" style={{ maxWidth: 160 }} title="Do'konning barcha kitoblari">{book.seller.name}</a>
                        ) : (
                          <span className="text-dark f-w-500 d-inline-block text-truncate" style={{ maxWidth: 160 }}>{book.seller?.name || 'Ichki katalog'}</span>
                        )}
                        {book.seller?.verified ? <i className="ti ti-discount-check-filled text-primary ms-1" title="Tasdiqlangan do'kon"></i> : null}
                      </td>
                      <td className="text-end text-nowrap">
                        <div className="f-w-600 text-dark">{fmt(effectivePrice)} so‘m</div>
                        {discountPercent ? <div className="f-s-12"><span className="text-muted text-decoration-line-through">{fmt(book.price)}</span> <span className="text-danger">−{discountPercent}%</span></div> : null}
                      </td>
                      <td className="text-end text-nowrap">
                        <span className={book.stock === 0 ? 'text-danger f-w-600' : book.stock <= 15 ? 'text-warning-dark f-w-600' : 'text-dark'}>
                          {book.stock === 0 ? 'Tugagan' : `${fmt(book.stock)} dona`}
                        </span>
                      </td>
                      <td className="text-end text-nowrap">
                        <div className="text-dark">{fmt(book.sold)}</div>
                        <div className="f-s-12 text-muted">{fmt(book.views || 0)} ko‘rish</div>
                      </td>
                      <td>
                        <span className={`badge ${statusChip} d-inline-flex align-items-center gap-1`}>
                          <i className={`ti ${statusIcon}`}></i>{statusText}
                        </span>
                        {book.editionStatus === 'pending' ? (
                          <a href={book.submissionsUrl} className="d-block f-s-12 text-warning-dark mt-1" title="Karta tekshiruvda">Karta tekshiruvda</a>
                        ) : null}
                      </td>
                      <td className="text-end text-nowrap">
                        {book.moderateUrl && book.status !== 1 && !book.archived ? (
                          <button type="button" className="btn btn-light-success icon-btn w-30 h-30 b-r-8 me-1" onClick={() => handleModerate(book, 1)} title="Tasdiqlash" aria-label="Tasdiqlash"><i className="ti ti-check"></i></button>
                        ) : null}
                        {book.moderateUrl && !book.status && !book.archived ? (
                          <button type="button" className="btn btn-light-danger icon-btn w-30 h-30 b-r-8 me-1" onClick={() => setRejectTarget(book)} title="Rad etish" aria-label="Rad etish"><i className="ti ti-x"></i></button>
                        ) : null}
                        {book.archived ? (
                          <button type="button" className="btn btn-light-success icon-btn w-30 h-30 b-r-8 me-1" onClick={() => restoreBook(book)} title="Arxivdan qaytarish" aria-label="Arxivdan qaytarish"><i className="ti ti-rotate"></i></button>
                        ) : null}
                        <button type="button" className="btn btn-light-primary icon-btn w-30 h-30 b-r-8" onClick={() => handleOpenView(book)} title="Tafsilotlar" aria-label="Tafsilotlar"><i className="ti ti-chevron-right"></i></button>
                      </td>
                    </tr>
                  );
                })}

                {bookPagination.total === 0 ? (
                  <tr><td className="text-center py-5" colSpan={7}>Bu bo‘limda taklif topilmadi</td></tr>
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
      <Modal show={showView} onHide={() => setShowView(false)} centered size="lg" scrollable>
        <Modal.Header closeButton>
          <Modal.Title className="f-s-18 f-w-600 text-truncate">Taklif #{selectedBook?.id}</Modal.Title>
        </Modal.Header>


        <Modal.Body>
          {selectedBook ? (
            <div>
              {/* Kitob va do'kon — bir qatorda */}
              <div className="d-flex gap-3 align-items-start mb-3">
                <span className="kb-cover-mini" style={{ width: 64, height: 90 }}>
                  {selectedBook.cover && String(selectedBook.cover).startsWith('http') ? <img src={selectedBook.cover} alt="" /> : <i className="ti ti-book f-s-24"></i>}
                </span>
                <div className="min-w-0 flex-grow-1">
                  <h5 className="f-w-600 text-dark mb-1 text-break">{selectedBook.title}</h5>
                  <div className="f-s-13 text-secondary mb-2">
                    {selectedBook.author || 'Muallif noma’lum'}
                    {selectedBook.publisher ? ` · ${selectedBook.publisher}` : ''}
                    {' · '}<span className="text-dark f-w-500">{selectedBook.seller?.name || 'Ichki katalog'}</span>
                    {selectedBook.seller?.phone ? ` · ${selectedBook.seller.phone}` : ''}
                  </div>
                  <div className="d-flex gap-1 flex-wrap align-items-center">
                    {(() => { const [t, c, i] = statusBadge(selectedBook.status, selectedBook.archived); return <span className={`badge ${c}`}><i className={`ti ${i}`}></i> {t}</span>; })()}
                    {selectedBook.catalogUrl
                      ? <a href={selectedBook.catalogUrl} className="kb-mini-chip">Karta #{selectedBook.editionId} <i className="ti ti-arrow-up-right"></i></a>
                      : <span className="kb-mini-chip is-warn">Katalogga ulanmagan</span>}
                    {selectedBook.featured ? <span className="kb-mini-chip is-gold"><i className="ti ti-crown"></i>BuyBox</span> : null}
                    {!selectedBook.active ? <span className="kb-mini-chip">Do‘konda nofaol</span> : null}
                    {selectedBook.hidden ? <span className="kb-mini-chip">Yashirin</span> : null}
                  </div>
                </div>
              </div>

              <div className="kb-metrics kb-metrics-boxed mb-3">
                <div><span>Narx</span><b>{fmt(selectedBook.discountPrice || selectedBook.price)} so‘m</b>{selectedBook.discountPrice ? <small className="text-decoration-line-through">{fmt(selectedBook.price)} so‘m</small> : <small>chegirmasiz</small>}</div>
                <div><span>Qoldiq</span><b>{fmt(selectedBook.stock)} dona</b><small>{fmt(selectedBook.views || 0)} ko‘rish</small></div>
                <div><span>Sotilgan</span><b>{fmt(selectedBook.sold)} dona</b><small>umumiy</small></div>
                <div><span>Tushum</span><b>{fmt(selectedBook.totalRevenue || 0)} so‘m</b><small>umumiy</small></div>
              </div>

              <div className="nav kc-segment mb-3">
                {([
                  ['info', 'Ma’lumot', null],
                  ['orders', 'Buyurtmalar', selectedBook.recentOrders?.length || 0],
                  ['ai', 'AI tekshiruvi', null],
                ] as const).map(([key, text, count]) => (
                  <div className="nav-item" key={key}>
                    <button type="button" className={`nav-link ${inspectorTab === key ? 'active' : ''}`} onClick={() => setInspectorTab(key)}>
                      {text}{count !== null ? <span className="kb-tab-count">{count}</span> : null}
                    </button>
                  </div>
                ))}
              </div>

              {inspectorTab === 'info' ? (
                <div>
                  <dl className="kb-specs kb-specs-2 mb-0">
                    <SpecItem label="ISBN-13" value={selectedBook.isbn ? <span className="font-monospace">{selectedBook.isbn}</span> : null} />
                    <SpecItem label="Kategoriya" value={selectedBook.category} />
                    <SpecItem label="Nashriyot" value={selectedBook.publisher} />
                    <SpecItem label="Tarjimon" value={selectedBook.translator} />
                    <SpecItem label="Til / yozuv" value={[selectedBook.lang, selectedBook.langType].filter(Boolean).join(' / ')} />
                    <SpecItem label="Muqova" value={selectedBook.coverType} />
                    <SpecItem label="Sahifalar" value={selectedBook.pages ? `${selectedBook.pages} bet` : undefined} />
                    <SpecItem label="Nashr yili" value={selectedBook.year} />
                    <SpecItem label="Chegirma muddati" value={selectedBook.discountExpiresAt} />
                  </dl>
                  {selectedBook.description ? (
                    <div className="mt-3 pt-3 border-top">
                      <div className="f-s-12 text-secondary mb-1">Annotatsiya</div>
                      <div className="text-dark f-s-13 text-break" style={{ whiteSpace: 'pre-wrap', lineHeight: 1.6 }}>{selectedBook.description}</div>
                    </div>
                  ) : null}
                </div>
              ) : null}

              {/* Tab 2: Orders */}
              {inspectorTab === 'orders' ? (
                <div>
                  <div>
                    <div className="f-s-12 text-secondary mb-2">Oxirgi buyurtmalar</div>
                    <MiniOrdersTable rows={selectedBook.recentOrders || []} empty="Ushbu mahsulot bo'yicha buyurtmalar topilmadi" />
                    {selectedBook.sellerOrders && selectedBook.sellerOrders.length ? (
                      <div className="mt-4 pt-3 border-top">
                        <div className="f-s-12 text-secondary mb-2">Do'kon buyurtmalari</div>
                        <MiniOrdersTable rows={selectedBook.sellerOrders} empty="Seller order topilmadi" />
                      </div>
                    ) : null}
                  </div>
                </div>
              ) : null}

              {/* Tab 3: AI Audit */}
              {inspectorTab === 'ai' ? (
                <div>
                  <div>
                    <dl className="kb-specs kb-specs-2 mb-0">
                      <SpecItem label="AI xulosasi" value={aiStatusLabel(selectedBook.aiModerationStatus)} />
                      <SpecItem label="Tekshirgan model" value={selectedBook.aiModerationModel} />
                      <SpecItem label="Tekshirilgan vaqti" value={selectedBook.aiModerationCheckedAt} />
                      <SpecItem label="Moderatsiya qarori" value={selectedBook.statusLabel} />
                    </dl>
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
                <i className="ti ti-rotate"></i>Arxivdan qaytarish
              </Button>
            ) : (
              <Button variant="light-danger" onClick={() => selectedBook && archiveBook(selectedBook)}>
                <i className="ti ti-archive"></i>Sotuvdan olish
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
                    <label className="form-label f-w-600">Do'kon</label>
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
            <th>Holat</th>
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

