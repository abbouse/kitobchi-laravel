@extends('panel.layouts.panel')
@section('title', $marketNews->title)
@section('page-title', $marketNews->title)

@section('content')

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div class="d-flex align-items-center gap-3">
    <a href="{{ route('panel.market-news.index') }}" class="btn-p ghost icon">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">{{ $marketNews->title }}</h1>
      <p class="page-sub" style="display:flex;align-items:center;gap:8px">
        {{ $marketNews->created_at?->format('d.m.Y H:i') }}
        @if($marketNews->status)
          <span class="s-pill success" style="font-size:10px">Faol</span>
        @else
          <span class="s-pill danger" style="font-size:10px">Nofaol</span>
        @endif
      </p>
    </div>
  </div>
  <div class="d-flex gap-2">
    <form method="POST" action="{{ route('panel.market-news.toggle', $marketNews) }}">
      @csrf @method('PATCH')
      <button class="btn-p {{ $marketNews->status ? 'ghost' : 'success' }}">
        <i class="bi bi-{{ $marketNews->status ? 'pause' : 'play' }}-fill"></i>
        {{ $marketNews->status ? 'O\'chirish' : 'Faollashtirish' }}
      </button>
    </form>
    <a href="{{ route('panel.market-news.edit', $marketNews) }}" class="btn-p ghost">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
    <form method="POST" action="{{ route('panel.market-news.destroy', $marketNews) }}"
          onsubmit="return confirm('O\'chirilsinmi?')">
      @csrf @method('DELETE')
      <button class="btn-p danger"><i class="bi bi-trash"></i></button>
    </form>
  </div>
</div>

<div class="row g-3">

  {{-- ── Chap ─────────────────────────────────────── --}}
  <div class="col-xl-5">

    {{-- Rasm --}}
    @if($marketNews->imgUrl)
    <div class="p-card mb-3 fade-up" style="padding:0;overflow:hidden">
      <img src="{{ asset('storage/'.$marketNews->imgUrl) }}"
           style="width:100%;display:block;max-height:220px;object-fit:cover">
    </div>
    @endif

    {{-- Tafsilotlar --}}
    <div class="p-card fade-up">
      <div class="p-card-header"><div class="p-card-title">Tafsilotlar</div></div>
      <div style="padding:0 18px 14px">
        @php
          $actionColor = match($marketNews->action){
            'to_shop'    => 'warning',
            'to_product' => 'info',
            default      => 'muted',
          };
        @endphp
        @foreach([
          ['Joylashuv', $marketNews->align === 'top' ? '⬆ Yuqori' : '↔ O\'rta'],
          ['Status',    $marketNews->status ? '✅ Faol' : '⭕ Nofaol'],
        ] as [$k,$v])
        <div style="display:flex;justify-content:space-between;
                    padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $k }}</span>
          <span style="font-size:12px;font-weight:500;color:var(--p-text)">{{ $v }}</span>
        </div>
        @endforeach
        <div style="display:flex;justify-content:space-between;
                    padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">Action</span>
          <span class="s-pill {{ $actionColor }}" style="font-size:10px">
            {{ $marketNews->action_label }}
          </span>
        </div>
        @if($marketNews->action_id)
        <div style="display:flex;justify-content:space-between;padding:9px 0">
          <span style="font-size:12px;color:var(--p-hint)">Action ID</span>
          <span style="font-size:13px;font-weight:600;font-family:'JetBrains Mono',monospace;
                       color:var(--p-accent)">#{{ $marketNews->action_id }}</span>
        </div>
        @endif
      </div>
    </div>

  </div>

  {{-- ── O'ng ──────────────────────────────────────── --}}
  <div class="col-xl-7">

    {{-- Tavsif --}}
    @if($marketNews->description)
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Tavsif</div></div>
      <div style="padding:0 18px 18px;font-size:14px;color:var(--p-muted);line-height:1.8">
        {{ $marketNews->description }}
      </div>
    </div>
    @endif

    {{-- Action preview --}}
    @if($marketNews->action !== 'news' && $marketNews->action_id)
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          @if($marketNews->action === 'to_shop')
            <i class="bi bi-shop-window me-1" style="color:var(--p-warning)"></i>
            Do'kon preview
          @else
            <i class="bi bi-book me-1" style="color:var(--p-info)"></i>
            Kitob preview
          @endif
        </div>
        <span style="font-size:11px;color:var(--p-hint)">Flutter ichida shunday ko'rinadi</span>
      </div>
      <div style="padding:0 18px 18px">

        @if($marketNews->action === 'to_shop' && $preview)
        {{-- Seller preview --}}
        <a href="{{ route('panel.sellers.show', $preview->id) }}"
           style="display:flex;align-items:center;gap:14px;padding:14px;
                  background:var(--p-elevated);border-radius:12px;
                  border:1px solid var(--p-border);text-decoration:none;
                  transition:border-color .15s"
           onmouseover="this.style.borderColor='var(--p-warning)'"
           onmouseout="this.style.borderColor='var(--p-border)'">
          <div style="width:52px;height:52px;border-radius:10px;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-warning),#f97316);
                      display:flex;align-items:center;justify-content:center;
                      font-size:20px;font-weight:700;color:#fff">
            @if($preview->photo)
              <img src="{{ asset('storage/'.$preview->photo) }}"
                   style="width:100%;height:100%;object-fit:cover">
            @else
              {{ strtoupper(substr($preview->shop_name,0,1)) }}
            @endif
          </div>
          <div style="flex:1">
            <div style="font-size:15px;font-weight:600;color:var(--p-text)">
              {{ $preview->shop_name }}
              @if($preview->isVerified)
                <i class="bi bi-patch-check-fill"
                   style="color:var(--p-info);font-size:13px"></i>
              @endif
            </div>
            <div style="font-size:12px;color:var(--p-hint);margin-top:2px">
              {{ $preview->phone_number }}
            </div>
            @if($preview->rating)
            <div style="font-size:12px;color:var(--p-warning);margin-top:3px">
              ⭐ {{ $preview->rating }}
            </div>
            @endif
          </div>
          <i class="bi bi-arrow-up-right-square"
             style="font-size:18px;color:var(--p-accent)"></i>
        </a>

        @elseif($marketNews->action === 'to_product' && $preview)
        {{-- Book preview --}}
        @php
          $imgs  = is_array($preview->images) ? $preview->images : json_decode($preview->images ?? '[]', true);
          $img   = $imgs[0] ?? null;
          $price = ($preview->discountPrice ?? 0) > 0 ? $preview->discountPrice : $preview->price;
        @endphp
        <a href="{{ route('panel.books.show', $preview->id) }}"
           style="display:flex;align-items:center;gap:14px;padding:14px;
                  background:var(--p-elevated);border-radius:12px;
                  border:1px solid var(--p-border);text-decoration:none;
                  transition:border-color .15s"
           onmouseover="this.style.borderColor='var(--p-info)'"
           onmouseout="this.style.borderColor='var(--p-border)'">
          <div style="width:42px;height:58px;border-radius:6px;overflow:hidden;flex-shrink:0;
                      background:var(--p-elevated);display:flex;align-items:center;
                      justify-content:center">
            @if($img)
              <img src="{{ $img }}" style="width:100%;height:100%;object-fit:cover">
            @else
              <i class="bi bi-book" style="font-size:18px;color:var(--p-hint)"></i>
            @endif
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:14px;font-weight:600;color:var(--p-text);
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              {{ $preview->name }}
            </div>
            <div style="font-size:12px;color:var(--p-hint)">{{ $preview->author }}</div>
            <div style="font-size:13px;font-weight:700;color:var(--p-success);
                        font-family:'JetBrains Mono',monospace;margin-top:4px">
              {{ number_format($price) }} UZS
            </div>
          </div>
          <i class="bi bi-arrow-up-right-square"
             style="font-size:18px;color:var(--p-accent)"></i>
        </a>

        @else
        <div style="padding:24px;text-align:center;color:var(--p-hint)">
          <i class="bi bi-exclamation-circle"
             style="font-size:24px;display:block;margin-bottom:8px"></i>
          ID <strong>#{{ $marketNews->action_id }}</strong> topilmadi
          (o'chirilgan bo'lishi mumkin)
        </div>
        @endif

      </div>
    </div>

    @elseif($marketNews->action === 'news')
    <div class="p-card fade-up"
         style="background:var(--p-elevated);border-style:dashed">
      <div style="padding:24px;text-align:center;color:var(--p-hint)">
        <i class="bi bi-newspaper"
           style="font-size:28px;display:block;margin-bottom:8px"></i>
        Bu yangilik — bosish orqali hech yerga o'tmaydi
      </div>
    </div>
    @endif

  </div>

</div>

@endsection