@extends('a122.layouts.guest')

@section('title', 'Kirish')

@section('content')
<section class="a122-login-shell">
  <div class="a122-login-grid">
    <div class="a122-login-brand">
      <div class="a122-login-brand__halo a122-login-brand__halo--mint"></div>
      <div class="a122-login-brand__halo a122-login-brand__halo--gold"></div>

      <a href="{{ url('/') }}" class="a122-login-brand__back">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Saytga qaytish
      </a>

      <div class="a122-login-brand__content">
        <div class="a122-login-badge">
          <span class="a122-login-badge__dot"></span>
          A122 operational workspace
        </div>
        <h1 class="a122-login-brand__title">Kitobchi boshqaruv markazi</h1>
        <p class="a122-login-brand__desc">Buyurtmalar, sellerlar, support va moliyaviy oqimlarni bitta tinch, aniq va nazoratga qulay ish muhitida boshqaring.</p>

        <div class="a122-login-brand__stats">
          <div class="a122-login-stat">
            <div class="a122-login-stat__icon">
              <i data-lucide="shopping-bag" class="w-4 h-4"></i>
            </div>
            <div class="a122-login-stat__value">Buyurtmalar nazorati</div>
            <div class="a122-login-stat__label">to‘lov, yetkazish va holat o‘zgarishlari bir qarashda ko‘rinadi</div>
          </div>
          <div class="a122-login-stat">
            <div class="a122-login-stat__icon">
              <i data-lucide="store" class="w-4 h-4"></i>
            </div>
            <div class="a122-login-stat__value">Seller va filiallar</div>
            <div class="a122-login-stat__label">katalog, filial va ichki operatsiya bir xil ritmda boshqariladi</div>
          </div>
          <div class="a122-login-stat">
            <div class="a122-login-stat__icon">
              <i data-lucide="messages-square" class="w-4 h-4"></i>
            </div>
            <div class="a122-login-stat__value">Support va moderatsiya</div>
            <div class="a122-login-stat__label">mijoz bilan aloqa, shikoyat va community oqimi bir joyda jamlanadi</div>
          </div>
        </div>

        <div class="a122-login-brand__notice">
          <div class="a122-login-brand__notice-title">Ichki boshqaruv paneli</div>
          <p>Faqat vakolatli administratorlar uchun. Kirishdan keyin dashboard, moliya, book club va operatsion oqimlar yagona ish maydonida ochiladi.</p>
        </div>
      </div>
    </div>

    <div class="a122-login-card-wrap">
      <div class="a122-login-copy">
        <div class="kc-auth-titlebar">
          <h2 class="a122-login-title">Tizimga kirish</h2>
          <button data-theme-toggle class="topbar-theme-btn" aria-label="Tema almashtirish" title="Light / Dark mode">
            <span class="theme-icon-light"><i data-lucide="sun-medium" class="w-4.5 h-4.5"></i></span>
            <span class="theme-icon-dark"><i data-lucide="moon-star" class="w-4.5 h-4.5"></i></span>
          </button>
        </div>
        <p class="a122-login-sub">A122 paneliga kirish uchun ishchi email va parolni kiriting. Sessiya xavfsiz tarzda faqat ichki boshqaruv oqimlari uchun ochiladi.</p>
      </div>

      @if(session('error'))
        <div class="p-alert danger">
          <i class="bi bi-exclamation-circle"></i>
          <span>{{ session('error') }}</span>
        </div>
      @endif

      @if(session('success'))
        <div class="p-alert success">
          <i class="bi bi-check-circle"></i>
          <span>{{ session('success') }}</span>
        </div>
      @endif

      <form method="POST" action="{{ route('admin.login.post') }}" class="a122-login-form">
        @csrf
        <div>
          <label for="email" class="p-form-label">Email</label>
          <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="admin@example.com" class="p-form-control @error('email') is-invalid @enderror">
          @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div x-data="{ showPassword: false }">
          <label for="password" class="p-form-label">Parol</label>
          <div class="position-relative">
            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password" placeholder="Parolingizni kiriting" class="p-form-control pr-11">
            <button type="button" @click="showPassword = !showPassword" class="a122-password-toggle" aria-label="Parolni ko‘rsatish">
              <i x-show="!showPassword" data-lucide="eye" class="w-4 h-4"></i>
              <i x-show="showPassword" data-lucide="eye-off" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        <label class="a122-login-check">
          <input type="checkbox" name="remember" value="1" class="rounded">
          <span>Meni eslab qol</span>
        </label>

        <button type="submit" class="btn-p primary w-full">
          <i class="bi bi-box-arrow-in-right"></i>
          Tizimga kirish
        </button>
      </form>

      <div class="a122-login-footer">
        <span>© {{ date('Y') }} Kitobchi ecosystems</span>
        <span>Operational access only</span>
      </div>
    </div>
  </div>
</section>
@endsection
