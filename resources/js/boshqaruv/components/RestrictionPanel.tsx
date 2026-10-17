import { useState, type FormEvent } from 'react';
import { router } from '@inertiajs/react';
import { Button, Form } from 'react-bootstrap';
import Modal from './AppModal';

/**
 * Do'kon / kuryer uchun "Cheklovlar" kartasi: sabab va muddat bilan bloklash,
 * blokdan chiqarish, pul yechishni muzlatish va balansni (daftar orqali) tuzatish.
 */
export interface Restriction {
  blocked: boolean;
  blockedAt?: string | null;
  blockedUntil?: string | null;
  reason?: string | null;
  blockedBy?: string | null;
  frozen: boolean;
  frozenAt?: string | null;
  freezeReason?: string | null;
  balance: number;
  hidden?: boolean | null;
  canFinance: boolean;
  durations: Record<string, string>;
  urls: { block: string; unblock: string; freeze: string; adjust: string; visibility?: string };
}

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

type Dialog = null | 'block' | 'freeze' | 'adjust';

export default function RestrictionPanel({ restriction: r, subject }: { restriction?: Restriction; subject: 'seller' | 'courier' }) {
  const [dialog, setDialog] = useState<Dialog>(null);
  const [busy, setBusy] = useState(false);
  if (!r) return null;

  const who = subject === 'seller' ? 'Do‘kon' : 'Kuryer';
  const close = () => { if (!busy) setDialog(null); };
  const opts = { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false), onSuccess: () => setDialog(null) };

  const submit = (event: FormEvent<HTMLFormElement>, url: string, extra: Record<string, unknown> = {}) => {
    event.preventDefault();
    const data = { ...Object.fromEntries(new FormData(event.currentTarget)), ...extra };
    router.post(url, data as Record<string, string>, opts);
  };

  const unblock = () => {
    if (!confirm(`${who} blokdan chiqarilsinmi?`)) return;
    router.patch(r.urls.unblock, { message: 'Admin tomonidan blokdan chiqarildi.' }, { preserveScroll: true });
  };

  return (
    <div className={`card ${r.blocked ? 'border-danger' : r.frozen ? 'border-warning' : ''}`}>
      <div className="card-header d-flex align-items-center justify-content-between">
        <h5 className="mb-0"><i className="ti ti-shield-lock me-1"></i>Cheklovlar</h5>
        {r.blocked ? <span className="badge text-light-danger">Cheklangan</span> : <span className="badge text-light-success">Faol</span>}
      </div>
      <div className="card-body">
        {r.blocked ? (
          <div className="alert alert-light-danger mb-3 f-s-13">
            <div className="f-w-600 mb-1">{r.blockedUntil ? `${r.blockedUntil} gacha cheklangan` : 'Muddatsiz bloklangan'}</div>
            {r.reason ? <div>Sabab: {r.reason}</div> : null}
            {r.blockedBy || r.blockedAt ? <div className="text-secondary">{[r.blockedBy, r.blockedAt].filter(Boolean).join(' · ')}</div> : null}
            <div className="text-secondary mt-1">Ilovaga kira olmaydi, ochiq sessiyalari yopilgan{subject === 'seller' ? ', mahsulotlari xaridorlarga ko‘rinmaydi' : ''}.</div>
          </div>
        ) : null}
        {r.hidden && !r.blocked ? <div className="alert alert-light-secondary mb-3 f-s-13"><i className="ti ti-eye-off me-1"></i>Do‘kon xaridorlardan yashirilgan</div> : null}
        {r.frozen ? (
          <div className="alert alert-light-warning mb-3 f-s-13">
            <div className="f-w-600 mb-1"><i className="ti ti-snowflake me-1"></i>Pul yechish muzlatilgan</div>
            {r.freezeReason ? <div>Sabab: {r.freezeReason}</div> : null}
            {r.frozenAt ? <div className="text-secondary">{r.frozenAt}</div> : null}
          </div>
        ) : null}

        <div className="d-flex justify-content-between align-items-center mb-3 f-s-14">
          <span className="text-secondary">Balans</span>
          <span className="f-w-600">{fmt(r.balance)} so‘m</span>
        </div>

        <div className="d-grid gap-2">
          {r.blocked
            ? <button type="button" className="btn btn-outline-success btn-sm" onClick={unblock}><i className="ti ti-lock-open me-1"></i>Blokdan chiqarish</button>
            : <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => setDialog('block')}><i className="ti ti-ban me-1"></i>Cheklash / bloklash</button>}
          {r.urls.visibility && !r.blocked ? (
            <button type="button" className="btn btn-light-secondary btn-sm" onClick={() => {
              if (!confirm(r.hidden ? 'Do‘kon yana xaridorlarga ko‘rsatilsinmi?' : 'Do‘kon va barcha mahsulotlari xaridorlardan yashirilsinmi? (do‘kon ilovasi ishlayveradi)')) return;
              router.post(r.urls.visibility as string, { hidden: r.hidden ? 0 : 1 }, { preserveScroll: true });
            }}><i className={`ti ${r.hidden ? 'ti-eye' : 'ti-eye-off'} me-1`}></i>{r.hidden ? 'Vitrinaga qaytarish' : 'Vitrinadan yashirish'}</button>
          ) : null}
          {r.canFinance ? (
            <>
              {r.frozen
                ? <button type="button" className="btn btn-light-warning btn-sm" disabled={busy} onClick={() => router.post(r.urls.freeze, { frozen: 0 }, { preserveScroll: true })}><i className="ti ti-sun me-1"></i>Pul yechishni ochish</button>
                : <button type="button" className="btn btn-light-warning btn-sm" onClick={() => setDialog('freeze')}><i className="ti ti-snowflake me-1"></i>Pul yechishni muzlatish</button>}
              <button type="button" className="btn btn-light-primary btn-sm" onClick={() => setDialog('adjust')}><i className="ti ti-adjustments-dollar me-1"></i>Balansni tuzatish</button>
            </>
          ) : <p className="f-s-12 text-secondary mb-0">Pul yechishni muzlatish va balansni tuzatish — moliya bo‘limi uchun.</p>}
        </div>
      </div>

      <Modal show={dialog === 'block'} onHide={close} centered>
        <Form onSubmit={(e) => submit(e, r.urls.block)}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{who}ni cheklash</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label>Muddat</Form.Label>
              <Form.Select name="duration" defaultValue="7_days">
                {Object.entries(r.durations).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
              </Form.Select>
            </Form.Group>
            <Form.Group>
              <Form.Label>Sabab (foydalanuvchiga ko‘rinadi)</Form.Label>
              <Form.Control as="textarea" rows={3} name="reason" required minLength={5} maxLength={500} placeholder="Masalan: soxta mahsulot bo‘yicha shikoyatlar tekshirilmoqda" />
            </Form.Group>
            <p className="f-s-12 text-secondary mt-2 mb-0">
              Darhol ilovadan chiqariladi va qayta kira olmaydi.
              {subject === 'seller' ? ' Xodimlari va do‘kon kuryerlari ham to‘xtaydi, mahsulotlar yashiriladi.' : ' Olib ketilmagan buyurtmalari boshqa kuryerlarga qaytariladi.'}
              {' '}Muddat tugagach avtomatik ochiladi.
            </p>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light-secondary" onClick={close}>Bekor</Button>
            <Button type="submit" variant="danger" disabled={busy}>{busy ? 'Saqlanmoqda…' : 'Cheklash'}</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      <Modal show={dialog === 'freeze'} onHide={close} centered>
        <Form onSubmit={(e) => submit(e, r.urls.freeze, { frozen: 1 })}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Pul yechishni muzlatish</Modal.Title></Modal.Header>
          <Modal.Body>
            <Form.Group>
              <Form.Label>Sabab</Form.Label>
              <Form.Control as="textarea" rows={3} name="reason" required maxLength={500} placeholder="Masalan: qaytarishlar bo‘yicha hisob-kitob yakunlanguncha" />
            </Form.Group>
            <p className="f-s-12 text-secondary mt-2 mb-0">Yangi pul yechish so‘rovlari va kutilayotganlarini tasdiqlash to‘xtatiladi. Balans o‘zgarmaydi.</p>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light-secondary" onClick={close}>Bekor</Button>
            <Button type="submit" variant="warning" disabled={busy}>Muzlatish</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      <Modal show={dialog === 'adjust'} onHide={close} centered>
        <Form onSubmit={(e) => submit(e, r.urls.adjust)}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Balansni tuzatish</Modal.Title></Modal.Header>
          <Modal.Body>
            <p className="f-s-13 text-secondary">Joriy balans: <b>{fmt(r.balance)} so‘m</b>. Qo‘shish uchun musbat, ayirish uchun manfiy summa kiriting (masalan −50000).</p>
            <Form.Group className="mb-3">
              <Form.Label>Summa (so‘m)</Form.Label>
              <Form.Control type="number" name="amount" required step={1} />
            </Form.Group>
            <Form.Group>
              <Form.Label>Sabab (daftarga yoziladi)</Form.Label>
              <Form.Control as="textarea" rows={2} name="reason" required minLength={5} maxLength={300} />
            </Form.Group>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light-secondary" onClick={close}>Bekor</Button>
            <Button type="submit" variant="primary" disabled={busy}>Saqlash</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
