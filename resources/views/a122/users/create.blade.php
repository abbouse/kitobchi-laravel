@extends('a122.layouts.admin')
@section('title', 'Yangi foydalanuvchi')
@section('page-title', 'Yangi foydalanuvchi')

@section('content')
<x-admin.page-header
  eyebrow="User management"
  title="Yangi foydalanuvchi"
  subtitle="Yangi akkaunt yaratish, aloqa ma’lumotlari va moderatsiya rolini bir joydan sozlang." />
@include('a122.users._form', ['user' => null, 'action' => route('admin.users.store'), 'method' => 'POST'])
@endsection
