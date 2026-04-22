@extends('a122.layouts.admin')

@section('title', 'Stationery')
@section('page-title', 'Stationery')

@section('content')
<section class="card p-0 overflow-hidden">
  <div class="p-4 border-b border-gray-100 dark:border-white/5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <h3 class="font-bold">Kanstovarlar</h3>
    <form method="get" class="relative w-full sm:w-72">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Qidirish..." class="pl-9 pr-3 py-2 text-sm rounded-lg bg-gray-50 dark:bg-white/5 border border-transparent focus:border-emerald-500 focus:outline-none w-full">
    </form>
  </div>
  <div class="tab-pills fade-up mb-0 px-4 pt-4">
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
  <div class="overflow-x-auto">
    <table class="tbl" data-index-grid>
      <thead>
      <tr>
        <th>ID</th>
        <th>Nomi</th>
        <th>Kategoriya</th>
        <th>Narx</th>
        <th>Stock</th>
        <th>Status</th>
        <th class="text-right">Amallar</th>
      </tr>
      </thead>
      <tbody>
      @forelse($rows as $row)
        <tr>
          <td>#{{ $row['id'] }}</td>
          <td class="font-medium">{{ $row['title'] }}</td>
          <td>{{ $row['category'] }}</td>
          <td>{{ $row['price'] }}</td>
          <td>{{ $row['stock'] }}</td>
          <td><span class="badge badge-{{ $row['status'] === 'active' ? 'success' : ($row['status'] === 'pending' ? 'warning' : 'danger') }}">{{ $row['status'] }}</span></td>
          <td>
            <div class="flex items-center justify-end gap-1 flex-wrap">
              <form method="POST" action="{{ route('admin.stationery.moderate', $row['id']) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_approved" value="1">
                <button type="submit" class="btn-ghost p-2 rounded-lg" title="Faollashtirish"><i data-lucide="badge-check" class="w-4 h-4"></i></button>
              </form>
              <form method="POST" action="{{ route('admin.stationery.moderate', $row['id']) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_approved" value="2">
                <button type="submit" class="btn-ghost p-2 rounded-lg" title="Rad etish"><i data-lucide="badge-x" class="w-4 h-4"></i></button>
              </form>
              <a href="{{ route('admin.stationery.show', $row['id']) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
              <a href="{{ route('admin.stationery.edit', $row['id']) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></a>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center text-gray-500">Ma'lumot topilmadi</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="p-4 border-t border-gray-100 dark:border-white/5">
    {{ $items->links('a122.partials.pagination') }}
  </div>
</section>
@endsection
