@extends('panel.layouts.panel')
@section('title', 'Tahrirlash: '.$apiClient->name)
@section('page-title', 'API mijoz tahrirlash')

@section('content')
<div class="kc-page-inner w-full min-w-0">
    <x-panel.page-header back-href="{{ route('panel.api-clients.index') }}">
  <x-slot name="heading">{{ $apiClient->name }}</x-slot>
  <x-slot name="meta">API mijoz tahrirlash</x-slot>
</x-panel.page-header>


    <form method="POST" action="{{ route('panel.api-clients.update', $apiClient) }}">
      @csrf @method('PUT')
      @include('panel.api-clients._form', compact('apiClient'))
    </form>
</div>
@endsection