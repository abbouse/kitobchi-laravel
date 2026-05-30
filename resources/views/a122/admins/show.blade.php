@extends('a122.layouts.admin')
@section('title', 'Admin: '.$admin->name)
@section('page-title', $admin->name)

@section('content')
@php
  $adminPerms = $admin->permissions ?? [];
  $allPerms = ['users','books','stationery','orders','sellers','hubs','couriers','promocodes','discounts','settings','admins'];
  $rolePillClass = match($admin->role) {
    'superadmin' => 'danger',
    'admin' => 'accent',
    'moderator' => 'warning',
    default => 'muted',
  };
@endphp

<x-a122.page-header back-href="{{ route('admin.admins.index') }}">
  <x-slot name="heading">{{ $admin->name }}</x-slot>
  <x-slot name="meta">ID: #{{ $admin->id }} · {{ $admin->getRoleLabelAttribute() }}</x-slot>
</x-a122.page-header>

<section class="a122-section mb-4">
  <div class="a122-section-body">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="kpi-soft">
        <div class="metric-label">Rol</div>
        <div class="metric-value text-xl">{{ $admin->getRoleLabelAttribute() }}</div>
        <div class="metric-meta">Panel darajasi</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Holat</div>
        <div class="metric-value text-xl">{{ $admin->is_active ? 'Faol' : 'Bloklangan' }}</div>
        <div class="metric-meta">Session kirishi</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Ruxsatlar</div>
        <div class="metric-value text-xl">{{ $admin->isSuperAdmin() ? 'All' : count($adminPerms) }}</div>
        <div class="metric-meta">{{ $admin->isSuperAdmin() ? 'Superadmin' : 'Biriktirilgan modul' }}</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Oxirgi kirish</div>
        <div class="metric-value text-xl">{{ $admin->last_login_at ? $admin->last_login_at->format('d.m') : '—' }}</div>
        <div class="metric-meta">{{ $admin->last_login_at ? $admin->last_login_at->format('H:i') : 'Vaqt yo‘q' }}</div>
      </div>
    </div>
  </div>
</section>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  {{-- Chap: ma'lumotlar --}}
  <div class="xl:col-span-8">

    <div class="card-panel mb-3">
      <div class="dash-card-head">
        <div class="dash-card-title">Admin ma'lumotlari</div>
        <div class="flex gap-2">
          <a href="{{ route('admin.admins.edit', $admin) }}" class="btn-p ghost sm">
            <i class="bi bi-pencil"></i> Tahrirlash
          </a>
          @if($admin->id !== auth('panel')->id())
          <form method="POST" action="{{ route('admin.admins.toggle', $admin) }}">
            @csrf @method('PATCH')
            <button class="btn-p {{ $admin->is_active ? 'danger' : '' }} ghost sm">
              <i class="bi bi-{{ $admin->is_active ? 'lock' : 'unlock' }}"></i>
              {{ $admin->is_active ? 'Bloklash' : 'Faollashtirish' }}
            </button>
          </form>
          @endif
        </div>
      </div>
      <div class="dash-card-body">
        <div class="flex items-center gap-4 mb-4">
          <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;
                      color:#fff;flex-shrink:0;overflow:hidden">
            @if($admin->avatar)
              <img src="{{ $admin->avatar }}" style="width:100%;height:100%;object-fit:cover">
            @else
              {{ strtoupper(substr($admin->name, 0, 1)) }}
            @endif
          </div>
          <div>
            <div style="font-size:20px;font-weight:700;color:var(--p-text)">{{ $admin->name }}</div>
            <div style="font-size:13px;color:var(--p-hint)">{{ $admin->email }}</div>
            <div class="flex gap-2 mt-2">
              <span class="s-pill {{ $rolePillClass }}">
                {{ $admin->getRoleLabelAttribute() }}
              </span>
              <span class="s-pill {{ $admin->is_active ? 'success' : 'danger' }}">
                {{ $admin->is_active ? 'Faol' : 'Bloklangan' }}
              </span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          @foreach([
            ['ID',              '#'.$admin->id],
            ['Email',           $admin->email],
            ['Rol',             $admin->getRoleLabelAttribute()],
            ['Oxirgi IP',       $admin->last_ip ?? '—'],
            ['Oxirgi kirish',   $admin->last_login_at ? $admin->last_login_at->format('d.m.Y H:i') : '—'],
            ["Qo'shildi",       $admin->created_at?->format('d.m.Y H:i')],
          ] as [$k, $v])
          <div class="">
            <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">{{ $k }}</div>
            <div style="font-size:14px;font-weight:500;color:var(--p-text)">{{ $v }}</div>
          </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Ruxsatlar --}}
    <div class="card-panel">
      <div class="dash-card-head"><div class="dash-card-title">Ruxsatlar</div></div>
      <div class="dash-card-body">
        @if($admin->isSuperAdmin())
          <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;
                      background:var(--p-accent-d);border-radius:8px;border:1px solid rgba(79,124,255,.2)">
            <i class="bi bi-shield-fill-check" style="color:var(--p-accent);font-size:20px"></i>
            <div>
              <div style="font-size:13px;font-weight:600;color:var(--p-accent)">Superadmin</div>
              <div style="font-size:11px;color:var(--p-hint)">Barcha ruxsatlarga ega</div>
            </div>
          </div>
        @else
          <div class="flex flex-wrap gap-2">
            @foreach($allPerms as $perm)
            <span class="s-pill {{ in_array($perm, $adminPerms) ? 'success' : 'muted' }}"
                  style="font-size:12px;padding:5px 12px">
              <i class="bi bi-{{ in_array($perm, $adminPerms) ? 'check-circle-fill' : 'x-circle' }} mr-1"></i>
              {{ $perm }}
            </span>
            @endforeach
          </div>
        @endif
      </div>
    </div>

  </div>

  {{-- O'ng: tezkor amallar --}}
  <div class="xl:col-span-4">
    <div class="card-panel">
      <div class="dash-card-head"><div class="dash-card-title">Amallar</div></div>
      <div class="dash-card-body flex flex-col gap-2">
        <a href="{{ route('admin.admins.edit', $admin) }}" class="btn-p primary" style="justify-content:center">
          <i class="bi bi-pencil"></i> Tahrirlash
        </a>
        @if($admin->id !== auth('panel')->id())
        <form method="POST" action="{{ route('admin.admins.toggle', $admin) }}">
          @csrf @method('PATCH')
          <button class="btn-p {{ $admin->is_active ? 'danger' : 'ghost' }}" style="width:100%;justify-content:center">
            <i class="bi bi-{{ $admin->is_active ? 'lock' : 'unlock' }}"></i>
            {{ $admin->is_active ? 'Bloklash' : 'Faollashtirish' }}
          </button>
        </form>
        <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}"
              onsubmit="return confirm('Adminni o\'chirishni tasdiqlaysizmi?')">
          @csrf @method('DELETE')
          <button class="btn-p danger ghost" style="width:100%;justify-content:center">
            <i class="bi bi-trash"></i> O'chirish
          </button>
        </form>
        @endif
        <a href="{{ route('admin.admins.index') }}" class="btn-p ghost" style="justify-content:center">
          <i class="bi bi-arrow-left"></i> Ro'yxatga
        </a>
      </div>
    </div>
  </div>

</div>
@endsection
