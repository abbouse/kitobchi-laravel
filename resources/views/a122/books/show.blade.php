@extends('a122.layouts.admin')
@section('title', $book->name)
@section('page-title', 'Kitob tafsiloti')

@section('content')
<div class="space-y-6">
  <x-a122.page-header back-href="{{ route('admin.books.index') }}">
    <x-slot name="heading">{{ $book->name }}</x-slot>
    <x-slot name="meta">{{ $book->author ?: 'Muallif ko‘rsatilmagan' }} · {{ $book->category?->name_uz ?: 'Kategoriya yo‘q' }}</x-slot>
    <x-slot name="actions">
      <a href="{{ route('admin.books.edit', $book) }}" class="btn-p primary"><i class="bi bi-pencil-square"></i> Tahrirlash</a>
    </x-slot>
  </x-a122.page-header>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <div class="xl:col-span-8 space-y-4">
      <section class="card p-5">
        <div class="grid grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)] gap-5">
          <div class="space-y-3">
            <div class="rounded-[1.4rem] overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] min-h-[320px] flex items-center justify-center">
              @if($images->isNotEmpty())
                <img src="{{ $images->first() }}" alt="{{ $book->name }}" class="w-full h-full object-cover">
              @else
                <div class="text-center text-[var(--p-hint)]">
                  <i class="bi bi-book text-4xl"></i>
                  <div class="mt-2 text-sm">Rasm biriktirilmagan</div>
                </div>
              @endif
            </div>
            @if($images->count() > 1)
              <div class="grid grid-cols-4 gap-2">
                @foreach($images->slice(0, 4) as $image)
                  <div class="rounded-2xl overflow-hidden border border-[var(--p-border)] bg-[var(--p-elevated)] aspect-square">
                    <img src="{{ $image }}" alt="{{ $book->name }}" class="w-full h-full object-cover">
                  </div>
                @endforeach
              </div>
            @endif
          </div>
          <div class="space-y-4">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="badge {{ $book->is_approved == 1 ? 'badge-success' : ($book->is_approved == 2 ? 'badge-danger' : 'badge-warning') }}">
                {{ $book->is_approved == 1 ? 'Tasdiqlangan' : ($book->is_approved == 2 ? 'Rad etilgan' : 'Moderatsiyada') }}
              </span>
              <span class="badge {{ $book->status ? 'badge-info' : 'badge-muted' }}">{{ $book->status ? 'Faol' : 'Nofaol' }}</span>
              @if($book->recommended)
                <span class="badge badge-warning">Recommended</span>
              @endif
            </div>

            <div class="data-grid two">
              <div class="data-kv"><dt>Sotuvchi</dt><dd>{{ $book->seller?->shop_name ?: 'Ichki katalog' }}</dd></div>
              <div class="data-kv"><dt>Kategoriya</dt><dd>{{ $book->category?->name_uz ?: '—' }}</dd></div>
              <div class="data-kv"><dt>Narx</dt><dd>{{ number_format((float)$book->price, 0, '.', ' ') }} UZS</dd></div>
              <div class="data-kv"><dt>Chegirma narxi</dt><dd>{{ $book->discountPrice ? number_format((float)$book->discountPrice, 0, '.', ' ') . ' UZS' : '—' }}</dd></div>
              <div class="data-kv"><dt>Ombor</dt><dd>{{ number_format((int)($book->count ?? 0)) }} ta</dd></div>
              <div class="data-kv"><dt>Ko‘rishlar</dt><dd>{{ number_format((int)($book->views ?? 0)) }}</dd></div>
            </div>

            <div class="content-prose">
              {{ $book->description ?: 'Kitob uchun batafsil tavsif kiritilmagan.' }}
            </div>
          </div>
        </div>
      </section>

      <section class="card p-5">
        <h3 class="text-lg font-black mb-4">Savdo va buyurtma analitikasi</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="kpi-soft"><div class="metric-label">Sotilgan</div><div class="metric-value text-xl">{{ number_format((int)($book->totalSales ?? 0)) }}</div></div>
          <div class="kpi-soft"><div class="metric-label">Mijozlar</div><div class="metric-value text-xl">{{ number_format((int)($book->totalClients ?? 0)) }}</div></div>
          <div class="kpi-soft"><div class="metric-label">Daromad</div><div class="metric-value text-xl">{{ number_format((float)($book->totalRevenue ?? 0), 0, '.', ' ') }}</div></div>
          <div class="kpi-soft"><div class="metric-label">Hafta savdosi</div><div class="metric-value text-xl">{{ number_format((int)($book->totalSalesWeek ?? 0)) }}</div></div>
        </div>

        <div class="table-wrap mt-4">
          <table class="tbl">
            <thead><tr><th>So‘nggi buyurtmalar</th><th>Mijoz</th><th>Summa</th><th>Status</th><th>Sana</th><th></th></tr></thead>
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
                <tr><td colspan="6" class="text-center text-sm text-gray-500 py-8">Bu kitob ishtirok etgan buyurtmalar topilmadi.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <div class="xl:col-span-4 space-y-4">
      <section class="card p-5">
        <h3 class="text-lg font-black">Texnik ma’lumot</h3>
        <dl class="space-y-3 mt-4">
          <div><dt class="metric-label">Til</dt><dd class="font-semibold mt-1">{{ $book->lang ?: '—' }}</dd></div>
          <div><dt class="metric-label">Muqova</dt><dd class="font-semibold mt-1">{{ $book->coverType ?: '—' }}</dd></div>
          <div><dt class="metric-label">Sahifalar</dt><dd class="font-semibold mt-1">{{ $book->pages ?: '—' }}</dd></div>
          <div><dt class="metric-label">Yil</dt><dd class="font-semibold mt-1">{{ $book->year ?: '—' }}</dd></div>
          <div><dt class="metric-label">Kangaroo score</dt><dd class="font-semibold mt-1">{{ $book->kangaroo_listing_score ?: '—' }}</dd></div>
          <div><dt class="metric-label">UGC score</dt><dd class="font-semibold mt-1">{{ $book->ugc_aggregate_score ?: '—' }}</dd></div>
          <div><dt class="metric-label">Yaratilgan</dt><dd class="font-semibold mt-1">{{ optional($book->created_at)->format('d.m.Y H:i') ?: '—' }}</dd></div>
        </dl>
      </section>

      <section class="card p-5">
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
</div>
@endsection
