@extends('a122.layouts.admin')
@section('title','Adminlar')
@section('page-title','Admin boshqaruvi')
@section('breadcrumb','Panel / Adminlar')

@section('content')

<x-a122.page-header>
  <x-slot name="heading">Adminlar</x-slot>
  <x-slot name="meta">Panel foydalanuvchilari va ularning ruxsatlari</x-slot>
</x-a122.page-header>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Adminlar ro‘yxati</div>
    <div class="a122-index-header__meta">{{ $admins->total() }} ta admin topildi</div>
  </div>
  <div class="a122-index-header__actions">
    <form method="GET" class="a122-index-search-form">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Ism, email yoki rol bo‘yicha qidiring">
    </form>
    <a href="{{ route('admin.admins.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i> Yangi admin
    </a>
  </div>
</div>

<div class="tab-pills fade-up mb-3">
  @foreach([
    'active' => ['Faol', $counts['active'] ?? 0],
    'inactive' => ['Bloklangan', $counts['inactive'] ?? 0],
    'all' => ['Barchasi', $counts['all'] ?? 0],
  ] as $key => [$label, $count])
    <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="tab-pill {{ $tab === $key ? 'active' : '' }}">
      {{ $label }} <span>{{ $count }}</span>
    </a>
  @endforeach
</div>


<div class="p-card fade-up">
  <div class="p-card-header">
    <div class="p-card-title">Adminlar ro'yxati</div>
    <div class="p-card-sub">{{ $admins->total() }} ta admin</div>
  </div>

  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
      <thead>
        <tr>
          <th>ID</th><th>Admin</th><th>Rol</th>
          <th>Ruxsatlar</th><th>Oxirgi kirish</th>
          <th>Holat</th><th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($admins as $admin)
        <tr>
          <td><span style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">#{{ $admin->id }}</span></td>
          <td>
            <div class="flex items-center gap-2">
              @include('a122.partials.avatar', [
                'name' => $admin->name,
                'image' => $admin->avatar,
                'class' => 'av',
              ])
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $admin->name }}</div>
                <div style="font-size:11px;color:var(--p-hint)">{{ $admin->email }}</div>
              </div>
            </div>
          </td>
          <td>
            @php $colors = ['superadmin'=>'danger','admin'=>'accent','moderator'=>'warning']; @endphp
            <span class="s-pill {{ $colors[$admin->role] ?? 'muted' }}">{{ $admin->role_label }}</span>
          </td>
          <td>
            @if($admin->isSuperAdmin())
              <span style="font-size:12px;color:var(--p-hint)">Barcha ruxsatlar</span>
            @else
              <div class="flex flex-wrap gap-1">
                @foreach(array_slice($admin->permissions ?? [], 0, 3) as $perm)
                  <span class="s-pill accent" style="font-size:10px;padding:2px 7px">{{ $perm }}</span>
                @endforeach
                @if(count($admin->permissions ?? []) > 3)
                  <span style="font-size:11px;color:var(--p-hint)">+{{ count($admin->permissions)-3 }} ta</span>
                @endif
              </div>
            @endif
          </td>
          <td>
            @if($admin->last_login_at)
              <div style="font-size:12px;color:var(--p-text)">{{ $admin->last_login_at->format('d.m.Y H:i') }}</div>
              <div style="font-size:11px;color:var(--p-hint)">{{ $admin->last_ip }}</div>
            @else
              <span style="font-size:12px;color:var(--p-hint)">Hali kirмagan</span>
            @endif
          </td>
          <td>
            @if($admin->is_active)
              <span class="s-pill success">Aktiv</span>
            @else
              <span class="s-pill danger">Bloklangan</span>
            @endif
          </td>
          <td>
            <div class="flex gap-1">
              <a href="{{ route('admin.admins.edit', $admin) }}" class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              @if($admin->id !== auth('panel')->id())
              <form method="POST" action="{{ route('admin.admins.toggle', $admin) }}">
                @csrf @method('PATCH')
                <button class="btn-p {{ $admin->is_active ? 'danger' : 'success' }} sm"
                        title="{{ $admin->is_active ? 'Bloklash' : 'Faollashtirish' }}">
                  <i class="bi bi-{{ $admin->is_active ? 'lock' : 'unlock' }}"></i>
                </button>
              </form>
              <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}"
                    onsubmit="return confirm('Adminni o\'chirishni tasdiqlaysizmi?')">
                @csrf @method('DELETE')
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-shield" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Adminlar topilmadi
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($admins->hasPages())
    {{ $admins->links('a122.partials.pagination') }}
  @endif
</div>

@endsection
