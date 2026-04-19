@extends('layouts.landing')

@php
    $__seoTitle = __('landing.seo.title');
    $__seoDesc = __('landing.seo.description');
    $__inLang = config('landing_locales.bcp47.'.app()->getLocale(), 'uz-UZ');
@endphp

@section('title', $__seoTitle)

@push('head')
<style>
/* popcorn-html bilan bir xil: mask-size cover (vendor `.coverage-wrap` ustidan) */
#coverage .coverage-wrap {
    -webkit-mask-image: url('{{ asset('images/uz_map.png') }}');
    mask-image: url('{{ asset('images/uz_map.png') }}');
    -webkit-mask-size: cover;
    mask-size: cover;
    -webkit-mask-position: center;
    mask-position: center;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
}
</style>
@endpush

@push('meta')
    @include('partials.seo-social', [
        'title' => $__seoTitle,
        'description' => $__seoDesc,
        'canonical' => url('/'),
    ])
    @php
        $base = rtrim((string) (config('app.url') ?: url('/')), '/');
        $logo = url('/images/logo/logo_blue.png');
        $ld = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => $base.'/#website',
                    'url' => $base.'/',
                    'name' => 'Kitobchi',
                    'description' => $__seoDesc,
                    'inLanguage' => $__inLang,
                    'publisher' => ['@id' => $base.'/#organization'],
                ],
                [
                    '@type' => 'Organization',
                    '@id' => $base.'/#organization',
                    'name' => 'Kitobchi',
                    'url' => $base.'/',
                    'logo' => ['@type' => 'ImageObject', 'url' => $logo],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    @php
        $kcScreenshot = function (string $base): ?string {
            $allowed = ['png', 'webp', 'jpg', 'jpeg'];
            $baseLower = strtolower($base);
            $dirs = [
                [public_path('images/screenshots'), 'images/screenshots'],
                [public_path('screenshots'), 'screenshots'],
                [storage_path('app/public/screenshots'), 'storage/screenshots'],
            ];
            foreach ($dirs as [$dir, $urlPrefix]) {
                if (! is_dir($dir)) {
                    continue;
                }
                foreach (scandir($dir) ?: [] as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $full = $dir . DIRECTORY_SEPARATOR . $file;
                    if (! is_file($full)) {
                        continue;
                    }
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (! in_array($ext, $allowed, true)) {
                        continue;
                    }
                    if (strtolower(pathinfo($file, PATHINFO_FILENAME)) !== $baseLower) {
                        continue;
                    }
                    return asset($urlPrefix . '/' . $file);
                }
            }
            return null;
        };
        $shotHero1 = $kcScreenshot('kitobchi_1');
        $shotHero2 = $kcScreenshot('kitobchi_2');
        $shotBiz = $kcScreenshot('kitobchi_b');
    @endphp
    <header class="hero">
        <div class="page-padding">
            <div class="container">
                <div class="home-hero-header">
                    <div class="eyebrow-pill">
                        <div class="eyebrow-pill-inner">
                            <div><strong>{{ __('landing.hero.eyebrow') }}</strong></div>
                        </div>
                        <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                    </div>
                    <h1 class="home-hero-heading">{{ __('landing.hero.heading_line1') }}<br>{{ __('landing.hero.heading_line2') }}</h1>
                    <div class="home-hero-subheading">
                        <p class="subheading">{{ __('landing.hero.sub') }}</p>
                    </div>
                    <div class="btn-wrapper cc-hero">
                        <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" rel="noopener" class="cta w-inline-block kc-smart-store" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" data-appstore="https://apps.apple.com/uz/app/kitobchi/id6753818078" aria-label="{{ __('landing.hero.download_aria') }}">
                            <div class="cta-bg u-rainbow u-blur-perf"></div>
                            <div class="cta-inner">
                                <div><strong>{{ __('landing.hero.download') }}</strong></div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="home-hero-img-wrap">
                    <img src="{{ asset('vendor/popcorn/images/hero-portal-bg-new.webp') }}" alt="" width="780" height="978" class="home-hero-portal" loading="eager" decoding="async" sizes="(max-width: 479px) 96vw, (max-width: 991px) 95vw, 741px">
                    <div class="home-hero-mockup cc-1">
                        @if($shotHero1)
                            <img src="{{ $shotHero1 }}" alt="{{ __('landing.hero.shot1_alt') }}" class="u-auto-img" width="390" height="844" loading="eager" decoding="async">
                        @else
                            <div class="u-auto-img kc-ph" aria-hidden="true"></div>
                        @endif
                    </div>
                    <div class="home-hero-mockup cc-2">
                        @if($shotHero2)
                            <img src="{{ $shotHero2 }}" alt="{{ __('landing.hero.shot2_alt') }}" class="u-auto-img" width="390" height="844" loading="eager" decoding="async">
                        @else
                            <div class="u-auto-img kc-ph" aria-hidden="true"></div>
                        @endif
                    </div>
                </div>
                <div class="hero-highlights-wrap">
                    <div class="hero-highlight-cell">
                        <div class="icon-wrap" aria-hidden="true">
                            <div class="icon-inner">
                                <img src="{{ asset('vendor/popcorn/images/feature-pin.png') }}" width="32" height="32" alt="" class="icon-img" loading="lazy">
                            </div>
                            <div class="icon-bg u-rainbow u-blur-perf"></div>
                        </div>
                        <h3 class="heading-m u-mb-m">{{ __('landing.hero.highlight1_title') }}</h3>
                        <p class="subheading cc-features">{{ __('landing.hero.highlight1_text') }}</p>
                    </div>
                    <div class="hero-highlight-cell">
                        <div class="icon-wrap" aria-hidden="true">
                            <div class="icon-inner">
                                <div class="svg-embed w-embed">
                                    <svg width="22" height="22" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M4 8.88887L6.66667 11.5555L11.8889 5.33331" stroke="#393737" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="icon-bg u-rainbow u-blur-perf"></div>
                        </div>
                        <h3 class="heading-m u-mb-m">{{ __('landing.hero.highlight2_title') }}</h3>
                        <p class="subheading cc-features">{{ __('landing.hero.highlight2_text') }}</p>
                    </div>
                    <div class="hero-highlight-cell cc-cancel">
                        <div class="icon-wrap" aria-hidden="true">
                            <div class="icon-inner">
                                <img src="{{ asset('vendor/popcorn/images/feature-event.png') }}" width="32" height="32" alt="" class="icon-img" loading="lazy">
                            </div>
                            <div class="icon-bg u-rainbow u-blur-perf"></div>
                        </div>
                        <h3 class="heading-m u-mb-m">{{ __('landing.hero.highlight3_title') }}</h3>
                        <p class="subheading cc-features">{{ __('landing.hero.highlight3_text') }}</p>
                    </div>
                </div>
                <div class="hero-notice">{{ __('landing.hero.notice') }}</div>
            </div>
        </div>
    </header>

    @php
        if (!function_exists('kcStatRound')) {
            function kcStatRound(int $n): int {
                if ($n <= 0) return 0;
                $len = strlen((string) $n);
                $step = (int) max(1, (int) pow(10, $len - 1) / 2);
                return (int)(ceil($n / $step) * $step);
            }
        }
        if (!function_exists('kcFmtStat')) {
            function kcFmtStat(int $n): string {
                if ($n < 1000) return (string) $n;
                if ($n < 1_000_000) {
                    $val = rtrim(rtrim(number_format($n / 1000, 1, '.', ''), '0'), '.');
                    return $val . 'K';
                }
                $val = rtrim(rtrim(number_format($n / 1_000_000, 1, '.', ''), '0'), '.');
                return $val . 'M';
            }
        }
        $storesTarget    = kcStatRound($landingPartnerStoresCount * 3);
        $salesTarget     = kcStatRound($landingSalesCount * 600);
        $customersTarget = kcStatRound($landingCustomersCount * 5);
    @endphp
    <section class="section" id="stats">
        <div class="page-padding">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-heading">{{ __('landing.stats.heading_line1') }}<br>{{ __('landing.stats.heading_line2') }}</h2>
                    <p class="subheading">{{ __('landing.stats.sub') }}</p>
                </div>
                <div class="features-grid kc-stats-grid">
                    <div class="features-card cc-green kc-stat-card">
                        <div class="features-visual">
                            <div class="kc-stat-num{{ $storesTarget > 0 ? ' js-count' : '' }}"
                                 @if($storesTarget > 0) data-count-target="{{ $storesTarget }}" @endif
                                 aria-label="{{ $storesTarget }}{{ $storesTarget > 0 ? '+' : '' }} {{ __('landing.stats.partners_aria') }}">
                                <span class="kc-stat-num__val">{{ kcFmtStat($storesTarget) }}</span>@if($storesTarget > 0)<span class="kc-stat-num__plus" aria-hidden="true">+</span>@endif
                            </div>
                        </div>
                        <div class="features-card-content">
                            <h3 class="heading-m u-mb-m">{{ __('landing.stats.partners') }}</h3>
                            <p class="subheading cc-features">{{ __('landing.stats.partners_desc') }}</p>
                        </div>
                    </div>
                    <div class="features-card cc-blue kc-stat-card">
                        <div class="features-visual">
                            <div class="kc-stat-num{{ $salesTarget > 0 ? ' js-count' : '' }}"
                                 @if($salesTarget > 0) data-count-target="{{ $salesTarget }}" @endif
                                 aria-label="{{ $salesTarget }}{{ $salesTarget > 0 ? '+' : '' }} {{ __('landing.stats.sales_aria') }}">
                                <span class="kc-stat-num__val">{{ kcFmtStat($salesTarget) }}</span>@if($salesTarget > 0)<span class="kc-stat-num__plus" aria-hidden="true">+</span>@endif
                            </div>
                        </div>
                        <div class="features-card-content">
                            <h3 class="heading-m u-mb-m">{{ __('landing.stats.sales') }}</h3>
                            <p class="subheading cc-features">{{ __('landing.stats.sales_desc') }}</p>
                        </div>
                    </div>
                    <div class="features-card cc-purple kc-stat-card">
                        <div class="features-visual">
                            <div class="kc-stat-num{{ $customersTarget > 0 ? ' js-count' : '' }}"
                                 @if($customersTarget > 0) data-count-target="{{ $customersTarget }}" @endif
                                 aria-label="{{ $customersTarget }}{{ $customersTarget > 0 ? '+' : '' }} {{ __('landing.stats.customers_aria') }}">
                                <span class="kc-stat-num__val">{{ kcFmtStat($customersTarget) }}</span>@if($customersTarget > 0)<span class="kc-stat-num__plus" aria-hidden="true">+</span>@endif
                            </div>
                        </div>
                        <div class="features-card-content">
                            <h3 class="heading-m u-mb-m">{{ __('landing.stats.customers') }}</h3>
                            <p class="subheading cc-features">{{ __('landing.stats.customers_desc') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="coverage">
        <div class="page-padding">
            <div class="container">
                <div class="section-header cc-coverage">
                    <div class="eyebrow-pill">
                        <div class="eyebrow-pill-inner"><div>{{ __('landing.coverage.eyebrow') }}</div></div>
                        <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                    </div>
                    <h2 class="section-heading">{{ __('landing.coverage.heading_line1') }}<br>{{ __('landing.coverage.heading_line2') }}</h2>
                    <p class="subheading">{{ __('landing.coverage.sub') }}</p>
                </div>
                <div class="coverage">
                    <div class="coverage-wrap">
                        <canvas id="uzMapCanvas" class="coverage-map"></canvas>
                    </div>
                    <div class="coverage-countries-wrap scroll-observe">
                    @foreach(__('landing.coverage.regions') as $i => $city)
                        <div class="country-tag-wrap cc-{{ ($i % 9) + 1 }}">
                            <div class="country-tag cc-{{ ($i % 9) + 1 }}"><div>{{ $city }}</div></div>
                        </div>
                    @endforeach
                    </div>{{-- coverage-countries-wrap --}}
                </div>{{-- coverage --}}
            </div>
        </div>
    </section>

    <div class="kc-book-ticker" aria-label="{{ __('landing.ticker.aria') }}">
        <div class="kc-book-ticker__track">
            @php
                $books = $featuredBooks ?? collect();
                $clrs = ['#393737','#5a5757','#6b6560','#4a4a48'];
            @endphp
            @if($books->count() > 0)
                @foreach([0, 1] as $_)
                    @foreach($books as $i => $book)
                        <article class="kc-book-card">
                            @if($book->first_image)
                                <img src="{{ asset('storage/'.$book->first_image) }}" alt="{{ $book->name }}" loading="lazy"
                                     onerror="this.outerHTML='<div class=\'kc-book-card__ph\' style=\'background:linear-gradient(135deg,{{ $clrs[$i % 4] }}22,{{ $clrs[($i+1) % 4] }}22)\'>&#128218;</div>'">
                            @else
                                <div class="kc-book-card__ph" style="background:linear-gradient(135deg,{{ $clrs[$i % 4] }}28,{{ $clrs[($i+1) % 4] }}18)">&#128218;</div>
                            @endif
                            <div class="kc-book-card__meta">
                                <div>{{ $book->name }}</div>
                                <div class="a">{{ $book->author }}</div>
                                <div style="margin-top:4px">
                                    @if($book->discountPrice && $book->discountPrice < $book->price)
                                        {{ number_format($book->discountPrice) }} UZS
                                    @else
                                        {{ number_format($book->price) }} UZS
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                @endforeach
            @else
                @foreach(['Dune','1984','Sapiens','Alxemik'] as $i => $t)
                    <article class="kc-book-card">
                        <div class="kc-book-card__ph" style="background:linear-gradient(135deg,{{ $clrs[$i % 4] }}28,{{ $clrs[($i+1) % 4] }}18)">&#128218;</div>
                        <div class="kc-book-card__meta"><div>{{ $t }}</div><div class="a">{{ __('landing.ticker.placeholder_author') }}</div><div style="margin-top:4px">45 000 UZS</div></div>
                    </article>
                @endforeach
                @foreach(['Dune','1984','Sapiens','Alxemik'] as $i => $t)
                    <article class="kc-book-card">
                        <div class="kc-book-card__ph" style="background:linear-gradient(135deg,{{ $clrs[$i % 4] }}28,{{ $clrs[($i+1) % 4] }}18)">&#128218;</div>
                        <div class="kc-book-card__meta"><div>{{ $t }}</div><div class="a">{{ __('landing.ticker.placeholder_author') }}</div><div style="margin-top:4px">45 000 UZS</div></div>
                    </article>
                @endforeach
            @endif
        </div>
    </div>

    <section class="section cc-reviews">
        <div class="page-padding">
            <div class="container">
                <div class="section-header cc-reviews">
                    <h2 class="section-heading">{{ __('landing.reviews.heading_line1') }}<br>{{ __('landing.reviews.heading_line2') }}</h2>
                </div>
                <div class="features-grid cc-reviews">
                    @if($landingUgcReviews->isNotEmpty())
                        @foreach($landingUgcReviews as $ugcPost)
                            @php
                                $stars   = (int) round($ugcPost->kangaroo_post_star ?? 5);
                                $stars   = max(1, min(5, $stars));
                                $excerpt = mb_strtolower(mb_substr(trim($ugcPost->text), 0, 1))
                                           . mb_substr(trim($ugcPost->text), 1);
                                $excerpt = mb_strlen($ugcPost->text) > 160
                                           ? mb_substr($excerpt, 0, 157) . '…'
                                           : $excerpt;
                                $handle  = trim(($ugcPost->user->name ?? '') . ' ' . ($ugcPost->user->lastname ?? ''))
                                           ?: __('landing.reviews.user_default');
                            @endphp
                            <div class="review-card">
                                <div class="stars" aria-hidden="true">
                                    @for($s = 1; $s <= 5; $s++)
                                        <img src="{{ asset('vendor/popcorn/images/review-star.webp') }}"
                                             width="14" height="14" alt=""
                                             class="star-icon{{ $s > $stars ? ' kc-star-dim' : '' }}"
                                             loading="lazy">
                                    @endfor
                                </div>
                                <h3 class="review-title">&ldquo;{{ $excerpt }}&rdquo;</h3>
                                <div class="u-mt-auto">{{ $handle }}</div>
                            </div>
                        @endforeach
                    @else
                        @foreach(__('landing.reviews.fallback') as $fb)
                            <div class="review-card">
                                <div class="stars" aria-hidden="true">@foreach(range(1,5) as $_)<img src="{{ asset('vendor/popcorn/images/review-star.webp') }}" width="14" height="14" alt="" class="star-icon" loading="lazy">@endforeach</div>
                                <h3 class="review-title">&ldquo;{{ $fb['quote'] }}&rdquo;</h3>
                                <div class="u-mt-auto">{{ $fb['author'] }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="section cc-home-faq" id="faq">
        <div class="page-padding">
            <div class="container">
                <div class="section-header cc-faq">
                    <div class="eyebrow-pill">
                        <div class="eyebrow-pill-inner"><div>{{ __('landing.faq.eyebrow') }}</div></div>
                        <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                    </div>
                    <h2 class="section-heading">{{ __('landing.faq.heading_line1') }}<br>{{ __('landing.faq.heading_line2') }}</h2>
                </div>
                <div class="faq-wrap kc-faq">
                    @foreach(__('landing.faq.items') as $item)
                        <details class="faq-item kc-faq-details">
                            <summary class="faq-header">
                                <div>{{ $item['q'] }}</div>
                                <div class="faq-icon w-embed">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0003 0.833984C15.0629 0.833984 19.167 4.93804 19.167 10.0007C19.167 15.0632 15.0629 19.1673 10.0003 19.1673C4.93774 19.1673 0.83366 15.0632 0.83366 10.0006C0.83366 4.93804 4.93774 0.833984 10.0003 0.833984ZM13.3337 7.57214L14.5122 8.75065L10.0003 13.2625L5.48849 8.75065L6.66699 7.57214L10.0003 10.9055L13.3337 7.57214Z" fill="currentColor"></path>
                                    </svg>
                                </div>
                            </summary>
                            <div class="faq-answer" style="height:auto;opacity:1">
                                <div class="faq-answer-inner w-richtext"><p>{{ $item['a'] }}</p></div>
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="section cc-cta-banner" id="business">
        <div class="page-padding">
            <div class="container">
                <div class="cta-banner">
                    <div class="cta-banner-left">
                        <div class="cta-banner-left-content">
                            <h3 class="heading-l">{{ __('landing.business.heading_steps') }}</h3>
                            <ul role="list" class="steps-list w-list-unstyled">
                                <li class="steps-list-item"><div class="steps-list-step">1</div><div>{{ __('landing.business.step1_label') }}</div></li>
                                <li class="steps-list-item"><div class="steps-list-step">2</div><div>{{ __('landing.business.step2_label') }}</div></li>
                                <li class="steps-list-item"><div class="steps-list-step">3</div><div>{{ __('landing.business.step3_label') }}</div></li>
                            </ul>
                            <div class="timed-tag u-rainbow">
                                <div>{{ __('landing.business.timed_tag') }}</div>
                                <div class="svg-embed w-embed">
                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M7.24634 0.730242C3.42974 0.778366 0.374344 3.91179 0.422468 7.7284C0.470591 11.545 3.60402 14.6004 7.42063 14.5523C11.2372 14.5042 14.2926 11.3707 14.2445 7.55412C14.1855 3.74226 11.0585 0.693119 7.24634 0.730242ZM11.453 5.11651L6.33504 10.3651C6.22416 10.4788 6.07743 10.5371 5.93 10.539C5.78256 10.5409 5.63442 10.4863 5.5207 10.3754L3.18798 8.10077C2.95995 7.87842 2.95536 7.51444 3.17771 7.28642C3.40005 7.0584 3.76403 7.05381 3.99206 7.27615L5.91247 9.14874L10.6283 4.31243C10.8507 4.0844 11.2147 4.07982 11.4427 4.30216C11.6707 4.5245 11.6753 4.88848 11.453 5.11651Z" fill="#212121"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="cta-banner-right">
                        <div class="div-block-3">
                            <h3 class="heading-l">{{ __('landing.business.title_line1') }}<br>{{ __('landing.business.title_line2') }}</h3>
                            <div class="cta-banner-price">{{ __('landing.business.price') }}</div>
                            <div class="u-weight-600">{{ __('landing.business.join') }}</div>
                        </div>
                        <div class="btn-wrapper cc-cta">
                            <a href="https://play.google.com/store/apps/details?id=com.kitobchi.business.kitobchibusiness" target="_blank" rel="noopener noreferrer" class="cta w-inline-block kc-smart-store" data-play="https://play.google.com/store/apps/details?id=com.kitobchi.business.kitobchibusiness" data-appstore="https://apps.apple.com/uz/app/kitobchi-business/id6753881071" aria-label="{{ __('landing.business.apply_aria') }}">
                                <div class="cta-bg u-rainbow u-blur-perf cc-dark"></div>
                                <div class="cta-inner cc-dark">
                                    <div><strong>{{ __('landing.business.apply') }}</strong></div>
                                </div>
                            </a>
                        </div>
                        @if($shotBiz)
                            <img src="{{ $shotBiz }}" loading="lazy" width="360" height="732" alt="" class="cta-banner-mockup" sizes="(max-width: 479px) 100vw, 360px">
                            <img src="{{ $shotBiz }}" loading="lazy" width="380" height="215" alt="" class="cta-banner-img-mobile" sizes="(max-width: 479px) 100vw, 380px">
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
