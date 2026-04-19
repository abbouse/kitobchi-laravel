@extends('panel.layouts.panel')
@section('title', 'Tahrirlash: '.$reel->title)
@section('page-title', 'Reel tahrirlash')

@section('content')

<x-panel.page-header back-href="{{ route('panel.reels.show', $reel) }}">
  <x-slot name="heading">{{ $reel->title }}</x-slot>
  <x-slot name="meta">Reel ma'lumotlarini tahrirlash</x-slot>
</x-panel.page-header>


<div class="kc-page-inner w-full min-w-0">
    <form method="POST" action="{{ route('panel.reels.update', $reel) }}">
      @csrf @method('PUT')
      @include('panel.reels._form', compact('reel'))
    </form>
</div>

@endsection