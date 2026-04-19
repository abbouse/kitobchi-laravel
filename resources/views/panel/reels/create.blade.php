@extends('panel.layouts.panel')
@section('title', 'Yangi Reel')
@section('page-title', 'Yangi Reel')

@section('content')

<x-panel.page-header back-href="{{ route('panel.reels.index') }}">
  <x-slot name="heading">Yangi Reel yaratish</x-slot>
</x-panel.page-header>

<div class="kc-page-inner w-full min-w-0">
    <form method="POST" action="{{ route('panel.reels.store') }}">
      @csrf
      @include('panel.reels._form', ['reel' => null])
    </form>
</div>

@endsection