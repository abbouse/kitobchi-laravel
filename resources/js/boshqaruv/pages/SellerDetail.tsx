import { toneOf } from '../utils/tone';
import { Fragment, FormEvent, useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import PaginationControls from '../components/PaginationControls';
import {
  Seller, SellerOrderRow, StatusMeta, PaginationMeta,
  fmt, badgeClass, sellerChip, sellerLabel, karmaChip, initialsOf,
  Info, ListBlock, MapButtons,
} from '../components/SellerCommon';

type DetailProps = Seller & {
  isStaffView: boolean;
  isStaffOwner: boolean;
  backUrl: string;
  locationsPagination: PaginationMeta;
  documentsPagination: PaginationMeta;
  contractHistoryPagination: PaginationMeta;
  sellerOrders: SellerOrderRow[];
  sellerOrdersPagination: PaginationMeta;
  sellerOrderStatuses: Record<string, StatusMeta>;
  transactionsPagination: PaginationMeta;
  banLogsPagination: PaginationMeta;
};

const TABS = [
  { key: 'overview', label: 'Umumiy', icon: 'bi-person-lines-fill' },
  { key: 'branches', label: 'Filiallar & QR', icon: 'bi-shop' },
  { key: 'contract', label: 'Shartnoma', icon: 'bi-file-earmark-text' },
  { key: 'legal', label: 'Rekvizitlar', icon: 'bi-bank' },
  { key: 'documents', label: 'Hujjatlar', icon: 'bi-folder2-open' },
  { key: 'staff', label: 'Xodimlar', icon: 'bi-people' },
  { key: 'orders', label: 'Buyurtmalar', icon: 'bi-receipt' },
  { key: 'transactions', label: 'Tranzaksiyalar', icon: 'bi-cash-coin' },
  { key: 'activity', label: 'Ogohlantirishlar', icon: 'bi-shield-exclamation' },
];

function currentQuery(): Record<string, string> {
  return Object.fromEntries(new URLSearchParams(window.location.search));
}

export default function SellerDetail() {
  const seller = usePage<DetailProps>().props;
  const [tab, setTab] = useState<string>(() => currentQuery().tab || 'overview');

  const changeTab = (key: string) => {
    setTab(key);
    const params = new URLSearchParams(window.location.search);
    params.set('tab', key);
    window.history.replaceState(null, '', `${window.location.pathname}?${params.toString()}`);
  };

  const paginate = (key: string, page: number) => {
    router.get(window.location.pathname, { ...currentQuery(), [key]: page }, { preserveState: true, preserveScroll: true, replace: true });
  };

  const runPatch = (url?: string, message?: string, payload: Record<string, string> = {}) => {
    if (!url || (message && !confirm(message))) return;
    router.patch(url, payload, { preserveScroll: true });
  };

  const warnSeller = () => {
    const title = prompt('Ogohlantirish sarlavhasi', 'Admin ogohlantirishi');
    if (!title) return;
    const message = prompt('Ogohlantirish matni', 'Iltimos, marketplace qoidalariga amal qiling.');
    if (!message) return;
    router.post(seller.actions?.warnUrl || '', { title, message }, { preserveScroll: true });
  };

  const resetPassword = () => {
    if (!seller.actions?.resetPasswordUrl || !confirm(`${seller.name} uchun yangi parol SMS orqali yuborilsinmi?`)) return;
    router.post(seller.actions.resetPasswordUrl, {}, { preserveScroll: true });
  };

  const rotateQr = () => {
    if (!seller.actions?.rotateQrUrl || !confirm("Eski QR ishlamay qoladi. Yangilansinmi?")) return;
    router.post(seller.actions.rotateQrUrl, {}, { preserveScroll: true });
  };

  const uploadDocument = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!seller.actions?.uploadDocumentUrl) return;
    const form = event.currentTarget;
    router.post(seller.actions.uploadDocumentUrl, new FormData(form), { preserveScroll: true, onSuccess: () => form.reset() });
  };

  return (
    <div>
      <Link href={seller.backUrl} className="back-link">
        <i className="bi bi-arrow-left me-1"></i>Sotuvchilar
      </Link>

      <div className="card-panel seller-profile-header mb-4">
        <div className="d-flex flex-wrap justify-content-between gap-4">
          <div className="d-flex gap-3">
            <div className="resource-avatar xl">{seller.photo ? <img src={seller.photo} alt="" /> : initialsOf(seller.name)}</div>
            <div>
              <div className="d-flex flex-wrap align-items-center gap-2">
                <h1 className="page-title mb-0">{seller.name}</h1>
                <span className={`st ${toneOf(sellerChip(seller.status))}`}><i></i>{sellerLabel(seller.status)}</span>
                {seller.verified ? <span className="chip chip-info"><i className="bi bi-patch-check-fill me-1"></i>Tasdiqlangan</span> : null}
                {seller.premium ? <span className="chip chip-purple"><i className="bi bi-gem me-1"></i>Premium</span> : null}
              </div>
              <div className="text-muted mt-1">{seller.ownerName || '—'} · {seller.phone || '—'}</div>
              <div className="text-muted small">{[seller.region, seller.district].filter(Boolean).join(', ') || 'Hudud kiritilmagan'} · ID #{seller.id}</div>
            </div>
          </div>
          <div className="d-flex flex-wrap gap-2 align-self-start">
            <Link href={seller.actions?.editUrl || '#'} className="btn btn-sm btn-primary-gradient border-0"><i className="bi bi-pencil-square me-1"></i>Tahrirlash</Link>
            {seller.status !== 'approved' ? <button type="button" className="btn btn-sm btn-outline-success" onClick={() => runPatch(seller.actions?.approveUrl, 'Seller tasdiqlansinmi?')}><i className="bi bi-check2-circle me-1"></i>Tasdiqlash</button> : null}
            {seller.status !== 'rejected' ? <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => runPatch(seller.actions?.rejectUrl, 'Seller bekor qilinsinmi?')}><i className="bi bi-x-circle me-1"></i>Bekor qilish</button> : null}
            {seller.status === 'blocked' ? <button type="button" className="btn btn-sm btn-outline-primary" onClick={() => runPatch(seller.actions?.unblockUrl, 'Seller blokdan chiqarilsinmi?', { message: 'Admin tomonidan blokdan chiqarildi.' })}><i className="bi bi-unlock me-1"></i>Blokdan chiqarish</button> : null}
            <button type="button" className="btn btn-sm btn-outline-warning" onClick={warnSeller}><i className="bi bi-exclamation-triangle me-1"></i>Ogohlantirish</button>
            <button type="button" className="btn btn-sm btn-light" onClick={resetPassword}><i className="bi bi-key me-1"></i>Parol reset</button>
          </div>
        </div>
        <div className="row g-3 mt-1">
          <div className="col-xl-2 col-md-4 col-6"><MiniStatCard icon="bi-star-fill" label="Karma" value={`${Math.round(seller.karma || 0)}%`} /></div>
          <div className="col-xl-2 col-md-4 col-6"><MiniStatCard icon="bi-wallet2" label="Balans" value={`${fmt(seller.balance || 0)} so'm`} /></div>
          <div className="col-xl-2 col-md-4 col-6"><MiniStatCard icon="bi-graph-up-arrow" label="Tushum" value={`${fmt(seller.totalRevenue || 0)} so'm`} /></div>
          <div className="col-xl-2 col-md-4 col-6"><MiniStatCard icon="bi-box-seam" label="Mahsulot" value={String(seller.products || 0)} /></div>
          <div className="col-xl-2 col-md-4 col-6"><MiniStatCard icon="bi-receipt" label="Buyurtma" value={String(seller.orders || 0)} /></div>
          <div className="col-xl-2 col-md-4 col-6"><MiniStatCard icon="bi-shield-exclamation" label="Ogohlantirish" value={`${seller.warningCount || 0}/3`} /></div>
        </div>
      </div>

      <div className="seller-tabs mb-4">
        {TABS.map((item) => (
          <button key={item.key} type="button" className={`seller-tab-btn ${tab === item.key ? 'active' : ''}`} onClick={() => changeTab(item.key)}>
            <i className={`bi ${item.icon}`}></i>{item.label}
          </button>
        ))}
      </div>

      {tab === 'overview' ? <OverviewTab seller={seller} onSeeOrders={() => changeTab('orders')} /> : null}
      {tab === 'branches' ? <BranchesTab seller={seller} pagination={seller.locationsPagination} onPage={(p) => paginate('locations_page', p)} onRotateQr={rotateQr} /> : null}
      {tab === 'contract' ? <ContractTab seller={seller} pagination={seller.contractHistoryPagination} onPage={(p) => paginate('contract_history_page', p)} /> : null}
      {tab === 'legal' ? <LegalTab seller={seller} /> : null}
      {tab === 'documents' ? <DocumentsTab seller={seller} pagination={seller.documentsPagination} onPage={(p) => paginate('documents_page', p)} onUpload={uploadDocument} /> : null}
      {tab === 'staff' ? <StaffTab seller={seller} /> : null}
      {tab === 'orders' ? <OrdersTab orders={seller.sellerOrders || []} pagination={seller.sellerOrdersPagination} statuses={seller.sellerOrderStatuses || {}} onPage={(p) => paginate('orders_page', p)} onPatch={runPatch} /> : null}
      {tab === 'transactions' ? <TransactionsTab transactions={(seller.transactions as Array<Record<string, unknown>>) || []} pagination={seller.transactionsPagination} onPage={(p) => paginate('transactions_page', p)} /> : null}
      {tab === 'activity' ? <BanLogsTab banLogs={(seller.banLogs as Array<Record<string, unknown>>) || []} pagination={seller.banLogsPagination} onPage={(p) => paginate('ban_logs_page', p)} warningCount={seller.warningCount} /> : null}
    </div>
  );
}

function MiniStatCard({ icon, label, value }: { icon: string; label: string; value: string }) {
  return (
    <div className="mini-stat h-100">
      <i className={`bi ${icon}`}></i>
      <span>{label}</span>
      <strong>{value}</strong>
    </div>
  );
}

function OverviewTab({ seller, onSeeOrders }: { seller: DetailProps; onSeeOrders: () => void }) {
  return (
    <div className="row g-3">
      <div className="col-12">
        <div className="detail-panel">
          <div className="d-flex flex-wrap gap-2">
            {(seller.activityTypeLabels || []).length > 0
              ? (seller.activityTypeLabels || []).map((label) => <span className="chip chip-info" key={label}>{label}</span>)
              : <span className="chip chip-gray">Faoliyat turi belgilanmagan</span>}
            <span className={`chip ${seller.premium ? 'chip-purple' : 'chip-gray'}`}><i className="bi bi-gem me-1"></i>{seller.premium ? `Premium · ${seller.premiumExpiresAt || '—'} gacha` : "Premium yo'q"}</span>
          </div>
        </div>
      </div>

      <div className="col-12">
        <div className="detail-panel">
          <div className="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div style={{ minWidth: 0 }}>
              <div className="small text-muted mb-1">Do'kon karmasi</div>
              <div className="d-flex flex-wrap align-items-center gap-2 mb-2">
                <div className="fw-bold" style={{ fontSize: '2rem', lineHeight: 1 }}>{Math.round(seller.karma || seller.reputationScore || 0)}%</div>
                <span className={`st ${toneOf(karmaChip(seller.karmaCode))}`}><i></i>{seller.karmaLabelUz || '—'}</span>
              </div>
              <div className="text-muted small" style={{ maxWidth: 760 }}>{seller.karmaHintUz || "Do'kon sifati haqida tavsiya tayyorlanmoqda."}</div>
            </div>
            <div className="d-flex flex-wrap gap-2">
              <span className="chip chip-gray">Mahsulot {Math.round(seller.productScore || 0)}%</span>
              <span className="chip chip-gray">Javob {Math.round(seller.responseScore || 0)}%</span>
              <span className="chip chip-gray">Buyurtma {Math.round(seller.successScore || 0)}%</span>
              <span className="chip chip-gray">Katalog {Math.round(seller.catalogHealth || 0)}%</span>
            </div>
          </div>
        </div>
      </div>

      <Info title="Asosiy ma'lumotlar" rows={[
        ['Egasi', seller.ownerName || '—'], ['Telefon', seller.phone || '—'], ['Hudud', [seller.region, seller.district].filter(Boolean).join(', ') || '—'],
        ['Status', sellerLabel(seller.status)], ['Reyting', `${seller.rating || 0} (${seller.ratingReviewsCount || 0} sharh)`], ['Reputatsiya', String(seller.reputationScore || 0)],
      ]} />
      <Info title="Moliya va komissiya" rows={[
        ['Balans', `${fmt(seller.balance || 0)} so'm`], ['Umumiy tushum', `${fmt(seller.totalRevenue || 0)} so'm`],
        ['Komissiya', seller.commission?.activePromotion
          ? `${seller.commission.activePromotion.type === 'free' ? '0' : seller.commission.activePromotion.value}% imtiyoz · ${seller.commission.activePromotion.endsAtLabel || '—'} gacha`
          : seller.commissionMode === 'individual' ? `Individual · ${seller.commissionRate ?? 0}%` : 'Global tarif'],
        ['Berilgan imtiyoz (jami)', `${fmt(seller.commissionBenefitTotal || 0)} so'm`],
      ]} />

      {(seller.commission?.history || []).length ? (
        <div className="col-12">
          <div className="detail-panel">
            <div className="fw-semibold mb-2">Komissiya imtiyozi tarixi</div>
            <div className="d-grid gap-2">
              {(seller.commission?.history || []).map((promotion) => (
                <div className="d-flex align-items-start justify-content-between gap-3 border-bottom pb-2" key={promotion.id}>
                  <div>
                    <div className="small fw-semibold">{promotion.type === 'free' ? '0% komissiya' : `${promotion.value}% komissiya`} · {promotion.reason}</div>
                    <div className="text-muted small">{promotion.startsAtLabel || '—'} — {promotion.endsAtLabel || '—'}</div>
                  </div>
                  <span className={`chip ${promotion.status === 'active' ? 'chip-success' : promotion.status === 'scheduled' ? 'chip-info' : 'chip-gray'}`}>
                    {promotion.status === 'active' ? 'Faol' : promotion.status === 'scheduled' ? 'Rejada' : promotion.status === 'revoked' ? 'Bekor qilingan' : 'Tugagan'}
                  </span>
                </div>
              ))}
            </div>
          </div>
        </div>
      ) : null}

      <ListBlock
        title="So'nggi faoliyat"
        empty="Buyurtma yo'q"
        items={(seller.recentOrders || []).slice(0, 6)}
        render={(item) => <><strong>#{item.id} · {fmt(Number(item.amount || 0))} so'm</strong><span>{String(item.customer || 'Mijoz')} · {String(item.date || '—')}</span></>}
        action={<button type="button" className="btn btn-sm btn-light" onClick={onSeeOrders}>Barchasi</button>}
      />
      <ListBlock
        title={`Ogohlantirishlar (${seller.warningCount || 0}/3)`}
        empty="Ogohlantirish yo'q"
        items={(seller.banLogs || []).slice(0, 6)}
        render={(item) => <><strong>{String(item.title || '—')}</strong><span>{String(item.message || '')} · {String(item.date || '—')}</span></>}
      />
    </div>
  );
}

function BranchesTab({ seller, pagination, onPage, onRotateQr }: { seller: DetailProps; pagination: PaginationMeta; onPage: (p: number) => void; onRotateQr: () => void }) {
  return (
    <div className="row g-3">
      <div className="col-xl-4">
        <div className="detail-panel h-100 text-center">
          <h6 className="fw-bold mb-3">Do'kon QR</h6>
          {seller.qr?.imageUrl ? <img src={String(seller.qr.imageUrl)} alt="Do'kon QR" className="img-fluid rounded-4 border bg-white p-2 mb-3" style={{ maxWidth: 220 }} /> : <div className="text-muted small py-4">QR hali yaratilmagan</div>}
          <div className="small text-muted text-break">{String(seller.qr?.url || '—')}</div>
          <div className="small text-muted text-break mt-1">Token: {String(seller.qr?.token || '—')}</div>
          <div className="small text-muted mt-1">Yangilangan: {String(seller.qr?.rotatedAt || '—')}</div>
          <div className="d-flex gap-2 justify-content-center mt-3 flex-wrap">
            {seller.qr?.imageUrl ? <a className="btn btn-sm btn-light" href={String(seller.qr.imageUrl)} target="_blank" rel="noreferrer">Ochish</a> : null}
            {seller.qr?.imageUrl ? <a className="btn btn-sm btn-light" href={String(seller.qr.imageUrl)} download={`kitobchi-seller-${seller.id}-qr.png`}>Yuklab olish</a> : null}
            <button type="button" className="btn btn-sm btn-outline-primary" onClick={onRotateQr}><i className="bi bi-arrow-clockwise me-1"></i>Yangilash</button>
          </div>
        </div>
      </div>
      <div className="col-xl-8">
        <div className="row g-3">
          {(seller.locations || []).map((item) => (
            <div className="col-md-6" key={String(item.id)}>
              <div className="detail-panel h-100">
                <div className="d-flex gap-3 align-items-start">
                  {item.qrImageUrl ? <img src={String(item.qrImageUrl)} alt="Filial QR" className="rounded-4 border bg-white p-2" style={{ width: 96, height: 96 }} /> : null}
                  <div className="min-w-0">
                    <div className="d-flex gap-2 flex-wrap mb-2">
                      <span className={`chip ${item.main ? 'chip-warning' : 'chip-gray'}`}>{item.main ? 'Asosiy filial' : 'Filial'}</span>
                      <span className="chip chip-gray">ID: {String(item.id)}</span>
                    </div>
                    <div className="fw-semibold">{String(item.address || '—')}</div>
                    <div className="small text-muted">{String(item.description || '')}</div>
                    <div className="small text-muted text-break mt-2">URL: {String(item.qrUrl || '—')}</div>
                    <MapButtons mapLinks={(item.mapLinks || {}) as Record<string, string>} />
                    <div className="d-flex gap-2 flex-wrap mt-2">
                      {item.qrImageUrl ? <a href={String(item.qrImageUrl)} className="btn btn-sm btn-light" target="_blank" rel="noreferrer">QR ochish</a> : null}
                      {item.rotateUrl ? <button type="button" className="btn btn-sm btn-outline-primary" onClick={() => confirm("Eski filial QR ishlamay qoladi. Yangilansinmi?") && router.post(String(item.rotateUrl), {}, { preserveScroll: true })}>QR yangilash</button> : null}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          ))}
          {(seller.locations || []).length === 0 ? <div className="col-12 text-muted small">Filial yo'q</div> : null}
        </div>
        <PaginationControls {...pagination} onPageChange={onPage} />
      </div>
    </div>
  );
}

function ContractTab({ seller, pagination, onPage }: { seller: DetailProps; pagination: PaginationMeta; onPage: (p: number) => void }) {
  return (
    <div className="row g-3">
      <Info title="Shartnoma" rows={[
        ['Raqam', String(seller.contract?.number || '—')], ['Imzolangan', seller.contract?.signed ? 'Ha' : "Yo'q"], ['Holat', String(seller.contract?.status || '—')],
        ['Imzolangan sana', String(seller.contract?.signedAt || '—')], ['Tugash sanasi', String(seller.contract?.expiresAt || '—')], ['Qolgan kun', String(seller.contract?.daysRemaining ?? '—')],
        ['Izoh', String(seller.contract?.notes || '—')],
      ]} />
      <div className="col-xl-6">
        <div className="detail-panel h-100">
          <h6 className="fw-bold mb-3">Tez uzaytirish</h6>
          <form onSubmit={(event) => { event.preventDefault(); if (seller.actions?.extendContractUrl) router.patch(seller.actions.extendContractUrl, Object.fromEntries(new FormData(event.currentTarget)) as Record<string, string>, { preserveScroll: true }); }} className="row g-2">
            <div className="col-4"><select name="months" className="form-select form-select-sm" defaultValue="12"><option value="3">3 oy</option><option value="6">6 oy</option><option value="12">12 oy</option><option value="24">24 oy</option></select></div>
            <div className="col-8"><input name="notes" className="form-control form-control-sm" placeholder="Izoh" /></div>
            <div className="col-12"><button type="submit" className="btn btn-sm btn-primary-gradient border-0">Uzaytirish</button></div>
          </form>
        </div>
      </div>
      <div className="col-12">
        <div className="detail-panel">
          <h6 className="fw-bold mb-3">Shartnoma tarixi</h6>
          <div className="table-responsive">
            <table className="data-table">
              <thead><tr><th>Amal</th><th>Raqam</th><th>Eski → Yangi</th><th>Kim tomonidan</th><th>Izoh</th><th>Sana</th></tr></thead>
              <tbody>
                {(seller.contractHistory || []).map((item) => (
                  <tr key={String(item.id)}>
                    <td><span className="chip chip-info">{String(item.action || 'Yangilandi')}</span></td>
                    <td>{String(item.number || '—')}</td>
                    <td className="text-muted">{String(item.oldExpiresAt || '—')} → {String(item.newExpiresAt || '—')}</td>
                    <td>{String(item.performedBy || '—')}</td>
                    <td className="text-muted">{String(item.notes || '')}</td>
                    <td className="text-muted">{String(item.date || '—')}</td>
                  </tr>
                ))}
                {(seller.contractHistory || []).length === 0 ? <tr><td colSpan={6} className="text-center text-muted py-4">Tarix yo'q</td></tr> : null}
              </tbody>
            </table>
          </div>
          <PaginationControls {...pagination} onPageChange={onPage} />
        </div>
      </div>
    </div>
  );
}

function LegalTab({ seller }: { seller: DetailProps }) {
  return (
    <div className="row g-3">
      <Info title="Huquqiy ma'lumotlar" rows={[
        ['Yuridik turi', String(seller.legal?.typeLabel || seller.legalName || '—')], ['STIR', String(seller.legal?.inn || '—')], ['Pasport', String(seller.legal?.passport || '—')],
        ['Pasport beruvchi', String(seller.legal?.passportIssuedBy || '—')], ['Pasport sanasi', String(seller.legal?.passportIssuedAt || '—')], ['Yuridik manzil', String(seller.legal?.legalAddress || seller.address || '—')],
      ]} />
      <Info title="Bank rekvizitlari" rows={[
        ['Bank', String(seller.bank?.name || '—')], ['Hisob', String(seller.bank?.account || '—')], ['MFO', String(seller.bank?.mfo || '—')],
        ['SWIFT', String(seller.bank?.swift || '—')], ['Karta', String(seller.bank?.card || '—')], ['Karta egasi', String(seller.bank?.cardHolder || '—')],
      ]} />
    </div>
  );
}

function DocumentsTab({ seller, pagination, onPage, onUpload }: { seller: DetailProps; pagination: PaginationMeta; onPage: (p: number) => void; onUpload: (e: FormEvent<HTMLFormElement>) => void }) {
  return (
    <div className="detail-panel">
      <form onSubmit={onUpload} className="row g-2 mb-4">
        <div className="col-md-3">
          <select name="type" className="form-select form-select-sm" required>
            <option value="passport">Pasport</option>
            <option value="contract">Shartnoma</option>
            <option value="inn_certificate">STIR guvohnomasi</option>
            <option value="license">Litsenziya</option>
            <option value="bank_details">Bank rekvizitlari</option>
            <option value="addendum">Qo'shimcha kelishuv</option>
            <option value="other">Boshqa</option>
          </select>
        </div>
        <div className="col-md-4"><input type="file" name="file" className="form-control form-control-sm" accept=".pdf,image/*" required /></div>
        <div className="col-md-3"><input name="description" className="form-control form-control-sm" placeholder="Izoh" /></div>
        <div className="col-md-2"><button type="submit" className="btn btn-sm btn-primary-gradient border-0 w-100">Yuklash</button></div>
      </form>
      <div className="d-grid gap-2">
        {(seller.documents || []).map((item) => (
          <div className="d-flex justify-content-between align-items-center border rounded-3 p-3 gap-2" key={String(item.id)}>
            <div>
              <strong>{String(item.typeLabel || item.type || 'Hujjat')}</strong>
              <div className="small text-muted">{String(item.name || '—')} · {String(item.size || 0)} KB · {String(item.date || '—')}</div>
              {item.uploadedBy ? <div className="small text-muted">Yukladi: {String(item.uploadedBy)}</div> : null}
              {item.description ? <div className="small text-muted">{String(item.description)}</div> : null}
            </div>
            <div className="d-flex gap-2 flex-shrink-0">
              {item.url ? <a href={String(item.url)} target="_blank" rel="noreferrer" className="btn btn-sm btn-light">Ko'rish</a> : null}
              {item.deleteUrl ? <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => confirm("Hujjat o'chirilsinmi?") && router.delete(String(item.deleteUrl), { preserveScroll: true })}><i className="bi bi-trash"></i></button> : null}
            </div>
          </div>
        ))}
        {(seller.documents || []).length === 0 ? <div className="text-muted small">Hujjat topilmadi</div> : null}
      </div>
      <PaginationControls {...pagination} onPageChange={onPage} />
    </div>
  );
}

type StaffRow = {
  id: number; name: string; phone: string; role: number; roleLabel: string;
  status: string; hidden: boolean; canWithdraw: boolean; location?: string | null;
  passwordResetLimit: number; createdAt?: string; resetPasswordUrl: string; toggleUrl: string;
};
type StaffData = {
  staff: StaffRow[];
  locations: Array<{ id: number; fullAddress: string; isMain: boolean }>;
  roles: Array<{ value: number; label: string }>;
  storeUrl: string;
};

function StaffTab({ seller }: { seller: DetailProps }) {
  const [data, setData] = useState<StaffData | null>(null);
  const [loading, setLoading] = useState(false);
  const [showAdd, setShowAdd] = useState(false);
  const [role, setRole] = useState(2);

  const reload = async () => {
    if (!seller.actions?.staffUrl) return;
    setLoading(true);
    try {
      const response = await fetch(seller.actions.staffUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      if (response.ok) setData(await response.json());
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void reload();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [seller.id]);

  const submitAdd = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!data?.storeUrl) return;
    const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
    router.post(data.storeUrl, payload, {
      preserveScroll: true,
      onSuccess: () => { setShowAdd(false); void reload(); },
    });
  };

  const resetStaffPassword = (member: StaffRow) => {
    if (!confirm(`${member.name} uchun yangi parol yaratilib, ${member.phone} raqamiga SMS yuborilsinmi?`)) return;
    router.post(member.resetPasswordUrl, {}, { preserveScroll: true, onSuccess: () => void reload() });
  };

  const toggleStaff = (member: StaffRow) => {
    const activate = member.hidden || member.status !== 'active';
    if (!confirm(activate ? `${member.name} faollashtirilsinmi?` : `${member.name} deaktivatsiya qilinsinmi?`)) return;
    router.patch(member.toggleUrl, {}, { preserveScroll: true, onSuccess: () => void reload() });
  };

  return (
    <div className="detail-panel">
      {loading && !data ? <div className="text-center text-muted py-4">Yuklanmoqda...</div> : null}
      {data ? (
        <>
          {data.staff.length ? (
            <div className="table-responsive mb-3">
              <table className="data-table">
                <thead><tr><th>Hodim</th><th>Rol</th><th>Filial</th><th>Holat</th><th>Amallar</th></tr></thead>
                <tbody>
                  {data.staff.map((member) => {
                    const active = !member.hidden && member.status === 'active';
                    return (
                      <tr key={member.id}>
                        <td>
                          <div className="fw-semibold">{member.name}</div>
                          <small className="text-muted">{member.phone}{member.createdAt ? ` · ${member.createdAt}` : ''}</small>
                        </td>
                        <td>
                          <span className="chip chip-purple">{member.roleLabel}</span>
                          {member.canWithdraw ? <small className="d-block text-muted">Pul yechish: bor</small> : null}
                        </td>
                        <td className="text-muted" style={{ maxWidth: 180 }}>{member.location || '—'}</td>
                        <td><span className={`chip ${active ? 'chip-success' : 'chip-gray'}`}>{active ? 'Faol' : 'Nofaol'}</span></td>
                        <td>
                          <button className="btn btn-sm btn-light me-1" title="Parolni yangilash (SMS bilan boradi)" onClick={() => resetStaffPassword(member)}>
                            <i className="bi bi-key"></i>
                          </button>
                          <button className="btn btn-sm btn-light" title={active ? 'Deaktivatsiya' : 'Faollashtirish'} onClick={() => toggleStaff(member)}>
                            <i className={`bi ${active ? 'bi-pause-circle text-warning' : 'bi-play-circle text-success'}`}></i>
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          ) : <p className="text-muted">Bu do&apos;konda hali hodim yo&apos;q.</p>}

          {showAdd ? (
            <form onSubmit={submitAdd} className="detail-panel">
              <h6 className="fw-bold mb-3">Yangi hodim qo&apos;shish</h6>
              <div className="row g-2">
                <div className="col-md-6"><label className="form-label">Ism</label><input name="firstname" required maxLength={50} className="form-control form-control-sm" /></div>
                <div className="col-md-6"><label className="form-label">Familiya</label><input name="lastname" required maxLength={50} className="form-control form-control-sm" /></div>
                <div className="col-md-6"><label className="form-label">Telefon</label><input name="phone_number" required maxLength={20} placeholder="+998901234567" className="form-control form-control-sm" /></div>
                <div className="col-md-6"><label className="form-label">Parol <span className="text-muted">(bo&apos;sh qoldirilsa avtomatik yaratiladi)</span></label><input name="password" minLength={6} maxLength={64} className="form-control form-control-sm" /></div>
                <div className="col-md-6">
                  <label className="form-label">Rol</label>
                  <select name="role" className="form-select form-select-sm" value={role} onChange={(e) => setRole(Number(e.target.value))}>
                    {data.roles.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}
                  </select>
                </div>
                <div className="col-md-6">
                  <label className="form-label">Filial</label>
                  <select name="seller_location_id" required className="form-select form-select-sm" defaultValue={data.locations[0]?.id ?? ''}>
                    {data.locations.map((location) => <option key={location.id} value={location.id}>{location.fullAddress}{location.isMain ? ' (asosiy)' : ''}</option>)}
                  </select>
                </div>
                {role === 4 ? (
                  <div className="col-12 form-check ms-2">
                    <input className="form-check-input" type="checkbox" name="can_withdraw_balance" value="1" id="staff-can-withdraw" />
                    <label className="form-check-label" htmlFor="staff-can-withdraw">Balansdan pul yechishga ruxsat</label>
                  </div>
                ) : null}
              </div>
              <div className="d-flex gap-2 mt-3">
                <button className="btn btn-sm btn-primary-gradient border-0" type="submit">Qo&apos;shish (parol SMS bilan boradi)</button>
                <button className="btn btn-sm btn-light" type="button" onClick={() => setShowAdd(false)}>Bekor</button>
              </div>
            </form>
          ) : (
            <button className="btn btn-sm btn-primary-gradient border-0" type="button" onClick={() => setShowAdd(true)} disabled={data.locations.length === 0}>
              <i className="bi bi-person-plus me-1"></i>Hodim qo&apos;shish
            </button>
          )}
          {data.locations.length === 0 ? <small className="text-danger d-block mt-2">Do&apos;konda faol filial yo&apos;q — avval filial kerak.</small> : null}
        </>
      ) : null}
    </div>
  );
}

function OrdersTab({ orders, pagination, statuses, onPage, onPatch }: {
  orders: SellerOrderRow[];
  pagination: PaginationMeta;
  statuses: Record<string, StatusMeta>;
  onPage: (p: number) => void;
  onPatch: (url?: string, message?: string, payload?: Record<string, string>) => void;
}) {
  const [expanded, setExpanded] = useState<number | null>(null);

  return (
    <div className="detail-panel">
      <div className="table-responsive">
        <table className="data-table">
          <thead><tr><th></th><th>ID</th><th>Mijoz</th><th>Summa</th><th>Mahsulot</th><th>Holat</th><th>Sana</th></tr></thead>
          <tbody>
            {orders.map((order) => (
              <Fragment key={order.id}>
                <tr style={{ cursor: 'pointer' }} onClick={() => setExpanded(expanded === order.id ? null : order.id)}>
                  <td style={{ width: 28 }}><i className={`bi ${expanded === order.id ? 'bi-chevron-down' : 'bi-chevron-right'} text-muted`}></i></td>
                  <td><div className="fw-bold">#{order.id}</div><small className="text-muted">ORD #{order.orderId || '—'}</small></td>
                  <td><div>{order.customer}</div><small className="text-muted">{order.customerPhone || '—'}</small></td>
                  <td><div className="fw-semibold">{fmt(order.amount)} so'm</div><small className="text-muted">{order.deliveryType || '—'}</small></td>
                  <td>{order.summary?.itemsCount || 0} ta</td>
                  <td onClick={(event) => event.stopPropagation()}>
                    <select className={`form-select form-select-sm ${badgeClass(order.statusBadge)}`} value={order.status} onChange={(e) => onPatch(order.statusUrl, undefined, { status: e.target.value })}>
                      {Object.entries(statuses).map(([value, meta]) => <option key={value} value={value}>{meta.label}</option>)}
                    </select>
                  </td>
                  <td className="text-muted">{order.date || order.acceptedAt || '—'}</td>
                </tr>
                {expanded === order.id ? (
                  <tr>
                    <td colSpan={7} className="bg-light">
                      <div className="row g-3 p-2">
                        <Info title="Manzil" rows={[
                          ['Qabul qiluvchi', String(order.address?.fullName || '—')], ['Telefon', String(order.address?.phone || '—')],
                          ['Viloyat', String(order.address?.region || '—')], ['Tuman', String(order.address?.district || '—')],
                          ['Ko‘cha', String(order.address?.street || '—')], ['Uy', String(order.address?.home || '—')],
                        ]} />
                        <div className="col-xl-6">
                          <div className="detail-panel h-100">
                            <h6 className="fw-bold mb-2">Mahsulotlar</h6>
                            {(order.items || []).map((item, index) => (
                              <div className="d-flex justify-content-between border-bottom py-2" key={`${item.name}-${index}`}>
                                <div><strong>{item.name}</strong><div className="text-muted small">{item.author || item.type || '—'}</div></div>
                                <div className="text-end"><div>{item.quantity} x {fmt(item.price)}</div><strong>{fmt(item.quantity * item.price)} so'm</strong></div>
                              </div>
                            ))}
                            {(order.items || []).length === 0 ? <div className="text-muted">Mahsulotlar topilmadi</div> : null}
                          </div>
                        </div>
                        <div className="col-12"><MapButtons mapLinks={(order.address?.mapLinks || {}) as Record<string, string>} /></div>
                      </div>
                    </td>
                  </tr>
                ) : null}
              </Fragment>
            ))}
            {orders.length === 0 ? <tr><td colSpan={7} className="text-center text-muted py-5">Buyurtma topilmadi</td></tr> : null}
          </tbody>
        </table>
      </div>
      <PaginationControls {...pagination} onPageChange={onPage} />
    </div>
  );
}

function TransactionsTab({ transactions, pagination, onPage }: { transactions: Array<Record<string, unknown>>; pagination: PaginationMeta; onPage: (p: number) => void }) {
  return (
    <div className="detail-panel">
      <div className="table-responsive">
        <table className="data-table">
          <thead><tr><th>Sana</th><th>Tur</th><th>Summa</th><th>Komissiya</th><th>Net</th><th>Holat</th></tr></thead>
          <tbody>
            {transactions.map((item) => (
              <tr key={String(item.id)}>
                <td className="text-muted">{String(item.date || '—')}</td>
                <td>{String(item.category || item.type || '—')}</td>
                <td>{fmt(Number(item.amount || 0))} so'm</td>
                <td className="text-muted">{fmt(Number(item.commission || 0))} so'm</td>
                <td className="fw-semibold">{fmt(Number(item.net || 0))} so'm</td>
                <td><span className={`chip ${item.status === 'approved' ? 'chip-success' : item.status === 'pending' ? 'chip-warning' : 'chip-danger'}`}>{String(item.status || '—')}</span></td>
              </tr>
            ))}
            {transactions.length === 0 ? <tr><td colSpan={6} className="text-center text-muted py-5">Tranzaksiya topilmadi</td></tr> : null}
          </tbody>
        </table>
      </div>
      <PaginationControls {...pagination} onPageChange={onPage} />
    </div>
  );
}

function BanLogsTab({ banLogs, pagination, onPage, warningCount }: { banLogs: Array<Record<string, unknown>>; pagination: PaginationMeta; onPage: (p: number) => void; warningCount?: number }) {
  return (
    <div className="detail-panel">
      <div className="mb-3"><span className={`chip ${(warningCount || 0) >= 3 ? 'chip-danger' : (warningCount || 0) > 0 ? 'chip-warning' : 'chip-gray'}`}>Faol ogohlantirishlar: {warningCount || 0}/3</span></div>
      <div className="d-grid gap-2">
        {banLogs.map((item) => (
          <div className="mini-stat" key={String(item.id)}>
            <div className="d-flex justify-content-between align-items-start gap-2">
              <div>
                <strong>{String(item.title || '—')}</strong>
                <div className="text-muted small mt-1">{String(item.message || '')}</div>
              </div>
              <span className={`chip ${item.type === 'unban' ? 'chip-success' : 'chip-warning'} flex-shrink-0`}>{item.type === 'unban' ? 'Blokdan chiqarish' : 'Ogohlantirish'}</span>
            </div>
            <div className="text-muted small mt-2">{String(item.date || '—')}</div>
          </div>
        ))}
        {banLogs.length === 0 ? <div className="text-muted small">Ogohlantirish topilmadi</div> : null}
      </div>
      <PaginationControls {...pagination} onPageChange={onPage} />
    </div>
  );
}
