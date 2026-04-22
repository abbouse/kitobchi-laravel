@extends('a122.layouts.admin')
@section('title', 'Shikoyatlar')
@section('page-title', 'Shikoyatlar')

@section('content')

<x-a122.page-header>
  <x-slot name="heading">Shikoyatlar</x-slot>
  <x-slot name="meta">Foydalanuvchilar yuborgan shikoyatlar</x-slot>
</x-a122.page-header>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
  @foreach([
    [$counts['pending'] ?? 0, 'Kutilmoqda', 'warning', 'bi-hourglass-split'],
    [$counts['reviewed'] ?? 0, "Ko'rilgan", 'success', 'bi-check2-circle'],
    [$counts['dismissed'] ?? 0, 'Rad etilgan', 'muted', 'bi-slash-circle'],
    [($counts['pending'] ?? 0) + ($counts['reviewed'] ?? 0) + ($counts['dismissed'] ?? 0), 'Jami shikoyat', 'accent', 'bi-flag'],
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
    <div class="a122-index-header__title">Shikoyatlar ro'yxati</div>
    <div class="a122-index-header__meta">{{ $reports->total() }} ta shikoyat ko'rinmoqda</div>
  </div>
</div>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
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
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">#{{ $r->id }}</td>

          <td>
            @if($r->user)
            <div class="flex items-center gap-2">
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
                <a href="{{ route('admin.users.show',$r->user_id) }}"
                   style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
                  {{ $r->user->name }} {{ $r->user->lastname }}
                </a>
                <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
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
                     font-family:'JetBrains Mono',monospace">
            {{ \Carbon\Carbon::parse($r->created_at)->format('d.m.Y H:i') }}
          </td>

          <td>
            <div class="flex gap-1 items-center">
              <a href="{{ route('admin.complaints.show',$r) }}" class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>

              @if($r->status === 'pending')
              {{-- Ko'rildi --}}
              <form method="POST" action="{{ route('admin.complaints.status',$r) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="reviewed">
                <button class="btn-p success sm" title="Ko'rildi">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
              {{-- Rad etish --}}
              <form method="POST" action="{{ route('admin.complaints.status',$r) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="dismissed">
                <button class="btn-p ghost sm" title="Rad etish" style="color:var(--p-muted)">
                  <i class="bi bi-x-lg"></i>
                </button>
              </form>
              @endif

              {{-- Qayta pending --}}
              @if($r->status !== 'pending')
              <form method="POST" action="{{ route('admin.complaints.status',$r) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="pending">
                <button class="btn-p ghost sm" title="Qayta ochish" style="color:var(--p-warning)">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </form>
              @endif

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
  <div class="flex items-center justify-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $reports->firstItem() }}–{{ $reports->lastItem() }} / {{ $reports->total() }}
    </div>
    {{ $reports->links('a122.partials.pagination') }}
  </div>
  @endif
</div>

@endsection
