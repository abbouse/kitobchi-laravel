@extends('a122.layouts.admin')
@section('title', $book->name)
@section('page-title', 'Kitob tafsiloti')

@section('content')
<div class="space-y-6">
  @php
    $bookStatusLabel = $book->status ? 'Faol' : 'Nofaol';
    $approvalLabel = $book->is_approved == 1 ? 'Tasdiqlangan' : ($book->is_approved == 2 ? 'Rad etilgan' : 'Moderatsiyada');
    $discountActive = $book->discountPrice && (!$book->discountExpiresAt || \Illuminate\Support\Carbon::parse($book->discountExpiresAt)->isFuture());
    $recommendationActive = $book->recommended && (!$book->recommendedExpiresAt || \Illuminate\Support\Carbon::parse($book->recommendedExpiresAt)->isFuture());
  @endphp
  <x-a122.page-header back-href="{{ route('admin.books.index') }}">
    <x-slot name="heading">{{ $book->name }}</x-slot>
    <x-slot name="meta">{{ $book->author ?: 'Muallif ko‘rsatilmagan' }} · {{ $book->category?->name_uz ?: 'Kategoriya yo‘q' }}{{ $book->publisher?->name ? ' · '.$book->publisher->name : '' }}</x-slot>
    <x-slot name="actions">
      <a href="{{ route('admin.books.edit', $book) }}" class="btn-p primary"><i class="bi bi-pencil-square"></i> Tahrirlash</a>
    </x-slot>
  </x-a122.page-header>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <div class="xl:col-span-8 space-y-4">
      <section class="a122-section">
        <div class="a122-section-body">
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="kpi-soft">
              <div class="metric-label">Joriy narx</div>
              <div class="metric-value text-xl">{{ number_format((float) $book->price, 0, '.', ' ') }}</div>
              <div class="metric-meta">UZS</div>
            </div>
            <div class="kpi-soft">
              <div class="metric-label">Ombordagi soni</div>
              <div class="metric-value text-xl">{{ number_format((int) ($book->count ?? 0)) }}</div>
              <div class="metric-meta">Dona</div>
            </div>
            <div class="kpi-soft">
              <div class="metric-label">Ko‘rishlar</div>
              <div class="metric-value text-xl">{{ number_format((int) ($book->views ?? 0)) }}</div>
              <div class="metric-meta">Jami trafik</div>
            </div>
            <div class="kpi-soft">
              <div class="metric-label">Sotilgan</div>
              <div class="metric-value text-xl">{{ number_format((int) ($book->totalSales ?? 0)) }}</div>
              <div class="metric-meta">Buyurtma itemlari</div>
            </div>
          </div>
        </div>
      </section>

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
                {{ $approvalLabel }}
              </span>
              <span class="badge {{ $book->status ? 'badge-info' : 'badge-muted' }}">{{ $bookStatusLabel }}</span>
              <span class="badge {{ $book->is_hidden ? 'badge-danger' : 'badge-success' }}">{{ $book->is_hidden ? 'Yashirin' : 'Ko‘rinadi' }}</span>
              @if($book->recommended)
                <span class="badge badge-warning">Recommended</span>
              @endif
              @if($discountActive)
                <span class="badge badge-success">Chegirma faol</span>
              @endif
              @if($recommendationActive)
                <span class="badge badge-info">Recommendation faol</span>
              @endif
            </div>

            <div class="data-grid two">
              <div class="data-kv">
                <dt>Sotuvchi</dt>
                <dd>
                  @if($book->seller)
                    <a href="{{ route('admin.sellers.show', $book->seller) }}" class="font-semibold text-[var(--p-accent)] hover:underline">{{ $book->seller->shop_name }}</a>
                  @else
                    Ichki katalog
                  @endif
                </dd>
              </div>
              <div class="data-kv"><dt>Nashriyot</dt><dd>{{ $book->publisher?->name ?: '—' }}</dd></div>
              <div class="data-kv"><dt>Kategoriya</dt><dd>{{ $book->category?->name_uz ?: '—' }}</dd></div>
              <div class="data-kv"><dt>ISBN</dt><dd>{{ $book->isbn ?: '—' }}</dd></div>
              <div class="data-kv"><dt>Til / yozuv</dt><dd>{{ $book->lang ?: '—' }}{{ $book->langType ? ' · '.$book->langType : '' }}</dd></div>
              <div class="data-kv"><dt>Narx</dt><dd>{{ number_format((float)$book->price, 0, '.', ' ') }} UZS</dd></div>
              <div class="data-kv"><dt>Chegirma narxi</dt><dd>{{ $book->discountPrice ? number_format((float)$book->discountPrice, 0, '.', ' ') . ' UZS' : '—' }}</dd></div>
              <div class="data-kv"><dt>Ombor</dt><dd>{{ number_format((int)($book->count ?? 0)) }} ta</dd></div>
              <div class="data-kv"><dt>Ko‘rishlar</dt><dd>{{ number_format((int)($book->views ?? 0)) }}</dd></div>
              <div class="data-kv"><dt>Muqova / sahifa</dt><dd>{{ $book->coverType ?: '—' }}{{ $book->pages ? ' · '.$book->pages.' sahifa' : '' }}</dd></div>
              <div class="data-kv"><dt>Nashr yili</dt><dd>{{ $book->year ?: '—' }}</dd></div>
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
      <section class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Admin nazorati</div>
            <div class="a122-section-head__meta">Moderatsiya, ko‘rinish va promotion parametrlari.</div>
          </div>
        </div>
        <div class="a122-section-body">
          <div class="data-grid">
            <div class="data-kv"><dt>Moderatsiya</dt><dd>{{ $approvalLabel }}</dd></div>
            <div class="data-kv"><dt>Marketplace holati</dt><dd>{{ $bookStatusLabel }}</dd></div>
            <div class="data-kv"><dt>Visibility</dt><dd>{{ $book->is_hidden ? 'Yashirin' : 'Ochiq' }}</dd></div>
            <div class="data-kv"><dt>Chegirma muddati</dt><dd>{{ optional($book->discountExpiresAt)->format('d.m.Y H:i') ?: '—' }}</dd></div>
            <div class="data-kv"><dt>Recommendation muddati</dt><dd>{{ optional($book->recommendedExpiresAt)->format('d.m.Y H:i') ?: '—' }}</dd></div>
            <div class="data-kv"><dt>Media soni</dt><dd>{{ $images->count() }} ta</dd></div>
          </div>
        </div>
      </section>

      <section class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Texnik ma’lumot</div>
            <div class="a122-section-head__meta">Katalog sifati va texnik atributlar.</div>
          </div>
        </div>
        <div class="a122-section-body">
          <dl class="space-y-3">
            <div><dt class="metric-label">Til</dt><dd class="font-semibold mt-1">{{ $book->lang ?: '—' }}</dd></div>
            <div><dt class="metric-label">Yozuv turi</dt><dd class="font-semibold mt-1">{{ $book->langType ?: '—' }}</dd></div>
            <div><dt class="metric-label">Muqova</dt><dd class="font-semibold mt-1">{{ $book->coverType ?: '—' }}</dd></div>
            <div><dt class="metric-label">Sahifalar</dt><dd class="font-semibold mt-1">{{ $book->pages ?: '—' }}</dd></div>
            <div><dt class="metric-label">Yil</dt><dd class="font-semibold mt-1">{{ $book->year ?: '—' }}</dd></div>
            <div><dt class="metric-label">Kangaroo listing bahosi</dt><dd class="font-semibold mt-1">{{ $book->kangaroo_listing_score ?: '—' }}</dd></div>
            <div><dt class="metric-label">AI sharh bahosi</dt><dd class="font-semibold mt-1">{{ $book->ugc_aggregate_score ?: '—' }}</dd></div>
            <div><dt class="metric-label">Yaratilgan</dt><dd class="font-semibold mt-1">{{ optional($book->created_at)->format('d.m.Y H:i') ?: '—' }}</dd></div>
            <div><dt class="metric-label">Yangilangan</dt><dd class="font-semibold mt-1">{{ optional($book->updated_at)->format('d.m.Y H:i') ?: '—' }}</dd></div>
          </dl>
        </div>
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
