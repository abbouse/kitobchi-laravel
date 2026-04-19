{{-- resources/views/panel/market-news/create.blade.php --}}
@extends('panel.layouts.panel')
@section('title', 'Yangi yangilik')
@section('page-title', 'Yangi yangilik')

@section('content')
<x-panel.page-header back-href="{{ route('panel.market-news.index') }}">
  <x-slot name="heading">Yangi yangilik yaratish</x-slot>
</x-panel.page-header>

<form method="POST" action="{{ route('panel.market-news.store') }}"
      enctype="multipart/form-data">
  @csrf
  @include('panel.market-news._form', ['marketNews' => null])
</form>
@endsection