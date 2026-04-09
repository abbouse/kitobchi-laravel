@extends('panel.layouts.panel')
@section('title', 'Yangi Reel')
@section('page-title', 'Yangi Reel')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="{{ route('panel.reels.index') }}" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h1 class="page-title">Yangi Reel yaratish</h1>
</div>

<div class="row justify-content-center">
  <div class="col-xl-8">
    <form method="POST" action="{{ route('panel.reels.store') }}">
      @csrf
      @include('panel.reels._form', ['reel' => null])
    </form>
  </div>
</div>

@endsection