@extends('a122.layouts.admin')
@section('title', 'Reels')
@section('page-title', 'Reels')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Content" title="Reels" subtitle="{{ $reels->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 24rem;">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Sarlavha yoki tavsif" class="form-control">
    </form>
    <a href="{{ route('admin.reels.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  @if(session('success'))
    <div class="alert alert-success border-0 mb-0">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger border-0 mb-0">{{ session('error') }}</div>
  @endif

  <x-admin.section-card title="Reels jadvali" :meta="$reels->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Sarlavha</th>
            <th>Tavsif</th>
            <th>Tartib</th>
            <th>Videolar</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reels as $reel)
            <tr>
              <td class="text-secondary">#{{ $reel->id }}</td>
              <td class="fw-semibold">{{ $reel->title }}</td>
              <td class="text-secondary text-truncate" style="max-width: 22rem;">{{ $reel->description ?: '—' }}</td>
              <td>{{ $reel->order }}</td>
              <td><span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis">{{ $reel->items_count ?? $reel->items->count() }}</span></td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="{{ route('admin.reels.show', $reel) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('admin.reels.edit', $reel) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.reels.destroy', $reel) }}" onsubmit="return confirm('Bu reelni o‘chirishga ishonchingiz komilmi?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center py-5 text-secondary">Reel topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($reels->hasPages())
    <div>{{ $reels->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
