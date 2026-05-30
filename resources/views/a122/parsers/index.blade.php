@extends('a122.layouts.admin')
@section('title', 'Parserlar')
@section('page-title', 'Parserlar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header
    eyebrow="Catalog ingestion"
    title="Tashqi katalog parserlari"
    subtitle="Tashqi sayt kataloglarini yig‘ish, stock holatini ko‘rish va sellerga import qilish oynasi."
  >
    <a href="{{ route('admin.books.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-book me-2"></i>Kitoblar
    </a>
  </x-admin.page-header>

  <div class="row g-4">
    @foreach($providers as $provider)
      <div class="col-12 col-xl-6">
        <article class="card-panel h-100 p-4">
          <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
            <div class="min-w-0">
              <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="chip chip-purple">Provider</span>
                <span class="chip chip-gray">Seller #{{ $provider['seller_id'] }}</span>
              </div>
              <h2 class="h5 fw-bold mb-1 text-truncate">{{ $provider['label'] }}</h2>
              <p class="text-secondary mb-0">{{ $provider['subtitle'] }}</p>
            </div>
            <div class="d-inline-grid place-items-center rounded-3 fw-bold text-white flex-shrink-0"
                 style="width:52px;height:52px;background:linear-gradient(135deg,#4f46e5,#7c3aed);letter-spacing:.08em;">
              BU
            </div>
          </div>

          <div class="row g-3 mb-4">
            @foreach([
              ['Jami', $provider['total'], 'chip-info'],
              ['Stock bor', $provider['in_stock'], 'chip-success'],
              ['Stock yo‘q', $provider['out_of_stock'], 'chip-warning'],
              ['Import', $provider['imported'], 'chip-purple'],
            ] as [$label, $value, $chip])
              <div class="col-6 col-md-3">
                <div class="rounded-3 border p-3 h-100" style="border-color:var(--template-border)!important;background:color-mix(in srgb,var(--template-card) 82%,var(--template-bg));">
                  <div class="small text-secondary text-uppercase fw-bold mb-2" style="letter-spacing:.08em;">{{ $label }}</div>
                  <div class="h4 fw-bold mb-0">{{ number_format($value) }}</div>
                </div>
              </div>
            @endforeach
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <span class="small text-secondary">Katalogni ko‘rish, yangilash va bazaga qo‘shish</span>
            <a href="{{ $provider['route'] }}" class="btn-primary-gradient text-decoration-none">
              Ochish <i class="bi bi-arrow-up-right ms-2"></i>
            </a>
          </div>
        </article>
      </div>
    @endforeach
  </div>
</div>
@endsection
