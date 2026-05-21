@extends('a122.layouts.admin')
@section('title', $blogger->full_name)
@section('page-title', 'Hamkor bloger')

@section('content')
<div class="space-y-6">
  <x-a122.page-header back-href="{{ route('admin.bloggers.index') }}">
    <x-slot name="heading">{{ $blogger->full_name }}</x-slot>
    <x-slot name="meta">{{ $blogger->phone_number ?: 'Telefon yo‘q' }} · {{ $blogger->status_label }} · {{ optional($blogger->active_until)->format('d.m.Y H:i') ?: 'Muddat belgilanmagan' }}</x-slot>
    <x-slot name="actions">
      <a href="{{ route('admin.bloggers.edit', $blogger) }}" class="btn-p primary"><i class="bi bi-pencil-square"></i> Tahrirlash</a>
    </x-slot>
  </x-a122.page-header>

  @if(session('success'))
    <div class="px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-sm">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 text-sm">{{ session('error') }}</div>
  @endif

  <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <div class="xl:col-span-4 space-y-4">
      <section class="a122-section">
        <div class="a122-section-head"><div><div class="a122-section-head__title">Profil</div><div class="a122-section-head__meta">Asosiy ma’lumot va aloqa kanallari.</div></div></div>
        <div class="a122-section-body">
          <div class="space-y-3">
            <div class="data-kv"><dt>To‘liq ism</dt><dd>{{ $blogger->full_name }}</dd></div>
            <div class="data-kv"><dt>Telefon</dt><dd>{{ $blogger->phone_number ?: '—' }}</dd></div>
            <div class="data-kv"><dt>Holat</dt><dd><span class="badge {{ $blogger->is_active_now ? 'badge-success' : 'badge-muted' }}">{{ $blogger->status_label }}</span></dd></div>
            <div class="data-kv"><dt>Faol muddati</dt><dd>{{ optional($blogger->active_until)->format('d.m.Y H:i') ?: '—' }}</dd></div>
            <div class="data-kv"><dt>Manzil</dt><dd>{{ $blogger->address ?: '—' }}</dd></div>
          </div>
        </div>
      </section>

      <section class="a122-section">
        <div class="a122-section-head"><div><div class="a122-section-head__title">Tarmoqlar</div><div class="a122-section-head__meta">Bloger bilan ishlash uchun tezkor havolalar.</div></div></div>
        <div class="a122-section-body">
          <div class="space-y-3">
            @forelse($blogger->socialLinks() as $label => $url)
              <div class="flex items-center justify-between gap-3 rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] px-4 py-3">
                <div>
                  <div class="text-xs text-gray-500">{{ $label }}</div>
                  <div class="text-sm font-semibold break-all">{{ preg_replace('#^https?://#', '', $url) }}</div>
                </div>
                <a href="{{ $url }}" target="_blank" rel="noopener" class="btn-ghost p-2 rounded-lg"><i class="bi bi-box-arrow-up-right"></i></a>
              </div>
            @empty
              <div class="text-sm text-gray-500">Ijtimoiy tarmoq linklari kiritilmagan.</div>
            @endforelse
          </div>
        </div>
      </section>

      <section class="card p-5">
        <h3 class="text-lg font-black mb-4">Qisqa statistika</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 xl:grid-cols-1 gap-3">
          <div class="kpi-soft"><div class="metric-label">Jo‘natildi</div><div class="metric-value text-xl">{{ $stats['delivered_shipments'] }}</div></div>
          <div class="kpi-soft"><div class="metric-label">Kutilmoqda</div><div class="metric-value text-xl">{{ $stats['pending_shipments'] }}</div></div>
          <div class="kpi-soft"><div class="metric-label">Itemlar jami</div><div class="metric-value text-xl">{{ $stats['items_total'] }}</div></div>
        </div>
      </section>
    </div>

    <div class="xl:col-span-8 space-y-4">
      <section class="card p-5">
        <h3 class="text-lg font-black mb-4">Yangi jo‘natma</h3>
        <form method="POST" action="{{ route('admin.bloggers.shipments.store', $blogger) }}" class="space-y-4">
          @csrf
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Jo‘natma vaqti</label>
              <input type="datetime-local" name="scheduled_for" value="{{ old('scheduled_for', now()->format('Y-m-d\TH:i')) }}" class="input" required>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Izoh</label>
              <input name="note" value="{{ old('note') }}" class="input" placeholder="Masalan: May collab box">
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Itemlar ro‘yxati</label>
            <textarea name="items_text" rows="8" class="input" placeholder="Har qatorga bitta item nomi yozing" required>{{ old('items_text') }}</textarea>
            <div class="mt-2 text-xs text-gray-500">Bu itemlar katalog mahsuloti emas, bloggerga yuboriladigan erkin nomlar ro‘yxati.</div>
          </div>
          <button type="submit" class="btn btn-primary">Jo‘natma yaratish</button>
        </form>
      </section>

      <section class="card p-5">
        <div class="flex items-center justify-between gap-3 mb-4">
          <h3 class="text-lg font-black">Jo‘natmalar tarixi</h3>
          <span class="badge badge-info">{{ $blogger->shipments->count() }} ta jo‘natma</span>
        </div>

        <div class="space-y-4">
          @forelse($blogger->shipments as $shipment)
            <article class="rounded-[1.5rem] border border-[var(--p-border)] bg-[var(--p-elevated)] p-4">
              <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <div class="text-sm font-black">#BLG-{{ $shipment->id }}</div>
                    <span class="badge {{ $shipment->status_color }}">{{ $shipment->status_label }}</span>
                  </div>
                  <div class="text-sm text-gray-500 mt-1">{{ $shipment->scheduledForDisplay('d.m.Y H:i') }}</div>
                  @if($shipment->note)
                    <div class="text-sm text-gray-600 mt-2">{{ $shipment->note }}</div>
                  @endif
                </div>

                <div class="flex flex-wrap gap-2">
                  @if($shipment->status === \App\Models\BloggerShipment::STATUS_PENDING)
                    <form method="POST" action="{{ route('admin.bloggers.shipments.delivered', [$blogger, $shipment]) }}">
                      @csrf @method('PATCH')
                      <button class="btn btn-primary">Yetkazildi</button>
                    </form>
                  @else
                    <form method="POST" action="{{ route('admin.bloggers.shipments.pending', [$blogger, $shipment]) }}">
                      @csrf @method('PATCH')
                      <button class="btn btn-secondary">Yetkazilmadi</button>
                    </form>
                  @endif
                  <a href="{{ route('admin.bloggers.shipments.edit', [$blogger, $shipment]) }}" class="btn btn-secondary">Tahrirlash</a>
                  <form method="GET" target="_blank" action="{{ route('admin.bloggers.shipments.print', [$blogger, $shipment]) }}" class="flex items-center gap-2">
                    <select name="locale" class="input !py-2 !w-[110px]">
                      <option value="uz">O‘zbekcha</option>
                      <option value="ru">Русский</option>
                      <option value="en">English</option>
                      <option value="ja">日本語</option>
                    </select>
                    <button class="btn btn-secondary">Chek chiqarish</button>
                  </form>
                  <form method="POST" action="{{ route('admin.bloggers.shipments.destroy', [$blogger, $shipment]) }}" onsubmit="return confirm('Jo‘natmani o‘chirishni tasdiqlaysizmi?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-secondary text-rose-500">O‘chirish</button>
                  </form>
                </div>
              </div>

              <div class="mt-4 grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_230px] gap-4">
                <div class="rounded-[1.3rem] border border-[var(--p-border)] bg-white/70 dark:bg-white/[0.02] p-4">
                  <div class="text-xs font-semibold text-gray-500 uppercase tracking-[0.14em] mb-3">Itemlar</div>
                  <div class="space-y-2">
                    @foreach($shipment->items as $item)
                      <div class="flex items-center gap-3 rounded-2xl bg-[var(--p-elevated)] px-3 py-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-[var(--p-accent-soft)] text-[var(--p-accent)] text-xs font-bold">{{ $loop->iteration }}</span>
                        <span class="font-medium">{{ $item->name }}</span>
                      </div>
                    @endforeach
                  </div>
                </div>
                <div class="rounded-[1.3rem] border border-[var(--p-border)] bg-white/70 dark:bg-white/[0.02] p-4">
                  <div class="text-xs font-semibold text-gray-500 uppercase tracking-[0.14em] mb-3">Print va status</div>
                  <div class="space-y-3 text-sm">
                    <div class="data-kv"><dt>Holat</dt><dd>{{ $shipment->status_label }}</dd></div>
                    <div class="data-kv"><dt>Yetkazilgan vaqt</dt><dd>{{ optional($shipment->delivered_at)->format('d.m.Y H:i') ?: '—' }}</dd></div>
                    <div class="data-kv"><dt>Itemlar soni</dt><dd>{{ $shipment->items->count() }}</dd></div>
                  </div>
                </div>
              </div>
            </article>
          @empty
            <div class="text-center py-10 text-gray-400">Bu bloger uchun jo‘natmalar hali yaratilmagan.</div>
          @endforelse
        </div>
      </section>
    </div>
  </div>
</div>
@endsection
