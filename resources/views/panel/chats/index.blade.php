@extends('panel.layouts.panel')
@section('title', 'Chat kuzatuv')
@section('page-title', 'Chat kuzatuv')

@section('content')

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Chat kuzatuv</h1>
    <p class="page-sub">Foydalanuvchilar suhbatlari va AI chat tarixi</p>
  </div>
</div>

{{-- Tabs --}}
<div class="tab-pills fade-up mb-3">
  @foreach([
    'user'   => ['👤 User–User',    $counts['user']],
    'seller' => ['🏪 User–Do\'kon', $counts['seller']],
    'ai'     => ['🤖 AI Chat',      $counts['ai']],
  ] as $k => [$l, $c])
  <a href="{{ request()->fullUrlWithQuery(['tab'=>$k,'page'=>1]) }}"
     class="tab-pill {{ $tab===$k?'active':'' }}">
    {{ $l }} <span class="tab-count">{{ $c }}</span>
  </a>
  @endforeach
</div>

{{-- Search --}}
<form method="GET" class="filter-bar fade-up mb-3">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="search-box" style="width:240px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="search" name="search" value="{{ request('search') }}"
           placeholder="Ism, telefon, do'kon...">
  </div>
  <button type="submit" class="btn-p primary">
    <i class="bi bi-funnel"></i> Qidirish
  </button>
  @if(request('search'))
  <a href="{{ request()->fullUrlWithQuery(['search'=>null]) }}" class="btn-p ghost">
    <i class="bi bi-x"></i> Tozalash
  </a>
  @endif
</form>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        @if($tab === 'ai')
        <tr>
          <th>Foydalanuvchi</th>
          <th>Xabarlar</th>
          <th>So'nggi xabar</th>
          <th>Vaqt</th>
          <th></th>
        </tr>
        @elseif($tab === 'seller')
        <tr>
          <th>#</th>
          <th>Foydalanuvchi</th>
          <th>Do'kon</th>
          <th>So'nggi faollik</th>
          <th></th>
        </tr>
        @else
        <tr>
          <th>#</th>
          <th>Yuboruvchi</th>
          <th>Qabul qiluvchi</th>
          <th>So'nggi faollik</th>
          <th></th>
        </tr>
        @endif
      </thead>
      <tbody>
        @forelse($conversations as $conv)

        {{-- AI tab --}}
        @if($tab === 'ai')
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;
                          background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                          display:flex;align-items:center;justify-content:center;
                          font-size:11px;font-weight:700;color:#fff">
                @if($conv->avatar)
                  <img src="{{ $conv->avatar }}" style="width:100%;height:100%;object-fit:cover">
                @else
                  {{ strtoupper(substr($conv->name??'U',0,1)) }}
                @endif
              </div>
              <div>
                <a href="{{ route('panel.users.show',$conv->user_id) }}"
                   style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
                  {{ $conv->name }} {{ $conv->lastname }}
                </a>
                <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                  {{ $conv->phone_number }}
                </div>
              </div>
            </div>
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">
            {{ $conv->messages_count ?? '—' }}
          </td>
          <td style="font-size:12px;color:var(--p-muted);max-width:200px;
                     white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            {{ Str::limit($conv->last_message ?? '—', 40) }}
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            {{ $conv->last_at
              ? \Carbon\Carbon::parse($conv->last_at)->diffForHumans()
              : '—' }}
          </td>
          <td>
            <a href="{{ route('panel.chats.show-ai', $conv->user_id) }}"
               class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>

        {{-- Seller tab --}}
        @elseif($tab === 'seller')
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #{{ $conv->id }}
          </td>
          <td>
            @include('panel.chats._user-cell', [
              'name'   => $conv->user1_name,
              'lname'  => $conv->user1_lastname,
              'avatar' => $conv->user1_avatar,
              'uid'    => $conv->user_id,
            ])
          </td>
          <td>
            @if($conv->seller_name)
            <a href="{{ route('panel.sellers.show', $conv->seller_id_val) }}"
               style="font-size:12.5px;font-weight:500;color:var(--p-warning);text-decoration:none">
              <i class="bi bi-shop-window me-1"></i>{{ $conv->seller_name }}
            </a>
            @else
              <span style="color:var(--p-hint)">—</span>
            @endif
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            {{ $conv->last_message_at
              ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans()
              : '—' }}
          </td>
          <td>
            <a href="{{ route('panel.chats.show', $conv->id) }}"
               class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>

        {{-- User-User tab --}}
        @else
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #{{ $conv->id }}
          </td>
          <td>
            @include('panel.chats._user-cell', [
              'name'   => $conv->user1_name,
              'lname'  => $conv->user1_lastname,
              'avatar' => $conv->user1_avatar,
              'uid'    => $conv->user_id,
            ])
          </td>
          <td>
            @if($conv->user2_name)
            @include('panel.chats._user-cell', [
              'name'   => $conv->user2_name,
              'lname'  => $conv->user2_lastname,
              'avatar' => $conv->user2_avatar,
              'uid'    => $conv->receiver_id,
            ])
            @else
              <span style="color:var(--p-hint);font-size:12px">—</span>
            @endif
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            {{ $conv->last_message_at
              ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans()
              : '—' }}
          </td>
          <td>
            <a href="{{ route('panel.chats.show', $conv->id) }}"
               class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        @endif

        @empty
        <tr>
          <td colspan="5" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-chat-square" style="font-size:28px;display:block;margin-bottom:8px"></i>
            Suhbatlar yo'q
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($conversations->hasPages())
  <div class="d-flex align-items-center justify-content-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $conversations->firstItem() }}–{{ $conversations->lastItem() }}
      / {{ $conversations->total() }}
    </div>
    {{ $conversations->links('panel.partials.pagination') }}
  </div>
  @endif
</div>

@endsection