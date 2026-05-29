@extends('a122.layouts.admin')
@section('title', 'Adminlar')
@section('page-title', 'Adminlar')
@section('breadcrumb', 'Panel / Adminlar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Admin" title="Adminlar" subtitle="{{ $admins->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Ism, email yoki rol" class="form-control">
    </form>
    <a href="{{ route('admin.admins.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      @foreach([
        'active' => ['Faol', $counts['active'] ?? 0],
        'inactive' => ['Bloklangan', $counts['inactive'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
      ] as $key => [$label, $count])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
          {{ $label }}
          <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($count) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <x-admin.section-card title="Adminlar jadvali" :meta="$admins->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Admin</th>
            <th>Rol</th>
            <th>Ruxsatlar</th>
            <th>Oxirgi kirish</th>
            <th>Holat</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($admins as $admin)
            @php $roleColors = ['superadmin' => 'danger', 'admin' => 'primary', 'moderator' => 'warning']; @endphp
            <tr>
              <td class="text-secondary">#{{ $admin->id }}</td>
              <td>
                <div class="d-flex align-items-center gap-3">
                  @include('a122.partials.avatar', [
                    'name' => $admin->name,
                    'image' => $admin->avatar,
                    'class' => 'av',
                  ])
                  <div>
                    <div class="fw-semibold">{{ $admin->name }}</div>
                    <div class="small text-secondary">{{ $admin->email }}</div>
                  </div>
                </div>
              </td>
              <td><span class="badge rounded-pill text-bg-{{ $roleColors[$admin->role] ?? 'secondary' }}">{{ $admin->role_label }}</span></td>
              <td>
                @if($admin->isSuperAdmin())
                  <span class="text-secondary">Barcha ruxsatlar</span>
                @else
                  <div class="d-flex flex-wrap gap-1">
                    @foreach(array_slice($admin->permissions ?? [], 0, 3) as $permission)
                      <span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis">{{ $permission }}</span>
                    @endforeach
                    @if(count($admin->permissions ?? []) > 3)
                      <span class="small text-secondary">+{{ count($admin->permissions) - 3 }}</span>
                    @endif
                  </div>
                @endif
              </td>
              <td>
                @if($admin->last_login_at)
                  <div>{{ $admin->last_login_at->format('d.m.Y H:i') }}</div>
                  <div class="small text-secondary">{{ $admin->last_ip }}</div>
                @else
                  <span class="text-secondary">Hali kirmagan</span>
                @endif
              </td>
              <td>
                @if($admin->is_active)
                  <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">Faol</span>
                @else
                  <span class="badge rounded-pill text-bg-danger-subtle border border-danger-subtle text-danger-emphasis">Bloklangan</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="{{ route('admin.admins.show', $admin) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  @if($admin->id !== auth('panel')->id())
                    <form method="POST" action="{{ route('admin.admins.toggle', $admin) }}">
                      @csrf
                      @method('PATCH')
                      <button class="btn btn-sm btn-light border kc-table-action" title="{{ $admin->is_active ? 'Bloklash' : 'Faollashtirish' }}">
                        <i class="bi bi-{{ $admin->is_active ? 'lock' : 'unlock' }}"></i>
                      </button>
                    </form>
                    <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" onsubmit="return confirm('Adminni o‘chirishni tasdiqlaysizmi?')">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                        <i class="bi bi-trash3"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center py-5 text-secondary">Admin topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($admins->hasPages())
    <div>{{ $admins->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
