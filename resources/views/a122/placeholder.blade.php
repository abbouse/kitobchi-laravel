@extends('a122.layouts.admin')
@section('title', $title ?? 'Sahifa')
@section('page-title', $title ?? 'Sahifa')

@section('content')
<div class="card-panel p-5 text-center mx-auto" style="max-width:640px;">
  <div class="d-inline-grid place-items-center rounded-3 text-white mb-4"
       style="width:64px;height:64px;background:linear-gradient(135deg,#4f46e5,#7c3aed);">
    <i class="bi bi-grid-1x2-fill fs-3"></i>
  </div>
  <h1 class="page-title mb-2">{{ $title ?? 'Sahifa' }}</h1>
  <p class="page-subtitle mx-auto" style="max-width:420px;">Ushbu modul uchun boshqaruv oynasi tayyorlanmoqda.</p>
  <a href="{{ route('admin.dashboard') }}" class="btn-primary-gradient d-inline-flex align-items-center gap-2 mt-4 text-decoration-none">
    <i class="bi bi-arrow-left"></i>
    Dashboard
  </a>
</div>
@endsection
