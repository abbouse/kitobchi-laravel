<header class="sticky top-0 z-30 topbar-shell">
  @php($panelAdmin = auth('panel')->user())
  <div class="flex items-center gap-3 px-4 sm:px-6 lg:px-8 min-h-[4.5rem]">
    <button
      type="button"
      data-sidebar-toggle
      aria-expanded="true"
      class="topbar-icon-btn -ml-2"
      aria-label="Menyu">
      <i data-lucide="menu" class="w-5 h-5"></i>
    </button>

    <div class="min-w-0">
      <div class="topbar-subtitle hidden sm:block">A122 marketplace admin</div>
      <h1 class="topbar-title">@yield('page-title', 'Dashboard')</h1>
    </div>

    <div class="hidden md:flex flex-1 max-w-md mx-4">
      <div class="relative w-full topbar-search">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input type="text" placeholder="Modul, user, buyurtma yoki ID qidiring..." />
      </div>
    </div>

    <div class="ml-auto flex items-center justify-end gap-3">
      <button data-theme-toggle class="topbar-theme-btn" aria-label="Tema almashtirish" title="Light / Dark mode">
        <span class="theme-icon-light">
          <i data-lucide="sun-medium" class="w-4.5 h-4.5"></i>
        </span>
        <span class="theme-icon-dark">
          <i data-lucide="moon-star" class="w-4.5 h-4.5"></i>
        </span>
        <span class="theme-label">Theme</span>
      </button>

      <div class="relative" x-data="{ open: false }" @click.away="open = false">
        <button type="button" @click="open = !open" class="topbar-user topbar-user--button" :aria-expanded="open.toString()" aria-haspopup="true">
          @include('a122.partials.avatar', [
            'name' => $panelAdmin?->name ?? 'Admin',
            'image' => $panelAdmin?->avatar,
            'class' => 'topbar-user__avatar',
          ])
          <div class="topbar-user__meta">
            <div class="topbar-user__name">{{ $panelAdmin?->name ?? 'Admin' }}</div>
            <div class="topbar-user__role">{{ $panelAdmin?->role_label ?? 'Panel user' }}</div>
          </div>
          <i data-lucide="chevron-down" class="w-4 h-4 text-[var(--p-hint)] hidden sm:block"></i>
        </button>

        <div x-show="open" x-transition.origin.top.right x-cloak class="topbar-dropdown">
          <div class="topbar-dropdown__head">
            <div class="topbar-dropdown__name">{{ $panelAdmin?->name ?? 'Admin' }}</div>
            <div class="topbar-dropdown__email">{{ $panelAdmin?->email ?? 'email yo‘q' }}</div>
          </div>

          @if($panelAdmin)
            <a href="{{ route('admin.admins.edit', $panelAdmin) }}" class="topbar-dropdown__item" @click="open = false">
              <i data-lucide="user-cog" class="w-4 h-4"></i>
              Admin sozlamalari
            </a>
          @endif

          <form method="POST" action="{{ route('admin.logout') }}" class="pt-2 mt-2 border-t border-[var(--p-border)]">
            @csrf
            <button type="submit" class="topbar-dropdown__item topbar-dropdown__item--danger">
              <i data-lucide="log-out" class="w-4 h-4"></i>
              Logout
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</header>
