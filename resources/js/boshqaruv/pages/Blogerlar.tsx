import { useState } from 'react';
import { PageCrumbs } from '../Layout';
import type { FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';

type Blogger = { id: number; name: string; firstName?: string; lastName?: string; phone?: string; address?: string; platforms?: string[]; shipments?: number; status: string; activeUntil?: string; instagramUrl?: string; telegramUrl?: string; youtubeUrl?: string; tiktokUrl?: string; dataUrl?: string; createUrl?: string; updateUrl?: string; destroyUrl?: string };
type Shipment = { id: number; scheduledFor?: string; status: string; deliveredAt?: string; note?: string; itemsText?: string; items?: Array<{ id: number; name: string }>; updateUrl?: string; destroyUrl?: string };
type Detail = Blogger & { shipments: Shipment[]; actions: Record<string, string> };

export default function Blogerlar() {
  const { bloggers = [] } = usePage<{ bloggers?: Blogger[] }>().props;
  const [selected, setSelected] = useState<Blogger | null>(null);
  const [detail, setDetail] = useState<Detail | null>(null);
  const [editing, setEditing] = useState<Partial<Blogger> | null>(null);
  const [shipment, setShipment] = useState<Partial<Shipment> | null>(null);

  const open = async (blogger: Blogger) => {
    setSelected(blogger); setDetail(null);
    const response = await fetch(blogger.dataUrl || `/boshqaruv/blogerlar/${blogger.id}/data`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (response.ok) setDetail(await response.json());
  };
  const destroy = (blogger: Blogger) => blogger.destroyUrl && confirm(`${blogger.name} blogerini o'chirasizmi?`) && router.delete(blogger.destroyUrl, { preserveScroll: true });

  return <div>
    <div className="page-head"><div><h1 className="page-title">Hamkor blogerlar</h1><PageCrumbs /><p className="page-subtitle">Blogerlar, social linklar va jo'natma nazorati</p></div><button className="btn btn-primary" onClick={() => setEditing({})}><i className="bi bi-plus-lg me-1"></i>Bloger qo'shish</button></div>
    <div className="row g-3">{bloggers.map((blogger) => <div className="col-xl-4 col-md-6" key={blogger.id}><div className="card-panel h-100"><div className="d-flex align-items-start gap-3 mb-3"><div className="resource-avatar" style={{ width: 44, height: 44 }}>{initials(blogger.name)}</div><div style={{ minWidth: 0, flex: 1 }}><div className="fw-bold text-truncate">{blogger.name}</div><small className="text-muted">{(blogger.platforms || []).join(', ') || blogger.phone || 'Bloger'} · {blogger.shipments || 0} shipment</small></div><span className={`chip ${blogger.status === 'Faol' ? 'chip-success' : 'chip-gray'}`}>{blogger.status}</span></div><div className="small text-muted mb-3">{blogger.address || blogger.activeUntil || '—'}</div><div className="d-flex gap-2"><button className="btn btn-sm btn-light-secondary flex-fill" onClick={() => open(blogger)}><i className="bi bi-eye"></i></button><button className="btn btn-sm btn-light-secondary flex-fill" onClick={() => setEditing(blogger)}><i className="bi bi-pencil"></i></button><button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(blogger)}><i className="bi bi-trash"></i></button></div></div></div>)}</div>
    <BloggerDetail blogger={selected} detail={detail} onHide={() => { setSelected(null); setDetail(null); }} onEdit={() => detail && setEditing(detail)} onShipment={(item) => setShipment(item || {})} />
    <BloggerForm blogger={editing} onHide={() => setEditing(null)} />
    <ShipmentForm shipment={shipment} action={shipment?.updateUrl || detail?.actions.shipmentStoreUrl} onHide={() => setShipment(null)} />
  </div>;
}

function BloggerDetail({ blogger, detail, onHide, onEdit, onShipment }: { blogger: Blogger | null; detail: Detail | null; onHide: () => void; onEdit: () => void; onShipment: (shipment?: Shipment) => void }) {
  const removeShipment = (item: Shipment) => item.destroyUrl && confirm("Jo'natma o'chirilsinmi?") && router.delete(item.destroyUrl, { preserveScroll: true });
  return <Modal show={!!blogger} onHide={onHide} size="xl" centered><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{blogger?.name}</Modal.Title></Modal.Header><Modal.Body>{!detail ? <div className="text-muted py-4 text-center">Ma'lumot yuklanmoqda...</div> : <div className="row g-3"><div className="col-lg-4"><div className="detail-panel h-100"><h6 className="fw-bold">Kontakt</h6><Info label="Telefon" value={detail.phone} /><Info label="Manzil" value={detail.address} /><Info label="Aktivlik" value={detail.activeUntil} /><Info label="Instagram" value={detail.instagramUrl} /><Info label="Telegram" value={detail.telegramUrl} /></div></div><div className="col-lg-8"><div className="detail-panel h-100"><div className="d-flex justify-content-between mb-3"><h6 className="fw-bold mb-0">Jo'natmalar</h6><button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" onClick={() => onShipment()}><i className="bi bi-plus-lg"></i></button></div>{detail.shipments.map((item) => <div className="border-bottom py-2" key={item.id}><div className="d-flex justify-content-between gap-2"><div><strong>{item.scheduledFor || '—'}</strong><div className="small text-muted">{item.status} · {item.note || ''}</div><div className="small">{(item.items || []).map((x) => x.name).join(', ')}</div></div><div><button className="btn btn-light-success icon-btn w-30 h-30 b-r-22 me-1" onClick={() => onShipment(item)}><i className="bi bi-pencil"></i></button><button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => removeShipment(item)}><i className="bi bi-trash"></i></button></div></div></div>)}{detail.shipments.length === 0 ? <div className="text-muted small">Jo'natma yo'q</div> : null}</div></div></div>}</Modal.Body><Modal.Footer>{detail ? <Button variant="outline-primary" onClick={onEdit}>Tahrirlash</Button> : null}<Button variant="light-secondary" onClick={onHide}>Yopish</Button></Modal.Footer></Modal>;
}

function BloggerForm({ blogger, onHide }: { blogger: Partial<Blogger> | null; onHide: () => void }) {
  const isEdit = !!blogger?.id;
  const submit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); const form = new FormData(event.currentTarget); if (isEdit) form.append('_method', 'put'); router.post(isEdit ? String(blogger?.updateUrl || `/boshqaruv/blogerlar/${blogger?.id}`) : '/boshqaruv/blogerlar', form, { preserveScroll: true, onSuccess: onHide }); };
  return <Modal show={!!blogger} onHide={onHide} centered><form onSubmit={submit}><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{isEdit ? 'Blogerni tahrirlash' : "Bloger qo'shish"}</Modal.Title></Modal.Header><Modal.Body><div className="row g-3"><Field name="first_name" label="Ism" defaultValue={blogger?.firstName || blogger?.name?.split(' ')[0]} required /><Field name="last_name" label="Familiya" defaultValue={blogger?.lastName || blogger?.name?.split(' ').slice(1).join(' ')} /><Field name="phone_number" label="Telefon" defaultValue={blogger?.phone} /><Field name="active_until" label="Aktiv muddat" type="datetime-local" defaultValue={blogger?.activeUntil ? `${blogger.activeUntil}T23:59` : ''} required /><Field name="instagram_url" label="Instagram" defaultValue={blogger?.instagramUrl} /><Field name="telegram_url" label="Telegram" defaultValue={blogger?.telegramUrl} /><Field name="youtube_url" label="YouTube" defaultValue={blogger?.youtubeUrl} /><Field name="tiktok_url" label="TikTok" defaultValue={blogger?.tiktokUrl} /><div className="col-12"><label className="form-label">Manzil</label><textarea name="address" defaultValue={blogger?.address || ''} className="form-control" rows={2}></textarea></div></div></Modal.Body><Modal.Footer><Button variant="light-secondary" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">Saqlash</Button></Modal.Footer></form></Modal>;
}

function ShipmentForm({ shipment, action, onHide }: { shipment: Partial<Shipment> | null; action?: string; onHide: () => void }) {
  const isEdit = !!shipment?.id;
  const submit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); if (!action) return; const form = new FormData(event.currentTarget); if (isEdit) form.append('_method', 'put'); router.post(action, form, { preserveScroll: true, onSuccess: onHide }); };
  return <Modal show={!!shipment} onHide={onHide} centered><form onSubmit={submit}><Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Jo'natma</Modal.Title></Modal.Header><Modal.Body><div className="row g-3"><Field name="scheduled_for" label="Sana" type="datetime-local" defaultValue={shipment?.scheduledFor} required /><div className="col-md-6"><label className="form-label">Status</label><select name="status" defaultValue={shipment?.status || 'pending'} className="form-select"><option value="pending">Pending</option><option value="delivered">Delivered</option></select></div><div className="col-12"><label className="form-label">Itemlar</label><textarea name="items_text" defaultValue={shipment?.itemsText || ''} className="form-control" rows={4} required></textarea></div><div className="col-12"><label className="form-label">Izoh</label><textarea name="note" defaultValue={shipment?.note || ''} className="form-control" rows={2}></textarea></div></div></Modal.Body><Modal.Footer><Button variant="light-secondary" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">Saqlash</Button></Modal.Footer></form></Modal>;
}

function Field({ name, label, type = 'text', defaultValue, required }: { name: string; label: string; type?: string; defaultValue?: string | null; required?: boolean }) { return <div className="col-md-6"><label className="form-label">{label}</label><input name={name} type={type} defaultValue={defaultValue || ''} required={required} className="form-control" /></div>; }
function Info({ label, value }: { label: string; value?: string | null }) { return <div className="mb-2"><small className="text-muted d-block">{label}</small><strong className="text-break">{value || '—'}</strong></div>; }
function initials(name?: string) { return (name || 'BL').split(' ').map((x) => x[0]).join('').slice(0, 2).toUpperCase(); }
