@extends('panel.layouts.panel')
@section('title', 'Vakansiya tahriri')
@section('page-title', 'Vakansiya tahriri')

@section('content')
<x-panel.page-header back-href="{{ route('panel.vacancies.index') }}">
    <x-slot name="heading">{{ $vacancy->title }}</x-slot>
</x-panel.page-header>

<form method="POST" action="{{ route('panel.vacancies.update', $vacancy) }}">
    @csrf
    @method('PUT')
    @include('panel.vacancies._form', ['vacancy' => $vacancy])
</form>
@endsection
