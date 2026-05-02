@extends('a122.layouts.admin')
@section('title', 'Tranzaksiya #' . $transaction->id)
@section('page-title', 'Tranzaksiya tafsilotlari')

@section('content')
<div class="space-y-6">
  @php
    $statusTone = $transaction->status === 'approved' ? 'badge-success' : ($transaction->status === 'rejected' ? 'badge-danger' : 'badge-warning');
    $margin = (float) ($transaction->commissionPrice ?? 0);
    $gross = (float) ($transaction->amount ?? 0);
    $net = (float) ($transaction->netAmount ?? 0);
  @endphp
  <x-a122.page-header back-href="{{ route('admin.transactions.index') }}">
    <x-slot name="heading">#TRX-{{ $transaction->id }}</x-slot>
    <x-slot name="meta">{{ $transaction->seller?->shop_name ?: 'Seller yo‘q' }} · {{ optional($transaction->created_at)->format('d.m.Y H:i') }}</x-slot>
  </x-a122.page-header>

  @if(session('success'))
    <div class="p-alert success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="p-alert danger">{{ session('error') }}</div>
  @endif

  <section class="a122-section">
    <div class="a122-section-body">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="kpi-soft">
          <div class="metric-label">Brutto</div>
          <div class="metric-value text-xl">{{ number_format($gross, 0, '.', ' ') }}</div>
          <div class="metric-meta">UZS</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Komissiya</div>
          <div class="metric-value text-xl">{{ number_format($margin, 0, '.', ' ') }}</div>
          <div class="metric-meta">{{ $transaction->commissionPercent ?: 0 }}%</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Sof summa</div>
          <div class="metric-value text-xl">{{ number_format($net, 0, '.', ' ') }}</div>
          <div class="metric-meta">UZS</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Holat</div>
          <div class="metric-value text-xl">{{ $transaction->status_label }}</div>
          <div class="metric-meta">Joriy payout bosqichi</div>
        </div>
      </div>
    </div>
  </section>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="a122-section xl:col-span-8">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Tranzaksiya ma’lumotlari</div>
          <div class="a122-section-head__meta">Payout yozuvi, seller konteksti va hisob-kitob tarkibi.</div>
        </div>
      </div>
      <div class="a122-section-body">
      <div class="data-grid two">
        <div class="data-kv"><dt>Tranzaksiya ID</dt><dd>#{{ $transaction->id }}</dd></div>
        <div class="data-kv"><dt>Sana</dt><dd>{{ $transaction->created_at ? $transaction->created_at->format('d.m.Y H:i') : '—' }}</dd></div>
        <div class="data-kv">
          <dt>Sotuvchi</dt>
          <dd>
            @if($transaction->seller)
              <a href="{{ route('admin.sellers.show', $transaction->seller) }}" class="font-semibold text-[var(--p-accent)] hover:underline">{{ $transaction->seller->shop_name }}</a>
            @else
              —
            @endif
          </dd>
        </div>
        <div class="data-kv"><dt>Telefon</dt><dd>{{ $transaction->seller?->phone_number ?: '—' }}</dd></div>
        <div class="data-kv"><dt>Brutto miqdor</dt><dd>{{ number_format((float)($transaction->amount ?? 0), 0, '.', ' ') }} UZS</dd></div>
        <div class="data-kv"><dt>Holat</dt><dd><span class="badge {{ $statusTone }}">{{ $transaction->status_label }}</span></dd></div>
        <div class="data-kv"><dt>Komissiya %</dt><dd>{{ $transaction->commissionPercent ?: '—' }}</dd></div>
        <div class="data-kv"><dt>Komissiya summasi</dt><dd>{{ $transaction->commissionPrice ? number_format((float)$transaction->commissionPrice, 0, '.', ' ') . ' UZS' : '—' }}</dd></div>
        <div class="data-kv"><dt>Sof summa</dt><dd>{{ $transaction->netAmount ? number_format((float)$transaction->netAmount, 0, '.', ' ') . ' UZS' : '—' }}</dd></div>
        <div class="data-kv"><dt>Karta</dt><dd>{{ $transaction->card ?: '—' }}</dd></div>
        <div class="data-kv"><dt>Yangilangan</dt><dd>{{ optional($transaction->updated_at)->format('d.m.Y H:i') ?: '—' }}</dd></div>
      </div>

      @if($transaction->comment ?? $transaction->note ?? $transaction->rejected_desc)
        <div class="mt-5">
          <h4 class="text-sm font-bold mb-2">Izoh</h4>
          <div class="content-prose">{{ $transaction->comment ?? $transaction->note ?? $transaction->rejected_desc }}</div>
        </div>
      @endif
      </div>
    </section>

    <section class="a122-section xl:col-span-4">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Seller summary</div>
          <div class="a122-section-head__meta">Shu seller bo‘yicha payout oqimi va operatsion actionlar.</div>
        </div>
      </div>
      <div class="a122-section-body">
      <div class="grid grid-cols-1 gap-3">
        <div class="kpi-soft"><div class="metric-label">Tasdiqlangan tranzaksiyalar</div><div class="metric-value text-xl">{{ number_format((int) $sellerTotals['approved_count']) }}</div></div>
        <div class="kpi-soft"><div class="metric-label">Tasdiqlangan summa</div><div class="metric-value text-xl">{{ number_format((float) $sellerTotals['approved_sum'], 0, '.', ' ') }}</div></div>
        <div class="kpi-soft"><div class="metric-label">Pending summa</div><div class="metric-value text-xl">{{ number_format((float) $sellerTotals['pending_sum'], 0, '.', ' ') }}</div></div>
      </div>

      @if($transaction->status === 'pending')
        <div class="mt-5 space-y-3">
          <form method="POST" action="{{ route('admin.transactions.approve', $transaction) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-primary w-full flex items-center justify-center gap-2">
              <i data-lucide="check-circle" class="w-4 h-4"></i> Tasdiqlash
            </button>
          </form>

          <form method="POST" action="{{ route('admin.transactions.reject', $transaction) }}" onsubmit="return confirm('Tranzaksiyani rad etishga ishonchingiz komilmi?')">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-danger w-full flex items-center justify-center gap-2">
              <i data-lucide="x-circle" class="w-4 h-4"></i> Rad etish
            </button>
          </form>
        </div>
      @endif
      </div>
    </section>
  </div>

  <section class="a122-section">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title">Sellerning yaqin tranzaksiyalari</div>
        <div class="a122-section-head__meta">Shu payout yozuvi qaysi oqim ichida turganini tez ko‘rish uchun.</div>
      </div>
      <div class="a122-section-head__actions">
        <span class="badge badge-info">{{ $sellerTransactions->count() }} ta</span>
      </div>
    </div>
    <div class="a122-section-body">
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>ID</th><th>Miqdor</th><th>Holat</th><th>Sana</th><th></th></tr></thead>
          <tbody>
            @forelse($sellerTransactions as $row)
              <tr>
                <td>#TRX-{{ $row->id }}</td>
                <td>{{ number_format((float) $row->amount, 0, '.', ' ') }} UZS</td>
                <td><span class="badge badge-{{ $row->status_color }}">{{ $row->status_label }}</span></td>
                <td>{{ optional($row->created_at)->format('d.m.Y H:i') }}</td>
                <td class="text-right"><a href="{{ route('admin.transactions.show', $row) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a></td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-sm text-gray-500 py-8">Boshqa tranzaksiyalar topilmadi.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
@endsection
