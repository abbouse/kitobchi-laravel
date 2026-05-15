@php
  $panelAdmin = auth('panel')->user();
  $quickLinks = [
    ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['label' => 'Foydalanuvchilar', 'href' => route('admin.users.index')],
    ['label' => 'Kitoblar', 'href' => route('admin.books.index')],
    ['label' => 'Kanstovar', 'href' => route('admin.stationery.index')],
    ['label' => 'Sotuvchilar', 'href' => route('admin.sellers.index')],
    ['label' => 'Buyurtmalar', 'href' => route('admin.orders.index')],
    ['label' => 'Support', 'href' => route('admin.support.index')],
    ['label' => 'Sozlamalar', 'href' => route('admin.settings.index')],
  ];
@endphp

<header class="kc-topbar">
  <div class="container-fluid px-3 px-lg-4 px-xxl-5">
    <div class="kc-topbar__inner d-flex align-items-center gap-3">
      <button
        type="button"
        data-sidebar-toggle
        aria-expanded="true"
        class="btn btn-white shadow-sm border kc-topbar__menu"
        aria-label="Menyu">
        <i class="bi bi-list fs-5"></i>
      </button>

      <div class="flex-grow-1 min-w-0">
        <div class="kc-topbar__eyebrow">@yield('page-eyebrow', 'A122 control room')</div>
        <div class="kc-topbar__title text-truncate">@yield('page-title', 'Dashboard')</div>
      </div>

      <div class="kc-topbar__search d-none d-lg-block w-100">
        <form class="kc-search" onsubmit="event.preventDefault();const input=this.querySelector('input');const option=[...document.querySelectorAll('#a122-quick-nav-list option')].find(o=>o.value===input.value);if(option?.dataset?.href){window.location=option.dataset.href;}">
          <i class="bi bi-search kc-search__icon"></i>
          <input type="text" list="a122-quick-nav-list" class="form-control" placeholder="Bo‘lim, sahifa yoki oqim qidiring..." />
          <datalist id="a122-quick-nav-list">
            @foreach ($quickLinks as $link)
              <option value="{{ $link['label'] }}" data-href="{{ $link['href'] }}"></option>
            @endforeach
          </datalist>
        </form>
      </div>

      <button data-theme-toggle class="btn btn-white shadow-sm border kc-topbar__action d-none d-sm-inline-flex align-items-center px-3" aria-label="Tema almashtirish">
        <i class="bi bi-circle-half me-2"></i>
        <span class="small fw-semibold">Theme</span>
      </button>

      <div class="dropdown">
        <button
          class="btn kc-topbar__user dropdown-toggle d-inline-flex align-items-center gap-2"
          type="button"
          data-bs-toggle="dropdown"
          aria-expanded="false">
          @include('a122.partials.avatar', [
            'name' => $panelAdmin?->name ?? 'Admin',
            'image' => $panelAdmin?->avatar,
            'class' => 'kc-topbar__user-avatar',
          ])
          <span class="text-start d-none d-md-inline-block">
            <span class="d-block fw-semibold text-dark">{{ $panelAdmin?->name ?? 'Admin' }}</span>
            <span class="d-block small text-secondary">{{ $panelAdmin?->role_label ?? 'Panel user' }}</span>
          </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2">
          <li class="px-2 py-2 border-bottom">
            <div class="fw-semibold">{{ $panelAdmin?->name ?? 'Admin' }}</div>
            <div class="small text-secondary">{{ $panelAdmin?->email ?? 'email yo‘q' }}</div>
          </li>
          @if($panelAdmin)
            <li>
              <a href="{{ route('admin.admins.edit', $panelAdmin) }}" class="dropdown-item rounded-3 py-2">
                <i class="bi bi-person-gear me-2"></i>
                Admin sozlamalari
              </a>
            </li>
          @endif
          <li><hr class="dropdown-divider my-2"></li>
          <li>
            <form method="POST" action="{{ route('admin.logout') }}">
              @csrf
              <button type="submit" class="dropdown-item rounded-3 py-2 text-danger">
                <i class="bi bi-box-arrow-right me-2"></i>
                Logout
              </button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </div>
</header>
