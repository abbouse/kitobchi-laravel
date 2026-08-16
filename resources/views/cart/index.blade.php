@extends('layouts.marketplace')

@section('title', 'Savatcha — Kitobchi')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-6 rounded-t-2xl grow">
    
    <div class="flex items-center gap-2 mb-5">
        <a href="{{ route('web.catalog') }}" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-300" title="Katalogga qaytish">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </a>
        <nav aria-label="breadcrumb" class="relative min-w-0">
            <ol class="flex items-center gap-2">
                <li class="flex min-w-0 text-[#8F8FA1] text-sm">
                    <a href="{{ url('/') }}" class="hover:text-neutral-900 transition-colors">Asosiy</a>
                </li>
                <li class="flex text-gray text-xs">/</li>
                <li class="flex min-w-0 text-[#8F8FA1] text-sm font-semibold">
                    Savatcha
                </li>
            </ol>
        </nav>
    </div>

    <h1 class="text-4xl text-primary font-bold dark:text-white mb-6">
        Savatcha
        <span id="kcCartCount" class="text-xl font-medium text-neutral-400"></span>
    </h1>

    <div id="kcCartPageContent"></div>

</div>
@endsection

@push('scripts')
<script>
// ── Tanlash (selection) ────────────────────────────────────────────
// Piyolamarket'da savat har bir mahsulotni alohida checkbox bilan
// tanlash imkonini beradi (masalan ba'zi mahsulotlarni keyinroqqa
// qoldirib, faqat tanlanganlarini xarid qilish). `selected` maydoni
// yo'q (eski savat yozuvlari) bo'lsa ham xato bermasin uchun har doim
// `!== false` bilan tekshiramiz — ya'ni yo'q/undefined = tanlangan.
function kcSelectedItems(cart) {
    return cart.filter(i => i.selected !== false);
}

function kcToggleSelectAll(checked) {
    const cart = kcCart || JSON.parse(localStorage.getItem('kc_cart') || '[]');
    cart.forEach(i => i.selected = checked);
    if (typeof kcCart !== 'undefined') kcCart = cart;
    localStorage.setItem('kc_cart', JSON.stringify(cart));
    renderCartPage();
}

function kcToggleItemSelect(id, checked) {
    const cart = kcCart || JSON.parse(localStorage.getItem('kc_cart') || '[]');
    const item = cart.find(i => i.id === id);
    if (item) item.selected = checked;
    if (typeof kcCart !== 'undefined') kcCart = cart;
    localStorage.setItem('kc_cart', JSON.stringify(cart));
    renderCartPage();
}

// Promokod: bu yerda jonli tekshirilmaydi (mehmon hali telefon raqamini
// kiritmagan — backend promokodni FOYDALANUVCHI bo'yicha tekshiradi).
// Shu sababli faqat saqlab qo'yamiz, haqiqiy tekshiruv/qo'llash
// checkout tasdiqlanganda serverda amalga oshadi (WebCheckoutController
// -> buy_book -> validatePromocode, xato bo'lsa checkoutda ko'rsatiladi).
function kcSavePromo(value) {
    localStorage.setItem('kc_promo', value || '');
}

function renderCartPage() {
    const container = document.getElementById('kcCartPageContent');
    const countEl = document.getElementById('kcCartCount');
    if (!container) return;

    const cart = kcCart || JSON.parse(localStorage.getItem('kc_cart') || '[]');

    if (countEl) countEl.textContent = cart.length > 0 ? `(${cart.length})` : '';

    if (!cart || cart.length === 0) {
        container.innerHTML = `
            <div class="pt-10 pb-20">
                <div class="py-10 text-center">
                    <div class="w-full">
                        <img alt="Empty cart" class="w-full max-w-[250px] mx-auto mb-4" src="{{ asset('images/empty-basket.svg') }}">
                    </div>
                    <h2 class="text-xl font-bold dark:text-white mb-2">
                        Savatingiz bo'sh
                    </h2>
                    <p class="text-neutral-500 mb-6">
                        Yoqqan mahsulotlaringizni savatga qo'shing — xaridni shu yerdan bir necha bosishda yakunlaysiz
                    </p>
                    <div>
                        <a href="{{ route('web.catalog') }}" class="font-medium items-center transition-colors py-1.5 gap-1.5 text-inverted bg-primary hover:bg-primary/75 h-12 justify-center sm:min-w-40 rounded-2xl text-base max-md:w-full inline-flex px-6" style="color:#fff;">
                            Katalogga o‘tish
                        </a>
                    </div>
                </div>
            </div>`;
        return;
    }

    let itemsHtml = '';

    cart.forEach(item => {
        const isSelected = item.selected !== false;
        const openTag = item.url ? `<a href="${item.url}" class="group block bg-white rounded-xl overflow-hidden">` : `<div>`;
        const closeTag = item.url ? '</a>' : '</div>';

        itemsHtml += `
            <div class="flex gap-3 border-b border-secondary-300 pb-5 ${isSelected ? '' : 'opacity-50'}">
                <label class="flex items-start pt-1 cursor-pointer shrink-0">
                    <input type="checkbox" class="w-5 h-5 accent-primary cursor-pointer" ${isSelected ? 'checked' : ''} onchange="kcToggleItemSelect(${item.id}, this.checked)">
                </label>
                <div class="flex max-md:flex-col gap-4 md:gap-5 flex-1 min-w-0">
                    <div class="w-full md:w-32 lg:w-40 xl:w-48 shrink-0 relative bg-neutral-100 rounded-xl overflow-hidden aspect-square flex items-center justify-center">
                        <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 flex flex-col sm:justify-between py-2 gap-4 min-w-0">
                        <div class="flex items-start justify-between gap-4">
                            ${openTag}
                                <h3 class="text-lg md:text-xl font-medium text-primary hover:text-primary-500 transition-colors line-clamp-2">${item.name}</h3>
                            ${closeTag}
                            <button type="button" onclick="removeFromCart(${item.id}); renderCartPage();" class="shrink-0 p-2 text-neutral-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all duration-300">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 block"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14ZM10 11v6M14 11v6"/></svg>
                            </button>
                        </div>
                        <div class="flex max-sm:flex-col sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-neutral-500">Narxi:</span>
                                <span class="font-bold text-lg md:text-xl text-primary">${new Intl.NumberFormat('uz').format(item.price)} so'm</span>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end gap-6 max-sm:w-full">
                                <div class="flex items-center gap-3 bg-secondary-100 rounded-lg p-1">
                                    <button type="button" onclick="updateQty(${item.id}, -1); renderCartPage();" class="w-8 h-8 rounded-md bg-white flex items-center justify-center text-primary shadow-sm hover:bg-gray-50 transition-colors font-medium">−</button>
                                    <span class="w-8 text-center font-medium text-primary">${item.qty}</span>
                                    <button type="button" onclick="updateQty(${item.id}, 1); renderCartPage();" class="w-8 h-8 rounded-md bg-white flex items-center justify-center text-primary shadow-sm hover:bg-gray-50 transition-colors font-medium">+</button>
                                </div>
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
        <div class="flex items-center justify-between bg-white rounded-xl border border-secondary-200 px-4 py-3 mb-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" class="w-5 h-5 accent-primary cursor-pointer" ${allSelected ? 'checked' : ''} onchange="kcToggleSelectAll(this.checked)">
                <span class="font-medium text-primary">Barcha mahsulotlarni tanlash</span>
            </label>
            <span class="text-sm text-neutral-400">${totalCount} ta mahsulot tanlandi</span>
        </div>

        <div class="flex flex-col lg:flex-row gap-6 xl:gap-8 relative items-start">
            <div class="flex-1 flex flex-col gap-5 w-full min-w-0">
                <div class="flex flex-col gap-5 w-full bg-white p-4 sm:p-6 rounded-2xl border border-secondary-200">
                    ${itemsHtml}
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-secondary-200">
                    <input type="text" id="kcPromoInput" placeholder="Promokod" value="${savedPromo.replace(/"/g, '&quot;')}" oninput="kcSavePromo(this.value)" class="w-full h-12 bg-secondary-100 border-none rounded-xl px-4 text-sm font-medium text-primary outline-none focus:ring-2 ring-primary/20 transition-all" autocomplete="off">
                </div>
            </div>

            <div class="w-full lg:w-[380px] shrink-0 sticky top-24">
                <div class="bg-white rounded-2xl p-5 md:p-6 border border-secondary-200 shadow-sm flex flex-col gap-6">
                    <h3 class="text-xl font-bold text-primary">
                        Buyurtmangiz
                    </h3>
                    <div class="flex flex-col gap-4">
                        <div class="flex justify-between items-center text-base">
                            <span class="text-neutral-500">Mahsulotlar (${totalCount}):</span>
                            <span class="font-medium text-primary">${new Intl.NumberFormat('uz').format(total)} so'm</span>
                        </div>
                        <div class="flex justify-between items-center text-base">
                            <span class="text-neutral-500">Yetkazib berish:</span>
                            <span class="font-medium text-neutral-500">Hududga qarab</span>
                        </div>

                        <hr class="border-secondary-200 my-1">

                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-primary">Mahsulotlar:</span>
                            <span class="text-2xl font-bold text-primary">${new Intl.NumberFormat('uz').format(total)} so'm</span>
                        </div>
                        <div class="text-xs text-neutral-400 -mt-2">Yetkazib berish narxi va promokod chegirmasi keyingi bosqichda hisoblanadi</div>
                    </div>

                    <a href="${checkoutDisabled ? '#' : `{{ route('web.checkout') }}`}" class="font-medium items-center transition-colors gap-2 text-inverted bg-primary hover:bg-primary/75 h-14 justify-center rounded-2xl text-lg w-full inline-flex ${checkoutDisabled ? 'opacity-40 pointer-events-none' : ''}" style="color:#fff;">
                        Xaridni davom ettirish
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.1em;height:1.1em;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>`;
}

document.addEventListener('DOMContentLoaded', renderCartPage);
</script>
@endpush
