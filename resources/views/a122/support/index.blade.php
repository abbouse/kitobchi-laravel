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
  <div class="a122-index-header__actions">
    <form method="GET" class="a122-index-search-form">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search"></i>
      <input
        type="search"
        name="search"
        value="{{ request('search') }}"
        placeholder="Ism, username, telegram ID yoki ticket #"
      >
    </form>
  </div>
</div>

<div class="tab-pills fade-up mb-3">
  @foreach([
    'queue' => ['Navbatda', $counts['queue'] ?? 0],
    'active' => ['Aktiv', $counts['active'] ?? 0],
    'closed' => ['Yopilgan', $counts['closed'] ?? 0],
    'rated' => ['Baholangan', $counts['rated'] ?? 0],
    'all' => ['Barchasi', $counts['all'] ?? 0],
  ] as $key => [$label, $count])
    <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="tab-pill {{ $tab === $key ? 'active' : '' }}">
      {{ $label }} <span>{{ $count }}</span>
    </a>
  @endforeach
</div>

<div class="a122-section mb-3">
  <div class="a122-section-body">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <div>
        <label class="p-form-label">Operator</label>
        <select name="operator_id" class="p-form-control">
          <option value="">Barchasi</option>
          @foreach($operators as $operator)
            <option value="{{ $operator->telegram_id }}" @selected((string) request('operator_id') === (string) $operator->telegram_id)>
              {{ $operator->name ?: ($operator->username ? '@'.$operator->username : $operator->telegram_id) }}
            </option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="p-form-label">Boshlanish sanasi</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="p-form-control">
      </div>
      <div>
        <label class="p-form-label">Tugash sanasi</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="p-form-control">
      </div>
      <div class="flex items-end gap-2">
        <button class="btn-p primary flex-1"><i class="bi bi-funnel"></i> Filtrlash</button>
        <a href="{{ route('admin.support.index', ['tab' => $tab]) }}" class="btn-p ghost">Tozalash</a>
      </div>
    </form>
  </div>
</div>

<div class="a122-section">
  <div class="a122-section-head">
    <div>
      <div class="a122-section-head__title">Support inbox</div>
      <div class="a122-section-head__meta">Aktiv yozishmalar, oxirgi javob va tarix shu yerda ko‘rinadi.</div>
    </div>
  </div>
  <div class="a122-section-body">
    <div class="a122-compact-list">
      @forelse($tickets as $ticket)
        @php
          $st = $statuses[$ticket->status] ?? ['label'=>$ticket->status,'class'=>'ob-p'];
          $lastMessage = $ticket->latestMessage;
          $lastActorClass = $lastMessage?->sent_by === 'user'
            ? 'muted'
            : ($lastMessage?->sent_by === 'admin'
              ? 'accent'
              : ($lastMessage?->sent_by === 'operator' ? 'warning' : 'success'));
          $lastActorLabel = $lastMessage?->sent_by === 'user'
            ? 'Foydalanuvchi'
            : ($lastMessage?->sent_by === 'admin'
              ? 'Admin'
              : ($lastMessage?->sent_by === 'operator' ? 'Operator' : 'Tizim'));
        @endphp
        <div class="a122-compact-list__item" style="padding:18px 0">
          <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="flex items-start gap-3 min-w-0 flex-1">
              @include('a122.partials.avatar', [
                'name' => $ticket->name ?: 'Noma\'lum',
                'image' => null,
                'class' => 'av'
              ])
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                  <a href="{{ route('admin.support.show', $ticket) }}" class="a122-compact-list__title" style="text-decoration:none">
                    {{ $ticket->name ?: 'Noma\'lum foydalanuvchi' }}
                  </a>
                  <span class="s-pill muted" style="font-size:10px;padding:3px 8px">#{{ $ticket->id }}</span>
                  <span class="o-badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                  <span class="s-pill muted" style="font-size:10px;padding:3px 8px">{{ $ticket->messages_count ?? 0 }} ta xabar</span>
                </div>

                <div class="a122-compact-list__sub" style="margin-bottom:8px">
                  @if($ticket->username)
                    <span>t.me/{{ $ticket->username }}</span>
                    <span>·</span>
                  @endif
                  <span>Telegram ID: {{ $ticket->user_id ?: '—' }}</span>
                  @if($ticket->created_at)
                    <span>·</span>
                    <span>{{ $ticket->created_at->format('d.m.Y H:i') }}</span>
                  @endif
                </div>

                <div style="font-size:13px;line-height:1.65;color:var(--p-text);margin-bottom:10px">
                  {{ \Illuminate\Support\Str::limit($lastMessage?->message ?: $ticket->first_msg ?: 'Murojaat matni yo‘q', 220) }}
                </div>

                <div class="flex flex-wrap items-center gap-2">
                  @if($lastMessage)
                    <span class="s-pill {{ $lastActorClass }}" style="font-size:10px;padding:3px 8px">{{ $lastActorLabel }}</span>
                    <span style="font-size:11px;color:var(--p-hint)">{{ strtoupper($lastMessage->message_type) }}</span>
                    <span style="font-size:11px;color:var(--p-hint)">· {{ $lastMessage->created_at?->format('d.m H:i') }}</span>
                  @else
                    <span style="font-size:11px;color:var(--p-hint)">Yozishma tarixi hali yo‘q</span>
                  @endif

                  @if($ticket->operator)
                    <span class="s-pill accent" style="font-size:10px;padding:3px 8px">
                      Operator: {{ $ticket->operator->name ?? ($ticket->operator->username ? '@'.$ticket->operator->username : $ticket->operator->telegram_id) }}
                    </span>
                  @elseif($ticket->status === 'queue')
                    <span class="s-pill warning" style="font-size:10px;padding:3px 8px">Operator tayinlanmagan</span>
                  @endif

                  @if($ticket->rating)
                    <span class="s-pill success" style="font-size:10px;padding:3px 8px">Baho: {{ $ticket->rating }}/5</span>
                  @endif
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <a href="{{ route('admin.support.show', $ticket) }}" class="btn-p ghost sm">
                <i class="bi bi-clock-history"></i>
                <span>History</span>
              </a>
              <a href="{{ route('admin.support.show', $ticket) }}" class="btn-p primary sm">
                <i class="bi bi-chat-left-text"></i>
                <span>Chatni ochish</span>
              </a>
            </div>
          </div>
        </div>
      @empty
        <div style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-chat-square-text" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Murojaatlar topilmadi
        </div>
      @endforelse
    </div>
  </div>
  @if($tickets->hasPages())
    {{ $tickets->links('a122.partials.pagination') }}
  @endif
</div>
@endsection
