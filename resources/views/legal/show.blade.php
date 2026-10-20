@extends('legal.layouts.app')

@php
    $__seoTitle = $policy->localizedTitle().__('legal.show.seo_suffix');
    $__seoDesc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($policy->localizedContent()))), 160, '…');
    $__inLang = config('landing_locales.bcp47.'.app()->getLocale(), 'uz-UZ');
@endphp

@section('title', $__seoTitle)

@push('meta')
    @include('partials.seo-social', [
        'title' => $__seoTitle,
        'description' => $__seoDesc,
        'canonical' => route('legal.policy', $policy->slug),
        'ogType' => 'article',
        'articleModified' => $policy->updated_at?->toIso8601String(),
    ])
    @php
        $ld = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    '@id' => route('legal.policy', $policy->slug).'#webpage',
                    'url' => route('legal.policy', $policy->slug),
                    'name' => $__seoTitle,
                    'description' => $__seoDesc,
                    'inLanguage' => $__inLang,
                    'dateModified' => $policy->updated_at?->toIso8601String(),
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => __('legal.show.breadcrumb_home'),
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => __('legal.show.breadcrumb_legal'),
                            'item' => route('legal.index'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $policy->localizedTitle(),
                            'item' => route('legal.policy', $policy->slug),
                        ],
                    ],
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
        <a href="{{ route('legal.index') }}">{{ __('legal.show.breadcrumb_legal') }}</a>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
        <span>{{ $policy->localizedTitle() }}</span>
    </nav>

    <article class="kl-card">
        <h1 class="kl-title">{{ $policy->localizedTitle() }}</h1>
        <p class="kl-updated">{{ __('legal.show.updated') }} {{ $policy->updated_at->format('d.m.Y') }}</p>

        <div class="kl-prose">
            {!! $policy->localizedContent() !!}
        </div>

        <div class="kl-actions">
            <a href="{{ route('legal.index') }}" class="kl-btn kl-btn--primary">{{ __('legal.show.all_docs') }}</a>
            <a href="#" class="kl-btn" onclick="window.print(); return false;">{{ __('legal.show.print') }}</a>
        </div>
    </article>

    @php $otherPolicies = $policies->where('id', '!=', $policy->id)->take(4); @endphp
    @if($otherPolicies->isNotEmpty())
        <h2 class="kl-more-title">{{ __('legal.show.other_heading') }}</h2>
        <div class="kl-list">
            @foreach($otherPolicies as $other)
                <a href="{{ route('legal.policy', $other->slug) }}" class="kl-row">
                    <span class="kl-row__title">{{ $other->localizedTitle() }}</span>
                    <span class="kl-row__meta">{{ $other->updated_at->format('d.m.Y') }}</span>
                    <svg class="kl-row__chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @endforeach
        </div>
    @endif
@endsection
