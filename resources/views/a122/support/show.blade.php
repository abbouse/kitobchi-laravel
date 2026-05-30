@extends('a122.layouts.admin')
@section('title', 'Murojaat #'.$botTicket->id)
@section('page-title', 'Murojaat #'.$botTicket->id)

@section('content')
@php
  $st = $statuses[$botTicket->status] ?? ['label' => $botTicket->status, 'class' => 'ob-p'];
  $messageCount = $botTicket->messages->count();
@endphp

<x-a122.page-header back-href="{{ route('admin.support.index') }}">
  <x-slot name="heading">Murojaat #{{ $botTicket->id }}</x-slot>
  <x-slot name="meta">{{ $botTicket->created_at?->format('d.m.Y H:i') }}</x-slot>
</x-a122.page-header>

<section class="a122-section mb-4">
  <div class="a122-section-body">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="kpi-soft">
        <div class="metric-label">Holat</div>
        <div class="metric-value text-xl">{{ $st['label'] }}</div>
        <div class="metric-meta">Joriy support bosqichi</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Tarix</div>
        <div class="metric-value text-xl">{{ $messageCount }}</div>
        <div class="metric-meta">Saqlangan xabarlar</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Baholash</div>
        <div class="metric-value text-xl">{{ $botTicket->rating ?: '—' }}</div>
        <div class="metric-meta">5 ballik tizim</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Operator</div>
        <div class="metric-value text-xl">{{ $botTicket->operator?->name ? \Illuminate\Support\Str::limit($botTicket->operator->name, 14) : '—' }}</div>
        <div class="metric-meta">{{ $botTicket->operator ? 'Biriktirilgan' : 'Tayinlanmagan' }}</div>
      </div>
    </div>
  </div>
</section>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  <div class="xl:col-span-8">

    {{-- Suhbat tarixi --}}
    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Suhbat tarixi</div>
          <div class="a122-section-head__meta">Foydalanuvchi, operator, admin va tizim bo‘yicha to‘liq tarix.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <div class="space-y-3">
          @forelse($botTicket->messages as $msg)
            @php
              $isUser = $msg->sent_by === 'user';
              $isAdmin = $msg->sent_by === 'admin';
              $isOperator = $msg->sent_by === 'operator';
              $isSystem = $msg->sent_by === 'system';
              $bubbleClass = $isUser ? 'bg-[var(--p-elevated)] border-[var(--p-border)]' : ($isSystem ? 'bg-[var(--p-warning-d)] border-[rgba(245,166,35,.18)]' : 'bg-[var(--p-accent-soft)] border-[rgba(88,101,242,.12)]');
              $title = $isUser ? 'Foydalanuvchi' : ($isAdmin ? ('Admin · '.($msg->admin?->name ?: '')) : ($isOperator ? ('Operator · '.($msg->operator?->name ?: $msg->operator_id)) : 'Tizim'));
            @endphp
            <div class="rounded-2xl border {{ $bubbleClass }} px-4 py-3">
              <div class="flex items-center justify-between gap-3 mb-2">
                <div class="text-xs font-bold tracking-[0.08em] uppercase text-[var(--p-hint)]">{{ trim($title) }}</div>
                <div class="text-xs text-[var(--p-hint)]">{{ $msg->created_at?->format('d.m.Y H:i') }}</div>
              </div>
              <div class="text-sm leading-7 text-[var(--p-text)] whitespace-pre-line">{{ $msg->message ?: '—' }}</div>
              <div class="mt-2 flex items-center gap-2 text-[11px] text-[var(--p-hint)]">
                <span>{{ strtoupper($msg->message_type) }}</span>
                @if($msg->sent_by === 'admin')
                  <span>·</span>
                  <span class="{{ $msg->is_delivered ? 'text-emerald-600' : 'text-rose-600' }}">{{ $msg->is_delivered ? 'Yuborilgan' : 'Yuborilmadi' }}</span>
                @endif
                @if($msg->delivery_error)
                  <span>·</span>
                  <span class="text-rose-600">{{ $msg->delivery_error }}</span>
                @endif
              </div>
            </div>
          @empty
            <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-elevated)] px-4 py-4 text-sm text-[var(--p-muted)]">
              History hali yozilmagan.
            </div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Admin javobi</div>
          <div class="a122-section-head__meta">Admin paneldan foydalanuvchiga bevosita xabar yuborish.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <form method="POST" action="{{ route('admin.support.reply', $botTicket) }}">
          @csrf
          <label class="p-form-label">Javob matni</label>
          <textarea name="message" rows="5" class="p-form-control" placeholder="Foydalanuvchiga yuboriladigan javob..." required>{{ old('message') }}</textarea>
          <div class="flex justify-end mt-3">
            <button class="btn-p primary"><i class="bi bi-send"></i> Xabar yuborish</button>
          </div>
        </form>
      </div>
    </div>

    {{-- Ilovalar --}}
    @if($botTicket->attachments && $botTicket->attachments->count())
    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Ilovalar</div>
          <div class="a122-section-head__meta">{{ $botTicket->attachments->count() }} ta biriktirma, tur va yuboruvchi bilan.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <div class="table-responsive kc-twrap">
          <table class="table data-table align-middle mb-0">
            <thead>
              <tr><th>Fayl nomi</th><th>Tur</th><th>Hajm</th><th>Yuboruvchi</th></tr>
            </thead>
            <tbody>
              @foreach($botTicket->attachments as $att)
              <tr>
                <td style="font-size:13px;color:var(--p-text)">{{ $att->file_name ?: $att->file_id }}</td>
                <td><span class="s-pill muted">{{ $att->file_type }}</span></td>
                <td style="font-size:12px;color:var(--p-hint)">
                  {{ $att->file_size ? number_format($att->file_size / 1024, 1).' KB' : '—' }}
                </td>
                <td>
                  <span class="s-pill {{ $att->sent_by==='operator'?'accent':'muted' }}">
                    {{ $att->sent_by==='operator'?'Operator':'Foydalanuvchi' }}
                  </span>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    @endif

    {{-- Yopish --}}
    @if(in_array($botTicket->status, ['queue','active']))
    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Murojaatni yopish</div>
          <div class="a122-section-head__meta">Yakunlash sababi bilan ticketni operatsion yopish.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <form method="POST" action="{{ route('admin.support.close', $botTicket) }}">
          @csrf @method('PATCH')
          <label class="p-form-label">Yopish sababi (ixtiyoriy)</label>
          <div class="flex gap-2 mt-1">
            <input type="text" name="close_reason" class="p-form-control flex-fill"
                   placeholder="Muammo hal qilindi..." maxlength="100">
            <button class="btn-p danger">
              <i class="bi bi-x-circle"></i> Yopish
            </button>
          </div>
        </form>
      </div>
    </div>
    @endif

    @if($botTicket->close_reason)
    <div class="a122-section border-[rgba(255,92,106,.24)] bg-[var(--p-danger-d)]">
      <div class="a122-section-body">
        <div class="text-xs font-bold tracking-[0.14em] text-[var(--p-danger)] uppercase mb-1">Yopish sababi</div>
        <div class="text-sm text-[var(--p-text)]">{{ $botTicket->close_reason }}</div>
      </div>
    </div>
    @endif

  </div>

  <div class="xl:col-span-4">

    {{-- Status --}}
    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Holat</div>
          <div class="a122-section-head__meta">Support oqimidagi status va foydalanuvchi bahosi.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <span class="o-badge {{ $st['class'] }}" style="font-size:13px;padding:6px 14px">{{ $st['label'] }}</span>

        @if($botTicket->rating)
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--p-border)">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:6px">FOYDALANUVCHI BAHOSI</div>
          <div style="display:flex;gap:4px">
            @for($i=1;$i<=5;$i++)
              <i class="bi bi-star{{ $i<=$botTicket->rating?'-fill':'' }}"
                 style="font-size:20px;color:{{ $i<=$botTicket->rating?'var(--p-warning)':'var(--p-border)' }}"></i>
            @endfor
          </div>
          <div style="font-size:14px;font-weight:700;color:var(--p-warning);margin-top:4px">
            {{ $botTicket->rating }} / 5
          </div>
        </div>
        @endif
      </div>
    </div>

    {{-- Foydalanuvchi --}}
    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Murojaat egasi</div>
          <div class="a122-section-head__meta">Telegram identifikatori va user qidiruvga tez o‘tish.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <div style="margin-bottom:12px">
          <div style="font-size:15px;font-weight:600;color:var(--p-text)">{{ $botTicket->name ?: 'Noma\'lum' }}</div>
          @if($botTicket->username)
            <div style="font-size:13px;color:var(--p-hint)">t.me/{{ $botTicket->username }}</div>
          @endif
          @if($botTicket->user_id)
            <div style="font-size:12px;color:var(--p-accent);font-family:'JetBrains Mono',monospace;margin-top:4px">
              Telegram ID: {{ $botTicket->user_id }}
            </div>
          @endif
        </div>
        @if($botTicket->user_id)
        <a href="{{ route('admin.users.index', ['search'=>$botTicket->user_id]) }}"
           class="btn-p ghost sm">
          Foydalanuvchini izlash <i class="bi bi-search"></i>
        </a>
        @endif
      </div>
    </div>

    {{-- Operator --}}
    <div class="a122-section mb-3">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Operator</div>
          <div class="a122-section-head__meta">Mas’ul xodimni biriktirish yoki almashtirish.</div>
        </div>
      </div>
      <div class="a122-section-body">
        @if($botTicket->operator)
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
            <div style="width:8px;height:8px;border-radius:50%;background:var(--p-{{ $botTicket->operator->status==='online'?'success':($botTicket->operator->status==='busy'?'warning':'muted') }});flex-shrink:0"></div>
            <div>
              <div style="font-size:14px;font-weight:600;color:var(--p-text)">
                {{ $botTicket->operator->name ?? 'Noma\'lum' }}
              </div>
              @if($botTicket->operator->username)
                <div style="font-size:12px;color:var(--p-hint)">@{{ $botTicket->operator->username }}</div>
              @endif
            </div>
          </div>
        @endif

        @if(in_array($botTicket->status, ['queue','active']))
        <form method="POST" action="{{ route('admin.support.assign', $botTicket) }}">
          @csrf @method('PATCH')
          <label class="p-form-label">{{ $botTicket->operator ? 'Operatorni o\'zgartirish' : 'Operator tayinlash' }}</label>
          <div class="flex gap-2 mt-1">
            <select name="operator_id" class="p-form-control flex-fill">
              <option value="">Tanlang...</option>
              @foreach($operators as $op)
              <option value="{{ $op->id }}" {{ (int) $botTicket->operator_id === (int) $op->telegram_id ? 'selected' : '' }}>
                {{ $op->name ?? $op->username }}
                ({{ $op->status }})
              </option>
              @endforeach
            </select>
            <button class="btn-p sm"><i class="bi bi-check-lg"></i></button>
          </div>
        </form>
        @endif
      </div>
    </div>

    {{-- Meta --}}
    <div class="a122-section">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Timeline</div>
          <div class="a122-section-head__meta">Ticketning yaratilish va o‘zgarish vaqt nuqtalari.</div>
        </div>
      </div>
      <div class="a122-section-body">
        @foreach([
          ['ID',         '#'.$botTicket->id],
          ['Yaratildi',  $botTicket->created_at?->format('d.m.Y H:i')],
          ['Yangilandi', $botTicket->updated_at?->format('d.m.Y H:i')],
        ] as [$k,$v])
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $k }}</span>
          <span style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $v }}</span>
        </div>
        @endforeach
      </div>
    </div>

  </div>
</div>
@endsection
