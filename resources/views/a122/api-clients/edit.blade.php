@extends('a122.layouts.admin')
@section('title', 'Tahrirlash: '.$apiClient->name)
@section('page-title', 'API mijoz tahrirlash')

@section('content')
<div class="space-y-4">
  <x-a122.page-header back-href="{{ route('admin.api-clients.index') }}">
    <x-slot name="heading">{{ $apiClient->name }}</x-slot>
    <x-slot name="meta">Mijoz holati, huquqlari va secret boshqaruvi shu sahifada.</x-slot>
  </x-a122.page-header>

  <form method="POST" action="{{ route('admin.api-clients.update', $apiClient) }}">
    @csrf
    @method('PUT')
    @include('a122.api-clients._form', compact('apiClient'))
  </form>
</div>
@endsection
