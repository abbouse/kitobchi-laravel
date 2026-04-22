@extends('a122.layouts.admin')
@section('title', 'Kanstovar tahriri')
@section('page-title', 'Kanstovar tahriri')

@section('content')
<x-a122.page-header back-href="{{ route('admin.stationery.show', $item->id) }}">
  <x-slot name="heading">{{ $item->name }}</x-slot>
  <x-slot name="meta">Narx, ombor va moderatsiya sozlamalarini yangilash</x-slot>
</x-a122.page-header>

@include('a122.stationery.form', ['item' => $item, 'categories' => $categories, 'action' => route('admin.stationery.update', $item->id), 'method' => 'PUT'])
@endsection
