@extends('a122.layouts.admin')
@section('title', 'Promokod: '.$promocode->code)
@section('page-title', 'Promokod: '.$promocode->code)

@section('content')
@php
  $isActive = $promocode->status && $promocode->expires_at > now();
  $usesLimit = (int) ($promocode->usesLimit ?? 0);
  $perUserLimit = (int) ($promocode->per_user_limit ?? 1);
  $usedCount = (int) ($promocode->usedCount ?? 0);
  $remaining = $usesLimit > 0 ? max($usesLimit - $usedCount, 0) : null;
  $pct = $usesLimit > 0 ? min(round($usedCount / $usesLimit * 100), 100) : null;
@endphp

<x-a122.page-header back-href="{{ route('admin.promocodes.index') }}">
  <x-slot name="heading">Promokod: {{ $promocode->code }}</x-slot>
  <x-slot name="meta">ID: #{{ $promocode->id }} · {{ $promocode->created_at?->format('d.m.Y') }}</x-slot>
</x-a122.page-header>

<section class="a122-section mb-4">
  <div class="a122-section-body">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="kpi-soft">
        <div class="metric-label">Chegirma turi</div>
        <div class="metric-value text-xl">{{ $promocode->type === 'percent' ? 'Foiz' : 'Miqdor' }}</div>
        <div class="metric-meta">{{ $promocode->type === 'percent' ? $promocode->amount.'%' : number_format($promocode->amount).' UZS' }}</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Maks. chegirma</div>
        <div class="metric-value text-xl">
          {{ $promocode->type === 'percent' ? ($promocode->max_discount_amount ? number_format($promocode->max_discount_amount) . ' UZS' : 'Cheksiz') : '—' }}
        </div>
        <div class="metric-meta">{{ $promocode->type === 'percent' ? 'Foizli chegirma limiti' : 'Faqat foizli turda ishlaydi' }}</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Ishlatilgan</div>
        <div class="metric-value text-xl">{{ number_format($usedCount) }}</div>
        <div class="metric-meta">Jami foydalanish</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Qolgan limit</div>
        <div class="metric-value text-xl">{{ $remaining !== null ? number_format($remaining) : '∞' }}</div>
        <div class="metric-meta">{{ $usesLimit > 0 ? 'Cheklangan' : 'Cheksiz' }}</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">User limiti</div>
        <div class="metric-value text-xl">{{ $perUserLimit > 0 ? number_format($perUserLimit) : '∞' }}</div>
        <div class="metric-meta">Bir foydalanuvchi uchun maksimal ishlatish</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Holat</div>
        <div class="metric-value text-xl">{{ $isActive ? 'Aktiv' : 'Nofaol' }}</div>
        <div class="metric-meta">{{ optional($promocode->expires_at)->format('d.m.Y H:i') ?: 'Muddat yo‘q' }}</div>
      </div>
    </div>
  </div>
</section>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
  <div class="xl:col-span-4">
    <div class="a122-section mb-3">
      <div class="a122-section-body text-center py-8">
        <code class="inline-block rounded-2xl bg-[var(--p-elevated)] px-6 py-4 text-[26px] font-black tracking-[0.12em] text-[var(--p-accent)]">
          {{ $promocode->code }}
        </code>
        <div class="mt-3">
          <span class="badge {{ $isActive ? 'badge-success' : 'badge-danger' }}">
            {{ $isActive ? 'Aktiv' : 'Nofaol' }}
          </span>
        </div>
      </div>
    </div>

    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Qo‘llanish qoidalari</div>
          <div class="a122-section-head__meta">Promokodning turi, limitlari va minimal buyurtma sharti.</div>
        </div>
      </div>
      <div class="a122-section-body">
        @foreach([
          ['Tur',       $promocode->type === 'percent' ? 'Foiz (%)' : 'Miqdor (UZS)'],
          ['Chegirma',  $promocode->type === 'percent' ? $promocode->amount.'%' : number_format($promocode->amount).' UZS'],
          ['Maks. chegirma', $promocode->type === 'percent' ? ($promocode->max_discount_amount ? number_format($promocode->max_discount_amount).' UZS' : 'Cheksiz') : '—'],
          ['Min. buyurtma', $promocode->min_order_amount > 0 ? number_format($promocode->min_order_amount).' UZS' : '—'],
          ['Jami limit',     $promocode->usesLimit ?: 'Cheksiz'],
          ['Bir user limiti', $perUserLimit ?: 'Cheksiz'],
          ['Ishlatildi', $promocode->usedCount.' marta'],
          ['Muddat',    \Carbon\Carbon::parse($promocode->expires_at)->format('d.m.Y H:i')],
          ["Qo'shildi", $promocode->created_at?->format('d.m.Y H:i')],
        ] as [$k,$v])
        <div class="flex justify-between gap-4 py-2 border-b border-[var(--p-border)]">
          <span class="text-xs text-[var(--p-hint)]">{{ $k }}</span>
          <span class="text-sm font-medium text-[var(--p-text)] text-right">{{ $v }}</span>
        </div>
        @endforeach
      </div>
    </div>

    @if($promocode->usesLimit > 0)
    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Foydalanish progressi</div>
          <div class="a122-section-head__meta">Limitli promokod uchun ishlatilish darajasi.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <div class="flex justify-between items-center gap-4 mb-2">
          <span class="text-xs text-[var(--p-hint)]">{{ $usedCount }} / {{ $usesLimit }}</span>
          <span class="text-xs font-semibold text-[var(--p-text)]">{{ $pct }}%</span>
        </div>
        <div class="dash-prog-track" style="height:8px">
          <div class="dash-prog-fill" style="width:{{ $pct }}%;background:{{ $pct>=100?'var(--p-danger)':'var(--p-accent)' }}"></div>
        </div>
      </div>
    </div>
    @endif

    <a href="{{ route('admin.promocodes.edit', $promocode) }}" class="btn-p primary" style="width:100%;justify-content:center;margin-bottom:8px">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
  </div>

  <div class="xl:col-span-8">
    <div class="a122-section">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Foydalanish tarixi</div>
          <div class="a122-section-head__meta">{{ $histories->total() }} ta foydalanuvchi promokoddan foydalangan.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <div class="table-responsive kc-twrap">
          <table class="table data-table align-middle mb-0">
            <thead>
              <tr><th>#</th><th>Foydalanuvchi</th><th>Telefon</th><th>Sana</th></tr>
            </thead>
            <tbody>
              @forelse($histories as $h)
              <tr>
                <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">{{ $h->id }}</td>
                <td>
                  @if($h->user)
                  <a href="{{ route('admin.users.show', $h->user_id) }}" style="color:var(--p-text);font-weight:600">
                    {{ $h->user->name }} {{ $h->user->lastname }}
                  </a>
                  @else
                    <span style="color:var(--p-hint)">ID: {{ $h->user_id }}</span>
                  @endif
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">{{ $h->user?->phone_number ?? '—' }}</td>
                <td style="font-size:11px;color:var(--p-hint)">{{ \Carbon\Carbon::parse($h->created_at)->format('d.m.Y H:i') }}</td>
              </tr>
              @empty
              <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--p-hint)">Hali ishlatilmagan</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($histories->hasPages())
        {{ $histories->links('a122.partials.pagination') }}
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
