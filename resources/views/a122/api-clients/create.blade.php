@extends('a122.layouts.admin')
@section('title', 'Yangi API mijoz')
@section('page-title', 'Yangi API mijoz')

@section('content')
<div class="space-y-4">
  <x-a122.page-header back-href="{{ route('admin.api-clients.index') }}">
    <x-slot name="heading">Yangi API mijoz</x-slot>
    <x-slot name="meta">Integratsiya uchun yangi App ID va Secret yaratiladi. Faol huquqlarni oldindan belgilab qo'ying.</x-slot>
  </x-a122.page-header>

  <form method="POST" action="{{ route('admin.api-clients.store') }}">
    @csrf
    @include('a122.api-clients._form', ['apiClient' => null])
  </form>
</div>
@endsection
