@extends('panel.layouts.panel')
@section('title', 'Bot operatorlari')
@section('page-title', 'Bot operatorlari')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
  <div style="font-size:13px;color:var(--p-hint)">
    Telegram bot support operatorlari
  </div>
  <a href="{{ route('panel.bot-tickets.index') }}" class="btn-p ghost">
    <i class="bi bi-arrow-left"></i> Murojaatlarga
  </a>
</div>

<div class="row g-3">
  @forelse($operators as $op)
  @php
    $dotClr = match($op->status) { 'online'=>'success','busy'=>'warning',default=>'muted' };
    $dotLbl = match($op->status) { 'online'=>'Online','busy'=>'Band',default=>'Offline' };
  @endphp
  <div class="col-sm-6 col-xl-4">
    <div class="p-card">
      <div class="dash-card-body">

        {{-- Header --}}
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                        display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0">
              {{ strtoupper(substr($op->name ?? $op->username ?? 'O', 0, 1)) }}
            </div>
            <div>
              <div style="font-size:15px;font-weight:600;color:var(--p-text)">{{ $op->name ?: 'Noma\'lum' }}</div>
              @if($op->username)
                <div style="font-size:12px;color:var(--p-hint)">@{{ $op->username }}</div>
              @endif
              <div style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                TG: {{ $op->telegram_id }}
              </div>
            </div>
          </div>
          <div class="d-flex flex-column align-items-end gap-1">
            <span class="s-pill {{ $op->is_active ? 'success' : 'muted' }}">
              {{ $op->is_active ? 'Faol' : 'Blok' }}
            </span>
            <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:var(--p-{{ $dotClr }})">
              <div style="width:6px;height:6px;border-radius:50%;background:var(--p-{{ $dotClr }})"></div>
              {{ $dotLbl }}
            </div>
          </div>
        </div>

        {{-- Stats --}}
        @if($op->stats)
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0;border:1px solid var(--p-border);border-radius:8px;overflow:hidden;margin-bottom:14px">
          @foreach([
            ['Bajarildi', $op->stats->handled, 'text'],
            ['Yopildi',   $op->stats->closed,  'success'],
            ['O\'rt. baho', number_format($op->stats->avg_rating,1), 'warning'],
          ] as [$lbl,$val,$clr])
          <div style="text-align:center;padding:10px 6px;border-right:1px solid var(--p-border)">
            <div style="font-size:16px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-{{ $clr }})">{{ $val }}</div>
            <div style="font-size:10px;color:var(--p-hint)">{{ $lbl }}</div>
          </div>
          @endforeach
        </div>
        @endif

        {{-- Action --}}
        <form method="POST" action="{{ route('panel.bot-tickets.operator.toggle', $op) }}">
          @csrf @method('PATCH')
          <button class="btn-p {{ $op->is_active ? 'danger' : '' }} ghost sm" style="width:100%">
            <i class="bi bi-{{ $op->is_active ? 'lock' : 'unlock' }}"></i>
            {{ $op->is_active ? 'Bloklash' : 'Faollashtirish' }}
          </button>
        </form>

      </div>
    </div>
  </div>
  @empty
  <div class="col-12">
    <div class="p-card" style="text-align:center;padding:50px;color:var(--p-hint)">
      <i class="bi bi-headset" style="font-size:40px;display:block;margin-bottom:12px"></i>
      Operatorlar yo'q
    </div>
  </div>
  @endforelse
</div>

@if($operators->hasPages())
<div class="p-pagination mt-3">{{ $operators->links('panel.partials.pagination') }}</div>
@endif

@endsection