@extends('a122.layouts.admin')
@section('title', 'Kitoblar')
@section('page-title', 'Kitoblar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Catalog" title="Kitoblar" subtitle="{{ $books->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 24rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Kitob, muallif, do'kon yoki kategoriya" class="form-control">
    </form>
    <a href="{{ route('admin.books.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  <div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card label="Moderatsiyada" :value="number_format($counts['pending'] ?? 0)" icon="hourglass-split" tone="warning" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card label="Faol kitoblar" :value="number_format($counts['active'] ?? 0)" icon="patch-check" tone="success" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card label="Rad etilgan" :value="number_format($counts['rejected'] ?? 0)" icon="x-octagon" tone="danger" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card label="Jami katalog" :value="number_format($counts['all'] ?? 0)" icon="collection" tone="dark" />
    </div>
  </div>

  <div class="kc-filter-card">
    <div class="d-flex flex-column gap-3">
      <div class="d-flex flex-column flex-xl-row gap-3 align-items-xl-center justify-content-between">
        <div>
          <div class="fw-semibold">Holat bo‘yicha filter</div>
          <div class="small text-secondary">Avval moderatsiyadagi kitoblar ochiladi. Shu blokdan faol va rad etilgan yozuvlarga ham tez o‘tasiz.</div>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-center">
          <input type="hidden" name="search" value="{{ request('search') }}">
          <label for="books-tab-filter" class="small text-secondary mb-0">Ko‘rsatish:</label>
          <select id="books-tab-filter" name="tab" class="form-select" onchange="this.form.submit()" style="min-width: 220px;">
            <option value="pending" @selected($tab === 'pending')>Moderatsiyadagi kitoblar</option>
            <option value="active" @selected($tab === 'active')>Faol kitoblar</option>
            <option value="rejected" @selected($tab === 'rejected')>Rad etilgan kitoblar</option>
            <option value="all" @selected($tab === 'all')>Barcha kitoblar</option>
          </select>
        </form>
      </div>

      <div class="nav nav-pills flex-wrap gap-2">
        @foreach([
          'pending' => ['Moderatsiya', $counts['pending'] ?? 0, 'Avval ko‘rib chiqilishi kerak bo‘lganlar'],
          'active' => ['Faol', $counts['active'] ?? 0, 'Xaridorlarga ko‘rinayotgan kitoblar'],
          'rejected' => ['Rad etilgan', $counts['rejected'] ?? 0, 'Qayta ko‘rib chiqish kerak bo‘lishi mumkin'],
          'all' => ['Barchasi', $counts['all'] ?? 0, 'Umumiy katalog'],
        ] as $key => [$label, $count, $meta])
          <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
            <span class="d-flex flex-column align-items-start">
              <span>{{ $label }}</span>
              <span class="small {{ $tab === $key ? 'text-white-50' : 'text-secondary' }}">{{ $meta }}</span>
            </span>
            <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($count) }}</span>
          </a>
        @endforeach
      </div>
    </div>
    @if($tab === 'pending')
      <div class="small text-secondary mt-3">
        Hozir birinchi bo‘lib moderatsiyaga tushgan kitoblar ko‘rsatilmoqda. Shu yerning o‘zidan tasdiqlash yoki rad etish mumkin.
      </div>
    @endif
  </div>

  <x-admin.section-card title="Kitoblar jadvali" :meta="$books->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Kitob</th>
            <th>Do‘kon</th>
            <th>Kategoriya</th>
            <th>Narx</th>
            <th>Stok</th>
            <th>Status</th>
            <th>Yangilangan</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $row)
            @php
              $statusClass = $row['status'] === 'active'
                ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis'
                : ($row['status'] === 'pending'
                  ? 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis'
                  : 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis');
            @endphp
            <tr>
              <td class="text-secondary">#{{ $row['id'] }}</td>
              <td>
                <div class="fw-semibold">{{ $row['title'] }}</div>
                <div class="small text-secondary">{{ $row['author'] }}</div>
              </td>
              <td>{{ $row['seller'] }}</td>
              <td>{{ $row['category'] }}</td>
              <td class="fw-semibold">{{ $row['price'] }}</td>
              <td>{{ $row['stock'] }}</td>
              <td><span class="badge rounded-pill {{ $statusClass }}">{{ $row['status_label'] }}</span></td>
              <td class="small text-secondary">{{ $row['updated_at'] }}</td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                  <form method="POST" action="{{ route('admin.books.moderate', $row['id']) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_approved" value="1">
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Faollashtirish">
                      <i class="bi bi-patch-check"></i>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.books.moderate', $row['id']) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_approved" value="2">
                    <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </form>
                  <a href="{{ route('admin.books.show', $row['id']) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('admin.books.edit', $row['id']) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center py-5 text-secondary">Bu filtr bo‘yicha kitob topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if(isset($books) && method_exists($books, 'links'))
    <div>{{ $books->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
