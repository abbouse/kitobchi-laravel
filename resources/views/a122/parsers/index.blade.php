@extends('a122.layouts.admin')
@section('title', 'Parserlar')
@section('page-title', 'Parserlar')

@section('content')
<div class="d-flex flex-column gap-4">
  <section class="p-card p-card--hero">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
      <div>
        <span class="p-eyebrow">Catalog ingestion</span>
        <h1 class="p-page-title mb-2">Tashqi katalog parserlari</h1>
        <p class="p-page-subtitle mb-0">Tashqi sayt kataloglarini yig‘ish, stock bilan ko‘rish va sellerga professional tarzda import qilish oynasi.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.books.index') }}" class="btn btn-outline-secondary">
          <i class="bi bi-book me-2"></i>Kitoblar
        </a>
      </div>
    </div>
  </section>

  <div class="row g-4">
    @foreach($providers as $provider)
      <div class="col-12 col-xl-6">
        <a href="{{ $provider['route'] }}" class="text-decoration-none text-reset">
          <article class="p-card h-100 parser-provider-card">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
              <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="badge rounded-pill text-bg-dark">Provider</span>
                  <span class="badge rounded-pill text-bg-light">Seller #{{ $provider['seller_id'] }}</span>
                </div>
                <h2 class="h4 mb-1">{{ $provider['label'] }}</h2>
                <p class="text-secondary mb-0">{{ $provider['subtitle'] }}</p>
              </div>
              <div class="parser-provider-mark">BU</div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6 col-md-3">
                <div class="parser-stat">
                  <div class="parser-stat__label">Jami</div>
                  <div class="parser-stat__value">{{ number_format($provider['total']) }}</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="parser-stat">
                  <div class="parser-stat__label">Stock bor</div>
                  <div class="parser-stat__value">{{ number_format($provider['in_stock']) }}</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="parser-stat">
                  <div class="parser-stat__label">Stock yo‘q</div>
                  <div class="parser-stat__value">{{ number_format($provider['out_of_stock']) }}</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="parser-stat">
                  <div class="parser-stat__label">Import</div>
                  <div class="parser-stat__value">{{ number_format($provider['imported']) }}</div>
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between">
              <span class="text-secondary small">Katalogni ko‘rish, yangilash va bazaga qo‘shish</span>
              <span class="btn btn-dark">
                Ochish <i class="bi bi-arrow-up-right ms-2"></i>
              </span>
            </div>
          </article>
        </a>
      </div>
    @endforeach
  </div>
</div>
@endsection

@push('styles')
<style>
  .parser-provider-card {
    border-radius: 28px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }
  .parser-provider-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.12);
    border-color: rgba(15, 23, 42, 0.14);
  }
  .parser-provider-mark {
    width: 56px;
    height: 56px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 18px;
    background: linear-gradient(135deg, #111827 0%, #374151 100%);
    color: #fff;
    font-weight: 800;
    letter-spacing: .08em;
  }
  .parser-stat {
    padding: 14px 16px;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid rgba(148, 163, 184, 0.18);
  }
  .parser-stat__label {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #64748b;
    margin-bottom: 4px;
  }
  .parser-stat__value {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
  }
</style>
@endpush
