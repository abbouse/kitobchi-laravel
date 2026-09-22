import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import PaginationControls from '../components/PaginationControls';
import ModerationRejectModal from '../components/ModerationRejectModal';
import { EmptyState, StatWidget } from '../components/Axelit';
import { isbnCheckChip, type PickedEdition } from '../components/CatalogFields';

interface Submission {
  id: number;
  status: string;
  seller: string;
  isbn?: string | null;
  isbnCheck?: string | null;
  backIsbnServer?: string | null;
  backIsbnMethod?: string | null;
  backIsbnClient?: string | null;
  frontUrl?: string | null;
  backUrl?: string | null;
  payload?: Record<string, unknown> | null;
  editionId?: number | null;
  bookId?: number | null;
  rejectReason?: string | null;
  createdAt?: string;
  reviewedAt?: string | null;
  approveUrl: string;
  rejectUrl: string;
  mergeUrl: string;
  edition?: (PickedEdition & { publisher?: string | null; category?: string | null; lang?: string | null; langType?: string | null; coverType?: string | null; pages?: number | null; year?: number | null; description?: string | null }) | null;
  duplicates?: PickedEdition[];
}

type Props = {
  submissions: Submission[];
  pagination: { page: number; totalPages: number; from: number; to: number; total: number };
  filters: { tab?: string };
  counts: Record<string, number>;
};

const STATUS: Record<string, [string, string]> = {
  pending: ['Kutilmoqda', 'text-light-warning'],
  approved: ['Tasdiqlangan', 'text-light-success'],
  rejected: ['Rad etilgan', 'text-light-danger'],
  merged: ['Birlashtirilgan', 'text-light-info'],
};

const METHOD: Record<string, string> = { zbar: 'shtrix-kod', vision: 'AI (raqamlar)' };

export default function CatalogSubmissions() {
  const { submissions = [], pagination, filters = {}, counts = {} } = usePage<Props>().props;
  const tab = filters.tab || 'pending';
  const [rejectTarget, setRejectTarget] = useState<Submission | null>(null);

  const load = (params: Record<string, string | number>) => {
    router.get('/boshqaruv/catalog/submissions', { tab, ...params }, { preserveState: true, preserveScroll: true, replace: true });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Kitob arizalari</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Do'konlar katalogda topmagan kitoblar: old va orqa muqova, ISBN tekshiruvi</p>
        </div>
        <Link href="/boshqaruv/catalog" className="btn btn-light-secondary btn-sm"><i className="ti ti-book me-1"></i>Global katalog</Link>
      </div>

      <div className="row">
        {[
          { key: 'pending', label: 'Kutilmoqda', sub: "Ko'rib chiqilishi kerak" },
          { key: 'approved', label: 'Tasdiqlangan', sub: 'Katalogga qo\'shildi' },
          { key: 'merged', label: 'Birlashtirilgan', sub: 'Mavjud kartaga ulandi' },
          { key: 'rejected', label: 'Rad etilgan', sub: "Do'konga sabab bilan qaytdi" },
        ].map((item, index) => (
          <div className="col-xl-3 col-md-6" key={item.key}>
            <StatWidget index={index} label={item.label} value={counts[item.key] || 0} sub={item.sub} selected={tab === item.key} onClick={() => load({ tab: item.key, page: 1 })} />
          </div>
        ))}
      </div>

      {submissions.map((item) => {
        const [checkLabel, checkChip, checkIcon] = isbnCheckChip(item.isbnCheck);
        const [statusLabel, statusChip] = STATUS[item.status] || [item.status, 'text-light-secondary'];
        const edition = item.edition;
        const pending = item.status === 'pending';

        return (
          <div className="card" key={item.id}>
            <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div className="min-w-0">
                <h5 className="mb-0 text-truncate">{edition?.title || String(item.payload?.name || 'Nomsiz')}</h5>
                <p className="mb-0 text-secondary f-s-13">#{item.id} · {item.seller} · {item.createdAt}</p>
              </div>
              <div className="d-flex gap-2 align-items-center flex-wrap">
                <span className={`badge ${checkChip}`}><i className={`${checkIcon} me-1`}></i>{checkLabel}</span>
                <span className={`badge ${statusChip}`}>{statusLabel}</span>
              </div>
            </div>
            <div className="card-body">
              <div className="row g-3">
                <div className="col-md-5 col-xl-4">
                  <div className="row g-2">
                    {[['Old muqova', item.frontUrl], ['Orqa muqova', item.backUrl]].map(([label, url]) => (
                      <div className="col-6" key={label as string}>
                        <p className="f-s-12 text-secondary mb-1">{label}</p>
                        <a href={(url as string) || undefined} target="_blank" rel="noreferrer" className="d-block b-r-10 overflow-hidden bg-light-secondary h-200">
                          {url ? <img className="w-100 h-100 object-fit-cover" src={url as string} alt="" /> : null}
                        </a>
                      </div>
                    ))}
                  </div>
                </div>
                <div className="col-md-7 col-xl-8">
                  <div className="row g-3">
                    {[
                      ['Kiritilgan ISBN', item.isbn || '—'],
                      ['Orqa muqovadan (server)', item.backIsbnServer ? `${item.backIsbnServer} · ${METHOD[item.backIsbnMethod || ''] || item.backIsbnMethod}` : "o'qilmadi"],
                      ['Ilova o\'qigan', item.backIsbnClient || '—'],
                      ['Muallif', edition?.author || String(item.payload?.author || '—')],
                      ['Nashriyot', edition?.publisher || String(item.payload?.publisher || '—')],
                      ['Kategoriya', edition?.category || '—'],
                      ['Til / yozuv / muqova', [edition?.lang, edition?.langType, edition?.coverType].filter(Boolean).join(' / ') || '—'],
                      ['Sahifa / yil', [edition?.pages ? `${edition.pages} bet` : null, edition?.year].filter(Boolean).join(' / ') || '—'],
                    ].map(([label, value]) => (
                      <div className="col-sm-6 col-xl-3" key={label}>
                        <p className="mb-1 f-s-12 text-secondary">{label}</p>
                        <h6 className="mb-0 f-s-14 f-w-600 text-break">{value}</h6>
                      </div>
                    ))}
                  </div>
                  {edition?.description ? <p className="text-secondary f-s-13 mt-3 mb-0" style={{ whiteSpace: 'pre-wrap' }}>{edition.description}</p> : null}
                  {item.rejectReason ? <div className="alert alert-light-danger mt-3 mb-0 f-s-13">Rad etish sababi: {item.rejectReason}</div> : null}

                  {item.duplicates && item.duplicates.length ? (
                    <div className="mt-3">
                      <p className="f-w-600 mb-2"><i className="ti ti-alert-triangle text-warning me-1"></i>Katalogda shu ISBN bilan karta bor</p>
                      {item.duplicates.map((dup) => (
                        <div key={dup.id} className="d-flex align-items-center gap-3 p-2 b-r-10 bg-light-warning mb-2">
                          <div className="w-30 h-40 b-r-5 overflow-hidden d-flex-center bg-white flex-shrink-0">
                            {dup.cover ? <img className="w-100 h-100 object-fit-cover" src={dup.cover} alt="" /> : <i className="ti ti-book f-s-12"></i>}
                          </div>
                          <div className="min-w-0 flex-grow-1">
                            <Link href={dup.url || '#'} className="f-w-600 text-dark d-block text-truncate">{dup.title}</Link>
                            <small className="text-secondary">#{dup.id} · {dup.author || '—'} · {dup.offersCount} ta taklif</small>
                          </div>
                          {pending ? (
                            <button type="button" className="btn btn-warning btn-sm" onClick={() => { if (window.confirm("Ariza shu kartaga birlashtirilsinmi? Do'kon taklifi unga ulanadi.")) router.post(item.mergeUrl, { into_id: dup.id }, { preserveScroll: true }); }}>
                              <i className="ti ti-arrows-exchange me-1"></i>Shunga ulash
                            </button>
                          ) : null}
                        </div>
                      ))}
                    </div>
                  ) : null}

                  <div className="d-flex gap-2 flex-wrap mt-3">
                    {edition?.url ? <Link href={edition.url} className="btn btn-light-primary btn-sm"><i className="ti ti-edit me-1"></i>Kartani ochish / tahrirlash</Link> : null}
                    {pending ? (
                      <>
                        <button type="button" className="btn btn-light-danger btn-sm" onClick={() => setRejectTarget(item)}><i className="ti ti-x me-1"></i>Rad etish</button>
                        <button type="button" className="btn btn-success btn-sm" onClick={() => router.post(item.approveUrl, {}, { preserveScroll: true })}><i className="ti ti-check me-1"></i>Tasdiqlash</button>
                      </>
                    ) : null}
                  </div>
                </div>
              </div>
            </div>
          </div>
        );
      })}

      {!submissions.length ? <div className="card"><div className="card-body"><EmptyState text="Bu bo'limda ariza yo'q" /></div></div> : null}
      <PaginationControls {...pagination} onPageChange={(page) => load({ page })} />

      <ModerationRejectModal
        show={!!rejectTarget}
        itemLabel={rejectTarget?.edition?.title}
        onCancel={() => setRejectTarget(null)}
        onConfirm={(reason) => {
          if (rejectTarget) router.post(rejectTarget.rejectUrl, { reason }, { preserveScroll: true });
          setRejectTarget(null);
        }}
      />
    </div>
  );
}
