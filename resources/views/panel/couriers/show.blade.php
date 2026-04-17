@extends('panel.layouts.panel')
@section('title', $courier->first_name.' '.$courier->last_name)
@section('page-title', $courier->first_name.' '.$courier->last_name)

@section('content')

@php
  $cs    = trim((string)($courier->status ?? ''));
  $stCls = match($cs) { 'approved'=>'success', 'rejected'=>'danger', default=>'warning' };
  $stLbl = match($cs) { 'approved'=>'Tasdiqlangan', 'rejected'=>'Rad etildi', default=>'Kutilmoqda' };
@endphp

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div class="d-flex align-items-center gap-3">
    <a href="{{ route('panel.couriers.index') }}" class="btn-p ghost icon">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">{{ $courier->first_name }} {{ $courier->last_name }}</h1>
      <p class="page-sub" style="display:flex;align-items:center;gap:8px">
        ID: #{{ $courier->id }}
        <span class="s-pill {{ $stCls }}" style="font-size:11px">{{ $stLbl }}</span>
      </p>
    </div>
  </div>
  <div class="d-flex gap-2">
    @if($cs === 'pending')
      <form method="POST" action="{{ route('panel.couriers.approve', $courier) }}">
        @csrf @method('PATCH')
        <button class="btn-p success"><i class="bi bi-check-lg"></i> Tasdiqlash</button>
      </form>
      <form method="POST" action="{{ route('panel.couriers.reject', $courier) }}">
        @csrf @method('PATCH')
        <button class="btn-p danger ghost"><i class="bi bi-x-lg"></i> Rad etish</button>
      </form>
    @elseif($cs === 'rejected')
      <form method="POST" action="{{ route('panel.couriers.approve', $courier) }}">
        @csrf @method('PATCH')
        <button class="btn-p ghost">
          <i class="bi bi-arrow-counterclockwise"></i> Qayta tasdiqlash
        </button>
      </form>
    @elseif($cs === 'approved')
      <form method="POST" action="{{ route('panel.couriers.reject', $courier) }}"
            onsubmit="return confirm('Kuryerni bloklaysizmi?')">
        @csrf @method('PATCH')
        <button class="btn-p danger ghost">
          <i class="bi bi-slash-circle"></i> Bloklash
        </button>
      </form>
    @endif
    <a href="{{ route('panel.couriers.edit', $courier) }}" class="btn-p ghost">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
  </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4 fade-up">
  @foreach([
    [$orderCount,                                  'Buyurtmalar',  'accent',  'bi-bicycle'],
    [number_format($totalEarned/1000).'K UZS',     'Jami topdi',   'success', 'bi-cash-stack'],
    [number_format($pendingPay/1000).'K UZS',      'Kutilmoqda',   'warning', 'bi-hourglass-split'],
    [number_format($courier->balance ?? 0).' UZS', 'Balans',       'info',    'bi-wallet2'],
  ] as [$val,$lbl,$clr,$icon])
  <div class="col-6 col-xl-3">
    <div class="p-card d-flex align-items-center gap-3" style="padding:16px">
      <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;font-size:18px;
                  background:var(--p-{{ $clr }}-d,var(--p-elevated));
                  color:var(--p-{{ $clr }});display:flex;align-items:center;justify-content:center">
        <i class="bi {{ $icon }}"></i>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;
                    color:var(--p-text)">{{ $val }}</div>
        <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;
                    letter-spacing:.07em">{{ $lbl }}</div>
      </div>
    </div>
  </div>
  @endforeach
</div>

<div class="row g-3">

  {{-- ── CHAP ─────────────────────────────────────────────────── --}}
  <div class="col-xl-4">

    <div class="p-card mb-3 fade-up">
      <div style="text-align:center;padding:24px 20px 16px">
        <div style="width:72px;height:72px;border-radius:50%;margin:0 auto 12px;overflow:hidden;
                    background:linear-gradient(135deg,#14b8a6,#0d9488);
                    display:flex;align-items:center;justify-content:center;
                    font-size:26px;font-weight:700;color:#fff">
          @if($courier->photo)
            <img src="{{ asset('storage/'.$courier->photo) }}"
                 style="width:100%;height:100%;object-fit:cover">
          @else
            {{ strtoupper(substr($courier->first_name, 0, 1)) }}
          @endif
        </div>
        <div style="font-size:17px;font-weight:700;color:var(--p-text)">
          {{ $courier->first_name }} {{ $courier->last_name }}
        </div>
        <div style="font-size:12px;color:var(--p-hint);margin-top:2px">
          {{ $courier->phone_number }}
        </div>
        <div class="mt-2">
          <span class="s-pill {{ $stCls }}" style="font-size:11px">{{ $stLbl }}</span>
        </div>
      </div>

      <div style="border-top:1px solid var(--p-border);padding:16px 20px 8px">
        @foreach([
          ['bi-telephone', 'Telefon',    $courier->phone_number],
          ['bi-geo-alt',   'Viloyat',    $courier->region],
          ['bi-wallet2',   'Balans',     number_format($courier->balance ?? 0).' UZS'],
          ['bi-calendar',  "Qo'shildi", $courier->created_at?->format('d.m.Y')],
        ] as [$icon, $label, $value])
        <div class="d-flex align-items-start gap-3 mb-3">
          <div style="width:28px;height:28px;border-radius:7px;background:var(--p-elevated);
                      display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="bi {{ $icon }}" style="font-size:12px;color:var(--p-muted)"></i>
          </div>
          <div>
            <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                        letter-spacing:.07em">{{ $label }}</div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $value }}</div>
          </div>
        </div>
        @endforeach
      </div>

      @if($courier->fcm_token)
      <div style="border-top:1px solid var(--p-border);padding:12px 20px">
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                    letter-spacing:.07em;margin-bottom:4px">FCM Token</div>
        <div style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--p-muted);
                    word-break:break-all;background:var(--p-elevated);
                    padding:6px 8px;border-radius:6px">
          {{ Str::limit($courier->fcm_token, 60) }}
        </div>
      </div>
      @endif
    </div>

    @if($devices->count())
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-phone me-1"></i> Qurilmalar</div>
        <span class="s-pill muted" style="font-size:10px">{{ $devices->count() }} ta</span>
      </div>
      <div style="padding:0 18px 14px">
        @foreach($devices as $dev)
        <div style="padding:9px 0;border-bottom:1px solid var(--p-border);
                    {{ $loop->last ? 'border-bottom:none' : '' }}">
          <div style="font-size:13px;font-weight:500;color:var(--p-text)">
            {{ $dev->device_name ?: "Noma'lum qurilma" }}
          </div>
          <div style="font-size:11px;color:var(--p-hint);margin-top:1px">
            {{ $dev->platform }}
            @if($dev->created_at) · {{ \Carbon\Carbon::parse($dev->created_at)->format('d.m.Y') }} @endif
            @if($dev->fcm_token ?? null)
              <span class="s-pill success" style="font-size:9px;margin-left:4px">FCM</span>
            @endif
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    @if($banLogs->count())
    <div class="p-card fade-up" style="border-color:rgba(255,92,106,.2);background:var(--p-danger-d)">
      <div class="p-card-header">
        <div class="p-card-title" style="color:var(--p-danger)">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> Ban loglari
        </div>
      </div>
      <div style="padding:0 18px 14px">
        @foreach($banLogs as $log)
        <div style="padding:10px 0;border-bottom:1px solid rgba(255,92,106,.15);
                    {{ $loop->last ? 'border-bottom:none' : '' }}">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="s-pill {{ ($log->type ?? '')=='warning' ? 'warning' : 'muted' }}"
                  style="font-size:10px">
              {{ ($log->type ?? '') === 'warning' ? 'Ogohlantirish' : 'Ban' }}
            </span>
            <span style="font-size:10px;color:var(--p-hint)">
              {{ $log->created_at?->format('d.m.Y') }}
            </span>
          </div>
          <div style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $log->title }}</div>
          @if($log->message ?? null)
          <div style="font-size:12px;color:var(--p-muted);margin-top:2px">
            {{ Str::limit($log->message, 80) }}
          </div>
          @endif
        </div>
        @endforeach
      </div>
    </div>
    @endif

  </div>

  {{-- ── O'NG: Tabs ───────────────────────────────────────────── --}}
  <div class="col-xl-8">

    @php $activeTab = request('section', 'orders'); @endphp
    <div class="d-flex gap-2 flex-wrap mb-3 fade-up">
      @foreach([
        ['orders',       'Buyurtmalar',    'bi-bicycle',    $orderCount],
        ['transactions', 'Tranzaksiyalar', 'bi-credit-card', null],
      ] as [$key,$lbl,$icon,$cnt])
      <a href="{{ request()->fullUrlWithQuery(['section'=>$key]) }}"
         class="btn-p {{ $activeTab===$key?'':'ghost' }} sm" style="gap:5px">
        <i class="bi {{ $icon }}"></i> {{ $lbl }}
        @if($cnt !== null)<span class="tab-badge">{{ $cnt }}</span>@endif
      </a>
      @endforeach
    </div>

    @if($activeTab === 'orders')
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">So'nggi buyurtmalar</div>
        <a href="{{ route('panel.courier-orders.index', ['courier_id'=>$courier->id]) }}"
           class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="table-responsive">
        <table class="p-table">
          <thead>
            <tr>
              <th>#Buyurtma</th><th>Mijoz</th><th>Summa</th>
              <th>Kuryer haq</th><th>Status</th><th>Sana</th><th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentOrders as $order)
            @php
              $oCls = match($order->status ?? '') {
                'delivered'   => 'ob-c',
                'in_delivery' => 'ob-b',
                'pending'     => 'ob-a',
                'rejected'    => 'ob-f',
                default       => 'ob-p',
              };
              $oLbl = match($order->status ?? '') {
                'delivered'   => 'Yetkazildi',
                'in_delivery' => "Yo'lda",
                'pending'     => 'Kutilmoqda',
                'rejected'    => 'Rad etildi',
                default       => $order->status ?? '—',
              };
            @endphp
            <tr>
              <td>
                <a href="{{ route('panel.orders.show', $order->order_id) }}"
                   style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">
                  #{{ $order->order_id }}
                </a>
              </td>
              <td>
                @if($order->user)
                <div class="d-flex align-items-center gap-2">
                  <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;
                              background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                              display:flex;align-items:center;justify-content:center;
                              font-size:11px;font-weight:600;color:#fff">
                    @if($order->user->avatar)
                      <img src="{{ $order->user->avatar }}"
                           style="width:100%;height:100%;object-fit:cover">
                    @else{{ strtoupper(substr($order->user->name ?? 'U', 0, 1)) }}@endif
                  </div>
                  <span style="font-size:12px;color:var(--p-text)">
                    {{ $order->user->name }} {{ $order->user->lastname }}
                  </span>
                </div>
                @else
                  <span style="color:var(--p-hint);font-size:12px">#{{ $order->user_id }}</span>
                @endif
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-weight:600;
                         font-size:13px;color:var(--p-text)">
                {{ number_format($order->amount) }}
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-size:12px;
                         color:var(--p-success);font-weight:600">
                {{ number_format($order->courierPrice) }}
              </td>
              <td><span class="o-badge {{ $oCls }}">{{ $oLbl }}</span></td>
              <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
                {{ $order->created_at?->format('d.m H:i') }}
              </td>
              <td>
                <a href="{{ route('panel.orders.show', $order->order_id) }}"
                   class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" style="text-align:center;padding:30px;color:var(--p-hint)">
                Buyurtmalar yo'q
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

    @if($activeTab === 'transactions')
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div>
          <div class="p-card-title">Chiqim tranzaksiyalari</div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:2px">
            Tasdiqlangan:
            <span style="color:var(--p-success);font-weight:600">
              {{ number_format($totalEarned) }} UZS
            </span>
            · Kutilmoqda:
            <span style="color:var(--p-warning);font-weight:600">
              {{ number_format($pendingPay) }} UZS
            </span>
          </div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="p-table">
          <thead>
            <tr>
              <th>#</th><th>Karta</th><th>Miqdor</th>
              <th>Komissiya</th><th>Toza miqdor</th><th>Status</th><th>Sana</th>
            </tr>
          </thead>
          <tbody>
            @forelse($transactions as $tx)
            @php
              $txS   = trim((string)($tx->status ?? ''));
              $txCls = match($txS) { 'approved'=>'success', 'rejected'=>'danger', default=>'warning' };
              $txLbl = match($txS) { 'approved'=>'Tasdiqlangan', 'rejected'=>'Rad etildi', default=>'Kutilmoqda' };
            @endphp
            <tr>
              <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">#{{ $tx->id }}</td>
              <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">
                {{ $tx->card ? '****'.substr($tx->card, -4) : '—' }}
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text)">
                {{ number_format($tx->amount) }}
              </td>
              <td style="font-size:12px;color:var(--p-danger)">
                {{ $tx->commissionPercent ?? 0 }}%
                @if($tx->commissionPrice ?? null)
                  <span style="color:var(--p-hint)">({{ number_format($tx->commissionPrice) }})</span>
                @endif
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-success)">
                {{ number_format($tx->netAmount ?? $tx->amount) }}
              </td>
              <td>
                <span class="s-pill {{ $txCls }}" style="font-size:11px">{{ $txLbl }}</span>
              </td>
              <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
                {{ $tx->created_at?->format('d.m.Y H:i') }}
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" style="text-align:center;padding:30px;color:var(--p-hint)">
                Tranzaksiyalar yo'q
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @endif

  </div>
</div>

@endsection