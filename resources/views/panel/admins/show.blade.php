@extends('panel.layouts.panel')
@section('title', 'Admin: '.$admin->name)
@section('page-title', $admin->name)

@section('content')
<div class="row g-3">

  {{-- Chap: ma'lumotlar --}}
  <div class="col-xl-8">

    <div class="p-card mb-3">
      <div class="dash-card-head">
        <div class="dash-card-title">Admin ma'lumotlari</div>
        <div class="d-flex gap-2">
          <a href="{{ route('panel.admins.edit', $admin) }}" class="btn-p ghost sm">
            <i class="bi bi-pencil"></i> Tahrirlash
          </a>
          @if($admin->id !== auth('panel')->id())
          <form method="POST" action="{{ route('panel.admins.toggle', $admin) }}">
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
        <div class="d-flex align-items-center gap-4 mb-4">
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
            <div class="d-flex gap-2 mt-2">
              <span class="s-pill" style="background:rgba({{ $admin->getRoleColorAttribute() ?? '79,124,255' }},.15);color:var(--p-accent)">
                {{ $admin->getRoleLabelAttribute() }}
              </span>
              <span class="s-pill {{ $admin->is_active ? 'success' : 'danger' }}">
                {{ $admin->is_active ? 'Faol' : 'Bloklangan' }}
              </span>
            </div>
          </div>
        </div>

        <div class="row g-3">
          @foreach([
            ['ID',              '#'.$admin->id],
            ['Email',           $admin->email],
            ['Rol',             $admin->getRoleLabelAttribute()],
            ['Oxirgi IP',       $admin->last_ip ?? '—'],
            ['Oxirgi kirish',   $admin->last_login_at ? \Carbon\Carbon::parse($admin->last_login_at)->format('d.m.Y H:i') : '—'],
            ["Qo'shildi",       $admin->created_at?->format('d.m.Y H:i')],
          ] as [$k, $v])
          <div class="col-sm-6">
            <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">{{ $k }}</div>
            <div style="font-size:14px;font-weight:500;color:var(--p-text)">{{ $v }}</div>
          </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Ruxsatlar --}}
    <div class="p-card">
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
          @php
            $allPerms = ['users','books','stationery','orders','sellers','couriers','promocodes','discounts','settings','admins'];
            $adminPerms = $admin->permissions ?? [];
          @endphp
          <div class="d-flex flex-wrap gap-2">
            @foreach($allPerms as $perm)
            <span class="s-pill {{ in_array($perm, $adminPerms) ? 'success' : 'muted' }}"
                  style="font-size:12px;padding:5px 12px">
              <i class="bi bi-{{ in_array($perm, $adminPerms) ? 'check-circle-fill' : 'x-circle' }} me-1"></i>
              {{ $perm }}
            </span>
            @endforeach
          </div>
        @endif
      </div>
    </div>

  </div>

  {{-- O'ng: tezkor amallar --}}
  <div class="col-xl-4">
    <div class="p-card">
      <div class="dash-card-head"><div class="dash-card-title">Amallar</div></div>
      <div class="dash-card-body d-flex flex-column gap-2">
        <a href="{{ route('panel.admins.edit', $admin) }}" class="btn-p" style="justify-content:center">
          <i class="bi bi-pencil"></i> Tahrirlash
        </a>
        @if($admin->id !== auth('panel')->id())
        <form method="POST" action="{{ route('panel.admins.toggle', $admin) }}">
          @csrf @method('PATCH')
          <button class="btn-p {{ $admin->is_active ? 'danger' : 'ghost' }}" style="width:100%;justify-content:center">
            <i class="bi bi-{{ $admin->is_active ? 'lock' : 'unlock' }}"></i>
            {{ $admin->is_active ? 'Bloklash' : 'Faollashtirish' }}
          </button>
        </form>
        <form method="POST" action="{{ route('panel.admins.destroy', $admin) }}"
              onsubmit="return confirm('Adminni o\'chirishni tasdiqlaysizmi?')">
          @csrf @method('DELETE')
          <button class="btn-p danger ghost" style="width:100%;justify-content:center">
            <i class="bi bi-trash"></i> O'chirish
          </button>
        </form>
        @endif
        <a href="{{ route('panel.admins.index') }}" class="btn-p ghost" style="justify-content:center">
          <i class="bi bi-arrow-left"></i> Ro'yxatga
        </a>
      </div>
    </div>
  </div>

</div>
@endsection