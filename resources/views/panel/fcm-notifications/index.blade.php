@extends('panel.layouts.panel')
@section('title', 'Push Bildirishnomalar')
@section('page-title', 'Push Bildirishnomalar')

@section('content')

<x-panel.page-header>
  <x-slot name="heading">Push bildirishnomalar</x-slot>
  <x-slot name="meta">Barcha yuborilgan push xabarlar tarixi</x-slot>
  <x-slot name="actions">
    <a href="{{ route('panel.fcm-notifications.create') }}" class="btn-p primary">
        <i class="bi bi-send"></i> Yangi yuborish
      </a>
  </x-slot>
</x-panel.page-header>


<div class="tab-pills fade-up mb-3">
  <a href="{{ request()->fullUrlWithQuery(['tab'=>'all','page'=>1]) }}"
     class="tab-pill {{ $tab==='all'?'active':'' }}">
    Barchasi <span class="tab-badge">{{ $counts['all'] }}</span>
  </a>
  @foreach($targets as $key => $t)
  <a href="{{ request()->fullUrlWithQuery(['tab'=>$key,'page'=>1]) }}"
     class="tab-pill {{ $tab===$key?'active':'' }}">
    <i class="bi {{ $t['icon'] }}"></i> {{ Str::before($t['label'], ' (') }}
    <span class="tab-badge">{{ $counts[$key] }}</span>
  </a>
  @endforeach
</div>

{{-- Filter --}}
<div class="filter-bar mb-3 fade-up">
  <form method="GET" class="flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="search" name="search" class="p-form-control"
           placeholder="Sarlavha yoki matn bo'yicha..."
           value="{{ request('search') }}" style="width:260px">
    <button class="btn-p primary" type="submit"><i class="bi bi-search"></i></button>
    <a href="{{ route('panel.fcm-notifications.index', ['tab'=>$tab]) }}"
       class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

{{-- Table --}}
<div class="p-card p-0 fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
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
            <form method="POST" action="{{ route('panel.fcm-notifications.destroy', $notif) }}"
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
  <div class="p-pagination">{{ $notifications->links('panel.partials.pagination') }}</div>
  @endif
</div>

@endsection