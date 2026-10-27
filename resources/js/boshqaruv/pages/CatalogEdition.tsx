import { useState, type FormEvent, type ReactNode } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import FormAction from '../components/FormAction';
import { EmptyState } from '../components/Axelit';
import { CoverInputs, EditionFields, KeepImages, editionStatus, isbnCheckChip, type OptionItem } from '../components/CatalogFields';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Edition {
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
  createdAt?: string;
  url: string;
  translator?: string | null;
  publisherId?: number | null;
  categoryId?: number | null;
  lang?: string | null;
  langType?: string | null;
  coverType?: string | null;
  year?: number | null;
  pages?: number | null;
  description?: string | null;
  frontUrl?: string | null;
  backUrl?: string | null;
  banned?: boolean;
  banUrl?: string;
  unbanUrl?: string;
  variant?: string;
  variantDiff?: string[];
  mergedInto?: { id: number; title: string; url: string } | null;
  tagIds?: number[];
  rawImages?: string[];
  images?: (string | null)[];
}

interface Offer {
  id: number;
  artikul?: string | null;
  seller: string;
  sellerActive: boolean;
  price: number;
  discountPrice: number;
  effectivePrice: number;
  stock: number;
  preorderDate?: string | null;
  featured: boolean;
  approved: number;
  active: boolean;
  hidden: boolean;
  archived: boolean;
  sold: number;
  url: string;
}

interface Submission {
  id: number;
  type?: string;
  status: string;
  seller: string;
  isbn?: string | null;
  isbnCheck?: string | null;
  backIsbnServer?: string | null;
  backIsbnMethod?: string | null;
  backIsbnClient?: string | null;
  frontUrl?: string | null;
  backUrl?: string | null;
  message?: string | null;
  field?: string | null;
  suggested?: string | null;
  requestedCover?: string | null;
  proofUrls?: string[];
  createdAt?: string;
}

interface EditionVideo {
  status: 'processing' | 'ready' | 'failed' | string;
  sdUrl?: string | null;
  hdUrl?: string | null;
  posterUrl?: string | null;
  duration?: number | null;
  sdSize?: number | null;
  hdSize?: number | null;
  error?: string | null;
  canRetry?: boolean;
  updatedAt?: string | null;
}

const mb = (bytes?: number | null) => (bytes ? `${(bytes / 1048576).toFixed(1)} MB` : '—');
const mmss = (sec?: number | null) =>
  sec ? `${Math.floor(sec / 60)}:${String(sec % 60).padStart(2, '0')}` : '—';

/** Global kitobning mahsulot videosi: yuklash, holat, ko'rish, o'chirish. */
function EditionVideoCard({ base, video }: { base: string; video: EditionVideo | null }) {
  const [progress, setProgress] = useState<number | null>(null);
  const [fileName, setFileName] = useState<string | null>(null);

  const upload = (file: File) => {
    setFileName(file.name);
    setProgress(0);
    router.post(
      `${base}/video`,
      { video: file },
      {
        forceFormData: true,
        preserveScroll: true,
        onProgress: (e) => setProgress((e as { percentage?: number } | undefined)?.percentage ?? null),
        onFinish: () => {
          setProgress(null);
          setFileName(null);
        },
      },
    );
  };

  const status = video?.status;
  const chip =
    status === 'ready'
      ? ['Tayyor', 'bg-light-success text-success']
      : status === 'failed'
        ? ['Xatolik', 'bg-light-danger text-danger']
        : status === 'processing'
          ? ['Tayyorlanmoqda', 'bg-light-warning text-warning']
          : null;

  return (
    <div className="card">
      <div className="card-header d-flex align-items-center justify-content-between gap-2">
        <h5 className="mb-0">Mahsulot videosi</h5>
        {chip ? <span className={`badge ${chip[1]}`}>{chip[0]}</span> : null}
      </div>
      <div className="card-body">

        {status === 'ready' && (video?.hdUrl || video?.sdUrl) ? (
          <div className="mb-3">
            <video
              className="w-100 b-r-12 bg-dark"
              style={{ maxHeight: 320 }}
              controls
              preload="metadata"
              poster={video?.posterUrl || undefined}
              src={video?.hdUrl || video?.sdUrl || undefined}
            />
            <div className="d-flex flex-wrap gap-3 mt-2 f-s-12 text-secondary">
              <span><i className="ti ti-clock"></i> {mmss(video?.duration)}</span>
              <span>SD 480p: {mb(video?.sdSize)}</span>
              <span>HD 720p: {mb(video?.hdSize)}</span>
              {video?.updatedAt ? <span>{video.updatedAt}</span> : null}
            </div>
          </div>
        ) : null}

        {status === 'processing' ? (
          <div className="alert alert-light-warning f-s-13 mb-3">
            Video mobil uchun tayyorlanmoqda (480p va 720p). Bir necha daqiqadan so'ng sahifani yangilang.
            <button type="button" className="btn btn-link btn-sm p-0 ms-2" onClick={() => router.reload({ preserveScroll: true })}>
              Yangilash
            </button>
          </div>
        ) : null}

        {status === 'failed' ? (
          <div className="alert alert-light-danger f-s-13 mb-3">
            <div className="f-w-600 mb-1">Videoni tayyorlab bo'lmadi</div>
            <div className="text-break">{video?.error || "Noma'lum xatolik"}</div>
            {video?.canRetry ? (
              <button
                type="button"
                className="btn btn-sm btn-light-danger mt-2"
                onClick={() => router.post(`${base}/video/retry`, {}, { preserveScroll: true })}
              >
                <i className="ti ti-refresh"></i> Qayta urinish
              </button>
            ) : null}
          </div>
        ) : null}

        {progress !== null ? (
          <div className="mb-3">
            <div className="d-flex justify-content-between f-s-12 text-secondary mb-1">
              <span className="text-truncate me-2">{fileName}</span>
              <span>{Math.round(progress)}%</span>
            </div>
            <div className="progress" style={{ height: 6 }}>
              <div className="progress-bar bg-primary" style={{ width: `${progress}%` }} />
            </div>
          </div>
        ) : null}

        <div className="d-flex flex-wrap gap-2">
          <label className={`btn btn-sm btn-light-primary mb-0 ${progress !== null ? 'disabled' : ''}`}>
            <i className="ti ti-upload"></i> {video ? 'Videoni almashtirish' : 'Video yuklash'}
            <input
              type="file"
              accept="video/mp4,video/quicktime,video/webm,video/x-matroska,.mp4,.mov,.webm,.mkv,.m4v"
              hidden
              disabled={progress !== null}
              onChange={(e) => {
                const f = e.currentTarget.files?.[0];
                e.currentTarget.value = '';
                if (f) upload(f);
              }}
            />
          </label>
          {video ? (
            <button
              type="button"
              className="btn btn-sm btn-light-danger"
              disabled={progress !== null}
              onClick={() => {
                if (confirm("Videoni o'chirasizmi?")) {
                  router.delete(`${base}/video`, { preserveScroll: true });
                }
              }}
            >
              <i className="ti ti-trash"></i> O'chirish
            </button>
          ) : null}
        </div>
        <div className="f-s-12 text-secondary mt-2">
          MP4, MOV, WEBM yoki MKV, 300 MB gacha. Ilovada rasmlar ustida «Mahsulot videosi» tugmasi chiqadi.
        </div>
      </div>
    </div>
  );
}

type Props = {
  video?: EditionVideo | null;
  edition: Edition;
  offers: Offer[];
  submissions: Submission[];
  mergeCandidates: Edition[];
  formOptions: { categories: OptionItem[]; publishers: OptionItem[] };
};

export default function CatalogEdition() {
  const { edition, offers = [], submissions = [], mergeCandidates = [], formOptions, video = null } = usePage<Props>().props;
  const [label, chip] = editionStatus(edition.status, edition.verified);
  const base = `/boshqaruv/catalog/${edition.id}`;

  const [activeTab, setActiveTab] = useState<'offers' | 'about' | 'submissions' | 'merge'>('offers');
  const [copiedIsbn, setCopiedIsbn] = useState(false);
  const [selectedPhoto, setSelectedPhoto] = useState<string | null>(edition.frontUrl || edition.cover || null);
  const [autoCoverLoading, setAutoCoverLoading] = useState(false);
  const [autoCoverMsg, setAutoCoverMsg] = useState<string | null>(null);

  const handleAutoCover = async () => {
    setAutoCoverLoading(true);
    setAutoCoverMsg(null);
    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content
        || ((window as unknown as { csrfToken?: string }).csrfToken || '');
      const res = await fetch(`${base}/auto-cover`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });
      const data = await res.json();
      if (data.success) {
        setAutoCoverMsg(data.message || "Muqova o'rnatildi!");
        router.reload({ preserveScroll: true });
      } else {
        alert(data.message || 'Internetdan mos muqova topilmadi');
      }
    } catch {
      alert('Xatolik yuz berdi');
    } finally {
      setAutoCoverLoading(false);
    }
  };

  const totalSold = offers.reduce((sum, o) => sum + (o.sold || 0), 0);
  const totalStock = offers.reduce((sum, o) => sum + (o.stock || 0), 0);
  const buyBoxOffer = offers.find((o) => o.featured) || offers[0] || null;

  const copyIsbn = (text: string) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    setCopiedIsbn(true);
    setTimeout(() => setCopiedIsbn(false), 2000);
  };

  const submitUpdate = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    data.append('_method', 'PUT');
    router.post(base, data, { forceFormData: true, preserveScroll: true });
  };

  const submitMerge = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    router.post(`${base}/merge`, new FormData(event.currentTarget), { preserveScroll: true });
  };

  const submitBan = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    router.post(`${base}/ban`, new FormData(event.currentTarget), { preserveScroll: true });
  };

  const submitUnban = () => {
    if (window.confirm("Kitob sotuvga qaytarilsinmi?")) {
      router.post(`${base}/unban`, {}, { preserveScroll: true });
    }
  };

  const keepItems = (edition.rawImages || []).map((path, index) => ({ path, url: edition.images?.[index] || null }));
  const allImages = Array.from(new Set([edition.frontUrl, edition.backUrl, ...(edition.images || [])].filter((x): x is string => Boolean(x))));

  const isBanned = edition.banned || edition.status === 'rejected';

  const canEdit = !edition.deleted && edition.status !== 'merged';
  const needsVerify = canEdit && (!edition.verified || edition.status !== 'active') && !isBanned;
  const SOURCE: Record<string, string> = { admin: 'Admin', seller: "Do'kon arizasi", backfill: 'Mavjud kitobdan', legacy: 'Eski tizim', parser: 'Import' };
  const inSale = offers.filter((o) => o.approved === 1 && o.active && !o.hidden && !o.archived && o.sellerActive).length;

  const specs: Array<[string, ReactNode]> = [
    ['ISBN-13', edition.isbn ? (
      <span className="d-inline-flex align-items-center gap-1 font-monospace">
        {edition.isbn}
        <button type="button" className="btn btn-link p-0 text-secondary lh-1" onClick={() => copyIsbn(edition.isbn || '')} title="Nusxalash" aria-label="ISBN nusxalash">
          <i className={copiedIsbn ? 'ti ti-check text-success' : 'ti ti-copy'}></i>
        </button>
      </span>
    ) : '—'],
    ['Nashriyot', edition.publisher || '—'],
    ['Kategoriya', edition.category || '—'],
    ['Til / yozuv', [edition.lang, edition.langType].filter(Boolean).join(' / ') || '—'],
    ['Muqova', edition.coverType || '—'],
    ['Sahifalar', edition.pages ? `${edition.pages} bet` : '—'],
    ['Nashr yili', edition.year || '—'],
    ...(edition.translator ? [['Tarjimon', edition.translator] as [string, ReactNode]] : []),
    ['Manba', SOURCE[edition.source || 'admin'] || edition.source || 'Admin'],
    ['Yaratilgan', edition.createdAt || '—'],
  ];

  return (
    <div className="pb-4">
      {/* ── Sarlavha: nom, asosiy ma'lumot va 3 tagacha amal ── */}
      <div className="kb-page-head">
        <div className="d-flex align-items-start gap-3 min-w-0">
          <Link href="/boshqaruv/catalog" className="btn btn-light-primary icon-btn b-r-22 flex-shrink-0 mt-1" title="Katalogga qaytish" aria-label="Katalogga qaytish">
            <i className="ti ti-arrow-left f-s-18"></i>
          </Link>
          <div className="min-w-0">
            <div className="d-flex align-items-center gap-2 flex-wrap">
              <h4 className="main-title mb-0 text-break">{edition.title}</h4>
              <span className={`badge ${chip}`}>{label}</span>
              {edition.deleted && !isBanned ? <span className="badge text-light-secondary">O‘chirilgan</span> : null}
            </div>
            <PageCrumbs />
            <p className="mb-0 text-secondary f-s-13">
              {edition.author || 'Muallif noma’lum'}
              {edition.publisher ? <> · {edition.publisher}</> : null}
              {edition.variant ? <> · {edition.variant}</> : null}
              <span className="text-muted"> · #{edition.id}</span>
            </p>
          </div>
        </div>

        <div className="kb-page-actions">
          {canEdit ? (
            <FormAction
              label="Tahrirlash"
              icon="ti ti-edit"
              variant="light-secondary"
              size="md"
              modalSize="lg"
              title="Kitob kartasini tahrirlash"
              description="Nom, muallif, muqova va annotatsiya shu kitobning barcha do'kon takliflariga ko'chiriladi."
              onSubmit={submitUpdate}
            >
              <EditionFields values={edition} options={formOptions} />
              <hr />
              <label className="form-label f-w-600">Mavjud rasmlar</label>
              <KeepImages items={keepItems} />
              <div className="mt-3"><CoverInputs requireFront={false} /></div>
            </FormAction>
          ) : null}

          {isBanned || edition.deleted ? (
            <button type="button" className="btn btn-primary" onClick={submitUnban}>
              <i className="ti ti-rotate-clockwise"></i>Sotuvga qaytarish
            </button>
          ) : edition.status !== 'merged' ? (
            <FormAction
              label="Sotuvdan olish"
              icon="ti ti-ban"
              variant="light-danger"
              size="md"
              title="Sotuvdan olish"
              description="Kitob taqiqqa tushganda yoki sotuv to'xtatilganda."
              submitLabel="Sotuvdan olish"
              submitVariant="danger"
              confirmText="Kitob barcha do'konlardan sotuvdan olinsinmi? Savat va sevimlilardan ham chiqariladi."
              onSubmit={submitBan}
            >
              <div className="alert alert-light-danger f-s-13 mb-3">
                {edition.offersCount} ta do‘kon taklifi yashiriladi va arxivlanadi, savat va sevimlilardan olinadi.
                Buyurtmalar tarixi va BookClub muhokamalari saqlanadi.
              </div>
              <label className="form-label">Sabab</label>
              <input type="text" name="reason" className="form-control" defaultValue="O'zbekistonda taqiqlangan adabiyot yoki sotuv cheklovi" />
            </FormAction>
          ) : null}

          {needsVerify ? (
            <button type="button" className="btn btn-primary" onClick={() => router.post(`${base}/verify`, {}, { preserveScroll: true })}>
              <i className="ti ti-circle-check"></i>Tasdiqlash
            </button>
          ) : null}
        </div>
      </div>

      {/* ── Holat ogohlantirishlari (faqat kerak bo'lganda) ── */}
      {isBanned ? (
        <div className="alert alert-light-danger d-flex align-items-center gap-3 mb-3">
          <i className="ti ti-ban f-s-20 flex-shrink-0"></i>
          <div className="flex-grow-1 f-s-13">
            <b>Sotuvdan olingan.</b> Ilovada va qidiruvda ko‘rinmaydi, do‘kon takliflari yashirilgan. Buyurtmalar tarixi saqlanadi.
          </div>
        </div>
      ) : null}
      {edition.mergedInto ? (
        <div className="alert alert-light-warning d-flex align-items-center gap-3 mb-3">
          <i className="ti ti-arrows-exchange f-s-20 flex-shrink-0"></i>
          <div className="f-s-13">
            Bu karta <Link href={edition.mergedInto.url} className="f-w-600">#{edition.mergedInto.id} {edition.mergedInto.title}</Link> ga birlashtirilgan — barcha takliflar o‘sha yerda.
          </div>
        </div>
      ) : null}

      <div className="row g-3">
        {/* ── Chap: muqova va xususiyatlar (telefonda pastda) ── */}
        <div className="col-xl-4 col-lg-5 order-2 order-lg-1">
          <div className="card">
            <div className="card-body">
              <div className="kb-cover-stage">
                <div className="kb-cover-main">
                  {selectedPhoto ? <img src={selectedPhoto} alt={edition.title} /> : <i className="ti ti-book"></i>}
                </div>
              </div>
              {allImages.length > 1 ? (
                <div className="d-flex justify-content-center gap-2 flex-wrap mt-3">
                  {allImages.map((img, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => setSelectedPhoto(img)}
                      className={`kb-cover-thumb ${selectedPhoto === img ? 'is-active' : ''}`}
                      title={img === edition.frontUrl ? 'Old muqova' : img === edition.backUrl ? 'Orqa muqova' : `Rasm ${idx + 1}`}
                    >
                      <img src={img} alt="" />
                    </button>
                  ))}
                </div>
              ) : null}
              {canEdit ? (
                <div className="text-center mt-3">
                  <button type="button" className="btn btn-sm btn-light-secondary" onClick={handleAutoCover} disabled={autoCoverLoading} title="Book.uz va Asaxiy'dan muqovani topib o'rnatadi">
                    <i className={`ti ${autoCoverLoading ? 'ti-loader-2' : 'ti-wand'}`}></i>
                    {autoCoverLoading ? 'Qidirilmoqda…' : 'Muqovani internetdan topish'}
                  </button>
                  {autoCoverMsg ? <div className="f-s-12 text-success mt-1">{autoCoverMsg}</div> : null}
                </div>
              ) : null}
            </div>
            <div className="card-body border-top">
              <dl className="kb-specs mb-0">
                {specs.map(([k, v]) => (
                  <div key={k}>
                    <dt>{k}</dt>
                    <dd>{v}</dd>
                  </div>
                ))}
              </dl>
            </div>
          </div>
          <EditionVideoCard base={base} video={video} />
        </div>

        {/* ── O'ng: sotuv ko'rsatkichlari va bo'limlar ── */}
        <div className="col-xl-8 col-lg-7 order-1 order-lg-2">
          <div className="card">
            <div className="kb-metrics">
              <div>
                <span>Eng arzon narx</span>
                <b>{edition.minPrice ? `${fmt(edition.minPrice)} so'm` : '—'}</b>
                <small>{buyBoxOffer ? buyBoxOffer.seller : 'Taklif yo‘q'}</small>
              </div>
              <div>
                <span>Takliflar</span>
                <b>{fmt(edition.offersCount)}</b>
                <small>{fmt(inSale)} tasi sotuvda</small>
              </div>
              <div>
                <span>Qoldiq</span>
                <b>{fmt(totalStock)} dona</b>
                <small>barcha do‘konlarda</small>
              </div>
              <div>
                <span>Sotilgan</span>
                <b>{fmt(totalSold)} dona</b>
                <small>umumiy</small>
              </div>
            </div>

            <div className="card-header border-top">
              <div className="nav kc-segment">
                {([
                  ['offers', 'Takliflar', offers.length],
                  ['about', 'Annotatsiya', null],
                  ['submissions', 'Arizalar', submissions.length || null],
                  ['merge', 'O‘xshash kartalar', mergeCandidates.length || null],
                ] as const).map(([key, text, count]) => (
                  <div className="nav-item" key={key}>
                    <button type="button" className={`nav-link ${activeTab === key ? 'active' : ''}`} onClick={() => setActiveTab(key)}>
                      {text}
                      {count !== null ? <span className="kb-tab-count">{count}</span> : null}
                    </button>
                  </div>
                ))}
              </div>
            </div>

            {activeTab === 'offers' && (
              <div className="table-responsive app-scroll">
                <table className="table table-bottom-border align-middle mb-0 kb-offers-table">
                  <thead>
                    <tr>
                      <th>Do‘kon</th>
                      <th className="text-end">Narx</th>
                      <th className="text-end">Qoldiq</th>
                      <th className="text-end">Sotilgan</th>
                      <th>Holat</th>
                      <th className="text-end" style={{ width: 52 }}></th>
                    </tr>
                  </thead>
                  <tbody>
                    {offers.map((offer) => {
                      const discounted = offer.discountPrice > 0 && offer.discountPrice < offer.price;
                      return (
                        <tr key={offer.id} className={offer.featured ? 'kb-row-winner' : ''}>
                          <td>
                            <div className="f-w-600 text-dark d-flex align-items-center gap-1">
                              <span className="text-truncate">{offer.seller}</span>
                              {offer.featured ? <i className="ti ti-crown text-warning" title="Ilovada shu taklif ko'rsatiladi (BuyBox)"></i> : null}
                            </div>
                            <div className="f-s-12 text-secondary">#{offer.id}{offer.artikul ? ` · ${offer.artikul}` : ''}</div>
                          </td>
                          <td className="text-end text-nowrap">
                            <div className="f-w-600 text-dark">{fmt(offer.effectivePrice)} so‘m</div>
                            {discounted ? <div className="f-s-12 text-muted text-decoration-line-through">{fmt(offer.price)} so‘m</div> : null}
                          </td>
                          <td className="text-end text-nowrap">
                            <span className={offer.stock === 0 ? 'text-danger f-w-600' : offer.stock <= 5 ? 'text-warning-dark f-w-600' : 'text-dark'}>{fmt(offer.stock)} dona</span>
                            {offer.preorderDate ? <div className="f-s-12 text-warning-dark">Predzakaz · {offer.preorderDate}</div> : null}
                          </td>
                          <td className="text-end text-nowrap text-secondary">{fmt(offer.sold)}</td>
                          <td>
                            {offer.archived ? <span className="badge text-light-secondary">Arxiv</span>
                              : offer.approved === 1 ? (offer.active && !offer.hidden && offer.sellerActive
                                ? <span className="badge text-light-success">Sotuvda</span>
                                : <span className="badge text-light-secondary">Yashirin</span>)
                                : offer.approved === 2 ? <span className="badge text-light-danger">Rad etilgan</span>
                                  : <span className="badge text-light-warning">Moderatsiyada</span>}
                          </td>
                          <td className="text-end">
                            <a href={offer.url} className="btn btn-light-secondary icon-btn w-30 h-30 b-r-8" title="Taklifni ochish" aria-label="Taklifni ochish">
                              <i className="ti ti-external-link"></i>
                            </a>
                          </td>
                        </tr>
                      );
                    })}
                    {!offers.length ? (
                      <tr><td colSpan={6} className="text-center py-5">Hozircha birorta do‘kon bu kitobga taklif qo‘ymagan</td></tr>
                    ) : null}
                  </tbody>
                </table>
              </div>
            )}

            {activeTab === 'about' && (
              <div className="card-body">
                {edition.description ? (
                  <div className="text-dark f-s-14" style={{ whiteSpace: 'pre-wrap', lineHeight: 1.7 }}>{edition.description}</div>
                ) : (
                  <EmptyState icon="ti ti-file-text" text={canEdit ? 'Annotatsiya kiritilmagan — «Tahrirlash» orqali qo‘shing.' : 'Annotatsiya kiritilmagan.'} />
                )}
              </div>
            )}

            {activeTab === 'submissions' && (
              <div className="card-body">
                {submissions.length ? (
                  <div className="d-flex flex-column gap-2">
                    {submissions.map((item) => {
                      const [checkLabel, checkChip, checkIcon] = isbnCheckChip(item.isbnCheck);
                      return (
                        <div key={item.id} className="kb-sub-row">
                          <div className="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div className="min-w-0">
                              <span className="f-w-600 text-dark">{item.seller}</span>
                              <span className="text-secondary f-s-12"> · ariza #{item.id}{item.createdAt ? ` · ${item.createdAt}` : ''}</span>
                            </div>
                            <div className="d-flex gap-1">
                              <span className={`badge ${checkChip}`}><i className={checkIcon}></i> {checkLabel}</span>
                              <span className="badge text-light-secondary">{item.status}</span>
                            </div>
                          </div>
                          <div className="f-s-13 text-secondary mt-1">
                            ISBN: <span className="font-monospace text-dark">{item.isbn || '—'}</span>
                            {item.backIsbnServer ? <> · orqa muqovadan: <span className="font-monospace text-success">{item.backIsbnServer}</span></> : null}
                          </div>
                          {item.message ? <div className="f-s-13 text-dark mt-1">{item.message}</div> : null}
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <EmptyState icon="ti ti-inbox" text="Bu kitob bo‘yicha do‘konlardan ariza kelmagan" />
                )}
              </div>
            )}

            {activeTab === 'merge' && (
              <div className="card-body">
                <div className="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
                  <p className="mb-0 text-secondary f-s-13" style={{ maxWidth: 520 }}>
                    Bir kitob bir necha kartada ochilib qolgan bo‘lsa — bittasiga birlashtiring. Takliflar, sharhlar va arizalar tanlangan kartaga o‘tadi.
                  </p>
                  {canEdit && !isBanned ? (
                    <FormAction
                      label="Birlashtirish"
                      icon="ti ti-arrows-exchange"
                      variant="light-primary"
                      title="Boshqa kartaga birlashtirish"
                      description="Bu kartaning barcha takliflari, sharhlari va arizalari tanlangan kartaga o'tadi."
                      submitLabel="Birlashtirish"
                      submitVariant="warning"
                      confirmText="Kartalar birlashtirilsinmi?"
                      onSubmit={submitMerge}
                    >
                      {mergeCandidates.length ? (
                        <div className="list-group mb-3">
                          {mergeCandidates.map((item) => (
                            <label key={item.id} className="list-group-item d-flex align-items-center gap-3">
                              <input className="form-check-input" type="radio" name="into_id" value={item.id} required />
                              <span className="min-w-0">
                                <span className="f-w-600 d-block text-truncate">{item.title}</span>
                                <small className="text-secondary">#{item.id} · {item.author || '—'} · {item.isbn || "ISBN yo'q"} · {item.offersCount} ta taklif</small>
                                {item.variantDiff?.length ? <small className="d-block text-danger">Boshqa nashr: {item.variantDiff.join(', ')}</small> : null}
                              </span>
                            </label>
                          ))}
                        </div>
                      ) : (
                        <>
                          <p className="text-secondary f-s-13">O‘xshash karta topilmadi — karta ID sini kiriting.</p>
                          <input className="form-control" name="into_id" type="number" min={1} placeholder="Karta ID" required />
                        </>
                      )}
                      <div className="form-check mt-3">
                        <input className="form-check-input" type="checkbox" name="force" value="1" id="merge_force" />
                        <label className="form-check-label" htmlFor="merge_force">Muqova yoki til farq qilsa ham birlashtirish</label>
                      </div>
                    </FormAction>
                  ) : null}
                </div>
                {mergeCandidates.length ? (
                  <div className="d-flex flex-column gap-2">
                    {mergeCandidates.map((candidate) => (
                      <Link key={candidate.id} href={candidate.url} className="kb-sub-row d-flex align-items-center gap-3 text-decoration-none">
                        <span className="kb-cover-mini">{candidate.cover ? <img src={candidate.cover} alt="" /> : <i className="ti ti-book"></i>}</span>
                        <span className="min-w-0 flex-grow-1">
                          <span className="f-w-600 text-dark d-block text-truncate">{candidate.title}</span>
                          <span className="f-s-12 text-secondary d-block">#{candidate.id} · {candidate.author || '—'} · {candidate.isbn || "ISBN yo'q"} · {candidate.offersCount} ta taklif</span>
                        </span>
                        {candidate.variantDiff?.length
                          ? <span className="badge text-light-warning flex-shrink-0">Farq: {candidate.variantDiff.join(', ')}</span>
                          : <span className="badge text-light-success flex-shrink-0">To‘liq mos</span>}
                      </Link>
                    ))}
                  </div>
                ) : (
                  <EmptyState icon="ti ti-arrows-exchange" text="O‘xshash karta topilmadi" />
                )}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
