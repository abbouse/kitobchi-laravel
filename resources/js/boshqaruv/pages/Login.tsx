import { useState } from 'react';
import { router } from '@inertiajs/react';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(true);

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    router.post('/boshqaruv/login', { email, password, remember });
  };

  return (
    <div className="login-wrap">
      <div className="login-left">
        <div style={{ position: 'relative', zIndex: 1 }}>
          <div className="d-flex align-items-center gap-2 mb-4">
            <div className="brand-logo"><i className="bi bi-book-half"></i></div>
            <div>
              <div style={{ fontWeight: 700, fontSize: 22 }}>Kitobchi</div>
              <div style={{ fontSize: 11, color: '#c7d2fe', letterSpacing: 2 }}>ADMIN PANEL</div>
            </div>
          </div>
          <h1 style={{ fontSize: 44, fontWeight: 800, lineHeight: 1.15, marginBottom: 16 }}>
            Kitob dunyosini<br/>bir joydan boshqaring
          </h1>
          <p style={{ fontSize: 16, color: '#e0e7ff', maxWidth: 420 }}>
            Buyurtmalar, kuryerlar, sotuvchilar, mijozlar, Book Club va barcha analitika — barchasi bitta professional panelda.
          </p>
        </div>
        <div style={{ position: 'relative', zIndex: 1, display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 16 }}>
          {[
            { k: '12K+', v: 'Kitoblar' },
            { k: '3.4K', v: 'Mijozlar' },
            { k: '99.8%', v: 'Uptime' },
          ].map((s) => (
            <div key={s.k} style={{ background: 'rgba(255,255,255,.08)', borderRadius: 12, padding: 16, backdropFilter: 'blur(10px)' }}>
              <div style={{ fontSize: 28, fontWeight: 800 }}>{s.k}</div>
              <div style={{ fontSize: 12, color: '#c7d2fe' }}>{s.v}</div>
            </div>
          ))}
        </div>
      </div>

      <div className="login-right">
        <div className="login-card">
          <h2 style={{ fontWeight: 700, fontSize: 28 }}>Xush kelibsiz</h2>
          <p style={{ color: '#6b7280', marginBottom: 28 }}>Boshqaruv paneliga kirish uchun hisob ma'lumotlaringizni kiriting.</p>

          <form onSubmit={submit}>
            <div className="mb-3">
              <label className="form-label fw-semibold small">Email manzil</label>
              <div className="input-group">
                <span className="input-group-text bg-white"><i className="bi bi-envelope text-muted"></i></span>
                <input type="email" className="form-control" value={email} onChange={(e) => setEmail(e.target.value)} required />
              </div>
            </div>
            <div className="mb-3">
              <label className="form-label fw-semibold small">Parol</label>
              <div className="input-group">
                <span className="input-group-text bg-white"><i className="bi bi-lock text-muted"></i></span>
                <input type="password" className="form-control" value={password} onChange={(e) => setPassword(e.target.value)} required />
              </div>
            </div>
            <div className="d-flex justify-content-between align-items-center mb-4">
              <div className="form-check">
                <input type="checkbox" className="form-check-input" id="remember" checked={remember} onChange={(e) => setRemember(e.target.checked)} />
                <label className="form-check-label small" htmlFor="remember">Eslab qolish</label>
              </div>
              <a href="#" className="small text-decoration-none" style={{ color: '#4f46e5', fontWeight: 600 }}>Parolni unutdingizmi?</a>
            </div>
            <button type="submit" className="btn btn-primary-gradient w-100 py-2 mb-3">
              <i className="bi bi-box-arrow-in-right me-2"></i>Kirish
            </button>
            <div className="text-center">
              <small className="text-muted">Kitobchi boshqaruv paneli</small>
            </div>
          </form>

          <div className="mt-4 pt-4 border-top text-center">
            <small className="text-muted">© 2026 Kitobchi Admin. Barcha huquqlar himoyalangan.</small>
          </div>
        </div>
      </div>
    </div>
  );
}
