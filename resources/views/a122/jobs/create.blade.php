@extends('a122.layouts.admin')
@section('title', 'Yangi vakansiya')

@section('content')
<x-a122.page-header back-href="{{ route('admin.jobs.index') }}">
    <x-slot name="heading">Yangi vakansiya</x-slot>
    <x-slot name="meta">Marketplace jamoasi uchun yangi pozitsiya ochish</x-slot>
</x-a122.page-header>

<form method="POST" action="{{ route('admin.jobs.store') }}">
    @csrf
    @include('a122.jobs._form', ['vacancy' => null])
</form>
@endsection
