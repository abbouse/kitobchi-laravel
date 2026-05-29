@extends('a122.layouts.admin')
@section('title', 'Support murojaat')
@section('page-title', 'Support murojaatlar')
@section('page-eyebrow', 'Support operations')

@section('content')
@php
  $supportTabs = [
    'queue' => ['label' => 'Navbatda', 'count' => $counts['queue'] ?? 0],
    'active' => ['label' => 'Aktiv', 'count' => $counts['active'] ?? 0],
    'closed' => ['label' => 'Yopilgan', 'count' => $counts['closed'] ?? 0],
    'rated' => ['label' => 'Baholangan', 'count' => $counts['rated'] ?? 0],
    'all' => ['label' => 'Barchasi', 'count' => $counts['all'] ?? 0],
  ];

  $toneClass = fn ($sentBy) => match ($sentBy) {
    'admin' => 'primary',
    'operator' => 'warning',
    'system' => 'success',
    default => 'muted',
  };

  $actorLabel = fn ($sentBy) => match ($sentBy) {
    'admin' => 'Admin',
    'operator' => 'Operator',
    'system' => 'Tizim',
    default => 'Foydalanuvchi',
  };
@endphp

<div class="d-flex flex-column gap-4">
  <x-admin.page-header
    eyebrow="Support operations"
    title="Support murojaatlari"
    subtitle="Telegram bot orqali kelgan support oqimini navbat, operator va yozishma sifati bo‘yicha boshqaring.">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input
        type="search"
        name="search"
        value="{{ request('search') }}"
        placeholder="Ism, username, Telegram ID yoki ticket #"
        class="form-control">
    </form>
  </x-admin.page-header>

  <div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Jami murojaat"
        :value="number_format($counts['all'] ?? 0)"
        meta="Bot orqali kelgan barcha support oqimlari"
        icon="headset"
        tone="primary" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Navbatda"
        :value="number_format($counts['queue'] ?? 0)"
        meta="Operator biriktirilishini kutayotgan chatlar"
        icon="hourglass-split"
        tone="warning" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Aktiv chatlar"
        :value="number_format($counts['active'] ?? 0)"
        meta="Hozir javob almashinuvida bo‘lgan support oqimlari"
        icon="lightning-charge"
        tone="info" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Yopilgan / baholangan"
        :value="number_format($counts['closed'] ?? 0) . ' / ' . number_format($counts['rated'] ?? 0)"
        meta="Quality signal va tugallangan yordam sessiyalari"
        icon="check2-circle"
        tone="success" />
    </div>
  </div>

  <x-admin.section-card title="Filtrlar va navbat segmentlari" meta="Operator, vaqt oralig‘i va ticket statusi bo‘yicha support oqimini toraytiring.">
    <div class="d-flex flex-column gap-3">
      <div class="kc-filter-card">
        <div class="nav nav-pills flex-wrap">
          @foreach($supportTabs as $key => $tabItem)
            <a
              href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}"
              class="nav-link {{ $tab === $key ? 'active' : '' }}">
              {{ $tabItem['label'] }}
              <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">
                {{ number_format($tabItem['count']) }}
              </span>
            </a>
          @endforeach
        </div>
      </div>

      <form method="GET" class="row g-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="hidden" name="search" value="{{ request('search') }}">
        <div class="col-12 col-md-4">
          <label class="form-label small text-uppercase fw-semibold text-secondary">Operator</label>
          <select name="operator_id" class="form-select rounded-4 border-0 shadow-sm">
            <option value="">Barchasi</option>
            @foreach($operators as $operator)
              <option value="{{ $operator->telegram_id }}" @selected((string) request('operator_id') === (string) $operator->telegram_id)>
                {{ $operator->name ?: ($operator->username ? '@'.$operator->username : $operator->telegram_id) }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small text-uppercase fw-semibold text-secondary">Boshlanish sanasi</label>
          <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control rounded-4 border-0 shadow-sm">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small text-uppercase fw-semibold text-secondary">Tugash sanasi</label>
          <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control rounded-4 border-0 shadow-sm">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end gap-2">
          <button class="btn-p primary flex-fill">
            <i class="bi bi-funnel"></i>
            <span>Filtrlash</span>
          </button>
          <a href="{{ route('admin.support.index', ['tab' => $tab]) }}" class="btn-p ghost">Tozalash</a>
        </div>
      </form>
    </div>
  </x-admin.section-card>

  <x-admin.section-card title="Support inbox" :meta="$tickets->total() . ' ta support ticket topildi.'">
    <div class="kc-list-shell">
      @forelse($tickets as $ticket)
        @php
          $st = $statuses[$ticket->status] ?? ['label' => $ticket->status, 'class' => 'text-bg-light border'];
          $lastMessage = $ticket->latestMessage;
          $lastTone = $toneClass($lastMessage?->sent_by);
        @endphp

        <div class="kc-list-row">
          <div class="d-flex flex-column gap-3 flex-xl-row justify-content-xl-between align-items-xl-start">
            <div class="d-flex align-items-start gap-3 min-w-0 flex-grow-1">
              @include('a122.partials.avatar', [
                'name' => $ticket->name ?: 'Noma\'lum',
                'image' => null,
                'class' => 'w-10 h-10 rounded-4 text-sm',
              ])

              <div class="min-w-0 flex-grow-1">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <a href="{{ route('admin.support.show', $ticket) }}" class="kc-list-row__title text-decoration-none">
                    {{ $ticket->name ?: 'Noma’lum foydalanuvchi' }}
                  </a>
                  <span class="badge rounded-pill text-bg-light border">#{{ $ticket->id }}</span>
                  <span class="badge rounded-pill {{ $st['class'] }}">{{ $st['label'] }}</span>
                  <span class="kc-inline-label">{{ $ticket->messages_count ?? 0 }} ta xabar</span>
                </div>

                <div class="kc-list-row__meta">
                  @if($ticket->username)
                    <span>t.me/{{ $ticket->username }}</span>
                  @endif
                  <span>Telegram ID: {{ $ticket->user_id ?: '—' }}</span>
                  @if($ticket->created_at)
                    <span>{{ $ticket->created_at->format('d.m.Y H:i') }}</span>
                  @endif
                </div>

                <div class="kc-list-row__snippet">
                  {{ \Illuminate\Support\Str::limit($lastMessage?->message ?: $ticket->first_msg ?: 'Murojaat matni yo‘q', 220) }}
                </div>

                <div class="kc-list-row__chips">
                  @if($lastMessage)
                    <span class="kc-inline-label {{ $lastTone }}">{{ $actorLabel($lastMessage->sent_by) }}</span>
                    <span class="kc-inline-label">{{ strtoupper($lastMessage->message_type) }}</span>
                    <span class="kc-inline-label">{{ $lastMessage->created_at?->format('d.m H:i') }}</span>
                  @else
                    <span class="kc-inline-label">Yozishma tarixi hali yo‘q</span>
                  @endif

                  @if($ticket->operator)
                    <span class="kc-inline-label primary">
                      Operator: {{ $ticket->operator->name ?? ($ticket->operator->username ? '@'.$ticket->operator->username : $ticket->operator->telegram_id) }}
                    </span>
                  @elseif($ticket->status === 'queue')
                    <span class="kc-inline-label warning">Operator tayinlanmagan</span>
                  @endif

                  @if($ticket->rating)
                    <span class="kc-inline-label success">Baho: {{ $ticket->rating }}/5</span>
                  @endif
                </div>
              </div>
            </div>

            <div class="kc-list-row__actions">
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
        <div class="text-center py-5 text-secondary">
          <i class="bi bi-chat-square-text d-block mb-2" style="font-size:2rem;"></i>
          Murojaatlar topilmadi.
        </div>
      @endforelse
    </div>
  </x-admin.section-card>

  @if($tickets->hasPages())
    <div>{{ $tickets->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
