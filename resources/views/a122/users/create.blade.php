@extends('a122.layouts.admin')
@section('title', 'Yangi foydalanuvchi')
@section('page-title', 'Yangi foydalanuvchi')

@section('content')
<h2 class="text-2xl font-bold tracking-tight mb-6">Yangi foydalanuvchi qo'shish</h2>
@include('a122.users._form', ['user' => null, 'action' => route('admin.users.store'), 'method' => 'POST'])
@endsection
