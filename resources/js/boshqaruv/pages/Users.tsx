import type React from 'react';
import { useEffect, useState, type FormEvent } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls from '../components/PaginationControls';

import { toneBadge } from '../utils/tone';
import { StatWidget, EmptyState } from '../components/Axelit';
import { Avatar as UserAvatar } from '../components/Profile';
import FormAction, { ActionRow } from '../components/FormAction';

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
        <div className="card-body">
          <div className="nav kc-segment mb-3">{tabs.map(([key, label]) => <div key={key} className="nav-item"><button
              className={`nav-link ${tab === key ? 'active' : ''}`}
              onClick={() => { setTab(key); loadUsers(1, key); }}>{label} <span className="badge">{fmt(userCounts[key] || 0)}</span></button></div>)}</div>
          <form className="d-flex align-items-center gap-2 flex-wrap mb-3" onSubmit={(e) => { e.preventDefault(); loadUsers(); }}>
            <div className="app-form app-icon-form position-relative" style={{ width: 'min(320px, 100%)' }}>
              <i className="ti ti-search"></i>
              <input className="form-control form-control-sm" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Ism, telefon, email yoki ID" aria-label="Foydalanuvchi qidirish" />
            </div>
            <button className="btn btn-sm btn-light-secondary" type="submit">Qidirish</button>
            {search ? <button className="btn btn-sm btn-light-secondary" type="button" onClick={() => { setSearch(''); loadUsers(1, tab, ''); }}>Tozalash</button> : null}
            <span className="ms-auto f-s-13 text-secondary">{fmt(userPagination.total)} ta foydalanuvchi</span>
          </form>
          <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle"><thead><tr><th>ID</th><th>Foydalanuvchi</th><th>Telefon</th><th className="text-end">Buyurtma</th><th className="text-end">Sarflangan</th><th className="text-end">Karta</th><th className="text-end">Oxirgi aktivlik</th><th>Holat</th><th></th></tr></thead><tbody>
            {users.map((user) => <tr key={user.id} role="button" onClick={() => openDetail(user)}>
              <td className="text-secondary text-nowrap kb-num">#{user.id}</td><td><div className="d-flex align-items-center gap-2"><UserAvatar src={user.avatar} name={user.name} size="md" /><div className="min-w-0"><div className="f-w-600 text-nowrap">{user.name}{user.verified ? <i className="ti ti-discount-check-filled text-primary ms-1" title="Tasdiqlangan"></i> : null}</div><div className="f-s-12 text-secondary text-nowrap">A'zo: {user.joinedLabel || user.joined || '—'}</div></div></div></td>
              <td className="text-nowrap">{user.phone || '—'}</td><td className="text-end f-w-600 text-nowrap">{user.orders}</td><td className="text-end f-w-600 text-nowrap">{fmt(user.spent)}</td><td className="text-end f-w-600 text-nowrap">{user.cards || 0}</td><td className="text-end">{user.online ? <span className="st ok"><i></i>Online</span> : <span className="f-s-13 text-secondary">{user.lastSeenAt || '—'}</span>}</td><td><Status user={user} /></td>
              <td className="text-end" onClick={(e) => e.stopPropagation()}><button type="button" className="btn btn-light-secondary icon-btn w-30 h-30" onClick={() => openDetail(user)} title="Profilni ochish" aria-label="Profilni ochish"><i className="ti ti-arrows-maximize"></i></button></td>
            </tr>)}
            {userPagination.total === 0 ? <tr><td colSpan={9} className="text-center py-5 text-secondary"><i className="ti ti-users d-block f-s-24 mb-2"></i>Foydalanuvchi topilmadi</td></tr> : null}
          </tbody></table></div>
          <PaginationControls {...userPagination} onPageChange={(page) => loadUsers(page)} />
        </div>
      </div>
      <ProfileModal user={selected} detail={detail} loading={loading} onHide={() => { setSelected(null); setDetail(null); }} reload={() => selected && openDetail(selected)} />
    </div>
  );
}

const USER_TABS: Array<[string, string, string]> = [
  ['overview', 'Umumiy', 'ti ti-layout-dashboard'],
  ['orders', 'Buyurtmalar', 'ti ti-receipt'],
  ['shopping', 'Savat va sevimlilar', 'ti ti-shopping-cart'],
  ['finance', 'Split va kartalar', 'ti ti-credit-card'],
  ['devices', 'Qurilma va manzillar', 'ti ti-device-mobile'],
  ['social', 'Kuzatuvchilar', 'ti ti-users'],
  ['other', 'Boshqa', 'ti ti-dots-circle-horizontal'],
];

function ProfileModal({ user, detail, loading, onHide, reload }: { user: UserRow | null; detail: UserDetail | null; loading: boolean; onHide: () => void; reload: () => void }) {
  const [tab, setTab] = useState('overview');
  useEffect(() => { setTab('overview'); }, [user?.id]);
  const p = detail?.profile || {};
  const s = detail?.stats || {};
  const split = detail?.splitProfile || null;
  const post = (url?: string, data: Record<string, string> = {}) => url && router.post(url, data, { preserveScroll: true, onSuccess: reload });
  const patch = (url?: string) => url && router.patch(url, {}, { preserveScroll: true, onSuccess: reload });
  const submitTo = (url?: string) => (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!url) return false;
    router.post(url, Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>, { preserveScroll: true, onSuccess: reload });
    return true;
  };
  const counts: Record<string, number> = detail ? {
    orders: detail.orders.length,
    shopping: detail.cartItems.length + detail.favourites.length + detail.stockAlerts.length,
    finance: detail.cards.length,
    devices: detail.devices.length + detail.addresses.length,
    social: detail.followers.length + detail.following.length,
    other: detail.searchHistory.length + detail.giftCertificates.length + detail.mysteryBoxes.length,
  } : {};
  const blocked = Boolean(p.blocked);

  return (
    <Modal show={!!user} onHide={onHide} centered size="xl">
      <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{user?.name}</Modal.Title></Modal.Header>
      <Modal.Body>
        {loading ? (
          <div className="text-center py-5"><span className="spinner-border text-primary"></span><p className="text-secondary mt-2 mb-0">Profil yuklanmoqda...</p></div>
        ) : !detail ? (
          <div className="alert alert-light-danger mb-0">Profilni yuklab bo‘lmadi.</div>
        ) : (
          <div data-kb-bare>
            {/* ── 360 sarlavha: kim, holati, aloqa, asosiy amallar ── */}
            <div className="card kb-u360-head">
              <div className="card-body">
                <div className="d-flex align-items-start gap-3">
                  <UserAvatar src={String(p.avatar || user?.avatar || '') || null} name={String(p.name || user?.name || '')} size="xl" />
                  <div className="flex-grow-1 min-w-0">
                    <div className="d-flex align-items-center gap-2 flex-wrap">
                      <h5 className="kb-u360-name mb-0">{String(p.name || user?.name || '')}</h5>
                      {p.verified ? <i className="ti ti-discount-check-filled text-primary f-s-18" title="Tasdiqlangan (verified)"></i> : null}
                      <Status user={user!} />
                      {user?.online ? <span className="badge text-light-success"><i className="ti ti-point-filled"></i>Online</span> : null}
                      {p.support ? <span className="badge text-light-info">Support</span> : null}
                      {p.phoneVerified ? null : <span className="badge text-light-warning">Telefon tasdiqlanmagan</span>}
                    </div>
                    <div className="kb-u360-meta">
                      {[
                        p.position || p.roleTitle ? String(p.position || p.roleTitle) : null,
                        p.phone ? String(p.phone) : null,
                        p.username ? `@${String(p.username)}` : null,
                        p.email ? String(p.email) : null,
                        p.joined ? `ro‘yxatdan o‘tgan: ${String(p.joined)}` : null,
                        user?.online ? null : p.lastSeenAt ? `oxirgi faollik: ${String(p.lastSeenAt)}` : null,
                      ].filter(Boolean).map((item, index) => <span key={index}>{item}</span>)}
                    </div>
                    {p.bio ? <p className="text-secondary f-s-13 mb-0 mt-2">{String(p.bio)}</p> : null}
                  <div className="d-flex gap-2 flex-wrap kb-u360-actions mt-3">
                    <button type="button" className={`btn btn-sm ${p.verified ? 'btn-light-secondary' : 'btn-light-primary'}`} disabled={!detail.actions.verifyUrl} onClick={() => patch(detail.actions.verifyUrl)}>
                      <i className="ti ti-discount-check"></i>{p.verified ? 'Verified — o‘chirish' : 'Verified berish'}
                    </button>
                    <button type="button" className={`btn btn-sm ${p.premium ? 'btn-light-secondary' : 'btn-light-warning'}`} disabled={!detail.actions.premiumUrl} onClick={() => patch(detail.actions.premiumUrl)}>
                      <i className="ti ti-crown"></i>{p.premium ? 'Premium — o‘chirish' : 'Premium berish'}
                    </button>
                    {blocked ? (
                      <button type="button" className="btn btn-sm btn-light-success" disabled={!detail.actions.unblockUrl} onClick={() => post(detail.actions.unblockUrl)}><i className="ti ti-lock-open"></i>Blokdan chiqarish</button>
                    ) : (
                      <FormAction label="Bloklash" icon="ti ti-lock" variant="light-danger" title="Foydalanuvchini bloklash" description="Bloklangan foydalanuvchi ilovaga kira olmaydi va buyurtma bera olmaydi." submitLabel="Bloklash" submitVariant="danger" disabled={!detail.actions.blockUrl} onSubmit={submitTo(detail.actions.blockUrl)}>
                        <label className="form-label">Muddat</label>
                        <select name="block_period" className="form-select mb-3" defaultValue="10_days">
                          <option value="10_days">10 kun</option>
                          <option value="1_month">1 oy</option>
                          <option value="1_year">1 yil</option>
                          <option value="3_years">3 yil</option>
                          <option value="forever">Abadiy</option>
                        </select>
                        <label className="form-label">Sabab</label>
                        <textarea name="block_reason" className="form-control" rows={2} placeholder="Bloklash sababi" required />
                      </FormAction>
                    )}
                  </div>
                  </div>
                </div>
                {blocked ? (
                  <div className="alert alert-light-danger d-flex align-items-start gap-2 mb-0 mt-3 py-2">
                    <i className="ti ti-lock mt-1"></i>
                    <div><strong>Bloklangan · {String(p.blockLabel || 'Abadiy')}.</strong> Sabab: {String(p.blockReason || '—')}</div>
                  </div>
                ) : null}
                <div className="kb-u360-stats">
                  {[
                    ['Buyurtmalar', fmt(Number(s.orders || 0)), `${fmt(Number(s.paidOrders || 0))} ta to‘langan`],
                    ['Sarflangan', `${fmt(Number(s.spent || 0))} so‘m`, null],
                    ['Balans', `${fmt(Number(p.balance || 0))} so‘m`, null],
                    ['Cashback', `${fmt(Number(p.cashback || 0))} so‘m`, null],
                    ['Kuzatuvchi', fmt(Number(s.followers || 0)), `kuzatadi: ${fmt(Number(s.following || 0))}`],
                    ['Ilovada', `${fmt(Math.round(Number(p.spentSeconds || 0) / 60))} daq.`, p.locale ? `til: ${String(p.locale)}` : null],
                  ].map(([label, value, sub]) => (
                    <div key={String(label)}>
                      <div className="kb-u360-stat-label">{label}</div>
                      <div className="kb-u360-stat-value">{value}</div>
                      {sub ? <div className="kb-u360-stat-sub">{sub}</div> : null}
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <ul className="nav nav-tabs kb-u360-tabs mb-3" role="tablist" aria-label="Profil bo'limlari">
              {USER_TABS.map(([key, label, icon]) => (
                <li className="nav-item" key={key}>
                  <button type="button" role="tab" aria-selected={tab === key} className={`nav-link ${tab === key ? 'active' : ''}`} onClick={() => setTab(key)}>
                    <i className={icon}></i>{label}{counts[key] ? <span className="kb-tab-count">{counts[key]}</span> : null}
                  </button>
                </li>
              ))}
            </ul>

            <div>
              {tab === 'overview' ? (
                <div className="row">
                  <div className="col-xl-7">
                    <div className="card">
                      <div className="card-header d-flex align-items-center justify-content-between">
                        <h5 className="mb-0">So‘nggi buyurtmalar</h5>
                        {detail.orders.length ? <button type="button" className="btn btn-link btn-sm p-0" onClick={() => setTab('orders')}>Hammasi ({fmt(Number(s.orders || detail.orders.length))})</button> : null}
                      </div>
                      <div className="card-body pt-0">
                        {detail.orders.slice(0, 5).map((order, index) => (
                          <div className="kb-u360-row" key={String(order.id || index)}>
                            <div className="min-w-0 flex-grow-1">
                              <div className="f-w-600 f-s-13">#{String(order.id ?? '—').replace(/^#/, '')}</div>
                              <div className="f-s-12 text-secondary text-truncate">{[order.date, order.delivery, order.payment].filter(Boolean).map(String).join(' · ')}</div>
                            </div>
                            <span className="badge text-light-secondary">{String(order.status ?? '—')}</span>
                            <div className="f-w-600 f-s-13 text-nowrap kb-num text-end" style={{ minWidth: 96 }}>{fmt(Number(order.amount || 0))} so‘m</div>
                            {order.url ? <a className="btn btn-light-secondary icon-btn w-30 h-30" href={String(order.url)} title="Buyurtmani ochish" aria-label="Buyurtmani ochish"><i className="ti ti-external-link"></i></a> : null}
                          </div>
                        ))}
                        {detail.orders.length === 0 ? <EmptyState text="Buyurtma hali yo‘q" /> : null}
                      </div>
                    </div>
                    <div className="row">
                      <Info title="Profil ma'lumotlari" rows={[['Telefon', p.phone], ['Telefon tasdig‘i', p.phoneVerified ? 'Tasdiqlangan' : 'Tasdiqlanmagan'], ['Telefon tasdiqlangan sana', p.phoneVerifiedAt], ['Verified badge', p.verified ? 'Yoqilgan' : 'O‘chiq'], ['Email', p.email], ['Username', p.username], ['Telegram ID', p.telegramId], ['Til', p.locale], ['Daraja', p.position], ['Role title', p.roleTitle], ['Staff roli', p.staffRole], ['AI limiti', p.aiLimit], ['Oxirgi faollik', p.lastSeenAt], ['Ro‘yxatdan o‘tgan', p.joined], ['Spent time', `${fmt(Number(p.spentSeconds || 0))} sec`]]} />
                      <Info title="Hisob va statistika" rows={[['Buyurtmalar', s.orders], ['To‘langan orderlar', s.paidOrders], ['Sarflangan', `${fmt(s.spent || 0)} so'm`], ['Balans', `${fmt(Number(p.balance || 0))} so'm`], ['Cashback', `${fmt(Number(p.cashback || 0))} so'm`], ['Kartalar', s.cards], ['Qurilmalar', s.devices], ['Manzillar', s.addresses], ['Search history', s.searches], ['Sevimlilar', s.favourites], ['Stock-alert (xabar berish)', s.stockAlerts], ['Savat itemlari', s.cartItems], ['Savat soni', s.cartQuantity], ['Savat jami', `${fmt(s.cartTotal || 0)} so'm`], ['Followers', s.followers], ['Following', s.following], ['Gift sertifikat', s.giftCertificates], ['Mystery Box', s.mysteryBoxes]]} />
                    </div>
                  </div>
                  <div className="col-xl-5">
                    <div className="card">
                      <div className="card-header d-flex align-items-center justify-content-between">
                        <h5 className="mb-0">Manzillar</h5>
                        {detail.addresses.length > 3 ? <button type="button" className="btn btn-link btn-sm p-0" onClick={() => setTab('devices')}>Hammasi ({detail.addresses.length})</button> : null}
                      </div>
                      <div className="card-body pt-0">
                        {detail.addresses.slice(0, 3).map((address, index) => {
                          const links = (address.mapLinks || {}) as Record<string, string>;
                          return (
                            <div className="kb-u360-row align-items-start" key={String(address.id || index)}>
                              <i className="ti ti-map-pin text-secondary f-s-18 mt-1"></i>
                              <div className="min-w-0 flex-grow-1">
                                <div className="f-s-13 text-dark">{String(address.address || 'Manzil kiritilmagan')}{address.main ? <span className="badge text-light-primary ms-2">Asosiy</span> : null}</div>
                                <div className="d-flex align-items-center gap-2 flex-wrap mt-1">
                                  {address.uzpostIndex ? <span className="kb-zip" title="Pochta indeksi"><i className="ti ti-mailbox"></i>{String(address.uzpostIndex)}</span> : <span className="f-s-12 text-secondary">Indeks aniqlanmagan</span>}
                                  {links.yandex ? <a className="f-s-12" href={links.yandex} target="_blank" rel="noreferrer">Yandex</a> : null}
                                  {links.google ? <a className="f-s-12" href={links.google} target="_blank" rel="noreferrer">Google</a> : null}
                                </div>
                              </div>
                            </div>
                          );
                        })}
                        {detail.addresses.length === 0 ? <EmptyState text="Manzil saqlanmagan" /> : null}
                      </div>
                    </div>
                    <div className="card">
                      <div className="card-header"><h5 className="mb-0">To‘lov va qurilmalar</h5></div>
                      <div className="card-body pt-0">
                        {detail.cards.map((card, index) => (
                          <div className="kb-u360-row" key={`c${String(card.id || index)}`}>
                            <i className="ti ti-credit-card text-secondary f-s-18"></i>
                            <div className="min-w-0 flex-grow-1">
                              <div className="f-s-13 f-w-600">{String(card.vendor || 'Karta')} · {String(card.number || '****')}</div>
                              <div className="f-s-12 text-secondary">{[card.name, card.expires].filter(Boolean).map(String).join(' · ') || '—'}</div>
                            </div>
                          </div>
                        ))}
                        {detail.devices.map((device, index) => (
                          <div className="kb-u360-row" key={`d${String(device.id || index)}`}>
                            <i className="ti ti-device-mobile text-secondary f-s-18"></i>
                            <div className="min-w-0 flex-grow-1">
                              <div className="f-s-13 f-w-600 text-truncate">{String(device.name || 'Noma’lum qurilma')}</div>
                              <div className="f-s-12 text-secondary">{String(device.platform || '—')}</div>
                            </div>
                            <span className={`badge ${device.push ? 'text-light-success' : 'text-light-secondary'}`}>Push: {device.push ? 'Ha' : "Yo'q"}</span>
                          </div>
                        ))}
                        {detail.cards.length + detail.devices.length === 0 ? <EmptyState text="Karta yoki qurilma yo‘q" /> : null}
                        {detail.cards.length ? <button type="button" className="btn btn-link btn-sm p-0 mt-2" onClick={() => setTab('finance')}>Kartalar va Split boshqaruvi</button> : null}
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}

              {tab === 'orders' ? (
                <div className="row">
                  <TableBlock title="So‘nggi buyurtmalar" rows={detail.orders} cols={[['ID', 'id'], ['Status', 'status'], ['To‘lov', 'payment'], ['Yetkazish', 'delivery'], ['Summa', 'amount'], ['Sana', 'date']]} />
                </div>
              ) : null}

              {tab === 'shopping' ? (
                <div className="row">
                  <ProductListBlock title="Savat" rows={detail.cartItems} empty="Savat bo‘sh" cart totalCount={Number(s.cartItems || 0)} />
                  <ProductListBlock title="Sevimlilar" rows={detail.favourites} empty="Sevimli mahsulot topilmadi" />
                  <ProductListBlock title="Kelganda xabar berish (stock-alert)" rows={detail.stockAlerts} empty="Xabar berish so‘rovi yo‘q" alert dateKey="requestedAt" totalCount={Number(s.stockAlerts || 0)} />
                </div>
              ) : null}

              {tab === 'finance' ? (
                <div className="row">
                  {split ? (
                    <>
                      <div className="col-xl-6">
                        <div className="card h-100">
                          <div className="card-header d-flex align-items-center justify-content-between gap-2">
                            <h5 className="mb-0">Split boshqaruvi</h5>
                            {split.manuallyBlocked ? <span className="badge text-light-danger">Split bloklangan</span> : <span className="badge text-light-success">Split ochiq</span>}
                          </div>
                          <div className="card-body pt-0">
                            <ActionRow
                              icon="ti ti-shield-lock"
                              tone={split.manuallyBlocked ? 'danger' : 'success'}
                              title="Split holati"
                              value={split.manuallyBlocked ? 'Admin tomonidan bloklangan' : split.eligible ? 'Mos (foydalanish mumkin)' : 'Mos emas'}
                              meta={split.manuallyBlocked ? `Sabab: ${String(split.manualBlockReason || '—')}` : undefined}
                              action={split.manuallyBlocked ? (
                                <button type="button" className="btn btn-sm btn-light-success" onClick={() => post(detail.actions.splitUnblockUrl)}>Blokni bekor qilish</button>
                              ) : (
                                <FormAction label="Bloklash" icon="ti ti-lock" variant="light-danger" title="Splitni bloklash" submitLabel="Splitni bloklash" submitVariant="danger" disabled={!detail.actions.splitBlockUrl} onSubmit={submitTo(String(detail.actions.splitBlockUrl || ''))}>
                                  <label className="form-label">Sabab</label>
                                  <textarea name="reason" className="form-control" rows={2} placeholder="Masalan: ko‘p risk signali, split vaqtincha to‘xtatildi" required />
                                </FormAction>
                              )}
                            />
                            <ActionRow
                              icon="ti ti-adjustments"
                              tone="primary"
                              title="Qo'lda split limit"
                              value={Number(split.manualLimit || 0) > 0 ? `${fmt(Number(split.manualLimit))} so'm` : 'Berilmagan — skoring rejimi'}
                              meta="Limit berilsa skoring talablari chetlab o'tiladi"
                              action={
                                <FormAction label={Number(split.manualLimit || 0) > 0 ? 'Yangilash' : 'Limit berish'} icon="ti ti-edit" title="Qo'lda split limit" description="Limit berilsa skoring talablari chetlab o'tiladi. Tasdiqlangan telefon, tasdiqlangan karta, admin blok, muddati o'tgan to'lov va default baribir tekshiriladi. 0 kiritilsa limit olib tashlanadi." disabled={!detail.actions.splitManualLimitUrl} onSubmit={submitTo(String(detail.actions.splitManualLimitUrl || ''))}>
                                  <label className="form-label">Limit summasi (so'm)</label>
                                  <input name="amount" type="number" min={0} step={1000} className="form-control" defaultValue={Number(split.manualLimit || 0) || ''} placeholder="Masalan 500000" required />
                                </FormAction>
                              }
                            />
                            {Array.isArray(split.reasons) && split.reasons.length > 0 ? (
                              <div className="pt-3">
                                <p className="mb-2 f-s-13 text-secondary">Split rad sabablari</p>
                                <div className="d-flex flex-wrap gap-2">{split.reasons.map((reason, index) => <span key={index} className="badge text-light-warning">{String(reason)}</span>)}</div>
                              </div>
                            ) : null}
                          </div>
                        </div>
                      </div>
                      <Info title="Split profili" rows={[['Moslik', split.eligible ? 'Ha' : "Yo'q"], ['Admin split blok', split.manuallyBlocked ? 'Yoqilgan' : 'Yo‘q'], ['Split blok sababi', split.manualBlockReason], ['Split bloklangan sana', split.manualBlockedAt], ['Ishonch skori', split.confidenceScore], ['Hisoblangan limit', `${fmt(Number(split.computedLimit || 0))} so'm`], ['Bo‘sh limit', `${fmt(Number(split.availableLimit || 0))} so'm`], ['Faol exposure', `${fmt(Number(split.activeExposure || 0))} so'm`], ['Reputation', split.reputationScore], ['Karta yoshi', `${fmt(Number(split.verifiedCardAgeDays || 0))} kun`], ['Saved-card to‘lovlar', split.successfulCardPayments180d], ['Completed orderlar', split.completedOrdersAll], ['COD strike', split.codReturnStrikes], ['Yangilangan', split.lastRefreshedAt]]} />
                    </>
                  ) : null}
                  <ListBlock title="Kartalar" icon="ti ti-credit-card" rows={detail.cards} render={(x) => ({
                    title: `${String(x.vendor || 'Karta')} · ${String(x.number || '****')}`,
                    meta: `${String(x.name || 'Nomsiz')} · ${String(x.expires || 'Muddat yo‘q')}`,
                    action: x.destroyUrl ? <button type="button" className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" title="Kartani o'chirish" onClick={() => confirm("Kartani o'chirasizmi?") && router.delete(String(x.destroyUrl), { preserveScroll: true, onSuccess: reload })}><i className="ti ti-trash"></i></button> : null,
                  })} />
                </div>
              ) : null}

              {tab === 'devices' ? (
                <div className="row">
                  <ListBlock title="Qurilmalar" icon="ti ti-device-mobile" rows={detail.devices} render={(x) => ({
                    title: String(x.name || 'Noma’lum qurilma'),
                    meta: `${String(x.platform || '—')} · ${String(x.deviceId || '—')}`,
                    action: <span className={`badge ${x.push ? 'text-light-success' : 'text-light-secondary'}`}>Push: {x.push ? 'Ha' : "Yo'q"}</span>,
                  })} />
                  <ListBlock title="Manzillar" icon="ti ti-map-pin" rows={detail.addresses} render={(x) => ({
                    title: String(x.address || 'Manzil kiritilmagan'),
                    meta: `${x.main ? 'Asosiy manzil' : 'Qo‘shimcha manzil'} · ${x.uzpostIndex ? `Pochta indeksi: ${String(x.uzpostIndex)} · ` : ''}${[x.lat, x.lon].filter(Boolean).join(', ') || 'koordinata yo‘q'}`,
                    extra: <MapButtons mapLinks={(x.mapLinks || {}) as Record<string, string>} />,
                  })} />
                </div>
              ) : null}

              {tab === 'social' ? (
                <div className="row">
                  <ListBlock title="Kuzatuvchilar (followers)" rows={detail.followers} person render={(x) => ({ title: String(x.name), meta: String(x.phone || 'Telefon yo‘q'), avatar: String(x.avatar || '') || null })} />
                  <ListBlock title="Kuzatadi (following)" rows={detail.following} person render={(x) => ({ title: String(x.name), meta: String(x.phone || 'Telefon yo‘q'), avatar: String(x.avatar || '') || null })} />
                </div>
              ) : null}

              {tab === 'other' ? (
                <div className="row">
                  <TableBlock title="Qidiruv tarixi" rows={detail.searchHistory} cols={[['So‘z', 'text'], ['Natija', 'resultName'], ['Turi', 'resultType'], ['Topilgan', 'resultCount'], ['Qidirilgan', 'searchCount'], ['Sana', 'date']]} />
                  <TableBlock title="Gift sertifikatlar" rows={detail.giftCertificates} cols={[['Kod', 'code'], ['Rol', 'role'], ['Status', 'status'], ['Miqdor', 'amount'], ['Muddat', 'expires']]} />
                  <ListBlock title="Mystery Box obunalari" icon="ti ti-gift" rows={detail.mysteryBoxes} render={(x) => ({
                    title: `${String(x.name)} · ${String(x.status)}`,
                    meta: `${String(x.booksPerMonth)} kitob / ${String(x.totalMonths)} oy · ${fmt(Number(x.price || 0))} so'm · progress ${String(x.progress)}%`,
                    extra: <div className="d-flex gap-2 flex-wrap mt-2">{x.showUrl ? <a className="btn btn-sm btn-light-secondary" href={String(x.showUrl)}><i className="ti ti-eye me-1"></i>Obunani ochish</a> : null}{x.indexUrl ? <a className="btn btn-sm btn-light-secondary" href={String(x.indexUrl)}><i className="ti ti-package me-1"></i>Mystery list</a> : null}</div>,
                  })} />
                </div>
              ) : null}
            </div>
          </div>
        )}
      </Modal.Body>
      <Modal.Footer><Button variant="light-secondary" onClick={onHide}>Yopish</Button></Modal.Footer>
    </Modal>
  );
}

function Status({ user }: { user: Pick<UserRow, 'status' | 'premium'> }) {
  const tone = user.status === 'blocked' ? 'danger' : user.status === 'pending' ? 'warn' : 'ok';
  const label = user.status === 'blocked' ? 'Bloklangan' : user.status === 'pending' ? 'Kutilmoqda' : user.premium ? 'Premium' : 'Faol';
  return <span className={`badge ${toneBadge(tone)}`}>{label}</span>;
}
function Info({ title, rows }: { title: string; rows: Array<[string, unknown]> }) { return <div className="col-xl-6"><div className="card h-100"><div className="card-header"><h5 className="mb-0">{title}</h5></div><div className="card-body"><ul className="list-group list-group-flush">{rows.map(([k, v]) => <li className="list-group-item d-flex justify-content-between gap-3 px-0" key={k}><span className="text-secondary">{k}</span><span className="f-w-600 text-dark text-end">{String(v ?? '—')}</span></li>)}</ul></div></div></div>; }
type ListItem = { title: React.ReactNode; meta?: React.ReactNode; action?: React.ReactNode; extra?: React.ReactNode; avatar?: string | null };
function ListBlock({ title, rows, render, icon = 'ti ti-point', person }: { title: string; rows: Row[]; render: (x: Row) => ListItem; icon?: string; person?: boolean }) {
  return (
    <div className="col-xl-6">
      <div className="card h-100">
        <div className="card-header d-flex align-items-center justify-content-between"><h5 className="mb-0">{title}</h5><span className="badge text-light-primary">{rows.length}</span></div>
        <div className="card-body pt-0">
          {rows.map((x, i) => {
            const item = render(x);
            return (
              <div className="py-3 b-b-1-light kc-action-row" key={String(x.id || i)}>
                <div className="d-flex align-items-center gap-3">
                  {person ? <UserAvatar src={item.avatar} name={typeof item.title === 'string' ? item.title : ''} /> : <span className="h-40 w-40 d-flex-center b-r-10 f-s-20 flex-shrink-0 text-light-primary"><i className={icon}></i></span>}
                  <div className="flex-grow-1 min-w-0">
                    <h6 className="mb-0 f-w-600 f-s-14 text-break">{item.title}</h6>
                    {item.meta ? <div className="f-s-12 text-secondary text-break">{item.meta}</div> : null}
                  </div>
                  {item.action ? <div className="flex-shrink-0">{item.action}</div> : null}
                </div>
                {item.extra}
              </div>
            );
          })}
          {rows.length === 0 ? <EmptyState text="Ma'lumot topilmadi" /> : null}
        </div>
      </div>
    </div>
  );
}
function ProductListBlock({ title, rows, empty, cart, alert, dateKey = 'addedAt', totalCount }: { title: string; rows: Row[]; empty: string; cart?: boolean; alert?: boolean; dateKey?: string; totalCount?: number }) {
  const countLabel = totalCount !== undefined && totalCount > rows.length ? `${rows.length}/${totalCount} ta` : `${rows.length} ta`;
  const dateLabel = alert ? 'So‘ragan' : 'Qo‘shilgan';
  return <div className="col-12"><div className="card"><div className="card-header d-flex align-items-center justify-content-between"><h5 className="mb-0">{alert ? <><i className="ti ti-bell me-2 text-warning"></i>{title}</> : title}</h5><span className="f-w-600 text-nowrap">{countLabel}</span></div><div className="card-body"><div className="row g-2">{rows.map((x, i) => { const outOfStock = Number(x.stock || 0) <= 0; return <div className="col-xl-6" key={String(x.id || i)}><div className="b-1-light b-r-15 p-3 h-100"><div className="d-flex gap-3 align-items-start"><div className="h-55 w-55 b-r-15 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 f-s-20">{x.image ? <img className="w-100 h-100 object-fit-cover" src={String(x.image)} alt="" /> : <i className="ti ti-package"></i>}</div><div className="min-w-0 flex-grow-1"><div className="d-flex flex-wrap gap-1 mb-1"><span className="badge text-light-info">{String(x.typeLabel || x.productType || 'Mahsulot')}</span>{x.variant ? <span className="badge text-light-primary">{String((x.variant as Row).name || 'Variant')}</span> : null}{alert ? <span className={`badge ${outOfStock ? 'text-light-danger' : 'text-light-success'}`}>{outOfStock ? 'Hozir tugagan' : 'Sotuvda bor'}</span> : <span className={`badge ${x.status === 'Faol' ? 'text-light-success' : 'text-light-secondary'}`}>{String(x.status || '—')}</span>}</div><strong className="d-block text-truncate">{String(x.name || 'Mahsulot')}</strong><span className="d-block text-muted f-s-13">ID #{String(x.productId || '—')} · Stock: {fmt(Number(x.stock || 0))} · {dateLabel}: {String(x[dateKey] || '—')}</span><div className="d-flex flex-wrap gap-2 align-items-center mt-2"><span className="f-w-600">{fmt(Number(x.unitPrice || x.price || 0))} so'm</span>{cart ? <span className="text-muted f-s-13">× {fmt(Number(x.quantity || 1))} = {fmt(Number(x.total || 0))} so'm</span> : null}{x.url ? <a className="btn btn-sm btn-light-secondary ms-auto" href={String(x.url)}><i className="ti ti-external-link me-1"></i>Product</a> : null}</div>{x.seller ? <span className="d-block text-muted f-s-13 mt-1">Seller: {String(x.seller)}</span> : null}</div></div></div></div>; })}{rows.length === 0 ? <div className="col-12"><div className="text-muted text-center py-4">{empty}</div></div> : null}</div></div></div></div>;
}
function TableBlock({ title, rows, cols }: { title: string; rows: Row[]; cols: Array<[string, string]> }) {
  const hasLinks = rows.some((row) => row.url);
  return <div className="col-12"><div className="card"><div className="card-header"><h5 className="mb-0">{title}</h5></div><div className="card-body"><div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle mb-0"><thead><tr>{cols.map(([l]) => <th key={l}>{l}</th>)}{hasLinks ? <th>Amal</th> : null}</tr></thead><tbody>{rows.map((x, i) => <tr key={String(x.id || i)}>{cols.map(([, k]) => <td key={k}>{k === 'amount' ? `${fmt(Number(x[k] || 0))} so'm` : String(x[k] ?? '—')}</td>)}{hasLinks ? <td>{x.url ? <a className="btn btn-sm btn-light-secondary" href={String(x.url)}><i className="ti ti-external-link me-1"></i>Ochish</a> : '—'}</td> : null}</tr>)}{rows.length === 0 ? <tr><td colSpan={cols.length + (hasLinks ? 1 : 0)} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Ma'lumot topilmadi</td></tr> : null}</tbody></table></div></div></div></div>;
}
function MapButtons({ mapLinks }: { mapLinks?: Record<string, string> }) {
  if (!mapLinks?.google && !mapLinks?.yandex) return <span className="text-muted f-s-13">Xarita linki yo'q</span>;
  return <div className="d-flex gap-2 flex-wrap mt-2">{mapLinks.google ? <a className="btn btn-sm btn-light-secondary" href={mapLinks.google} target="_blank" rel="noreferrer"><i className="ti ti-map-pin me-1"></i>Google Map</a> : null}{mapLinks.yandex ? <a className="btn btn-sm btn-light-secondary" href={mapLinks.yandex} target="_blank" rel="noreferrer"><i className="ti ti-map me-1"></i>Yandex Map</a> : null}</div>;
}
