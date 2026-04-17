@extends('panel.layouts.panel')
@section('title', $user->name.' '.$user->lastname.' — Profil')
@section('page-title', $user->name.' '.$user->lastname)

@section('content')

@php $activeSection = request('section', 'orders'); @endphp

{{-- ── Header ──────────────────────────────────────────────── --}}
<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div class="d-flex align-items-center gap-3">
    <a href="{{ route('panel.users.index') }}" class="btn-p ghost icon">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">{{ $user->name }} {{ $user->lastname }}</h1>
      <p class="page-sub">
        ID: #{{ $user->id }}
        · {{ $user->created_at?->format('d.m.Y') }} da qo'shilgan
        @if($user->last_seen_at)
          · {{ \Carbon\Carbon::parse($user->last_seen_at)->diffForHumans() }}
        @endif
      </p>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <form method="POST" action="{{ route('panel.users.toggle-premium', $user) }}">
      @csrf @method('PATCH')
      <button class="btn-p {{ $user->is_premium ? 'danger' : 'warning' }} ghost">
        <i class="bi bi-star{{ $user->is_premium ? '-fill' : '' }}"></i>
        {{ $user->is_premium ? 'Premium olish' : 'Premium berish' }}
      </button>
    </form>
    <a href="{{ route('panel.users.edit', $user) }}" class="btn-p ghost">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
  </div>
</div>

{{-- ════ PROFIL KARTA (gorizontal, to'liq kenglik) ════════════ --}}
<div class="p-card mb-3 fade-up">

  {{-- Avatar + ism + kontakt --}}
  <div style="padding:20px 24px 16px;display:flex;align-items:center;
              gap:18px;flex-wrap:wrap;border-bottom:1px solid var(--p-border)">

    {{-- Avatar --}}
    <div style="width:66px;height:66px;border-radius:50%;overflow:hidden;flex-shrink:0;
                background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                display:flex;align-items:center;justify-content:center;
                font-size:24px;font-weight:700;color:#fff">
      @if($user->avatar)
        <img src="{{ $user->avatar }}" style="width:100%;height:100%;object-fit:cover">
      @else
        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
      @endif
    </div>

    {{-- Ism + rol + bio --}}
    <div style="flex:1;min-width:180px">
      <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-bottom:3px">
        <span style="font-size:16px;font-weight:700;color:var(--p-text)">
          {{ $user->name }} {{ $user->lastname }}
        </span>
        @if($user->is_premium)
          <span class="s-pill warning" style="font-size:10px">⭐ Premium</span>
        @endif
        @if($user->isVerified)
          <span class="s-pill success" style="font-size:10px">Ishonchli</span>
        @endif
        @if($user->isSupport ?? false)
          <span class="s-pill info" style="font-size:10px">Support</span>
        @endif
      </div>
      @if($user->role_emoji || $user->role_title)
        <div style="font-size:12px;color:var(--p-hint);margin-bottom:3px">
          {{ $user->role_emoji }} {{ $user->role_title }}
          @if($user->role_place) · {{ $user->role_place }} @endif
        </div>
      @endif
      @if($user->bio)
        <div style="font-size:12px;color:var(--p-muted);font-style:italic;line-height:1.5">
          "{{ Str::limit($user->bio, 100) }}"
        </div>
      @endif
    </div>

    {{-- Kontakt + premium info --}}
    <div style="display:flex;flex-direction:column;gap:6px;min-width:200px">
      <div style="display:flex;align-items:center;gap:7px">
        <i class="bi bi-telephone" style="color:var(--p-muted);font-size:12px;width:14px"></i>
        <span style="font-size:13px;color:var(--p-text);font-family:'JetBrains Mono',monospace">
          {{ $user->phone_number ?: '—' }}
        </span>
      </div>
      @if($user->email)
      <div style="display:flex;align-items:center;gap:7px">
        <i class="bi bi-envelope" style="color:var(--p-muted);font-size:12px;width:14px"></i>
        <span style="font-size:12px;color:var(--p-muted)">{{ $user->email }}</span>
      </div>
      @endif
      @if($user->is_premium && $user->premium_until)
      <div style="display:flex;align-items:center;gap:7px">
        <i class="bi bi-star-fill" style="color:var(--p-warning);font-size:11px;width:14px"></i>
        <span style="font-size:12px;color:var(--p-warning)">
          Premium: {{ \Carbon\Carbon::parse($user->premium_until)->format('d.m.Y') }} gacha
        </span>
      </div>
      @endif
    </div>
  </div>

  {{-- Stats strip --}}
  @php
    $stripStats = [
      ['bi-bag-check',  'Buyurtmalar', $orderCount,                              'accent'],
      ['bi-cash-stack', 'Xarid',       number_format($totalSpent).' UZS',        'success'],
      ['bi-wallet2',    'Balans',      number_format($user->real_balance??0),     'warning'],
      ['bi-star-fill',  'Cashback',    number_format($user->cashback??0),         'info'],
      ['bi-gift',       'Sertifikat',  $giftCertCount,                            'accent'],
      ['bi-box-seam',   'Mystery Box', $mysterySubCount,                          'muted'],
      ['bi-cart3',      'Savat',       $cartItems->count(),                       'muted'],
      ['bi-people',     'Followers',   $followersCount,                           'muted'],
      ['bi-robot',      'AI limit',    $user->ai_limit??0,                        'muted'],
    ];
  @endphp
  <div style="display:flex;flex-wrap:wrap">
    @foreach($stripStats as $i => [$icon,$lbl,$val,$clr])
    <div style="flex:1;min-width:80px;padding:10px 12px;text-align:center;
                {{ $i>0?'border-left:1px solid var(--p-border)':'' }}">
      <div style="font-size:14px;font-weight:700;font-family:'JetBrains Mono',monospace;
                  color:var(--p-{{ $clr }})">{{ $val }}</div>
      <div style="font-size:9px;color:var(--p-hint);margin-top:2px;text-transform:uppercase;
                  letter-spacing:.06em;display:flex;align-items:center;
                  justify-content:center;gap:3px">
        <i class="bi {{ $icon }}" style="font-size:9px"></i>{{ $lbl }}
      </div>
    </div>
    @endforeach
  </div>
</div>

{{-- ════ ASOSIY: chap info + o'ng tabs ════════════════════════ --}}
<div class="row g-3">

  {{-- ── CHAP: ixcham info kartalar ─────────────────────────── --}}
  <div class="col-xl-4">

    {{-- Moliyaviy --}}
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-wallet2 me-1"></i>Moliyaviy</div>
      </div>
      <div style="padding:0 18px 8px">
        @foreach([
          ['Balans',   number_format($user->real_balance??0).' UZS', 'warning'],
          ['Cashback', number_format($user->cashback??0).' UZS',     'success'],
          ['AI limit', ($user->ai_limit??0).' ta',                   'info'],
        ] as [$k,$v,$clr])
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $k }}</span>
          <span style="font-size:13px;font-weight:600;font-family:'JetBrains Mono',monospace;
                       color:var(--p-{{ $clr }})">{{ $v }}</span>
        </div>
        @endforeach
      </div>
    </div>

    {{-- Qurilmalar --}}
    @if($devices && count($devices))
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-phone me-1"></i>Qurilmalar</div>
        <span class="s-pill muted" style="font-size:10px">{{ count($devices) }} ta</span>
      </div>
      <div style="padding:0 18px 8px">
        @foreach($devices as $dev)
        <div style="display:flex;align-items:center;gap:9px;
                    padding:7px 0;border-bottom:1px solid var(--p-border);
                    {{ $loop->last?'border-bottom:none':'' }}">
          <div style="width:28px;height:28px;border-radius:6px;background:var(--p-elevated);
                      display:flex;align-items:center;justify-content:center;
                      font-size:12px;color:var(--p-muted);flex-shrink:0">
            <i class="bi bi-{{ ($dev->platform??'')==='ios'?'apple':'android2' }}"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:500;color:var(--p-text);
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              {{ $dev->device_name ?: Str::limit($dev->device_id??'Qurilma',16) }}
            </div>
            <div style="font-size:10px;color:var(--p-hint)">
              {{ strtoupper($dev->platform??'') }}
              @if($dev->created_at??null)
                · {{ \Carbon\Carbon::parse($dev->created_at)->format('d.m.Y') }}
              @endif
            </div>
          </div>
          <span class="s-pill {{ ($dev->fcm_token??null)?'success':'muted' }}"
                style="font-size:9px">FCM</span>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Manzillar --}}
    @if($addresses && count($addresses))
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-geo-alt me-1" style="color:var(--p-accent)"></i>Manzillar
        </div>
        <span class="s-pill muted" style="font-size:10px">{{ count($addresses) }} ta</span>
      </div>
      <div style="padding:0 18px 8px">
        @foreach($addresses as $addr)
        @php $isMain = (int)($addr->id??0) === (int)($user->mainAddressID??0); @endphp
        <div style="display:flex;gap:9px;padding:9px 0;
                    border-bottom:1px solid var(--p-border);
                    {{ $loop->last?'border-bottom:none':'' }}">
          <div style="width:26px;height:26px;border-radius:6px;flex-shrink:0;margin-top:1px;
                      background:{{ $isMain?'var(--p-accent-d)':'var(--p-elevated)' }};
                      color:{{ $isMain?'var(--p-accent)':'var(--p-hint)' }};
                      display:flex;align-items:center;justify-content:center;font-size:12px">
            <i class="bi bi-geo-alt{{ $isMain?'-fill':'' }}"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:500;color:var(--p-text);
                        line-height:1.4;margin-bottom:4px">
              {{ $addr->fullAddress ?? '—' }}
              @if($isMain)
                <span class="s-pill accent" style="font-size:9px;margin-left:3px">Asosiy</span>
              @endif
            </div>
            @if(($addr->lat??null) && ($addr->lon??null))
            <a href="https://maps.yandex.uz/?text={{ $addr->lat }}+{{ $addr->lon }}&z=16"
               target="_blank"
               style="font-size:10px;color:var(--p-info);text-decoration:none;
                      display:inline-flex;align-items:center;gap:3px">
              <i class="bi bi-map" style="font-size:10px"></i> Xarita
            </a>
            @endif
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Bank kartalar --}}
    @if(isset($cards) && $cards->count())
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-credit-card me-1" style="color:var(--p-accent)"></i>Bank kartalar
        </div>
        <span class="s-pill muted" style="font-size:10px">{{ $cards->count() }} ta</span>
      </div>
      <div style="padding:0 18px 8px">
        @foreach($cards as $card)
        @php
          $num    = $card->card_number ?? '';
          $masked = strlen($num) >= 16
            ? substr($num,0,4).' **** **** '.substr($num,-4) : ($num ?: '—');
        @endphp
        <div style="display:flex;align-items:center;gap:9px;padding:7px 0;
                    border-bottom:1px solid var(--p-border);
                    {{ $loop->last?'border-bottom:none':'' }}">
          <div style="width:38px;height:24px;border-radius:4px;flex-shrink:0;
                      background:linear-gradient(135deg,#1e2340,#2d3561);
                      display:flex;align-items:center;justify-content:center">
            <i class="bi bi-credit-card-2-front" style="font-size:11px;color:#fff;opacity:.85"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:600;font-family:'JetBrains Mono',monospace;
                        color:var(--p-text);letter-spacing:.04em">{{ $masked }}</div>
            <div style="font-size:10px;color:var(--p-hint)">
              {{ \Carbon\Carbon::parse($card->created_at)->format('d.m.Y') }}
            </div>
          </div>
          <form method="POST"
                action="{{ route('panel.users.card.destroy', [$user, $card]) }}"
                onsubmit="return confirm('{{ addslashes($masked) }} — o\'chirilsinmi?')">
            @csrf @method('DELETE')
            <button class="btn-p danger sm"
                    style="width:26px;height:26px;padding:0;flex-shrink:0">
              <i class="bi bi-trash" style="font-size:10px"></i>
            </button>
          </form>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Ijtimoiy (followers + following) --}}
    @if($followers->count() || $following->count())
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title"><i class="bi bi-people me-1"></i>Ijtimoiy</div>
      </div>
      <div style="padding:12px 18px">
        @if($followers->count())
        <div style="{{ $following->count()?'margin-bottom:14px':'' }}">
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                      letter-spacing:.07em;margin-bottom:7px">
            Obunachilari · {{ $followersCount }} ta
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:4px">
            @foreach($followers as $f)
            <a href="{{ route('panel.users.show', $f->id) }}"
               title="{{ $f->name }} {{ $f->lastname }}"
               style="width:30px;height:30px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;
                      font-size:11px;font-weight:700;color:#fff;text-decoration:none">
              @if($f->avatar)
                <img src="{{ $f->avatar }}" style="width:100%;height:100%;object-fit:cover">
              @else {{ strtoupper(substr($f->name,0,1)) }} @endif
            </a>
            @endforeach
            @if($followersCount > 8)
              <span style="font-size:10px;color:var(--p-hint);padding:8px 4px;
                           align-self:center">+{{ $followersCount-8 }}</span>
            @endif
          </div>
        </div>
        @endif

        @if($following->count())
        <div style="{{ $followers->count()?'border-top:1px solid var(--p-border);padding-top:14px':'' }}">
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                      letter-spacing:.07em;margin-bottom:7px">
            Obunalar · {{ $followingCount }} ta
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:4px">
            @foreach($following as $f)
            <a href="{{ route('panel.users.show', $f->id) }}"
               title="{{ $f->name }} {{ $f->lastname }}"
               style="width:30px;height:30px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-info),#0ea5e9);
                      display:flex;align-items:center;justify-content:center;
                      font-size:11px;font-weight:700;color:#fff;text-decoration:none">
              @if($f->avatar)
                <img src="{{ $f->avatar }}" style="width:100%;height:100%;object-fit:cover">
              @else {{ strtoupper(substr($f->name,0,1)) }} @endif
            </a>
            @endforeach
          </div>
        </div>
        @endif
      </div>
    </div>
    @endif

  </div>{{-- /col-xl-4 --}}

  {{-- ── O'NG: Tabs ──────────────────────────────────────── --}}
  <div class="col-xl-8">

    <div class="tab-pills fade-up mb-3">
      @foreach([
        ['orders',   'Buyurtmalar',  'bi-bag-check',  $orderCount],
        ['cart',     'Savat',        'bi-cart3',       $cartItems->count()],
        ['gifts',    'Sertifikatlar','bi-gift',         $giftCertCount],
        ['mystery',  'Mystery Box',  'bi-box-seam',     $mysterySubCount],
        ['bookclub', 'Book Club',    'bi-chat-quote',   $bcPostsCount + $bcRepostsCount],
      ] as [$key,$lbl,$icon,$cnt])
      <a href="{{ request()->fullUrlWithQuery(['section'=>$key]) }}"
         class="tab-pill {{ $activeSection===$key?'active':'' }}">
        <i class="bi {{ $icon }}" style="font-size:12px"></i>
        {{ $lbl }}
        @if($cnt > 0)
          <span class="tab-count">{{ $cnt }}</span>
        @endif
      </a>
      @endforeach
    </div>

    {{-- ─── BUYURTMALAR ──────────────────────────────────── --}}
    @if($activeSection === 'orders')

      @if($orderCount === 0)
      <div class="p-card fade-up" style="text-align:center;padding:56px 24px">
        <i class="bi bi-bag-x"
           style="font-size:38px;color:var(--p-hint);display:block;margin-bottom:10px"></i>
        <div style="font-size:14px;color:var(--p-hint)">Buyurtmalar yo'q</div>
      </div>
      @else
      <div class="p-card fade-up">
        <div class="p-card-header">
          <div class="p-card-title">So'nggi buyurtmalar</div>
          <a href="{{ route('panel.orders.index', ['user_id'=>$user->id]) }}"
             class="btn-p ghost sm" style="font-size:11px">
            Barchasi <i class="bi bi-arrow-right"></i>
          </a>
        </div>
        <div class="table-responsive">
          <table class="p-table">
            <thead>
              <tr><th>#</th><th>Summa</th><th>Status</th><th>To'lov</th><th>Sana</th><th></th></tr>
            </thead>
            <tbody>
              @foreach($recentOrders as $order)
              @php
                $stMap=['A'=>'ob-a','P'=>'ob-p','B'=>'ob-b','C'=>'ob-c','F'=>'ob-f'];
                $stLbl=['A'=>'Kutilmoqda','P'=>'Qadoqlanmoqda','B'=>"Yo'lda",'C'=>'Yetkazildi','F'=>'Bekor'];
                $pCls=match($order->paymentStatus){2=>'success',3=>'danger',1=>'warning',default=>'muted'};
                $pLbl=match($order->paymentStatus){2=>"To'langan",3=>'Rad',1=>'Karta',default=>'Naqd'};
              @endphp
              <tr>
                <td>
                  <a href="{{ route('panel.orders.show',$order) }}"
                     style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);
                            font-weight:600;font-size:12px">#{{ $order->id }}</a>
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:600;
                           font-size:13px;color:var(--p-text)">
                  {{ number_format($order->amount) }}
                </td>
                <td>
                  <span class="o-badge {{ $stMap[$order->status]??'ob-p' }}">
                    {{ $stLbl[$order->status]??$order->status }}
                  </span>
                </td>
                <td>
                  <span class="s-pill {{ $pCls }}" style="font-size:10px">{{ $pLbl }}</span>
                </td>
                <td style="font-size:11px;color:var(--p-hint);
                           font-family:'JetBrains Mono',monospace">
                  {{ $order->created_at?->format('d.m H:i') }}
                </td>
                <td>
                  <a href="{{ route('panel.orders.show',$order) }}" class="btn-p ghost sm">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      @endif

    {{-- ─── SAVAT ───────────────────────────────────────── --}}
    @elseif($activeSection === 'cart')

      @if($cartItems->count() === 0)
      <div class="p-card fade-up" style="text-align:center;padding:56px 24px">
        <i class="bi bi-cart-x"
           style="font-size:38px;color:var(--p-hint);display:block;margin-bottom:10px"></i>
        <div style="font-size:14px;color:var(--p-hint)">Savat bo'sh</div>
      </div>
      @else
      <div class="p-card fade-up">
        <div class="p-card-header">
          <div class="p-card-title">Savatdagi mahsulotlar</div>
          <span style="font-size:15px;font-weight:700;font-family:'JetBrains Mono',monospace;
                       color:var(--p-success)">
            {{ number_format($cartTotal) }} UZS
          </span>
        </div>
        @foreach($cartItems as $item)
        <div style="display:flex;align-items:center;gap:12px;
                    padding:11px 18px;border-top:1px solid var(--p-border)">
          <div style="width:{{ $item->product_type==='book'?'34px':'38px' }};
                      height:{{ $item->product_type==='book'?'46px':'38px' }};
                      border-radius:{{ $item->product_type==='book'?'4px':'7px' }};
                      overflow:hidden;flex-shrink:0;background:var(--p-elevated);
                      display:flex;align-items:center;justify-content:center">
            @if($item->image)
              <img src="{{ $item->image }}" style="width:100%;height:100%;object-fit:cover">
            @else
              <i class="bi bi-{{ $item->product_type==='book'?'book':'box' }}"
                 style="color:var(--p-hint);font-size:12px"></i>
            @endif
          </div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:4px;margin-bottom:2px">
              <span class="s-pill {{ $item->product_type==='book'?'info':'warning' }}"
                    style="font-size:9px">
                {{ $item->product_type==='book'?'Kitob':'Kanstovar' }}
              </span>
              @if($item->variant)
                <span class="s-pill muted" style="font-size:9px">{{ $item->variant }}</span>
              @endif
            </div>
            <a href="{{ $item->route }}" target="_blank"
               style="font-size:13px;font-weight:500;color:var(--p-text);
                      text-decoration:none;display:block;
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              {{ $item->name }}
            </a>
            <div style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              {{ number_format($item->price) }} × {{ $item->count }}
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:13px;font-weight:700;font-family:'JetBrains Mono',monospace;
                        color:var(--p-text)">{{ number_format($item->subtotal) }}</div>
            <div style="font-size:10px;color:var(--p-hint)">UZS</div>
          </div>
        </div>
        @endforeach
        <div style="padding:11px 18px;border-top:1px solid var(--p-border);
                    background:var(--p-elevated);display:flex;
                    justify-content:space-between;align-items:center">
          <span style="font-size:12px;color:var(--p-hint)">
            {{ $cartItems->count() }} ta mahsulot
          </span>
          <span style="font-size:15px;font-weight:700;font-family:'JetBrains Mono',monospace;
                       color:var(--p-success)">{{ number_format($cartTotal) }} UZS</span>
        </div>
      </div>
      @endif

    {{-- ─── GIFT SERTIFIKATLAR ──────────────────────────── --}}
    @elseif($activeSection === 'gifts')
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-gift me-1" style="color:var(--p-accent)"></i> Gift Sertifikatlar
        </div>
        <span class="s-pill muted" style="font-size:10px">{{ $giftCertCount }} ta</span>
      </div>
      <div class="table-responsive">
        <table class="p-table">
          <thead>
            <tr>
              <th>Kod</th>
              <th>Rol</th>
              <th style="text-align:right">Miqdor</th>
              <th>Qabul qiluvchi</th>
              <th>Holat</th>
              <th>Yaratildi</th>
              <th>Ishlatildi</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($giftCerts as $cert)
            @php
              $isBuyer = $cert->buyer_user_id === $user->id;
              $stCls = match($cert->status){
                'used'=>'success','sent'=>'accent','paid'=>'info',
                'cancelled'=>'danger',default=>'warning'
              };
              $stLbl = match($cert->status){
                'pending_payment'=>'Kutilmoqda','paid'=>"To'landi",
                'sent'=>'Yuborildi','used'=>'Ishlatildi',
                'cancelled'=>'Bekor',default=>$cert->status
              };
            @endphp
            <tr>
              <td>
                <code style="font-family:'JetBrains Mono',monospace;font-size:11px;font-weight:700;
                             color:var(--p-accent);background:var(--p-accent-d);
                             padding:2px 7px;border-radius:4px">{{ $cert->code }}</code>
              </td>
              <td>
                <span class="s-pill {{ $isBuyer ? 'info' : 'success' }}" style="font-size:10px">
                  {{ $isBuyer ? 'Sotib olgan' : 'Qabul qilgan' }}
                </span>
              </td>
              <td style="text-align:right;font-family:'JetBrains Mono',monospace;
                         font-weight:700;font-size:13px;color:var(--p-success)">
                {{ number_format($cert->nominal_uzs) }} UZS
              </td>
              <td style="font-size:12px;color:var(--p-muted)">
                @if($isBuyer)
                  {{ $cert->recipient_name ?: ($cert->recipient?->name ?? '—') }}
                  @if($cert->recipient_phone)
                  <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                    {{ $cert->recipient_phone }}
                  </div>
                  @endif
                @else
                  <a href="{{ route('panel.users.show',$cert->buyer_user_id) }}"
                     style="font-size:12px;color:var(--p-accent);text-decoration:none">
                    {{ $cert->buyer?->name }}
                  </a>
                @endif
              </td>
              <td><span class="s-pill {{ $stCls }}" style="font-size:10px">{{ $stLbl }}</span></td>
              <td style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                {{ $cert->created_at?->format('d.m.Y') }}
              </td>
              <td style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                {{ $cert->used_at?->format('d.m.Y') ?? '—' }}
              </td>
              <td>
                <a href="{{ route('panel.gift-certificates.show',$cert) }}"
                   class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8" style="text-align:center;padding:24px;color:var(--p-hint)">
                Sertifikatlar yo'q
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{-- Statistika --}}
      @if($giftCertCount > 0)
      @php
        $boughtCerts = $giftCerts->where('buyer_user_id',$user->id);
        $receivedUsed = $giftCerts->where('recipient_user_id',$user->id)->where('status','used');
      @endphp
      <div style="padding:12px 20px;background:var(--p-elevated);
                  border-top:1px solid var(--p-border);display:flex;gap:24px;flex-wrap:wrap">
        @if($boughtCerts->count())
        <div>
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
            Sotib olgan
          </div>
          <div style="font-size:15px;font-weight:700;font-family:'JetBrains Mono',monospace;
                      color:var(--p-accent)">{{ $boughtCerts->count() }} ta</div>
        </div>
        <div>
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
            Jami sarflagan
          </div>
          <div style="font-size:15px;font-weight:700;font-family:'JetBrains Mono',monospace;
                      color:var(--p-text)">{{ number_format($boughtCerts->sum('nominal_uzs')) }} UZS</div>
        </div>
        @endif
        @if($receivedUsed->count())
        <div>
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
            Qabul qilib ishlatdi
          </div>
          <div style="font-size:15px;font-weight:700;font-family:'JetBrains Mono',monospace;
                      color:var(--p-success)">{{ number_format($receivedUsed->sum('nominal_uzs')) }} UZS</div>
        </div>
        @endif
      </div>
      @endif
    </div>

    {{-- ─── MYSTERY BOX ─────────────────────────────────── --}}
    @elseif($activeSection === 'mystery')
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-box-seam me-1" style="color:var(--p-accent)"></i> Mystery Box obunalari
        </div>
        <span class="s-pill muted" style="font-size:10px">{{ $mysterySubCount }} ta</span>
      </div>

      @forelse($mysterySubs as $sub)
      @php $addr = is_array($sub->address) ? $sub->address : []; @endphp
      <div style="padding:16px 20px;border-top:1px solid var(--p-border)">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)">
              {{ $sub->plan?->name_uz ?? '—' }}
              <span class="s-pill {{ $sub->status_color }}" style="font-size:10px;margin-left:6px">
                {{ $sub->status_label }}
              </span>
            </div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
              {{ $sub->total_months }} oy ·
              {{ $sub->books_per_month }} kitob/oy ·
              {{ number_format($sub->price_uzs) }} UZS ·
              Boshlandi: {{ $sub->started_at?->format('d.m.Y') ?? '—' }}
            </div>
          </div>
          <a href="{{ route('panel.mystery-box.subscription',$sub) }}"
             class="btn-p ghost sm"><i class="bi bi-arrow-up-right-square"></i></a>
        </div>

        {{-- Progress --}}
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="flex:1;height:7px;background:var(--p-elevated);border-radius:4px;overflow:hidden">
            <div style="height:100%;border-radius:4px;
                        background:var(--p-{{ $sub->status_color }});
                        width:{{ $sub->progress_pct }}%"></div>
          </div>
          <span style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--p-muted);
                       white-space:nowrap">
            {{ $sub->delivered_months }}/{{ $sub->total_months }} oy
          </span>
          @if($sub->next_delivery_at)
          <span style="font-size:11px;color:{{ $sub->next_delivery_at->isPast() ? 'var(--p-danger)' : 'var(--p-hint)' }};
                       white-space:nowrap">
            <i class="bi bi-calendar3"></i>
            {{ $sub->next_delivery_at->format('d.m.Y') }}
          </span>
          @endif
        </div>

        {{-- Manzil --}}
        <div style="background:var(--p-elevated);border-radius:8px;padding:10px 12px;
                    display:flex;align-items:flex-start;gap:10px">
          <i class="bi bi-geo-alt" style="color:var(--p-accent);font-size:14px;margin-top:2px"></i>
          <div>
            <div style="font-size:12.5px;font-weight:500;color:var(--p-text)">
              {{ $addr['fullName'] ?? '—' }}
              @if($addr['phoneNumber'] ?? null)
              <span style="font-family:'JetBrains Mono',monospace;color:var(--p-hint);font-size:11px">
                · {{ $addr['phoneNumber'] }}
              </span>
              @endif
            </div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:2px">
              {{ $addr['fullAddress'] ?? '—' }}
            </div>
          </div>
          @if(($addr['lat'] ?? null) && ($addr['lon'] ?? null))
          <a href="https://maps.yandex.uz/?text={{ $addr['lat'] }}+{{ $addr['lon'] }}&z=16"
             target="_blank" class="btn-p ghost sm" style="margin-left:auto;flex-shrink:0">
            <i class="bi bi-map"></i>
          </a>
          @endif
        </div>

        {{-- Oylik yetkazishlar holati --}}
        @if($sub->deliveries->count())
        <div style="margin-top:10px;display:flex;gap:5px;flex-wrap:wrap">
          @foreach($sub->deliveries->sortBy('month_number') as $d)
          <a href="{{ route('panel.mystery-box.subscription',$sub) }}"
             style="display:flex;align-items:center;gap:4px;padding:4px 10px;
                    border-radius:5px;font-size:11px;font-family:'JetBrains Mono',monospace;
                    background:var(--p-elevated);border:1px solid var(--p-border);
                    text-decoration:none;
                    color:{{ match($d->status){
                      'delivered'=>'var(--p-success)',
                      'shipped'=>'var(--p-info)',
                      'preparing'=>'var(--p-warning)',
                      default=>'var(--p-hint)'
                    } }}"
             title="{{ $d->status_label }}">
            <i class="bi bi-{{ match($d->status){
              'delivered'=>'check-circle-fill',
              'shipped'=>'truck',
              'preparing'=>'box-seam',
              default=>'hourglass'
            } }}"></i>
            {{ $d->month_number }}-oy
          </a>
          @endforeach
        </div>
        @endif
      </div>
      @empty
      <div style="padding:36px;text-align:center;color:var(--p-hint)">
        <i class="bi bi-box" style="font-size:28px;display:block;margin-bottom:8px"></i>
        Mystery Box obunalari yo'q
      </div>
      @endforelse
    </div>

    {{-- ─── BOOK CLUB ───────────────────────────────────── --}}
    @elseif($activeSection === 'bookclub')

      <div class="d-flex gap-2 mb-3">
        @foreach([['posts','Postlar',$bcPostsCount],['reposts','Repostlar',$bcRepostsCount]] as [$k,$l,$cnt])
        <a href="{{ request()->fullUrlWithQuery(['section'=>'bookclub','bc_tab'=>$k]) }}"
           class="btn-p {{ $tab===$k?'':'ghost' }} sm">
          {{ $l }} <span class="tab-badge">{{ $cnt }}</span>
        </a>
        @endforeach
      </div>

      @if($bcPosts->count() === 0)
      <div class="p-card fade-up" style="text-align:center;padding:56px 24px">
        <i class="bi bi-chat-square-text"
           style="font-size:38px;color:var(--p-hint);display:block;margin-bottom:10px"></i>
        <div style="font-size:14px;color:var(--p-hint)">
          {{ $tab==='reposts'?'Repostlar':'Postlar' }} yo'q
        </div>
      </div>
      @else
        @foreach($bcPosts as $post)
          @include('panel.book-club._post-card', ['post'=>$post,'showUser'=>false])
        @endforeach
        {{ $bcPosts->appends(request()->except('bc_page'))->links('panel.partials.pagination') }}
      @endif

    @endif

  </div>{{-- /col-xl-8 --}}
</div>{{-- /row --}}

@endsection