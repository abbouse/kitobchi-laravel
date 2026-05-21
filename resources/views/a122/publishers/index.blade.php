@extends('a122.layouts.admin')
@section('title', 'Nashriyotlar')
@section('page-title', 'Nashriyotlar')

@section('content')
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h2 class="text-xl font-bold">Nashriyotlar</h2>
      <p class="text-xs text-gray-500 mt-0.5">Kitoblarni yagona publisher bazasiga bog‘lash uchun ro‘yxat.</p>
    </div>
    <div class="flex items-center gap-2">
      <form method="GET" class="relative">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Nashriyot qidirish..." class="input !pl-9 !py-2 w-64">
      </form>
      <a href="{{ route('admin.publishers.create') }}" class="btn btn-primary">
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
      <div class="text-xs text-gray-500">Jami nashriyot</div>
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
            <th>Nomi</th>
            <th>Kitoblar</th>
            <th>Yangilangan</th>
            <th class="text-right">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($publishers as $publisher)
            <tr>
              <td class="text-gray-500 text-xs">{{ $publisher->id }}</td>
              <td>
                <div class="w-14 h-14 rounded-2xl overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] flex items-center justify-center">
                  @if($publisher->image_url)
                    <img src="{{ $publisher->image_url }}" alt="{{ $publisher->name }}" class="w-full h-full object-cover">
                  @else
                    <i data-lucide="building-2" class="w-5 h-5 text-gray-400"></i>
                  @endif
                </div>
              </td>
              <td class="font-semibold">{{ $publisher->name }}</td>
              <td><span class="font-semibold">{{ $publisher->books_count }}</span></td>
              <td class="text-gray-500">{{ optional($publisher->updated_at)->format('d.m.Y H:i') ?: '—' }}</td>
              <td>
                <div class="flex items-center justify-end gap-1">
                  <a href="{{ route('admin.publishers.edit', $publisher) }}" class="btn-ghost p-2 rounded-lg">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.publishers.destroy', $publisher) }}" onsubmit="return confirm('Nashriyotni o\\'chirishni tasdiqlaysizmi?')">
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
                Hali nashriyotlar qo‘shilmagan
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div>{{ $publishers->links('a122.partials.pagination') }}</div>
</div>
@endsection
