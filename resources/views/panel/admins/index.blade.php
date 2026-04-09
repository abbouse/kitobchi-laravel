@extends('panel.layouts.panel')
@section('title','Adminlar')
@section('page-title','Admin boshqaruvi')
@section('breadcrumb','Panel / Adminlar')

@section('content')

<div class="page-header fade-up d-flex align-items-start justify-content-between">
  <div>
    <h1 class="page-title">Adminlar</h1>
    <p class="page-sub">Panel foydalanuvchilari va ularning ruxsatlari</p>
  </div>
  <a href="{{ route('panel.admins.create') }}" class="btn-p primary">
    <i class="bi bi-plus-lg"></i> Yangi admin
  </a>
</div>

<div class="p-card fade-up">
  <div class="p-card-header">
    <div class="p-card-title">Adminlar ro'yxati</div>
    <div class="p-card-sub">{{ $admins->total() }} ta admin</div>
  </div>

  <div class="table-responsive">
    <table class="p-table">
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
          <td><span style="font-family:'DM Mono',monospace;color:var(--p-accent);font-size:12px">#{{ $admin->id }}</span></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="av" style="background:linear-gradient(135deg,var(--p-accent),#7c5cfc);color:#fff">
                {{ strtoupper(substr($admin->name,0,1)) }}
              </div>
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
              <div class="d-flex flex-wrap gap-1">
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
            <div class="d-flex gap-1">
              <a href="{{ route('panel.admins.edit', $admin) }}" class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              @if($admin->id !== auth('panel')->id())
              <form method="POST" action="{{ route('panel.admins.toggle', $admin) }}">
                @csrf @method('PATCH')
                <button class="btn-p {{ $admin->is_active ? 'danger' : 'success' }} sm"
                        title="{{ $admin->is_active ? 'Bloklash' : 'Faollashtirish' }}">
                  <i class="bi bi-{{ $admin->is_active ? 'lock' : 'unlock' }}"></i>
                </button>
              </form>
              <form method="POST" action="{{ route('panel.admins.destroy', $admin) }}"
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
  <div class="d-flex align-items-center justify-content-between mt-3"
       style="padding-top:12px;border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">{{ $admins->firstItem() }}–{{ $admins->lastItem() }} / {{ $admins->total() }}</div>
    <div class="p-pagination">
      @if($admins->onFirstPage())
        <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
      @else
        <a href="{{ $admins->previousPageUrl() }}" class="p-page-btn"><i class="bi bi-chevron-left"></i></a>
      @endif
      @foreach($admins->getUrlRange(max(1,$admins->currentPage()-2),min($admins->lastPage(),$admins->currentPage()+2)) as $page=>$url)
        <a href="{{ $url }}" class="p-page-btn {{ $page===$admins->currentPage()?'active':'' }}">{{ $page }}</a>
      @endforeach
      @if($admins->hasMorePages())
        <a href="{{ $admins->nextPageUrl() }}" class="p-page-btn"><i class="bi bi-chevron-right"></i></a>
      @else
        <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
      @endif
    </div>
  </div>
  @endif
</div>

@endsection