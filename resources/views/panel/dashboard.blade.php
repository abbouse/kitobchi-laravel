@extends('panel.layouts.panel')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
.kpi-card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:14px;padding:20px;position:relative;overflow:hidden;transition:border-color .2s,transform .2s;}
.kpi-card:hover{border-color:var(--p-border2);transform:translateY(-2px);}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:14px 14px 0 0;}
.kpi-card.green::before{background:var(--p-success)}.kpi-card.blue::before{background:var(--p-accent)}
.kpi-card.yellow::before{background:var(--p-warning)}.kpi-card.red::before{background:var(--p-danger)}
.kpi-card.cyan::before{background:var(--p-info)}.kpi-card.purple::before{background:#7c5cfc}
.kpi-card.teal::before{background:#14b8a6}.kpi-card.pink::before{background:#ec4899}
.kpi-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;margin-bottom:14px;}
.kpi-value{font-size:26px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-text);letter-spacing:-.5px;line-height:1;margin-bottom:5px;}
.kpi-label{font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;font-weight:500;margin-bottom:12px;}
.kpi-footer{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid var(--p-border);font-size:12px;}
.kpi-change{display:inline-flex;align-items:center;gap:3px;font-size:12px;font-weight:600;}
.kpi-change.up{color:var(--p-success)}.kpi-change.down{color:var(--p-danger)}.kpi-change.neutral{color:var(--p-muted)}
.dash-card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:14px;overflow:hidden;}
.dash-card-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px 0;margin-bottom:16px;}
.dash-card-title{font-size:14px;font-weight:600;color:var(--p-text);}
.dash-card-sub{font-size:12px;color:var(--p-hint);margin-top:2px;}
.dash-card-body{padding:0 20px 20px;}
.live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--p-success);box-shadow:0 0 0 2px rgba(34,201,142,.25);animation:pulse 2s infinite;flex-shrink:0;}
@keyframes pulse{0%,100%{box-shadow:0 0 0 2px rgba(34,201,142,.25)}50%{box-shadow:0 0 0 5px rgba(34,201,142,.05)}}
.dash-prog{margin-bottom:13px;}
.dash-prog-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;}
.dash-prog-label{font-size:12px;color:var(--p-muted);}
.dash-prog-val{font-size:12px;font-weight:600;color:var(--p-text);font-family:'JetBrains Mono',monospace;}
.dash-prog-track{height:5px;background:var(--p-elevated);border-radius:10px;overflow:hidden;}
.dash-prog-fill{height:100%;border-radius:10px;transition:width .8s cubic-bezier(.4,0,.2,1);}
.o-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:3px 9px;border-radius:20px;}
.o-badge::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0;}
.ob-c{background:var(--p-success-d);color:var(--p-success)}.ob-a{background:var(--p-warning-d);color:var(--p-warning)}
.ob-b{background:var(--p-info-d);color:var(--p-info)}.ob-f{background:var(--p-danger-d);color:var(--p-danger)}.ob-p{background:var(--p-elevated);color:var(--p-muted)}
.d-av{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#fff;flex-shrink:0;overflow:hidden;}
.d-av img{width:100%;height:100%;object-fit:cover;}
.top-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--p-border);}
.top-row:last-child{border-bottom:none;}
.book-thumb{width:36px;height:50px;border-radius:5px;overflow:hidden;background:var(--p-elevated);flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.book-thumb img{width:100%;height:100%;object-fit:cover;}
.rank-num{width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;font-family:'JetBrains Mono',monospace;flex-shrink:0;}
.rn-1{background:rgba(245,166,35,.2);color:var(--p-warning)}.rn-2{background:rgba(139,145,168,.12);color:var(--p-muted)}
.rn-3{background:rgba(205,127,50,.18);color:#cd7f32}.rn-n{background:transparent;color:var(--p-hint)}
.stat-grid{display:grid;border-top:1px solid var(--p-border);margin-top:16px;padding-top:16px;}
.stat-cell{text-align:center;}.stat-cell+.stat-cell{border-left:1px solid var(--p-border);}
.stat-cell-val{font-size:17px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-text);}
.stat-cell-lbl{font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-top:2px;}
.fin-row{display:flex;align-items:center;padding:10px 0;border-bottom:1px solid var(--p-border);}
.fin-row:last-child{border-bottom:none;}
.fin-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;margin-right:12px;}
.fin-val{font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;text-align:right;}
.fin-month{font-size:10px;color:var(--p-hint);text-align:right;margin-top:1px;font-family:'JetBrains Mono',monospace;}
.alert-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:9px;margin-bottom:8px;font-size:13px;}
.alert-item:last-child{margin-bottom:0;}
.alert-item.danger{background:var(--p-danger-d);border:1px solid rgba(255,92,106,.2);color:var(--p-danger);}
.alert-item.warning{background:var(--p-warning-d);border:1px solid rgba(245,166,35,.2);color:var(--p-warning);}
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-sub">
      {{ now()->format('d.m.Y, l') }} &nbsp;·&nbsp;
      <span style="color:var(--p-success)">
        <span class="live-dot" style="width:6px;height:6px;vertical-align:middle"></span>
        Real vaqt
      </span>
    </p>
  </div>
  <a href="{{ route('panel.dashboard',['clear_cache'=>1]) }}" class="btn-p ghost">
    <i class="bi bi-arrow-clockwise"></i> Yangilash
  </a>
</div>

{{-- ALERTS --}}
@if(count($alerts))
<div class="fade-up mb-4">
  @foreach($alerts as [$color,$icon,$title,$desc,$url])
  <div class="alert-item {{ $color }}">
    <i class="bi {{ $icon }}" style="font-size:16px;flex-shrink:0"></i>
    <div style="flex:1"><span style="font-weight:600">{{ $title }}:</span> {{ $desc }}</div>
    <a href="{{ $url }}" style="color:inherit;font-weight:700;white-space:nowrap;text-decoration:none">Ko'rish →</a>
  </div>
  @endforeach
</div>
@endif

{{-- ROW 1: 6 KPI karta --}}
<div class="row g-3 mb-4">

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card green">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Jami daromad</div>
          <div class="kpi-value">{{ number_format($totalRevenue/1_000_000,1) }}<span style="font-size:16px;color:var(--p-muted)">M</span></div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">UZS · To'langan</div>
        </div>
        <div class="kpi-icon" style="background:var(--p-success-d);color:var(--p-success)"><i class="bi bi-graph-up-arrow"></i></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-change up"><i class="bi bi-arrow-up-right"></i> Bugun: {{ number_format($todayRevenue/1000) }}K</span>
        <span style="color:var(--p-hint)">Bu oy: {{ number_format($monthRevenue/1_000_000,1) }}M</span>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card blue">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Buyurtmalar</div>
          <div class="kpi-value">{{ number_format($totalOrders) }}</div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">ta jami</div>
        </div>
        <div class="kpi-icon" style="background:var(--p-accent-d);color:var(--p-accent)"><i class="bi bi-bag-check"></i></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-change up"><i class="bi bi-plus"></i> {{ $todayOrders }} bugun</span>
        <div class="d-flex gap-2">
          <span style="color:var(--p-warning);font-size:11px">{{ $pendingOrders }} kutmoqda</span>
          <span style="color:var(--p-danger);font-size:11px">{{ $cancelledOrders }} bekor</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card cyan">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Yakunlanish</div>
          <div class="kpi-value">{{ $completionRate }}<span style="font-size:16px;color:var(--p-muted)">%</span></div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">{{ $completedOrders }} yetkazildi</div>
        </div>
        <div class="kpi-icon" style="background:var(--p-info-d);color:var(--p-info)"><i class="bi bi-patch-check"></i></div>
      </div>
      <div class="kpi-footer">
        <div style="flex:1;margin-right:10px">
          <div class="dash-prog-track" style="height:6px">
            <div class="dash-prog-fill" style="width:{{ $completionRate }}%;background:var(--p-info)"></div>
          </div>
        </div>
        <span style="font-size:11px;color:var(--p-hint)">{{ $cancellationRate }}% bekor</span>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card yellow">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Foydalanuvchilar</div>
          <div class="kpi-value">{{ number_format($totalUsers) }}</div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">ta ro'yxatdan o'tgan</div>
        </div>
        <div class="kpi-icon" style="background:var(--p-warning-d);color:var(--p-warning)"><i class="bi bi-people"></i></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-change up">
          <span class="live-dot" style="width:6px;height:6px;vertical-align:middle;margin-right:3px"></span>
          {{ $onlineUsers }} online
        </span>
        <span style="color:var(--p-hint);font-size:11px">+{{ $newUsersToday }} bugun</span>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card pink">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Gift Sertifikatlar</div>
          <div class="kpi-value">{{ number_format($giftTotal) }}</div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">ta jami</div>
        </div>
        <div class="kpi-icon" style="background:rgba(236,72,153,.1);color:#ec4899"><i class="bi bi-gift"></i></div>
      </div>
      <div class="kpi-footer">
        <span class="kpi-change {{ $giftUsed>0?'up':'neutral' }}">
          <i class="bi bi-check-circle"></i> {{ $giftUsed }} ishlatildi
        </span>
        @if($giftPending>0)
          <span style="color:var(--p-warning);font-size:11px">{{ $giftPending }} kutmoqda</span>
        @else
          <span style="color:var(--p-hint);font-size:11px">{{ $giftSent }} yuborilgan</span>
        @endif
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-4 fade-up">
    <div class="kpi-card teal">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="kpi-label">Mystery Box</div>
          <div class="kpi-value">{{ number_format($mysteryActive) }}</div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:3px">faol obuna</div>
        </div>
        <div class="kpi-icon" style="background:rgba(20,184,166,.1);color:#14b8a6"><i class="bi bi-box-seam"></i></div>
      </div>
      <div class="kpi-footer">
        @if($mysteryDueCount>0)
          <span class="kpi-change down"><i class="bi bi-exclamation-triangle"></i> {{ $mysteryDueCount }} navbatda</span>
        @else
          <span class="kpi-change neutral">Navbat yo'q</span>
        @endif
        <span style="color:var(--p-hint);font-size:11px">{{ $mysteryPending }} kutmoqda</span>
      </div>
    </div>
  </div>
</div>

{{-- ROW 2: Chart + Donut --}}
<div class="row g-3 mb-4">
  <div class="col-xl-8 fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div><div class="dash-card-title">Oylik daromad</div><div class="dash-card-sub">Oxirgi 6 oy · UZS</div></div>
        <a href="{{ route('panel.orders.index') }}" class="btn-p ghost sm">Buyurtmalar <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="dash-card-body"><div id="chartRevenue" style="min-height:260px"></div></div>
    </div>
  </div>
  <div class="col-xl-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div><div class="dash-card-title">Holat taqsimoti</div><div class="dash-card-sub">Jami {{ number_format($totalOrders) }} ta</div></div>
      </div>
      <div class="dash-card-body">
        <div id="chartDonut" style="min-height:200px"></div>
        <div class="stat-grid" style="grid-template-columns:repeat(2,1fr);gap:0">
          @foreach([['Yetkazildi',$completedOrders,'success'],["Yo'lda",$onwayOrders,'info'],['Kutilmoqda',$pendingOrders,'warning'],['Bekor',$cancelledOrders,'danger']] as [$l,$v,$c])
          <div class="stat-cell" style="padding:10px 6px">
            <div class="stat-cell-val" style="color:var(--p-{{ $c }})">{{ number_format($v) }}</div>
            <div class="stat-cell-lbl">{{ $l }}</div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ROW 3: MOLIYAVIY HISOBOT (superadmin only) --}}
@if($isSuperAdmin)
<div class="row g-3 mb-4">

  {{-- Daromad-chiqim tahlili --}}
  <div class="col-xl-5 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title"><i class="bi bi-calculator me-1" style="color:var(--p-accent)"></i>Moliyaviy hisobot</div>
        <div class="dash-card-sub">Jami · Bu oy</div>
      </div>
      <div class="dash-card-body">

        @php
          $finRows = [
            ['accent',  'bi-activity',         'GMV (brutto aylanma)',  'Barcha buyurtmalar summasi',                      $gmvTotal,            $gmvMonth,            false],
            ['success', 'bi-check-circle',      "To'langan daromad",    'paymentStatus = 2',                               $totalRevenue,        $monthRevenue,        false],
            ['info',    'bi-truck',             'Yetkazish daromadi',   'Delivery fee',                                    $totalDeliveryIncome, $monthDeliveryIncome, false],
            ['purple',  'bi-percent',           'Seller komissiya',     "O'rtacha {$avgCommissionPct}%",                  $totalCommissionEarned,$monthCommissionEarned,false],
            ['teal',    'bi-box-seam',          'Mystery Box daromad',  'Faol + yakunlangan',                              $mysteryRevTotal,     $mysteryRevMonth,     false],
            ['pink',    'bi-gift',              'Gift Sertifikat',      'Ishlatilgan · Buyurtmada: '.number_format($giftUsedInOrders/1000).'K',$giftRevenue,0,false],
        ];
          $finCosts = [
            ['danger',  'bi-ticket-perforated', 'Promokod chegirma',    "{$promoOrdersCount} ta buyurtmada",               $totalPromoDiscount,  $monthPromoDiscount,  true],
            ['warning', 'bi-cash-stack',        'Cashback to\'lovi',    "Foydalanuvchilarga qaytarildi",                  $totalCashbackPaid,   $monthCashbackPaid,   true],
            ['muted',   'bi-shop-window',       'Seller to\'lovlari',   "Approved · Kutilmoqda: ".number_format($pendingSellerPayout/1000).'K',$totalSellerPayout,$monthSellerPayout,true],
            ['muted',   'bi-bicycle',           'Kuryer to\'lovlari',   "Delivered · Kutilmoqda: ".number_format($pendingCourierPayout/1000).'K',$totalCourierPayout,$monthCourierPayout,true],
            ['danger',  'bi-x-circle',          'Bekor buyurtma',       'status=F buyurtmalar',                            $cancelledRevLoss,    $cancelledMonthLoss,  true],
        ];
        @endphp

        @foreach($finRows as [$clr,$ico,$lbl,$sub,$total,$month,$minus])
        <div class="fin-row">
          @php
            $finBg  = match($clr){ 'purple'=>'rgba(124,92,252,.12)', 'teal'=>'rgba(20,184,166,.1)', 'pink'=>'rgba(236,72,153,.1)', default=>"var(--p-{$clr}-d)" };
            $finClr = match($clr){ 'purple'=>'#7c5cfc', 'teal'=>'#14b8a6', 'pink'=>'#ec4899', default=>"var(--p-{$clr})" };
          @endphp
          <div class="fin-icon" style="background:{{ $finBg }};color:{{ $finClr }}">
            <i class="bi {{ $ico }}"></i>
          </div>
          <div class="flex-grow-1">
            <div style="font-size:13px;color:var(--p-text)">{{ $lbl }}</div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:1px">{{ $sub }}</div>
          </div>
          <div>
            <div class="fin-val" style="color:{{ $finClr }}">
              {{ number_format($total/1_000_000,1) }}M
            </div>
            @if($month > 0)<div class="fin-month">Bu oy: {{ number_format($month/1000) }}K</div>@endif
          </div>
        </div>
        @endforeach

        <div style="height:1px;background:var(--p-border);margin:12px 0"></div>

        @foreach($finCosts as [$clr,$ico,$lbl,$sub,$total,$month,$minus])
        <div class="fin-row">
          @php $costBg = $clr==='muted' ? 'var(--p-elevated)' : "var(--p-{$clr}-d)"; @endphp
          <div class="fin-icon" style="background:{{ $costBg }};color:var(--p-{{ $clr }})">
            <i class="bi {{ $ico }}"></i>
          </div>
          <div class="flex-grow-1">
            <div style="font-size:13px;color:var(--p-{{ $clr }})">{{ $lbl }}</div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:1px">{{ $sub }}</div>
          </div>
          <div>
            <div class="fin-val" style="color:var(--p-{{ $clr }})">−{{ number_format($total/1_000_000,1) }}M</div>
            @if($month > 0)<div class="fin-month">Bu oy: −{{ number_format($month/1000) }}K</div>@endif
          </div>
        </div>
        @endforeach

        <div style="height:1px;background:var(--p-border);margin:12px 0"></div>

        {{-- Platform sof foyda --}}
        <div class="fin-row" style="background:var(--p-success-d);border-radius:10px;padding:12px;margin:-4px">
          <div class="fin-icon" style="background:var(--p-success-d);color:var(--p-success)">
            <i class="bi bi-stars"></i>
          </div>
          <div class="flex-grow-1">
            <div style="font-size:13px;font-weight:700;color:var(--p-text)">Platform sof foyda</div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:1px">Komissiya + Yetkazish − Chiqimlar</div>
          </div>
          <div>
            <div class="fin-val" style="color:var(--p-success);font-size:17px">
              {{ number_format($platformProfit/1_000_000,2) }}M
            </div>
            <div class="fin-month">Bu oy: {{ number_format($platformProfitMonth/1000) }}K</div>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- O'ng: AOV + Mahsulot turi + Xaridorlar --}}
  <div class="col-xl-7 fade-up">

    <div class="dash-card mb-3">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">AOV trend (o'rtacha buyurtma)</div>
          <div class="dash-card-sub">Joriy: {{ number_format($avgOrderValue) }} UZS · O'rtacha komissiya: {{ $avgCommissionPct }}%</div>
        </div>
      </div>
      <div class="dash-card-body"><div id="chartAov" style="min-height:130px"></div></div>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title">Mahsulot turi</div>
            <div class="dash-card-sub">Daromad bo'yicha</div>
          </div>
          <div class="dash-card-body">
            @php
              $typeTotal = max(1,$revenueByType['book']+$revenueByType['stationery']);
              $bookPct = round($revenueByType['book']/$typeTotal*100,1);
              $statPct = round($revenueByType['stationery']/$typeTotal*100,1);
            @endphp
            <div id="chartTypePie" style="min-height:120px"></div>
            @foreach([['Kitoblar',$revenueByType['book'],'accent','bi-book',$bookPct],['Kanstovar',$revenueByType['stationery'],'warning','bi-pencil-square',$statPct]] as [$l,$v,$c,$i,$p])
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi {{ $i }}" style="font-size:13px;color:var(--p-{{ $c }});width:16px"></i>
              <span style="flex:1;font-size:12px;color:var(--p-muted)">{{ $l }}</span>
              <span style="font-size:12px;font-weight:600;font-family:'JetBrains Mono',monospace;color:var(--p-text)">{{ number_format($v/1000) }}K</span>
              <span class="s-pill {{ $c }}" style="font-size:10px;min-width:36px;text-align:center">{{ $p }}%</span>
            </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title">Xaridorlar (bu oy)</div>
            <div class="dash-card-sub">Yangi vs Takroriy</div>
          </div>
          <div class="dash-card-body">
            @php $totalB=max(1,$repeatBuyersMonth+$newBuyersMonth); $repeatPct=$totalB>1?round($repeatBuyersMonth/$totalB*100):0; @endphp
            <div id="chartBuyers" style="min-height:120px"></div>
            @foreach([['Yangi xaridor',$newBuyersMonth,'success','1 marta'],['Takroriy',$repeatBuyersMonth,'accent','2+ marta']] as [$l,$v,$c,$s])
            <div class="d-flex align-items-center gap-2 mb-2">
              <div style="width:10px;height:10px;border-radius:50%;background:var(--p-{{ $c }});flex-shrink:0"></div>
              <div style="flex:1"><div style="font-size:12px;color:var(--p-muted)">{{ $l }}</div><div style="font-size:10px;color:var(--p-hint)">{{ $s }}</div></div>
              <span style="font-size:13px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-text)">{{ number_format($v) }}</span>
            </div>
            @endforeach
            <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--p-border)">
              <div class="d-flex justify-content-between" style="font-size:11px;color:var(--p-hint);margin-bottom:4px">
                <span>Qayta qaytish</span>
                <span style="color:var(--p-accent);font-weight:600">{{ $repeatPct }}%</span>
              </div>
              <div class="dash-prog-track">
                <div class="dash-prog-fill" style="width:{{ $repeatPct }}%;background:var(--p-accent)"></div>
              </div>
            </div>
            @if($deliveryTypeSplit->count())
            <div style="margin-top:10px;padding-top:8px;border-top:1px solid var(--p-border)">
              <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Yetkazish turi</div>
              @foreach($deliveryTypeSplit->take(3) as $dt)
              <div class="d-flex justify-content-between mb-1" style="font-size:12px">
                <span style="color:var(--p-muted)">{{ $dt->deliveryType }}</span>
                <span style="font-family:'JetBrains Mono',monospace;color:var(--p-text);font-weight:600">{{ number_format($dt->cnt) }} ta</span>
              </div>
              @endforeach
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

{{-- ROW 4: User holat + Online + Top mahsulotlar --}}
<div class="row g-3 mb-4">
  <div class="col-xl-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title">Foydalanuvchilar holati</div>
        <div class="dash-card-sub">{{ number_format($totalUsers) }} ta jami</div>
      </div>
      <div class="dash-card-body">
        @foreach([['Online (5 daqiqa)',$onlineUsers,'success'],['Aktiv (FCM)',$activeUsers,'accent'],['Premium',$premiumUsers,'warning'],['Tasdiqlangan',$verifiedUsers,'info'],['Izolat (30+ kun)',$isolatedUsers,'danger'],['Bu hafta yangi',$newUsersWeek,'muted']] as [$l,$v,$c])
        <div class="dash-prog">
          <div class="dash-prog-top">
            <span class="dash-prog-label">{{ $l }}</span>
            <span class="dash-prog-val">{{ number_format($v) }}</span>
          </div>
          <div class="dash-prog-track">
            <div class="dash-prog-fill" style="width:{{ $totalUsers>0?min(round($v/$totalUsers*100),100):0 }}%;background:var(--p-{{ $c }})"></div>
          </div>
        </div>
        @endforeach
        <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);gap:0">
          @foreach([['Jami',$totalUsers,'text'],['Premium',$premiumUsers,'warning'],['+Bugun',$newUsersToday,'success']] as [$l,$v,$c])
          <div class="stat-cell" style="padding:10px 0">
            <div class="stat-cell-val" style="color:var(--p-{{ $c }})">{{ number_format($v) }}</div>
            <div class="stat-cell-lbl">{{ $l }}</div>
          </div>
          @endforeach
        </div>
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--p-border)">
          <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px">7 kunlik yangi userlar</div>
          <div id="chartUserSparkline" style="min-height:60px"></div>
        </div>
        @if($isolatedUsers>0)
        <div class="alert-item danger" style="margin-top:14px">
          <i class="bi bi-person-x" style="flex-shrink:0"></i>
          <span>{{ number_format($isolatedUsers) }} ta user 30+ kun yo'q</span>
        </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-xl-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title">Hozir online</div>
        <div class="dash-card-sub" style="display:flex;align-items:center;gap:6px">
          <span class="live-dot"></span> {{ $onlineUsers }} nafar
        </div>
      </div>
      <div class="dash-card-body">
        @forelse($onlineUsersList as $u)
        <a href="{{ route('panel.users.show',$u->id) }}" style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--p-border);text-decoration:none">
          <div class="d-av" style="background:linear-gradient(135deg,var(--p-accent),#7c5cfc)">
            @if($u->avatar)<img src="{{ $u->avatar }}">@else{{ strtoupper(substr($u->name??'U',0,1)) }}@endif
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:500;color:var(--p-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $u->name }} {{ $u->lastname }}</div>
            <div style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">{{ $u->last_seen_at ? \Carbon\Carbon::parse($u->last_seen_at)->diffForHumans() : '—' }}</div>
          </div>
          <span class="live-dot"></span>
        </a>
        @empty
        <div style="text-align:center;padding:30px;color:var(--p-hint)">
          <i class="bi bi-wifi-off" style="font-size:28px;display:block;margin-bottom:8px"></i>Hozir hech kim online emas
        </div>
        @endforelse
        <a href="{{ route('panel.users.index') }}" class="btn-p ghost" style="width:100%;justify-content:center;margin-top:14px">
          Barcha foydalanuvchilar <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>
  </div>

  <div class="col-xl-4 fade-up">
    <div class="dash-card h-100">
      <div class="dash-card-head">
        <div class="dash-card-title">Top mahsulotlar</div>
        <div class="dash-card-sub">Eng ko'p sotilganlar</div>
      </div>
      <div class="dash-card-body">
        @forelse($topMixedProducts as $i => $product)
        @php $imgs=is_array($product->images)?$product->images:json_decode($product->images??'[]',true);$img=$imgs[0]??null; @endphp
        <div class="top-row">
          <span class="rank-num {{ $i===0?'rn-1':($i===1?'rn-2':($i===2?'rn-3':'rn-n')) }}">{{ $i+1 }}</span>
          <div class="book-thumb">
            @if($img)
              <img src="{{ $img }}">
            @else
              <i class="bi bi-{{ $product->_type==='stationery'?'box':'book' }}" style="color:var(--p-hint);font-size:13px"></i>
            @endif
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12.5px;font-weight:500;color:var(--p-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $product->name }}</div>
            <div style="display:flex;align-items:center;gap:4px;margin-top:2px">
              <span class="s-pill {{ $product->_type==='stationery'?'warning':'info' }}" style="font-size:9px;padding:1px 5px">{{ $product->_type==='stationery'?'Kanstovar':'Kitob' }}</span>
              <span style="font-size:11px;color:var(--p-hint)">{{ number_format($product->total_revenue/1000) }}K UZS</span>
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:13px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-text)">{{ number_format($product->sold_count) }}</div>
            <div style="font-size:10px;color:var(--p-hint)">ta</div>
          </div>
        </div>
        @empty
        <div style="text-align:center;padding:30px;color:var(--p-hint)"><i class="bi bi-box" style="font-size:28px;display:block;margin-bottom:8px"></i>Ma'lumot yo'q</div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- ROW 5: Mystery navbat + Top buyers + So'nggi buyurtmalar --}}
<div class="row g-3 mb-4">

  @if($mysteryDueToday->count()||$mysteryDueSoon->count())
  <div class="col-xl-4 fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div>
          <div class="dash-card-title">
            <i class="bi bi-box-seam me-1" style="color:{{ $mysteryDueCount>0?'var(--p-danger)':'var(--p-accent)' }}"></i>Mystery Box navbati
          </div>
          <div class="dash-card-sub">
            @if($mysteryDueCount>0)
              <span style="color:var(--p-danger)">{{ $mysteryDueCount }} ta kechikdi!</span>
            @else
              7 kun ichida {{ $mysteryDueSoon->count() }} ta
            @endif
          </div>
        </div>
        <a href="{{ route('panel.mystery-box.subscriptions',['tab'=>'active']) }}" class="btn-p ghost sm">Barchasi</a>
      </div>
      <div class="dash-card-body">
        @if($mysteryDueToday->count())
        <div style="font-size:11px;color:var(--p-danger);font-weight:600;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px"><i class="bi bi-exclamation-triangle me-1"></i>Bugun / Kechikkan</div>
        @foreach($mysteryDueToday as $sub)
        <a href="{{ route('panel.mystery-box.subscription',$sub) }}" style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--p-border);text-decoration:none">
          <div class="d-av" style="background:linear-gradient(135deg,#14b8a6,#0d9488)">{{ strtoupper(substr($sub->user?->name??'M',0,1)) }}</div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12.5px;font-weight:500;color:var(--p-text)">{{ $sub->user?->name }} {{ $sub->user?->lastname }}</div>
            <div style="font-size:11px;color:var(--p-hint)">{{ $sub->plan?->name_uz }} · {{ $sub->next_delivery_at?->diffForHumans() }}</div>
          </div>
          <span class="s-pill danger" style="font-size:10px">Navbatda</span>
        </a>
        @endforeach
        @endif
        @if($mysteryDueSoon->count())
        <div style="font-size:11px;color:var(--p-hint);font-weight:600;text-transform:uppercase;letter-spacing:.07em;margin:{{ $mysteryDueToday->count()?'12px':'0' }} 0 8px">Yaqin 7 kun</div>
        @foreach($mysteryDueSoon as $sub)
        <a href="{{ route('panel.mystery-box.subscription',$sub) }}" style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--p-border);text-decoration:none">
          <div class="d-av" style="background:linear-gradient(135deg,#14b8a6,#0d9488)">{{ strtoupper(substr($sub->user?->name??'M',0,1)) }}</div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:500;color:var(--p-text)">{{ $sub->user?->name }} {{ $sub->user?->lastname }}</div>
            <div style="font-size:10px;color:var(--p-hint)">{{ $sub->next_delivery_at?->format('d.m.Y') }}</div>
          </div>
        </a>
        @endforeach
        @endif
      </div>
    </div>
  </div>
  @endif

  <div class="col-xl-{{ ($mysteryDueToday->count()||$mysteryDueSoon->count())?'4':'5' }} fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Top mijozlar</div>
        <div class="dash-card-sub">Eng ko'p xarid qilganlar</div>
      </div>
      <div class="dash-card-body">
        <table class="p-table">
          <thead><tr><th>#</th><th>Mijoz</th><th>Buyurtma</th><th style="text-align:right">Xarid</th></tr></thead>
          <tbody>
            @forelse($topBuyers as $i => $buyer)
            <tr>
              <td><span class="rank-num {{ $i===0?'rn-1':($i===1?'rn-2':($i===2?'rn-3':'rn-n')) }}">{{ $i+1 }}</span></td>
              <td>
                <a href="{{ route('panel.users.show',$buyer->user_id) }}" style="display:flex;align-items:center;gap:8px;text-decoration:none">
                  <div class="d-av" style="background:linear-gradient(135deg,var(--p-accent),#7c5cfc)">
                    @if($buyer->user?->avatar)<img src="{{ $buyer->user->avatar }}">@else{{ strtoupper(substr($buyer->user?->name??'U',0,1)) }}@endif
                  </div>
                  <span style="font-size:12.5px;font-weight:500;color:var(--p-text)">{{ $buyer->user?$buyer->user->name.' '.$buyer->user->lastname:'ID:'.$buyer->user_id }}</span>
                </a>
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">{{ $buyer->order_count }} ta</td>
              <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;color:var(--p-success)">{{ number_format($buyer->total_spent/1000) }}K</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--p-hint)">Ma'lumot yo'q</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-xl-{{ ($mysteryDueToday->count()||$mysteryDueSoon->count())?'4':'7' }} fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div><div class="dash-card-title">So'nggi buyurtmalar</div><div class="dash-card-sub">Oxirgi 10 ta</div></div>
        <a href="{{ route('panel.orders.index') }}" class="btn-p ghost sm">Barchasi <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive">
          <table class="p-table" style="min-width:400px">
            <thead><tr><th>#ID</th><th>Mijoz</th><th>Summa</th><th>Status</th><th>Vaqt</th><th></th></tr></thead>
            <tbody>
              @forelse($recentOrders as $order)
              @php $bc=match($order['status']){'Yetkazildi'=>'ob-c',"Yo'lda"=>'ob-b','Kutilmoqda'=>'ob-a','Bekor qilindi'=>'ob-f',default=>'ob-p'}; @endphp
              <tr>
                <td>
                  <span style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">#{{ $order['id'] }}</span>
                  @if($order['gift'])<span>🎁</span>@endif
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="d-av" style="background:linear-gradient(135deg,var(--p-accent),#7c5cfc);font-size:11px">
                      @if($order['avatar'])<img src="{{ $order['avatar'] }}">@else{{ strtoupper(substr($order['customer'],0,1)) }}@endif
                    </div>
                    <span style="font-size:12.5px;font-weight:500;color:var(--p-text)">{{ $order['customer'] }}</span>
                  </div>
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text);font-size:13px">{{ $order['amount'] }} <span style="font-size:10px;color:var(--p-hint)">UZS</span></td>
                <td><span class="o-badge {{ $bc }}">{{ $order['status'] }}</span></td>
                <td style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace;white-space:nowrap">{{ $order['date'] }}</td>
                <td><a href="{{ route('panel.orders.show',$order['id']) }}" class="btn-p ghost sm"><i class="bi bi-eye"></i></a></td>
              </tr>
              @empty
              <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--p-hint)">Buyurtmalar yo'q</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ROW 6: Biznes holat --}}
<div class="row g-3 mb-2">
  @foreach([['Sotuvchilar',$approvedSellers,$totalSellers,'sellers.index','success','bi-shop-window',$pendingSellers,'Yangi ariza'],['Kuryerlar',$activeCouriers,$totalCouriers,'couriers.index','info','bi-bicycle',0,'']] as [$title,$active,$total,$route,$color,$icon,$pending,$pendingLbl])
  <div class="col-md-6 fade-up">
    <div class="dash-card">
      <div class="dash-card-head">
        <div class="dash-card-title"><i class="bi {{ $icon }} me-1" style="color:var(--p-{{ $color }})"></i>{{ $title }}</div>
        <a href="{{ route('panel.'.$route) }}" class="btn-p ghost sm">Ko'rish</a>
      </div>
      <div class="dash-card-body">
        <div style="display:flex;align-items:center;gap:20px">
          <div style="text-align:center">
            <div style="font-size:36px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-{{ $color }})">{{ number_format($active) }}</div>
            <div style="font-size:11px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">Faol</div>
          </div>
          <div style="flex:1">
            <div class="dash-prog-top">
              <span class="dash-prog-label">Faollik</span>
              <span class="dash-prog-val">{{ $total>0?round($active/$total*100):0 }}%</span>
            </div>
            <div class="dash-prog-track">
              <div class="dash-prog-fill" style="width:{{ $total>0?round($active/$total*100):0 }}%;background:var(--p-{{ $color }})"></div>
            </div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:6px">Jami: {{ number_format($total) }} ta</div>
            @if($pending>0)
            <div class="alert-item warning" style="margin-top:8px;padding:6px 10px">
              <i class="bi bi-clock" style="flex-shrink:0;font-size:13px"></i><span>{{ $pending }} ta {{ $pendingLbl }}</span>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>

@endsection

@push('scripts')
<script>
const isDark = document.documentElement.getAttribute('data-bs-theme') !== 'light';
const C = {
  text:isDark?'#eef0f7':'#1a1d2e', muted:isDark?'#555c75':'#9ca3af',
  grid:isDark?'rgba(255,255,255,0.05)':'rgba(0,0,0,0.06)',
  surface:isDark?'#181c27':'#ffffff',
  accent:'#4f7cff', success:isDark?'#22c98e':'#16a34a',
  warning:isDark?'#f5a623':'#d97706', danger:isDark?'#ff5c6a':'#dc2626',
  info:isDark?'#38bdf8':'#0284c7', teal:'#14b8a6', pink:'#ec4899', purple:'#7c5cfc',
};

@php $revLabels=collect($monthlyRevenue)->pluck('month')->toJson(); $revAmounts=collect($monthlyRevenue)->map(fn($m)=>round($m['total']/1_000_000,1))->toJson(); @endphp

new ApexCharts(document.getElementById('chartRevenue'),{
  series:[{name:'Daromad (mln)',data:{!! $revAmounts !!}}],
  chart:{type:'bar',height:260,toolbar:{show:false},background:'transparent',fontFamily:'Inter, sans-serif',animations:{enabled:true,speed:600}},
  colors:[C.accent],plotOptions:{bar:{borderRadius:7,columnWidth:'46%',dataLabels:{position:'top'}}},
  dataLabels:{enabled:true,formatter:v=>v+'M',offsetY:-22,style:{fontSize:'11px',colors:[C.muted],fontFamily:'JetBrains Mono, monospace'}},
  xaxis:{categories:{!! $revLabels !!},axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'12px'}}},
  yaxis:{labels:{style:{colors:C.muted,fontSize:'11px'},formatter:v=>v+'M'}},
  grid:{borderColor:C.grid,strokeDashArray:5,xaxis:{lines:{show:false}}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' mln UZS'}},
  fill:{type:'gradient',gradient:{shade:'dark',type:'vertical',gradientToColors:['#2650cc'],stops:[0,100]}},
}).render();

new ApexCharts(document.getElementById('chartDonut'),{
  series:[{{ $completedOrders }},{{ $onwayOrders }},{{ $pendingOrders }},{{ $cancelledOrders }}],
  labels:['Yetkazildi',"Yo'lda",'Kutilmoqda','Bekor'],colors:[C.success,C.info,C.warning,C.danger],
  chart:{type:'donut',height:200,toolbar:{show:false},background:'transparent',fontFamily:'Inter, sans-serif'},
  legend:{position:'bottom',fontSize:'12px',labels:{colors:C.muted},markers:{width:8,height:8,radius:4},itemMargin:{horizontal:8}},
  dataLabels:{enabled:false},
  plotOptions:{pie:{donut:{size:'74%',labels:{show:true,total:{show:true,label:'Jami',fontSize:'12px',color:C.muted,formatter:()=>'{{ number_format($totalOrders) }}'},value:{fontSize:'20px',fontWeight:700,color:C.text,fontFamily:'JetBrains Mono, monospace'}}}}},
  stroke:{width:2,colors:[C.surface]},tooltip:{theme:isDark?'dark':'light'},
}).render();

@php $sparkDays=collect($dailyNewUsers)->pluck('day')->toJson();$sparkCounts=collect($dailyNewUsers)->pluck('count')->toJson(); @endphp
new ApexCharts(document.getElementById('chartUserSparkline'),{
  series:[{name:'Yangi user',data:{!! $sparkCounts !!}}],
  chart:{type:'bar',height:60,sparkline:{enabled:true},background:'transparent'},
  colors:[C.accent],plotOptions:{bar:{borderRadius:3,columnWidth:'60%'}},
  xaxis:{categories:{!! $sparkDays !!}},tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
}).render();

@if($isSuperAdmin)
@php $aovLabels=collect($aovMonthly)->pluck('month')->toJson();$aovVals=collect($aovMonthly)->pluck('aov')->toJson(); @endphp
new ApexCharts(document.getElementById('chartAov'),{
  series:[{name:'AOV',data:{!! $aovVals !!}}],
  chart:{type:'line',height:130,toolbar:{show:false},background:'transparent',fontFamily:'Inter, sans-serif'},
  colors:[C.teal],stroke:{curve:'smooth',width:2.5},
  markers:{size:4,colors:[C.teal],strokeColors:C.surface,strokeWidth:2},
  xaxis:{categories:{!! $aovLabels !!},axisBorder:{show:false},axisTicks:{show:false},labels:{style:{colors:C.muted,fontSize:'11px'}}},
  yaxis:{labels:{style:{colors:C.muted,fontSize:'10px'},formatter:v=>Math.round(v/1000)+'K'}},
  grid:{borderColor:C.grid,strokeDashArray:4},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Number(v).toLocaleString()+' UZS'}},
}).render();

new ApexCharts(document.getElementById('chartTypePie'),{
  series:[{{ $revenueByType['book'] }},{{ $revenueByType['stationery'] }}],
  labels:['Kitoblar','Kanstovar'],colors:[C.accent,C.warning],
  chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
  dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
  plotOptions:{pie:{donut:{size:'65%'}}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>Math.round(v/1000)+'K UZS'}},
}).render();

new ApexCharts(document.getElementById('chartBuyers'),{
  series:[{{ $newBuyersMonth }},{{ $repeatBuyersMonth }}],
  labels:['Yangi','Takroriy'],colors:[C.success,C.accent],
  chart:{type:'donut',height:120,toolbar:{show:false},background:'transparent'},
  dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:[C.surface]},
  plotOptions:{pie:{donut:{size:'65%'}}},
  tooltip:{theme:isDark?'dark':'light',y:{formatter:v=>v+' ta'}},
}).render();
@endif
</script>
@endpush