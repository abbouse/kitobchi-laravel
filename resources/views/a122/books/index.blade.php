@extends('a122.layouts.admin')
@section('title', 'Kitoblar')
@section('page-title', 'Kitoblar')

@section('content')
<div>
  <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
    <div>
      <h2 class="text-xl font-bold tracking-tight">Kitoblar</h2>
      <p class="text-xs text-gray-500 mt-0.5">{{ $books->total() }} ta yozuv topildi</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <form method="GET" class="relative min-w-[220px]">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Sarlavha, muallif, kategoriya..." class="input !pl-9 !py-2 w-full">
      </form>
      <a href="{{ route('admin.books.create') }}" class="btn btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>Qo‘shish</span>
      </a>
    </div>
  </div>
  <div class="tab-pills fade-up mb-3">
    @foreach([
      'pending' => ['Moderatsiya', $counts['pending'] ?? 0],
      'active' => ['Faol', $counts['active'] ?? 0],
      'rejected' => ['Rad etilgan', $counts['rejected'] ?? 0],
      'all' => ['Barchasi', $counts['all'] ?? 0],
    ] as $key => [$label, $count])
      <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="tab-pill {{ $tab === $key ? 'active' : '' }}">
        {{ $label }} <span>{{ $count }}</span>
      </a>
    @endforeach
  </div>
  <div class="hidden">
    @forelse($books as $book)
      @php
        $statusLabel = (int) ($book->is_approved ?? 0) === 1 ? 'active' : ((int) ($book->is_approved ?? 0) === 2 ? 'banned' : 'pending');
      @endphp
      <div class="card p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="font-semibold">{{ $book->name }}</div>
            <div class="text-xs text-gray-500">{{ $book->author ?: '—' }}</div>
          </div>
          <span class="badge {{ $statusLabel === 'active' ? 'badge-success' : ($statusLabel === 'pending' ? 'badge-warning' : 'badge-danger') }}">{{ $statusLabel }}</span>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
          <div><div class="text-xs text-gray-500">Kategoriya</div><div>{{ $book->category?->name_uz ?: '—' }}</div></div>
          <div><div class="text-xs text-gray-500">Narx</div><div>{{ number_format((float) $book->price, 0) }} UZS</div></div>
          <div><div class="text-xs text-gray-500">Stok</div><div>{{ (int) ($book->count ?? 0) }}</div></div>
          <div><div class="text-xs text-gray-500">ID</div><div>#{{ $book->id }}</div></div>
        </div>
        <div class="mt-4 flex items-center justify-end gap-1">
          <a href="{{ route('admin.books.show', $book) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
          <a href="{{ route('admin.books.edit', $book) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></a>
        </div>
      </div>
    @empty
      <div class="card p-6 text-sm text-gray-500">Kitoblar topilmadi.</div>
    @endforelse
  </div>

  <div class="table-wrap">
    <div class="overflow-x-auto">
      <table class="tbl" data-index-grid>
        <thead><tr><th>Kitob</th><th>Kategoriya</th><th>Narx</th><th>Stok</th><th>Status</th><th class="text-right">Amallar</th></tr></thead>
        <tbody>
          @forelse($books as $book)
            @php
              $statusLabel = (int) ($book->is_approved ?? 0) === 1 ? 'active' : ((int) ($book->is_approved ?? 0) === 2 ? 'banned' : 'pending');
            @endphp
            <tr>
              <td><div class="font-semibold">{{ $book->name }}</div><div class="text-xs text-gray-500">{{ $book->author ?: '—' }}</div></td>
              <td>{{ $book->category?->name_uz ?: '—' }}</td>
              <td>{{ number_format((float) $book->price, 0) }} UZS</td>
              <td>{{ (int) ($book->count ?? 0) }}</td>
              <td><span class="badge {{ $statusLabel === 'active' ? 'badge-success' : ($statusLabel === 'pending' ? 'badge-warning' : 'badge-danger') }}">{{ $statusLabel }}</span></td>
              <td>
                <div class="flex items-center justify-end gap-1 flex-wrap">
                  <form method="POST" action="{{ route('admin.books.moderate', $book) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_approved" value="1">
                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Faollashtirish">
                      <i data-lucide="badge-check" class="w-4 h-4"></i>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.books.moderate', $book) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_approved" value="2">
                    <button type="submit" class="btn-ghost p-2 rounded-lg" title="Rad etish">
                      <i data-lucide="badge-x" class="w-4 h-4"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.books.show', $book) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
                  <a href="{{ route('admin.books.edit', $book) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-sm text-gray-500 py-8">Kitoblar topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@if(isset($books) && method_exists($books, 'links'))
  <div class="mt-4">{{ $books->links('a122.partials.pagination') }}</div>
@endif
@endsection
