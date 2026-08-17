@extends('layouts.marketplace')

@section('title', 'Savatcha — Kitobchi')

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

        <h1 class="text-3xl lg:text-4xl text-neutral-900 font-bold mb-6 flex items-baseline gap-3">
            {{ __('marketplace.cart_title') }}
            <span id="kcCartCount" class="text-lg font-normal text-neutral-400"></span>
        </h1>

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
            return JSON.parse(localStorage.getItem('cart')) || [];
        } catch (e) {
            return [];
        }
    }

    function kcSetCart(cart) {
        localStorage.setItem('cart', JSON.stringify(cart));
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
                    <div class="w-20 h-20 rounded-full bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto mb-4">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-neutral-900 mb-2">
                        ${KC_CART_I18N.empty}
                    </h2>
                    <p class="text-neutral-400 text-sm mb-6">
                        ${KC_CART_I18N.emptyDesc}
                    </p>
                    <div>
                        <a href="{{ route('web.catalog') }}" class="inline-flex items-center justify-center bg-primary hover:bg-primary/90 text-white font-semibold px-6 py-3 rounded-2xl text-base transition-colors">
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
                <div class="flex items-center gap-4 bg-white p-4 md:p-5 rounded-3xl border border-secondary-100 ${isSelected ? '' : 'opacity-50'}">
                    <label class="cursor-pointer shrink-0">
                        <input type="checkbox" class="w-5 h-5 accent-primary cursor-pointer rounded" ${isSelected ? 'checked' : ''} onchange="kcToggleItemSelect(${item.id}, this.checked)">
                    </label>
                    <div class="w-20 h-28 md:w-24 md:h-32 bg-neutral-100 rounded-2xl overflow-hidden shrink-0 flex items-center justify-center">
                        <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0 flex flex-col justify-between self-stretch py-1">
                        <div class="flex items-start justify-between gap-2">
                            ${openTag}
                                <h3 class="text-base md:text-lg font-semibold text-neutral-900 hover:text-primary transition-colors line-clamp-2">${item.name}</h3>
                            ${closeTag}
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" aria-label="${KC_CART_I18N.moveToFavorites || ''}" onclick="toggleFavorite(this, ${item.id}, '${inferredType}')" class="kc-cart-fav-btn p-2 text-neutral-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors cursor-pointer border-none bg-transparent">
                                    <svg class="kc-heart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:1.25rem;height:1.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                                </button>
                                <button type="button" onclick="removeFromCart(${item.id}); renderCartPage();" class="p-2 text-neutral-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors cursor-pointer border-none bg-transparent">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14ZM10 11v6M14 11v6"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-4 mt-2 flex-wrap">
                            <div class="flex items-baseline gap-2">
                                <span class="text-xs text-neutral-400 font-medium">${KC_CART_I18N.priceLabel}:</span>
                                <span class="font-bold text-base md:text-lg text-neutral-900">${new Intl.NumberFormat('uz').format(item.price)} ${KC_CART_I18N.currency}</span>
                            </div>
                            <div class="flex items-center gap-2 bg-[#F1F5F9] rounded-xl p-1">
                                <button type="button" onclick="updateQty(${item.id}, -1); renderCartPage();" class="w-7 h-7 rounded-lg bg-white flex items-center justify-center text-neutral-800 shadow-sm hover:bg-neutral-50 transition-colors font-bold text-sm cursor-pointer border-none">−</button>
                                <span class="w-6 text-center font-bold text-sm text-neutral-900">${item.qty}</span>
                                <button type="button" onclick="updateQty(${item.id}, 1); renderCartPage();" class="w-7 h-7 rounded-lg bg-white flex items-center justify-center text-neutral-800 shadow-sm hover:bg-neutral-50 transition-colors font-bold text-sm cursor-pointer border-none">+</button>
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
            <div class="flex items-center justify-between px-3 py-3 bg-white rounded-2xl border border-secondary-100 mb-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" class="w-5 h-5 accent-primary cursor-pointer rounded" ${allSelected ? 'checked' : ''} onchange="kcToggleSelectAll(this.checked)">
                    <span class="font-semibold text-sm text-neutral-800">${KC_CART_I18N.selectAll}</span>
                </label>
                <span class="text-xs text-neutral-400 font-medium">${KC_CART_I18N.selectedCount.replace(':count', totalCount)}</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] xl:grid-cols-[1fr_380px] gap-6 items-start">
                <div class="flex flex-col gap-3 w-full min-w-0">
                    ${itemsHtml}
                </div>

                <div class="w-full shrink-0 sticky top-24">
                    <div class="bg-white rounded-3xl p-6 border border-secondary-100 shadow-sm flex flex-col gap-4">
                        <input type="text" id="kcPromoInput" placeholder="${KC_CART_I18N.promoCode}" value="${savedPromo.replace(/"/g, '&quot;')}" oninput="kcSavePromo(this.value)" class="w-full h-12 bg-secondary-100 border-none rounded-2xl px-4 text-sm font-medium text-neutral-900 outline-none focus:ring-2 ring-primary/20 transition-all" autocomplete="off">

                        <div class="flex flex-col gap-3 pt-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-neutral-500">${KC_CART_I18N.productsCount.replace(':count', totalCount)}</span>
                                <span class="font-bold text-neutral-900">${new Intl.NumberFormat('uz').format(total)} ${KC_CART_I18N.currency}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-neutral-500">${KC_CART_I18N.delivery}</span>
                                <span class="font-medium text-neutral-400">${KC_CART_I18N.deliveryByRegion}</span>
                            </div>

                            <hr class="border-secondary-100 my-1">

                            <div class="flex justify-between items-baseline">
                                <span class="text-base font-bold text-neutral-900">${KC_CART_I18N.totalLabel}</span>
                                <span class="text-2xl font-bold text-neutral-900">${new Intl.NumberFormat('uz').format(total)} ${KC_CART_I18N.currency}</span>
                            </div>
                            <div class="text-xs text-neutral-400 -mt-1">${KC_CART_I18N.deliveryNote}</div>
                        </div>

                        <a href="${checkoutDisabled ? '#' : `{{ route('web.checkout') }}`}" class="mt-2 font-semibold items-center justify-center transition-all gap-2 text-white bg-primary hover:bg-primary/90 active:scale-[0.99] h-12 rounded-2xl text-base w-full inline-flex ${checkoutDisabled ? 'opacity-40 pointer-events-none' : ''}">
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
