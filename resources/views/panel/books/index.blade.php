@extends('panel.layouts.panel')

@section('title','Kitoblar')
@section('page-title','Kitoblar')
@section('breadcrumb','Panel / Kitoblar')

@section('content')

<x-panel.page-header>
  <x-slot name="heading">Kitoblar</x-slot>
  <x-slot name="meta">Barcha kitoblar moderatsiyasi va boshqaruvi</x-slot>
  <x-slot name="actions">
    <div class="flex gap-2">
        <a href="{{ route('panel.books.import') }}" class="btn-p ghost"><i class="bi bi-upload"></i> Import</a>
        <a href="{{ route('panel.books.export', request()->all()) }}" class="btn-p ghost"><i class="bi bi-download"></i> Export</a>
      </div>
  </x-slot>
</x-panel.page-header>


{{-- ── Moderatsiya tablari ─────────────── --}}
<div class="tab-pills fade-up">
  @foreach(['pending'=>'Kutilmoqda','approved'=>'Tasdiqlangan','rejected'=>'Rad etilgan','all'=>'Barchasi'] as $key => $label)
  <a href="{{ route('panel.books.index', array_merge(request()->except('tab','page'), ['tab'=>$key])) }}"
     class="tab-pill {{ $tab === $key ? 'active' : '' }}">
    {{ $label }}
    <span class="tab-count">
      {{ $key === 'all' ? array_sum($counts) : ($counts[$key] ?? 0) }}
    </span>
  </a>
  @endforeach
</div>

{{-- ── Filter bar ───────────────────────── --}}
<form method="GET" action="{{ route('panel.books.index') }}" id="bookFilter">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="filter-bar fade-up">
    <div class="search-box" style="width:200px;margin-left:0">
      <i class="bi bi-search"></i>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, muallif, ID..."/>
    </div>
    <select name="category_id" class="p-form-control" style="width:160px" onchange="bookFilter.submit()">
      <option value="">Kategoriya</option>
      @foreach($categories as $cat)
        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected':'' }}>{{ $cat->name_uz }}</option>
      @endforeach
    </select>
    <select name="seller_id" class="p-form-control" style="width:160px" onchange="bookFilter.submit()">
      <option value="">Sotuvchi</option>
      @foreach($sellers as $s)
        <option value="{{ $s->id }}" {{ request('seller_id') == $s->id ? 'selected':'' }}>{{ $s->shop_name }}</option>
      @endforeach
    </select>
    <select name="status" class="p-form-control" style="width:130px" onchange="bookFilter.submit()">
      <option value="">Ko'rinish</option>
      <option value="1" {{ request('status')==='1'?'selected':'' }}>Ko'rinadigan</option>
      <option value="0" {{ request('status')==='0'?'selected':'' }}>Yashirin</option>
    </select>
    <button type="submit" class="btn-p primary"><i class="bi bi-funnel"></i> Filter</button>
    @if(request()->hasAny(['search','category_id','seller_id','status']))
    <a href="{{ route('panel.books.index',['tab'=>$tab]) }}" class="btn-p ghost"><i class="bi bi-x"></i> Tozalash</a>
    @endif
  </div>
</form>

{{-- ── Table ────────────────────────────── --}}
<div class="p-card fade-up">
  <div class="p-card-header">
    <div>
      <div class="p-card-title">Kitoblar ro'yxati</div>
      <div class="p-card-sub">{{ $books->total() }} ta natija</div>
    </div>
  </div>
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Kitob</th>
          <th>Muallif</th>
          <th>Sotuvchi</th>
          <th>Narx</th>
          <th>Zaxira</th>
          <th>Moderatsiya</th>
          <th>Holat</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($books as $book)
        <tr>
          <td><span style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">#{{ $book->id }}</span></td>
          <td>
            <div class="flex items-center gap-2">
              @php $img = is_array($book->images) ? ($book->images[0] ?? null) : null; @endphp
              <div style="width:38px;height:52px;border-radius:6px;overflow:hidden;background:var(--p-elevated);flex-shrink:0">
                @if($img)
                  <img src="{{ asset('storage/' . $img) }}" style="width:100%;height:100%;object-fit:cover" alt="">
                @else
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-book" style="color:var(--p-hint)"></i>
                  </div>
                @endif
              </div>
              <div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text);max-width:160px" class="text-truncate">{{ $book->name }}</div>
                <div style="font-size:11px;color:var(--p-hint)">{{ $book->category?->name_uz ?? '—' }}</div>
              </div>
            </div>
          </td>
          <td style="font-size:13px;color:var(--p-muted)">{{ $book->author }}</td>
          <td>
            <div style="font-size:12px;color:var(--p-text)">{{ $book->seller?->shop_name ?? '—' }}</div>
          </td>
          <td>
            <div style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:500;color:var(--p-text)">{{ number_format($book->price) }}</div>
            @if($book->discountPrice > 0)
            <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--p-success)">-{{ number_format($book->discountPrice) }}</div>
            @endif
          </td>
          <td>
            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;color:var(--p-text)">{{ $book->count }}</span>
            <span style="font-size:11px;color:var(--p-hint)"> dona</span>
          </td>
          <td>
            @if($book->is_approved == 1)
              <span class="s-pill success">✓ Tasdiqlangan</span>
            @elseif($book->is_approved == 2)
              <span class="s-pill danger">✗ Rad etilgan</span>
            @else
              <span class="s-pill warning">⟳ Kutilmoqda</span>
            @endif
          </td>
          <td>
            @if($book->is_hidden)
              <span class="s-pill danger">Yashirin</span>
            @elseif($book->status)
              <span class="s-pill success">Ko'rinadi</span>
            @else
              <span class="s-pill muted">O'chirilgan</span>
            @endif
          </td>
          <td>
            <div class="flex gap-1">
              {{-- Tez moderatsiya --}}
              @if($book->is_approved != 1)
              <form method="POST" action="{{ route('panel.books.moderate', $book) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="is_approved" value="1">
                <button class="btn-p success sm" title="Tasdiqlash"><i class="bi bi-check-lg"></i></button>
              </form>
              @endif
              @if($book->is_approved != 2)
              <form method="POST" action="{{ route('panel.books.moderate', $book) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="is_approved" value="2">
                <button class="btn-p danger sm" title="Rad etish"><i class="bi bi-x-lg"></i></button>
              </form>
              @endif
              <a href="{{ route('panel.books.show', $book) }}" class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              <a href="{{ route('panel.books.edit', $book) }}" class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-book" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Kitoblar topilmadi
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($books->hasPages())
  <div class="flex items-center justify-between mt-3" style="padding-top:12px;border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">{{ $books->firstItem() }}–{{ $books->lastItem() }} / {{ $books->total() }}</div>
    <div class="p-pagination">
      @if($books->onFirstPage())
        <span class="p-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
      @else
        <a href="{{ $books->previousPageUrl() }}" class="p-page-btn"><i class="bi bi-chevron-left"></i></a>
      @endif
      @foreach($books->getUrlRange(max(1,$books->currentPage()-2), min($books->lastPage(),$books->currentPage()+2)) as $page => $url)
        <a href="{{ $url }}" class="p-page-btn {{ $page===$books->currentPage()?'active':'' }}">{{ $page }}</a>
      @endforeach
      @if($books->hasMorePages())
        <a href="{{ $books->nextPageUrl() }}" class="p-page-btn"><i class="bi bi-chevron-right"></i></a>
      @else
        <span class="p-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
      @endif
    </div>
  </div>
  @endif
</div>

@endsection