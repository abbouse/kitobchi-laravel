@extends('a122.layouts.admin')
@section('title', 'Kitob Kategoriyalari')
@section('page-title', 'Kitob Kategoriyalari')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
  <div>
    <h2 class="text-xl font-bold">Kitob Kategoriyalari</h2>
    <p class="text-xs text-gray-500 mt-0.5">Jami: {{ $stats['total'] }}, Faol: {{ $stats['active'] }}</p>
  </div>
  <div class="flex items-center gap-2">
    <form method="GET" class="relative">
      <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
      <input name="search" value="{{ request('search') }}" placeholder="Qidirish..." class="input !pl-9 !py-2 w-60">
    </form>
    <a href="{{ route('admin.book-categories.create') }}" class="btn btn-primary">
      <i data-lucide="plus" class="w-4 h-4"></i> Qo'shish
    </a>
  </div>
</div>

@if(session('success'))
  <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-sm flex items-center gap-2">
    <i data-lucide="check-circle" class="w-4 h-4"></i> {{ session('success') }}
  </div>
@endif
@if(session('error'))
  <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 text-sm flex items-center gap-2">
    <i data-lucide="alert-circle" class="w-4 h-4"></i> {{ session('error') }}
  </div>
@endif

<div class="table-wrap">
  <div class="overflow-x-auto">
    <table class="tbl" data-index-grid>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nomi (UZ)</th>
          <th>Nomi (RU)</th>
          <th>Icon</th>
          <th>Kitoblar</th>
          <th>Holat</th>
          <th class="text-right">Amallar</th>
        </tr>
      </thead>
      <tbody>
        @forelse($categories as $cat)
          <tr>
            <td class="text-gray-500 text-xs">{{ $cat->id }}</td>
            <td class="font-semibold">{{ $cat->name_uz }}</td>
            <td class="text-gray-500">{{ $cat->name_ru }}</td>
            <td class="text-lg">{{ $cat->icon }}</td>
            <td><span class="font-semibold">{{ $cat->books_count }}</span></td>
            <td>
              @if($cat->is_active)
                <span class="badge badge-success">Faol</span>
              @else
                <span class="badge badge-muted">Yashirin</span>
              @endif
            </td>
            <td>
              <div class="flex items-center justify-end gap-1">
                <form method="POST" action="{{ route('admin.book-categories.toggle', $cat) }}">
                  @csrf @method('PATCH')
                  <button class="btn-ghost p-2 rounded-lg" title="{{ $cat->is_active ? 'Yashirish' : 'Faollashtirish' }}">
                    <i data-lucide="{{ $cat->is_active ? 'eye-off' : 'eye' }}" class="w-4 h-4"></i>
                  </button>
                </form>
                <a href="{{ route('admin.book-categories.edit', $cat) }}" class="btn-ghost p-2 rounded-lg">
                  <i data-lucide="pencil" class="w-4 h-4"></i>
                </a>
                <form method="POST" action="{{ route('admin.book-categories.destroy', $cat) }}" onsubmit="return confirm('O\'chirishni tasdiqlaysizmi?')">
                  @csrf @method('DELETE')
                  <button class="btn-ghost p-2 rounded-lg text-rose-500">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center py-10 text-gray-400">
            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
            Hech narsa topilmadi
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-4">{{ $categories->links('a122.partials.pagination') }}</div>
@endsection
