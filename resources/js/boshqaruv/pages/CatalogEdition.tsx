import { type FormEvent } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import FormAction from '../components/FormAction';
import { EmptyState } from '../components/Axelit';
import { AboutList, MediaCard } from '../components/Profile';
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
  rawImages?: string[];
  images?: (string | null)[];
  variant?: string;
  variantDiff?: string[];
  mergedInto?: { id: number; title: string; url: string } | null;
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
  condition: string;
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
  status: string;
  seller: string;
  isbn?: string | null;
  isbnCheck?: string | null;
  backIsbnServer?: string | null;
  backIsbnMethod?: string | null;
  createdAt?: string;
  rejectReason?: string | null;
}

type Props = {
  edition: Edition;
  offers: Offer[];
  submissions: Submission[];
  mergeCandidates: Edition[];
  formOptions: { categories: OptionItem[]; publishers: OptionItem[] };
};

const CONDITION: Record<string, string> = { new: 'Yangi', used_good: 'Ishlatilgan (yaxshi)', used_fair: 'Ishlatilgan' };

export default function CatalogEdition() {
  const { edition, offers = [], submissions = [], mergeCandidates = [], formOptions } = usePage<Props>().props;
  const [label, chip] = editionStatus(edition.status, edition.verified);
  const base = `/boshqaruv/catalog/${edition.id}`;

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

  const keepItems = (edition.rawImages || []).map((path, index) => ({ path, url: edition.images?.[index] || null }));

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">{edition.title}</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Katalog kartasi #{edition.id}{edition.variant ? ` · ${edition.variant}` : ''}</p>
        </div>
        <div className="d-flex gap-2 flex-wrap">
          <Link href="/boshqaruv/catalog" className="btn btn-light-secondary btn-sm"><i className="ti ti-arrow-left me-1"></i>Katalog</Link>
          {!edition.deleted && edition.status !== 'merged' && (!edition.verified || edition.status !== 'active') ? (
            <button type="button" className="btn btn-success btn-sm" onClick={() => router.post(`${base}/verify`, {}, { preserveScroll: true })}>
              <i className="ti ti-circle-check me-1"></i>Tasdiqlash
            </button>
          ) : null}
          {!edition.deleted && edition.status !== 'merged' ? (
            <FormAction label="Tahrirlash" icon="ti ti-edit" variant="light-primary" modalSize="lg" title="Kitob kartasini tahrirlash" description="Saqlangach nom, muallif, muqova va tavsif shu kitobning barcha do'kon takliflariga ko'chiriladi." onSubmit={submitUpdate}>
              <EditionFields values={edition} options={formOptions} />
              <hr />
              <label className="form-label">Mavjud rasmlar</label>
              <KeepImages items={keepItems} />
              <div className="mt-3"><CoverInputs requireFront={false} /></div>
              <div className="alert alert-light-primary mt-3 mb-0 f-s-13">
                <i className="ti ti-info-circle me-1"></i>
                Saqlangach ma'lumot shu kartaga ulangan barcha do'kon takliflariga avtomatik ko'chiriladi.
              </div>
            </FormAction>
          ) : null}
          {!edition.deleted && edition.status !== 'merged' ? (
            <FormAction label="Birlashtirish" icon="ti ti-arrows-exchange" variant="light-warning" title="Boshqa kartaga birlashtirish" description="Bu kartaning barcha takliflari, sharhlari va arizalari tanlangan kartaga o'tadi. Bu karta 'birlashtirilgan' bo'lib qoladi." submitLabel="Birlashtirish" submitVariant="warning" confirmText="Kartalar birlashtirilsinmi?" onSubmit={submitMerge}>
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
                          <small className="d-block text-danger"><i className="ti ti-alert-triangle me-1"></i>Boshqa nashr — birlashtirilmasin ({item.variantDiff.join(', ')})</small>
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
          {edition.deleted ? (
            <button type="button" className="btn btn-light-success btn-sm" onClick={() => router.patch(`${base}/restore`, {}, { preserveScroll: true })}><i className="ti ti-rotate me-1"></i>Tiklash</button>
          ) : edition.status !== 'merged' ? (
            <button type="button" className="btn btn-light-danger btn-sm" onClick={() => { if (window.confirm("Karta o'chirilsinmi? Faol takliflari bo'lsa, o'chirilmaydi.")) router.delete(base, { preserveScroll: true }); }}>
              <i className="ti ti-trash me-1"></i>O'chirish
            </button>
          ) : null}
        </div>
      </div>

      {edition.mergedInto ? (
        <div className="alert alert-light-warning d-flex align-items-center gap-2">
          <i className="ti ti-arrows-exchange f-s-18"></i>
          <span>Bu karta <Link href={edition.mergedInto.url} className="f-w-600">#{edition.mergedInto.id} {edition.mergedInto.title}</Link> ga birlashtirilgan.</span>
        </div>
      ) : null}

      <div className="row">
        <div className="col-lg-4 col-xxl-3">
          <MediaCard
            image={edition.cover}
            title={edition.title}
            subtitle={edition.author}
            badges={<>
              <span className={`badge ${chip}`}>{label}</span>
              {edition.deleted ? <span className="badge text-light-danger">O'chirilgan</span> : null}
            </>}
            stats={[
              { label: 'Taklif', value: fmt(edition.offersCount) },
              { label: 'Sotuvda', value: fmt(edition.inStockOffers) },
              { label: 'Eng arzon', value: edition.minPrice ? fmt(edition.minPrice) : '—' },
            ]}
          />
          {edition.backUrl ? (
            <div className="card">
              <div className="card-header"><h5 className="mb-0">Orqa muqova</h5></div>
              <div className="card-body">
                <a href={edition.backUrl} target="_blank" rel="noreferrer"><img className="w-100 b-r-10" src={edition.backUrl} alt="" /></a>
              </div>
            </div>
          ) : null}
        </div>

        <div className="col-lg-8 col-xxl-9">
          <AboutList
            title="Kitob ma'lumotlari"
            rows={[
              { icon: 'ti-qrcode', label: 'ISBN', value: edition.isbn },
              { icon: 'ti-user', label: 'Muallif', value: edition.author },
              { icon: 'ti-language', label: 'Tarjimon', value: edition.translator },
              { icon: 'ti-building', label: 'Nashriyot', value: edition.publisher },
              { icon: 'ti-bookmarks', label: 'Kategoriya', value: edition.category },
              { icon: 'ti-language', label: 'Til / yozuv', value: [edition.lang, edition.langType].filter(Boolean).join(' / ') },
              { icon: 'ti-book', label: 'Muqova / sahifa', value: [edition.coverType, edition.pages ? `${edition.pages} bet` : null].filter(Boolean).join(' / ') },
              { icon: 'ti-calendar', label: 'Yil', value: edition.year },
              { icon: 'ti-clock', label: 'Yaratilgan', value: edition.createdAt },
            ]}
          >
            {edition.description ? <p className="text-secondary f-s-13 mb-3" style={{ whiteSpace: 'pre-wrap' }}>{edition.description}</p> : null}
          </AboutList>

          <div className="card">
            <div className="card-header">
              <h5 className="mb-0">Do'kon takliflari</h5>
              <p className="mb-0 text-secondary f-s-13">Mijoz ro'yxatlarida "tanlangan" taklif chiqadi: sotuvda bor, eng arzon, ishonchli do'kon</p>
            </div>
            <div className="card-body">
              <div className="table-responsive app-scroll">
                <table className="table table-bottom-border align-middle mb-0">
                  <thead><tr><th>Do'kon</th><th>Narx</th><th>Qoldiq</th><th>Holat</th><th>Sotilgan</th><th>Status</th><th></th></tr></thead>
                  <tbody>
                    {offers.map((offer) => (
                      <tr key={offer.id}>
                        <td>
                          <span className="f-w-600">{offer.seller}</span>
                          <small className="d-block text-secondary">#{offer.id}{offer.artikul ? ` · ${offer.artikul}` : ''}</small>
                        </td>
                        <td>
                          <span className="f-w-600">{fmt(offer.effectivePrice)} so'm</span>
                          {offer.effectivePrice < offer.price ? <small className="d-block text-secondary text-decoration-line-through">{fmt(offer.price)}</small> : null}
                        </td>
                        <td>{fmt(offer.stock)}</td>
                        <td>{CONDITION[offer.condition] || offer.condition}</td>
                        <td>{fmt(offer.sold)}</td>
                        <td>
                          <div className="d-flex gap-1 flex-wrap">
                            {offer.featured ? <span className="badge text-light-success"><i className="ti ti-crown me-1"></i>Tanlangan</span> : null}
                            {offer.archived ? <span className="badge text-light-secondary">Arxiv</span>
                              : offer.approved === 1 ? (offer.active && !offer.hidden && offer.sellerActive ? <span className="badge text-light-primary">Sotuvda</span> : <span className="badge text-light-secondary">Yashirin</span>)
                              : offer.approved === 2 ? <span className="badge text-light-danger">Rad etilgan</span>
                              : <span className="badge text-light-warning">Moderatsiya</span>}
                          </div>
                        </td>
                        <td className="text-end"><a href={offer.url} className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" title="Kitoblar sahifasida ochish"><i className="ti ti-external-link"></i></a></td>
                      </tr>
                    ))}
                    {!offers.length ? <tr><td colSpan={7}><EmptyState text="Hali taklif yo'q" /></td></tr> : null}
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          {submissions.length ? (
            <div className="card">
              <div className="card-header"><h5 className="mb-0">Do'kon arizalari</h5></div>
              <div className="card-body">
                {submissions.map((item) => {
                  const [checkLabel, checkChip, checkIcon] = isbnCheckChip(item.isbnCheck);
                  return (
                    <div key={item.id} className="d-flex align-items-center gap-3 py-2 b-b-1-light flex-wrap">
                      <span className="f-w-600">#{item.id} · {item.seller}</span>
                      <span className={`badge ${checkChip}`}><i className={`${checkIcon} me-1`}></i>{checkLabel}</span>
                      <span className="text-secondary f-s-13">{item.isbn || '—'}{item.backIsbnServer ? ` · orqa: ${item.backIsbnServer} (${item.backIsbnMethod})` : ''}</span>
                      <span className="badge text-light-secondary ms-auto">{item.status}</span>
                      <small className="text-secondary">{item.createdAt}</small>
                    </div>
                  );
                })}
              </div>
            </div>
          ) : null}
        </div>
      </div>
    </div>
  );
}
