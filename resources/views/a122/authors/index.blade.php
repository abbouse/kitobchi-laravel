@extends('a122.layouts.admin')
@section('title', 'Mualliflar')
@section('page-title', 'Mualliflar')

@section('content')
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h2 class="text-xl font-bold">Mualliflar</h2>
      <p class="text-xs text-gray-500 mt-0.5">Book.uz dan bir marta olib, ichki kitoblar bilan bog‘lanadigan yagona author bazasi.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <form method="GET" class="relative">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Muallif qidirish..." class="input !pl-9 !py-2 w-64">
      </form>
      <form method="POST" action="{{ route('admin.authors.sync-book-uz') }}">
        @csrf
        <button class="btn btn-secondary">
          <i data-lucide="refresh-cw" class="w-4 h-4"></i> Book.uz sync
        </button>
      </form>
      <form method="POST" action="{{ route('admin.authors.backfill-books') }}">
        @csrf
        <button class="btn btn-secondary">
          <i data-lucide="link-2" class="w-4 h-4"></i> Eski kitoblarni ulash
        </button>
      </form>
      <a href="{{ route('admin.authors.create') }}" class="btn btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Qo'shish
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-sm flex items-center gap-2">
      <i data-lucide="check-circle" class="w-4 h-4"></i> {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 text-sm flex items-center gap-2">
      <i data-lucide="alert-circle" class="w-4 h-4"></i> {{ session('error') }}
    </div>
  @endif

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="card p-5">
      <div class="text-xs text-gray-500">Jami muallif</div>
      <div class="text-2xl font-black mt-2">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="card p-5">
      <div class="text-xs text-gray-500">Rasmli kartalar</div>
      <div class="text-2xl font-black mt-2">{{ number_format($stats['with_image']) }}</div>
    </div>
    <div class="card p-5">
      <div class="text-xs text-gray-500">Bog‘langan kitoblar</div>
      <div class="text-2xl font-black mt-2">{{ number_format($stats['linked_books']) }}</div>
    </div>
  </div>

  <div class="table-wrap">
    <div class="overflow-x-auto">
      <table class="tbl" data-index-grid>
        <thead>
          <tr>
            <th>ID</th>
            <th>Rasm</th>
            <th>Muallif</th>
            <th>Kitoblar</th>
            <th>Manba</th>
            <th class="text-right">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($authors as $author)
            <tr>
              <td class="text-gray-500 text-xs">{{ $author->id }}</td>
              <td>
                <div class="w-14 h-14 rounded-2xl overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] flex items-center justify-center">
                  @if($author->image_url)
                    <img src="{{ $author->image_url }}" alt="{{ $author->name }}" class="w-full h-full object-cover">
                  @else
                    <i data-lucide="pen-tool" class="w-5 h-5 text-gray-400"></i>
                  @endif
                </div>
              </td>
              <td>
                <div class="font-semibold">{{ $author->name }}</div>
                <div class="mt-1 d-flex flex-wrap align-items-center gap-2">
                  @if($author->external_id)
                    <span class="badge rounded-pill text-bg-light border">Book.uz</span>
                    <span class="text-xs text-gray-500">{{ $author->external_id }}</span>
                  @else
                    <span class="badge rounded-pill text-bg-light border">Manual</span>
                  @endif
                </div>
              </td>
              <td><span class="font-semibold">{{ $author->books_count }}</span></td>
              <td>
                @if($author->source_url)
                  <a href="{{ $author->source_url }}" target="_blank" rel="noopener" class="text-sm text-blue-600 hover:underline">Book.uz</a>
                @else
                  <span class="text-gray-400 text-sm">—</span>
                @endif
              </td>
              <td>
                <div class="flex items-center justify-end gap-1">
                  <a href="{{ route('admin.authors.edit', $author) }}" class="btn-ghost p-2 rounded-lg">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.authors.destroy', $author) }}" onsubmit="return confirm('Muallifni o\\'chirishni tasdiqlaysizmi?')">
                    @csrf @method('DELETE')
                    <button class="btn-ghost p-2 rounded-lg text-rose-500">
                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-10 text-gray-400">
                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
                Hali mualliflar qo‘shilmagan
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div>{{ $authors->links('a122.partials.pagination') }}</div>
</div>
@endsection
