<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<title>@yield('title', 'Kitobchi ID')</title>
<style>
:root{--bg:#F3F6FA;--card:#fff;--text:#0B1B33;--muted:#5B6B82;--line:#E1E8F1;--brand:#1E6FD4;--brand-2:#62B0FF;--danger:#C62E2E;--danger-bg:#FDECEC;--ok-bg:#EAF3FF}
@media (prefers-color-scheme:dark){:root{--bg:#0B1220;--card:#121B2C;--text:#EAF0F8;--muted:#98A6BB;--line:#22304A;--danger-bg:#3A1A1D;--ok-bg:#14243D}}
*{box-sizing:border-box}
html,body{margin:0;background:var(--bg);color:var(--text);font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif}
.wrap{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px 16px}
.brand{display:flex;justify-content:center;margin-bottom:20px;color:var(--text)}
.brand svg{height:40px;width:auto}
.card{width:100%;max-width:420px;background:var(--card);border:1px solid var(--line);border-radius:20px;padding:28px 24px;box-shadow:0 10px 30px rgba(11,27,51,.06)}
h1{font-size:22px;line-height:1.3;margin:0 0 6px}
p{margin:0 0 14px;color:var(--muted)}
.app{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--line);border-radius:14px;margin:0 0 18px}
.app img,.app .ph{width:44px;height:44px;border-radius:12px;object-fit:cover;flex:none;background:var(--ok-bg);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--brand)}
.app b{display:block;color:var(--text)}
.app small{color:var(--muted);word-break:break-all}
label{display:block;font-size:14px;font-weight:600;margin:0 0 6px}
input[type=tel],input[type=text]{width:100%;font-size:18px;padding:13px 14px;border:1.5px solid var(--line);border-radius:12px;background:transparent;color:var(--text);outline:none}
input:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(30,111,212,.15)}
input.code{letter-spacing:.5em;text-align:center;font-weight:700;font-size:22px}
.btn{display:block;width:100%;border:0;border-radius:12px;padding:14px 16px;font-size:16px;font-weight:700;cursor:pointer;margin-top:14px;text-align:center;text-decoration:none}
.btn-primary{background:var(--brand);color:#fff}
.btn-primary:hover{filter:brightness(1.05)}
.btn-ghost{background:transparent;color:var(--text);border:1.5px solid var(--line)}
.btn-link{background:none;border:0;color:var(--brand);font-weight:600;cursor:pointer;padding:0;font-size:14px;white-space:nowrap}
p a{color:var(--brand)}
.account span{min-width:0}
.btn-link[disabled]{color:var(--muted);cursor:default}
.err{background:var(--danger-bg);color:var(--danger);border-radius:12px;padding:10px 12px;font-size:14px;margin:0 0 14px}
.scopes{list-style:none;padding:0;margin:0 0 6px}
.scopes li{display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--line);font-size:15px}
.scopes li:last-child{border-bottom:0}
.scopes svg{flex:none;margin-top:3px;color:var(--brand)}
.account{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:14px;color:var(--muted);margin:0 0 16px}
.row{display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:14px;color:var(--muted)}
.foot{margin-top:18px;font-size:12px;color:var(--muted);text-align:center;max-width:420px}
.foot a{color:var(--muted)}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.grid2 .btn{margin-top:0}
</style>
</head>
<body>
<div class="wrap">
    <div class="brand">@include('kitobchi-id._logo', ['gid' => 'hdr'])</div>
    <main class="card">
        @yield('content')
    </main>
    <div class="foot">
        Kitobchi ID — Kitobchi akkauntingiz bilan boshqa ilovalarga xavfsiz kirish.
        <a href="{{ url('/legal/maxfiylik-siyosati') }}" target="_blank" rel="noopener">Maxfiylik siyosati</a>
    </div>
</div>
</body>
</html>
