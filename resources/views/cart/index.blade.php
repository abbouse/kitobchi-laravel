@extends('layouts.marketplace')

@section('title', 'Savatcha — Kitobchi')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-6 rounded-t-2xl grow">
    
    <div class="flex items-center gap-2 mb-5">
        <a href="{{ route('web.catalog') }}" class="rounded-md font-medium inline-flex items-center transition-colors px-2.5 py-1.5 text-sm gap-1.5 text-primary hover:text-primary/75 outline-primary/25">
            <i class="icon-up-arrow text-xl -rotate-135"></i>
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
    </h1>

    <div id="kcCartPageContent"></div>

</div>
@endsection

@push('scripts')
<script>
function renderCartPage() {
    const container = document.getElementById('kcCartPageContent');
    if (!container) return;

    const cart = kcCart || JSON.parse(localStorage.getItem('kc_cart') || '[]');

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
                        Ushbu bo’limda hozircha ma’lumot yo’q, ammo tez orada qo’shiladi
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

    let total = 0;
    let itemsHtml = '';

    cart.forEach(item => {
        const itemTotal = item.price * item.qty;
        total += itemTotal;
        const openTag = item.url ? `<a href="${item.url}" class="group block bg-white rounded-xl overflow-hidden">` : `<div>`;
        const closeTag = item.url ? '</a>' : '</div>';
        
        itemsHtml += `
            <div class="flex max-md:flex-col gap-4 md:gap-5 border-b border-secondary-300 pb-5">
                <div class="w-full md:w-32 lg:w-40 xl:w-48 shrink-0 relative bg-neutral-100 rounded-xl overflow-hidden aspect-square flex items-center justify-center">
                    <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">
                </div>
                <div class="flex-1 flex flex-col sm:justify-between py-2 gap-4">
                    <div class="flex items-start justify-between gap-4">
                        ${openTag}
                            <h3 class="text-lg md:text-xl font-medium text-primary hover:text-primary-500 transition-colors line-clamp-2">${item.name}</h3>
                        ${closeTag}
                        <button type="button" onclick="removeFromCart(${item.id}); renderCartPage();" class="shrink-0 p-2 text-neutral-400 hover:text-error-500 hover:bg-error-50 rounded-lg transition-all duration-300">
                            <span class="iconify i-lucide:trash-2 w-5 h-5 block"></span>
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
            </div>`;
    });

    const totalCount = cart.reduce((s, i) => s + i.qty, 0);

    container.innerHTML = `
        <div class="flex flex-col lg:flex-row gap-6 xl:gap-8 relative items-start">
            <div class="flex-1 flex flex-col gap-5 w-full bg-white p-4 sm:p-6 rounded-2xl border border-secondary-200">
                ${itemsHtml}
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
                            <span class="text-neutral-500">Chegirma:</span>
                            <span class="font-medium text-error-500">-0 so'm</span>
                        </div>
                        <div class="flex justify-between items-center text-base">
                            <span class="text-neutral-500">Yetkazib berish:</span>
                            <span class="font-medium text-primary">0 so'm</span>
                        </div>
                        
                        <hr class="border-secondary-200 my-1">
                        
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-primary">Jami:</span>
                            <span class="text-2xl font-bold text-primary">${new Intl.NumberFormat('uz').format(total)} so'm</span>
                        </div>
                    </div>
                    
                    <a href="{{ route('web.checkout') }}" class="font-medium items-center transition-colors py-1.5 gap-1.5 text-inverted bg-primary hover:bg-primary/75 h-14 justify-center rounded-2xl text-lg w-full inline-flex" style="color:#fff;">
                        Xaridni davom ettirish
                    </a>
                </div>
            </div>
        </div>`;
}

document.addEventListener('DOMContentLoaded', renderCartPage);
</script>
@endpush
