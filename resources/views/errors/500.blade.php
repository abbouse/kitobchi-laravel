@extends('layouts.error-public')

@section('title', __('errors.500_title').' — '.__('errors.meta_title'))

@section('content')
    <div class="kc-error-card">
        <div class="kc-error-pill-wrap">
            <div class="eyebrow-pill">
                <div class="eyebrow-pill-inner"><div>500</div></div>
                <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
            </div>
        </div>
        <h1 class="section-heading kc-error-heading">{{ __('errors.500_title') }}</h1>
        <p class="subheading kc-error-lead">{{ __('errors.500_lead') }}</p>
        <div class="kc-error-actions">
            <a href="{{ url('/') }}" class="cta w-inline-block">
                <div class="cta-bg u-rainbow u-blur-perf"></div>
                <div class="cta-inner">
                    <div><strong>{{ __('errors.cta_home') }}</strong></div>
                </div>
            </a>
            <button type="button" class="kc-error-secondary" onclick="history.length > 1 ? history.back() : (location.href='{{ url('/') }}')">
                {{ __('errors.cta_back') }}
            </button>
        </div>
    </div>
@endsection
