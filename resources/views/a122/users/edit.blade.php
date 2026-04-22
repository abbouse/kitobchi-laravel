@extends('a122.layouts.admin')
@section('title', 'Foydalanuvchini tahrirlash')
@section('page-title', 'Tahrirlash')

@section('content')
<x-a122.page-header back-href="{{ route('admin.users.show', data_get($user,'id')) }}">
  <x-slot name="heading">{{ data_get($user,'full_name') }}</x-slot>
  <x-slot name="meta">Profil, aloqa va account holatini yangilash</x-slot>
</x-a122.page-header>
@include('a122.users._form', ['user' => $user, 'action' => route('admin.users.update', data_get($user,'id')), 'method' => 'PUT'])
@endsection
