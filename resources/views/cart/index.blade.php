@extends('layouts.marketplace')

@section('title', 'Savat — Kitobchi Marketpleysi')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center u-mb-m">
        <h1 class="h3 fw-black text-dark mb-0">Xaridlar savati</h1>
        <a href="{{ route('web.catalog') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
            &larr; Xaridni davom ettirish
        </a>
    </div>

    <div class="row g-4" id="kcCartPageContainer">
        <!-- Rendered dynamically by client JS script -->
    </div>
</div>
@endsection

@push('scripts')
<script>
    function renderCartPage() {
        const container = document.getElementById('kcCartPageContainer');
        if (!container) return;

        if (kcCartState.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="p-5 bg-white rounded-4 border text-center">
                        <div style="font-size: 56px;">🛒</div>
                        <h3 class="fw-bold text-dark mt-3">Savatingiz hozircha bo'sh</h3>
                        <p class="text-muted mb-4">Katalogdan o'zingizga yoqqan kitob va mahsulotlarni tanlang.</p>
                        <a href="{{ route('web.catalog') }}" class="btn btn-primary rounded-pill px-4 fw-bold">
                            Katalogga o'tish
                        </a>
                    </div>
                </div>
            `;
            return;
        }

        let total = 0;
        let itemsHtml = '';

        kcCartState.forEach(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            itemsHtml += `
                <div class="p-3 bg-white rounded-3 border u-mb-s d-flex align-items-center gap-3">
                    <img src="${item.image}" alt="${item.name}" style="width: 70px; height: 95px; object-fit: cover; border-radius: 8px;">
                    <div class="flex-grow-1 min-width-0">
                        <h4 class="h6 fw-bold text-dark mb-1 text-truncate">${item.name}</h4>
                        <div class="text-primary fw-bold">${new Intl.NumberFormat().format(item.price)} UZS</div>
                    </div>
                    <div class="kc-qty-control">
                        <button type="button" class="kc-qty-btn" onclick="updateCartQty(${item.id}, -1); renderCartPage();">-</button>
                        <span class="kc-qty-val">${item.quantity}</span>
                        <button type="button" class="kc-qty-btn" onclick="updateCartQty(${item.id}, 1); renderCartPage();">+</button>
                    </div>
                    <div class="fw-extrabold text-dark text-end ms-3" style="min-width: 100px;">
                        ${new Intl.NumberFormat().format(itemTotal)} UZS
                    </div>
                </div>
            `;
        });

        container.innerHTML = `
            <div class="col-lg-8">
                ${itemsHtml}
            </div>
            <div class="col-lg-4">
                <div class="p-4 bg-white rounded-4 border shadow-sm sticky-top" style="top: 90px;">
                    <h5 class="fw-bold text-dark u-mb-m">Buyurtma xulosasi</h5>
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Mahsulotlar soni:</span>
                        <span class="fw-bold text-dark">${kcCartState.reduce((s, i) => s + i.quantity, 0)} ta</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted mb-3">
                        <span>Yetkazib berish:</span>
                        <span class="fw-bold text-success">20,000 UZS</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between h5 fw-black text-dark mb-4">
                        <span>Jami summasi:</span>
                        <span class="text-primary">${new Intl.NumberFormat().format(total + 20000)} UZS</span>
                    </div>
                    <a href="{{ route('web.checkout') }}" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold">
                        Buyurtmani rasmiylashtirish &rarr;
                    </a>
                </div>
            </div>
        `;
    }

    document.addEventListener('DOMContentLoaded', renderCartPage);
</script>
@endpush
