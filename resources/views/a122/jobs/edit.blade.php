@extends('a122.layouts.admin')
@section('title', isset($vacancy) ? 'Vakansiya tahriri' : 'Yangi vakansiya')

@section('content')
<x-a122.page-header back-href="{{ route('admin.jobs.index') }}">
    <x-slot name="heading">{{ $vacancy->title ?? 'Yangi vakansiya' }}</x-slot>
    <x-slot name="meta">{{ isset($vacancy) ? 'Vakansiyani tahrirlash va ko‘rinishini boshqarish' : 'Marketplace jamoasi uchun yangi pozitsiya ochish' }}</x-slot>
</x-a122.page-header>

<form method="POST" action="{{ isset($vacancy) ? route('admin.jobs.update', $vacancy) : route('admin.jobs.store') }}">
    @csrf
    @isset($vacancy)
    @method('PUT')
    @endisset
    @include('a122.jobs._form', ['vacancy' => $vacancy ?? null])
</form>
@endsection
