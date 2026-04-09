@extends('panel.layouts.panel')
@section('title', 'Tahrirlash: '.$marketNews->title)
@section('page-title', 'Yangilik tahrirlash')

@section('content')
<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="{{ route('panel.market-news.show', $marketNews) }}" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h1 class="page-title">{{ $marketNews->title }}</h1>
    <p class="page-sub">Yangilikni tahrirlash</p>
  </div>
</div>

<form method="POST" action="{{ route('panel.market-news.update', $marketNews) }}"
      enctype="multipart/form-data">
  @csrf @method('PUT')
  @include('panel.market-news._form', compact('marketNews'))
</form>
@endsection