@extends('legal.layouts.app')

@php
    $__seoTitle = __('legal.index.seo_title');
    $__seoDesc = __('legal.index.seo_desc');
    $__inLang = config('landing_locales.bcp47.'.app()->getLocale(), 'uz-UZ');
@endphp

@section('title', $__seoTitle)

@push('meta')
    @include('partials.seo-social', [
        'title' => $__seoTitle,
        'description' => $__seoDesc,
        'canonical' => route('legal.index'),
    ])
    @php
        $items = [];
        $pos = 1;
        foreach ($policies as $p) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => $p->localizedTitle(),
                'url' => route('legal.policy', $p->slug),
            ];
        }
        $ld = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    '@id' => route('legal.index').'#webpage',
                    'url' => route('legal.index'),
                    'name' => $__seoTitle,
                    'description' => $__seoDesc,
                    'inLanguage' => $__inLang,
                ],
                [
                    '@type' => 'ItemList',
                    'name' => __('legal.index.ld_list_name'),
                    'numberOfItems' => count($items),
                    'itemListElement' => $items,
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    <nav class="kl-crumbs" aria-label="Breadcrumb">
        <a href="{{ url('/') }}">{{ __('legal.show.breadcrumb_home') }}</a>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
        <span>{{ __('legal.index.heading') }}</span>
    </nav>

    <div class="kl-head">
        <h1 class="kl-title">{{ __('legal.index.heading') }}</h1>
        <p class="kl-intro">{{ __('legal.index.intro') }}</p>
    </div>

    @if($policies->isEmpty())
        <div class="kl-card kl-empty">{{ __('legal.index.empty') }}</div>
    @else
        <div class="kl-list">
            @foreach($policies as $policy)
                <a href="{{ route('legal.policy', $policy->slug) }}" class="kl-row">
                    <span class="kl-row__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                    </span>
                    <span class="kl-row__main">
                        <span class="kl-row__title">{{ $policy->localizedTitle() }}</span>
                        <span class="kl-row__meta">{{ __('legal.index.updated') }} {{ $policy->updated_at->format('d.m.Y') }}</span>
                    </span>
                    <svg class="kl-row__chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @endforeach
        </div>
    @endif
@endsection
