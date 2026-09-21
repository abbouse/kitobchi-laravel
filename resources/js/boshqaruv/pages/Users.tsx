import { useState } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';

import { toneBadge } from '../utils/tone';
import { StatWidget } from '../components/Axelit';

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
  followers: Row[]; following: Row[]; searchHistory: Row[]; favourites: Row[]; stockAlerts: Row[]; cartItems: Row[]; giftCertificates: Row[]; mysteryBoxes: Row[]; splitProfile?: Row | null; actions: Record<string, string>;
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
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div><h4 className="main-title mb-0">Foydalanuvchilar</h4><PageCrumbs /><p className="mb-0 text-secondary">Profil, online holat, kartalar, premium, bloklash va xarid tarixi boshqaruvi</p></div>
      </div>
      <div className="row">
        {[
          ['Jami', fmt(userCounts.all || 0), 'ti-users', 'rgba(var(--primary), 1)'], ['Online', fmt(userCounts.online || 0), 'ti-broadcast', 'rgba(var(--success), 1)'],
          ['Karta ulagan', fmt(userCounts.with_cards || 0), 'ti-credit-card', 'rgba(var(--primary), 1)'], ['Bloklangan', fmt(userCounts.blocked || 0), 'ti-lock-access', 'rgba(var(--danger), 1)'],
        ].map(([label, val, icon, color], kpiIndex) => <div className="col-xl-3 col-md-6" key={String(label)}><StatWidget index={kpiIndex} label={label} value={val} /></div>)}
      </div>
      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap"><div><h5 className="f-w-600">Foydalanuvchilar ro‘yxati</h5><small className="text-muted">{userPagination.total} ta yozuv</small></div><form className="d-flex gap-2" onSubmit={(e) => { e.preventDefault(); loadUsers(); }}><input className="form-control form-control-sm" style={{ maxWidth: 280 }} value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Ism, telefon, email yoki ID" /><button className="btn btn-sm btn-outline-secondary"><i className="ti ti-search"></i></button></form></div>
        <div className="card-body">

          <div className="nav nav-tabs app-tabs-primary flex-wrap mb-3">{tabs.map(([key, label]) => <div key={key} className="nav-item"><button
              className={`nav-link ${tab === key ? 'active' : ''}`}
              onClick={() => { setTab(key); loadUsers(1, key); }}>{label}<span className="badge text-light-secondary ms-2">{fmt(userCounts[key] || 0)}</span></button></div>)}</div>
          <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle"><thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Telefon</th><th className="text-end">Buyurtma</th><th className="text-end">Sarflangan</th><th className="text-end">Karta</th><th className="text-end">Oxirgi aktivlik</th><th>Holat</th><th></th></tr></thead><tbody>
            {users.map((user) => <tr key={user.id}>
              <td className="f-w-600 text-nowrap">#{user.id}</td><td><div className="d-flex align-items-center gap-2"><Avatar user={user} /><div><div className="f-w-600">{user.name}</div><small className="text-muted">A'zo bo'lgan: {user.joinedLabel || user.joined || '—'}</small></div></div></td>
              <td className="f-w-600 text-nowrap">{user.phone || '—'}</td><td className="text-end f-w-600 text-nowrap">{user.orders}</td><td className="text-end f-w-600 text-nowrap">{fmt(user.spent)}</td><td className="text-end f-w-600 text-nowrap">{user.cards || 0}</td><td className="text-end">{user.online ? <span className="st ok"><i></i>Online</span> : <span className="f-s-13 text-secondary">{user.lastSeenAt || '—'}</span>}</td><td><Status user={user} /></td>
              <td><button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => openDetail(user)}><i className="ti ti-eye"></i></button></td>
            </tr>)}
            {userPagination.total === 0 ? <tr><td colSpan={9} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Foydalanuvchi topilmadi</td></tr> : null}
          </tbody></table></div>
          <PaginationControls {...userPagination} onPageChange={(page) => loadUsers(page)} />
        </div>
      </div>
      <ProfileModal user={selected} detail={detail} loading={loading} onHide={() => { setSelected(null); setDetail(null); }} reload={() => selected && openDetail(selected)} />
    </div>
  );
}

function ProfileModal({ user, detail, loading, onHide, reload }: { user: UserRow | null; detail: UserDetail | null; loading: boolean; onHide: () => void; reload: () => void }) {
  const p = detail?.profile || {}; const s = detail?.stats || {};
  const split = detail?.splitProfile || null;
  const post = (url?: string, data: Record<string, string> = {}) => url && router.post(url, data, { preserveScroll: true, onSuccess: reload });
  return (
    <Modal show={!!user} onHide={onHide} centered size="xl"><Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{user?.name}</Modal.Title></Modal.Header><Modal.Body>
            {loading ? <div className="text-center py-5 text-muted">Profil yuklanmoqda...</div> : !detail ? <div className="text-danger">Profilni yuklab bo‘lmadi.</div> : <div className="row">
              <div className="col-12"><div className="card"><div className="card-body"><div className="d-flex flex-wrap align-items-center gap-3"><Avatar user={{ ...user!, avatar: String(p.avatar || '') }} large /><div><h4 className="mb-1">{String(p.name || '')}</h4><div className="text-muted">{String(p.email || 'Email yo‘q')} · {String(p.phone || 'Telefon yo‘q')}</div><div className="d-flex flex-wrap gap-2 mt-2"><Status user={user!} />{p.phoneVerified ? <span className="badge text-light-success">Telefon tasdiqlangan</span> : <span className="badge text-light-warning">Telefon tasdiqlanmagan</span>}{p.verified ? <span className="badge text-light-info">Verified badge</span> : null}{p.premium ? <span className="badge text-light-warning">Premium</span> : null}{p.support ? <span className="badge text-light-info">Support</span> : null}</div></div></div></div></div></div>
              <Info title="Profil ma'lumotlari" rows={[['Telefon', p.phone], ['Telefon tasdig‘i', p.phoneVerified ? 'Tasdiqlangan' : 'Tasdiqlanmagan'], ['Telefon tasdiqlangan sana', p.phoneVerifiedAt], ['Verified badge', p.verified ? 'Yoqilgan' : 'O‘chiq'], ['Email', p.email], ['Username', p.username], ['Telegram ID', p.telegramId], ['Til', p.locale], ['Daraja', p.position], ['Role title', p.roleTitle], ['Staff roli', p.staffRole], ['AI limiti', p.aiLimit], ['Oxirgi faollik', p.lastSeenAt], ['Ro‘yxatdan o‘tgan', p.joined], ['Spent time', `${fmt(Number(p.spentSeconds || 0))} sec`], ['Bio', p.bio]]} />
              <Info title="Hisob va statistika" rows={[['Buyurtmalar', s.orders], ['To‘langan orderlar', s.paidOrders], ['Sarflangan', `${fmt(s.spent || 0)} so'm`], ['Balans', `${fmt(Number(p.balance || 0))} so'm`], ['Cashback', `${fmt(Number(p.cashback || 0))} so'm`], ['Kartalar', s.cards], ['Qurilmalar', s.devices], ['Manzillar', s.addresses], ['Search history', s.searches], ['Sevimlilar', s.favourites], ['Stock-alert (xabar berish)', s.stockAlerts], ['Savat itemlari', s.cartItems], ['Savat soni', s.cartQuantity], ['Savat jami', `${fmt(s.cartTotal || 0)} so'm`], ['Followers', s.followers], ['Following', s.following], ['Gift sertifikat', s.giftCertificates], ['Mystery Box', s.mysteryBoxes]]} />
              {split ? <Info title="Split profili" rows={[['Moslik', split.eligible ? 'Ha' : "Yo'q"], ['Admin split blok', split.manuallyBlocked ? 'Yoqilgan' : 'Yo‘q'], ['Split blok sababi', split.manualBlockReason], ['Split bloklangan sana', split.manualBlockedAt], ['Ishonch skori', split.confidenceScore], ['Hisoblangan limit', `${fmt(Number(split.computedLimit || 0))} so'm`], ['Bo‘sh limit', `${fmt(Number(split.availableLimit || 0))} so'm`], ['Faol exposure', `${fmt(Number(split.activeExposure || 0))} so'm`], ['Reputation', split.reputationScore], ['Karta yoshi', `${fmt(Number(split.verifiedCardAgeDays || 0))} kun`], ['Saved-card to‘lovlar', split.successfulCardPayments180d], ['Completed orderlar', split.completedOrdersAll], ['COD strike', split.codReturnStrikes], ['Yangilangan', split.lastRefreshedAt]]} /> : null}
              <div className="col-12"><div className="card"><div className="card-header"><h5 className="mb-0">Akkaunt boshqaruvi</h5></div><div className="card-body">{p.blocked ? <div className="alert alert-light-danger mb-2">Bloklangan: {String(p.blockLabel || 'Abadiy')}<br />Sabab: {String(p.blockReason || '—')}</div> : <BlockForm action={detail.actions.blockUrl} onDone={reload} />}{split ? <div className="mt-3 pt-3 b-t-1-light"><div className="d-flex flex-wrap align-items-center gap-2 mb-2"><h6 className="f-w-600 mb-0">Split boshqaruvi</h6>{split.manuallyBlocked ? <span className="badge text-light-danger">Split bloklangan</span> : <span className="badge text-light-success">Split ochiq</span>}</div>{split.manuallyBlocked ? <div className="alert alert-light-warning mb-2">Sabab: {String(split.manualBlockReason || '—')}</div> : <SplitBlockForm action={String(detail.actions.splitBlockUrl || '')} onDone={reload} />}<div className="d-flex flex-wrap gap-2 mt-2">{split.manuallyBlocked ? <Button variant="outline-success" onClick={() => post(detail.actions.splitUnblockUrl)}>Split blokni bekor qilish</Button> : null}</div><div className="mt-3 pt-3 b-t-1-light"><div className="f-s-13 f-w-600 mb-1">Qo'lda split limit {Number(split.manualLimit || 0) > 0 ? <span className="badge text-light-success ms-1">joriy: {Number(split.manualLimit).toLocaleString()} so'm</span> : <span className="text-muted">(berilmagan — skoring rejimi)</span>}</div><SplitManualLimitForm action={String(detail.actions.splitManualLimitUrl || '')} current={Number(split.manualLimit || 0)} onDone={reload} /><div className="f-s-13 text-muted mt-1">Limit berilsa skoring talablari chetlab o'tiladi. Tasdiqlangan telefon, tasdiqlangan karta, admin blok, muddati o'tgan to'lov va default baribir tekshiriladi. 0 kiritilsa limit olib tashlanadi.</div></div></div> : null}<div className="d-flex flex-wrap gap-2 mt-3">{p.blocked ? <Button variant="outline-success" onClick={() => post(detail.actions.unblockUrl)}>Blokdan chiqarish</Button> : null}<Button variant="outline-secondary" onClick={() => router.patch(detail.actions.verifyUrl, {}, { preserveScroll: true, onSuccess: reload })}>{p.verified ? 'Verified badge o‘chirish' : 'Verified badge yoqish'}</Button><Button variant="outline-warning" onClick={() => router.patch(detail.actions.premiumUrl, {}, { preserveScroll: true, onSuccess: reload })}>{p.premium ? "Premiumni o'chirish" : 'Premium yoqish'}</Button></div></div></div></div>
              {split && Array.isArray(split.reasons) && split.reasons.length > 0 ? <div className="col-12"><div className="card"><div className="card-header"><h5 className="mb-0">Split rad sabablari</h5></div><div className="card-body"><div className="d-flex flex-wrap gap-2 mt-2">{split.reasons.map((reason, index) => <span key={index} className="badge text-light-warning">{String(reason)}</span>)}</div></div></div></div> : null}
              <TableBlock title="So‘nggi buyurtmalar" rows={detail.orders} cols={[['ID', 'id'], ['Status', 'status'], ['To‘lov', 'payment'], ['Yetkazish', 'delivery'], ['Summa', 'amount'], ['Sana', 'date']]} />
              <TableBlock title="Search history" rows={detail.searchHistory} cols={[['So‘z', 'text'], ['Natija', 'resultName'], ['Turi', 'resultType'], ['Topilgan', 'resultCount'], ['Qidirilgan', 'searchCount'], ['Sana', 'date']]} />
              <ProductListBlock title="Sevimlilar" rows={detail.favourites} empty="Sevimli mahsulot topilmadi" />
              <ProductListBlock title="Kelganda xabar berish (stock-alert)" rows={detail.stockAlerts} empty="Xabar berish so‘rovi yo‘q" alert dateKey="requestedAt" totalCount={Number(s.stockAlerts || 0)} />
              <ProductListBlock title="Savat" rows={detail.cartItems} empty="Savat bo‘sh" cart totalCount={Number(s.cartItems || 0)} />
              <ListBlock title="Kartalar" rows={detail.cards} render={(x) => <><strong>{String(x.vendor)} · {String(x.number || '****')}</strong><span>{String(x.name || 'Nomsiz')} · {String(x.expires || 'Muddat yo‘q')}</span>{x.destroyUrl ? <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22 mt-2" onClick={() => confirm("Kartani o'chirasizmi?") && router.delete(String(x.destroyUrl), { preserveScroll: true, onSuccess: reload })}><i className="ti ti-trash"></i></button> : null}</>} />
              <ListBlock title="Qurilmalar" rows={detail.devices} render={(x) => <><strong>{String(x.name || 'Noma’lum qurilma')}</strong><span>{String(x.platform || '—')} · {String(x.deviceId || '—')} · Push: {x.push ? 'Ha' : "Yo'q"}</span></>} />
              <ListBlock title="Manzillar" rows={detail.addresses} render={(x) => <><strong>{String(x.address || 'Manzil kiritilmagan')}</strong><span>{x.main ? 'Asosiy manzil' : 'Qo‘shimcha manzil'} · {[x.lat, x.lon].filter(Boolean).join(', ') || 'koordinata yo‘q'}</span><MapButtons mapLinks={(x.mapLinks || {}) as Record<string, string>} /></>} />
              <ListBlock title="Followers" rows={detail.followers} render={(x) => <><strong>{String(x.name)}</strong><span>{String(x.phone || 'Telefon yo‘q')}</span></>} />
              <ListBlock title="Following" rows={detail.following} render={(x) => <><strong>{String(x.name)}</strong><span>{String(x.phone || 'Telefon yo‘q')}</span></>} />
              <TableBlock title="Gift sertifikatlar" rows={detail.giftCertificates} cols={[['Kod', 'code'], ['Rol', 'role'], ['Status', 'status'], ['Miqdor', 'amount'], ['Muddat', 'expires']]} />
              <ListBlock title="Mystery Box obunalari" rows={detail.mysteryBoxes} render={(x) => <><strong>{String(x.name)} · {String(x.status)}</strong><span>{String(x.booksPerMonth)} kitob / {String(x.totalMonths)} oy · {fmt(Number(x.price || 0))} so'm · progress {String(x.progress)}%</span><div className="d-flex gap-2 flex-wrap mt-2">{x.showUrl ? <a className="btn btn-sm btn-light-secondary" href={String(x.showUrl)}><i className="ti ti-eye me-1"></i>Obunani ochish</a> : null}{x.indexUrl ? <a className="btn btn-sm btn-light-secondary" href={String(x.indexUrl)}><i className="ti ti-package me-1"></i>Mystery list</a> : null}</div></>} />
            </div>}
          </Modal.Body><Modal.Footer><Button variant="light-secondary" onClick={onHide}>Yopish</Button></Modal.Footer></Modal>
  );
}

function Avatar({ user, large }: { user: Pick<UserRow, 'name' | 'avatar'>; large?: boolean }) { return <div className={`d-flex-center bg-light-primary f-w-600 overflow-hidden flex-shrink-0 ${large ? 'h-55 w-55 b-r-50 f-s-18' : 'h-45 w-45 b-r-10 f-s-16'}`}>{user.avatar ? <img className="w-100 h-100 object-fit-cover" src={user.avatar} alt="" /> : user.name.slice(0, 2).toUpperCase()}</div>; }
function Status({ user }: { user: Pick<UserRow, 'status' | 'premium'> }) {
  const tone = user.status === 'blocked' ? 'danger' : user.status === 'pending' ? 'warn' : 'ok';
  const label = user.status === 'blocked' ? 'Bloklangan' : user.status === 'pending' ? 'Kutilmoqda' : user.premium ? 'Premium' : 'Faol';
  return <span className={`badge text-uppercase ${toneBadge(tone)}`}>{label}</span>;
}
function Info({ title, rows }: { title: string; rows: Array<[string, unknown]> }) { return <div className="col-xl-6"><div className="card h-100"><div className="card-header"><h5 className="mb-0">{title}</h5></div><div className="card-body"><ul className="list-group list-group-flush">{rows.map(([k, v]) => <li className="list-group-item d-flex justify-content-between gap-3 px-0" key={k}><span className="text-secondary">{k}</span><span className="f-w-600 text-dark text-end">{String(v ?? '—')}</span></li>)}</ul></div></div></div>; }
function ListBlock({ title, rows, render }: { title: string; rows: Row[]; render: (x: Row) => React.ReactNode }) { return <div className="col-xl-6"><div className="card h-100"><div className="card-header"><h5 className="mb-0">{title}</h5></div><div className="card-body"><div className="d-grid gap-2">{rows.map((x, i) => <div className="b-1-light b-r-15 p-3" key={String(x.id || i)}>{render(x)}</div>)}{rows.length === 0 ? <div className="text-muted">Ma'lumot topilmadi</div> : null}</div></div></div></div>; }
function ProductListBlock({ title, rows, empty, cart, alert, dateKey = 'addedAt', totalCount }: { title: string; rows: Row[]; empty: string; cart?: boolean; alert?: boolean; dateKey?: string; totalCount?: number }) {
  const countLabel = totalCount !== undefined && totalCount > rows.length ? `${rows.length}/${totalCount} ta` : `${rows.length} ta`;
  const dateLabel = alert ? 'So‘ragan' : 'Qo‘shilgan';
  return <div className="col-12"><div className="card"><div className="card-header d-flex align-items-center justify-content-between"><h5 className="mb-0">{alert ? <><i className="ti ti-bell me-2 text-warning"></i>{title}</> : title}</h5><span className="f-w-600 text-nowrap">{countLabel}</span></div><div className="card-body"><div className="row g-2">{rows.map((x, i) => { const outOfStock = Number(x.stock || 0) <= 0; return <div className="col-xl-6" key={String(x.id || i)}><div className="b-1-light b-r-15 p-3 h-100"><div className="d-flex gap-3 align-items-start"><div className="h-55 w-55 b-r-15 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 f-s-20">{x.image ? <img className="w-100 h-100 object-fit-cover" src={String(x.image)} alt="" /> : <i className="ti ti-package"></i>}</div><div className="min-w-0 flex-grow-1"><div className="d-flex flex-wrap gap-1 mb-1"><span className="badge text-light-info">{String(x.typeLabel || x.productType || 'Mahsulot')}</span>{x.variant ? <span className="badge text-light-primary">{String((x.variant as Row).name || 'Variant')}</span> : null}{alert ? <span className={`badge ${outOfStock ? 'text-light-danger' : 'text-light-success'}`}>{outOfStock ? 'Hozir tugagan' : 'Sotuvda bor'}</span> : <span className={`badge ${x.status === 'Faol' ? 'text-light-success' : 'text-light-secondary'}`}>{String(x.status || '—')}</span>}</div><strong className="d-block text-truncate">{String(x.name || 'Mahsulot')}</strong><span className="d-block text-muted f-s-13">ID #{String(x.productId || '—')} · Stock: {fmt(Number(x.stock || 0))} · {dateLabel}: {String(x[dateKey] || '—')}</span><div className="d-flex flex-wrap gap-2 align-items-center mt-2"><span className="f-w-600">{fmt(Number(x.unitPrice || x.price || 0))} so'm</span>{cart ? <span className="text-muted f-s-13">× {fmt(Number(x.quantity || 1))} = {fmt(Number(x.total || 0))} so'm</span> : null}{x.url ? <a className="btn btn-sm btn-light-secondary ms-auto" href={String(x.url)}><i className="ti ti-external-link me-1"></i>Product</a> : null}</div>{x.seller ? <span className="d-block text-muted f-s-13 mt-1">Seller: {String(x.seller)}</span> : null}</div></div></div></div>; })}{rows.length === 0 ? <div className="col-12"><div className="text-muted text-center py-4">{empty}</div></div> : null}</div></div></div></div>;
}
function TableBlock({ title, rows, cols }: { title: string; rows: Row[]; cols: Array<[string, string]> }) {
  const hasLinks = rows.some((row) => row.url);
  return <div className="col-12"><div className="card"><div className="card-header"><h5 className="mb-0">{title}</h5></div><div className="card-body"><div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle mb-0"><thead><tr>{cols.map(([l]) => <th key={l}>{l}</th>)}{hasLinks ? <th>Amal</th> : null}</tr></thead><tbody>{rows.map((x, i) => <tr key={String(x.id || i)}>{cols.map(([, k]) => <td key={k}>{k === 'amount' ? `${fmt(Number(x[k] || 0))} so'm` : String(x[k] ?? '—')}</td>)}{hasLinks ? <td>{x.url ? <a className="btn btn-sm btn-light-secondary" href={String(x.url)}><i className="ti ti-external-link me-1"></i>Ochish</a> : '—'}</td> : null}</tr>)}{rows.length === 0 ? <tr><td colSpan={cols.length + (hasLinks ? 1 : 0)} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Ma'lumot topilmadi</td></tr> : null}</tbody></table></div></div></div></div>;
}
function BlockForm({ action, onDone }: { action?: string; onDone: () => void }) { return <form className="row g-2 mt-2" onSubmit={(e) => { e.preventDefault(); if (!action) return; router.post(action, Object.fromEntries(new FormData(e.currentTarget).entries()), { preserveScroll: true, onSuccess: onDone }); }}><div className="col-md-4"><select name="block_period" className="form-select"><option value="10_days">10 kun</option><option value="1_month">1 oy</option><option value="1_year">1 yil</option><option value="3_years">3 yil</option><option value="forever">Abadiy</option></select></div><div className="col-md-6"><input name="block_reason" className="form-control" placeholder="Bloklash sababi" required /></div><div className="col-md-2"><button className="btn btn-outline-danger w-100">Bloklash</button></div></form>; }
function SplitBlockForm({ action, onDone }: { action: string; onDone: () => void }) { return <form className="row g-2 mt-2" onSubmit={(e) => { e.preventDefault(); if (!action) return; router.post(action, Object.fromEntries(new FormData(e.currentTarget).entries()), { preserveScroll: true, onSuccess: onDone }); }}><div className="col-md-9"><input name="reason" className="form-control" placeholder="Masalan: ko‘p risk signali, split vaqtincha to‘xtatildi" required /></div><div className="col-md-3"><button className="btn btn-outline-danger w-100">Splitni bloklash</button></div></form>; }
function SplitManualLimitForm({ action, current, onDone }: { action: string; current: number; onDone: () => void }) { return <form className="row g-2" onSubmit={(e) => { e.preventDefault(); if (!action) return; router.post(action, Object.fromEntries(new FormData(e.currentTarget).entries()), { preserveScroll: true, onSuccess: onDone }); }}><div className="col-md-9"><input name="amount" type="number" min={0} step={1000} className="form-control" defaultValue={current || ''} placeholder="Limit summasi (so'm), masalan 500000" required /></div><div className="col-md-3"><button className="btn btn-outline-primary w-100">{current > 0 ? 'Yangilash' : 'Limit berish'}</button></div></form>; }
function MapButtons({ mapLinks }: { mapLinks?: Record<string, string> }) {
  if (!mapLinks?.google && !mapLinks?.yandex) return <span className="text-muted f-s-13">Xarita linki yo'q</span>;
  return <div className="d-flex gap-2 flex-wrap mt-2">{mapLinks.google ? <a className="btn btn-sm btn-light-secondary" href={mapLinks.google} target="_blank" rel="noreferrer"><i className="ti ti-map-pin me-1"></i>Google Map</a> : null}{mapLinks.yandex ? <a className="btn btn-sm btn-light-secondary" href={mapLinks.yandex} target="_blank" rel="noreferrer"><i className="ti ti-map me-1"></i>Yandex Map</a> : null}</div>;
}
