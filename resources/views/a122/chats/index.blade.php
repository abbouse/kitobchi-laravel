@extends('a122.layouts.admin')
@section('title', 'Chat kuzatuv')
@section('page-title', 'Chat kuzatuv')

@section('content')

<x-a122.page-header>
  <x-slot name="heading">Chat kuzatuv</x-slot>
  <x-slot name="meta">Foydalanuvchilar suhbatlari va AI chat tarixi</x-slot>
</x-a122.page-header>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
  @foreach([
    [$counts['user'] ?? 0, 'User chat', 'accent', 'bi-people'],
    [$counts['seller'] ?? 0, "Seller chat", 'warning', 'bi-shop-window'],
    [$counts['ai'] ?? 0, 'AI chat', 'info', 'bi-robot'],
    [($counts['user'] ?? 0) + ($counts['seller'] ?? 0) + ($counts['ai'] ?? 0), 'Jami kanal', 'success', 'bi-chat-dots'],
  ] as [$value, $label, $tone, $icon])
    <div class="p-card flex items-center gap-3 fade-up" style="padding:14px">
      <div style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--p-{{ $tone }}-d,var(--p-elevated));color:var(--p-{{ $tone }})">
        <i class="bi {{ $icon }}"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;color:var(--p-text)">{{ $value }}</div>
        <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--p-hint)">{{ $label }}</div>
      </div>
    </div>
  @endforeach
</div>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Suhbatlar ro'yxati</div>
    <div class="a122-index-header__meta">{{ $conversations->total() }} ta suhbat oqimi mavjud</div>
  </div>
</div>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
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
            <div class="flex items-center gap-2">
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
                <a href="{{ route('admin.users.show',$conv->user_id) }}"
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
            <a href="{{ route('admin.chats.show-ai', $conv->user_id) }}"
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
            @include('a122.chats._user-cell', [
              'name'   => $conv->user1_name,
              'lname'  => $conv->user1_lastname,
              'avatar' => $conv->user1_avatar,
              'uid'    => $conv->user_id,
            ])
          </td>
          <td>
            @if($conv->seller_name)
            <a href="{{ route('admin.sellers.show', $conv->seller_id_val) }}"
               style="font-size:12.5px;font-weight:500;color:var(--p-warning);text-decoration:none">
              <i class="bi bi-shop-window mr-1"></i>{{ $conv->seller_name }}
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
            <a href="{{ route('admin.chats.show', $conv->id) }}"
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
            @include('a122.chats._user-cell', [
              'name'   => $conv->user1_name,
              'lname'  => $conv->user1_lastname,
              'avatar' => $conv->user1_avatar,
              'uid'    => $conv->user_id,
            ])
          </td>
          <td>
            @if($conv->user2_name)
            @include('a122.chats._user-cell', [
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
            <a href="{{ route('admin.chats.show', $conv->id) }}"
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
  <div class="flex items-center justify-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $conversations->firstItem() }}–{{ $conversations->lastItem() }}
      / {{ $conversations->total() }}
    </div>
    {{ $conversations->links('a122.partials.pagination') }}
  </div>
  @endif
</div>

@endsection
