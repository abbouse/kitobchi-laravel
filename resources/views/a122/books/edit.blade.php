@extends('a122.layouts.admin')
@section('title', 'Kitob tahriri')
@section('page-title', 'Kitob tahriri')

@section('content')
<div class="space-y-6">
  <x-a122.page-header back-href="{{ route('admin.books.show', $book) }}">
    <x-slot name="heading">{{ $book->name }}</x-slot>
    <x-slot name="meta">Katalog kartasi, narx va moderatsiya sozlamalari</x-slot>
  </x-a122.page-header>

  @include('a122.books._form', [
    'book' => $book,
    'categories' => $categories,
    'publishers' => $publishers,
    'sellers' => $sellers,
    'action' => route('admin.books.update', $book),
    'method' => 'PUT',
    'cancelHref' => route('admin.books.show', $book),
  ])
</div>
@endsection
