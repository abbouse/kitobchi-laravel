@extends('layouts.marketplace')

@section('title', 'Buyurtmani rasmiylashtirish — Kitobchi Marketpleysi')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center u-mb-m">
        <h1 class="h3 fw-black text-dark mb-0">Buyurtmani rasmiylashtirish</h1>
        <a href="{{ route('web.cart') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
            &larr; Savatga qaytish
        </a>
    </div>

    <div class="row g-4" id="kcCheckoutWrapper">
        <!-- Left: Form -->
        <div class="col-lg-7">
            <form id="kcCheckoutForm" class="p-4 bg-white rounded-4 border shadow-sm">
                <h5 class="fw-bold text-dark u-mb-m">1. Qabul qiluvchi ma'lumotlari</h5>
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Ismingiz va familiyangiz *</label>
                    <input type="text" name="customer_name" class="form-control rounded-3 py-2" placeholder="Masalan: Jamshid Karimov" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Telefon raqamingiz *</label>
                    <input type="tel" name="phone_number" class="form-control rounded-3 py-2" placeholder="+998 90 123 45 67" required>
                </div>

                <h5 class="fw-bold text-dark u-my-m">2. Yetkazib berish manzili</h5>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Viloyat / shahar *</label>
                    <select name="region" class="form-select rounded-3 py-2" required>
                        <option value="" disabled selected>Tanlang...</option>
                        @foreach($regions as $region)
                            <option value="{{ $region }}">{{ $region }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Tuman va to'liq manzil *</label>
                    <textarea name="address" class="form-control rounded-3" rows="3" placeholder="Chilonzor tumani, 12-uy, 45-xonadon" required></textarea>
                </div>

                <h5 class="fw-bold text-dark u-my-m">3. To'lov usuli</h5>
                <div class="d-grid gap-2 mb-4">
                    <label class="p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer">
                        <input type="radio" name="payment_method" value="cash" checked class="form-check-input">
                        <div>
                            <div class="fw-bold text-dark">💵 Qabul qilganda naqd yoki karta orqali</div>
                            <small class="text-muted">Kuryer mahsulotni topshirganda to'laysiz</small>
                        </div>
                    </label>
                    <label class="p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer">
                        <input type="radio" name="payment_method" value="click" class="form-check-input">
                        <div>
                            <div class="fw-bold text-dark">🔹 Click App / USSD</div>
                            <small class="text-muted">Click ilovasi orqali to'lov</small>
                        </div>
                    </label>
                    <label class="p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer">
                        <input type="radio" name="payment_method" value="payme" class="form-check-input">
                        <div>
                            <div class="fw-bold text-dark">🟢 Payme</div>
                            <small class="text-muted">Payme ilovasi orqali to'lov</small>
                        </div>
                    </label>
                    <label class="p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer">
                        <input type="radio" name="payment_method" value="uzum" class="form-check-input">
                        <div>
                            <div class="fw-bold text-dark">🟣 Uzum Pay</div>
                            <small class="text-muted">Uzum Pay orqali instant to'lov</small>
                        </div>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold" id="kcSubmitOrderBtn">
                    ✅ Buyurtmani tasdiqlash
                </button>
            </form>
        </div>

        <!-- Right: Summary -->
        <div class="col-lg-5">
            <div class="p-4 bg-white rounded-4 border shadow-sm sticky-top" style="top: 90px;">
                <h5 class="fw-bold text-dark u-mb-m">Buyurtma tarkibi</h5>
                <div id="kcCheckoutItemsList" class="u-mb-m">
                    <!-- Rendered dynamically -->
                </div>
                <hr>
                <div class="d-flex justify-content-between text-muted mb-2">
                    <span>Mahsulotlar:</span>
                    <span class="fw-bold text-dark" id="kcCheckoutSubtotal">0 UZS</span>
                </div>
                <div class="d-flex justify-content-between text-muted mb-3">
                    <span>Yetkazib berish:</span>
                    <span class="fw-bold text-success">20,000 UZS</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between h4 fw-black text-dark mb-0">
                    <span>Jami:</span>
                    <span class="text-primary" id="kcCheckoutGrandTotal">0 UZS</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function renderCheckoutSummary() {
        const listEl = document.getElementById('kcCheckoutItemsList');
        const subtotalEl = document.getElementById('kcCheckoutSubtotal');
        const grandTotalEl = document.getElementById('kcCheckoutGrandTotal');

        if (!kcCartState || kcCartState.length === 0) {
            window.location.href = "{{ route('web.catalog') }}";
            return;
        }

        let total = 0;
        let html = '';

        kcCartState.forEach(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            html += `
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="text-truncate me-2" style="max-width: 220px;">
                        <span class="fw-bold text-dark d-block text-truncate" style="font-size: 13.5px;">${item.name}</span>
                        <small class="text-muted">${item.quantity} x ${new Intl.NumberFormat().format(item.price)} UZS</small>
                    </div>
                    <span class="fw-bold text-dark" style="font-size: 13.5px;">${new Intl.NumberFormat().format(itemTotal)} UZS</span>
                </div>
            `;
        });

        if (listEl) listEl.innerHTML = html;
        if (subtotalEl) subtotalEl.innerText = new Intl.NumberFormat().format(total) + ' UZS';
        if (grandTotalEl) grandTotalEl.innerText = new Intl.NumberFormat().format(total + 20000) + ' UZS';
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderCheckoutSummary();

        const form = document.getElementById('kcCheckoutForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const btn = document.getElementById('kcSubmitOrderBtn');
                if (btn) btn.disabled = true;

                const formData = new FormData(this);
                const payload = {
                    customer_name: formData.get('customer_name'),
                    phone_number: formData.get('phone_number'),
                    region: formData.get('region'),
                    address: formData.get('address'),
                    payment_method: formData.get('payment_method'),
                    cart_items: kcCartState,
                };

                fetch("{{ route('web.checkout.process') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Clear Cart State
                        kcCartState = [];
                        localStorage.removeItem('kc_web_cart');

                        const wrapper = document.getElementById('kcCheckoutWrapper');
                        wrapper.innerHTML = `
                            <div class="col-12">
                                <div class="p-5 bg-white rounded-4 border text-center">
                                    <div style="font-size: 64px;">🎉</div>
                                    <h2 class="fw-black text-success mt-3">Buyurtmangiz muvaffaqiyatli qabul qilindi!</h2>
                                    <p class="text-muted fs-5 mb-3">Buyurtma kodi: <strong class="text-dark">${data.order_code}</strong></p>
                                    <p class="text-muted" style="max-width: 500px; margin: 0 auto 24px;">
                                        Bizning operatorimiz tez orada siz bilan bog'lanadi hamda yetkazib berish tafsilotlarini tasdiqlaydi.
                                    </p>
                                    <a href="{{ route('web.catalog') }}" class="btn btn-primary rounded-pill px-4 fw-bold">
                                        Bosh sahifaga qaytish
                                    </a>
                                </div>
                            </div>
                        `;
                    } else {
                        alert(data.message || 'Xatolik yuz berdi');
                        if (btn) btn.disabled = false;
                    }
                })
                .catch(() => {
                    alert('Tarmoq xatoligi. Qayta urinib ko\'ring.');
                    if (btn) btn.disabled = false;
                });
            });
        }
    });
</script>
@endpush
