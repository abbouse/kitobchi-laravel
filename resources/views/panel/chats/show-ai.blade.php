@extends('panel.layouts.panel')
@section('title', 'AI Chat — '.$conversation->name.' '.$conversation->lastname)
@section('page-title', 'AI Chat')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="{{ route('panel.chats.index', ['tab'=>'ai']) }}" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h1 class="page-title">AI Chat</h1>
    <p class="page-sub">
      {{ $conversation->name }} {{ $conversation->lastname }}
      · {{ $conversation->phone_number }}
    </p>
  </div>
</div>

<div class="row g-3">

  {{-- Chap: User info --}}
  <div class="col-xl-3">
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Foydalanuvchi</div></div>
      <div style="padding:14px 18px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
          <div style="width:46px;height:46px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;
                      font-size:18px;font-weight:700;color:#fff">
            @if($conversation->avatar)
              <img src="{{ $conversation->avatar }}" style="width:100%;height:100%;object-fit:cover">
            @else
              {{ strtoupper(substr($conversation->name??'U',0,1)) }}
            @endif
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              {{ $conversation->name }} {{ $conversation->lastname }}
            </div>
            <div style="font-size:12px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              {{ $conversation->phone_number }}
            </div>
          </div>
        </div>
        <a href="{{ route('panel.users.show', $conversation->user_id) }}"
           class="btn-p ghost" style="width:100%;justify-content:center;font-size:12px">
          <i class="bi bi-person"></i> Profil
        </a>
      </div>
    </div>

    <div class="p-card fade-up">
      <div class="p-card-header"><div class="p-card-title">Statistika</div></div>
      <div style="padding:0 18px 14px">
        <div style="display:flex;justify-content:space-between;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">Jami xabarlar</span>
          <span style="font-size:12px;font-weight:600;font-family:'JetBrains Mono',monospace;
                       color:var(--p-text)">{{ $messages->total() }} ta</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0">
          <span style="font-size:12px;color:var(--p-hint)">So'nggi faollik</span>
          <span style="font-size:12px;color:var(--p-muted)">
            {{ $conversation->updated_at
              ? \Carbon\Carbon::parse($conversation->updated_at)->diffForHumans()
              : '—' }}
          </span>
        </div>
      </div>
    </div>
  </div>

  {{-- O'ng: Xabarlar --}}
  <div class="col-xl-9">
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-robot me-1" style="color:var(--p-accent)"></i>
          AI suhbat tarixi
        </div>
      </div>
      <div style="padding:0 18px 18px;max-height:65vh;overflow-y:auto" id="ai-scroll">

        @forelse($messages as $msg)
        @php
          // chat_messages jadval: is_ai = true (bot), false (user)
          $isUser = !($msg->is_ai ?? false);
        @endphp
        <div style="display:flex;gap:10px;margin-bottom:14px;
                    {{ $isUser ? 'flex-direction:row-reverse' : '' }}">

          {{-- Avatar --}}
          <div style="width:30px;height:30px;border-radius:50%;flex-shrink:0;margin-top:2px;
                      background:{{ $isUser
                        ? 'linear-gradient(135deg,var(--p-accent),#7c5cfc)'
                        : 'linear-gradient(135deg,#14b8a6,#06b6d4)' }};
                      display:flex;align-items:center;justify-content:center;
                      font-size:13px;color:#fff">
            @if($isUser)
              {{ strtoupper(substr($conversation->name??'U',0,1)) }}
            @else
              <i class="bi bi-robot"></i>
            @endif
          </div>

          {{-- Bubble --}}
          <div style="max-width:72%;
                      {{ $isUser ? 'align-items:flex-end;display:flex;flex-direction:column' : '' }}">
            <div style="font-size:10px;color:var(--p-hint);margin-bottom:4px;font-weight:500;
                        {{ $isUser ? 'text-align:right' : '' }}">
              {{ $isUser ? ($conversation->name ?? 'User') : '🤖 AI Yordamchi' }}
            </div>
            <div style="background:{{ $isUser
              ? 'rgba(79,124,255,.1)'
              : 'var(--p-elevated)' }};
                        border:1px solid var(--p-border);
                        border-radius:{{ $isUser ? '12px 4px 12px 12px' : '4px 12px 12px 12px' }};
                        padding:11px 14px">
              <div style="font-size:13px;color:var(--p-text);line-height:1.7;
                          word-break:break-word;white-space:pre-wrap">
                {{ $msg->message ?? '' }}
              </div>
            </div>

            {{-- AI qaytargan mahsulotlar ro'yxati --}}
            @if(!$isUser && isset($msg->items) && $msg->items->count())
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;max-width:480px">
              @foreach($msg->items as $item)
              @php
                $isBook = $item->type === 'book';
                $route  = $isBook
                  ? route('panel.books.show', $item->id)
                  : route('panel.stationery.show', $item->id);
              @endphp
              <a href="{{ $route }}" target="_blank"
                 style="display:flex;align-items:center;gap:8px;
                        background:var(--p-surface);border:1px solid var(--p-border);
                        border-radius:10px;padding:7px 10px;text-decoration:none;
                        min-width:160px;max-width:220px;transition:border-color .12s"
                 onmouseover="this.style.borderColor='var(--p-border2)'"
                 onmouseout="this.style.borderColor='var(--p-border)'">

                {{-- Rasm --}}
                <div style="width:{{ $isBook ? '30px' : '36px' }};
                            height:{{ $isBook ? '42px' : '36px' }};
                            border-radius:{{ $isBook ? '4px' : '8px' }};
                            overflow:hidden;flex-shrink:0;background:var(--p-elevated);
                            display:flex;align-items:center;justify-content:center">
                  @if($item->image)
                    <img src="{{ $item->image }}"
                         style="width:100%;height:100%;object-fit:cover">
                  @else
                    <i class="bi bi-{{ $isBook ? 'book' : 'box' }}"
                       style="color:var(--p-hint);font-size:12px"></i>
                  @endif
                </div>

                {{-- Ma'lumot --}}
                <div style="min-width:0;flex:1">
                  <div style="font-size:11.5px;font-weight:600;color:var(--p-text);
                              white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    {{ Str::limit($item->name, 22) }}
                  </div>
                  @if($isBook && $item->author)
                  <div style="font-size:10px;color:var(--p-hint);
                              white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    {{ $item->author }}
                  </div>
                  @else
                  <span class="s-pill {{ $isBook ? 'info' : 'warning' }}"
                        style="font-size:9px;padding:1px 6px">
                    {{ $isBook ? 'Kitob' : 'Kanstovar' }}
                  </span>
                  @endif
                  <div style="font-size:10px;color:var(--p-success);font-weight:600;
                              font-family:'JetBrains Mono',monospace;margin-top:2px">
                    {{ number_format($item->price) }} UZS
                  </div>
                </div>
              </a>
              @endforeach
            </div>
            @endif
            <div style="font-size:10px;color:var(--p-hint);margin-top:3px;
                        font-family:'JetBrains Mono',monospace;
                        {{ $isUser ? 'text-align:right' : '' }}">
              {{ \Carbon\Carbon::parse($msg->created_at)->format('d.m H:i') }}
            </div>
          </div>
        </div>
        @empty
        <div style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-robot" style="font-size:32px;display:block;margin-bottom:8px"></i>
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
const el = document.getElementById('ai-scroll');
if (el) el.scrollTop = el.scrollHeight;
</script>
@endpush