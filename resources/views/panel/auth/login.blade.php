<!DOCTYPE html>
<html lang="uz" data-bs-theme="{{ session('theme','dark') }}">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Kirish — AdminPanel</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<style>
[data-bs-theme="dark"] {
  --bg: #0f1117; --surface: #181c27; --border: rgba(255,255,255,0.07);
  --text: #eef0f7; --muted: #8b91a8; --hint: #555c75;
  --accent: #4f7cff; --accent-d: rgba(79,124,255,0.12);
  --danger: #ff5c6a; --danger-d: rgba(255,92,106,0.12);
}
[data-bs-theme="light"] {
  --bg: #f4f6fb; --surface: #ffffff; --border: rgba(0,0,0,0.08);
  --text: #1a1d2e; --muted: #6b7280; --hint: #9ca3af;
  --accent: #4f7cff; --accent-d: rgba(79,124,255,0.10);
  --danger: #dc2626; --danger-d: rgba(220,38,38,0.10);
}
body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
.login-card {
  width: 100%;
  max-width: 400px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 36px 32px;
}
.brand {
  display: flex; align-items: center; gap: 10px; margin-bottom: 28px;
}
.brand-icon {
  width: 40px; height: 40px;
  background: var(--accent); border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; font-weight: 700; color: #fff;
  box-shadow: 0 0 20px rgba(79,124,255,.3);
}
.brand-name { font-size: 16px; font-weight: 600; color: var(--text); }
.brand-sub  { font-size: 11px; color: var(--hint); font-family: 'DM Mono',monospace; }

.form-label-p { font-size: 12px; font-weight: 500; color: var(--muted); margin-bottom: 5px; display: block; }
.form-control-p {
  width: 100%;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  outline: none;
  transition: border-color .2s;
}
.form-control-p:focus { border-color: var(--accent); }
.form-control-p::placeholder { color: var(--hint); }

.btn-login {
  width: 100%;
  background: var(--accent);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 11px;
  font-size: 14px;
  font-weight: 600;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer;
  transition: background .15s;
  margin-top: 4px;
}
.btn-login:hover { background: #6690ff; }

.alert-p {
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 13px;
  margin-bottom: 16px;
  display: flex; align-items: center; gap: 8px;
  background: var(--danger-d);
  color: var(--danger);
  border: 1px solid rgba(255,92,106,.2);
}
</style>
</head>
<body>
<div class="login-card">
  {{-- Brand --}}
  <div class="brand">
    <div class="brand-icon">K</div>
    <div>
      <div class="brand-name">KitobPanel</div>
      <div class="brand-sub">Admin boshqaruv tizimi</div>
    </div>
  </div>

  {{-- Error --}}
  @if(session('error'))
  <div class="alert-p">
    <i class="bi bi-x-circle-fill"></i> {{ session('error') }}
  </div>
  @endif

  {{-- Form --}}
  <form method="POST" action="{{ route('panel.login.post') }}">
    @csrf
    <div style="margin-bottom:16px">
      <label class="form-label-p">Email manzil</label>
      <input type="email" name="email" class="form-control-p @error('email') border-danger @enderror"
             value="{{ old('email') }}" placeholder="admin@example.com" required autofocus>
      @error('email')<div style="font-size:11px;color:var(--danger);margin-top:4px">{{ $message }}</div>@enderror
    </div>

    <div style="margin-bottom:20px">
      <label class="form-label-p">Parol</label>
      <input type="password" name="password" class="form-control-p"
             placeholder="••••••••" required>
    </div>

    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
      <input type="checkbox" name="remember" id="remember" style="accent-color:var(--accent)">
      <label for="remember" style="font-size:13px;color:var(--muted);cursor:pointer">Meni eslab qol</label>
    </div>

    <button type="submit" class="btn-login">
      <i class="bi bi-box-arrow-in-right me-1"></i> Kirish
    </button>
  </form>

  <div style="margin-top:20px;text-align:center;font-size:12px;color:var(--hint)">
    KitobPanel · Admin tizimi
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>