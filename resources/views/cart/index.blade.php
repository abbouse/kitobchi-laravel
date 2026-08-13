@extends('layouts.marketplace')

@section('title', 'Savatcha — Kitobchi')

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
            <h1 style="font-size:clamp(1.25rem,4vw,1.75rem);font-weight:800;color:#111827;margin:0;">Savatcha</h1>
        </div>

        <!-- Dynamic cart content -->
        <div id="kcCartPageContent"></div>

    </div>
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
            <div style="text-align:center;padding:4rem 1rem;background:#fff;border-radius:1.5rem;">
                <div class="kc-icon-empty" aria-hidden="true">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                </div>
                <h2 style="font-size:1.25rem;font-weight:700;color:#111827;margin:0 0 0.5rem;">Savatingiz bo'sh</h2>
                <p style="color:#6b7280;font-size:0.9375rem;max-width:360px;margin:0 auto 1.5rem;line-height:1.6;">
                    Katalogdan o'zingizga yoqqan kitob va mahsulotlarni tanlang.
                </p>
                <a href="{{ route('web.catalog') }}" class="kc-primary-btn">
                    Katalogga o'tish
                </a>
            </div>`;
        return;
    }

    let total = 0;
    let itemsHtml = '';

    cart.forEach(item => {
        const itemTotal = item.price * item.qty;
        total += itemTotal;
        itemsHtml += `
            <div style="display:flex;align-items:center;gap:0.875rem;padding:0.875rem;background:#fff;border-radius:0.875rem;margin-bottom:0.5rem;">
                <img src="${item.image}" alt="${item.name}" style="width:64px;height:80px;object-fit:cover;border-radius:0.625rem;flex-shrink:0;border:1px solid #f3f4f6;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.875rem;font-weight:600;color:#111827;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;margin-bottom:0.375rem;">${item.name}</div>
                    <div style="font-size:0.9375rem;font-weight:700;color:var(--color-tima-500);">${new Intl.NumberFormat('uz').format(item.price)} so'm</div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.75rem;flex-shrink:0;">
                    <div style="display:flex;align-items:center;gap:0.375rem;">
                        <button type="button"
                                onclick="updateQty(${item.id}, -1); renderCartPage();"
                                style="width:1.875rem;height:1.875rem;border:1px solid #e5e7eb;border-radius:9999px;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.1em;font-weight:700;">−</button>
                        <span style="min-width:1.5rem;text-align:center;font-weight:700;font-size:1rem;">${item.qty}</span>
                        <button type="button"
                                onclick="updateQty(${item.id}, 1); renderCartPage();"
                                style="width:1.875rem;height:1.875rem;border:1px solid #e5e7eb;border-radius:9999px;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.1em;font-weight:700;">+</button>
                    </div>
                    <div style="font-size:0.9375rem;font-weight:700;color:#111827;white-space:nowrap;">${new Intl.NumberFormat('uz').format(itemTotal)} so'm</div>
                    <button type="button" onclick="removeFromCart(${item.id}); renderCartPage();"
                            style="font-size:0.75rem;color:#9ca3af;background:none;border:none;cursor:pointer;padding:0;transition:color 0.2s;"
                            onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">
                        O'chirish
                    </button>
                </div>
            </div>`;
    });

    const totalCount = cart.reduce((s, i) => s + i.qty, 0);

    container.innerHTML = `
        <div id="kcCartGrid" style="display:grid;grid-template-columns:1fr;gap:1.5rem;align-items:flex-start;">
            <!-- Items -->
            <div>${itemsHtml}</div>

            <!-- Summary -->
            <div>
                <div style="background:#fff;border-radius:1.25rem;padding:1.5rem;border:1px solid #f3f4f6;position:sticky;top:80px;">
                    <h3 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 1.25rem;">Buyurtma xulosasi</h3>

                    <div style="display:flex;flex-direction:column;gap:0.625rem;margin-bottom:1.25rem;">
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;color:#6b7280;">
                            <span>Mahsulotlar soni:</span>
                            <span style="font-weight:600;color:#111827;">${totalCount} ta</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;color:#6b7280;">
                            <span>Mahsulotlar:</span>
                            <span style="font-weight:600;color:#111827;">${new Intl.NumberFormat('uz').format(total)} so'm</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;color:#6b7280;">
                            <span>Yetkazib berish:</span>
                            <span style="font-weight:600;color:#16a34a;">20,000 so'm</span>
                        </div>
                    </div>

                    <div style="border-top:1px solid #f3f4f6;padding-top:1rem;display:flex;justify-content:space-between;font-size:1.125rem;font-weight:800;color:#111827;margin-bottom:1.25rem;">
                        <span>Jami summasi:</span>
                        <span style="color:var(--color-tima-500);">${new Intl.NumberFormat('uz').format(total + 20000)} so'm</span>
                    </div>

                    <a href="{{ route('web.checkout') }}" class="kc-primary-btn" style="width:100%;">
                        Buyurtmani rasmiylashtirish →
                    </a>

                    <a href="{{ route('web.catalog') }}"
                       style="display:flex;align-items:center;justify-content:center;margin-top:0.75rem;font-size:0.875rem;color:#6b7280;text-decoration:none;gap:0.25rem;transition:color 0.2s;"
                       onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'">
                        ← Xaridni davom ettirish
                    </a>
                </div>
            </div>
        </div>`;

    // Responsive grid after render
    const grid = document.getElementById('kcCartGrid');
    if (grid && window.innerWidth >= 768) {
        grid.style.gridTemplateColumns = '1fr 360px';
    }
}

document.addEventListener('DOMContentLoaded', renderCartPage);
window.addEventListener('resize', function() {
    const grid = document.getElementById('kcCartGrid');
    if (grid) {
        grid.style.gridTemplateColumns = window.innerWidth >= 768 ? '1fr 360px' : '1fr';
    }
});
</script>
@endpush
