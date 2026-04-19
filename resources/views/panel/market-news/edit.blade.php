@extends('panel.layouts.panel')
@section('title', 'Tahrirlash: '.$marketNews->title)
@section('page-title', 'Yangilik tahrirlash')

@section('content')
<x-panel.page-header back-href="{{ route('panel.market-news.show', $marketNews) }}">
  <x-slot name="heading">{{ $marketNews->title }}</x-slot>
  <x-slot name="meta">Yangilikni tahrirlash</x-slot>
</x-panel.page-header>


<form method="POST" action="{{ route('panel.market-news.update', $marketNews) }}"
      enctype="multipart/form-data">
  @csrf @method('PUT')
  @include('panel.market-news._form', compact('marketNews'))
</form>
@endsection