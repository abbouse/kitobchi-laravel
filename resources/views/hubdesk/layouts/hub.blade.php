<!DOCTYPE html>
<html lang="uz">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <meta name="theme-color" content="#faf3ec" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Hub Desk') — Kitobchi Hub</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">

  {{-- Hub-desk uses its own minimal stylesheet on top of Tailwind base. --}}
  @vite(['resources/css/app.css', 'resources/css/kitobchi-hubdesk.css'])
  @stack('head')
</head>
<body class="hd-body antialiased">
  <div class="hd-shell hd-root">
    @hasSection('hide-topbar')
      {{-- skip topbar (used by login screen) --}}
    @else
      <header class="hd-topbar">
        <a href="{{ Auth::guard('hub_web')->check() ? route('hubdesk.index') : route('hubdesk.login') }}" class="hd-brand">
          <span class="hd-brand-mark">K</span>
          <span>Kitobchi</span>
          <span class="hd-brand-tag">Hub Desk</span>
        </a>

        @auth('hub_web')
          @php $staff = Auth::guard('hub_web')->user(); @endphp
          <div class="hd-top-meta">
            <div class="hd-top-user">
              <b>{{ $staff?->full_name ?? 'Hub xodimi' }}</b>
              <span>{{ $staff?->hub?->name ?? '—' }} · {{ $staff?->role ?? 'staff' }}</span>
            </div>
            <form method="POST" action="{{ route('hubdesk.logout') }}">
              @csrf
              <button type="submit" class="hd-btn hd-btn--ghost hd-btn--sm" title="Chiqish">
                Chiqish
              </button>
            </form>
          </div>
        @endauth
      </header>
    @endif

    <main class="hd-main">
      <div class="hd-container">
        @yield('content')
      </div>
    </main>
  </div>

  @stack('scripts')
</body>
</html>
