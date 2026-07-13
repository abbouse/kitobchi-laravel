import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);
type Counts = Record<string, number>;
type Row = Record<string, unknown>;

interface UserRow {
  id: number; name: string; firstName?: string; lastName?: string; email: string; phone: string; avatar?: string;
  orders: number; cards?: number; spent: number; status: string; position: string; staffRole?: string; verified?: boolean; phoneVerified?: boolean; premium?: boolean; online?: boolean; lastSeenAt?: string; joined?: string; joinedLabel?: string;
  dataUrl: string; blockUrl: string; unblockUrl: string; verifyUrl: string; premiumUrl: string;
}
interface UserDetail {
  profile: Row; stats: Record<string, number>; orders: Row[]; cards: Row[]; devices: Row[]; addresses: Row[];
  followers: Row[]; following: Row[]; searchHistory: Row[]; favourites: Row[]; cartItems: Row[]; giftCertificates: Row[]; mysteryBoxes: Row[]; splitProfile?: Row | null; actions: Record<string, string>;
}
interface PaginationMeta { page: number; totalPages: number; from: number; to: number; total: number }

const tabs = [
  ['all', 'Barchasi'], ['online', 'Online'], ['active', 'Faol'], ['pending', 'Kutilmoqda'], ['premium', 'Premium'],
  ['buyers', 'Xaridorlar'], ['with_cards', 'Karta ulagan'], ['no_cards', 'Kartasiz'], ['support', 'Support'], ['blocked', 'Bloklangan'],
];

export default function Users() {
  const { users = [], userCounts = {}, userPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, userFilters = {} } = usePage<{ users?: UserRow[]; userCounts?: Counts; userPagination?: PaginationMeta; userFilters?: { tab?: string; search?: string } }>().props;
  const [tab, setTab] = useState(userFilters.tab || 'all');
  const [search, setSearch] = useState(userFilters.search || '');
  const [selected, setSelected] = useState<UserRow | null>(null);
  const [detail, setDetail] = useState<UserDetail | null>(null);
  const [loading, setLoading] = useState(false);

  const loadUsers = (page = 1, activeTab = tab, term = search) => router.get('/boshqaruv/users', { users_page: page, users_tab: activeTab, users_search: term }, { preserveState: true, preserveScroll: true, replace: true });

  const openDetail = async (user: UserRow) => {
    setSelected(user); setDetail(null); setLoading(true);
    try {
      const response = await fetch(user.dataUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      if (!response.ok) throw new Error('Profilni yuklab bo‘lmadi');
      setDetail(await response.json());
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Foydalanuvchilar</h1><p className="page-subtitle">Profil, online holat, kartalar, premium, bloklash va xarid tarixi boshqaruvi</p></div>
      </div>
      <div className="row g-3 mb-4">
        {[
          ['Jami', userCounts.all || 0, 'bi-people', '#4f46e5'], ['Online', userCounts.online || 0, 'bi-broadcast', '#10b981'],
          ['Karta ulagan', userCounts.with_cards || 0, 'bi-credit-card', '#7c3aed'], ['Bloklangan', userCounts.blocked || 0, 'bi-person-lock', '#ef4444'],
        ].map(([label, val, icon, color]) => <div className="col-xl-3 col-md-6" key={String(label)}><div className="stat-card"><div className="d-flex gap-3 align-items-center"><div className="stat-icon" style={{ background: String(color) }}><i className={`bi ${icon}`}></i></div><div><div className="stat-value">{val}</div><div className="stat-label">{label}</div></div></div></div></div>)}
      </div>
      <div className="card-panel">
        <div className="panel-head"><div><div className="panel-title">Foydalanuvchilar ro‘yxati</div><small className="text-muted">{userPagination.total} ta yozuv</small></div><form className="d-flex gap-2" onSubmit={(e) => { e.preventDefault(); loadUsers(); }}><input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Ism, telefon, email yoki ID" /><button className="btn btn-sm btn-outline-secondary"><i className="bi bi-search"></i></button></form></div>
        <div className="d-flex flex-wrap gap-2 mb-3">{tabs.map(([key, label]) => <button className={`btn btn-sm ${tab === key ? 'btn-primary-gradient' : 'btn-light'}`} key={key} onClick={() => { setTab(key); loadUsers(1, key); }}>{label}<span className="badge rounded-pill bg-light text-dark ms-2">{fmt(userCounts[key] || 0)}</span></button>)}</div>
        <div className="table-responsive"><table className="data-table"><thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Telefon</th><th>Buyurtma</th><th>Sarflangan</th><th>Karta</th><th>Oxirgi aktivlik</th><th>Status</th><th>Amallar</th></tr></thead><tbody>
          {users.map((user) => <tr key={user.id}>
            <td className="fw-semibold text-primary">#{user.id}</td><td><div className="d-flex align-items-center gap-2"><Avatar user={user} /><div><div className="fw-semibold">{user.name}</div><small className="text-muted">A'zo bo'lgan: {user.joinedLabel || user.joined || '—'}</small></div></div></td>
            <td>{user.phone || '—'}</td><td>{user.orders}</td><td className="fw-semibold">{fmt(user.spent)} so'm</td><td><span className={`chip ${(user.cards || 0) > 0 ? 'chip-info' : 'chip-gray'}`}>{user.cards || 0} ta</span></td><td>{user.online ? <span className="chip chip-success">Online</span> : <span className="text-muted small">{user.lastSeenAt || '—'}</span>}</td><td><Status user={user} /></td>
            <td><button className="btn btn-sm btn-light" onClick={() => openDetail(user)}><i className="bi bi-eye"></i></button></td>
          </tr>)}
          {userPagination.total === 0 ? <tr><td colSpan={9} className="text-center text-muted py-5">Foydalanuvchi topilmadi</td></tr> : null}
        </tbody></table></div>
        <PaginationControls {...userPagination} onPageChange={(page) => loadUsers(page)} />
      </div>
      <ProfileModal user={selected} detail={detail} loading={loading} onHide={() => { setSelected(null); setDetail(null); }} reload={() => selected && openDetail(selected)} />
    </div>
  );
}

function ProfileModal({ user, detail, loading, onHide, reload }: { user: UserRow | null; detail: UserDetail | null; loading: boolean; onHide: () => void; reload: () => void }) {
  const p = detail?.profile || {}; const s = detail?.stats || {};
  const split = detail?.splitProfile || null;
  const post = (url?: string, data: Record<string, string> = {}) => url && router.post(url, data, { preserveScroll: true, onSuccess: reload });
  return <Modal show={!!user} onHide={onHide} centered size="xl"><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{user?.name}</Modal.Title></Modal.Header><Modal.Body>
    {loading ? <div className="text-center py-5 text-muted">Profil yuklanmoqda...</div> : !detail ? <div className="text-danger">Profilni yuklab bo‘lmadi.</div> : <div className="row g-3">
      <div className="col-12"><div className="detail-panel"><div className="d-flex flex-wrap align-items-center gap-3"><Avatar user={{ ...user!, avatar: String(p.avatar || '') }} large /><div><h4 className="mb-1">{String(p.name || '')}</h4><div className="text-muted">{String(p.email || 'Email yo‘q')} · {String(p.phone || 'Telefon yo‘q')}</div><div className="d-flex flex-wrap gap-2 mt-2"><Status user={user!} />{p.phoneVerified ? <span className="chip chip-success">Telefon tasdiqlangan</span> : <span className="chip chip-warning">Telefon tasdiqlanmagan</span>}{p.verified ? <span className="chip chip-info">Verified badge</span> : null}{p.premium ? <span className="chip chip-warning">Premium</span> : null}{p.support ? <span className="chip chip-info">Support</span> : null}</div></div></div></div></div>
      <Info title="Profil ma'lumotlari" rows={[['Telefon', p.phone], ['Telefon tasdig‘i', p.phoneVerified ? 'Tasdiqlangan' : 'Tasdiqlanmagan'], ['Telefon tasdiqlangan sana', p.phoneVerifiedAt], ['Verified badge', p.verified ? 'Yoqilgan' : 'O‘chiq'], ['Email', p.email], ['Username', p.username], ['Telegram ID', p.telegramId], ['Til', p.locale], ['Daraja', p.position], ['Role title', p.roleTitle], ['Staff roli', p.staffRole], ['AI limiti', p.aiLimit], ['Oxirgi faollik', p.lastSeenAt], ['Ro‘yxatdan o‘tgan', p.joined], ['Spent time', `${fmt(Number(p.spentSeconds || 0))} sec`], ['Bio', p.bio]]} />
      <Info title="Hisob va statistika" rows={[['Buyurtmalar', s.orders], ['To‘langan orderlar', s.paidOrders], ['Sarflangan', `${fmt(s.spent || 0)} so'm`], ['Balans', `${fmt(Number(p.balance || 0))} so'm`], ['Cashback', `${fmt(Number(p.cashback || 0))} so'm`], ['Kartalar', s.cards], ['Qurilmalar', s.devices], ['Manzillar', s.addresses], ['Search history', s.searches], ['Sevimlilar', s.favourites], ['Savat itemlari', s.cartItems], ['Savat soni', s.cartQuantity], ['Savat jami', `${fmt(s.cartTotal || 0)} so'm`], ['Followers', s.followers], ['Following', s.following], ['Gift sertifikat', s.giftCertificates], ['Mystery Box', s.mysteryBoxes]]} />
      {split ? <Info title="Split profili" rows={[['Moslik', split.eligible ? 'Ha' : "Yo'q"], ['Admin split blok', split.manuallyBlocked ? 'Yoqilgan' : 'Yo‘q'], ['Split blok sababi', split.manualBlockReason], ['Split bloklangan sana', split.manualBlockedAt], ['Ishonch skori', split.confidenceScore], ['Hisoblangan limit', `${fmt(Number(split.computedLimit || 0))} so'm`], ['Bo‘sh limit', `${fmt(Number(split.availableLimit || 0))} so'm`], ['Faol exposure', `${fmt(Number(split.activeExposure || 0))} so'm`], ['Reputation', split.reputationScore], ['Karta yoshi', `${fmt(Number(split.verifiedCardAgeDays || 0))} kun`], ['Saved-card to‘lovlar', split.successfulCardPayments180d], ['Completed orderlar', split.completedOrdersAll], ['COD strike', split.codReturnStrikes], ['Yangilangan', split.lastRefreshedAt]]} /> : null}
      <div className="col-12"><div className="detail-panel"><h6 className="fw-bold">Akkaunt boshqaruvi</h6>{p.blocked ? <div className="alert alert-danger mb-2">Bloklangan: {String(p.blockLabel || 'Abadiy')}<br />Sabab: {String(p.blockReason || '—')}</div> : <BlockForm action={detail.actions.blockUrl} onDone={reload} />}{split ? <div className="mt-3 pt-3 border-top"><div className="d-flex flex-wrap align-items-center gap-2 mb-2"><h6 className="fw-bold mb-0">Split boshqaruvi</h6>{split.manuallyBlocked ? <span className="chip chip-danger">Split bloklangan</span> : <span className="chip chip-success">Split ochiq</span>}</div>{split.manuallyBlocked ? <div className="alert alert-warning mb-2">Sabab: {String(split.manualBlockReason || '—')}</div> : <SplitBlockForm action={String(detail.actions.splitBlockUrl || '')} onDone={reload} />}<div className="d-flex flex-wrap gap-2 mt-2">{split.manuallyBlocked ? <Button variant="outline-success" onClick={() => post(detail.actions.splitUnblockUrl)}>Split blokni bekor qilish</Button> : null}</div><div className="mt-3 pt-3 border-top"><div className="small fw-semibold mb-1">Qo'lda split limit {Number(split.manualLimit || 0) > 0 ? <span className="chip chip-success ms-1">joriy: {Number(split.manualLimit).toLocaleString()} so'm</span> : <span className="text-muted">(berilmagan — skoring rejimi)</span>}</div><SplitManualLimitForm action={String(detail.actions.splitManualLimitUrl || '')} current={Number(split.manualLimit || 0)} onDone={reload} /><div className="small text-muted mt-1">Limit berilsa skoring talablari chetlab o'tiladi (admin blok, muddati o'tgan to'lov va default bundan mustasno). 0 kiritilsa limit olib tashlanadi. Berilganda mijozga push ketadi.</div></div></div> : null}<div className="d-flex flex-wrap gap-2 mt-3">{p.blocked ? <Button variant="outline-success" onClick={() => post(detail.actions.unblockUrl)}>Blokdan chiqarish</Button> : null}<Button variant="outline-secondary" onClick={() => router.patch(detail.actions.verifyUrl, {}, { preserveScroll: true, onSuccess: reload })}>{p.verified ? 'Verified badge o‘chirish' : 'Verified badge yoqish'}</Button><Button variant="outline-warning" onClick={() => router.patch(detail.actions.premiumUrl, {}, { preserveScroll: true, onSuccess: reload })}>{p.premium ? "Premiumni o'chirish" : 'Premium yoqish'}</Button></div></div></div>
      {split && Array.isArray(split.reasons) && split.reasons.length > 0 ? <div className="col-12"><div className="detail-panel"><h6 className="fw-bold">Split rad sabablari</h6><div className="d-flex flex-wrap gap-2 mt-2">{split.reasons.map((reason, index) => <span key={index} className="chip chip-warning">{String(reason)}</span>)}</div></div></div> : null}
      <TableBlock title="So‘nggi buyurtmalar" rows={detail.orders} cols={[['ID', 'id'], ['Status', 'status'], ['To‘lov', 'payment'], ['Yetkazish', 'delivery'], ['Summa', 'amount'], ['Sana', 'date']]} />
      <TableBlock title="Search history" rows={detail.searchHistory} cols={[['So‘z', 'text'], ['Natija', 'resultName'], ['Turi', 'resultType'], ['Topilgan', 'resultCount'], ['Qidirilgan', 'searchCount'], ['Sana', 'date']]} />
      <ProductListBlock title="Sevimlilar" rows={detail.favourites} empty="Sevimli mahsulot topilmadi" />
      <ProductListBlock title="Savat" rows={detail.cartItems} empty="Savat bo‘sh" cart totalCount={Number(s.cartItems || 0)} />
      <ListBlock title="Kartalar" rows={detail.cards} render={(x) => <><strong>{String(x.vendor)} · {String(x.number || '****')}</strong><span>{String(x.name || 'Nomsiz')} · {String(x.expires || 'Muddat yo‘q')}</span>{x.destroyUrl ? <button className="btn btn-sm btn-light text-danger mt-2" onClick={() => confirm("Kartani o'chirasizmi?") && router.delete(String(x.destroyUrl), { preserveScroll: true, onSuccess: reload })}><i className="bi bi-trash"></i></button> : null}</>} />
      <ListBlock title="Qurilmalar" rows={detail.devices} render={(x) => <><strong>{String(x.name || 'Noma’lum qurilma')}</strong><span>{String(x.platform || '—')} · {String(x.deviceId || '—')} · Push: {x.push ? 'Ha' : "Yo'q"}</span></>} />
      <ListBlock title="Manzillar" rows={detail.addresses} render={(x) => <><strong>{String(x.address || 'Manzil kiritilmagan')}</strong><span>{x.main ? 'Asosiy manzil' : 'Qo‘shimcha manzil'} · {[x.lat, x.lon].filter(Boolean).join(', ') || 'koordinata yo‘q'}</span><MapButtons mapLinks={(x.mapLinks || {}) as Record<string, string>} /></>} />
      <ListBlock title="Followers" rows={detail.followers} render={(x) => <><strong>{String(x.name)}</strong><span>{String(x.phone || 'Telefon yo‘q')}</span></>} />
      <ListBlock title="Following" rows={detail.following} render={(x) => <><strong>{String(x.name)}</strong><span>{String(x.phone || 'Telefon yo‘q')}</span></>} />
      <TableBlock title="Gift sertifikatlar" rows={detail.giftCertificates} cols={[['Kod', 'code'], ['Rol', 'role'], ['Status', 'status'], ['Miqdor', 'amount'], ['Muddat', 'expires']]} />
      <ListBlock title="Mystery Box obunalari" rows={detail.mysteryBoxes} render={(x) => <><strong>{String(x.name)} · {String(x.status)}</strong><span>{String(x.booksPerMonth)} kitob / {String(x.totalMonths)} oy · {fmt(Number(x.price || 0))} so'm · progress {String(x.progress)}%</span><div className="d-flex gap-2 flex-wrap mt-2">{x.showUrl ? <a className="btn btn-sm btn-light" href={String(x.showUrl)}><i className="bi bi-eye me-1"></i>Obunani ochish</a> : null}{x.indexUrl ? <a className="btn btn-sm btn-light" href={String(x.indexUrl)}><i className="bi bi-box-seam me-1"></i>Mystery list</a> : null}</div></>} />
    </div>}
  </Modal.Body><Modal.Footer><Button variant="light" onClick={onHide}>Yopish</Button></Modal.Footer></Modal>;
}

function Avatar({ user, large }: { user: Pick<UserRow, 'name' | 'avatar'>; large?: boolean }) { return <div className={`resource-avatar ${large ? '' : 'square'}`}>{user.avatar ? <img src={user.avatar} alt="" /> : user.name.slice(0, 2).toUpperCase()}</div>; }
function Status({ user }: { user: Pick<UserRow, 'status' | 'premium'> }) { return <span className={`chip ${user.status === 'blocked' ? 'chip-danger' : user.status === 'pending' ? 'chip-warning' : user.premium ? 'chip-purple' : 'chip-success'}`}>{user.status === 'blocked' ? 'Bloklangan' : user.status === 'pending' ? 'Kutilmoqda' : user.premium ? 'Premium' : 'Faol'}</span>; }
function Info({ title, rows }: { title: string; rows: Array<[string, unknown]> }) { return <div className="col-xl-6"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">{title}</h6><div className="address-list">{rows.map(([k, v]) => <div key={k}><span>{k}</span><strong>{String(v ?? '—')}</strong></div>)}</div></div></div>; }
function ListBlock({ title, rows, render }: { title: string; rows: Row[]; render: (x: Row) => React.ReactNode }) { return <div className="col-xl-6"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">{title}</h6><div className="d-grid gap-2">{rows.map((x, i) => <div className="mini-stat" key={String(x.id || i)}>{render(x)}</div>)}{rows.length === 0 ? <div className="text-muted">Ma'lumot topilmadi</div> : null}</div></div></div>; }
function ProductListBlock({ title, rows, empty, cart, totalCount }: { title: string; rows: Row[]; empty: string; cart?: boolean; totalCount?: number }) {
  const countLabel = totalCount !== undefined && totalCount > rows.length ? `${rows.length}/${totalCount} ta` : `${rows.length} ta`;
  return <div className="col-12"><div className="detail-panel"><div className="d-flex align-items-center justify-content-between mb-3"><h6 className="fw-bold mb-0">{title}</h6><span className="chip chip-gray">{countLabel}</span></div><div className="row g-2">{rows.map((x, i) => <div className="col-xl-6" key={String(x.id || i)}><div className="mini-stat h-100"><div className="d-flex gap-3 align-items-start"><div className="product-thumb-sm">{x.image ? <img src={String(x.image)} alt="" /> : <i className="bi bi-box-seam"></i>}</div><div className="min-w-0 flex-grow-1"><div className="d-flex flex-wrap gap-1 mb-1"><span className="chip chip-info">{String(x.typeLabel || x.productType || 'Mahsulot')}</span>{x.variant ? <span className="chip chip-purple">{String((x.variant as Row).name || 'Variant')}</span> : null}<span className={`chip ${x.status === 'Faol' ? 'chip-success' : 'chip-gray'}`}>{String(x.status || '—')}</span></div><strong className="d-block text-truncate">{String(x.name || 'Mahsulot')}</strong><span className="d-block text-muted small">ID #{String(x.productId || '—')} · Stock: {fmt(Number(x.stock || 0))} · Qo‘shilgan: {String(x.addedAt || '—')}</span><div className="d-flex flex-wrap gap-2 align-items-center mt-2"><span className="fw-semibold">{fmt(Number(x.unitPrice || x.price || 0))} so'm</span>{cart ? <span className="text-muted small">× {fmt(Number(x.quantity || 1))} = {fmt(Number(x.total || 0))} so'm</span> : null}{x.url ? <a className="btn btn-sm btn-light ms-auto" href={String(x.url)}><i className="bi bi-box-arrow-up-right me-1"></i>Product</a> : null}</div>{x.seller ? <span className="d-block text-muted small mt-1">Seller: {String(x.seller)}</span> : null}</div></div></div></div>)}{rows.length === 0 ? <div className="col-12"><div className="text-muted text-center py-4">{empty}</div></div> : null}</div></div></div>;
}
function TableBlock({ title, rows, cols }: { title: string; rows: Row[]; cols: Array<[string, string]> }) {
  const hasLinks = rows.some((row) => row.url);
  return <div className="col-12"><div className="detail-panel"><h6 className="fw-bold mb-3">{title}</h6><div className="table-responsive"><table className="table data-table mb-0"><thead><tr>{cols.map(([l]) => <th key={l}>{l}</th>)}{hasLinks ? <th>Amal</th> : null}</tr></thead><tbody>{rows.map((x, i) => <tr key={String(x.id || i)}>{cols.map(([, k]) => <td key={k}>{k === 'amount' ? `${fmt(Number(x[k] || 0))} so'm` : String(x[k] ?? '—')}</td>)}{hasLinks ? <td>{x.url ? <a className="btn btn-sm btn-light" href={String(x.url)}><i className="bi bi-box-arrow-up-right me-1"></i>Ochish</a> : '—'}</td> : null}</tr>)}{rows.length === 0 ? <tr><td colSpan={cols.length + (hasLinks ? 1 : 0)} className="text-center text-muted">Ma'lumot topilmadi</td></tr> : null}</tbody></table></div></div></div>;
}
function BlockForm({ action, onDone }: { action?: string; onDone: () => void }) { return <form className="row g-2 mt-2" onSubmit={(e) => { e.preventDefault(); if (!action) return; router.post(action, Object.fromEntries(new FormData(e.currentTarget).entries()), { preserveScroll: true, onSuccess: onDone }); }}><div className="col-md-4"><select name="block_period" className="form-select"><option value="10_days">10 kun</option><option value="1_month">1 oy</option><option value="1_year">1 yil</option><option value="3_years">3 yil</option><option value="forever">Abadiy</option></select></div><div className="col-md-6"><input name="block_reason" className="form-control" placeholder="Bloklash sababi" required /></div><div className="col-md-2"><button className="btn btn-outline-danger w-100">Bloklash</button></div></form>; }
function SplitBlockForm({ action, onDone }: { action: string; onDone: () => void }) { return <form className="row g-2 mt-2" onSubmit={(e) => { e.preventDefault(); if (!action) return; router.post(action, Object.fromEntries(new FormData(e.currentTarget).entries()), { preserveScroll: true, onSuccess: onDone }); }}><div className="col-md-9"><input name="reason" className="form-control" placeholder="Masalan: ko‘p risk signali, split vaqtincha to‘xtatildi" required /></div><div className="col-md-3"><button className="btn btn-outline-danger w-100">Splitni bloklash</button></div></form>; }
function SplitManualLimitForm({ action, current, onDone }: { action: string; current: number; onDone: () => void }) { return <form className="row g-2" onSubmit={(e) => { e.preventDefault(); if (!action) return; router.post(action, Object.fromEntries(new FormData(e.currentTarget).entries()), { preserveScroll: true, onSuccess: onDone }); }}><div className="col-md-9"><input name="amount" type="number" min={0} step={1000} className="form-control" defaultValue={current || ''} placeholder="Limit summasi (so'm), masalan 500000" required /></div><div className="col-md-3"><button className="btn btn-outline-primary w-100">{current > 0 ? 'Yangilash' : 'Limit berish'}</button></div></form>; }
function MapButtons({ mapLinks }: { mapLinks?: Record<string, string> }) {
  if (!mapLinks?.google && !mapLinks?.yandex) return <span className="text-muted small">Xarita linki yo'q</span>;
  return <div className="d-flex gap-2 flex-wrap mt-2">{mapLinks.google ? <a className="btn btn-sm btn-light" href={mapLinks.google} target="_blank" rel="noreferrer"><i className="bi bi-geo-alt me-1"></i>Google Map</a> : null}{mapLinks.yandex ? <a className="btn btn-sm btn-light" href={mapLinks.yandex} target="_blank" rel="noreferrer"><i className="bi bi-map me-1"></i>Yandex Map</a> : null}</div>;
}
