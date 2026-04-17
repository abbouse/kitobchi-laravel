<!DOCTYPE html>
<html lang="uz" data-bs-theme="{{ session('theme','dark') }}">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Kirish — kitobchi. Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<style>
:root {
  --bg:       #0c0e14;
  --surface:  #13161f;
  --elevated: #1a1e2c;
  --border:   rgba(255,255,255,0.08);
  --text:     #e8eaf4;
  --muted:    #8b91a8;
  --hint:     #4e5470;
  --accent:   #5b87ff;
  --accent-d: rgba(91,135,255,0.14);
  --danger:   #ff5370;
  --danger-d: rgba(255,83,112,0.13);
}

*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }

body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  -webkit-font-smoothing: antialiased;
}

/* Animated background */
.bg-mesh {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
}
.bg-mesh::before {
  content: '';
  position: absolute;
  width: 800px; height: 800px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(91,135,255,.12) 0%, transparent 70%);
  top: -300px; left: -200px;
  animation: floatA 18s ease-in-out infinite;
}
.bg-mesh::after {
  content: '';
  position: absolute;
  width: 600px; height: 600px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(124,92,252,.09) 0%, transparent 70%);
  bottom: -200px; right: -100px;
  animation: floatB 22s ease-in-out infinite;
}
.bg-dot {
  position: absolute;
  width: 500px; height: 500px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(32,201,151,.06) 0%, transparent 70%);
  top: 60%; left: 60%;
  animation: floatC 16s ease-in-out infinite;
}

/* Grid pattern overlay */
.bg-grid {
  position: fixed;
  inset: 0;
  z-index: 0;
  background-image:
    linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
  background-size: 44px 44px;
}

@keyframes floatA { 0%,100%{transform:translate(0,0) scale(1)} 33%{transform:translate(60px,-40px) scale(1.05)} 66%{transform:translate(-30px,50px) scale(.98)} }
@keyframes floatB { 0%,100%{transform:translate(0,0) scale(1)} 40%{transform:translate(-50px,30px) scale(1.06)} 70%{transform:translate(40px,-20px) scale(.96)} }
@keyframes floatC { 0%,100%{transform:translate(0,0)} 50%{transform:translate(-60px,-40px)} }

/* Login card */
.login-wrap {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 420px;
  padding: 16px;
  animation: fadeUp .4s cubic-bezier(.4,0,.2,1) both;
}

.login-card {
  background: rgba(19,22,31,0.85);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 40px 36px;
  box-shadow: 0 24px 64px rgba(0,0,0,.6), 0 0 0 1px rgba(91,135,255,.05);
}

/* Brand */
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
}
.brand-icon {
  width: 44px; height: 44px;
  background: linear-gradient(135deg, #5b87ff, #7c5cfc);
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; font-weight: 700; color: #fff;
  box-shadow: 0 4px 20px rgba(91,135,255,.45), 0 0 0 4px rgba(91,135,255,.12);
  flex-shrink: 0;
}
.brand-main { font-size: 18px; font-weight: 700; color: var(--text); letter-spacing: -.3px; }
.brand-sub  { font-size: 11px; color: var(--hint); font-family: 'JetBrains Mono', monospace; margin-top: 2px; }

/* Heading */
.login-heading { margin-bottom: 28px; }
.login-title   { font-size: 22px; font-weight: 700; color: var(--text); letter-spacing: -.4px; }
.login-desc    { font-size: 13px; color: var(--muted); margin-top: 5px; }

/* Form elements */
.field { margin-bottom: 18px; }
.field-label {
  font-size: 12px;
  font-weight: 500;
  color: var(--muted);
  margin-bottom: 6px;
  display: block;
}
.field-input-wrap { position: relative; }
.field-icon {
  position: absolute;
  left: 13px; top: 50%;
  transform: translateY(-50%);
  color: var(--hint);
  font-size: 14px;
  pointer-events: none;
  transition: color .2s;
}
.field-input {
  width: 100%;
  background: rgba(255,255,255,.04);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 11px 14px 11px 38px;
  font-size: 13.5px;
  color: var(--text);
  font-family: 'Inter', sans-serif;
  outline: none;
  transition: border-color .2s, background .2s, box-shadow .2s;
}
.field-input:focus {
  border-color: var(--accent);
  background: rgba(91,135,255,.05);
  box-shadow: 0 0 0 3px rgba(91,135,255,.15);
}
.field-input:focus ~ .field-icon,
.field-input-wrap:focus-within .field-icon {
  color: var(--accent);
}
.field-input::placeholder { color: var(--hint); }
.field-input.has-error { border-color: var(--danger); }
.field-error { font-size: 11.5px; color: var(--danger); margin-top: 5px; }

/* Password toggle */
.pw-toggle {
  position: absolute;
  right: 12px; top: 50%;
  transform: translateY(-50%);
  color: var(--hint);
  font-size: 14px;
  cursor: pointer;
  padding: 4px;
  transition: color .15s;
  background: none; border: none; outline: none;
}
.pw-toggle:hover { color: var(--muted); }

.field-input.pw-field { padding-right: 40px; }

/* Remember me */
.remember-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 24px;
}
.remember-check {
  width: 16px; height: 16px;
  accent-color: var(--accent);
  cursor: pointer;
  flex-shrink: 0;
}
.remember-label {
  font-size: 13px;
  color: var(--muted);
  cursor: pointer;
  user-select: none;
}

/* Submit button */
.btn-login {
  width: 100%;
  background: linear-gradient(135deg, #5b87ff, #6b5bff);
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 600;
  font-family: 'Inter', sans-serif;
  cursor: pointer;
  transition: all .2s;
  box-shadow: 0 4px 18px rgba(91,135,255,.35);
  position: relative;
  overflow: hidden;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-login:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 24px rgba(91,135,255,.5);
  background: linear-gradient(135deg, #6b94ff, #7c6bff);
}
.btn-login:active { transform: translateY(0); }
.btn-login::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(255,255,255,.08), transparent);
}

/* Alert */
.login-alert {
  padding: 11px 14px;
  border-radius: 10px;
  font-size: 13px;
  margin-bottom: 20px;
  display: flex; align-items: flex-start; gap: 9px;
  background: var(--danger-d);
  color: var(--danger);
  border: 1px solid rgba(255,83,112,.2);
  animation: fadeUp .2s ease both;
}

/* Footer text */
.login-footer {
  margin-top: 24px;
  text-align: center;
  font-size: 11.5px;
  color: var(--hint);
  font-family: 'JetBrains Mono', monospace;
}
.login-footer a { color: var(--accent); text-decoration: none; }
.login-footer a:hover { text-decoration: underline; }

/* Decorative dots */
.dots {
  position: fixed;
  z-index: 1;
  opacity: .3;
  pointer-events: none;
}
.dots-tl { top: 40px; left: 40px; }
.dots-br { bottom: 40px; right: 40px; transform: rotate(180deg); }
.dot-grid {
  display: grid;
  grid-template-columns: repeat(6, 10px);
  gap: 8px;
}
.dot-grid span {
  width: 3px; height: 3px;
  border-radius: 50%;
  background: var(--muted);
  display: block;
}

/* Animation */
@keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
</style>
</head>
<body>

{{-- Background --}}
<div class="bg-grid"></div>
<div class="bg-mesh"><div class="bg-dot"></div></div>

{{-- Decorative dots --}}
<div class="dots dots-tl">
  <div class="dot-grid">
    @for($i = 0; $i < 30; $i++)<span></span>@endfor
  </div>
</div>
<div class="dots dots-br">
  <div class="dot-grid">
    @for($i = 0; $i < 30; $i++)<span></span>@endfor
  </div>
</div>

{{-- Login card --}}
<div class="login-wrap">
  <div class="login-card">

    {{-- Brand --}}
    <div class="brand">
      <div class="brand-icon">K</div>
      <div>
        <div class="brand-main">kitobchi.</div>
        <div class="brand-sub">Admin boshqaruv tizimi</div>
      </div>
    </div>

    {{-- Heading --}}
    <div class="login-heading">
      <h1 class="login-title">Xush kelibsiz 👋</h1>
      <p class="login-desc">Tizimga kirish uchun ma'lumotlaringizni kiriting</p>
    </div>

    {{-- Error alert --}}
    @if(session('error'))
    <div class="login-alert">
      <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;font-size:15px;margin-top:1px"></i>
      <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('panel.login.post') }}">
      @csrf

      <div class="field">
        <label class="field-label" for="email">Email manzil</label>
        <div class="field-input-wrap">
          <i class="bi bi-envelope field-icon"></i>
          <input
            type="email" id="email" name="email"
            class="field-input {{ $errors->has('email') ? 'has-error' : '' }}"
            value="{{ old('email') }}"
            placeholder="admin@example.com"
            required autofocus autocomplete="email"
          />
        </div>
        @error('email')
          <div class="field-error"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
        @enderror
      </div>

      <div class="field">
        <label class="field-label" for="password">Parol</label>
        <div class="field-input-wrap">
          <i class="bi bi-lock field-icon"></i>
          <input
            type="password" id="password" name="password"
            class="field-input pw-field"
            placeholder="••••••••"
            required autocomplete="current-password"
          />
          <button type="button" class="pw-toggle" id="pwToggle" aria-label="Ko'rsatish">
            <i class="bi bi-eye" id="pwIcon"></i>
          </button>
        </div>
      </div>

      <div class="remember-row">
        <input type="checkbox" name="remember" id="remember" class="remember-check">
        <label for="remember" class="remember-label">Meni eslab qol</label>
      </div>

      <button type="submit" class="btn-login">
        <i class="bi bi-box-arrow-in-right"></i>
        Tizimga kirish
      </button>
    </form>

    <div class="login-footer">
      kitobchi.uz · Admin Panel &nbsp;·&nbsp;
      <a href="{{ route('panel.theme') }}" onclick="event.preventDefault();document.getElementById('themeForm').submit()">
        <i class="bi bi-circle-half"></i> Rejim
      </a>
    </div>

  </div>
</div>

{{-- Hidden theme form --}}
<form id="themeForm" method="POST" action="{{ route('panel.theme') }}" style="display:none">
  @csrf
  <input type="hidden" name="theme" value="{{ session('theme','dark') === 'dark' ? 'light' : 'dark' }}">
</form>

<script>
// Password visibility toggle
const pwToggle = document.getElementById('pwToggle');
const pwInput  = document.getElementById('password');
const pwIcon   = document.getElementById('pwIcon');
pwToggle?.addEventListener('click', () => {
  const show = pwInput.type === 'password';
  pwInput.type = show ? 'text' : 'password';
  pwIcon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
});
</script>
</body>
</html>
