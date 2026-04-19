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
    <section class="section">
        <div class="page-padding">
            <div class="container">
                <div class="section-header cc-legal">
                    <h1 class="section-heading">{{ $policy->localizedTitle() }}</h1>
                    <p class="subheading">{{ __('legal.show.updated') }} {{ $policy->updated_at->format('d.m.Y') }}</p>
                </div>

                <div class="legal-rt w-richtext">
                    {!! $policy->localizedContent() !!}
                </div>

                <div class="kc-legal-actions">
                    <a href="{{ route('legal.index') }}" class="cta w-inline-block">
                        <div class="cta-bg u-rainbow u-blur-perf cc-dark"></div>
                        <div class="cta-inner cc-dark">
                            <div><strong>{{ __('legal.show.all_docs') }}</strong></div>
                        </div>
                    </a>
                    <a href="#" class="cta w-inline-block" onclick="window.print(); return false;">
                        <div class="cta-bg u-rainbow u-blur-perf"></div>
                        <div class="cta-inner">
                            <div><strong>{{ __('legal.show.print') }}</strong></div>
                        </div>
                    </a>
                </div>

                @php $otherPolicies = $policies->where('id', '!=', $policy->id)->take(4); @endphp
                @if($otherPolicies->isNotEmpty())
                    <div class="section-header cc-legal kc-legal-more-head">
                        <h2 class="heading-m">{{ __('legal.show.other_heading') }}</h2>
                    </div>
                    <div class="kc-legal-index-list">
                        @foreach($otherPolicies as $other)
                            <a href="{{ route('legal.policy', $other->slug) }}" class="kc-legal-index-row w-inline-block">
                                <span class="kc-legal-index-title">{{ $other->localizedTitle() }}</span>
                                <span class="kc-legal-index-meta">{{ $other->updated_at->format('d.m.Y') }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
