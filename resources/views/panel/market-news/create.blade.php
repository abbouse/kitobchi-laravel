{{-- resources/views/panel/market-news/create.blade.php --}}
@extends('panel.layouts.panel')
@section('title', 'Yangi yangilik')
@section('page-title', 'Yangi yangilik')

@section('content')
<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="{{ route('panel.market-news.index') }}" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h1 class="page-title">Yangi yangilik yaratish</h1>
</div>

<form method="POST" action="{{ route('panel.market-news.store') }}"
      enctype="multipart/form-data">
  @csrf
  @include('panel.market-news._form', ['marketNews' => null])
</form>
@endsection