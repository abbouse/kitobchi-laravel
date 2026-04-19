{{-- resources/views/panel/api-clients/create.blade.php --}}
@extends('panel.layouts.panel')
@section('title', 'Yangi API mijoz')
@section('page-title', 'Yangi API mijoz')

@section('content')
<div class="kc-page-inner w-full min-w-0">
    <x-panel.page-header back-href="{{ route('panel.api-clients.index') }}">
      <x-slot name="heading">Yangi API mijoz</x-slot>
    </x-panel.page-header>

    <form method="POST" action="{{ route('panel.api-clients.store') }}">
      @csrf
      @include('panel.api-clients._form', ['apiClient' => null])
    </form>
</div>
@endsection