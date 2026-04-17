@extends('panel.layouts.panel')
@section('title', 'Suhbat #'.$conversation->id)
@section('page-title', 'Suhbat #'.$conversation->id)

@section('content')

@php
  $isShop = !empty($conversation->shop_id);
  // Ishtirokchi 1: user_id → u1
  $p1Name  = trim(($conversation->user1_name??'') . ' ' . ($conversation->user1_lastname??''));
  $p1Phone = $conversation->user1_phone ?? '';
  $p1Av    = $conversation->user1_avatar ?? null;
  $p1Id    = $conversation->user_id;
  // Ishtirokchi 2: seller yoki receiver
  $p2Name  = $isShop
    ? ($conversation->seller_name ?? 'Do\'kon')
    : trim(($conversation->user2_name??'') . ' ' . ($conversation->user2_lastname??''));
  $p2Phone = $conversation->user2_phone ?? '';
  $p2Av    = $conversation->user2_avatar ?? null;
  $p2Id    = $isShop ? null : $conversation->receiver_id;
  $p2Route = $isShop
    ? route('panel.sellers.show', $conversation->seller_id_val ?? 0)
    : ($p2Id ? route('panel.users.show', $p2Id) : null);
@endphp

<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="{{ route('panel.chats.index', ['tab' => $isShop ? 'seller' : 'user']) }}"
     class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h1 class="page-title">Suhbat #{{ $conversation->id }}</h1>
    <p class="page-sub">
      {{ $p1Name }} ↔
      @if($isShop)
        <i class="bi bi-shop-window me-1"></i>{{ $p2Name }}
      @else
        {{ $p2Name }}
      @endif
    </p>
  </div>
</div>

<div class="row g-3">

  {{-- Chap: Ishtirokchilar --}}
  <div class="col-xl-3">
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Ishtirokchilar</div></div>
      <div style="padding:14px 18px">

        {{-- Ishtirokchi 1 --}}
        <div style="display:flex;align-items:center;gap:10px;padding:10px 0;
                    border-bottom:1px solid var(--p-border)">
          <div style="width:36px;height:36px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;
                      font-size:13px;font-weight:700;color:#fff">
            @if($p1Av)
              <img src="{{ $p1Av }}" style="width:100%;height:100%;object-fit:cover">
            @else
              {{ strtoupper(substr($p1Name ?? 'U', 0, 1)) }}
            @endif
          </div>
          <div>
            <a href="{{ route('panel.users.show',$p1Id) }}"
               style="font-size:13px;font-weight:500;color:var(--p-text);text-decoration:none">
              {{ $p1Name }}
            </a>
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              {{ $p1Phone }}
            </div>
          </div>
        </div>

        {{-- Ishtirokchi 2 --}}
        <div style="display:flex;align-items:center;gap:10px;padding:10px 0">
          <div style="width:36px;height:36px;
                      border-radius:{{ $isShop ? '8px' : '50%' }};
                      overflow:hidden;flex-shrink:0;
                      background:{{ $isShop
                        ? 'linear-gradient(135deg,var(--p-warning),#f97316)'
                        : 'linear-gradient(135deg,var(--p-info),#0ea5e9)' }};
                      display:flex;align-items:center;justify-content:center;
                      font-size:13px;font-weight:700;color:#fff">
            @if($p2Av)
              <img src="{{ $p2Av }}" style="width:100%;height:100%;object-fit:cover">
            @else
              {{ strtoupper(substr($p2Name ?? 'U', 0, 1)) }}
            @endif
          </div>
          <div>
            @if($p2Route)
            <a href="{{ $p2Route }}"
               style="font-size:13px;font-weight:500;color:var(--p-text);text-decoration:none">
              @if($isShop)<i class="bi bi-shop-window me-1" style="color:var(--p-warning)"></i>@endif
              {{ $p2Name }}
            </a>
            @else
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $p2Name }}</div>
            @endif
            @if($p2Phone)
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              {{ $p2Phone }}
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    {{-- Statistika --}}
    <div class="p-card fade-up">
      <div class="p-card-header"><div class="p-card-title">Statistika</div></div>
      <div style="padding:0 18px 14px">
        @foreach([
          ['Jami xabarlar',     $messages->total()],
          ['Shikoyatli xabar',  count($reportedIds).' ta'],
        ] as [$k,$v])
        <div style="display:flex;justify-content:space-between;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $k }}</span>
          <span style="font-size:12px;font-weight:600;font-family:'JetBrains Mono',monospace;
                       color:var(--p-text)">{{ $v }}</span>
        </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Xabarlar --}}
  <div class="col-xl-9">
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">Xabarlar</div>
        @if(count($reportedIds) > 0)
        <span class="s-pill danger" style="font-size:10px">
          <i class="bi bi-flag-fill me-1"></i>{{ count($reportedIds) }} shikoyatli
        </span>
        @endif
      </div>
      <div style="padding:0 18px 18px;max-height:65vh;overflow-y:auto" id="msg-scroll">

        @forelse($messages as $msg)
        @php
          // sender_id == user_id (conversation boshlagan) bo'lsa chap, aks holda o'ng
          $isLeft    = $msg->sender_id == $conversation->user_id;
          $isReport  = in_array($msg->id, $reportedIds);
          $senderAv  = $isLeft ? $p1Av  : $p2Av;
          $senderInitial = strtoupper(substr($isLeft ? $p1Name : $p2Name, 0, 1));
        @endphp
        <div style="display:flex;gap:10px;margin-bottom:12px;
                    {{ $isLeft ? '' : 'flex-direction:row-reverse' }}">

          {{-- Avatar --}}
          <div style="width:28px;height:28px;border-radius:{{ $isLeft?'50%':($isShop?'8px':'50%') }};
                      flex-shrink:0;margin-top:2px;overflow:hidden;
                      background:{{ $isLeft
                        ? 'linear-gradient(135deg,var(--p-accent),#7c5cfc)'
                        : ($isShop
                          ? 'linear-gradient(135deg,var(--p-warning),#f97316)'
                          : 'linear-gradient(135deg,var(--p-info),#0ea5e9)') }};
                      display:flex;align-items:center;justify-content:center;
                      font-size:11px;font-weight:700;color:#fff">
            @if($senderAv)
              <img src="{{ $senderAv }}" style="width:100%;height:100%;object-fit:cover">
            @else
              {{ $senderInitial }}
            @endif
          </div>

          {{-- Bubble --}}
          <div style="max-width:70%;
                      {{ $isLeft ? '' : 'align-items:flex-end;display:flex;flex-direction:column' }}">
            <div style="background:{{ $isReport
              ? 'rgba(255,92,106,.1)'
              : ($isLeft ? 'var(--p-elevated)' : 'rgba(79,124,255,.1)') }};
                        border:1px solid {{ $isReport ? 'rgba(255,92,106,.3)' : 'var(--p-border)' }};
                        border-radius:{{ $isLeft ? '4px 12px 12px 12px' : '12px 4px 12px 12px' }};
                        padding:10px 13px">

              @if($isReport)
              <div style="font-size:10px;color:var(--p-danger);margin-bottom:5px;
                          display:flex;align-items:center;gap:4px">
                <i class="bi bi-flag-fill"></i> Shikoyat qilingan
              </div>
              @endif

              @if($msg->message ?? null)
              <div style="font-size:13px;color:var(--p-text);line-height:1.6;
                          word-break:break-word">
                {{ $msg->message }}
              </div>
              @endif

              @if($msg->image ?? null)
              <img src="{{ $msg->image }}"
                   style="max-width:200px;border-radius:6px;margin-top:6px;display:block">
              @endif
            </div>
            <div style="font-size:10px;color:var(--p-hint);margin-top:3px;
                        font-family:'JetBrains Mono',monospace;
                        {{ $isLeft ? '' : 'text-align:right' }}">
              {{ \Carbon\Carbon::parse($msg->created_at)->format('d.m H:i') }}
              @if($msg->is_read ?? false)
                <i class="bi bi-check2-all" style="color:var(--p-info)"></i>
              @endif
            </div>
          </div>
        </div>
        @empty
        <div style="text-align:center;padding:30px;color:var(--p-hint)">
          Xabarlar yo'q
        </div>
        @endforelse
      </div>

      @if($messages->hasPages())
      <div style="border-top:1px solid var(--p-border);padding:10px 18px;
                  display:flex;justify-content:center">
        {{ $messages->links('panel.partials.pagination') }}
      </div>
      @endif
    </div>
  </div>

</div>

@endsection

@push('scripts')
<script>
// Auto scroll to bottom on load
const el = document.getElementById('msg-scroll');
if (el) el.scrollTop = el.scrollHeight;
</script>
@endpush