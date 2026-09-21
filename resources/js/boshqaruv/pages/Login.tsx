import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(true);
  const [darkMode, setDarkMode] = useState(() => {
    if (typeof window === 'undefined') return false;
    return localStorage.getItem('boshqaruv-theme') === 'dark';
  });

  useEffect(() => {
    document.body.classList.toggle('boshqaruv-dark', darkMode);
    document.documentElement.setAttribute('data-bs-theme', darkMode ? 'dark' : 'light');
    localStorage.setItem('boshqaruv-theme', darkMode ? 'dark' : 'light');
  }, [darkMode]);

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    router.post('/boshqaruv/login', { email, password, remember });
  };

  return (
    <div className="login-wrap">
      <div className="login-left">
        <div className="login-brand-block">
          <img src="/images/logo/logo_white.png" alt="Kitobchi" className="login-brand-logo" />
          <span>Ichki boshqaruv muhiti</span>
          <h1>Ruxsatli xodimlar uchun kirish</h1>
          <p>
            Bu sahifa faqat Kitobchi administratsiyasi uchun. Kirishlar nazorat qilinadi va sessiya xavfsizligi tekshiriladi.
          </p>
        </div>
        <div className="login-security-grid">
          {[
            { icon: 'bi-shield-check', title: 'Audit', text: 'Har bir kirish qayd etiladi' },
            { icon: 'bi-person-lock', title: 'Ruxsat', text: 'Faqat tasdiqlangan adminlar' },
            { icon: 'bi-clock-history', title: 'Sessiya', text: 'Faollik muntazam tekshiriladi' },
          ].map((s) => (
            <div className="login-security-card" key={s.title}>
              <i className={`bi ${s.icon}`}></i>
              <strong>{s.title}</strong>
              <span>{s.text}</span>
            </div>
          ))}
        </div>
      </div>

      <div className="login-right">
        <div className="login-card">
          <div className="login-card-top">
            <img src={darkMode ? '/images/logo/logo_white.png' : '/images/logo/logo_blue.png'} alt="Kitobchi" className="login-card-logo" />
            <button type="button" className="login-theme-btn" onClick={() => setDarkMode((value) => !value)} aria-label={darkMode ? 'Light mode' : 'Dark mode'}>
              <i className={`bi ${darkMode ? 'bi-sun' : 'bi-moon'}`}></i>
            </button>
          </div>

          <h2>Kirish</h2>
          <p className="login-muted">Hisob ma'lumotlaringizni kiriting.</p>

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
              <a href="#" className="small text-decoration-none" style={{ color: 'var(--kc-ink)', fontWeight: 600 }}>Parolni unutdingizmi?</a>
            </div>
            <button type="submit" className="btn btn-primary-gradient w-100 py-2 mb-3">
              <i className="bi bi-box-arrow-in-right me-2"></i>Kirish
            </button>
            <div className="text-center">
              <small className="text-muted">Ruxsatsiz kirish taqiqlanadi.</small>
            </div>
          </form>

          <div className="mt-4 pt-4 border-top text-center">
            <small className="text-muted">© 2026 Kitobchi. Ichki foydalanish uchun.</small>
          </div>
        </div>
      </div>
    </div>
  );
}
