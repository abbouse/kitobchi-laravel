@extends('a122.layouts.admin')
@section('title', 'Qidiruv tarixi')
@section('page-title', 'Qidiruv tarixi')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Analytics" title="Qidiruv tarixi" subtitle="{{ $histories->total() }} ta yozuv" />

  <div class="kc-filter-card">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-12 col-xl">
        <label class="form-label">Qidiruv</label>
        <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Matn, natija, user yoki telefon">
      </div>
      <div class="col-12 col-md-4 col-xl-2">
        <label class="form-label">Turi</label>
        <select name="type" class="form-select">
          <option value="">Barchasi</option>
          @foreach(['book', 'author', 'stationery', 'tag'] as $type)
            <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-4 col-xl-2">
        <label class="form-label">Kim</label>
        <select name="scope" class="form-select">
          <option value="">Barchasi</option>
          <option value="user" {{ request('scope') === 'user' ? 'selected' : '' }}>User</option>
          <option value="guest" {{ request('scope') === 'guest' ? 'selected' : '' }}>Guest</option>
        </select>
      </div>
      <div class="col-12 col-md-4 col-xl-2">
        <label class="form-label">Draft</label>
        <select name="draft" class="form-select">
          <option value="">Barchasi</option>
          <option value="0" {{ request('draft') === '0' ? 'selected' : '' }}>Yo‘q</option>
          <option value="1" {{ request('draft') === '1' ? 'selected' : '' }}>Ha</option>
        </select>
      </div>
      <div class="col-12 col-xl-auto d-flex gap-2">
        <button type="submit" class="btn btn-primary">Filtrlash</button>
        <a href="{{ route('admin.search-history.index') }}" class="btn btn-light border">Tozalash</a>
      </div>
    </form>
  </div>

  <x-admin.section-card title="Qidiruvlar jadvali" :meta="$histories->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0 data-table">
        <thead>
          <tr>
            <th>Vaqt</th>
            <th>Qidiruv matni</th>
            <th>Natija</th>
            <th>Turi</th>
            <th>Kim qidirgan</th>
            <th>Session</th>
            <th>Draft</th>
          </tr>
        </thead>
        <tbody>
          @forelse($histories as $item)
            <tr>
              <td class="text-secondary text-nowrap">{{ $item->updated_at?->format('d.m.Y H:i:s') }}</td>
              <td class="fw-semibold">{{ $item->text }}</td>
              <td>
                <div>{{ $item->result_name ?: '—' }}</div>
                @if($item->result_count)
                  <div class="small text-secondary">Natijalar: {{ $item->result_count }}</div>
                @endif
              </td>
              <td><span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis">{{ $item->result_type ?: '—' }}</span></td>
              <td>
                @if($item->user)
                  <div class="fw-semibold">{{ trim(($item->user->name ?? '') . ' ' . ($item->user->lastname ?? '')) ?: 'User #' . $item->user->id }}</div>
                  <div class="small text-secondary">
                    @if($item->user->username) @{{ $item->user->username }} · @endif
                    {{ $item->user->phone ?: 'ID: ' . $item->user->id }}
                  </div>
                @else
                  <span class="badge rounded-pill text-bg-warning">Guest</span>
                @endif
              </td>
              <td class="text-truncate" style="max-width: 12rem;"><code class="kc-inline-code">{{ $item->session_id ?: '—' }}</code></td>
              <td>
                <span class="badge rounded-pill text-bg-{{ $item->is_draft ? 'warning' : 'success' }}">{{ $item->is_draft ? 'Ha' : 'Yo‘q' }}</span>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center py-5 text-secondary">Qidiruv tarixi topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($histories->hasPages())
    <div>{{ $histories->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
