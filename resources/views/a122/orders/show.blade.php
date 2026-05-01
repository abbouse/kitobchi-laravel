@extends('a122.layouts.admin')
@section('title', 'Buyurtma #' . $order->id)
@section('page-title', 'Buyurtma tafsiloti')

@section('content')
<div class="space-y-6">
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
                <td>{{ $it['name'] ?? ($it['product']->name ?? '—') }}</td>
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
          <div class="flex justify-between gap-3"><dt class="metric-label">Chegirma</dt><dd class="font-semibold">{{ number_format($summary['discount'], 0, '.', ' ') }} UZS</dd></div>
          <div class="flex justify-between gap-3"><dt class="metric-label">Cashback</dt><dd class="font-semibold">{{ number_format($summary['cashback'], 0, '.', ' ') }} UZS</dd></div>
          <div class="flex justify-between gap-3 border-t border-[var(--p-border)] pt-3"><dt class="font-bold">Jami</dt><dd class="font-black">{{ number_format((float)$order->amount, 0, '.', ' ') }} UZS</dd></div>
        </dl>
        </div>
      </div>

      <div class="a122-section">
        <div class="a122-section-head">
          <div>
            <div class="a122-section-head__title">Mijoz ma’lumoti</div>
            <div class="a122-section-head__meta">Asosiy aloqa ma’lumotlari va to‘lov holati.</div>
          </div>
        </div>
        <div class="a122-section-body">
        <div class="space-y-2 text-sm">
          <div><span class="metric-label">Ism</span><div class="font-semibold mt-1">{{ $order->user?->full_name ?: 'Mehmon' }}</div></div>
          <div><span class="metric-label">Telefon</span><div class="font-semibold mt-1">{{ $order->user?->phone_number ?: '—' }}</div></div>
          <div><span class="metric-label">To‘lov holati</span><div class="font-semibold mt-1">{{ (int)$order->paymentStatus === 2 ? 'To‘langan' : 'Kutilmoqda' }}</div></div>
        </div>
        </div>
      </div>
    </section>
  </div>
</div>
@endsection
