@extends('panel.layouts.panel')
@section('title', 'Shikoyatlar')
@section('page-title', 'Shikoyatlar')

@section('content')

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">Shikoyatlar</h1>
    <p class="page-sub">Foydalanuvchilar yuborgan shikoyatlar</p>
  </div>
</div>

{{-- Tabs --}}
<div class="tab-pills fade-up mb-3">
  @foreach([
    'pending'   => ['Kutilmoqda', 'warning', $counts['pending']],
    'reviewed'  => ["Ko'rib chiqilgan", 'success', $counts['reviewed']],
    'dismissed' => ['Rad etilgan', 'muted', $counts['dismissed']],
  ] as $k => [$l, $c, $cnt])
  <a href="{{ request()->fullUrlWithQuery(['tab'=>$k,'page'=>1]) }}"
     class="tab-pill {{ $tab===$k?'active':'' }}">
    {{ $l }} <span class="tab-count">{{ $cnt }}</span>
  </a>
  @endforeach
</div>

{{-- Filter --}}
<form method="GET" class="filter-bar fade-up mb-3">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="search-box" style="width:220px;margin-left:0">
    <i class="bi bi-search"></i>
    <input type="search" name="search" value="{{ request('search') }}"
           placeholder="Sabab, ID...">
  </div>
  <select name="type" class="p-form-control" style="width:180px">
    <option value="">Barcha turlar</option>
    @foreach($types as $type)
    <option value="{{ $type }}" {{ request('type')===$type?'selected':'' }}>
      {{ match($type){
        'conversation_message'=>'💬 Xabar',
        'book_club'=>'📚 Book Club',
        default=>$type
      } }}
    </option>
    @endforeach
  </select>
  <button type="submit" class="btn-p primary">
    <i class="bi bi-funnel"></i> Filter
  </button>
  @if(request('search') || request('type'))
  <a href="{{ route('panel.reports.index',['tab'=>$tab]) }}" class="btn-p ghost">
    <i class="bi bi-x"></i> Tozalash
  </a>
  @endif
</form>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Shikoyatchi</th>
          <th>Tur · ID</th>
          <th>Sabab</th>
          <th>Izoh</th>
          <th>Holat</th>
          <th>Vaqt</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($reports as $r)
        @php
          $stCls = match($r->status){
            'reviewed'=>'success','dismissed'=>'muted',default=>'warning'
          };
          $stLbl = match($r->status){
            'reviewed'=>'Ko\'rildi','dismissed'=>'Rad',default=>'Yangi'
          };
        @endphp
        <tr>
          <td style="font-family:'DM Mono',monospace;color:var(--p-accent)">#{{ $r->id }}</td>

          <td>
            @if($r->user)
            <div class="d-flex align-items-center gap-2">
              <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;
                          background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                          display:flex;align-items:center;justify-content:center;
                          font-size:11px;font-weight:700;color:#fff">
                @if($r->user->avatar)
                  <img src="{{ $r->user->avatar }}" style="width:100%;height:100%;object-fit:cover">
                @else
                  {{ strtoupper(substr($r->user->name,0,1)) }}
                @endif
              </div>
              <div>
                <a href="{{ route('panel.users.show',$r->user_id) }}"
                   style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
                  {{ $r->user->name }} {{ $r->user->lastname }}
                </a>
                <div style="font-size:10px;color:var(--p-hint);font-family:'DM Mono',monospace">
                  {{ $r->user->phone_number }}
                </div>
              </div>
            </div>
            @else
              <span style="color:var(--p-hint);font-size:12px">#{{ $r->user_id }}</span>
            @endif
          </td>

          <td>
            <span class="s-pill {{ match($r->reportable_type){
              'conversation_message'=>'info','book_club'=>'accent',default=>'muted'
            } }}" style="font-size:10px">
              {{ match($r->reportable_type){
                'conversation_message'=>'💬 Xabar',
                'book_club'=>'📚 Book Club',
                default=>$r->reportable_type
              } }}
            </span>
            <div style="font-size:10px;color:var(--p-hint);margin-top:2px">
              #{{ $r->reportable_id }}
            </div>
          </td>

          <td style="max-width:150px">
            <div style="font-size:12.5px;color:var(--p-text);font-weight:500">
              {{ $r->reason }}
            </div>
          </td>

          <td style="max-width:140px">
            @if($r->comment)
            <div style="font-size:12px;color:var(--p-muted);white-space:nowrap;
                        overflow:hidden;text-overflow:ellipsis;max-width:130px"
                 title="{{ $r->comment }}">
              {{ $r->comment }}
            </div>
            @else
              <span style="color:var(--p-hint);font-size:11px">—</span>
            @endif
          </td>

          <td><span class="s-pill {{ $stCls }}" style="font-size:10px">{{ $stLbl }}</span></td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'DM Mono',monospace">
            {{ \Carbon\Carbon::parse($r->created_at)->format('d.m.Y H:i') }}
          </td>

          <td>
            <div class="d-flex gap-1 align-items-center">
              <a href="{{ route('panel.reports.show',$r) }}" class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>

              @if($r->status === 'pending')
              {{-- Ko'rildi --}}
              <form method="POST" action="{{ route('panel.reports.status',$r) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="reviewed">
                <button class="btn-p success sm" title="Ko'rildi">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
              {{-- Rad etish --}}
              <form method="POST" action="{{ route('panel.reports.status',$r) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="dismissed">
                <button class="btn-p ghost sm" title="Rad etish" style="color:var(--p-muted)">
                  <i class="bi bi-x-lg"></i>
                </button>
              </form>
              @endif

              {{-- Qayta pending --}}
              @if($r->status !== 'pending')
              <form method="POST" action="{{ route('panel.reports.status',$r) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="pending">
                <button class="btn-p ghost sm" title="Qayta ochish" style="color:var(--p-warning)">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </form>
              @endif

              <form method="POST" action="{{ route('panel.reports.destroy',$r) }}"
                    onsubmit="return confirm('O\'chirilsinmi?')">
                @csrf @method('DELETE')
                <button class="btn-p danger sm">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-flag" style="font-size:28px;display:block;margin-bottom:8px"></i>
            Shikoyatlar yo'q
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($reports->hasPages())
  <div class="d-flex align-items-center justify-content-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $reports->firstItem() }}–{{ $reports->lastItem() }} / {{ $reports->total() }}
    </div>
    {{ $reports->links('panel.partials.pagination') }}
  </div>
  @endif
</div>

@endsection