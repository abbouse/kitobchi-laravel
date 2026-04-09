{{-- resources/views/panel/api-clients/create.blade.php --}}
@extends('panel.layouts.panel')
@section('title', 'Yangi API mijoz')
@section('page-title', 'Yangi API mijoz')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-7">

    <div class="d-flex align-items-center gap-3 mb-4 fade-up">
      <a href="{{ route('panel.api-clients.index') }}" class="btn-p ghost icon">
        <i class="bi bi-arrow-left"></i>
      </a>
      <h1 class="page-title">Yangi API mijoz</h1>
    </div>

    <form method="POST" action="{{ route('panel.api-clients.store') }}">
      @csrf
      @include('panel.api-clients._form', ['apiClient' => null])
    </form>

  </div>
</div>
@endsection