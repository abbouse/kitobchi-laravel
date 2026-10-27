import { useMemo, useState, type FormEvent } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import PaginationControls from '../components/PaginationControls';
import FormAction from '../components/FormAction';
import Modal from '../components/AppModal';
import { CoverInputs, EditionFields, editionStatus, type OptionItem } from '../components/CatalogFields';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface EditionRow {
  id: number;
  title: string;
  author?: string | null;
  isbn?: string | null;
  publisher?: string | null;
  category?: string | null;
  cover?: string | null;
  status: string;
  verified: boolean;
  source?: string;
  offersCount: number;
  inStockOffers: number;
  minPrice?: number | null;
  deleted?: boolean;
  banned?: boolean;
  createdAt?: string;
  url: string;
  variant?: string;
}

type Props = {
  editions: EditionRow[];
  pagination: { page: number; totalPages: number; from: number; to: number; total: number };
  filters: { search?: string; tab?: string; sort?: string; stock?: string };
  counts: Record<string, number>;
  formOptions: { categories: OptionItem[]; publishers: OptionItem[]; sellers: OptionItem[] };
};

export default function Catalog() {
  const { editions = [], pagination, filters = {}, counts = {}, formOptions } = usePage<Props>().props;
  const [search, setSearch] = useState(filters.search || '');
  const [copiedIsbn, setCopiedIsbn] = useState<string | null>(null);
  const [batchLoading, setBatchLoading] = useState(false);
  const [fixingId, setFixingId] = useState<number | null>(null);
  const tab = filters.tab || 'all';
  const sort = filters.sort || 'new';
  const stock = filters.stock || '';
  const [selected, setSelected] = useState<number[]>([]);
  // Sotuvdan olish oynasi: bitta karta yoki tanlanganlar
  const [banTarget, setBanTarget] = useState<{ ids: number[]; title: string; offers: number } | null>(null);

  const load = (params: Record<string, string | number>) => {
    setSelected([]);
    router.get('/boshqaruv/catalog', { search, tab, sort, stock, ...params }, { preserveState: true, preserveScroll: true, replace: true });
  };

  const pageIds = useMemo(() => editions.map((e) => e.id), [editions]);
  const allChecked = pageIds.length > 0 && pageIds.every((id) => selected.includes(id));
  const toggle = (id: number) => setSelected((cur) => (cur.includes(id) ? cur.filter((x) => x !== id) : [...cur, id]));
  const toggleAll = () => setSelected(allChecked ? [] : pageIds);
  const selectedRows = editions.filter((e) => selected.includes(e.id));
  const selectedBanned = selectedRows.filter((e) => e.banned || e.deleted).length;

  const bulk = (action: 'verify' | 'ban' | 'unban', ids: number[], reason?: string) => {
    router.post('/boshqaruv/catalog/bulk', { action, ids, reason: reason || '' }, {
      preserveScroll: true,
      onSuccess: () => { setSelected([]); setBanTarget(null); },
    });
  };

  const unbanOne = (edition: EditionRow) => {
    if (!confirm(`"${edition.title}" sotuvga qaytarilsinmi?`)) return;
    bulk('unban', [edition.id]);
  };

  const verifyOne = (edition: EditionRow) => router.post(`/boshqaruv/catalog/${edition.id}/verify`, {}, { preserveScroll: true });

  const copyToClipboard = (isbn: string) => {
    navigator.clipboard.writeText(isbn);
    setCopiedIsbn(isbn);
    setTimeout(() => setCopiedIsbn(null), 2000);
  };

  const handleAutoFixBatch = async () => {
    if (!confirm('Muammoli kitoblarning muqovalarini Book.uz va Asaxiy orqali avtomatik qidirib o\'rnatilsinmi?')) {
      return;
    }
    setBatchLoading(true);
    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content
        || ((window as unknown as { csrfToken?: string }).csrfToken || '');
      const res = await fetch('/boshqaruv/catalog/auto-fix-batch', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ limit: 30 }),
      });
      const data = await res.json();
      alert(data.message || 'Muqovalar yangilandi!');
      router.reload({ preserveScroll: true });
    } catch {
      alert('Xatolik yuz berdi');
    } finally {
      setBatchLoading(false);
    }
  };

  const handleSingleAutoCover = async (editionId: number) => {
    setFixingId(editionId);
    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content
        || ((window as unknown as { csrfToken?: string }).csrfToken || '');
      const res = await fetch(`/boshqaruv/catalog/${editionId}/auto-cover`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        router.reload({ preserveScroll: true });
      } else {
        alert(data.message || 'Muqova topilmadi');
      }
    } catch {
      alert('Xatolik yuz berdi');
    } finally {
      setFixingId(null);
    }
  };

  const submitCreate = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    router.post('/boshqaruv/catalog', new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true });
  };

  const TABS: Array<[string, string]> = [
    ['all', 'Barchasi'],
    ['unverified', 'Tasdiqlanmagan'],
    ['pending', 'Tekshiruvda'],
    ['no_cover', 'Muqovasiz'],
    ['duplicates', 'Dublikatlar'],
    ['rejected', 'Taqiqlangan'],
    ['deleted', "O'chirilgan"],
  ];

  return (
    <div>
      {/* ── Sarlavha ── */}
      <div className="kb-page-head">
        <div>
          <h4 className="main-title mb-0">Global katalog</h4>
          <PageCrumbs />
          <p className="mb-0 text-secondary">Har bir nashr uchun bitta karta — do‘konlar unga o‘z narxi va qoldig‘i bilan ulanadi</p>
        </div>
        <div className="kb-page-actions">
          <Link href="/boshqaruv/catalog/submissions" className="btn btn-light-secondary">
            <i className="ti ti-inbox"></i>Arizalar
            {(counts.submissions || 0) > 0 ? <span className="badge bg-danger rounded-pill">{fmt(counts.submissions)}</span> : null}
          </Link>
          <FormAction
            label="Yangi karta"
            icon="ti ti-plus"
            variant="primary"
            size="md"
            modalSize="lg"
            title="Katalogga yangi kitob kartasi"
            description="ISBN-13 avtomatik tekshiriladi. Old va orqa muqova rasmini yuklash tavsiya etiladi."
            submitLabel="Kartani saqlash"
            onSubmit={submitCreate}
          >
            <EditionFields options={formOptions} />
            <hr />
            <CoverInputs requireFront />
          </FormAction>
        </div>
      </div>

      <div className="card">
        {/* ── Karta sarlavhasi: nom + qidiruv ── */}
        <div className="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
          <div>
            <h5 className="f-w-600 mb-0">Kitob kartalari</h5>
            <p className="mb-0 text-secondary f-s-13">
              {fmt(pagination.total)} ta topildi
              {(counts.unlinked || 0) > 0 ? <> · <span className="text-warning-dark">{fmt(counts.unlinked)} ta taklif kartaga ulanmagan</span></> : null}
            </p>
          </div>
          <form className="kb-toolbar" onSubmit={(event) => { event.preventDefault(); load({ page: 1 }); }}>
            <div className="app-form app-icon-form position-relative">
              <i className="ti ti-search"></i>
              <input
                className="form-control form-control-sm"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Nomi, muallif, ISBN yoki ID"
                aria-label="Katalogdan qidirish"
              />
            </div>
            {search ? (
              <button type="button" className="btn btn-sm btn-light-secondary" onClick={() => { setSearch(''); load({ search: '', page: 1 }); }}>Tozalash</button>
            ) : null}
          </form>
        </div>

        <div className="card-body">
          {/* ── Filtrlar: holat (segment) + sotuv va saralash (select) ── */}
          <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div className="nav kc-segment">
              {TABS.map(([key, text]) => (
                <div className="nav-item" key={key}>
                  <button type="button" className={`nav-link ${tab === key ? 'active' : ''}`} onClick={() => load({ tab: key, page: 1 })}>
                    {text}
                    {counts[key] !== undefined ? <span className="kb-tab-count">{fmt(counts[key])}</span> : null}
                  </button>
                </div>
              ))}
            </div>
            <div className="kb-toolbar">
              <select className="form-select form-select-sm" value={stock} onChange={(e) => load({ stock: e.target.value, page: 1 })} aria-label="Sotuv holati">
                <option value="">Sotuv: hammasi</option>
                <option value="in">Sotuvda bor</option>
                <option value="out">Omborda yo‘q</option>
                <option value="no_offers">Taklifsiz</option>
              </select>
              <select className="form-select form-select-sm" value={sort} onChange={(e) => load({ sort: e.target.value, page: 1 })} disabled={tab === 'duplicates'} aria-label="Saralash">
                <option value="new">Avval yangilari</option>
                <option value="old">Avval eskilari</option>
                <option value="offers">Ko‘p taklifli</option>
                <option value="price">Arzon narx</option>
                <option value="title">Nomi (A–Z)</option>
              </select>
              {tab === 'no_cover' ? (
                <button type="button" className="btn btn-sm btn-light-primary" onClick={handleAutoFixBatch} disabled={batchLoading} title="Book.uz va Asaxiy'dan muqovasi yo'q kitoblarga muqova topadi (30 tagacha)">
                  <i className={`ti ${batchLoading ? 'ti-loader-2' : 'ti-wand'}`}></i>{batchLoading ? 'Qidirilmoqda…' : 'Muqovalarni topish'}
                </button>
              ) : null}
            </div>
          </div>

          {selected.length ? (
            <div className="kb-bulkbar">
              <span className="f-w-600"><i className="ti ti-checks"></i> {selected.length} ta tanlandi</span>
              <div className="d-flex gap-2 flex-wrap">
                <button type="button" className="btn btn-sm btn-success" onClick={() => bulk('verify', selected)}>
                  <i className="ti ti-circle-check"></i>Tasdiqlash
                </button>
                {selectedBanned ? (
                  <button type="button" className="btn btn-sm btn-light-success" onClick={() => confirm(`${selectedBanned} ta karta sotuvga qaytarilsinmi?`) && bulk('unban', selectedRows.filter((e) => e.banned || e.deleted).map((e) => e.id))}>
                    <i className="ti ti-rotate-clockwise"></i>Sotuvga qaytarish
                  </button>
                ) : null}
                {selectedRows.length - selectedBanned > 0 ? (
                  <button type="button" className="btn btn-sm btn-light-danger" onClick={() => {
                    const rows = selectedRows.filter((e) => !e.banned && !e.deleted);
                    setBanTarget({ ids: rows.map((e) => e.id), title: `${rows.length} ta karta`, offers: rows.reduce((sum, e) => sum + e.offersCount, 0) });
                  }}>
                    <i className="ti ti-ban"></i>Sotuvdan olish
                  </button>
                ) : null}
                <button type="button" className="btn btn-sm btn-light-secondary" onClick={() => setSelected([])}>Bekor</button>
              </div>
            </div>
          ) : null}

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0 kb-catalog-table">
              <thead>
                <tr>
                  <th style={{ width: 36 }}>
                    <input type="checkbox" className="form-check-input" checked={allChecked} onChange={toggleAll} disabled={!pageIds.length} aria-label="Hammasini tanlash" />
                  </th>
                  <th>Kitob</th>
                  <th>ISBN</th>
                  <th className="text-end">Takliflar</th>
                  <th className="text-end">Eng arzon</th>
                  <th>Holat</th>
                  <th className="text-end" style={{ width: 90 }}></th>
                </tr>
              </thead>
              <tbody>
                {editions.map((edition) => {
                  const [statusText, statusChip] = editionStatus(edition.status, edition.verified);
                  const badCover = !edition.cover || edition.cover.includes('1790422172657') || edition.cover.includes('Screenshot_2026_09_26_072402');
                  const state = edition.banned ? ['Taqiqlangan', 'text-light-danger'] : edition.deleted ? ["O'chirilgan", 'text-light-secondary'] : [statusText, statusChip];

                  return (
                    <tr key={edition.id} className={selected.includes(edition.id) ? 'table-active' : ''}>
                      <td>
                        <input type="checkbox" className="form-check-input" checked={selected.includes(edition.id)} onChange={() => toggle(edition.id)} aria-label={`${edition.title} tanlash`} />
                      </td>
                      <td>
                        <div className="d-flex align-items-center gap-3 min-w-0">
                          <Link href={edition.url} className="kb-cover-mini">
                            {edition.cover ? <img src={edition.cover} alt="" loading="lazy" /> : <i className="ti ti-book"></i>}
                          </Link>
                          <div className="min-w-0">
                            <Link href={edition.url} className="f-w-600 text-dark d-block text-truncate kb-catalog-title" title={edition.title}>{edition.title}</Link>
                            <div className="f-s-12 text-secondary text-truncate">
                              {edition.author || 'Muallif noma’lum'}{edition.publisher ? ` · ${edition.publisher}` : ''}{edition.variant ? ` · ${edition.variant}` : ''}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td>
                        {edition.isbn ? (
                          <button type="button" className="kb-isbn" onClick={() => copyToClipboard(edition.isbn!)} title="Nusxalash">
                            {edition.isbn}
                            <i className={`ti ${copiedIsbn === edition.isbn ? 'ti-check text-success' : 'ti-copy'}`}></i>
                          </button>
                        ) : <span className="text-muted f-s-13">—</span>}
                      </td>
                      <td className="text-end text-nowrap">
                        <span className="f-w-600 text-dark">{edition.offersCount}</span>
                        <div className={`f-s-12 ${edition.inStockOffers > 0 ? 'text-success' : 'text-muted'}`}>
                          {edition.offersCount ? (edition.inStockOffers > 0 ? `${edition.inStockOffers} tasi omborda` : 'omborda yo‘q') : 'taklif yo‘q'}
                        </div>
                      </td>
                      <td className="text-end text-nowrap">
                        {edition.minPrice ? <span className="f-w-600 text-dark">{fmt(edition.minPrice)} so‘m</span> : <span className="text-muted">—</span>}
                      </td>
                      <td><span className={`badge ${state[1]}`}>{state[0]}</span></td>
                      <td className="text-end text-nowrap">
                        {badCover && !edition.banned && !edition.deleted ? (
                          <button type="button" className="btn btn-light-secondary icon-btn w-30 h-30 b-r-8 me-1" title="Muqovani internetdan topish" aria-label="Muqovani topish" onClick={() => handleSingleAutoCover(edition.id)} disabled={fixingId === edition.id}>
                            {fixingId === edition.id ? <span className="spinner-border spinner-border-sm" style={{ width: 12, height: 12 }} /> : <i className="ti ti-wand"></i>}
                          </button>
                        ) : null}
                        {!edition.verified && !edition.banned && !edition.deleted && edition.status !== 'merged' ? (
                          <button type="button" className="btn btn-light-success icon-btn w-30 h-30 b-r-8 me-1" title="Tasdiqlash" aria-label="Tasdiqlash" onClick={() => verifyOne(edition)}>
                            <i className="ti ti-check"></i>
                          </button>
                        ) : null}
                        {edition.banned || edition.deleted ? (
                          <button type="button" className="btn btn-light-success icon-btn w-30 h-30 b-r-8 me-1" title="Sotuvga qaytarish" aria-label="Sotuvga qaytarish" onClick={() => unbanOne(edition)}>
                            <i className="ti ti-rotate-clockwise"></i>
                          </button>
                        ) : null}
                        <Link href={edition.url} className="btn btn-light-primary icon-btn w-30 h-30 b-r-8" title="Kartani ochish" aria-label="Kartani ochish">
                          <i className="ti ti-chevron-right"></i>
                        </Link>
                      </td>
                    </tr>
                  );
                })}

                {!editions.length ? (
                  <tr><td colSpan={7} className="text-center py-5">Bu bo‘limda kitob kartasi topilmadi</td></tr>
                ) : null}
              </tbody>
            </table>
          </div>

          <PaginationControls {...pagination} onPageChange={(page) => load({ page })} />
        </div>
      </div>

      <BanModal target={banTarget} onHide={() => setBanTarget(null)} onSubmit={(reason) => banTarget && bulk('ban', banTarget.ids, reason)} />
    </div>
  );
}


function BanModal({ target, onHide, onSubmit }: { target: { ids: number[]; title: string; offers: number } | null; onHide: () => void; onSubmit: (reason: string) => void }) {
  const [reason, setReason] = useState("O'zbekistonda taqiqlangan adabiyot yoki sotuv cheklovi");
  const [busy, setBusy] = useState(false);
  return (
    <Modal show={!!target} onHide={onHide} centered>
      <form className="app-form" onSubmit={(e) => { e.preventDefault(); setBusy(true); onSubmit(reason); setTimeout(() => setBusy(false), 1500); }}>
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Sotuvdan olish</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="d-flex align-items-start gap-3 p-3 b-r-12 bg-light-danger mb-3">
            <span className="h-40 w-40 d-flex-center b-r-50 bg-white text-danger flex-shrink-0 f-s-20"><i className="ti ti-ban"></i></span>
            <div className="min-w-0">
              <div className="f-w-600 text-dark text-truncate">{target?.title}</div>
              <div className="f-s-13 text-secondary">{target?.offers ?? 0} ta do'kon taklifi yashiriladi, qoldiq 0 ga tushadi, savat va sevimlilardan olinadi. Buyurtmalar tarixi saqlanadi.</div>
            </div>
          </div>
          <label className="form-label f-w-600">Sabab</label>
          <input className="form-control" value={reason} onChange={(e) => setReason(e.target.value)} required maxLength={255} />
          <div className="d-flex flex-wrap gap-1 mt-2">
            {["O'zbekistonda taqiqlangan adabiyot", 'Mualliflik huquqi buzilishi', 'Kontrafakt nashr', "Noto'g'ri karta"].map((r) => (
              <button type="button" key={r} className="btn btn-sm btn-light-secondary b-r-22" onClick={() => setReason(r)}>{r}</button>
            ))}
          </div>
        </Modal.Body>
        <Modal.Footer>
          <button type="button" className="btn btn-light-secondary" onClick={onHide}>Bekor</button>
          <button type="submit" className="btn btn-danger" disabled={busy}>{busy ? 'Bajarilmoqda…' : 'Sotuvdan olish'}</button>
        </Modal.Footer>
      </form>
    </Modal>
  );
}
