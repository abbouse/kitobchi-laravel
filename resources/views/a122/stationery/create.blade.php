@extends('a122.layouts.admin')
@section('title', 'Yangi kanstovar')
@section('page-title', 'Yangi kanstovar')

@section('content')
<x-a122.page-header back-href="{{ route('admin.stationery.index') }}">
  <x-slot name="heading">Yangi kanstovar qo‘shish</x-slot>
  <x-slot name="meta">Marketplace stationery katalogiga mahsulot qo‘shish</x-slot>
</x-a122.page-header>

@include('a122.stationery.form', ['item' => null, 'categories' => $categories, 'action' => route('admin.stationery.store'), 'method' => 'POST'])
@endsection
