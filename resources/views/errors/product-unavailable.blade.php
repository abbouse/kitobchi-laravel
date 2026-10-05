@extends('layouts.error-public')

@section('title', __('errors.product_unavailable_title').' — '.__('errors.meta_title'))

@push('meta')
    <meta name="robots" content="noindex">
@endpush

@push('head')
    <style>
        .kc-error-code { display:inline-flex; align-items:center; gap:6px; margin-top:18px; padding:6px 12px; border-radius:999px; background:rgba(15,23,42,.06); color:#64748b; font:600 12px/1 ui-monospace,SFMono-Regular,Menlo,monospace; letter-spacing:.4px; user-select:all; }
    </style>
@endpush

@section('content')
    <div class="kc-error-card">
        <div class="kc-error-pill-wrap">
            <div class="eyebrow-pill">
                <div class="eyebrow-pill-inner"><div>📚</div></div>
                <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
            </div>
        </div>
        <h1 class="section-heading kc-error-heading">{{ __('errors.product_unavailable_title') }}</h1>
        <p class="subheading kc-error-lead">{{ __('errors.product_unavailable_lead') }}</p>
        <div class="kc-error-actions">
            <a href="{{ url('/catalog') }}" class="cta w-inline-block">
                <div class="cta-bg u-rainbow u-blur-perf"></div>
                <div class="cta-inner">
                    <div><strong>{{ __('errors.cta_catalog') }}</strong></div>
                </div>
            </a>
            <button type="button" class="kc-error-secondary" onclick="history.length > 1 ? history.back() : (location.href='{{ url('/') }}')">
                {{ __('errors.cta_back') }}
            </button>
        </div>
        <div class="kc-error-code" title="{{ __('errors.product_unavailable_code_hint') }}">
            {{ __('errors.code') }}: {{ $code }}@if($productId) · #{{ $productId }}@endif
        </div>
    </div>
@endsection
