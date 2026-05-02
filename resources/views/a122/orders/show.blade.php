@extends('a122.layouts.admin')
@section('title', 'Buyurtma #' . $order->id)
@section('page-title', 'Buyurtma tafsiloti')

@section('content')
<div class="space-y-6">
  @php
    $orderStatusLabel = match($order->status) {
      'A' => 'Kutilmoqda',
      'P' => 'Qadoqlanmoqda',
      'B' => "Yo'lda",
      'C' => 'Yetkazildi',
      'F' => 'Bekor qilingan',
      default => $order->status ?: '—',
    };
    $paymentLabel = match ((int) $order->paymentStatus) {
      2 => 'Karta orqali to‘langan',
      1 => 'Karta orqali, tasdiq kutilmoqda',
      0 => 'Naqd to‘lov',
      default => 'Aniqlanmagan',
    };
    $paymentMethodLabel = match ((int) $order->paymentStatus) {
      2, 1 => 'Karta / Payme',
      0 => 'Naqd',
      default => 'Boshqa',
    };
    $deliveryTypeLabel = match ((string) ($order->deliveryType ?? '')) {
      'pickup' => "Do'kondan olib ketish",
      '' => 'Yetkazib berish',
      default => (string) $order->deliveryType,
    };
    $primaryAddress = collect($order->address ?? [])->first() ?? [];
    $fullAddress = $primaryAddress['fullAddress']
      ?? $primaryAddress['branch_address']
      ?? $order->recipient_address
      ?? 'Manzil kiritilmagan';
    $branchLabel = !empty($primaryAddress['location_id'])
      ? ('Filial #'.$primaryAddress['location_id'])
      : null;
    $isGiftToOther = (bool) ($order->is_gift_to_other ?? false);
    $packagingPrice = (int) ($order->packaging_price ?? 0);
  @endphp
  <x-a122.page-header back-href="{{ route('admin.orders.index') }}">
    <x-slot name="heading">#ORD-{{ $order->id }}</x-slot>
    <x-slot name="meta">{{ $order->user?->full_name ?: 'Mehmon foydalanuvchi' }} · {{ optional($order->created_at)->format('d.m.Y H:i') }}</x-slot>
    <x-slot name="actions">
      <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Buyurtmani bekor qilasizmi?')">
        @csrf
        <button class="btn-p danger"><i class="bi bi-x-circle"></i> Bekor qilish</button>
      </form>
    </x-slot>
  </x-a122.page-header>

  <section class="a122-section">
    <div class="a122-section-body">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="kpi-soft">
          <div class="metric-label">Jami summa</div>
          <div class="metric-value text-xl">{{ number_format((float)$order->amount, 0, '.', ' ') }}</div>
          <div class="metric-meta">UZS</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Mahsulotlar</div>
          <div class="metric-value text-xl">{{ number_format($summary['items_count']) }}</div>
          <div class="metric-meta">Buyurtma itemlari</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">Status</div>
          <div class="metric-value text-xl">{{ $orderStatusLabel }}</div>
          <div class="metric-meta">Operatsion bosqich</div>
        </div>
        <div class="kpi-soft">
          <div class="metric-label">To‘lov</div>
          <div class="metric-value text-xl">{{ $paymentMethodLabel }}</div>
          <div class="metric-meta">{{ $paymentLabel }}</div>
        </div>
      </div>
    </div>
  </section>

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <section class="a122-section xl:col-span-8">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Buyurtma tarkibi</div>
          <div class="a122-section-head__meta">Buyurtmadagi barcha mahsulotlar, soni va narx bo‘yicha tafsilotlar.</div>
        </div>
        <div class="a122-section-head__actions">
          <span class="badge badge-info">{{ $summary['items_count'] }} ta mahsulot</span>
        </div>
      </div>
      <div class="a122-section-body">
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Mahsulot</th><th>Tip</th><th>Soni</th><th>Narx</th><th>Jami</th></tr></thead>
          <tbody>
            @foreach($items as $it)
              @php
                $qty = (int)($it['count_item'] ?? $it['count'] ?? 1);
                $price = (float)($it['item_price'] ?? $it['price'] ?? 0);
              @endphp
              <tr>
                <td>
                  @if(($it['type'] ?? 'book') === 'stationery' && !empty($it['product']))
                    <a href="{{ route('admin.stationery.show', $it['product']) }}" class="font-semibold text-[var(--p-accent)] hover:underline">
                      {{ $it['name'] ?? ($it['product']->name ?? '—') }}
                    </a>
                  @elseif(!empty($it['product']))
                    <a href="{{ route('admin.books.show', $it['product']) }}" class="font-semibold text-[var(--p-accent)] hover:underline">
                      {{ $it['name'] ?? ($it['product']->name ?? '—') }}
                    </a>
                  @else
                    {{ $it['name'] ?? '—' }}
                  @endif
                  @if(!empty($it['product']?->seller))
                    <div class="text-xs text-[var(--p-hint)] mt-1">
                      Do‘kon:
                      <a href="{{ route('admin.sellers.show', $it['product']->seller) }}" class="text-[var(--p-accent)] hover:underline">{{ $it['product']->seller->shop_name }}</a>
                    </div>
                  @endif
                </td>
                <td>{{ $it['type'] ?? 'book' }}</td>
                <td>{{ $qty }}</td>
                <td>{{ number_format($price, 0, '.', ' ') }} UZS</td>
                <td>{{ number_format($qty * $price, 0, '.', ' ') }} UZS</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      </div>
    </section>

    <section class="xl:col-span-4 space-y-4">
      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Holat boshqaruvi</div>
            <div class="a122-section-head__meta">Buyurtma statusini shu yerdan yangilash mumkin.</div>
          </div>
        </div>
        <div class="a122-section-body">
        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="space-y-3">
          @csrf
          @method('PATCH')
          <select name="status" class="p-form-control">
            <option value="A" @selected($order->status==='A')>Kutilmoqda</option>
            <option value="P" @selected($order->status==='P')>Qadoqlanmoqda</option>
            <option value="B" @selected($order->status==='B')>Yo'lda</option>
            <option value="C" @selected($order->status==='C')>Yetkazildi</option>
            <option value="F" @selected($order->status==='F')>Bekor</option>
          </select>
          <button class="btn-p primary w-full"><i class="bi bi-arrow-repeat"></i> Statusni yangilash</button>
        </form>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Moliyaviy xulosa</div>
            <div class="a122-section-head__meta">Subtotal, delivery, chegirma va yakuniy summa.</div>
          </div>
        </div>
      <div class="a122-section-body">
        <dl class="space-y-3">
          <div class="flex justify-between gap-3"><dt class="metric-label">Subtotal</dt><dd class="font-semibold">{{ number_format($summary['subtotal'], 0, '.', ' ') }} UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Yetkazish</dt><dd class="font-semibold">{{ number_format($summary['delivery'], 0, '.', ' ') }} UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Qadoqlash</dt><dd class="font-semibold">{{ number_format($packagingPrice, 0, '.', ' ') }} UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Chegirma</dt><dd class="font-semibold">{{ number_format($summary['discount'], 0, '.', ' ') }} UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Cashback</dt><dd class="font-semibold">{{ number_format($summary['cashback'], 0, '.', ' ') }} UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Promokod</dt><dd class="font-semibold">{{ $order->promocode ?: '—' }}</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Gift sertifikat</dt><dd class="font-semibold">{{ $order->gift_certificate_id ? '#'.$order->gift_certificate_id : '—' }}</dd></div>
          <div class="flex justify-between gap-3 border-t border-[var(--p-border)] pt-3"><dt class="font-bold">Jami</dt><dd class="font-black">{{ number_format((float)$order->amount, 0, '.', ' ') }} UZS</dd></div>
        </dl>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Yetkazish va manzil</div>
            <div class="a122-section-head__meta">Buyurtmaning fulfillment turi, filial va qabul manzili.</div>
          </div>
        </div>
        <div class="a122-section-body">
          <div class="space-y-3 text-sm">
            <div><span class="metric-label">Fulfillment turi</span><div class="font-semibold mt-1">{{ $deliveryTypeLabel }}</div></div>
            <div><span class="metric-label">Manzil</span><div class="font-semibold mt-1">{{ $fullAddress }}</div></div>
            @if($branchLabel)
              <div><span class="metric-label">Filial</span><div class="font-semibold mt-1">{{ $branchLabel }}{{ !empty($primaryAddress['branch_is_main']) ? ' · asosiy filial' : '' }}</div></div>
            @endif
            @if(!empty($primaryAddress['lat']) && !empty($primaryAddress['lon']))
              <div><span class="metric-label">Koordinata</span><div class="font-semibold mt-1">{{ $primaryAddress['lat'] }}, {{ $primaryAddress['lon'] }}</div></div>
            @endif
            <div><span class="metric-label">Recipient address</span><div class="font-semibold mt-1">{{ $order->recipient_address ?: '—' }}</div></div>
          </div>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Do‘konlar va seller oqimi</div>
            <div class="a122-section-head__meta">Buyurtmada ishtirok etgan sellerlar va ularning seller-order yozuvlari.</div>
          </div>
        </div>
        <div class="a122-section-body">
          @if($sellerOrders->isEmpty())
            <div class="text-sm text-gray-500">Seller orderlar hali yaratilmagan.</div>
          @else
            <div class="space-y-3">
              @foreach($sellerOrders as $sellerOrder)
                <div class="data-kv">
                  <dt class="flex items-center justify-between gap-3">
                    <span>
                      <a href="{{ route('admin.seller-orders.show', $sellerOrder) }}" class="font-semibold text-[var(--p-accent)] hover:underline">#SELL-{{ $sellerOrder->id }}</a>
                    </span>
                    <span class="text-xs text-[var(--p-hint)]">{{ $sellerOrder->created_at?->format('d.m.Y H:i') }}</span>
                  </dt>
                  <dd class="mt-1">
                    @if($sellerOrder->seller)
                      <a href="{{ route('admin.sellers.show', $sellerOrder->seller) }}" class="font-semibold text-[var(--p-text)] hover:underline">{{ $sellerOrder->seller->shop_name }}</a>
                    @else
                      Sotuvchi yo‘q
                    @endif
                  </dd>
                  <div class="mt-2 text-sm text-[var(--p-muted)]">
                    {{ number_format((float) ($sellerOrder->amount ?? 0), 0, '.', ' ') }} UZS
                    · {{ $sellerOrder->delivery_type ?: 'delivery' }}
                    @if($sellerOrder->courier)
                      · kuryer: <a href="{{ route('admin.couriers.show', $sellerOrder->courier) }}" class="text-[var(--p-accent)] hover:underline">{{ trim(($sellerOrder->courier->first_name ?? '').' '.($sellerOrder->courier->last_name ?? '')) ?: 'Kuryer' }}</a>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Mijoz va to‘lov ma’lumoti</div>
            <div class="a122-section-head__meta">Asosiy aloqa, gift recipient va to‘lov usuli tafsilotlari.</div>
          </div>
        </div>
      <div class="a122-section-body">
        <div class="space-y-2 text-sm">
          <div>
            <span class="metric-label">Ism</span>
            <div class="font-semibold mt-1">
              @if($order->user)
                <a href="{{ route('admin.users.show', $order->user) }}" class="text-[var(--p-accent)] hover:underline">{{ $order->user->full_name ?: 'Foydalanuvchi' }}</a>
              @else
                Mehmon
              @endif
            </div>
          </div>
          <div><span class="metric-label">Telefon</span><div class="font-semibold mt-1">{{ $order->user?->phone_number ?: '—' }}</div></div>
          <div><span class="metric-label">To‘lov turi</span><div class="font-semibold mt-1">{{ $paymentMethodLabel }}</div></div>
          <div><span class="metric-label">To‘lov holati</span><div class="font-semibold mt-1">{{ $paymentLabel }}</div></div>
          <div><span class="metric-label">Buyurtma statusi</span><div class="font-semibold mt-1">{{ $orderStatusLabel }}</div></div>
          <div><span class="metric-label">Gift buyurtmami</span><div class="font-semibold mt-1">{{ $isGiftToOther ? 'Ha' : "Yo'q" }}</div></div>
          @if($isGiftToOther)
            <div><span class="metric-label">Qabul qiluvchi</span><div class="font-semibold mt-1">{{ $order->recipient_name ?: '—' }}</div></div>
            <div><span class="metric-label">Qabul qiluvchi telefoni</span><div class="font-semibold mt-1">{{ $order->recipient_phone ?: '—' }}</div></div>
            <div><span class="metric-label">Qabul qiluvchi hududi</span><div class="font-semibold mt-1">{{ $order->recipient_region ?: '—' }}</div></div>
          @endif
          <div><span class="metric-label">Mijoz istagi</span><div class="font-semibold mt-1">{{ $order->buyerWish ?: '—' }}</div></div>
        </div>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Kuryer va bajarilish</div>
            <div class="a122-section-head__meta">Courier assignment, pickup/delivery bosqichi va tegishli order yozuvi.</div>
          </div>
        </div>
        <div class="a122-section-body">
          @if($courierOrder || $assignedCourier)
            <div class="space-y-3 text-sm">
              @if($courierOrder)
                <div>
                  <span class="metric-label">Courier order</span>
                  <div class="font-semibold mt-1">
                    <a href="{{ route('admin.courier-orders.show', $courierOrder) }}" class="text-[var(--p-accent)] hover:underline">#COR-{{ $courierOrder->id }}</a>
                  </div>
                </div>
              @endif
              @if($assignedCourier)
                <div>
                  <span class="metric-label">Biriktirilgan kuryer</span>
                  <div class="font-semibold mt-1">
                    <a href="{{ route('admin.couriers.show', $assignedCourier) }}" class="text-[var(--p-accent)] hover:underline">{{ trim(($assignedCourier->first_name ?? '').' '.($assignedCourier->last_name ?? '')) ?: 'Kuryer' }}</a>
                  </div>
                </div>
                <div><span class="metric-label">Telefon</span><div class="font-semibold mt-1">{{ $assignedCourier->phone_number ?: '—' }}</div></div>
                <div><span class="metric-label">Hudud</span><div class="font-semibold mt-1">{{ $assignedCourier->region ?: '—' }}</div></div>
              @endif
              @if($courierOrder)
                <div><span class="metric-label">Courier order holati</span><div class="font-semibold mt-1">{{ $courierOrder->status ?: '—' }}</div></div>
                <div><span class="metric-label">Courier narxi</span><div class="font-semibold mt-1">{{ number_format((float) ($courierOrder->courierPrice ?? 0), 0, '.', ' ') }} UZS</div></div>
              @endif
            </div>
          @else
            <div class="text-sm text-gray-500">Kuryer hali biriktirilmagan.</div>
          @endif
        </div>
      </div>
    </section>
  </div>
</div>
@endsection
