@extends('panel.layouts.panel')
@section('title', $book->name)
@section('page-title', $book->name)
@section('breadcrumb', 'Panel / Kitoblar / Ko\'rish')

@section('content')

<div class="page-header fade-up d-flex align-items-start justify-content-between">
  <div class="d-flex align-items-center gap-3">
    <a href="{{ route('panel.books.index') }}" class="btn-p ghost icon"><i class="bi bi-arrow-left"></i></a>
    <div>
      <h1 class="page-title">{{ $book->name }}</h1>
      <p class="page-sub">{{ $book->author }} · ID: #{{ $book->id }}</p>
    </div>
  </div>
  <div class="d-flex gap-2">
    @if($book->is_approved != 1)
    <form method="POST" action="{{ route('panel.books.moderate', $book) }}">
      @csrf @method('PATCH')
      <input type="hidden" name="is_approved" value="1">
      <button class="btn-p success"><i class="bi bi-check-lg"></i> Tasdiqlash</button>
    </form>
    @endif
    @if($book->is_approved != 2)
    <form method="POST" action="{{ route('panel.books.moderate', $book) }}">
      @csrf @method('PATCH')
      <input type="hidden" name="is_approved" value="2">
      <button class="btn-p danger"><i class="bi bi-x-lg"></i> Rad etish</button>
    </form>
    @endif
    <a href="{{ route('panel.books.edit', $book) }}" class="btn-p primary"><i class="bi bi-pencil"></i> Tahrirlash</a>
  </div>
</div>

<div class="row g-3">

  {{-- ── Rasm + asosiy ──────────────── --}}
  <div class="col-xl-4 fade-up d1">
    <div class="p-card mb-3">
      {{-- Rasmlar --}}
      @php $imgs = is_array($book->images) ? $book->images : []; @endphp
      @if(count($imgs))
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:6px;margin-bottom:14px">
        @foreach($imgs as $img)
        <div style="border-radius:8px;overflow:hidden;aspect-ratio:2/3;background:var(--p-elevated)">
          <img src="{{ asset('storage/' . $img) }}" style="width:100%;height:100%;object-fit:cover" alt="Photo">
        </div>
        @endforeach
      </div>
      @endif

      {{-- Holat badges --}}
      <div class="d-flex flex-wrap gap-2 mb-3">
        @if($book->is_approved == 1)
          <span class="s-pill success">✓ Tasdiqlangan</span>
        @elseif($book->is_approved == 2)
          <span class="s-pill danger">✗ Rad etilgan</span>
        @else
          <span class="s-pill warning">⟳ Kutilmoqda</span>
        @endif

        @if($book->status)
          <span class="s-pill accent">Ko'rinadi</span>
        @else
          <span class="s-pill muted">Ko'rinmaydi</span>
        @endif

        @if($book->is_hidden)
          <span class="s-pill danger">Yashirin</span>
        @endif
      </div>

      {{-- Ma'lumotlar --}}
      @php
        $info = [
          ['label'=>'Kategoriya','value'=>$book->category?->name_uz ?? '—'],
          ['label'=>'Sotuvchi','value'=>$book->seller?->shop_name ?? '—'],
          ['label'=>'Til','value'=>$book->lang ?? '—'],
          ['label'=>'Yozuv','value'=>$book->langType ?? '—'],
          ['label'=>'Muqova','value'=>$book->coverType ?? '—'],
          ['label'=>'Yili','value'=>$book->year ?? '—'],
          ['label'=>'Sahifalar','value'=>($book->pages ?? '—').' bet'],
          ['label'=>'Zaxira','value'=>($book->count ?? 0).' dona'],
        ];
      @endphp
      @foreach($info as $row)
      <div class="d-flex justify-content-between align-items-center mb-2"
           style="padding:7px 0;border-bottom:1px solid var(--p-border)">
        <span style="font-size:12px;color:var(--p-hint)">{{ $row['label'] }}</span>
        <span style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $row['value'] }}</span>
      </div>
      @endforeach
    </div>
  </div>

  {{-- ── O'ng ustun ──────────────────── --}}
  <div class="col-xl-8">

    {{-- Narx --}}
    <div class="row g-3 mb-3">
      <div class="col-md-4 fade-up d1">
        <div class="p-card text-center">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:4px">Asosiy narx</div>
          <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-text)">
            {{ number_format($book->price) }}
          </div>
          <div style="font-size:11px;color:var(--p-hint)">UZS</div>
        </div>
      </div>
      <div class="col-md-4 fade-up d2">
        <div class="p-card text-center">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:4px">Chegirma narxi</div>
          <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-success)">
            {{ number_format($book->discountPrice ?? 0) }}
          </div>
          <div style="font-size:11px;color:var(--p-hint)">UZS</div>
        </div>
      </div>
      <div class="col-md-4 fade-up d3">
        <div class="p-card text-center">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:4px">Chegirma %</div>
          <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-warning)">
            {{ $book->discount_percent ?? 0 }}%
          </div>
        </div>
      </div>
    </div>

    {{-- Tavsif --}}
    @if($book->description)
    <div class="p-card mb-3 fade-up d2">
      <div class="p-card-title mb-2">Kitob haqida</div>
      <div style="font-size:13px;color:var(--p-muted);line-height:1.7">{{ $book->description }}</div>
    </div>
    @endif

    {{-- Teglar --}}
    @if($book->tags->count())
    <div class="p-card mb-3 fade-up d3">
      <div class="p-card-title mb-2">Teglar</div>
      <div class="d-flex flex-wrap gap-2">
        @foreach($book->tags as $tag)
          <span class="s-pill accent" style="font-size:12px">{{ $tag->tag_name_uz ?? $tag->name }}</span>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Statistika --}}
    <div class="p-card fade-up d3">
      <div class="p-card-title mb-3">Savdo statistikasi</div>
      <div class="row g-3">
        @php
          $stats = [
            ['label'=>'Jami sotildi','value'=>number_format($book->totalSales ?? 0).' ta','color'=>'var(--p-accent)'],
            ['label'=>'Jami daromad','value'=>number_format($book->totalRevenue ?? 0).' UZS','color'=>'var(--p-success)'],
            ['label'=>'Jami mijozlar','value'=>number_format($book->totalClients ?? 0).' ta','color'=>'var(--p-info)'],
            ['label'=>'Bu hafta','value'=>number_format($book->totalSalesWeek ?? 0).' ta','color'=>'var(--p-warning)'],
          ];
        @endphp
        @foreach($stats as $s)
        <div class="col-6 col-md-3">
          <div style="text-align:center;padding:12px;background:var(--p-elevated);border-radius:8px">
            <div style="font-size:15px;font-weight:700;font-family:'JetBrains Mono',monospace;color:{{ $s['color'] }}">{{ $s['value'] }}</div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:3px">{{ $s['label'] }}</div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

@endsection