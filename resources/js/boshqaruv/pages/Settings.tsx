import { useState } from 'react';

export default function Settings() {
  const [tab, setTab] = useState('general');
  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Sozlamalar</h1>
          <p className="page-subtitle">Tizim parametrlari, hisob va xavfsizlik</p>
        </div>
        <button className="btn btn-primary-gradient"><i className="bi bi-check2 me-1"></i>O'zgarishlarni saqlash</button>
      </div>

      <div className="row g-3">
        <div className="col-xl-3">
          <div className="card-panel">
            <nav className="nav flex-column">
              {[
                { k: 'general', l: 'Umumiy', i: 'bi-gear' },
                { k: 'account', l: 'Hisob', i: 'bi-person' },
                { k: 'notifications', l: 'Bildirishnomalar', i: 'bi-bell' },
                { k: 'security', l: 'Xavfsizlik', i: 'bi-shield-lock' },
                { k: 'payment', l: "To'lov usullari", i: 'bi-credit-card' },
                { k: 'shipping', l: 'Yetkazib berish', i: 'bi-truck' },
                { k: 'api', l: 'API & Integratsiya', i: 'bi-code-slash' },
              ].map((t) => (
                <a key={t.k} onClick={() => setTab(t.k)}
                  className={`d-flex align-items-center gap-2 px-3 py-2 rounded mb-1 text-decoration-none ${tab === t.k ? 'bg-primary bg-opacity-10 text-primary fw-semibold' : 'text-dark'}`}
                  style={{ cursor: 'pointer' }}>
                  <i className={`bi ${t.i}`}></i> {t.l}
                </a>
              ))}
            </nav>
          </div>
        </div>

        <div className="col-xl-9">
          {tab === 'general' && (
            <div className="card-panel">
              <h5 className="fw-bold mb-4">Umumiy sozlamalar</h5>
              <div className="row g-3">
                <div className="col-md-6"><label className="form-label small fw-semibold">Kompaniya nomi</label><input className="form-control" defaultValue="BookHub LLC" /></div>
                <div className="col-md-6"><label className="form-label small fw-semibold">Email</label><input className="form-control" defaultValue="info@bookhub.uz" /></div>
                <div className="col-md-6"><label className="form-label small fw-semibold">Telefon</label><input className="form-control" defaultValue="+998 71 200 00 00" /></div>
                <div className="col-md-6"><label className="form-label small fw-semibold">Valyuta</label><select className="form-select"><option>UZS — O'zbek so'mi</option><option>USD</option></select></div>
                <div className="col-md-6"><label className="form-label small fw-semibold">Til</label><select className="form-select"><option>O'zbek</option><option>Rus</option><option>Ingliz</option></select></div>
                <div className="col-md-6"><label className="form-label small fw-semibold">Vaqt zonasi</label><select className="form-select"><option>Asia/Tashkent (UTC+5)</option></select></div>
                <div className="col-12"><label className="form-label small fw-semibold">Manzil</label><textarea className="form-control" rows={2} defaultValue="Toshkent sh., Amir Temur shoh ko'chasi 108" /></div>
              </div>
            </div>
          )}

          {tab === 'account' && (
            <div className="card-panel">
              <h5 className="fw-bold mb-4">Hisob ma'lumotlari</h5>
              <div className="d-flex align-items-center gap-3 mb-4 pb-4 border-bottom">
                <div style={{ width: 80, height: 80, borderRadius: '50%', background: 'linear-gradient(135deg,#f472b6,#8b5cf6)', color: 'white', display: 'grid', placeItems: 'center', fontSize: 32, fontWeight: 700 }}>AS</div>
                <div>
                  <div className="fw-bold fs-5">Admin Sobir</div>
                  <div className="text-muted">admin@bookhub.uz</div>
                  <button className="btn btn-sm btn-outline-primary mt-2"><i className="bi bi-camera me-1"></i>Avatar o'zgartirish</button>
                </div>
              </div>
              <div className="row g-3">
                <div className="col-md-6"><label className="form-label small fw-semibold">To'liq ism</label><input className="form-control" defaultValue="Sobir Abdullayev" /></div>
                <div className="col-md-6"><label className="form-label small fw-semibold">Rol</label><input className="form-control" value="Super Administrator" disabled /></div>
                <div className="col-md-6"><label className="form-label small fw-semibold">Email</label><input className="form-control" defaultValue="admin@bookhub.uz" /></div>
                <div className="col-md-6"><label className="form-label small fw-semibold">Telefon</label><input className="form-control" defaultValue="+998 90 123 45 67" /></div>
              </div>
            </div>
          )}

          {tab === 'notifications' && (
            <div className="card-panel">
              <h5 className="fw-bold mb-4">Bildirishnomalar</h5>
              {[
                ['Yangi buyurtma', 'Har bir buyurtmada ogohlantirish', true],
                ['Kam qolgan mahsulot', 'Ombor 10 danadan kam bo\'lganda', true],
                ['Yangi foydalanuvchi', 'Mijoz ro\'yxatdan o\'tganda', false],
                ['Ticket ochilganda', 'Yuqori muhimlikdagi ticketlar', true],
                ['Haftalik hisobot', 'Har dushanba ertalab 9:00', true],
              ].map(([t, d, v]) => (
                <div key={String(t)} className="d-flex justify-content-between align-items-center py-3 border-bottom">
                  <div>
                    <div className="fw-semibold">{String(t)}</div>
                    <small className="text-muted">{d}</small>
                  </div>
                  <div className="form-check form-switch"><input type="checkbox" className="form-check-input" defaultChecked={Boolean(v)} /></div>
                </div>
              ))}
            </div>
          )}

          {tab === 'security' && (
            <div className="card-panel">
              <h5 className="fw-bold mb-4">Xavfsizlik</h5>
              <div className="mb-4 pb-4 border-bottom">
                <h6 className="fw-semibold">Parolni o'zgartirish</h6>
                <div className="row g-3">
                  <div className="col-12"><label className="form-label small">Joriy parol</label><input type="password" className="form-control" /></div>
                  <div className="col-md-6"><label className="form-label small">Yangi parol</label><input type="password" className="form-control" /></div>
                  <div className="col-md-6"><label className="form-label small">Tasdiqlang</label><input type="password" className="form-control" /></div>
                </div>
              </div>
              <h6 className="fw-semibold">Ikki bosqichli tasdiqlash</h6>
              <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                <div><div className="fw-semibold">2FA yoqilgan</div><small className="text-muted">Google Authenticator orqali</small></div>
                <div className="form-check form-switch"><input type="checkbox" className="form-check-input" defaultChecked /></div>
              </div>
            </div>
          )}

          {tab === 'payment' && (
            <div className="card-panel">
              <h5 className="fw-bold mb-4">To'lov usullari</h5>
              {[
                ['Click', 'Onlayn to\'lov tizimi', true],
                ['Payme', 'Onlayn to\'lov tizimi', true],
                ['Uzum', 'Uzum Nasiya va to\'lov', true],
                ['Naqd pul', 'Yetkazib berishda to\'lov', true],
                ['Bank kartasi', 'Visa / Mastercard / Humo / Uzcard', true],
              ].map(([n, d, v]) => (
                <div key={String(n)} className="d-flex justify-content-between align-items-center py-3 border-bottom">
                  <div className="d-flex align-items-center gap-2">
                    <div style={{ width: 40, height: 40, borderRadius: 8, background: 'linear-gradient(135deg,#c7d2fe,#f3e8ff)', display: 'grid', placeItems: 'center' }}><i className="bi bi-credit-card-2-front"></i></div>
                    <div><div className="fw-semibold">{String(n)}</div><small className="text-muted">{d}</small></div>
                  </div>
                  <div className="form-check form-switch"><input type="checkbox" className="form-check-input" defaultChecked={Boolean(v)} /></div>
                </div>
              ))}
            </div>
          )}

          {tab === 'shipping' && (
            <div className="card-panel">
              <h5 className="fw-bold mb-4">Yetkazib berish</h5>
              {[
                { z: 'Toshkent shahri', p: '25,000', t: '1 kun' },
                { z: 'Viloyat markazlari', p: '45,000', t: '2-3 kun' },
                { z: 'Tumanlar', p: '65,000', t: '3-5 kun' },
                { z: 'Qishloqlar', p: '85,000', t: '5-7 kun' },
              ].map((s) => (
                <div key={s.z} className="row g-2 py-3 border-bottom align-items-center">
                  <div className="col-md-5"><input className="form-control" defaultValue={s.z} /></div>
                  <div className="col-md-3"><div className="input-group"><input className="form-control" defaultValue={s.p} /><span className="input-group-text">so'm</span></div></div>
                  <div className="col-md-3"><input className="form-control" defaultValue={s.t} /></div>
                  <div className="col-md-1 text-end"><button className="btn btn-sm btn-light text-danger"><i className="bi bi-trash"></i></button></div>
                </div>
              ))}
            </div>
          )}

          {tab === 'api' && (
            <div className="card-panel">
              <h5 className="fw-bold mb-4">API va Integratsiyalar</h5>
              <div className="mb-4 p-3 bg-light rounded">
                <div className="fw-semibold mb-2">API Key</div>
                <code className="d-block p-2 bg-white rounded border">bh_live_sk_8f4a2c9e1d3b5a7f6e8c0d2b4a6f8e1c</code>
                <button className="btn btn-sm btn-outline-secondary mt-2"><i className="bi bi-arrow-repeat me-1"></i>Regenerate</button>
              </div>
              <h6 className="fw-semibold mb-3">Webhook URL</h6>
              <div className="input-group mb-4">
                <input className="form-control" defaultValue="https://bookhub.uz/api/webhooks" />
                <button className="btn btn-outline-secondary">Test</button>
              </div>
              <h6 className="fw-semibold mb-3">Ulangan servislar</h6>
              {[['Telegram Bot', true], ['SMS Gateway (Eskiz)', true], ['1C Buxgalteriya', false], ['Google Analytics', true]].map(([n, v]) => (
                <div key={String(n)} className="d-flex justify-content-between py-2 border-bottom">
                  <span>{String(n)}</span>
                  <div className="form-check form-switch"><input type="checkbox" className="form-check-input" defaultChecked={Boolean(v)} /></div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
