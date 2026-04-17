@extends('panel.layouts.panel')
@section('title', 'Mystery Box')
@section('page-title', 'Mystery Box obunalari')

@section('content')

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Mystery Box</h1>
    <p class="page-sub">Oylik kitob qutisi obunalari</p>
  </div>
  <a href="{{ route('panel.mystery-box.plans') }}" class="btn-p ghost">
    <i class="bi bi-list-ul"></i> Tariflar
  </a>
</div>

{{-- Navbat alert --}}
@if($dueToday > 0)
<div class="p-alert warning fade-up">
  <i class="bi bi-clock-fill"></i>
  Bugun <strong>{{ $dueToday }} ta</strong> obunachi uchun jo'natish navbati keldi!
  @if($dueWeek > $dueToday)
    Bu hafta jami: {{ $dueWeek }} ta.
  @endif
</div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4 fade-up">
  @foreach([
    ['Faol',        $counts['active'],          'success', 'bi-check-circle'],
    ['Kutilmoqda',  $counts['pending_payment'],  'warning', 'bi-hourglass'],
    ['To\'xtatilgan',$counts['paused'],          'muted',   'bi-pause-circle'],
    ['Yakunlandi',  $counts['completed'],        'info',    'bi-flag'],
  ] as [$l,$v,$c,$i])
  <div class="col-6 col-xl-3">
    <div class="p-card d-flex align-items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-{{ $c }}-d,var(--p-elevated));color:var(--p-{{ $c }});
                  display:flex;align-items:center;justify-content:center">
        <i class="bi {{ $i }}"></i>
      </div>
      <div>
        <div style="font-size:19px;font-weight:700;font-family:'JetBrains Mono',monospace;
                    color:var(--p-{{ $c }})">{{ $v }}</div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                    letter-spacing:.07em">{{ $l }}</div>
      </div>
    </div>
  </div>
  @endforeach
</div>

{{-- Tabs --}}
<div class="tab-pills fade-up mb-3">
  @foreach([
    'active'          => ['Faol',         $counts['active']],
    'all'             => ['Barchasi',     $counts['all']],
    'pending_payment' => ['Kutilmoqda',   $counts['pending_payment']],
    'paused'          => ["To'xtatilgan", $counts['paused']],
    'completed'       => ['Yakunlangan',  $counts['completed']],
  ] as $k => [$l, $c])
  <a href="{{ request()->fullUrlWithQuery(['tab'=>$k,'page'=>1]) }}"
     class="tab-pill {{ $tab===$k?'active':'' }}">
    {{ $l }} <span class="tab-count">{{ $c }}</span>
  </a>
  @endforeach
</div>

{{-- Filter --}}
<form method="GET" class="filter-bar fade-up mb-3">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="search-box" style="width:240px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="search" name="search" value="{{ request('search') }}"
           placeholder="Ism, telefon...">
  </div>
  <button type="submit" class="btn-p primary">
    <i class="bi bi-funnel"></i> Filter
  </button>
</form>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Foydalanuvchi</th>
          <th>Tarif</th>
          <th>Manzil</th>
          <th>Progress</th>
          <th>Keyingi yetkazish</th>
          <th>Holat</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($subs as $sub)
        @php
          $addr = is_array($sub->address) ? $sub->address : [];
          $isOverdue = $sub->next_delivery_at && $sub->next_delivery_at->isPast()
            && $sub->status === 'active';
        @endphp
        <tr style="{{ $isOverdue ? 'background:var(--p-warning-d)' : '' }}">
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #{{ $sub->id }}
          </td>

          <td>
            @if($sub->user)
            <a href="{{ route('panel.users.show',$sub->user_id) }}"
               style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
              {{ $sub->user->name }} {{ $sub->user->lastname }}
            </a>
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              {{ $sub->user->phone_number }}
            </div>
            @endif
          </td>

          <td>
            @if($sub->plan)
            <div style="font-size:12.5px;font-weight:500;color:var(--p-text)">
              {{ $sub->plan->name_uz }}
            </div>
            <div style="font-size:10px;color:var(--p-hint)">
              {{ $sub->plan->months }} oy · {{ $sub->books_per_month }} kitob/oy
            </div>
            @endif
          </td>

          <td style="max-width:150px">
            @if($addr['fullAddress'] ?? null)
            <div style="font-size:12px;color:var(--p-muted);white-space:nowrap;
                        overflow:hidden;text-overflow:ellipsis"
                 title="{{ $addr['fullAddress'] }}">
              {{ $addr['fullAddress'] }}
            </div>
            @if($addr['phoneNumber'] ?? null)
            <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              {{ $addr['phoneNumber'] }}
            </div>
            @endif
            @else
              <span style="color:var(--p-hint)">—</span>
            @endif
          </td>

          <td style="min-width:120px">
            <div style="display:flex;align-items:center;gap:8px">
              <div style="flex:1;height:6px;background:var(--p-elevated);
                          border-radius:3px;overflow:hidden">
                <div style="height:100%;background:var(--p-success);
                            border-radius:3px;width:{{ $sub->progress_pct }}%">
                </div>
              </div>
              <span style="font-size:11px;color:var(--p-muted);font-family:'JetBrains Mono',monospace;
                           white-space:nowrap">
                {{ $sub->delivered_months }}/{{ $sub->total_months }}
              </span>
            </div>
          </td>

          <td style="white-space:nowrap">
            @if($sub->next_delivery_at)
            <div style="font-size:12px;font-weight:600;
                        color:{{ $isOverdue ? 'var(--p-danger)' : 'var(--p-text)' }};
                        font-family:'JetBrains Mono',monospace">
              {{ $sub->next_delivery_at->format('d.m.Y') }}
            </div>
            <div style="font-size:10px;color:{{ $isOverdue ? 'var(--p-danger)' : 'var(--p-hint)' }}">
              {{ $sub->next_delivery_at->diffForHumans() }}
              @if($isOverdue) ⚠️ Kechikdi @endif
            </div>
            @else
              <span style="color:var(--p-hint)">—</span>
            @endif
          </td>

          <td>
            <span class="s-pill {{ $sub->status_color }}" style="font-size:10px">
              {{ $sub->status_label }}
            </span>
          </td>

          <td>
            <a href="{{ route('panel.mystery-box.subscription', $sub) }}"
               class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-box" style="font-size:28px;display:block;margin-bottom:8px"></i>
            Obunalar topilmadi
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($subs->hasPages())
  <div class="d-flex justify-content-between align-items-center px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $subs->firstItem() }}–{{ $subs->lastItem() }} / {{ $subs->total() }}
    </div>
    {{ $subs->links('panel.partials.pagination') }}
  </div>
  @endif
</div>

@endsection