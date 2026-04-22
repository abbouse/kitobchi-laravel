@extends('a122.layouts.admin')
@section('title', 'Support murojaat')
@section('page-title', 'Support murojaatlar')

@section('content')

<x-a122.page-header>
  <x-slot name="heading">Support murojaatlari</x-slot>
  <x-slot name="meta">Telegram bot orqali kelgan yordam so'rovlari</x-slot>
</x-a122.page-header>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5 mb-4">
  @foreach([
    [$counts['all'] ?? 0, 'Jami murojaat', 'accent', 'bi-headset'],
    [$counts['queue'] ?? 0, 'Navbatda', 'warning', 'bi-hourglass-split'],
    [$counts['active'] ?? 0, 'Aktiv', 'info', 'bi-lightning-charge'],
    [$counts['closed'] ?? 0, 'Yopilgan', 'muted', 'bi-check2-circle'],
    [$counts['rated'] ?? 0, 'Baholangan', 'success', 'bi-star'],
  ] as [$value, $label, $tone, $icon])
    <div class="p-card flex items-center gap-3 fade-up" style="padding:14px">
      <div style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--p-{{ $tone }}-d,var(--p-elevated));color:var(--p-{{ $tone }})">
        <i class="bi {{ $icon }}"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;color:var(--p-text)">{{ $value }}</div>
        <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--p-hint)">{{ $label }}</div>
      </div>
    </div>
  @endforeach
</div>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Murojaatlar ro'yxati</div>
    <div class="a122-index-header__meta">{{ $tickets->total() }} ta support ticket yuklandi</div>
  </div>
</div>

{{-- Table --}}
<div class="p-card p-0">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
      <thead>
        <tr>
          <th>#</th>
          <th>Foydalanuvchi</th>
          <th>Birinchi xabar</th>
          <th>Operator</th>
          <th>Status</th>
          <th>Baho</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($tickets as $ticket)
        @php $st = $statuses[$ticket->status] ?? ['label'=>$ticket->status,'class'=>'ob-p']; @endphp
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">#{{ $ticket->id }}</td>
          <td>
            <div>
              <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                {{ $ticket->name ?: 'Noma\'lum' }}
              </div>
              @if($ticket->username)
                <div style="font-size:11px;color:var(--p-hint)">t.me/{{ $ticket->username }}</div>
              @endif
              @if($ticket->user_id)
                <div style="font-size:10px;color:var(--p-accent);font-family:'JetBrains Mono',monospace">
                  user #{{ $ticket->user_id }}
                </div>
              @endif
            </div>
          </td>
          <td style="max-width:200px">
            <div style="font-size:12px;color:var(--p-muted);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
              {{ $ticket->first_msg ?: '—' }}
            </div>
          </td>
          <td>
            @if($ticket->operator)
              <div style="display:flex;align-items:center;gap:6px">
                <div style="width:6px;height:6px;border-radius:50%;background:var(--p-{{ $ticket->operator->status==='online'?'success':($ticket->operator->status==='busy'?'warning':'muted') }})"></div>
                <span style="font-size:13px;color:var(--p-text)">{{ $ticket->operator->name ?? $ticket->operator->username }}</span>
              </div>
            @elseif($ticket->status === 'queue')
              <span style="font-size:12px;color:var(--p-warning)">Tayinlanmagan</span>
            @else
              <span style="color:var(--p-hint)">—</span>
            @endif
          </td>
          <td><span class="o-badge {{ $st['class'] }}">{{ $st['label'] }}</span></td>
          <td>
            @if($ticket->rating)
              <div style="display:flex;align-items:center;gap:2px">
                @for($i=1;$i<=5;$i++)
                  <i class="bi bi-star{{ $i<=$ticket->rating?'-fill':'' }}"
                     style="font-size:12px;color:{{ $i<=$ticket->rating?'var(--p-warning)':'var(--p-border)' }}"></i>
                @endfor
              </div>
            @else
              <span style="color:var(--p-hint)">—</span>
            @endif
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
            {{ $ticket->created_at?->format('d.m H:i') }}
          </td>
          <td>
            <a href="{{ route('admin.support.show', $ticket) }}" class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-chat-square-text" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Murojaatlar topilmadi
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($tickets->hasPages())
  {{ $tickets->links('a122.partials.pagination') }}
  @endif
</div>
@endsection
