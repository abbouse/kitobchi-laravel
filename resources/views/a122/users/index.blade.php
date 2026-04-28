@extends('a122.layouts.admin')
@section('title', 'Foydalanuvchilar')
@section('page-title', 'Foydalanuvchilar')

@section('content')
<div>
  <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
    <div>
      <h2 class="text-xl font-bold tracking-tight">Foydalanuvchilar</h2>
      <p class="text-xs text-gray-500 mt-0.5">{{ $users->total() }} ta yozuv topildi</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <form method="GET" class="relative min-w-[220px]">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ism, email yoki rol bo'yicha qidirish..." class="input !pl-9 !py-2 w-full">
      </form>
      <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>Qo‘shish</span>
      </a>
    </div>
  </div>
  <div class="tab-pills fade-up mb-3">
    @foreach([
      'all' => ['Barchasi', $counts['all'] ?? 0],
      'buyers' => ['Xarid qilganlar', $counts['buyers'] ?? 0],
      'pending' => ['Kutilmoqda', $counts['pending'] ?? 0],
      'active' => ['Faol', $counts['active'] ?? 0],
      'premium' => ['Premium', $counts['premium'] ?? 0],
      'blocked' => ['Bloklangan', $counts['blocked'] ?? 0],
    ] as $key => [$label, $count])
      <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="tab-pill {{ $tab === $key ? 'active' : '' }}">
        {{ $label }} <span>{{ $count }}</span>
      </a>
    @endforeach
  </div>
  <div class="hidden">
    @forelse($users as $user)
      <div class="card p-4">
        <div class="flex items-start gap-3">
          @include('a122.partials.avatar', [
            'name' => trim(($user->name ?? 'U').' '.($user->lastname ?? '')),
            'image' => $user->avatar,
            'class' => 'w-11 h-11 rounded-2xl text-sm',
          ])
          <div class="min-w-0 flex-1">
            <div class="font-semibold text-sm">{{ trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: '—' }}</div>
            <div class="text-xs text-gray-500 break-all">{{ $user->email ?: '—' }}</div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
              <span class="badge badge-info">{{ $user->position ?: 'User' }}</span>
              <span class="badge {{ $user->isBlocked() ? 'badge-danger' : ($user->isVerified ? 'badge-success' : 'badge-warning') }}">{{ $user->isBlocked() ? 'blocked' : ($user->isVerified ? 'active' : 'pending') }}</span>
              <span class="text-xs text-gray-500">{{ optional($user->created_at)->format('Y-m-d') }}</span>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <a href="{{ route('admin.users.show', $user) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></a>
          </div>
        </div>
      </div>
    @empty
      <div class="card p-6 text-sm text-gray-500">Foydalanuvchilar topilmadi.</div>
    @endforelse
  </div>

  <div class="table-wrap">
    <div class="overflow-x-auto">
      <table class="tbl" data-index-grid>
        <thead><tr><th>Foydalanuvchi</th><th>Rol</th><th>Status</th><th>Qo'shilgan</th><th class="text-right">Amallar</th></tr></thead>
        <tbody>
          @forelse($users as $user)
            <tr>
              <td>
                <div class="flex items-center gap-3">
                  @include('a122.partials.avatar', [
                    'name' => trim(($user->name ?? 'U').' '.($user->lastname ?? '')),
                    'image' => $user->avatar,
                    'class' => 'w-9 h-9 rounded-full text-xs',
                  ])
                  <div>
                    <div class="font-semibold">{{ trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: '—' }}</div>
                    <div class="text-xs text-gray-500">{{ $user->email ?: '—' }}</div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-info">{{ $user->position ?: 'User' }}</span></td>
              <td>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="badge {{ $user->isVerified ? 'badge-success' : 'badge-warning' }}">{{ $user->isVerified ? 'active' : 'pending' }}</span>
                  @if($user->isBlocked())
                    <span class="badge badge-danger">blocked</span>
                  @endif
                  @if($user->is_premium)
                    <span class="badge badge-info">premium</span>
                  @endif
                </div>
              </td>
              <td>{{ optional($user->created_at)->format('Y-m-d') }}</td>
              <td>
                <div class="flex items-center justify-end gap-1">
                  <form method="POST" action="{{ route('admin.users.verify', $user) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="{{ $user->isVerified ? 'Tasdiqni bekor qilish' : 'Tasdiqlash' }}">
                      <i data-lucide="{{ $user->isVerified ? 'badge-x' : 'badge-check' }}" class="w-4 h-4"></i>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.users.premium', $user) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="{{ $user->is_premium ? 'Premiumni o‘chirish' : 'Premiumni yoqish' }}">
                      <i data-lucide="{{ $user->is_premium ? 'gem' : 'sparkles' }}" class="w-4 h-4"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.users.show', $user) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
                  <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-sm text-gray-500 py-8">Foydalanuvchilar topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@if(isset($users) && method_exists($users, 'links'))
  <div class="mt-4">{{ $users->links('a122.partials.pagination') }}</div>
@endif
@endsection
