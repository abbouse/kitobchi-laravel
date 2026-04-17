@extends('panel.layouts.panel')
@section('title', 'Support murojaat')
@section('page-title', 'Support murojaatlar')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
  <div class="tab-pills">
    <a href="{{ request()->fullUrlWithQuery(['tab'=>'all','page'=>1]) }}"
       class="tab-pill {{ $tab==='all'?'active':'' }}">
      Barchasi <span class="tab-badge">{{ $counts['all'] }}</span>
    </a>
    @foreach($statuses as $key => $s)
    <a href="{{ request()->fullUrlWithQuery(['tab'=>$key,'page'=>1]) }}"
       class="tab-pill {{ $tab===$key?'active':'' }}">
      {{ $s['label'] }} <span class="tab-badge">{{ $counts[$key] ?? 0 }}</span>
    </a>
    @endforeach
  </div>
  <a href="{{ route('panel.bot-tickets.operators') }}" class="btn-p ghost">
    <i class="bi bi-headset"></i> Operatorlar
  </a>
</div>

{{-- Filter --}}
<div class="filter-bar mb-3">
  <form method="GET" class="d-flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="search" name="search" class="p-form-control" placeholder="ID, ism, username..."
           value="{{ request('search') }}" style="width:200px">
    <select name="operator_id" class="p-form-control" style="width:180px">
      <option value="">Barcha operatorlar</option>
      @foreach($operators as $op)
      <option value="{{ $op->id }}" {{ request('operator_id')==$op->id?'selected':'' }}>
        {{ $op->name ?? $op->username }}
      </option>
      @endforeach
    </select>
    <input type="date" name="date_from" class="p-form-control" value="{{ request('date_from') }}" style="width:145px">
    <input type="date" name="date_to"   class="p-form-control" value="{{ request('date_to') }}"   style="width:145px">
    <button class="btn-p" type="submit"><i class="bi bi-search"></i></button>
    <a href="{{ route('panel.bot-tickets.index',['tab'=>$tab]) }}" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

{{-- Table --}}
<div class="p-card p-0">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Foydalanuvchi</th>
          <th>Birinchi xabar</th>
          <th>Operator</th>
          <th>Status</th>
          <th>Baho</th>
          <th>Sana</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($tickets as $ticket)
        @php $st = $statuses[$ticket->status] ?? ['label'=>$ticket->status,'class'=>'ob-p']; @endphp
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">#{{ $ticket->id }}</td>
          <td>
            <div>
              <div style="font-size:13px;font-weight:500;color:var(--p-text)">
                {{ $ticket->name ?: 'Noma\'lum' }}
              </div>
              @if($ticket->username)
                <div style="font-size:11px;color:var(--p-hint)">t.me/{{ $ticket->username }}</div>
              @endif
              @if($ticket->user_id)
                <div style="font-size:10px;color:var(--p-accent);font-family:'JetBrains Mono',monospace">
                  user #{{ $ticket->user_id }}
                </div>
              @endif
            </div>
          </td>
          <td style="max-width:200px">
            <div style="font-size:12px;color:var(--p-muted);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
              {{ $ticket->first_msg ?: '—' }}
            </div>
          </td>
          <td>
            @if($ticket->operator)
              <div style="display:flex;align-items:center;gap:6px">
                <div style="width:6px;height:6px;border-radius:50%;background:var(--p-{{ $ticket->operator->status==='online'?'success':($ticket->operator->status==='busy'?'warning':'muted') }})"></div>
                <span style="font-size:13px;color:var(--p-text)">{{ $ticket->operator->name ?? $ticket->operator->username }}</span>
              </div>
            @elseif($ticket->status === 'queue')
              <span style="font-size:12px;color:var(--p-warning)">Tayinlanmagan</span>
            @else
              <span style="color:var(--p-hint)">—</span>
            @endif
          </td>
          <td><span class="o-badge {{ $st['class'] }}">{{ $st['label'] }}</span></td>
          <td>
            @if($ticket->rating)
              <div style="display:flex;align-items:center;gap:2px">
                @for($i=1;$i<=5;$i++)
                  <i class="bi bi-star{{ $i<=$ticket->rating?'-fill':'' }}"
                     style="font-size:12px;color:{{ $i<=$ticket->rating?'var(--p-warning)':'var(--p-border)' }}"></i>
                @endfor
              </div>
            @else
              <span style="color:var(--p-hint)">—</span>
            @endif
          </td>
          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap">
            {{ $ticket->created_at?->format('d.m H:i') }}
          </td>
          <td>
            <a href="{{ route('panel.bot-tickets.show', $ticket) }}" class="btn-p ghost sm">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-chat-square-text" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Murojaatlar topilmadi
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($tickets->hasPages())
  <div class="p-pagination">{{ $tickets->links('panel.partials.pagination') }}</div>
  @endif
</div>
@endsection