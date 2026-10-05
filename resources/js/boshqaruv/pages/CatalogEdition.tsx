import { useState, type FormEvent } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import FormAction from '../components/FormAction';
import { EmptyState, StatWidget, MiniStat } from '../components/Axelit';
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
    <div className="card border-0 shadow-sm b-r-16 overflow-hidden mt-4">
      <div className="card-body p-4">
        <div className="d-flex align-items-center justify-content-between mb-3">
          <h6 className="f-w-700 text-dark mb-0 d-flex align-items-center gap-2">
            <i className="ti ti-movie text-primary"></i>
            Mahsulot videosi
          </h6>
          {chip ? <span className={`badge ${chip[1]}`}>{chip[0]}</span> : null}
        </div>

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
          <label className={`btn btn-primary mb-0 ${progress !== null ? 'disabled' : ''}`}>
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
              className="btn btn-light-danger"
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
          MP4, MOV, WEBM yoki MKV, 300 MB gacha. Ilovada rasmlar ustida "Mahsulot videosi" tugmasi paydo bo'ladi;
          sekin internetda 480p, Wi‑Fi da 720p o'ynaydi.
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

  return (
    <div className="pb-5">
      {/* ── Top Header & Actions ── */}
      <div className="d-flex align-items-center justify-content-between flex-wrap gap-3 mx-1 mb-4">
        <div className="min-w-0">
          <div className="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span className="badge bg-light-primary text-primary f-w-600">ID #{edition.id}</span>
            <span className={`badge ${chip}`}>{label}</span>
            {isBanned ? (
              <span className="badge bg-danger-300 text-danger-dark f-w-600">
                <i className="ti ti-ban me-1"></i>Sotuvdan olingan (Taqiqlangan)
              </span>
            ) : null}
            {edition.deleted && !isBanned ? (
              <span className="badge text-light-secondary">O'chirilgan</span>
            ) : null}
            {edition.variant ? <span className="badge text-light-info">{edition.variant}</span> : null}
          </div>
          <h3 className="main-title mb-1 f-w-700 text-dark text-break">{edition.title}</h3>
          <p className="mb-0 text-secondary f-s-14 d-flex align-items-center gap-2 flex-wrap">
            <span>Muallif: <strong className="text-dark">{edition.author || '—'}</strong></span>
            <span>·</span>
            {edition.isbn ? (
              <span className="d-inline-flex align-items-center gap-1">
                ISBN: <code className="text-primary f-w-600">{edition.isbn}</code>
                <button
                  type="button"
                  className="btn btn-link p-0 text-secondary"
                  onClick={() => copyIsbn(edition.isbn || '')}
                  title="Nusxalash"
                >
                  <i className={copiedIsbn ? "ti ti-check text-success" : "ti ti-copy"}></i>
                </button>
                {copiedIsbn ? <small className="text-success f-w-600 ms-1">Nusxalandi!</small> : null}
              </span>
            ) : (
              <span className="text-muted">ISBN yo'q</span>
            )}
            <span>·</span>
            <span>Yaratilgan: <span className="text-dark">{edition.createdAt || '—'}</span></span>
          </p>
        </div>

        <div className="d-flex gap-2 flex-wrap align-items-center">
          <Link href="/boshqaruv/catalog" className="btn btn-light-secondary btn-sm d-inline-flex align-items-center">
            <i className="ti ti-arrow-left me-1"></i>Katalog
          </Link>

          {!edition.deleted && edition.status !== 'merged' && (!edition.verified || edition.status !== 'active') && !isBanned ? (
            <button
              type="button"
              className="btn btn-success btn-sm d-inline-flex align-items-center shadow-sm"
              onClick={() => router.post(`${base}/verify`, {}, { preserveScroll: true })}
            >
              <i className="ti ti-circle-check me-1"></i>Katalogda Tasdiqlash
            </button>
          ) : null}

          {!edition.deleted && edition.status !== 'merged' ? (
            <button
              type="button"
              className="btn btn-outline-primary btn-sm d-inline-flex align-items-center"
              onClick={handleAutoCover}
              disabled={autoCoverLoading}
              title="Internetdan (Book.uz, Asaxiy) kitob muqovasini avtomatik qidirib topish va o'rnatish"
            >
              <i className={`ti ${autoCoverLoading ? 'ti-rotate-clockwise' : 'ti-wand'} me-1`}></i>
              {autoCoverLoading ? 'Qidirilmoqda...' : 'Avto-muqova'}
            </button>
          ) : null}

          {!edition.deleted && edition.status !== 'merged' ? (
            <FormAction
              label="Tahrirlash"
              icon="ti ti-edit"
              variant="primary"
              modalSize="lg"
              title="Kitob kartasini tahrirlash"
              description="Saqlangach nom, muallif, muqova, annotatsiya shu kitobning barcha do'kon takliflariga avtomatik ko'chiriladi va vektorlar qayta hisoblanadi."
              onSubmit={submitUpdate}
            >
              <EditionFields values={edition} options={formOptions} />
              <hr />
              <label className="form-label f-w-600">Mavjud rasmlar</label>
              <KeepImages items={keepItems} />
              <div className="mt-3"><CoverInputs requireFront={false} /></div>
              <div className="alert alert-light-primary mt-3 mb-0 f-s-13">
                <i className="ti ti-info-circle me-1"></i>
                Saqlangach ma'lumot barcha do'konlarga sinxronlashtiriladi va qidiruv indeksi yangilanadi.
              </div>
            </FormAction>
          ) : null}

          {!edition.deleted && edition.status !== 'merged' && !isBanned ? (
            <FormAction
              label="Birlashtirish"
              icon="ti ti-arrows-exchange"
              variant="light-warning"
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
                        {item.variant ? <small className="d-block text-primary">{item.variant}</small> : null}
                        {item.variantDiff?.length ? (
                          <small className="d-block text-danger"><i className="ti ti-alert-triangle me-1"></i>Boshqa nashr ({item.variantDiff.join(', ')})</small>
                        ) : null}
                      </span>
                    </label>
                  ))}
                </div>
              ) : <p className="text-secondary f-s-13">O'xshash karta topilmadi — ID ni qo'lda kiriting.</p>}
              {!mergeCandidates.length ? <input className="form-control" name="into_id" type="number" min={1} placeholder="Karta ID" required /> : null}
              <div className="form-check mt-3">
                <input className="form-check-input" type="checkbox" name="force" value="1" id="merge_force" />
                <label className="form-check-label" htmlFor="merge_force">Majburiy birlashtirish (muqova/til farqiga qaramay)</label>
              </div>
            </FormAction>
          ) : null}

          {isBanned || edition.deleted ? (
            <button
              type="button"
              className="btn btn-success btn-sm d-inline-flex align-items-center shadow-sm"
              onClick={submitUnban}
            >
              <i className="ti ti-rotate-clockwise me-1"></i>Sotuvga qaytarish
            </button>
          ) : edition.status !== 'merged' ? (
            <FormAction
              label="Sotuvdan olish"
              icon="ti ti-ban"
              variant="light-danger"
              title="Kitobni sotuvdan olib tashlash (Taqiqlash)"
              description="Ushbu amal kitob davlat taqiqiga tushgan yoki sotuvdan olinayotgan holatlar uchun himoyadir."
              submitLabel="Sotuvdan to'liq olib tashlash"
              submitVariant="danger"
              confirmText="Haqiqatan ham bu kitobni barcha do'konlardan sotuvdan olib tashlamoqchimisiz? Kitob savat va sevimlilardan o'chiriladi."
              onSubmit={submitBan}
            >
              <div className="alert alert-danger f-s-13 mb-3">
                <i className="ti ti-alert-triangle me-1"></i>
                Ushbu amal kitobning barcha do'kon takliflarini ({edition.offersCount} ta) darhol <strong>sotuvdan yashiradi</strong> va <strong>arxivlaydi</strong>. Xaridorlarning <strong>savati</strong> va <strong>sevimlilaridan</strong> butunlay tozalanadi.
                <br /><br />
                <em>Eslatma: Avval sotib olingan va to'langan buyurtmalar tarixi hamda BookClub muhokamalari saqlanib qoladi.</em>
              </div>
              <div className="mb-3">
                <label className="form-label f-w-600">Sotuvdan olish sababi (izoh)</label>
                <input
                  type="text"
                  name="reason"
                  className="form-control"
                  placeholder="Masalan: O'zbekistonda taqiqlangan adabiyot ro'yxatiga tushgan"
                  defaultValue="O'zbekistonda taqiqlangan adabiyot yoki sotuv cheklovi"
                />
              </div>
            </FormAction>
          ) : null}
        </div>
      </div>

      {/* ── Banned or Merged Alert Banner ── */}
      {isBanned ? (
        <div className="alert alert-danger d-flex align-items-center gap-3 p-3 mb-4 b-r-12 shadow-sm border-0">
          <div className="h-45 w-45 d-flex-center b-r-50 bg-white text-danger flex-shrink-0 f-s-24 shadow-sm">
            <i className="ti ti-ban"></i>
          </div>
          <div className="flex-grow-1">
            <h5 className="mb-1 text-danger f-w-700">Ushbu kitob boshqaruv tomonidan sotuvdan olib tashlangan</h5>
            <p className="mb-0 text-dark f-s-13">
              Mijozlar ilovasida va qidiruvda ko'rinmaydi. Barcha do'kon takliflari yashirilgan, savat va sevimlilardan chiqarilgan.
              Tarixiy sotilgan buyurtmalar va BookClub'dagi izohlar saqlanadi.
            </p>
          </div>
          <button type="button" className="btn btn-outline-danger btn-sm" onClick={submitUnban}>
            Qayta tiklash
          </button>
        </div>
      ) : null}

      {edition.mergedInto ? (
        <div className="alert alert-light-warning d-flex align-items-center gap-3 p-3 mb-4 b-r-12 border">
          <i className="ti ti-arrows-exchange f-s-24 text-warning"></i>
          <div>
            <h6 className="mb-0 f-w-600">Bu karta boshqa nashrga birlashtirilgan</h6>
            <p className="mb-0 text-secondary f-s-13">
              Barcha do'kon takliflari va ma'lumotlar <Link href={edition.mergedInto.url} className="f-w-700 text-primary">#{edition.mergedInto.id} {edition.mergedInto.title}</Link> ga o'tkazilgan.
            </p>
          </div>
        </div>
      ) : null}

      {/* ── 4 Top KPI Stat Widgets (Axelit eCommerce Ritm) ── */}
      <div className="row g-3 mb-4">
        <div className="col-xl-3 col-md-6">
          <StatWidget
            index={0}
            label="Eng yaxshi narx (BuyBox)"
            value={edition.minPrice ? `${fmt(edition.minPrice)} so'm` : 'Taklif yo\'q'}
            sub={buyBoxOffer ? `${buyBoxOffer.seller} tomonidan` : 'Do\'konlar ulanmagan'}
            variant="provided"
          />
        </div>
        <div className="col-xl-3 col-md-6">
          <StatWidget
            index={1}
            label="Do'kon takliflari"
            value={`${fmt(edition.offersCount)} ta`}
            sub={`${fmt(edition.inStockOffers)} tasi sotuvda mavjud`}
            variant="primary"
          />
        </div>
        <div className="col-xl-3 col-md-6">
          <StatWidget
            index={2}
            label="Ombor qoldig'i"
            value={`${fmt(totalStock)} dona`}
            sub={`${fmt(totalSold)} dona sotilgan`}
            variant="store"
          />
        </div>
        <div className="col-xl-3 col-md-6">
          <StatWidget
            index={3}
            label="Katalog Holati"
            value={label}
            sub={isBanned ? "Sotuv bloklangan" : edition.verified ? "Rasmiy tekshirilgan" : "Moderatsiyada"}
            variant={isBanned ? "danger" : "info"}
          />
        </div>
      </div>

      {/* ── Main Layout: Left Media Specs + Right Marketplace Tabs ── */}
      <div className="row g-4">
        {/* Left Column: Visual Media Showcase & Master Specs */}
        <div className="col-xl-4 col-lg-5">
          <div className="card border-0 shadow-sm b-r-16 overflow-hidden mb-4">
            <div className="card-body p-4 text-center bg-light-secondary bg-opacity-25 position-relative">
              {/* Cover Showcase with 3D Shadow */}
              <div className="d-flex justify-content-center mb-3">
                <div
                  className="position-relative b-r-12 overflow-hidden shadow"
                  style={{ width: 220, height: 320, background: '#f8f9fa' }}
                >
                  {selectedPhoto ? (
                    <img
                      src={selectedPhoto}
                      alt={edition.title}
                      className="w-100 h-100 object-fit-cover"
                    />
                  ) : (
                    <div className="w-100 h-100 d-flex-center text-muted f-s-40">
                      <i className="ti ti-book"></i>
                    </div>
                  )}

                  {/* Overlays */}
                  <div className="position-absolute top-0 start-0 m-2">
                    <span className="badge bg-dark bg-opacity-75 text-white f-s-12">
                      {edition.coverType || 'Yumshoq'}
                    </span>
                  </div>
                  {edition.lang ? (
                    <div className="position-absolute top-0 end-0 m-2">
                      <span className="badge bg-primary text-white f-s-12">
                        {edition.lang}
                      </span>
                    </div>
                  ) : null}
                </div>
              </div>

              {/* Multiple Photos Thumbnails */}
              {allImages.length > 1 ? (
                <div className="d-flex justify-content-center gap-2 flex-wrap mt-3">
                  {allImages.map((img, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => setSelectedPhoto(img)}
                      className={`btn p-0 border b-r-8 overflow-hidden ${selectedPhoto === img ? 'ring-2 border-primary shadow-sm' : 'opacity-75'}`}
                      style={{ width: 44, height: 60 }}
                    >
                      <img src={img} alt="" className="w-100 h-100 object-fit-cover" />
                    </button>
                  ))}
                </div>
              ) : null}

              {/* Front / Back Switch Quick Buttons */}
              {edition.frontUrl && edition.backUrl ? (
                <div className="btn-group btn-group-sm mt-3 shadow-xs">
                  <button
                    type="button"
                    className={`btn ${selectedPhoto === edition.frontUrl ? 'btn-primary' : 'btn-light-secondary'}`}
                    onClick={() => setSelectedPhoto(edition.frontUrl || null)}
                  >
                    Old muqova
                  </button>
                  <button
                    type="button"
                    className={`btn ${selectedPhoto === edition.backUrl ? 'btn-primary' : 'btn-light-secondary'}`}
                    onClick={() => setSelectedPhoto(edition.backUrl || null)}
                  >
                    Orqa muqova
                  </button>
                </div>
              ) : null}
            </div>

            {/* Quick Specs List */}
            <div className="card-body p-4 border-top">
              <h6 className="f-w-700 text-dark mb-3 d-flex align-items-center gap-2">
                <i className="ti ti-list-details text-primary"></i>
                Katalog Xarakteristikalari
              </h6>

              <ul className="list-unstyled mb-0">
                <li className="d-flex justify-content-between py-2 border-bottom">
                  <span className="text-secondary f-s-13">ISBN-13</span>
                  <span className="f-w-600 text-dark font-monospace d-flex align-items-center gap-1">
                    {edition.isbn || '—'}
                    {edition.isbn ? (
                      <button
                        type="button"
                        className="btn btn-link p-0 text-secondary"
                        onClick={() => copyIsbn(edition.isbn || '')}
                      >
                        <i className="ti ti-copy f-s-14"></i>
                      </button>
                    ) : null}
                  </span>
                </li>
                <li className="d-flex justify-content-between py-2 border-bottom">
                  <span className="text-secondary f-s-13">Nashriyot</span>
                  <span className="f-w-600 text-dark">{edition.publisher || '—'}</span>
                </li>
                <li className="d-flex justify-content-between py-2 border-bottom">
                  <span className="text-secondary f-s-13">Kategoriya</span>
                  <span className="f-w-600 text-primary">{edition.category || '—'}</span>
                </li>
                <li className="d-flex justify-content-between py-2 border-bottom">
                  <span className="text-secondary f-s-13">Til / Yozuv</span>
                  <span className="f-w-600 text-dark">{[edition.lang, edition.langType].filter(Boolean).join(' / ') || '—'}</span>
                </li>
                <li className="d-flex justify-content-between py-2 border-bottom">
                  <span className="text-secondary f-s-13">Muqova</span>
                  <span className="f-w-600 text-dark">{edition.coverType || '—'}</span>
                </li>
                <li className="d-flex justify-content-between py-2 border-bottom">
                  <span className="text-secondary f-s-13">Sahifalar soni</span>
                  <span className="f-w-600 text-dark">{edition.pages ? `${edition.pages} bet` : '—'}</span>
                </li>
                <li className="d-flex justify-content-between py-2 border-bottom">
                  <span className="text-secondary f-s-13">Chop etilgan yili</span>
                  <span className="f-w-600 text-dark">{edition.year || '—'}</span>
                </li>
                {edition.translator ? (
                  <li className="d-flex justify-content-between py-2 border-bottom">
                    <span className="text-secondary f-s-13">Tarjimon</span>
                    <span className="f-w-600 text-dark">{edition.translator}</span>
                  </li>
                ) : null}
                <li className="d-flex justify-content-between py-2">
                  <span className="text-secondary f-s-13">Manba</span>
                  <span className="badge bg-light-secondary text-secondary">{edition.source || 'admin'}</span>
                </li>
              </ul>
            </div>
          </div>
          <EditionVideoCard base={base} video={video} />
        </div>

        {/* Right Column: Marketplace Ecosystem Tabs */}
        <div className="col-xl-8 col-lg-7">
          <div className="card border-0 shadow-sm b-r-16">
            {/* Header Tabs (Axelit Segment Pill Style) */}
            <div className="card-header bg-transparent border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div className="nav kc-segment">
                <div className="nav-item">
                  <button
                    type="button"
                    className={`nav-link ${activeTab === 'offers' ? 'active' : ''}`}
                    onClick={() => setActiveTab('offers')}
                  >
                    <i className="ti ti-building-store me-1"></i>
                    Do'kon takliflari
                    <span className="badge bg-light-primary text-primary ms-2">{offers.length}</span>
                  </button>
                </div>
                <div className="nav-item">
                  <button
                    type="button"
                    className={`nav-link ${activeTab === 'about' ? 'active' : ''}`}
                    onClick={() => setActiveTab('about')}
                  >
                    <i className="ti ti-notes me-1"></i>
                    Annotatsiya
                  </button>
                </div>
                <div className="nav-item">
                  <button
                    type="button"
                    className={`nav-link ${activeTab === 'submissions' ? 'active' : ''}`}
                    onClick={() => setActiveTab('submissions')}
                  >
                    <i className="ti ti-inbox me-1"></i>
                    Arizalar
                    {submissions.length ? (
                      <span className="badge bg-light-warning text-warning-dark ms-2">{submissions.length}</span>
                    ) : null}
                  </button>
                </div>
                <div className="nav-item">
                  <button
                    type="button"
                    className={`nav-link ${activeTab === 'merge' ? 'active' : ''}`}
                    onClick={() => setActiveTab('merge')}
                  >
                    <i className="ti ti-arrows-exchange me-1"></i>
                    Birlashtirish
                    {mergeCandidates.length ? (
                      <span className="badge bg-light-info text-info ms-2">{mergeCandidates.length}</span>
                    ) : null}
                  </button>
                </div>
              </div>
            </div>

            {/* Tab 1: Do'kon takliflari (Offers & BuyBox) */}
            {activeTab === 'offers' && (
              <div className="card-body p-0">
                <div className="p-3 bg-light-primary bg-opacity-25 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                  <div className="d-flex align-items-center gap-2">
                    <span className="h-35 w-35 d-flex-center b-r-50 bg-primary text-white">
                      <i className="ti ti-crown f-s-18"></i>
                    </span>
                    <div>
                      <h6 className="mb-0 f-w-700 text-dark">BuyBox Tanlovi</h6>
                      <small className="text-secondary">Xaridorlar ilovasida eng arzon, ishonchli va sotuvda bor do'kon ko'rsatiladi</small>
                    </div>
                  </div>
                  {buyBoxOffer ? (
                    <span className="badge bg-success-300 text-success-dark f-w-600 f-s-13 px-3 py-2">
                      G'olib: {buyBoxOffer.seller} ({fmt(buyBoxOffer.effectivePrice)} so'm)
                    </span>
                  ) : null}
                </div>

                <div className="table-responsive app-scroll">
                  <table className="table table-hover align-middle mb-0">
                    <thead className="table-light">
                      <tr>
                        <th className="ps-4">Do'kon</th>
                        <th>Narxi</th>
                        <th>Ombor qoldig'i</th>
                        <th>Sotilgan</th>
                        <th>BuyBox</th>
                        <th>Holati</th>
                        <th className="pe-4 text-end">Amal</th>
                      </tr>
                    </thead>
                    <tbody>
                      {offers.map((offer) => {
                        const isWinner = offer.featured;
                        const isLowStock = offer.stock > 0 && offer.stock <= 5;
                        const isOutOfStock = offer.stock === 0;

                        return (
                          <tr key={offer.id} className={isWinner ? 'bg-light-primary bg-opacity-10' : ''}>
                            <td className="ps-4">
                              <div className="d-flex align-items-center gap-2">
                                <div className="h-35 w-35 d-flex-center b-r-8 bg-light-secondary text-dark f-w-700 flex-shrink-0">
                                  {offer.seller.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                  <span className="f-w-600 text-dark d-block">{offer.seller}</span>
                                  <small className="text-muted">
                                    ID #{offer.id}{offer.artikul ? ` · ${offer.artikul}` : ''}
                                  </small>
                                </div>
                              </div>
                            </td>

                            <td>
                              <div>
                                <span className="f-w-700 text-dark f-s-15">
                                  {fmt(offer.effectivePrice)} so'm
                                </span>
                                {offer.discountPrice > 0 && offer.discountPrice < offer.price ? (
                                  <small className="d-block text-muted text-decoration-line-through">
                                    {fmt(offer.price)} so'm
                                  </small>
                                ) : null}
                              </div>
                            </td>

                            <td>
                              {isOutOfStock ? (
                                <span className="badge bg-danger-300 text-danger-dark f-w-600">
                                  Tugagan (0)
                                </span>
                              ) : isLowStock ? (
                                <span className="badge bg-warning-300 text-warning-dark f-w-600">
                                  {fmt(offer.stock)} dona (Kam)
                                </span>
                              ) : (
                                <span className="badge bg-success-300 text-success-dark f-w-600">
                                  {fmt(offer.stock)} dona
                                </span>
                              )}
                              {offer.preorderDate ? (
                                <div><span className="badge text-light-warning mt-1">Predzakaz · {offer.preorderDate} dan</span></div>
                              ) : null}
                            </td>

                            <td>
                              <span className="f-w-600 text-secondary d-flex align-items-center gap-1">
                                <i className="ti ti-shopping-bag text-muted"></i>
                                {fmt(offer.sold)} dona
                              </span>
                            </td>

                            <td>
                              {isWinner ? (
                                <span className="badge bg-warning-300 text-warning-dark f-w-700 px-2 py-1 shadow-xs">
                                  <i className="ti ti-crown me-1"></i>Tanlangan
                                </span>
                              ) : (
                                <span className="text-muted f-s-13">—</span>
                              )}
                            </td>

                            <td>
                              {offer.archived ? (
                                <span className="badge text-light-secondary">Arxiv</span>
                              ) : offer.approved === 1 ? (
                                offer.active && !offer.hidden && offer.sellerActive ? (
                                  <span className="badge text-light-success">
                                    <i className="ti ti-check me-1"></i>Sotuvda
                                  </span>
                                ) : (
                                  <span className="badge text-light-secondary">Yashirin</span>
                                )
                              ) : offer.approved === 2 ? (
                                <span className="badge text-light-danger">Rad etilgan</span>
                              ) : (
                                <span className="badge text-light-warning">Moderatsiya</span>
                              )}
                            </td>

                            <td className="pe-4 text-end">
                              <a
                                href={offer.url}
                                className="btn btn-light-primary icon-btn w-32 h-32 b-r-8"
                                title="Taklifni to'liq ko'rish"
                              >
                                <i className="ti ti-external-link"></i>
                              </a>
                            </td>
                          </tr>
                        );
                      })}

                      {!offers.length ? (
                        <tr>
                          <td colSpan={7} className="py-5 text-center">
                            <EmptyState
                              icon="ti ti-building-store"
                              text="Hozircha birorta ham do'kon ushbu kitobga taklif kiritmagan."
                            />
                          </td>
                        </tr>
                      ) : null}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* Tab 2: Annotatsiya va Tavsif */}
            {activeTab === 'about' && (
              <div className="card-body p-4">
                <div className="mb-4">
                  <h6 className="f-w-700 text-dark mb-2 d-flex align-items-center gap-2">
                    <i className="ti ti-file-text text-primary"></i>
                    Kitob annotatsiyasi va rasmiy tavsifi
                  </h6>
                  {edition.description ? (
                    <div
                      className="p-4 bg-light-secondary bg-opacity-25 b-r-12 text-dark f-s-15 leading-relaxed"
                      style={{ whiteSpace: 'pre-wrap', lineHeight: 1.7 }}
                    >
                      {edition.description}
                    </div>
                  ) : (
                    <div className="alert alert-light-warning d-flex align-items-center gap-2">
                      <i className="ti ti-alert-triangle f-s-20"></i>
                      <span>Ushbu kitob uchun hali tavsif kiritilmagan. Yuqoridagi "Tahrirlash" orqali tavsif qo'shishingiz mumkin.</span>
                    </div>
                  )}
                </div>

                <div className="pt-3 border-top">
                  <h6 className="f-w-700 text-dark mb-2">Qidiruv teglari va xususiyatlari</h6>
                  <div className="d-flex gap-2 flex-wrap">
                    <span className="badge bg-light-primary text-primary px-3 py-2 b-r-8">{edition.title}</span>
                    {edition.author ? <span className="badge bg-light-info text-info px-3 py-2 b-r-8">{edition.author}</span> : null}
                    {edition.publisher ? <span className="badge bg-light-secondary text-dark px-3 py-2 b-r-8">{edition.publisher}</span> : null}
                    {edition.category ? <span className="badge bg-light-success text-success px-3 py-2 b-r-8">{edition.category}</span> : null}
                  </div>
                </div>
              </div>
            )}

            {/* Tab 3: Do'kon arizalari (Submissions) */}
            {activeTab === 'submissions' && (
              <div className="card-body p-4">
                <div className="d-flex align-items-center justify-content-between mb-3">
                  <h6 className="f-w-700 text-dark mb-0">Do'konlardan kelgan arizalar</h6>
                  <span className="text-secondary f-s-13">Jami {submissions.length} ta ariza</span>
                </div>

                {submissions.length ? (
                  <div className="d-flex flex-col gap-3">
                    {submissions.map((item) => {
                      const [checkLabel, checkChip, checkIcon] = isbnCheckChip(item.isbnCheck);

                      return (
                        <div key={item.id} className="p-3 border b-r-12 bg-white hover-shadow transition">
                          <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div className="d-flex align-items-center gap-2">
                              <span className="badge bg-light-primary text-primary f-w-600">Ariza #{item.id}</span>
                              <span className="f-w-600 text-dark">{item.seller}</span>
                              <span className="text-muted f-s-12">· {item.createdAt}</span>
                            </div>
                            <span className={`badge ${checkChip} d-flex align-items-center gap-1`}>
                              <i className={checkIcon}></i>
                              {checkLabel}
                            </span>
                          </div>

                          <div className="d-flex align-items-center gap-3 text-secondary f-s-13 flex-wrap">
                            <span>ISBN: <code className="text-dark f-w-600">{item.isbn || '—'}</code></span>
                            {item.backIsbnServer ? (
                              <span>Orqa muqovadan o'qildi: <code className="text-success f-w-600">{item.backIsbnServer}</code> ({item.backIsbnMethod})</span>
                            ) : null}
                            <span className="badge text-light-secondary ms-auto">{item.status}</span>
                          </div>

                          {item.message ? (
                            <p className="mb-0 mt-2 text-dark f-s-13 bg-light p-2 b-r-8">
                              <i className="ti ti-message-dots me-1 text-secondary"></i>
                              {item.message}
                            </p>
                          ) : null}
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <EmptyState
                    icon="ti ti-inbox"
                    text="Ushbu nashr uchun do'konlardan arizalar kelib tushmagan."
                  />
                )}
              </div>
            )}

            {/* Tab 4: O'xshash kartalar & Birlashtirish */}
            {activeTab === 'merge' && (
              <div className="card-body p-4">
                <div className="d-flex align-items-center justify-content-between mb-3">
                  <div>
                    <h6 className="f-w-700 text-dark mb-0">Dublikat yoki O'xshash Nashrlar</h6>
                    <small className="text-secondary">Bir xil kitob bir nechta nusxada ochilgan bo'lsa, ularni bitta master-kartaga birlashtiring</small>
                  </div>
                </div>

                {mergeCandidates.length ? (
                  <div className="list-group">
                    {mergeCandidates.map((candidate) => (
                      <div key={candidate.id} className="list-group-item d-flex align-items-center justify-content-between p-3 flex-wrap gap-2">
                        <div className="d-flex align-items-center gap-3">
                          <div className="w-40 h-55 b-r-8 overflow-hidden bg-light flex-shrink-0 border">
                            {candidate.cover ? <img src={candidate.cover} alt="" className="w-100 h-100 object-fit-cover" /> : <i className="ti ti-book"></i>}
                          </div>
                          <div>
                            <Link href={candidate.url} className="f-w-600 text-dark d-block hover-primary">
                              #{candidate.id} · {candidate.title}
                            </Link>
                            <small className="text-secondary d-block">
                              {candidate.author || '—'} · ISBN: {candidate.isbn || "ISBN yo'q"} · {candidate.offersCount} ta taklif
                            </small>
                            {candidate.variantDiff?.length ? (
                              <small className="badge bg-danger-300 text-danger-dark mt-1">
                                Farqlar mavjud: {candidate.variantDiff.join(', ')}
                              </small>
                            ) : (
                              <small className="badge bg-success-300 text-success-dark mt-1">
                                Aniq mos keluvchi dublikat
                              </small>
                            )}
                          </div>
                        </div>

                        <Link href={candidate.url} className="btn btn-outline-primary btn-sm">
                          Ko'rish
                        </Link>
                      </div>
                    ))}
                  </div>
                ) : (
                  <EmptyState
                    icon="ti ti-arrows-exchange"
                    text="Tizimda ushbu kitobga o'xshash boshqa dublikat kartalar topilmadi."
                  />
                )}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
