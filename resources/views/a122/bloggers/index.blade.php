@extends('a122.layouts.admin')
@section('title', 'Hamkor blogerlar')
@section('page-title', 'Hamkor blogerlar')

@section('content')
<div class="space-y-6">
  <x-a122.page-header>
    <x-slot name="heading">Hamkor blogerlar</x-slot>
    <x-slot name="meta">Muddatga bog‘langan hamkorlar, jo‘natmalar va print oqimini bitta joydan boshqaring.</x-slot>
    <x-slot name="actions">
      <a href="{{ route('admin.bloggers.create') }}" class="btn-p primary"><i class="bi bi-plus-lg"></i> Yangi bloger</a>
    </x-slot>
  </x-a122.page-header>

  @if(session('success'))
    <div class="px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-sm">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 text-sm">{{ session('error') }}</div>
  @endif

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="card p-5"><div class="text-xs text-gray-500">Jami blogerlar</div><div class="text-2xl font-black mt-2">{{ number_format($stats['total']) }}</div></div>
    <div class="card p-5"><div class="text-xs text-gray-500">Faol</div><div class="text-2xl font-black mt-2">{{ number_format($stats['active']) }}</div></div>
    <div class="card p-5"><div class="text-xs text-gray-500">Nofaol</div><div class="text-2xl font-black mt-2">{{ number_format($stats['inactive']) }}</div></div>
    <div class="card p-5"><div class="text-xs text-gray-500">Jo‘natmalar</div><div class="text-2xl font-black mt-2">{{ number_format($stats['shipments']) }}</div></div>
  </div>

  <form method="GET" class="card p-4">
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_180px_auto] gap-3">
      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">Qidiruv</label>
        <input name="search" value="{{ request('search') }}" class="input" placeholder="Ism, familiya yoki telefon">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">Holat</label>
        <select name="tab" class="input">
          <option value="all" @selected($tab === 'all')>Barchasi</option>
          <option value="active" @selected($tab === 'active')>Faol</option>
          <option value="inactive" @selected($tab === 'inactive')>Nofaol</option>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-primary">Filterlash</button>
        <a href="{{ route('admin.bloggers.index') }}" class="btn btn-secondary">Tozalash</a>
      </div>
    </div>
  </form>

  <div class="table-wrap">
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead>
          <tr>
            <th>Bloger</th>
            <th>Aloqa</th>
            <th>Faollik</th>
            <th>Jo‘natmalar</th>
            <th>Tarmoqlar</th>
            <th class="text-right">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($bloggers as $blogger)
            @php $socialCount = count($blogger->socialLinks()); @endphp
            <tr>
              <td>
                <div class="font-semibold">{{ $blogger->full_name }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($blogger->address ?: 'Manzil kiritilmagan', 72) }}</div>
              </td>
              <td>{{ $blogger->phone_number ?: '—' }}</td>
              <td>
                <span class="badge {{ $blogger->is_active_now ? 'badge-success' : 'badge-muted' }}">{{ $blogger->status_label }}</span>
                <div class="text-xs text-gray-500 mt-1">{{ optional($blogger->active_until)->format('d.m.Y H:i') ?: 'Muddat yo‘q' }}</div>
              </td>
              <td>{{ $blogger->shipments_count }}</td>
              <td>{{ $socialCount ? $socialCount . ' ta link' : '—' }}</td>
              <td>
                <div class="flex justify-end items-center gap-2">
                  <a href="{{ route('admin.bloggers.show', $blogger) }}" class="btn-ghost p-2 rounded-lg"><i class="bi bi-eye"></i></a>
                  <a href="{{ route('admin.bloggers.edit', $blogger) }}" class="btn-ghost p-2 rounded-lg"><i class="bi bi-pencil"></i></a>
                  <form method="POST" action="{{ route('admin.bloggers.destroy', $blogger) }}" onsubmit="return confirm('Blogerni o‘chirishni tasdiqlaysizmi?')">
                    @csrf @method('DELETE')
                    <button class="btn-ghost p-2 rounded-lg text-rose-500"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-10 text-gray-400">Hamkor blogerlar hali qo‘shilmagan.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div>{{ $bloggers->links('a122.partials.pagination') }}</div>
</div>
@endsection
