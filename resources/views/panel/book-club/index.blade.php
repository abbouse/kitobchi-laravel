@extends('panel.layouts.panel')
@section('title', 'Book Club')
@section('page-title', 'Book Club postlari')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3 fade-up">
  <div class="tab-pills">
    @foreach(['all'=>'Barchasi','posts'=>'Postlar','reposts'=>'Repostlar'] as $k=>$l)
    <a href="{{ request()->fullUrlWithQuery(['tab'=>$k,'page'=>1]) }}"
       class="tab-pill {{ $tab===$k?'active':'' }}">
      {{ $l }} <span class="tab-badge">{{ $counts[$k] }}</span>
    </a>
    @endforeach
  </div>
</div>

<div class="filter-bar mb-3 fade-up">
  <form method="GET" class="d-flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="search-box" style="width:240px;margin-left:0">
      <i class="bi bi-search"></i>
      <input type="search" name="search"
             placeholder="Matn, foydalanuvchi..."
             value="{{ request('search') }}">
    </div>
    <select name="product_type" class="p-form-control" style="width:150px">
      <option value="">Barcha tur</option>
      <option value="book"       {{ request('product_type')==='book'?'selected':'' }}>📚 Kitob</option>
      <option value="stationery" {{ request('product_type')==='stationery'?'selected':'' }}>✏️ Kanstovar</option>
    </select>
    <button class="btn-p primary" type="submit">
      <i class="bi bi-funnel"></i> Filter
    </button>
    @if(request('search') || request('product_type'))
    <a href="{{ route('panel.book-club.index',['tab'=>$tab]) }}" class="btn-p ghost">
      <i class="bi bi-x"></i> Tozalash
    </a>
    @endif
  </form>
</div>

{{-- 2 ustunli grid: xl dan boshlab 2 ustun, kichikda 1 ustun --}}
@forelse($posts as $post)
  @if($loop->first)<div class="row g-3">@endif

  <div class="col-12 col-xl-6 fade-up">
    @include('panel.book-club._post-card', ['post' => $post, 'showUser' => true])
  </div>

  @if($loop->last)</div>@endif
@empty
<div class="p-card fade-up" style="text-align:center;padding:50px;color:var(--p-hint)">
  <i class="bi bi-chat-square-text" style="font-size:36px;display:block;margin-bottom:12px"></i>
  Postlar topilmadi
</div>
@endforelse

<div class="mt-3">
  {{ $posts->links('panel.partials.pagination') }}
</div>

@endsection