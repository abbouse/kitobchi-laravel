@extends('a122.layouts.admin')
@section('title', 'Yangi kitob')
@section('page-title', 'Yangi kitob')

@section('content')
<div class="space-y-6">
  <x-a122.page-header back-href="{{ route('admin.books.index') }}">
    <x-slot name="heading">Yangi kitob qo‘shish</x-slot>
    <x-slot name="meta">Marketplace katalogiga yangi mahsulot joylashtirish</x-slot>
  </x-a122.page-header>

  @include('a122.books._form', [
    'book' => null,
    'categories' => $categories,
    'publishers' => $publishers,
    'sellers' => $sellers,
    'action' => route('admin.books.store'),
    'method' => 'POST',
    'cancelHref' => route('admin.books.index'),
  ])
</div>
@endsection
