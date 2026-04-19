@extends('panel.layouts.panel')
@section('title', 'Yangi vakansiya')
@section('page-title', 'Yangi vakansiya')

@section('content')
<x-panel.page-header back-href="{{ route('panel.vacancies.index') }}">
    <x-slot name="heading">Yangi vakansiya</x-slot>
</x-panel.page-header>

<form method="POST" action="{{ route('panel.vacancies.store') }}">
    @csrf
    @include('panel.vacancies._form', ['vacancy' => null])
</form>
@endsection
