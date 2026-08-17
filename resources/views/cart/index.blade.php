@extends('layouts.marketplace')

@section('title', 'Savatcha — Kitobchi')

@push('meta')
<meta name="robots" content="noindex, follow">
@endpush

@section('content')
<div class="py-6 min-h-dvh">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        
        <div class="flex items-center gap-2 mb-5">
            <a href="{{ route('web.catalog') }}" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-300 shrink-0" title="{{ __('marketplace.back_to_catalog') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </a>
            <nav aria-label="breadcrumb" class="relative min-w-0">
                <ol class="flex items-center gap-2 text-sm text-[#8F8FA1]">
                    <li>
                        <a href="{{ url('/') }}" class="hover:text-neutral-900 transition-colors">{{ __('marketplace.breadcrumb_home') }}</a>
                    </li>
                    <li class="text-gray-300">/</li>
                    <li class="text-neutral-900 font-semibold">{{ __('marketplace.cart_title') }}</li>
                </ol>
            </nav>
        </div>

        <h1 class="text-3xl lg:text-4xl text-primary font-bold mb-6 flex items-baseline gap-3 max-md:hidden">
            {{ __('marketplace.cart_title') }}
            <span id="kcCartCount" class="text-lg font-normal text-neutral-400"></span>
        </h1>

        <!-- Mobile header title -->
        <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40">
            <div class="grid grid-cols-5 items-center gap-2">
                <div class="col-span-1">
                    <a href="javascript:history.back()" class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                    </a>
                </div>
                <div class="col-span-3">
                    <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">{{ __('marketplace.cart_title') }}</h1>
                </div>
                <div class="col-span-1 flex justify-end"></div>
            </div>
        </div>

        <div id="kcCartPageContent"></div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    const KC_CART_I18N = {
        empty: @json(__('marketplace.cart_empty_title')),
        emptyDesc: @json(__('marketplace.cart_empty_desc')),
        goToCatalog: @json(__('marketplace.go_to_catalog')),
        selectAll: @json(__('marketplace.select_all')),
        selectedCount: @json(__('marketplace.selected_count')),
        priceLabel: @json(__('marketplace.price_label')),
        promoCode: @json(__('marketplace.promo_code')),
        moveToFavorites: @json(__('marketplace.favorites')),
        productsCount: @json(__('marketplace.products_count')),
        delivery: @json(__('marketplace.delivery')),
        deliveryByRegion: @json(__('marketplace.delivery_by_region')),
        deliveryNote: @json(__('marketplace.cart_delivery_note')),
        totalLabel: @json(__('marketplace.cart_total_label')),
        continuePurchase: @json(__('marketplace.continue_purchase')),
        currency: @json(__('marketplace.currency')),
    };

    function kcGetCart() {
        try {
            if (typeof kcCart !== 'undefined' && Array.isArray(kcCart) && kcCart.length > 0) {
                return kcCart;
            }
            const data = localStorage.getItem('kc_cart') || localStorage.getItem('cart');
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    }

    function kcSetCart(cart) {
        if (typeof kcCart !== 'undefined') kcCart = cart;
        localStorage.setItem('kc_cart', JSON.stringify(cart));
        if (typeof updateBadges === 'function') updateBadges();
    }

    function kcSelectedItems(cart) {
        return cart.filter(i => i.selected !== false);
    }

    function kcToggleSelectAll(checked) {
        const cart = kcGetCart().map(i => ({ ...i, selected: checked }));
        kcSetCart(cart);
        renderCartPage();
    }

    function kcToggleItemSelect(itemId, checked) {
        const cart = kcGetCart().map(i => i.id === itemId ? { ...i, selected: checked } : i);
        kcSetCart(cart);
        renderCartPage();
    }

    function kcSavePromo(val) {
        localStorage.setItem('kc_promo', (val || '').trim());
    }

    function renderCartPage() {
        const container = document.getElementById('kcCartPageContent');
        const countEl = document.getElementById('kcCartCount');
        if (!container) return;

        const cart = kcGetCart();
        const kcCartTotalQty = cart.reduce((s, i) => s + (i.qty || 1), 0);
        if (countEl) countEl.textContent = cart.length > 0 ? KC_CART_I18N.productsCount.replace(':count', kcCartTotalQty) : '';

        if (!cart || cart.length === 0) {
            container.innerHTML = `
                <div class="py-16 text-center">
                    <div class="w-20 h-20 rounded-full bg-secondary-100 text-neutral-400 flex items-center justify-center mx-auto mb-4">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-primary mb-2">
                        ${KC_CART_I18N.empty}
                    </h2>
                    <p class="text-neutral-400 text-sm mb-6">
                        ${KC_CART_I18N.emptyDesc}
                    </p>
                    <div>
                        <a href="{{ route('web.catalog') }}" class="inline-flex items-center justify-center bg-primary hover:bg-primary/90 text-white font-semibold px-6 py-3 rounded-2xl text-base transition-colors no-underline">
                            ${KC_CART_I18N.goToCatalog}
                        </a>
                    </div>
                </div>`;
            return;
        }

        let itemsHtml = '';
        cart.forEach(item => {
            const isSelected = item.selected !== false;
            const openTag = item.url ? `<a href="${item.url}" class="group block no-underline">` : `<div>`;
            const closeTag = item.url ? '</a>' : '</div>';
            const inferredType = (item.url || '').includes('/stationery/') ? 'stationery' : 'book';

            itemsHtml += `
                <div class="rounded-[20px] p-4 bg-white flex gap-4 transition-colors border border-secondary-100 ${isSelected ? '' : 'opacity-60'}">
                    <div class="pt-1">
                        <input type="checkbox" class="w-5 h-5 accent-primary cursor-pointer rounded" ${isSelected ? 'checked' : ''} onchange="kcToggleItemSelect(${item.id}, this.checked)">
                    </div>
                    <div class="flex gap-3 flex-1 overflow-hidden">
                        <div class="w-[90px] h-[120px] md:w-[100px] md:h-[133px] rounded-xl overflow-hidden shrink-0 bg-gray-50 flex items-center justify-center">
                            <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">
                        </div>
                        <div class="flex flex-col justify-between flex-1 min-w-0">
                            <div class="space-y-1">
                                <div class="flex justify-between items-start gap-3">
                                    ${openTag}
                                        <h3 class="text-sm md:text-base font-semibold text-neutral-900 hover:text-primary transition-colors line-clamp-2 m-0">${item.name}</h3>
                                    ${closeTag}
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" aria-label="${KC_CART_I18N.moveToFavorites || ''}" onclick="toggleFavorite(this, ${item.id}, '${inferredType}')" class="kc-cart-fav-btn p-1.5 text-neutral-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors cursor-pointer border-none bg-transparent">
                                            <svg class="kc-heart-icon w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                                        </button>
                                        <button type="button" onclick="removeFromCart(${item.id}); renderCartPage();" class="p-1.5 text-neutral-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors cursor-pointer border-none bg-transparent">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14ZM10 11v6M14 11v6"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-4 mt-2 flex-wrap pt-2">
                                <div class="flex items-baseline gap-1">
                                    <span class="font-bold text-base md:text-lg text-neutral-900">${new Intl.NumberFormat('uz').format(item.price)} ${KC_CART_I18N.currency}</span>
                                </div>
                                <div class="flex items-center gap-2 bg-secondary-100 rounded-xl p-1">
                                    <button type="button" onclick="updateQty(${item.id}, -1); renderCartPage();" class="w-7 h-7 rounded-lg bg-white flex items-center justify-center text-neutral-800 shadow-sm hover:bg-neutral-50 transition-colors font-bold text-sm cursor-pointer border-none">−</button>
                                    <span class="w-6 text-center font-bold text-sm text-neutral-900">${item.qty}</span>
                                    <button type="button" onclick="updateQty(${item.id}, 1); renderCartPage();" class="w-7 h-7 rounded-lg bg-white flex items-center justify-center text-neutral-800 shadow-sm hover:bg-neutral-50 transition-colors font-bold text-sm cursor-pointer border-none">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
        });

        const selected = kcSelectedItems(cart);
        const total = selected.reduce((s, i) => s + i.price * i.qty, 0);
        const totalCount = selected.reduce((s, i) => s + i.qty, 0);
        const allSelected = cart.every(i => i.selected !== false);
        const savedPromo = localStorage.getItem('kc_promo') || '';
        const checkoutDisabled = totalCount === 0;

        container.innerHTML = `
            <div class="flex flex-col lg:flex-row gap-5">
                <div class="md:p-6 rounded-3xl bg-secondary-50 flex-1 space-y-4">
                    <div class="flex items-center justify-between p-3 bg-white rounded-2xl border border-secondary-100">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" class="w-5 h-5 accent-primary cursor-pointer rounded" ${allSelected ? 'checked' : ''} onchange="kcToggleSelectAll(this.checked)">
                            <span class="font-semibold text-sm text-neutral-800">${KC_CART_I18N.selectAll}</span>
                        </label>
                        <span class="text-xs text-neutral-400 font-medium">${KC_CART_I18N.selectedCount.replace(':count', totalCount)}</span>
                    </div>

                    <div class="space-y-3">
                        ${itemsHtml}
                    </div>
                </div>

                <div class="lg:w-96 shrink-0">
                    <div class="p-4 md:p-6 rounded-3xl bg-secondary-50 sticky top-24 space-y-4 border border-secondary-100">
                        <h2 class="text-lg md:text-xl font-bold text-primary m-0">Buyurtmangiz</h2>
                        <input type="text" id="kcPromoInput" placeholder="${KC_CART_I18N.promoCode}" value="${savedPromo.replace(/"/g, '&quot;')}" oninput="kcSavePromo(this.value)" class="w-full h-12 bg-white border border-secondary-100 rounded-2xl px-4 text-sm font-medium text-neutral-900 outline-none focus:ring-2 ring-primary/20 transition-all" autocomplete="off">

                        <div class="space-y-3 pt-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-neutral-500">${KC_CART_I18N.productsCount.replace(':count', totalCount)}</span>
                                <span class="font-bold text-neutral-900">${new Intl.NumberFormat('uz').format(total)} ${KC_CART_I18N.currency}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-neutral-500">${KC_CART_I18N.delivery}</span>
                                <span class="font-medium text-neutral-400">${KC_CART_I18N.deliveryByRegion}</span>
                            </div>

                            <hr class="border-secondary-100 my-2">

                            <div class="flex justify-between items-baseline">
                                <span class="text-base font-bold text-neutral-900">${KC_CART_I18N.totalLabel}</span>
                                <span class="text-xl md:text-2xl font-bold text-primary">${new Intl.NumberFormat('uz').format(total)} ${KC_CART_I18N.currency}</span>
                            </div>
                            <div class="text-xs text-neutral-400">${KC_CART_I18N.deliveryNote}</div>
                        </div>

                        <a href="${checkoutDisabled ? '#' : `{{ route('web.checkout') }}`}" class="font-semibold items-center justify-center transition-all gap-2 text-white bg-primary hover:bg-primary/90 active:scale-[0.99] h-12 rounded-2xl text-base w-full inline-flex no-underline ${checkoutDisabled ? 'opacity-40 pointer-events-none' : ''}">
                            ${KC_CART_I18N.continuePurchase}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.1em;height:1.1em;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>`;
    }

    document.addEventListener('DOMContentLoaded', renderCartPage);
</script>
@endpush
