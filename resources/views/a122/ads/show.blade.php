@extends('a122.layouts.admin')
@section('title', "Reklama #{$ad->id}")

@section('content')
@php
    $adModeration = $ad->moderation ?: 'pending';
    $adPayment = $ad->paymentStatus ?: 'pending';
@endphp
<x-a122.page-header back-href="{{ route('admin.ads.index') }}">
    <x-slot name="heading">Reklama #{{ $ad->id }}</x-slot>
    <x-slot name="meta">Banner, bog'langan obyekt va moderatsiya holati</x-slot>
    <x-slot name="actions">
        @if($ad->moderation === 'pending')
            <form method="POST" action="{{ route('admin.ads.moderate', $ad) }}" class="inline-flex">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="approve">
                <button class="btn-p success" type="submit"><i class="bi bi-check-lg"></i> Tasdiqlash</button>
            </form>
            <form method="POST" action="{{ route('admin.ads.moderate', $ad) }}" class="inline-flex">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="reject">
                <button class="btn-p danger" type="submit"><i class="bi bi-x-lg"></i> Rad etish</button>
            </form>
        @endif
    </x-slot>
</x-a122.page-header>

<section class="a122-section mb-4">
    <div class="a122-section-body">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="kpi-soft">
                <div class="metric-label">Moderatsiya</div>
                <div class="metric-value text-xl">{{ ucfirst($adModeration) }}</div>
                <div class="metric-meta">Admin review holati</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">To‘lov</div>
                <div class="metric-value text-xl">{{ ucfirst($adPayment) }}</div>
                <div class="metric-meta">Payment bosqichi</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Budjet</div>
                <div class="metric-value text-xl">{{ number_format((int) $ad->amount) }}</div>
                <div class="metric-meta">UZS</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Muddat</div>
                <div class="metric-value text-xl">{{ $ad->days ?: '—' }}</div>
                <div class="metric-meta">Kun</div>
            </div>
        </div>
    </div>
</section>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    <div class="xl:col-span-8 space-y-4">
        <div class="p-card">
            <div class="p-card-header">
                <div>
                    <div class="p-card-title"><i class="bi bi-megaphone mr-2" style="color:var(--p-accent)"></i>Banner preview</div>
                    <div class="p-card-sub">Reklama materiali va tavsifi</div>
                </div>
            </div>

            @if($ad->banner_img)
                <a href="{{ $ad->banner_img }}" target="_blank" rel="noopener">
                    <img src="{{ $ad->banner_img }}" alt="Ad banner" style="width:100%;max-height:380px;object-fit:cover;border-radius:18px;border:1px solid var(--p-border)">
                </a>
            @else
                <div class="hero-panel" style="min-height:240px;display:flex;align-items:center;justify-content:center">
                    <div style="text-align:center;color:var(--p-hint)">
                        <i class="bi bi-image" style="font-size:44px;display:block;margin-bottom:8px"></i>
                        Banner rasmi biriktirilmagan
                    </div>
                </div>
            @endif

            <div class="content-prose" style="margin-top:18px">
                <h3>Tavsif</h3>
                <p>{{ $ad->description ?: 'Reklama uchun tavsif kiritilmagan.' }}</p>
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-header">
                <div>
                    <div class="p-card-title"><i class="bi bi-box-seam mr-2" style="color:var(--p-info)"></i>Bog'langan obyekt</div>
                    <div class="p-card-sub">Reklama bosilganda foydalanuvchi qayerga yo'naltiriladi</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="data-kv">
                    <div class="label">Action</div>
                    <div class="value">{{ $ad->action ?: '—' }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Mahsulot turi</div>
                    <div class="value">{{ $ad->product_type ?: '—' }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Mahsulot ID</div>
                    <div class="value">#{{ $ad->product_id ?: '—' }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Reklama turi</div>
                    <div class="value">{{ $ad->type ?: '—' }}</div>
                </div>
            </div>

            <div class="mt-4">
                @if($product)
                    <div class="module-link-card">
                        <div>
                            <div class="module-link-card__title">{{ $product->title ?? $product->name ?? ('#'.$product->id) }}</div>
                            <div class="module-link-card__meta">Bog'langan obyekt topildi va reklamaga ulangan.</div>
                        </div>
                        <div class="s-pill accent">ID {{ $product->id }}</div>
                    </div>
                @else
                    <div class="p-quote-block">
                        Bog'langan obyekt hozircha topilmadi yoki model turi resolve bo'lmadi.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="xl:col-span-4 space-y-4">
        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-title"><i class="bi bi-activity mr-2" style="color:var(--p-success)"></i>Status overview</div>
            </div>
            <div class="space-y-3">
                <div class="data-kv">
                    <div class="label">Moderatsiya</div>
                    <div class="value">
                        <span class="s-pill {{ $ad->moderation === 'approved' ? 'success' : ($ad->moderation === 'rejected' ? 'danger' : 'warning') }}">
                            {{ $ad->moderation }}
                        </span>
                    </div>
                </div>
                <div class="data-kv">
                    <div class="label">To'lov</div>
                    <div class="value">
                        <span class="s-pill {{ $ad->paymentStatus === 'paid' ? 'success' : ($ad->paymentStatus === 'failed' ? 'danger' : 'warning') }}">
                            {{ $ad->paymentStatus ?: 'pending' }}
                        </span>
                    </div>
                </div>
                <div class="data-kv">
                    <div class="label">Summa</div>
                    <div class="value">{{ number_format((int) $ad->amount) }} UZS</div>
                </div>
                <div class="data-kv">
                    <div class="label">Kun soni</div>
                    <div class="value">{{ $ad->days ?: '—' }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Tugash sanasi</div>
                    <div class="value">{{ optional($ad->expire_at)?->format('d.m.Y H:i') ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-title"><i class="bi bi-shop mr-2" style="color:var(--p-warning)"></i>Seller</div>
            </div>
            <div class="space-y-3">
                <div class="data-kv">
                    <div class="label">Seller ID</div>
                    <div class="value">#{{ $ad->seller_id }}</div>
                </div>
                <div class="data-kv">
                    <div class="label">Do'kon</div>
                    <div class="value">{{ $ad->seller->shop_name ?? 'Noma\'lum seller' }}</div>
                </div>
                @if($ad->seller)
                    <a href="{{ route('admin.sellers.show', $ad->seller) }}" class="btn-p ghost" style="width:100%;justify-content:center">
                        <i class="bi bi-arrow-right-circle"></i> Seller profilini ochish
                    </a>
                @endif
            </div>
        </div>

        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-title"><i class="bi bi-clock-history mr-2" style="color:var(--p-muted)"></i>Timeline</div>
            </div>
            <div class="space-y-3">
                @foreach($timeline as $item)
                    <div class="data-kv">
                        <div class="label">{{ $item['label'] }}</div>
                        <div class="value">{{ $item['value'] ?: '—' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
