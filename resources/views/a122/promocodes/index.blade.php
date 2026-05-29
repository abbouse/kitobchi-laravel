@extends('a122.layouts.admin')
@section('title', 'Promokodlar')
@section('page-title', 'Promokodlar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Commerce" title="Promokodlar" subtitle="{{ $promocodes->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 24rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Kod yoki ID" class="form-control">
    </form>
    <a href="{{ route('admin.promocodes.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      @foreach([['all', 'Barchasi'], ['active', 'Faol'], ['expired', 'Muddati o‘tgan']] as [$key, $label])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
          {{ $label }}
          <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($counts[$key] ?? 0) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <x-admin.section-card title="Promokodlar jadvali" :meta="$promocodes->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Kod</th>
            <th>Tur</th>
            <th>Miqdor</th>
            <th>Min. buyurtma</th>
            <th>Limit</th>
            <th>Muddat</th>
            <th>Status</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($promocodes as $promo)
            @php
              $expired = $promo->expires_at <= now();
              $full = $promo->usesLimit > 0 && $promo->usedCount >= $promo->usesLimit;
              $isActive = $promo->status && !$expired && !$full;
            @endphp
            <tr>
              <td class="text-secondary">#{{ $promo->id }}</td>
              <td><code class="kc-inline-code">{{ $promo->code }}</code></td>
              <td>
                <span class="badge rounded-pill {{ $promo->type === 'percent' ? 'text-bg-info-subtle border border-info-subtle text-info-emphasis' : 'text-bg-primary-subtle border border-primary-subtle text-primary-emphasis' }}">
                  {{ $promo->type === 'percent' ? 'Foiz' : 'Miqdor' }}
                </span>
              </td>
              <td class="fw-semibold">{{ $promo->type === 'percent' ? $promo->amount . '%' : number_format($promo->amount) . ' UZS' }}</td>
              <td class="text-secondary">{{ $promo->min_order_amount > 0 ? number_format($promo->min_order_amount) . ' UZS' : '—' }}</td>
              <td>
                @if($promo->usesLimit > 0)
                  <span class="{{ $full ? 'text-danger' : '' }}">{{ $promo->usedCount }}</span> / {{ $promo->usesLimit }}
                @else
                  <span class="text-secondary">{{ $promo->usedCount }} / ∞</span>
                @endif
              </td>
              <td class="{{ $expired ? 'text-danger' : 'text-secondary' }} text-nowrap">{{ \Carbon\Carbon::parse($promo->expires_at)->format('d.m.Y H:i') }}</td>
              <td>
                @if($isActive)
                  <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">Faol</span>
                @else
                  <span class="badge rounded-pill text-bg-secondary">Nofaol</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="{{ route('admin.promocodes.show', $promo) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('admin.promocodes.edit', $promo) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.promocodes.destroy', $promo) }}" onsubmit="return confirm('O‘chirilsinmi?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center py-5 text-secondary">Promokod topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($promocodes->hasPages())
    <div>{{ $promocodes->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
