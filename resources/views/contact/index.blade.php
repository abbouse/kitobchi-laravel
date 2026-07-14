@extends('legal.layouts.app')

@section('main_classes', 'cc-careers')

@php
    $__seoTitle = __('contact.seo.title');
    $__seoDesc = __('contact.seo.desc');
    $telHref = fn (string $phone) => 'tel:'.preg_replace('/[^+\d]/', '', $phone);

    $supportCards = [
        [
            'title' => __('contact.cards.kitobchi_title'),
            'sub' => __('contact.cards.kitobchi_sub'),
            'phone' => $contacts['kitobchi']['phone'],
            'email' => $contacts['kitobchi']['email'],
            'icon' => 'book',
        ],
        [
            'title' => __('contact.cards.business_title'),
            'sub' => __('contact.cards.business_sub'),
            'phone' => $contacts['business']['phone'],
            'email' => $contacts['business']['email'],
            'icon' => 'shop',
        ],
        [
            'title' => __('contact.cards.express_title'),
            'sub' => __('contact.cards.express_sub'),
            'phone' => $contacts['express']['phone'],
            'email' => $contacts['express']['email'],
            'icon' => 'truck',
        ],
    ];
@endphp

@section('title', $__seoTitle)

@push('meta')
    @include('partials.seo-social', [
        'title' => $__seoTitle,
        'description' => $__seoDesc,
        'canonical' => route('contact.index'),
    ])
@endpush

@section('content')
    <header class="hero cc-careers">
        <div class="page-padding">
            <div class="container">
                <div class="home-hero-header">
                    <div class="eyebrow-pill">
                        <div class="eyebrow-pill-inner"><div>{{ __('contact.hero.eyebrow') }}</div></div>
                        <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                    </div>
                    <h1 class="section-heading cc-careers-hero">{{ __('contact.hero.heading_l1') }}<br><span class="u-weight-400">{{ __('contact.hero.heading_l2') }}</span></h1>
                    <p class="subheading cc-careers cc-last">{{ __('contact.hero.p1') }}</p>
                </div>
            </div>
        </div>
    </header>

    <section class="section" id="support-lines">
        <div class="page-padding">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-heading cc-open-role">{{ __('contact.cards.title') }}</h2>
                </div>

                <div class="kc-careers-vacancy-grid">
                    @foreach($supportCards as $card)
                        <div class="kc-careers-vacancy-card">
                            <div class="kc-careers-vacancy-card__head">
                                <div class="kc-careers-vacancy-card__icon">
                                    @if($card['icon'] === 'book')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                                    @elseif($card['icon'] === 'shop')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v3a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0V9z"/><path d="M5 14.5V21h14v-6.5"/></svg>
                                    @else
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="5" width="14" height="12" rx="1.5"/><path d="M15 9h4l3 4v4h-7"/><circle cx="6" cy="19" r="1.8"/><circle cx="18" cy="19" r="1.8"/></svg>
                                    @endif
                                </div>
                                <h3 class="kc-careers-vacancy-card__title">{{ $card['title'] }}</h3>
                            </div>
                            <ul class="kc-careers-vacancy-meta">
                                <li><span class="kc-careers-vacancy-meta__ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>{{ $card['sub'] }}</li>
                                @if($card['phone'] !== '')
                                    <li>
                                        <span class="kc-careers-vacancy-meta__ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                                        <a href="{{ $telHref($card['phone']) }}" class="kc-careers-mail" style="text-decoration:none">{{ $card['phone'] }}</a>
                                    </li>
                                @endif
                                @if($card['email'] !== '')
                                    <li>
                                        <span class="kc-careers-vacancy-meta__ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg></span>
                                        <a href="mailto:{{ $card['email'] }}" class="kc-careers-mail" style="text-decoration:none">{{ $card['email'] }}</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endforeach

                    <div class="kc-careers-vacancy-card">
                        <div class="kc-careers-vacancy-card__head">
                            <div class="kc-careers-vacancy-card__icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4 20-7z"/><path d="M22 2 11 13"/></svg>
                            </div>
                            <h3 class="kc-careers-vacancy-card__title">{{ __('contact.cards.community_title') }}</h3>
                        </div>
                        <ul class="kc-careers-vacancy-meta">
                            <li><span class="kc-careers-vacancy-meta__ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>{{ __('contact.cards.community_sub') }}</li>
                            <li>
                                <span class="kc-careers-vacancy-meta__ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4 20-7z"/></svg></span>
                                <a href="https://t.me/kitobchi_market" target="_blank" rel="noopener" class="kc-careers-mail" style="text-decoration:none">t.me/kitobchi_market</a>
                            </li>
                            <li>
                                <span class="kc-careers-vacancy-meta__ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg></span>
                                <a href="https://instagram.com/kitobchi_market" target="_blank" rel="noopener" class="kc-careers-mail" style="text-decoration:none">instagram.com/kitobchi_market</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section kc-careers-inquiry-wrap" id="contact-form">
        <div class="page-padding">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-heading cc-open-role">{{ __('contact.form.title') }}</h2>
                    <p class="subheading cc-features">{{ __('contact.form.subtitle') }}</p>
                </div>

                @if(session('contact_success'))
                    <div class="kc-careers-flash kc-careers-flash--ok">{{ session('contact_success') }}</div>
                @endif
                @if(session('contact_error'))
                    <div class="kc-careers-flash kc-careers-flash--err">{{ session('contact_error') }}</div>
                @endif
                @if($errors->any())
                    <div class="kc-careers-flash kc-careers-flash--err">
                        <ul class="mb-0 list-inside list-disc pl-0">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="kc-inquiry-card">
                    <form method="post" action="{{ route('contact.store') }}">
                        @csrf
                        {{-- Honeypot: odam ko'rmaydi, bot to'ldirsa rad etiladi --}}
                        <div style="position:absolute;left:-9999px;top:auto;height:1px;overflow:hidden" aria-hidden="true">
                            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>
                        <div class="kc-apply-grid">
                            <div>
                                <label class="kc-apply-label">{{ __('contact.form.full_name') }}</label>
                                <input type="text" name="full_name" class="kc-apply-input" required maxlength="120" value="{{ old('full_name') }}">
                            </div>
                            <div>
                                <label class="kc-apply-label">{{ __('contact.form.phone') }}</label>
                                <input type="tel" name="phone" class="kc-apply-input" required maxlength="32" placeholder="+998 90 123 45 67" value="{{ old('phone') }}">
                            </div>
                            <div>
                                <label class="kc-apply-label">{{ __('contact.form.email') }}</label>
                                <input type="email" name="email" class="kc-apply-input" value="{{ old('email') }}">
                            </div>
                            <div>
                                <label class="kc-apply-label">{{ __('contact.form.topic') }}</label>
                                <select name="topic" class="kc-apply-input" required>
                                    <option value="kitobchi" @selected(old('topic', 'kitobchi') === 'kitobchi')>{{ __('contact.form.topic_kitobchi') }}</option>
                                    <option value="business" @selected(old('topic') === 'business')>{{ __('contact.form.topic_business') }}</option>
                                    <option value="express" @selected(old('topic') === 'express')>{{ __('contact.form.topic_express') }}</option>
                                    <option value="other" @selected(old('topic') === 'other')>{{ __('contact.form.topic_other') }}</option>
                                </select>
                            </div>
                            <div class="kc-apply-span2">
                                <label class="kc-apply-label">{{ __('contact.form.message') }}</label>
                                <textarea name="message" class="kc-apply-input kc-apply-textarea" rows="6" required minlength="10" maxlength="6000" placeholder="{{ __('contact.form.message_ph') }}">{{ old('message') }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="cta w-inline-block kc-apply-submit">
                            <div class="cta-bg u-rainbow u-blur-perf cc-dark"></div>
                            <div class="cta-inner cc-dark"><div><strong>{{ __('contact.form.submit') }}</strong></div></div>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
