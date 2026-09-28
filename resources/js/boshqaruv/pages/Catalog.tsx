import { useState, type FormEvent } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import PaginationControls from '../components/PaginationControls';
import FormAction from '../components/FormAction';
import { EmptyState, StatWidget } from '../components/Axelit';
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
  filters: { search?: string; tab?: string };
  counts: Record<string, number>;
  formOptions: { categories: OptionItem[]; publishers: OptionItem[]; sellers: OptionItem[] };
};

const SOURCE_LABELS: Record<string, { label: string; chip: string }> = {
  admin: { label: 'Admin', chip: 'bg-light-primary text-primary' },
  seller: { label: "Do'kon arizasi", chip: 'bg-light-warning text-warning' },
  backfill: { label: 'Mavjud kitobdan', chip: 'bg-light-secondary text-secondary' },
  legacy: { label: 'Eski tizim', chip: 'bg-light-secondary text-secondary' },
  parser: { label: 'Import / Parser', chip: 'bg-light-info text-info' },
};

export default function Catalog() {
  const { editions = [], pagination, filters = {}, counts = {}, formOptions } = usePage<Props>().props;
  const [search, setSearch] = useState(filters.search || '');
  const [copiedIsbn, setCopiedIsbn] = useState<string | null>(null);
  const [batchLoading, setBatchLoading] = useState(false);
  const [fixingId, setFixingId] = useState<number | null>(null);
  const tab = filters.tab || 'all';

  const load = (params: Record<string, string | number>) => {
    router.get('/boshqaruv/catalog', { search, tab, ...params }, { preserveState: true, preserveScroll: true, replace: true });
  };

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

  return (
    <div>
      {/* ── Page Header ── */}
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <div className="d-flex align-items-center gap-2">
            <h4 className="main-title mb-0">Global kitoblar katalogi</h4>
            <span className="badge bg-light-primary text-primary f-s-12 b-r-8">Master Catalog</span>
          </div>
          <PageCrumbs />
          <p className="mb-0 text-secondary f-s-13">
            Har bir nashr uchun yagona karta — barcha do'konlar shu kartaga narx va qoldiq bilan ulanadi
          </p>
        </div>

        <div className="d-flex gap-2 flex-wrap align-items-center">
          {/* Qidiruv formasi */}
          <form className="d-flex gap-2" onSubmit={(event) => { event.preventDefault(); load({ page: 1 }); }}>
            <div className="position-relative" style={{ minWidth: 280 }}>
              <input
                className="form-control form-control-sm pe-4"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="ISBN-13, kitob nomi, muallif yoki ID..."
              />
              {search ? (
                <button
                  type="button"
                  className="btn btn-link btn-sm position-absolute top-50 end-0 translate-middle-y text-secondary text-decoration-none p-1 me-1"
                  onClick={() => { setSearch(''); load({ search: '', page: 1 }); }}
                  title="Tozalash"
                >
                  <i className="ti ti-x"></i>
                </button>
              ) : null}
            </div>
            <button className="btn btn-sm btn-outline-secondary" type="submit" title="Qidirish">
              <i className="ti ti-search"></i>
            </button>
          </form>

          {/* Arizalar tugmasi */}
          <Link href="/boshqaruv/catalog/submissions" className="btn btn-sm btn-outline-primary position-relative d-inline-flex align-items-center gap-1">
            <i className="ti ti-inbox"></i>
            <span>Arizalar</span>
            {(counts.submissions || 0) > 0 ? (
              <span className="badge bg-danger rounded-pill f-s-10 ms-1">{counts.submissions}</span>
            ) : null}
          </Link>

          {/* Muqovalarni avto-tuzatish */}
          <button
            type="button"
            className="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1"
            onClick={handleAutoFixBatch}
            disabled={batchLoading}
            title="Book.uz va Asaxiy orqali muqovasiz yoki noto'g'ri rasmli kitoblarni avtomatik tuzatish"
          >
            <i className={`ti ${batchLoading ? 'ti-loader rotate' : 'ti-wand'}`}></i>
            <span>{batchLoading ? 'Qidirilmoqda...' : 'Avto-muqova'}</span>
          </button>

          {/* Yangi kitob qo'shish modal */}
          <FormAction
            label="Yangi kitob kartasi"
            icon="ti ti-plus"
            variant="primary"
            modalSize="lg"
            title="Katalogga yangi kitob kartasi"
            description="Karta rasmiy ma'lumotlar bilan ochiladi. Keyinchalik barcha do'konlar ushbu kartaga o'z narxlari bilan ulanishadi."
            submitLabel="Kartani saqlash"
            onSubmit={submitCreate}
          >
            <div className="alert alert-light-primary f-s-13 mb-3">
              <i className="ti ti-info-circle me-1"></i>
              <b>Muhim:</b> ISBN-13 nazorat raqami avtomatik tekshiriladi. Kitob old va orqa muqova rasmlarini yuklash tavsiya etiladi.
            </div>
            <EditionFields options={formOptions} />
            <hr />
            <CoverInputs requireFront />
          </FormAction>
        </div>
      </div>

      {/* ── Top KPI StatWidgets ── */}
      <div className="row">
        {[
          { key: 'all', label: 'Kitob kartalari', value: counts.all || 0, sub: `${fmt(counts.unlinked || 0)} ta taklif ulanmagan` },
          { key: 'unverified', label: 'Tasdiqlanmagan', value: counts.unverified || 0, sub: "Eski kitoblardan ochilgan" },
          { key: 'pending', label: "Do'kon arizalari", value: counts.submissions || 0, sub: 'Old/orqa muqovasi bilan tekshiruvda', href: '/boshqaruv/catalog/submissions' },
          { key: 'duplicates', label: 'Dublikat ISBN', value: undefined, sub: "Bir ISBN ostida bir nechta variant" },
        ].map((item, index) => (
          <div className="col-xl-3 col-md-6" key={item.key}>
            <StatWidget
              index={index}
              label={item.label}
              value={item.value ?? '—'}
              sub={item.sub}
              selected={tab === item.key}
              href={item.href}
              onClick={item.href ? undefined : () => load({ tab: item.key, page: 1 })}
            />
          </div>
        ))}
      </div>

      {/* ── Main Catalog Table Card ── */}
      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600 mb-0">Nashrlar ro'yxati</h5>
            <p className="mb-0 text-secondary f-s-13">{fmt(pagination.total)} ta kitob kartasi topildi</p>
          </div>

          {/* Segment Tabs */}
          <div className="nav kc-segment">
            {[
              ['all', 'Barchasi'],
              ['unverified', 'Tasdiqlanmagan'],
              ['pending', 'Tekshiruvda'],
              ['duplicates', 'Dublikatlar'],
              ['no_cover', 'Muqovasiz'],
              ['rejected', 'Taqiqlangan'],
              ['deleted', "O'chirilgan"],
            ].map(([key, label]) => (
              <div className="nav-item" key={key}>
                <button
                  type="button"
                  className={`nav-link ${tab === key ? 'active' : ''}`}
                  onClick={() => load({ tab: key, page: 1 })}
                >
                  {label}
                  {counts[key] !== undefined ? (
                    <span className="badge text-light-secondary ms-2">{fmt(counts[key])}</span>
                  ) : null}
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
                  <th>Kitob va muallif</th>
                  <th>ISBN va Nashr varianti</th>
                  <th>Nashriyot</th>
                  <th>Do'kon takliflari</th>
                  <th>BuyBox narxi</th>
                  <th>Holat</th>
                  <th className="text-end" style={{ width: 80 }}>Amallar</th>
                </tr>
              </thead>
              <tbody>
                {editions.map((edition) => {
                  const [statusText, statusChip] = editionStatus(edition.status, edition.verified);
                  const src = edition.source ? SOURCE_LABELS[edition.source] : null;

                  return (
                    <tr key={edition.id}>
                      {/* Muqova */}
                      <td>
                        <div className="position-relative d-inline-block">
                          <Link href={edition.url} className="d-block text-decoration-none">
                            <div
                              className="b-r-8 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 position-relative shadow-sm"
                              style={{ width: 48, height: 66, border: '1px solid rgba(0,0,0,0.06)' }}
                            >
                              {edition.cover ? (
                                <img
                                  className="w-100 h-100 object-fit-cover"
                                  src={edition.cover}
                                  alt={edition.title}
                                  loading="lazy"
                                />
                              ) : (
                                <i className="ti ti-book text-primary f-s-20"></i>
                              )}
                            </div>
                          </Link>
                          {(!edition.cover || edition.cover.includes('1790422172657') || edition.cover.includes('Screenshot_2026_09_26_072402')) && (
                            <button
                              type="button"
                              className="btn btn-primary icon-btn w-20 h-20 b-r-22 position-absolute bottom-0 end-0 m-0 shadow-sm p-0 d-flex-center"
                              title="Muqovani internetdan avtomatik topish"
                              onClick={(e) => { e.preventDefault(); e.stopPropagation(); handleSingleAutoCover(edition.id); }}
                              disabled={fixingId === edition.id}
                              style={{ transform: 'translate(25%, 25%)', zIndex: 2 }}
                            >
                              <i className={`ti ${fixingId === edition.id ? 'ti-loader rotate' : 'ti-wand'} f-s-10`}></i>
                            </button>
                          )}
                        </div>
                      </td>

                      {/* Kitob va muallif */}
                      <td>
                        <Link
                          href={edition.url}
                          className="f-w-600 d-block text-truncate text-dark text-decoration-none hover-primary"
                          style={{ maxWidth: 300 }}
                          title={edition.title}
                        >
                          {edition.title}
                        </Link>
                        <div className="d-flex align-items-center gap-2 mt-1 flex-wrap">
                          <span className="text-secondary f-s-13">
                            <i className="ti ti-user me-1"></i>{edition.author || 'Muallif noma\'lum'}
                          </span>
                          {edition.category ? (
                            <span className="badge bg-light-primary text-primary f-s-11 b-r-6">{edition.category}</span>
                          ) : null}
                          {src ? (
                            <span className={`badge ${src.chip} f-s-11 b-r-6`}>{src.label}</span>
                          ) : null}
                        </div>
                      </td>

                      {/* ISBN va Nashr varianti */}
                      <td>
                        {edition.isbn ? (
                          <div className="d-flex align-items-center gap-1">
                            <span
                              className="badge bg-light-secondary text-dark font-monospace cursor-pointer d-inline-flex align-items-center gap-1"
                              onClick={() => copyToClipboard(edition.isbn!)}
                              title="Nusxalash uchun bosing"
                              style={{ userSelect: 'all' }}
                            >
                              <i className={`ti ${copiedIsbn === edition.isbn ? 'ti-check text-success' : 'ti-copy'} f-s-12`}></i>
                              {edition.isbn}
                            </span>
                            {copiedIsbn === edition.isbn ? (
                              <small className="text-success f-s-11 animate__animated animate__fadeIn">Nusxalandi!</small>
                            ) : null}
                          </div>
                        ) : (
                          <span className="text-muted f-s-13">ISBN yo'q</span>
                        )}
                        {edition.variant ? (
                          <span className="badge bg-light-info text-info f-s-11 mt-1 d-inline-block b-r-6">
                            {edition.variant}
                          </span>
                        ) : null}
                      </td>

                      {/* Nashriyot */}
                      <td>
                        <span className="text-dark f-s-13 f-w-500">{edition.publisher || '—'}</span>
                      </td>

                      {/* Do'kon takliflari */}
                      <td>
                        <div className="d-flex align-items-center gap-2">
                          <span className="f-w-600 f-s-14 text-dark">{edition.offersCount} ta</span>
                          {edition.inStockOffers > 0 ? (
                            <span className="badge bg-light-success text-success f-s-11">
                              <i className="ti ti-check me-1"></i>{edition.inStockOffers} ta omborda
                            </span>
                          ) : (
                            <span className="badge bg-light-secondary text-secondary f-s-11">
                              Omborda yo'q
                            </span>
                          )}
                        </div>
                      </td>

                      {/* BuyBox narxi */}
                      <td>
                        {edition.minPrice ? (
                          <div>
                            <span className="f-w-600 text-dark f-s-14">{fmt(edition.minPrice)} so'm</span>
                            <small className="d-block text-secondary f-s-11">Eng arzon narx</small>
                          </div>
                        ) : (
                          <span className="text-muted f-s-13">—</span>
                        )}
                      </td>

                      {/* Holat */}
                      <td>
                        <div className="d-flex flex-column gap-1">
                          <span className={`badge ${statusChip} d-inline-flex align-items-center gap-1 w-fit`}>
                            {edition.verified ? <i className="ti ti-circle-check f-s-12"></i> : null}
                            {statusText}
                          </span>
                          {edition.banned ? (
                            <span className="badge bg-light-danger text-danger d-inline-flex align-items-center gap-1 w-fit">
                              <i className="ti ti-ban f-s-11"></i>Taqiqlangan
                            </span>
                          ) : null}
                          {edition.deleted ? (
                            <span className="badge bg-light-danger text-danger d-inline-flex align-items-center gap-1 w-fit">
                              <i className="ti ti-trash f-s-11"></i>O'chirilgan
                            </span>
                          ) : null}
                        </div>
                      </td>

                      {/* Amallar */}
                      <td className="text-end">
                        <Link
                          href={edition.url}
                          className="btn btn-light-primary icon-btn w-32 h-32 b-r-22"
                          title="Karta sahifasini ochish"
                        >
                          <i className="ti ti-arrow-right f-s-15"></i>
                        </Link>
                      </td>
                    </tr>
                  );
                })}

                {!editions.length ? (
                  <tr>
                    <td colSpan={8} className="py-5">
                      <EmptyState text="Ushbu bo'limda birorta kitob kartasi topilmadi" />
                    </td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>

          <PaginationControls {...pagination} onPageChange={(page) => load({ page })} />
        </div>
      </div>
    </div>
  );
}

