@extends('layouts.marketplace')

@section('title', 'Buyurtmani rasmiylashtirish — Kitobchi')

@section('content')
<div class="kc-page-surface" style="padding:1.5rem 0;">
    <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">

        <!-- Page Header -->
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem;">
            <a href="{{ route('web.catalog') }}"
               style="width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:9999px;text-decoration:none;color:#374151;transition:all 0.2s;flex-shrink:0;"
               onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 style="font-size:clamp(1.25rem,4vw,1.75rem);font-weight:800;color:#111827;margin:0;">
                Buyurtmani rasmiylashtirish
            </h1>
        </div>

        <!-- Main layout: form (left) + summary (right) -->
        <div id="kcCheckoutWrapper" style="display:grid;grid-template-columns:1fr;gap:1.5rem;align-items:flex-start;">

            <!-- ====== LEFT: FORM ====== -->
            <div>
                <form id="kcCheckoutForm" autocomplete="on">

                    <!-- Section 1: Customer Info -->
                    <div class="kc-form-card" style="margin-bottom:1rem;">
                        <h2 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem;">
                            <span class="kc-step-badge">1</span>
                            Qabul qiluvchi ma'lumotlari
                        </h2>

                        <div style="display:flex;flex-direction:column;gap:1rem;">
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.375rem;">
                                    Ism va familiya <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="text" name="customer_name" autocomplete="name"
                                       placeholder="Masalan: Jamshid Karimov"
                                       required
                                       class="kc-input"
                                       onfocus="this.style.borderColor='var(--color-tima-500)'" onblur="this.style.borderColor='#e5e7eb'">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.375rem;">
                                    Telefon raqam <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="tel" name="phone_number" autocomplete="tel"
                                       placeholder="+998 90 123 45 67"
                                       required
                                       class="kc-input"
                                       onfocus="this.style.borderColor='var(--color-tima-500)'" onblur="this.style.borderColor='#e5e7eb'">
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Delivery Address -->
                    <div class="kc-form-card" style="margin-bottom:1rem;">
                        <h2 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem;">
                            <span class="kc-step-badge">2</span>
                            Yetkazib berish manzili
                        </h2>

                        <div style="display:flex;flex-direction:column;gap:1rem;">
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.375rem;">
                                    Viloyat / Shahar <span style="color:#ef4444;">*</span>
                                </label>
                                <div style="position:relative;">
                                    <select name="region" required
                                            class="kc-select"
                                            style="padding-right:2.5rem;appearance:none;-webkit-appearance:none;cursor:pointer;"
                                            onfocus="this.style.borderColor='var(--color-tima-500)'" onblur="this.style.borderColor='#e5e7eb'">
                                        <option value="" disabled selected>Tanlang...</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region }}">{{ $region }}</option>
                                        @endforeach
                                    </select>
                                    <div style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);pointer-events:none;color:#6b7280;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.375rem;">
                                    Tuman va to'liq manzil <span style="color:#ef4444;">*</span>
                                </label>
                                <textarea name="address" rows="3" required
                                          placeholder="Chilonzor tumani, 12-uy, 45-xonadon"
                                          class="kc-textarea"
                                          onfocus="this.style.borderColor='var(--color-tima-500)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Payment Method -->
                    <div class="kc-form-card" style="margin-bottom:1rem;">
                        <h2 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem;">
                            <span class="kc-step-badge">3</span>
                            To'lov usuli
                        </h2>

                        <div style="display:flex;flex-direction:column;gap:0.75rem;">

                            <!-- Cash option -->
                            <label id="kcPayCashLabel"
                                   style="display:flex;align-items:flex-start;gap:1rem;padding:1rem;border:2px solid var(--color-tima-500);border-radius:1rem;cursor:pointer;background:var(--color-tima-50);transition:all 0.2s;">
                                <input type="radio" name="payment_method" value="cash" checked
                                       onchange="kcSelectPayment(this)"
                                       style="width:1.25rem;height:1.25rem;accent-color:var(--color-tima-500);margin-top:2px;flex-shrink:0;cursor:pointer;">
                                <div>
                                    <div style="font-size:0.9375rem;font-weight:700;color:#111827;display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem;">
                                        Qabul qilganda naqd to'lov
                                    </div>
                                    <div style="font-size:0.8125rem;color:#6b7280;line-height:1.5;">
                                        Kuryer mahsulotni yetkazib berganda naqd pulda to'laysiz
                                    </div>
                                </div>
                            </label>

                            <!-- Card option -->
                            <label id="kcPayCardLabel"
                                   style="display:flex;align-items:flex-start;gap:1rem;padding:1rem;border:2px solid #e5e7eb;border-radius:1rem;cursor:pointer;background:#fff;transition:all 0.2s;">
                                <input type="radio" name="payment_method" value="card"
                                       onchange="kcSelectPayment(this)"
                                       style="width:1.25rem;height:1.25rem;accent-color:var(--color-tima-500);margin-top:2px;flex-shrink:0;cursor:pointer;">
                                <div>
                                    <div style="font-size:0.9375rem;font-weight:700;color:#111827;display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem;">
                                        Karta orqali to'lov
                                    </div>
                                    <div style="font-size:0.8125rem;color:#6b7280;line-height:1.5;">
                                        Kuryerga karta orqali yoki karta raqamiga o'tkazib to'laysiz
                                    </div>
                                </div>
                            </label>

                        </div>
                    </div>

                    <!-- Submit Button (mobile: shown here, desktop: in summary box) -->
                    <div id="kcSubmitMobile">
                        <button type="submit" id="kcSubmitOrderBtn" class="kc-primary-btn" style="width:100%;height:3.5rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            Buyurtmani tasdiqlash
                        </button>
                    </div>

                </form>
            </div>

            <!-- ====== RIGHT: ORDER SUMMARY ====== -->
            <div>
                <div class="kc-panel-card" style="position:sticky;top:80px;">
                    <h3 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 1.25rem;">Buyurtma tarkibi</h3>

                    <!-- Items list -->
                    <div id="kcCheckoutItemsList" style="margin-bottom:1rem;display:flex;flex-direction:column;gap:0.75rem;">
                        <!-- JS rendered -->
                    </div>

                    <div style="border-top:1px solid #f3f4f6;padding-top:1rem;display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1.25rem;">
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;color:#6b7280;">
                            <span>Mahsulotlar:</span>
                            <span id="kcCheckoutSubtotal" style="font-weight:600;color:#111827;">0 so'm</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;color:#6b7280;">
                            <span>Yetkazib berish:</span>
                            <span style="font-weight:600;color:#16a34a;">20,000 so'm</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:1.125rem;font-weight:800;color:#111827;padding-top:0.75rem;border-top:1px solid #f3f4f6;margin-top:0.25rem;">
                            <span>Jami:</span>
                            <span id="kcCheckoutGrandTotal" style="color:var(--color-tima-500);">0 so'm</span>
                        </div>
                    </div>

                    <!-- Submit (desktop, in summary) -->
                    <button type="submit" form="kcCheckoutForm" class="kc-primary-btn" style="width:100%;height:3.25rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                        Buyurtmani tasdiqlash
                    </button>

                    <!-- Trust badges -->
                    <div style="display:flex;flex-direction:column;gap:0.5rem;margin-top:1rem;padding-top:1rem;border-top:1px solid #f3f4f6;">
                        <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#6b7280;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Xavfsiz buyurtma
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#6b7280;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                            Original mahsulotlar kafolati
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media(min-width: 768px) {
        #kcCheckoutWrapper { grid-template-columns: 1fr 380px !important; }
        #kcSubmitMobile { display: none !important; }
    }
</style>
@endsection

@push('scripts')
<script>
    function kcSelectPayment(radio) {
        // Reset all
        document.querySelectorAll('[id^="kcPay"]').forEach(el => {
            el.style.borderColor = '#e5e7eb';
            el.style.background = '#fff';
        });
        // Highlight selected
        const label = radio.closest('label');
        if (label) {
            label.style.borderColor = 'var(--color-tima-500)';
            label.style.background = 'var(--color-tima-50)';
        }
    }

    function renderCheckoutSummary() {
        const listEl = document.getElementById('kcCheckoutItemsList');
        const subtotalEl = document.getElementById('kcCheckoutSubtotal');
        const grandTotalEl = document.getElementById('kcCheckoutGrandTotal');

        const cart = kcCart || JSON.parse(localStorage.getItem('kc_cart') || '[]');

        if (!cart || cart.length === 0) {
            window.location.href = "{{ route('web.catalog') }}";
            return;
        }

        let total = 0;
        let html = '';

        cart.forEach(item => {
            const itemTotal = item.price * item.qty;
            total += itemTotal;
            html += `<div style="display:flex;align-items:center;gap:0.75rem;">
                <img src="${item.image}" alt="${item.name}" style="width:44px;height:56px;object-fit:cover;border-radius:0.5rem;flex-shrink:0;border:1px solid #f3f4f6;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.8125rem;font-weight:500;color:#111827;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.3;">${item.name}</div>
                    <div style="font-size:0.75rem;color:#9ca3af;margin-top:2px;">${item.qty} x ${new Intl.NumberFormat('uz').format(item.price)} so'm</div>
                </div>
                <div style="font-size:0.875rem;font-weight:700;color:#111827;white-space:nowrap;">${new Intl.NumberFormat('uz').format(itemTotal)} so'm</div>
            </div>`;
        });

        if (listEl) listEl.innerHTML = html;
        if (subtotalEl) subtotalEl.textContent = new Intl.NumberFormat('uz').format(total) + ' so\'m';
        if (grandTotalEl) grandTotalEl.textContent = new Intl.NumberFormat('uz').format(total + 20000) + ' so\'m';
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderCheckoutSummary();

        const form = document.getElementById('kcCheckoutForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const btn = document.getElementById('kcSubmitOrderBtn');
                const allBtns = document.querySelectorAll('[form="kcCheckoutForm"], #kcSubmitOrderBtn');
                allBtns.forEach(b => { b.disabled = true; b.style.opacity = '0.7'; });

                const formData = new FormData(this);
                const cart = kcCart || JSON.parse(localStorage.getItem('kc_cart') || '[]');
                const payload = {
                    customer_name: formData.get('customer_name'),
                    phone_number: formData.get('phone_number'),
                    region: formData.get('region'),
                    address: formData.get('address'),
                    payment_method: formData.get('payment_method'),
                    cart_items: cart,
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
                        // Clear cart
                        if (typeof kcCart !== 'undefined') kcCart = [];
                        localStorage.removeItem('kc_cart');

                        document.getElementById('kcCheckoutWrapper').innerHTML = `
                            <div style="grid-column:1/-1;text-align:center;padding:3rem 1rem;background:#fff;border-radius:1.5rem;">
                                <div style="font-size:4rem;margin-bottom:1rem;">🎉</div>
                                <h2 style="font-size:1.5rem;font-weight:800;color:#111827;margin:0 0 0.75rem;">Buyurtmangiz qabul qilindi!</h2>
                                <p style="color:#6b7280;font-size:1rem;margin:0 0 0.5rem;">Buyurtma kodi: <strong style="color:var(--color-tima-500);font-size:1.125rem;">${data.order_code}</strong></p>
                                <p style="color:#9ca3af;font-size:0.9375rem;max-width:400px;margin:0 auto 2rem;line-height:1.6;">
                                    Operatorimiz tez orada siz bilan bog'lanadi va yetkazib berish tafsilotlarini tasdiqlaydi.
                                </p>
                                <a href="{{ route('web.catalog') }}"
                                   style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.875rem 2rem;background:var(--color-tima-500);color:#fff;border-radius:9999px;font-weight:700;font-size:1rem;text-decoration:none;">
                                    Bosh sahifaga qaytish
                                </a>
                            </div>`;
                    } else {
                        alert(data.message || 'Xatolik yuz berdi');
                        allBtns.forEach(b => { b.disabled = false; b.style.opacity = '1'; });
                    }
                })
                .catch(() => {
                    alert("Tarmoq xatoligi. Qayta urinib ko'ring.");
                    allBtns.forEach(b => { b.disabled = false; b.style.opacity = '1'; });
                });
            });
        }
    });
</script>
@endpush
