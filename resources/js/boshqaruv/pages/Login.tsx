import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { applyTheme } from '../Layout';

/**
 * Kirish sahifasi — Axelit "sign_in" sahifasining tuzilmasi:
 * lavanda fon, markazda oq konteyner; chapda brend bloki, o'ngda shakl.
 */
export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(true);
  const [darkMode, setDarkMode] = useState(() => {
    if (typeof window === 'undefined') return false;
    try { return localStorage.getItem('boshqaruv-theme') === 'dark'; } catch { return false; }
  });

  useEffect(() => { applyTheme(darkMode); }, [darkMode]);

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    router.post('/boshqaruv/login', { email, password, remember });
  };

  const points = [
    { icon: 'bi-shield-check', tone: 'primary', title: 'Audit', text: 'Har bir kirish qayd etiladi' },
    { icon: 'bi-person-lock', tone: 'success', title: 'Ruxsat', text: 'Faqat tasdiqlangan adminlar' },
    { icon: 'bi-clock-history', tone: 'danger', title: 'Sessiya', text: 'Faollik muntazam tekshiriladi' },
  ];

  return (
    <div className="sign-in-bg">
      <div className="app-wrapper d-block">
        <div className="main-container">
          <div className="container">
            <div className="row sign-in-content-bg">
              <div className="col-lg-6 image-contentbox d-none d-lg-block">
                <div className="form-container">
                  <div className="signup-content mt-4">
                    <div className="kc-login-brand">
                      <img src="/favicon.svg" alt="" width={52} height={52} />
                      <strong>Kitobchi</strong>
                    </div>
                  </div>
                  <div className="kc-login-points">
                    {points.map((p) => (
                      <div className="kc-login-point" key={p.title}>
                        <span className={`h-45 w-45 d-flex-center b-r-50 text-light-${p.tone} flex-shrink-0 f-s-20`}><i className={`bi ${p.icon}`}></i></span>
                        <div>
                          <h6>{p.title}</h6>
                          <p>{p.text}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                  <p className="text-center text-secondary f-s-13 mb-0">© {new Date().getFullYear()} Kitobchi. Ichki foydalanish uchun.</p>
                </div>
              </div>

              <div className="col-lg-6 form-contentbox">
                <button
                  type="button"
                  className="btn btn-light-secondary icon-btn w-35 h-35 b-r-22 kc-login-theme"
                  onClick={() => setDarkMode((value) => !value)}
                  aria-label={darkMode ? "Yorug' rejim" : "Qorong'i rejim"}
                  title={darkMode ? "Yorug' rejim" : "Qorong'i rejim"}
                >
                  <i className={`${darkMode ? 'iconoir-sun-light' : 'iconoir-half-moon'} f-s-18`}></i>
                </button>
                <div className="form-container">
                  <form className="app-form" onSubmit={submit}>
                    <div className="row">
                      <div className="col-12">
                        <div className="mb-5 text-center text-lg-start">
                          <div className="kc-login-brand justify-content-center justify-content-lg-start d-lg-none mb-4">
                            <img src="/favicon.svg" alt="" width={44} height={44} />
                            <strong>Kitobchi</strong>
                          </div>
                          <h2 className="text-primary f-w-600">Boshqaruvga xush kelibsiz!</h2>
                          <p>Ruxsatli xodimlar uchun kirish. Hisob ma'lumotlaringizni kiriting.</p>
                        </div>
                      </div>
                      <div className="col-12">
                        <div className="mb-3">
                          <label htmlFor="login-email" className="form-label">Email manzil</label>
                          <input
                            id="login-email"
                            type="email"
                            className="form-control"
                            placeholder="admin@kitobchi.uz"
                            autoComplete="username"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            required
                          />
                        </div>
                      </div>
                      <div className="col-12">
                        <div className="mb-3">
                          <label htmlFor="login-password" className="form-label">Parol</label>
                          <a href="#" className="link-primary float-end" onClick={(e) => e.preventDefault()}>Parolni unutdingizmi?</a>
                          <input
                            id="login-password"
                            type="password"
                            className="form-control"
                            placeholder="Parolingizni kiriting"
                            autoComplete="current-password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            required
                          />
                        </div>
                      </div>
                      <div className="col-12">
                        <div className="form-check mb-3">
                          <input className="form-check-input" type="checkbox" id="remember" checked={remember} onChange={(e) => setRemember(e.target.checked)} />
                          <label className="form-check-label text-secondary" htmlFor="remember">Eslab qolish</label>
                        </div>
                      </div>
                      <div className="col-12">
                        <div className="mb-3">
                          <button type="submit" className="btn btn-primary w-100">Kirish</button>
                        </div>
                      </div>
                      <div className="col-12">
                        <div className="text-center text-lg-start text-secondary">
                          Ruxsatsiz kirish taqiqlanadi. Har bir urinish qayd etiladi.
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
