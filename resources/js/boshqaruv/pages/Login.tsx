import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { applyTheme } from '../Layout';
import { tiIcon } from '../utils/icons';

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
    { icon: 'ti-shield-check', tone: 'primary', title: 'Audit', text: 'Har bir kirish qayd etiladi' },
    { icon: 'ti-lock-access', tone: 'success', title: 'Ruxsat', text: 'Faqat tasdiqlangan adminlar' },
    { icon: 'ti-history', tone: 'danger', title: 'Sessiya', text: 'Faollik muntazam tekshiriladi' },
  ];

  return (
    <div className="sign-in-bg">
      <div className="app-wrapper d-block">
        <div className="main-container">
          <div className="container">
            <div className="row sign-in-content-bg">
              <div className="col-lg-6 image-contentbox d-none d-lg-block">
                <div className="form-container h-100 d-flex flex-column justify-content-between p-4">
                  <div className="signup-content mt-4">
                    <div className="d-flex align-items-center justify-content-center gap-3">
                      <img className="h-50 w-50 b-r-15" src="/favicon.svg" alt="" />
                      <span className="f-s-28 f-w-700 text-dark">Kitobchi</span>
                    </div>
                  </div>
                  <div className="d-grid gap-3 py-5 px-lg-4">
                    {points.map((p) => (
                      <div className="card card-body mb-0 d-flex flex-row align-items-center gap-3" key={p.title}>
                        <span className={`h-45 w-45 d-flex-center b-r-50 text-light-${p.tone} flex-shrink-0 f-s-20`}><i className={`${tiIcon(p.icon)}`}></i></span>
                        <div>
                          <h6 className="mb-0">{p.title}</h6>
                          <p className="mb-0 text-secondary f-s-13">{p.text}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                  <p className="text-center text-secondary f-s-13 mb-0">© {new Date().getFullYear()} Kitobchi. Ichki foydalanish uchun.</p>
                </div>
              </div>

              <div className="col-lg-6 form-contentbox position-relative">
                <button
                  type="button"
                  className="btn btn-light-secondary icon-btn w-35 h-35 b-r-22 position-absolute top-0 end-0 m-3"
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
                          <div className="d-flex align-items-center gap-3 justify-content-center justify-content-lg-start d-lg-none mb-4">
                            <img className="h-45 w-45 b-r-10" src="/favicon.svg" alt="" />
                            <span className="f-s-24 f-w-700 text-dark">Kitobchi</span>
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
