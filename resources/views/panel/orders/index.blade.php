@extends('panel.layouts.panel')
@section('title','Buyurtmalar')
@section('page-title','Buyurtmalar')
@section('breadcrumb','Panel / Buyurtmalar')

@section('content')

{{-- ── Page header ───────────────────────────────────────────────── --}}
<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Buyurtmalar</h1>
    <p class="page-sub">Barcha buyurtmalar ro'yxati</p>
  </div>
  <a href="{{ route('panel.orders.export', request()->all()) }}" class="btn-p ghost" style="gap:6px">
    <i class="bi bi-download"></i> Export
  </a>
</div>

{{-- ── Stats ──────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3 fade-up d1">
    <div class="metric-card" style="border-top-color:var(--p-warning)">
      <div class="metric-icon" style="background:var(--p-warning-d);color:var(--p-warning)">
        <i class="bi bi-hourglass-split"></i>
      </div>
      <div class="metric-label">Kutilmoqda</div>
      <div class="metric-value">{{ number_format($counts['A']) }}</div>
      <span class="s-pill warning" style="font-size:11px">Yangi buyurtmalar</span>
    </div>
  </div>
  <div class="col-6 col-xl-3 fade-up d2">
    <div class="metric-card" style="border-top-color:var(--p-info)">
      <div class="metric-icon" style="background:rgba(59,130,246,.12);color:var(--p-info)">
        <i class="bi bi-truck"></i>
      </div>
      <div class="metric-label">Yo'lda</div>
      <div class="metric-value">{{ number_format($counts['B']) }}</div>
      <span class="s-pill info" style="font-size:11px">Yetkazilmoqda</span>
    </div>
  </div>
  <div class="col-6 col-xl-3 fade-up d3">
    <div class="metric-card" style="border-top-color:var(--p-success)">
      <div class="metric-icon" style="background:var(--p-success-d);color:var(--p-success)">
        <i class="bi bi-currency-dollar"></i>
      </div>
      <div class="metric-label">Bugun daromad</div>
      <div class="metric-value">{{ number_format($stats['today_revenue']/1000000, 1) }}M</div>
      <span class="s-pill success" style="font-size:11px">{{ $stats['today_count'] }} ta buyurtma</span>
    </div>
  </div>
  <div class="col-6 col-xl-3 fade-up d4">
    <div class="metric-card" style="border-top-color:var(--p-accent)">
      <div class="metric-icon" style="background:var(--p-accent-d);color:var(--p-accent)">
        <i class="bi bi-graph-up-arrow"></i>
      </div>
      <div class="metric-label">Jami daromad</div>
      <div class="metric-value">{{ number_format($stats['total_revenue']/1000000, 1) }}M</div>
      <span class="s-pill accent" style="font-size:11px">UZS</span>
    </div>
  </div>
</div>

{{-- ── Status tabs ─────────────────────────────────────────────────── --}}
<div class="tab-pills fade-up mb-3">
  @php
    $tabs = [
      'A'   => ['Kutilmoqda',     'warning'],
      'P'   => ['Qadoqlanmoqda',  'muted'],
      'B'   => ["Yo'lda",         'info'],
      'C'   => ['Yetkazildi',     'success'],
      'F'   => ['Bekor',          'danger'],
      'all' => ['Barchasi',       'accent'],
    ];
  @endphp
  @foreach($tabs as $key => [$label, $pill])
  <a href="{{ route('panel.orders.index', array_merge(request()->except('tab','page'), ['tab'=>$key])) }}"
     class="tab-pill {{ $tab === $key ? 'active' : '' }}">
    {{ $label }}
    <span class="tab-count" style="{{ $tab===$key ? 'background:var(--p-accent);color:#fff' : '' }}">
      {{ $counts[$key] }}
    </span>
  </a>
  @endforeach
</div>

{{-- ── Filter ───────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('panel.orders.index') }}" id="orderFilter" class="fade-up">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="filter-bar mb-3">
    <div class="search-box" style="width:220px;margin-left:0">
      <i class="bi bi-search"></i>
      <input type="text" name="search" value="{{ request('search') }}"
             placeholder="ID, ism, telefon..."
             onchange="orderFilter.submit()"/>
    </div>
    <select name="payment_status" class="p-form-control" style="width:170px" onchange="orderFilter.submit()">
      <option value="">To'lov holati</option>
      <option value="0" {{ request('payment_status')==='0'?'selected':'' }}>Qabul qilinganida</option>
      <option value="1" {{ request('payment_status')==='1'?'selected':'' }}>Karta (kutilmoqda)</option>
      <option value="2" {{ request('payment_status')==='2'?'selected':'' }}>To'langan</option>
      <option value="3" {{ request('payment_status')==='3'?'selected':'' }}>Rad etildi</option>
    </select>
    <select name="delivery_type" class="p-form-control" style="width:160px" onchange="orderFilter.submit()">
      <option value="">Yetkazish turi</option>
      <option value="Kuryer"  {{ request('delivery_type')==='Kuryer'?'selected':'' }}>Kuryer</option>
      <option value="Starex"  {{ request('delivery_type')==='Starex'?'selected':'' }}>Starex (Pochta)</option>
    </select>
    <select name="gift_filter" class="p-form-control" style="width:140px" onchange="orderFilter.submit()">
      <option value="">Hammasi</option>
      <option value="gift"     {{ request('gift_filter')==='gift'?'selected':'' }}>🎁 Sovg'ali</option>
      <option value="other"    {{ request('gift_filter')==='other'?'selected':'' }}>👤 Boshqasiga</option>
      <option value="packaging"{{ request('gift_filter')==='packaging'?'selected':'' }}>📦 Qadoqlangan</option>
    </select>
    @if(request()->hasAny(['search','payment_status','delivery_type','gift_filter']))
    <a href="{{ route('panel.orders.index',['tab'=>$tab]) }}" class="btn-p ghost" style="gap:5px">
      <i class="bi bi-x-circle"></i> Tozalash
    </a>
    @endif
  </div>
</form>

{{-- ── Table ────────────────────────────────────────────────────────── --}}
<div class="p-card fade-up">
  <div class="p-card-header">
    <div>
      <div class="p-card-title">Buyurtmalar ro'yxati</div>
      <div class="p-card-sub">{{ $orders->total() }} ta natija</div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="p-table" style="min-width:900px">
      <thead>
        <tr>
          <th style="width:80px">#ID</th>
          <th>Mijoz</th>
          <th>Mahsulotlar</th>
          <th>Summa</th>
          <th>To'lov</th>
          <th>Yetkazish</th>
          <th>Belgilar</th>
          <th>Status</th>
          <th style="width:80px">Sana</th>
          <th style="width:50px"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $order)
        @php
          $statusMap = [
            'A' => ['warning','Kutilmoqda'],
            'P' => ['muted','Qadoqlanmoqda'],
            'B' => ['info',"Yo'lda"],
            'C' => ['success','Yetkazildi'],
            'F' => ['danger','Bekor'],
          ];
          $st = $statusMap[$order->status] ?? ['muted',$order->status];

          $payMap = [
            '0'=>['muted','Naqd'],
            '1'=>['info','Karta'],
            '2'=>['success',"To'langan"],
            '3'=>['danger','Rad'],
          ];
          $pay = $payMap[(string)$order->paymentStatus] ?? ['muted','—'];

          $itemCount = collect($order->items ?? [])->where('type','!=','gift')->sum('count_item');
          $previewItems = collect($order->items ?? [])->where('type','!=','gift')->take(2);
        @endphp
        <tr>
          {{-- ID --}}
          <td>
            <a href="{{ route('panel.orders.show', $order) }}"
               style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);
                      font-weight:700;font-size:14px;text-decoration:none">
              #{{ $order->id }}
            </a>
          </td>

          {{-- Mijoz --}}
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="av av-blue" style="width:32px;height:32px;font-size:12px;flex-shrink:0">
                {{ strtoupper(substr($order->user?->name ?? 'U', 0, 1)) }}
              </div>
              <div>
                <div style="font-size:13px;font-weight:600;color:var(--p-text);white-space:nowrap">
                  {{ $order->user ? $order->user->name.' '.$order->user->lastname : 'Mehmon' }}
                </div>
                <div style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                  {{ $order->user?->phone_number ?? '—' }}
                </div>
              </div>
            </div>
          </td>

          {{-- Mahsulotlar preview --}}
          <td>
            <div style="max-width:200px">
              @foreach($previewItems as $item)
              <div style="font-size:12px;color:var(--p-text);
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
                          max-width:200px">
                {{ $item['name'] ?? '—' }}
              </div>
              @endforeach
              @if($itemCount > 2)
              <div style="font-size:11px;color:var(--p-hint)">+ {{ $itemCount - 2 }} ta ko'proq</div>
              @endif
            </div>
          </td>

          {{-- Summa --}}
          <td>
            <div style="font-family:'JetBrains Mono',monospace;font-weight:700;
                        color:var(--p-text);font-size:14px;white-space:nowrap">
              {{ number_format($order->amount) }}
              <span style="font-size:10px;font-weight:400;color:var(--p-hint)">UZS</span>
            </div>
            @if($order->discountAmount > 0)
            <div style="font-size:11px;color:var(--p-success)">
              -{{ number_format($order->discountAmount) }} chegirma
            </div>
            @endif
            @if(($order->packaging_price ?? 0) > 0)
            <div style="font-size:11px;color:var(--p-muted)">
              +{{ number_format($order->packaging_price) }} qadoq
            </div>
            @endif
          </td>

          {{-- To'lov --}}
          <td>
            <span class="s-pill {{ $pay[0] }}" style="font-size:11px">{{ $pay[1] }}</span>
          </td>

          {{-- Yetkazish --}}
          <td>
            <div style="font-size:12px;color:var(--p-text);font-weight:500">
              {{ $order->deliveryType ?? '—' }}
            </div>
            @if($order->deliveryPrice > 0)
            <div style="font-size:11px;color:var(--p-hint)">
              {{ number_format($order->deliveryPrice) }} UZS
            </div>
            @else
            <div style="font-size:11px;color:var(--p-success)">Bepul</div>
            @endif
          </td>

          {{-- Belgilar --}}
          <td>
            <div class="d-flex gap-1 flex-wrap">
              @if($order->gift)
              <span class="s-pill accent" style="font-size:10px" title="Sovg'ali">🎁</span>
              @endif
              @if($order->is_gift_to_other ?? false)
              <span class="s-pill info" style="font-size:10px" title="Boshqasiga sovg'a">👤</span>
              @endif
              @if($order->with_packaging ?? false)
              <span class="s-pill muted" style="font-size:10px" title="Qadoqlash">📦</span>
              @endif
              @if($order->cashbackAmount > 0)
              <span class="s-pill success" style="font-size:10px" title="Cashback ishlatildi">💰</span>
              @endif
              @if($order->giftCertAmount > 0)
              <span class="s-pill warning" style="font-size:10px" title="Sertifikat ishlatildi">🎟</span>
              @endif
              @if($order->promocode)
              <span class="s-pill accent" style="font-size:10px" title="Promokod: {{ $order->promocode }}">%</span>
              @endif
            </div>
          </td>

          {{-- Status + dropdown --}}
          <td>
            <div class="d-flex align-items-center gap-1">
              <span class="s-pill {{ $st[0] }}" style="font-size:11px">{{ $st[1] }}</span>
              <div class="dropdown">
                <button class="btn-p ghost sm p-0"
                        style="width:22px;height:22px;border-radius:6px;padding:0"
                        data-bs-toggle="dropdown">
                  <i class="bi bi-chevron-down" style="font-size:10px"></i>
                </button>
                <ul class="dropdown-menu"
                    style="background:var(--p-surface);border:1px solid var(--p-border);
                           border-radius:10px;min-width:150px;padding:5px">
                  @foreach(['A'=>'Kutilmoqda','P'=>'Qadoqlanmoqda','B'=>"Yo'lda",'C'=>'Yetkazildi','F'=>'Bekor'] as $val=>$lbl)
                  <li>
                    <form method="POST" action="{{ route('panel.orders.status', $order) }}">
                      @csrf @method('PATCH')
                      <input type="hidden" name="status" value="{{ $val }}">
                      <button type="submit" class="dropdown-item"
                              style="color:{{ $val===$order->status?'var(--p-accent)':'var(--p-text)' }};
                                     font-size:12px;padding:6px 12px;border-radius:6px">
                        {{ $val===$order->status?'✓ ':'' }}{{ $lbl }}
                      </button>
                    </form>
                  </li>
                  @endforeach
                </ul>
              </div>
            </div>
          </td>

          {{-- Sana --}}
          <td style="font-size:11px;color:var(--p-hint);
                     font-family:'JetBrains Mono',monospace;white-space:nowrap">
            {{ $order->created_at?->format('d.m') }}<br>
            <span style="font-size:10px">{{ $order->created_at?->format('H:i') }}</span>
          </td>

          {{-- Ko'rish --}}
          <td>
            <a href="{{ route('panel.orders.show', $order) }}"
               class="btn-p ghost sm" style="padding:5px 8px">
              <i class="bi bi-arrow-right"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="10" style="text-align:center;padding:60px 20px;color:var(--p-hint)">
            <i class="bi bi-inbox" style="font-size:40px;display:block;margin-bottom:10px;
                                          color:var(--p-border)"></i>
            <div style="font-size:14px">Buyurtmalar topilmadi</div>
            <div style="font-size:12px;margin-top:4px">Filtrni o'zgartirib ko'ring</div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($orders->hasPages())
  <div class="d-flex align-items-center justify-content-between"
       style="padding:12px 16px;border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $orders->firstItem() }}–{{ $orders->lastItem() }} / {{ $orders->total() }} ta
    </div>
    <div class="p-pagination">
      @if($orders->onFirstPage())
        <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
      @else
        <a href="{{ $orders->previousPageUrl() }}" class="p-page-btn"><i class="bi bi-chevron-left"></i></a>
      @endif
      @foreach($orders->getUrlRange(max(1,$orders->currentPage()-2),min($orders->lastPage(),$orders->currentPage()+2)) as $page=>$url)
        <a href="{{ $url }}" class="p-page-btn {{ $page===$orders->currentPage()?'active':'' }}">{{ $page }}</a>
      @endforeach
      @if($orders->hasMorePages())
        <a href="{{ $orders->nextPageUrl() }}" class="p-page-btn"><i class="bi bi-chevron-right"></i></a>
      @else
        <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
      @endif
    </div>
  </div>
  @endif
</div>

@endsection