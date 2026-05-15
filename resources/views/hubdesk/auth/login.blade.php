@extends('hubdesk.layouts.hub')
@section('title', 'Kirish')
@section('hide-topbar', true)

@section('content')
<div class="hd-login-screen">
  <div class="hd-login-card">
    <div class="hd-login-brand">
      <span class="hd-brand-mark">K</span>
      <div>
        <div style="font-weight:700;font-size:16px;letter-spacing:-0.01em;">Kitobchi</div>
        <div style="font-size:12px;color:var(--hd-ink-faint);">Hub Desk terminali</div>
      </div>
    </div>

    <div class="hd-login-eyebrow">Hub xodimi kirishi</div>
    <h1 class="hd-login-title">Smenaga kirish</h1>
    <p class="hd-login-sub">
      Ushbu terminal — USB printer ulangan ish joyi. Bu yerda label va chekni
      brauzerdan to‘g‘ridan‑to‘g‘ri chop etasiz, buyurtmalar bo‘yicha holatni
      tezda ko‘rasiz.
    </p>

    @if($errors->any())
      <div class="hd-alert hd-alert--danger" role="alert" style="margin-bottom:14px;">
        <span aria-hidden="true">⚠</span>
        <span>{{ $errors->first() }}</span>
      </div>
    @endif

    <form method="POST" action="{{ route('hubdesk.login.post') }}" class="hd-login-form" autocomplete="on">
      @csrf
      <div class="hd-field">
        <label for="hd-username">Login</label>
        <input id="hd-username" class="hd-input" type="text" name="username"
               value="{{ old('username') }}" required autofocus
               autocapitalize="off" autocomplete="username"
               inputmode="text" spellcheck="false"
               placeholder="hub.staff">
      </div>
      <div class="hd-field">
        <label for="hd-password">Parol</label>
        <input id="hd-password" class="hd-input" type="password" name="password"
               required autocomplete="current-password"
               placeholder="••••••••">
      </div>
      <button type="submit" class="hd-btn hd-btn--primary hd-btn--xl hd-btn--block">
        Hub deskga kirish
      </button>
    </form>

    <div class="hd-login-footer">
      Login ma‘lumotlari yo‘qotilsa, hub menejeri yoki ofisdan yangi parol so‘rang.
      <br>Brauzer yopilsa — sessiya muddati cheklangan, qaytadan kirish kerak.
    </div>
  </div>
</div>
@endsection
