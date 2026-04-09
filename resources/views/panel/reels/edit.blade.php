@extends('panel.layouts.panel')
@section('title', 'Tahrirlash: '.$reel->title)
@section('page-title', 'Reel tahrirlash')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 fade-up">
  <a href="{{ route('panel.reels.show', $reel) }}" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h1 class="page-title">{{ $reel->title }}</h1>
    <p class="page-sub">Reel ma'lumotlarini tahrirlash</p>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-xl-8">
    <form method="POST" action="{{ route('panel.reels.update', $reel) }}">
      @csrf @method('PUT')
      @include('panel.reels._form', compact('reel'))
    </form>
  </div>
</div>

@endsection