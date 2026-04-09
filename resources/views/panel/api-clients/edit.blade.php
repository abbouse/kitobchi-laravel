@extends('panel.layouts.panel')
@section('title', 'Tahrirlash: '.$apiClient->name)
@section('page-title', 'API mijoz tahrirlash')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-7">

    <div class="d-flex align-items-center gap-3 mb-4 fade-up">
      <a href="{{ route('panel.api-clients.index') }}" class="btn-p ghost icon">
        <i class="bi bi-arrow-left"></i>
      </a>
      <div>
        <h1 class="page-title">{{ $apiClient->name }}</h1>
        <p class="page-sub">API mijoz tahrirlash</p>
      </div>
    </div>

    <form method="POST" action="{{ route('panel.api-clients.update', $apiClient) }}">
      @csrf @method('PUT')
      @include('panel.api-clients._form', compact('apiClient'))
    </form>

  </div>
</div>
@endsection