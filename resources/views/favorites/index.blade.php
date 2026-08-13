@extends('layouts.marketplace')

@section('title', "Sevimlilar | Kitobchi Marketpleysi")

@section('content')
<div style="min-height:100dvh;padding:1.5rem 0;">
    <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">

        <!-- ====== BREADCRUMBS ====== -->
        <div style="margin-bottom:1.25rem;">
            <nav aria-label="Breadcrumb">
                <ol style="display:flex;align-items:center;gap:0.5rem;list-style:none;padding:0;margin:0;">
                    <li>
                        <a href="{{ url('/') }}" style="font-size:0.875rem;color:#8F8FA1;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#8F8FA1'">Asosiy</a>
                    </li>
                    <li aria-hidden="true" style="color:#8F8FA1;font-size:0.75rem;">/</li>
                    <li>
                        <span style="font-size:0.875rem;color:#111827;font-weight:600;">Sevimlilar</span>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- ====== HEADING ====== -->
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem;">
            <iconify-icon icon="heroicons-solid:heart" style="font-size:26px;color:#ef4444;flex-shrink:0;"></iconify-icon>
            <h1 style="font-size:clamp(1.375rem,4vw,1.875rem);font-weight:800;color:#111827;margin:0;">
                Sevimlilar
                @if($items->isNotEmpty())
                    <span style="font-size:1rem;font-weight:600;color:#9ca3af;">({{ $items->count() }})</span>
                @endif
            </h1>
        </div>

        @if($items->isEmpty())
            <!-- ====== EMPTY STATE ====== -->
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:4.5rem 1rem;background:#fafafa;border-radius:1.5rem;">
                <iconify-icon icon="heroicons:heart" style="font-size:56px;color:#d1d5db;"></iconify-icon>
                <h2 style="font-size:1.125rem;font-weight:700;color:#111827;margin:1.25rem 0 0.5rem;">Sevimlilar ro'yxati bo'sh</h2>
                <p style="font-size:0.9rem;color:#6b7280;margin:0 0 1.5rem;max-width:360px;line-height:1.6;">
                    Yoqqan kitob yoki kanselyariya buyumlarini yurak belgisini bosib shu yerga qo'shing.
                </p>
                <a href="{{ route('web.catalog') }}" class="kc-primary-btn">
                    <iconify-icon icon="heroicons-solid:squares-2x2" style="font-size:18px;"></iconify-icon>
                    Katalogga o'tish
                </a>
            </div>
        @else
            <!-- ====== FAVORITES GRID ====== -->
            <div id="kcFavGrid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.5rem;">
                @foreach($items as $fav)
                    @php
                        $product = $fav->product;
                        $isStationery = $fav->type === 'stationery';
                        $favSlug = \Illuminate\Support\Str::slug($product->name);
                        $favUrl = $isStationery
                            ? route('web.stationery.show', ['id' => $product->id, 'slug' => $favSlug])
                            : route('web.books.show', ['id' => $product->id, 'slug' => $favSlug]);
                        $favImg = $product->first_image ? asset('storage/' . $product->first_image) : asset('images/logo/logo_blue.png');
                        $favRawPrice = (float) $product->price;
                        $favDiscRaw = $isStationery ? (float) ($product->discount_price ?? 0) : (float) ($product->discountPrice ?? 0);
                        $favIsDisc = $favDiscRaw > 0 && $favDiscRaw < $favRawPrice;
                        $favPrice = $favIsDisc ? $favDiscRaw : $favRawPrice;
                        $favDiscPct = $favIsDisc ? round((($favRawPrice - $favPrice) / $favRawPrice) * 100) : 0;
                    @endphp
                    <a href="{{ $favUrl }}" class="kc-product-card">
                        <div class="kc-product-card-img">
                            <img src="{{ $favImg }}" alt="{{ $product->name }}" loading="lazy">
                            @if($favIsDisc)
                                <span class="kc-discount-badge">-{{ $favDiscPct }}%</span>
                            @endif
                            <div class="kc-fav-btn-wrap">
                                <button aria-label="Sevimlilardan o'chirish"
                                        onclick="event.preventDefault(); removeFavoriteCard(this, {{ $product->id }}, '{{ $isStationery ? 'stationery' : 'book' }}');"
                                        class="kc-fav-btn">
                                    <iconify-icon icon="heroicons-solid:heart" style="font-size:16px;color:#ef4444;"></iconify-icon>
                                </button>
                            </div>
                        </div>
                        <div class="kc-product-card-body">
                            <div class="kc-product-card-title">{{ $product->name }}</div>
                            <div class="kc-product-card-price">{{ number_format($favPrice) }} so'm</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

    </div>
</div>

<style>
    @media(min-width: 640px) {
        #kcFavGrid { grid-template-columns: repeat(3, 1fr) !important; gap: 0.75rem !important; }
    }
    @media(min-width: 1024px) {
        #kcFavGrid { grid-template-columns: repeat(4, 1fr) !important; gap: 1rem !important; }
    }
    @media(min-width: 1280px) {
        #kcFavGrid { grid-template-columns: repeat(5, 1fr) !important; gap: 1.25rem !important; }
    }
</style>

@push('scripts')
<script>
    // Sevimlilar sahifasida yurakni bosish — boshqa sahifalardagi
    // toggleFavorite()'dan farqli o'laroq, bu yerda faqat "o'chirish"
    // ma'nosini bildiradi: kartani ro'yxatdan butunlay olib tashlaydi.
    function removeFavoriteCard(btn, productId, productType) {
        const card = btn.closest('.kc-product-card');
        if (card) {
            card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.92)';
            card.style.pointerEvents = 'none';
        }

        fetch("{{ route('web.favorites.toggle') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ product_id: productId, product_type: productType }),
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                if (typeof bumpFavBadge === 'function') bumpFavBadge(-1);
                setTimeout(() => {
                    if (card) card.remove();
                    const grid = document.getElementById('kcFavGrid');
                    if (grid && grid.children.length === 0) {
                        location.reload();
                    }
                }, 250);
            } else if (card) {
                card.style.opacity = '1';
                card.style.transform = 'none';
                card.style.pointerEvents = 'auto';
            }
        })
        .catch(() => {
            if (card) {
                card.style.opacity = '1';
                card.style.transform = 'none';
                card.style.pointerEvents = 'auto';
            }
        });
    }
</script>
@endpush
@endsection
