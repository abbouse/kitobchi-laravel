@extends('a122.layouts.admin')
@section('title', 'Foydalanuvchilar')
@section('page-title', 'Foydalanuvchilar')
@section('page-eyebrow', 'User operations')

@section('content')
@php
  $tabs = [
    'all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
    'buyers' => ['label' => 'Xarid qilganlar', 'count' => $counts['buyers'] ?? 0],
    'with_cards' => ['label' => 'Karta ulanganlar', 'count' => $counts['with_cards'] ?? 0],
    'pending' => ['label' => 'Kutilmoqda', 'count' => $counts['pending'] ?? 0],
    'active' => ['label' => 'Faol', 'count' => $counts['active'] ?? 0],
    'premium' => ['label' => 'Premium', 'count' => $counts['premium'] ?? 0],
    'blocked' => ['label' => 'Bloklangan', 'count' => $counts['blocked'] ?? 0],
  ];
@endphp

<div class="d-flex flex-column gap-4">
  <x-admin.page-header
    eyebrow="User operations"
    title="Foydalanuvchilar"
    subtitle="{{ $users->total() }} ta foydalanuvchi yozuvi topildi. Verifikatsiya, premium va access boshqaruvi shu sahifadan boshqariladi.">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input
        type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="Ism, email, telefon yoki rol..."
        class="form-control">
    </form>
    <a href="{{ route('admin.users.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Yangi foydalanuvchi</span>
    </a>
  </x-admin.page-header>

  <div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Jami foydalanuvchi"
        :value="number_format($counts['all'] ?? 0)"
        meta="Platformadagi barcha ro‘yxatdan o‘tgan akkauntlar"
        icon="people"
        tone="primary" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Xarid qilganlar"
        :value="number_format($counts['buyers'] ?? 0)"
        meta="Buyurtma yoki to‘lov aktivligi mavjud userlar"
        icon="bag-check"
        tone="success" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Karta ulanganlar"
        :value="number_format($counts['with_cards'] ?? 0)"
        meta="Saved card bilan checkoutni tez bajaradigan userlar"
        icon="credit-card-2-front"
        tone="info" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Premium / blok"
        :value="number_format($counts['premium'] ?? 0) . ' / ' . number_format($counts['blocked'] ?? 0)"
        meta="Loyalty va moderation segmentlari bir ko‘rinishda"
        icon="shield-lock"
        tone="warning" />
    </div>
  </div>

  <x-admin.section-card title="Segmentlar va filtrlash" meta="Faollik, access va checkout signaliga qarab userlarni tez ajrating.">
    <div class="d-flex flex-column gap-3">
      <div class="kc-filter-card">
        <div class="nav nav-pills flex-wrap">
          @foreach($tabs as $key => $tabItem)
            <a
              href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}"
              class="nav-link {{ $tab === $key ? 'active' : '' }}">
              {{ $tabItem['label'] }}
              <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">
                {{ number_format($tabItem['count']) }}
              </span>
            </a>
          @endforeach
        </div>
      </div>
      <div class="small text-secondary">
        Premium, blocked va staff role belgilarini jadval ichidan bir qarashda ko‘rish mumkin.
      </div>
    </div>
  </x-admin.section-card>

  <x-admin.section-card title="Foydalanuvchilar jadvali" :meta="$users->total() . ' ta yozuv yuklandi.'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Foydalanuvchi</th>
            <th>Rol va access</th>
            <th>Status</th>
            <th>Kontakt</th>
            <th>Qo‘shilgan</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $user)
            <tr>
              <td>
                <div class="d-flex align-items-center gap-3 min-w-0">
                  @include('a122.partials.avatar', [
                    'name' => trim(($user->name ?? 'U').' '.($user->lastname ?? '')),
                    'image' => $user->avatar,
                    'class' => 'w-10 h-10 rounded-4 text-sm',
                  ])
                  <div class="min-w-0">
                    <div class="fw-semibold text-truncate">{{ trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: '—' }}</div>
                    <div class="small text-secondary text-truncate">{{ $user->email ?: 'Email yo‘q' }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-2">
                  <span class="badge rounded-pill text-bg-light border">{{ $user->position ?: 'reader' }}</span>
                  @if($user->staff_role)
                    <span class="badge rounded-pill text-bg-warning-subtle border border-warning-subtle text-warning-emphasis">
                      {{ $user->staff_role === 'administrator' ? 'Administrator' : 'Moderator' }}
                    </span>
                  @endif
                  @if($user->is_premium)
                    <span class="badge rounded-pill text-bg-primary-subtle border border-primary-subtle text-primary-emphasis">Premium</span>
                  @endif
                </div>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-2">
                  <span class="badge rounded-pill {{ $user->isVerified ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis' : 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis' }}">
                    {{ $user->isVerified ? 'Faol' : 'Kutilmoqda' }}
                  </span>
                  @if($user->isBlocked())
                    <span class="badge rounded-pill text-bg-danger-subtle border border-danger-subtle text-danger-emphasis">Bloklangan</span>
                  @endif
                </div>
              </td>
              <td>
                <div class="small fw-semibold text-dark">{{ $user->phone_number ?: 'Telefon yo‘q' }}</div>
                <div class="small text-secondary">{{ $user->email ?: '—' }}</div>
              </td>
              <td>
                <div class="fw-semibold">{{ optional($user->created_at)->format('d.m.Y') ?: '—' }}</div>
                <div class="small text-secondary">{{ optional($user->created_at)->format('H:i') ?: '' }}</div>
              </td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                  <form method="POST" action="{{ route('admin.users.verify', $user) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="{{ $user->isVerified ? 'Tasdiqni bekor qilish' : 'Tasdiqlash' }}">
                      <i class="bi {{ $user->isVerified ? 'bi-patch-minus' : 'bi-patch-check' }}"></i>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.users.premium', $user) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="{{ $user->is_premium ? 'Premiumni o‘chirish' : 'Premiumni yoqish' }}">
                      <i class="bi {{ $user->is_premium ? 'bi-gem' : 'bi-stars' }}"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-secondary">Foydalanuvchilar topilmadi.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if(method_exists($users, 'links'))
    <div>{{ $users->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
