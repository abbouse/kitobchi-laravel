@extends('a122.layouts.admin')
@section('title', $item->name)
@section('page-title', 'Kanstovar tafsiloti')

@section('content')
<div class="space-y-6">
  <x-a122.page-header back-href="{{ route('admin.stationery.index') }}">
    <x-slot name="heading">{{ $item->name }}</x-slot>
    <x-slot name="meta">{{ $item->category?->name_uz ?: 'Kategoriya yo‘q' }} · {{ $item->material ?: 'Material ko‘rsatilmagan' }}</x-slot>
    <x-slot name="actions">
      <a href="{{ route('admin.stationery.edit', $item->id) }}" class="btn-p primary"><i class="bi bi-pencil-square"></i> Tahrirlash</a>
    </x-slot>
  </x-a122.page-header>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="card p-5 xl:col-span-8">
      <div class="grid grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)] gap-5">
        <div class="space-y-3">
          <div class="rounded-[1.4rem] overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] min-h-[320px] flex items-center justify-center">
            @if($images->isNotEmpty())
              <img src="{{ $images->first() }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
            @else
              <div class="text-center text-[var(--p-hint)]">
                <i class="bi bi-pencil-square text-4xl"></i>
                <div class="mt-2 text-sm">Rasm biriktirilmagan</div>
              </div>
            @endif
          </div>
          @if($images->count() > 1)
            <div class="grid grid-cols-4 gap-2">
              @foreach($images->slice(0, 4) as $image)
                <div class="rounded-2xl overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] aspect-square">
                  <img src="{{ $image }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                </div>
              @endforeach
            </div>
          @endif
        </div>

        <div class="space-y-4">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="badge {{ $item->is_approved == 1 ? 'badge-success' : ($item->is_approved == 2 ? 'badge-danger' : 'badge-warning') }}">
              {{ $item->is_approved == 1 ? 'Tasdiqlangan' : ($item->is_approved == 2 ? 'Rad etilgan' : 'Moderatsiyada') }}
            </span>
            <span class="badge {{ $item->status ? 'badge-info' : 'badge-muted' }}">{{ $item->status ? 'Faol' : 'Nofaol' }}</span>
            @if($item->recommended)
              <span class="badge badge-warning">Recommended</span>
            @endif
          </div>

          <div class="data-grid two">
            <div class="data-kv"><dt>Narx</dt><dd>{{ number_format((float)$item->price, 0, '.', ' ') }} UZS</dd></div>
            <div class="data-kv"><dt>Chegirma</dt><dd>{{ $item->discount_price ? number_format((float)$item->discount_price, 0, '.', ' ') . ' UZS' : '—' }}</dd></div>
            <div class="data-kv"><dt>Discount %</dt><dd>{{ $item->discount_percent ?: 0 }}%</dd></div>
            <div class="data-kv"><dt>Ombor</dt><dd>{{ number_format((int)($item->stock ?? 0)) }}</dd></div>
            <div class="data-kv"><dt>Ko‘rishlar</dt><dd>{{ number_format((int)($item->views ?? 0)) }}</dd></div>
            <div class="data-kv"><dt>Sotuvchi</dt><dd>{{ $item->seller?->shop_name ?: 'Ichki katalog' }}</dd></div>
          </div>

          <div class="content-prose">{{ $item->description ?: 'Mahsulot uchun tavsif kiritilmagan.' }}</div>
        </div>
      </div>
    </section>

    <section class="xl:col-span-4 space-y-4">
      <div class="card p-5">
        <h3 class="text-lg font-black mb-4">KPI</h3>
        <div class="grid grid-cols-2 gap-3">
          <div class="kpi-soft"><div class="metric-label">Sotilgan</div><div class="metric-value text-xl">{{ number_format((int)($item->totalSales ?? 0)) }}</div></div>
          <div class="kpi-soft"><div class="metric-label">Mijozlar</div><div class="metric-value text-xl">{{ number_format((int)($item->totalClients ?? 0)) }}</div></div>
          <div class="kpi-soft"><div class="metric-label">Daromad</div><div class="metric-value text-xl">{{ number_format((float)($item->totalRevenue ?? 0), 0, '.', ' ') }}</div></div>
          <div class="kpi-soft"><div class="metric-label">Variantlar</div><div class="metric-value text-xl">{{ $item->variants?->count() ?? 0 }}</div></div>
        </div>
      </div>

      <div class="card p-5">
        <h3 class="text-lg font-black mb-4">Variantlar</h3>
        <div class="space-y-3">
          @forelse($item->variants ?? [] as $variant)
            <div class="data-kv">
              <dt>{{ $variant->name ?? ('Variant #'.$variant->id) }}</dt>
              <dd>{{ number_format((float) ($variant->price ?? 0), 0, '.', ' ') }} UZS · stock {{ number_format((int) ($variant->stock ?? 0)) }}</dd>
            </div>
          @empty
            <div class="text-sm text-gray-500">Variantlar mavjud emas.</div>
          @endforelse
        </div>
      </div>
    </section>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="card p-5 xl:col-span-6">
      <h3 class="text-lg font-black mb-4">So‘nggi buyurtmalar</h3>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>ID</th><th>Mijoz</th><th>Summa</th><th>To‘lov</th><th>Sana</th><th></th></tr></thead>
          <tbody>
            @forelse($recentOrders as $order)
              <tr>
                <td>#ORD-{{ $order->id }}</td>
                <td>{{ $order->user?->full_name ?: 'Mehmon' }}</td>
                <td>{{ number_format((float) $order->amount, 0, '.', ' ') }} UZS</td>
                <td>{{ (int) $order->paymentStatus === 2 ? 'To‘langan' : 'Jarayonda' }}</td>
                <td>{{ optional($order->created_at)->format('d.m.Y H:i') }}</td>
                <td class="text-right"><a href="{{ route('admin.orders.show', $order) }}" class="btn-ghost p-2 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a></td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-sm text-gray-500 py-8">Buyurtmalar topilmadi.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="card p-5 xl:col-span-6">
      <h3 class="text-lg font-black mb-4">Seller oqimi</h3>
      <div class="space-y-3">
        @forelse($sellerOrders as $sellerOrder)
          <div class="data-kv">
            <dt>#SELL-{{ $sellerOrder->id }} · {{ optional($sellerOrder->created_at)->format('d.m.Y') }}</dt>
            <dd>{{ $sellerOrder->client?->full_name ?: 'Mijoz yo‘q' }}</dd>
            <div class="mt-2 text-sm text-[var(--p-muted)]">{{ number_format((float) $sellerOrder->amount, 0, '.', ' ') }} UZS · {{ $sellerOrder->status ?: 'status yo‘q' }}</div>
          </div>
        @empty
          <div class="text-sm text-gray-500">Seller order oqimi topilmadi.</div>
        @endforelse
      </div>
    </section>
  </div>
</div>
@endsection
