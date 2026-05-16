@extends('a122.layouts.admin')
@section('title', 'Foydalanuvchini tahrirlash')
@section('page-title', 'Tahrirlash')

@section('content')
<x-admin.page-header
  eyebrow="User management"
  title="{{ data_get($user,'full_name') }}"
  subtitle="Profil, aloqa, daraja va account holatini yangilang. O‘zgarishlar darhol foydalanuvchi profiliga ta’sir qiladi.">
  <a href="{{ route('admin.users.show', data_get($user,'id')) }}" class="btn btn-outline-secondary rounded-pill px-4">
    <i class="bi bi-arrow-left me-2"></i>Profilga qaytish
  </a>
</x-admin.page-header>
@include('a122.users._form', ['user' => $user, 'action' => route('admin.users.update', data_get($user,'id')), 'method' => 'PUT'])
@endsection
