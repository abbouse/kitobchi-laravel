@extends('a122.layouts.admin')
@section('title', 'Chat kuzatuv')
@section('page-title', 'Chat kuzatuv')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Community" title="Chat kuzatuv" subtitle="{{ $conversations->total() }} ta suhbat" />

  <div class="row g-3">
    @foreach([
      [$counts['user'] ?? 0, 'User chat', 'bi-people', 'primary'],
      [$counts['seller'] ?? 0, 'Seller chat', 'bi-shop-window', 'warning'],
      [$counts['ai'] ?? 0, 'AI chat', 'bi-robot', 'info'],
      [($counts['user'] ?? 0) + ($counts['seller'] ?? 0) + ($counts['ai'] ?? 0), 'Jami kanal', 'bi-chat-dots', 'success'],
    ] as [$value, $label, $icon, $tone])
      <div class="col-6 col-xl-3">
        <div class="a122-stat-tile h-100">
          <div class="a122-stat-tile__icon bg-{{ $tone }}-subtle text-{{ $tone }}">
            <i class="bi {{ $icon }}"></i>
          </div>
          <div>
            <div class="a122-stat-tile__value">{{ number_format($value) }}</div>
            <div class="a122-stat-tile__label">{{ $label }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      @foreach([
        'user' => ['User chat', $counts['user'] ?? 0],
        'seller' => ['Seller chat', $counts['seller'] ?? 0],
        'ai' => ['AI chat', $counts['ai'] ?? 0],
      ] as $key => [$label, $count])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
          {{ $label }}
          <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($count) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <x-admin.section-card title="Suhbatlar jadvali" :meta="$conversations->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0 data-table">
        <thead>
          @if($tab === 'ai')
            <tr>
              <th>Foydalanuvchi</th>
              <th>Xabarlar</th>
              <th>So‘nggi xabar</th>
              <th>Vaqt</th>
              <th class="text-end">Amallar</th>
            </tr>
          @elseif($tab === 'seller')
            <tr>
              <th>ID</th>
              <th>Foydalanuvchi</th>
              <th>Do‘kon</th>
              <th>So‘nggi faollik</th>
              <th class="text-end">Amallar</th>
            </tr>
          @else
            <tr>
              <th>ID</th>
              <th>Yuboruvchi</th>
              <th>Qabul qiluvchi</th>
              <th>So‘nggi faollik</th>
              <th class="text-end">Amallar</th>
            </tr>
          @endif
        </thead>
        <tbody>
          @forelse($conversations as $conv)
            @if($tab === 'ai')
              <tr>
                <td>
                  <a href="{{ route('admin.users.show', $conv->user_id) }}" class="fw-semibold text-decoration-none">{{ $conv->name }} {{ $conv->lastname }}</a>
                  <div class="small text-secondary">{{ $conv->phone_number }}</div>
                </td>
                <td class="fw-semibold">{{ $conv->messages_count ?? '—' }}</td>
                <td class="text-secondary text-truncate" style="max-width: 18rem;">{{ Str::limit($conv->last_message ?? '—', 54) }}</td>
                <td class="text-secondary text-nowrap">{{ $conv->last_at ? \Carbon\Carbon::parse($conv->last_at)->diffForHumans() : '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('admin.chats.show-ai', $conv->user_id) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish"><i class="bi bi-eye"></i></a>
                </td>
              </tr>
            @elseif($tab === 'seller')
              <tr>
                <td class="text-secondary">#{{ $conv->id }}</td>
                <td>@include('a122.chats._user-cell', ['name' => $conv->user1_name, 'lname' => $conv->user1_lastname, 'avatar' => $conv->user1_avatar, 'uid' => $conv->user_id])</td>
                <td>
                  @if($conv->seller_name)
                    <a href="{{ route('admin.sellers.show', $conv->seller_id_val) }}" class="fw-semibold text-decoration-none"><i class="bi bi-shop-window me-1"></i>{{ $conv->seller_name }}</a>
                  @else
                    <span class="text-secondary">—</span>
                  @endif
                </td>
                <td class="text-secondary text-nowrap">{{ $conv->last_message_at ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans() : '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('admin.chats.show', $conv->id) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish"><i class="bi bi-eye"></i></a>
                </td>
              </tr>
            @else
              <tr>
                <td class="text-secondary">#{{ $conv->id }}</td>
                <td>@include('a122.chats._user-cell', ['name' => $conv->user1_name, 'lname' => $conv->user1_lastname, 'avatar' => $conv->user1_avatar, 'uid' => $conv->user_id])</td>
                <td>
                  @if($conv->user2_name)
                    @include('a122.chats._user-cell', ['name' => $conv->user2_name, 'lname' => $conv->user2_lastname, 'avatar' => $conv->user2_avatar, 'uid' => $conv->receiver_id])
                  @else
                    <span class="text-secondary">—</span>
                  @endif
                </td>
                <td class="text-secondary text-nowrap">{{ $conv->last_message_at ? \Carbon\Carbon::parse($conv->last_message_at)->diffForHumans() : '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('admin.chats.show', $conv->id) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish"><i class="bi bi-eye"></i></a>
                </td>
              </tr>
            @endif
          @empty
            <tr><td colspan="5" class="text-center py-5 text-secondary">Suhbat topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($conversations->hasPages())
    <div>{{ $conversations->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
