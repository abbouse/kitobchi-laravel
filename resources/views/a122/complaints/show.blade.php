@extends('a122.layouts.admin')
@section('title', 'Shikoyat #'.$report->id)
@section('page-title', 'Shikoyat #'.$report->id)

@section('content')

@php
  $stCls = match($report->status){'reviewed'=>'success','dismissed'=>'muted',default=>'warning'};
  $stLbl = match($report->status){'reviewed'=>'Ko\'rib chiqilgan','dismissed'=>'Rad etilgan',default=>'Kutilmoqda'};
@endphp

<x-a122.page-header back-href="{{ route('admin.complaints.index') }}">
  <x-slot name="heading">Shikoyat #{{ $report->id }}</x-slot>
  <x-slot name="meta">
    <p class="page-sub flex flex-wrap items-center gap-2">
      {{ \Carbon\Carbon::parse($report->created_at)->format('d.m.Y H:i') }}
      <span class="s-pill {{ $stCls }}" style="font-size:11px">{{ $stLbl }}</span>
    </p>
  </x-slot>
  <x-slot name="actions">
    <div class="flex flex-wrap gap-2">
      @if($report->status === 'pending')
        <form method="POST" action="{{ route('admin.complaints.status',$report) }}">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="reviewed">
          <button class="btn-p success">
            <i class="bi bi-check-lg"></i> Ko'rildi
          </button>
        </form>
        <form method="POST" action="{{ route('admin.complaints.status',$report) }}">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="dismissed">
          <button class="btn-p ghost">
            <i class="bi bi-x-lg"></i> Rad etish
          </button>
        </form>
      @else
        <form method="POST" action="{{ route('admin.complaints.status',$report) }}">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="pending">
          <button class="btn-p ghost" style="color:var(--p-warning)">
            <i class="bi bi-arrow-counterclockwise"></i> Qayta ochish
          </button>
        </form>
      @endif

      <form method="POST" action="{{ route('admin.complaints.destroy',$report) }}"
            onsubmit="return confirm('O\'chirilsinmi?')">
        @csrf @method('DELETE')
        <button class="btn-p danger ghost"><i class="bi bi-trash"></i></button>
      </form>
    </div>
  </x-slot>
</x-a122.page-header>

<section class="a122-section mb-4">
  <div class="a122-section-body">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="kpi-soft">
        <div class="metric-label">Holat</div>
        <div class="metric-value text-xl">{{ $stLbl }}</div>
        <div class="metric-meta">Moderatsiya bosqichi</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Tur</div>
        <div class="metric-value text-xl">{{ $report->reportable_type === 'conversation_message' ? 'Chat' : ($report->reportable_type === 'book_club' ? 'Book Club' : 'Other') }}</div>
        <div class="metric-meta">Report category</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Qo‘shimcha reportlar</div>
        <div class="metric-value text-xl">{{ number_format($otherReports->count()) }}</div>
        <div class="metric-meta">Shu obyekt bo‘yicha</div>
      </div>
      <div class="kpi-soft">
        <div class="metric-label">Yaratilgan</div>
        <div class="metric-value text-xl">{{ \Carbon\Carbon::parse($report->created_at)->format('d.m') }}</div>
        <div class="metric-meta">{{ \Carbon\Carbon::parse($report->created_at)->format('H:i') }}</div>
      </div>
    </div>
  </div>
</section>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  <div class="xl:col-span-4">

    {{-- Shikoyatchi --}}
    <div class="card-panel mb-3 fade-up">
      <div class="card-panel-header"><div class="card-panel-title">Shikoyatchi</div></div>
      <div style="padding:14px 18px">
        @if($report->user)
        <div class="flex items-center gap-3 mb-3">
          <div style="width:46px;height:46px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;
                      font-size:18px;font-weight:700;color:#fff">
            @if($report->user->avatar)
              <img src="{{ $report->user->avatar }}" style="width:100%;height:100%;object-fit:cover">
            @else
              {{ strtoupper(substr($report->user->name,0,1)) }}
            @endif
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              {{ $report->user->name }} {{ $report->user->lastname }}
            </div>
            <div style="font-size:12px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              {{ $report->user->phone_number }}
            </div>
          </div>
        </div>
        <a href="{{ route('admin.users.show',$report->user_id) }}"
           class="btn-p ghost" style="width:100%;justify-content:center;font-size:12px">
          <i class="bi bi-person"></i> Profil
        </a>
        @else
          <div style="color:var(--p-hint)">User #{{ $report->user_id }}</div>
        @endif
      </div>
    </div>

    {{-- Shikoyat info --}}
    <div class="card-panel mb-3 fade-up">
      <div class="card-panel-header"><div class="card-panel-title">Tafsilotlar</div></div>
      <div style="padding:0 18px 14px">
        @foreach([
          ['Tur', match($report->reportable_type){
            'conversation_message'=>'💬 Xabar',
            'book_club'=>'📚 Book Club',
            default=>$report->reportable_type
          }],
          ['Obyekt ID', '#'.$report->reportable_id],
          ['Holat', $stLbl],
        ] as [$k,$v])
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:9px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $k }}</span>
          <span style="font-size:12px;font-weight:500;color:var(--p-text)">{{ $v }}</span>
        </div>
        @endforeach
      </div>
    </div>

    {{-- Boshqa shikoyatlar --}}
    @if($otherReports->count())
    <div class="card-panel fade-up" style="border-color:rgba(245,166,35,.3)">
      <div class="card-panel-header">
        <div class="card-panel-title" style="color:var(--p-warning)">
          <i class="bi bi-exclamation-triangle-fill mr-1"></i>Boshqa shikoyatlar
        </div>
        <span class="s-pill warning" style="font-size:10px">{{ $otherReports->count() }}</span>
      </div>
      <div style="padding:0 18px 14px">
        @foreach($otherReports as $or)
        <div style="padding:8px 0;border-bottom:1px solid var(--p-border)">
          <div class="flex items-center justify-between">
            <span style="font-size:12px;color:var(--p-text)">{{ $or->reason }}</span>
            <a href="{{ route('admin.complaints.show',$or) }}"
               style="font-size:11px;color:var(--p-accent)">Ko'rish</a>
          </div>
          <div style="font-size:10px;color:var(--p-hint)">
            {{ \Carbon\Carbon::parse($or->created_at)->format('d.m.Y') }}
            · <span class="s-pill {{ match($or->status){'reviewed'=>'success','dismissed'=>'muted',default=>'warning'} }}"
                   style="font-size:9px">{{ $or->status }}</span>
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif

  </div>

  <div class="xl:col-span-8">

    {{-- Sabab va izoh --}}
    <div class="card-panel mb-3 fade-up">
      <div class="card-panel-header"><div class="card-panel-title">Shikoyat matni</div></div>
      <div style="padding:16px 18px">
        <div style="margin-bottom:14px">
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                      letter-spacing:.07em;margin-bottom:6px">Sabab</div>
          <div style="font-size:15px;font-weight:600;color:var(--p-text)">
            {{ $report->reason }}
          </div>
        </div>
        @if($report->comment)
        <div>
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                      letter-spacing:.07em;margin-bottom:6px">Izoh</div>
          <div style="font-size:13px;color:var(--p-muted);line-height:1.7;
                      background:var(--p-elevated);padding:13px 15px;
                      border-radius:10px;border:1px solid var(--p-border)">
            {{ $report->comment }}
          </div>
        </div>
        @endif
      </div>
    </div>

    {{-- Reportable kontent --}}
    @if($reportable)
    <div class="card-panel fade-up" style="border-color:rgba(255,92,106,.2)">
      <div class="card-panel-header">
        <div class="card-panel-title" style="color:var(--p-danger)">
          <i class="bi bi-flag-fill mr-1"></i>Shikoyat qilingan kontent
        </div>
        <span class="s-pill danger" style="font-size:10px">
          #{{ $report->reportable_id }}
        </span>
      </div>
      <div style="padding:14px 18px">

        @if($report->reportable_type === 'conversation_message')
        <div style="background:var(--p-elevated);border-radius:10px;padding:14px;
                    border:1px solid var(--p-border)">
          @if($reportable->message ?? null)
          <div style="font-size:13px;color:var(--p-text);line-height:1.7;margin-bottom:8px">
            {{ $reportable->message }}
          </div>
          @endif
          @if($reportable->image ?? null)
          <img src="{{ $reportable->image }}"
               style="max-width:200px;border-radius:8px;display:block;margin-bottom:8px">
          @endif
          <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
            User #{{ $reportable->user_id ?? $reportable->sender_id }}
            · {{ \Carbon\Carbon::parse($reportable->created_at)->format('d.m.Y H:i') }}
          </div>
        </div>
        @if($reportable->conversation_id ?? null)
        <a href="{{ route('admin.chats.show',$reportable->conversation_id) }}"
           class="btn-p ghost sm mt-2">
          <i class="bi bi-chat-dots"></i> Suhbatni ko'rish
        </a>
        @endif

        @elseif($report->reportable_type === 'book_club')
        <div style="background:var(--p-elevated);border-radius:10px;padding:14px;
                    border:1px solid var(--p-border)">
          @if($reportable->text ?? null)
          <div style="font-size:13px;color:var(--p-text);line-height:1.7;margin-bottom:8px">
            {{ $reportable->text }}
          </div>
          @endif
          <div style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
            Post #{{ $reportable->id }}
            · User #{{ $reportable->user_id }}
            · {{ \Carbon\Carbon::parse($reportable->created_at)->format('d.m.Y H:i') }}
          </div>
        </div>
        <a href="{{ route('admin.book-club.show',$reportable->id) }}"
           class="btn-p ghost sm mt-2">
          <i class="bi bi-chat-quote"></i> Postni ko'rish
        </a>
        @endif

      </div>
    </div>

    @else
    <div class="card-panel fade-up">
      <div style="padding:30px;text-align:center;color:var(--p-hint)">
        <i class="bi bi-question-circle" style="font-size:28px;display:block;margin-bottom:8px"></i>
        Kontent topilmadi (o'chirilgan bo'lishi mumkin)
      </div>
    </div>
    @endif

  </div>
</div>

@endsection
