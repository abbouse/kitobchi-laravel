@extends('a122.layouts.admin')
@section('title', 'Push Bildirishnomalar')
@section('page-title', 'Push Bildirishnomalar')

@section('content')

<x-a122.page-header>
  <x-slot name="heading">Push bildirishnomalar</x-slot>
  <x-slot name="meta">Barcha yuborilgan push xabarlar tarixi</x-slot>
  <x-slot name="actions">
    <a href="{{ route('admin.push.create') }}" class="btn-p primary">
        <i class="bi bi-send"></i> Yangi yuborish
      </a>
  </x-slot>
</x-a122.page-header>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
  @foreach([
    [$counts['all'] ?? 0, 'Jami yuborish', 'accent', 'bi-bell'],
    [$counts['users'] ?? 0, 'Userlar', 'info', 'bi-people'],
    [$counts['business'] ?? 0, 'Sellerlar', 'warning', 'bi-shop-window'],
    [$counts['courier'] ?? 0, 'Kuryerlar', 'success', 'bi-bicycle'],
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
    <div class="a122-index-header__title">Yuborilgan pushlar</div>
    <div class="a122-index-header__meta">{{ $notifications->total() }} ta bildirishnoma tarixi ko'rinmoqda</div>
  </div>
</div>

{{-- Table --}}
<div class="p-card p-0 fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
      <thead>
        <tr>
          <th>#</th>
          <th>Sarlavha</th>
          <th>Matn</th>
          <th>Qabul qiluvchi</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($notifications as $notif)
        @php
          $t = $targets[$notif->who] ?? ['label'=>$notif->who,'color'=>'muted','icon'=>'bi-bell'];
        @endphp
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">
            #{{ $notif->id }}
          </td>
          <td>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              {{ $notif->name }}
            </div>
          </td>
          <td style="max-width:280px">
            <div style="font-size:12px;color:var(--p-muted);
                        overflow:hidden;display:-webkit-box;
                        -webkit-line-clamp:2;-webkit-box-orient:vertical">
              {{ $notif->description }}
            </div>
          </td>
          <td>
            <span class="s-pill {{ $t['color'] }}"
                  style="display:inline-flex;align-items:center;gap:5px;font-size:12px">
              <i class="bi {{ $t['icon'] }}"></i>
              {{ $t['label'] }}
            </span>
          </td>
          <td style="font-size:12px;color:var(--p-hint);white-space:nowrap;font-family:'JetBrains Mono',monospace">
            {{ $notif->created_at?->format('d.m.Y H:i') }}
          </td>
          <td>
            <form method="POST" action="{{ route('admin.push.destroy', $notif) }}"
                  onsubmit="return confirm('O\'chirilsinmi?')">
              @csrf @method('DELETE')
              <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" style="text-align:center;padding:50px;color:var(--p-hint)">
            <i class="bi bi-bell-slash" style="font-size:36px;display:block;margin-bottom:12px"></i>
            Bildirishnomalar yo'q
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($notifications->hasPages())
  {{ $notifications->links('a122.partials.pagination') }}
  @endif
</div>

@endsection
