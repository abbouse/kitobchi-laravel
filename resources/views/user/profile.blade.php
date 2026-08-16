@extends('layouts.marketplace')

@section('title', 'Mening profilim — Kitobchi')

@push('styles')
<style>
    /* Desktop'da chap ustun (foydalanuvchi kartasi) + o'ng ustun (manzillar,
       buyurtmalar) yonma-yon — avval bu JS orqali (DOMContentLoaded'da
       window.innerWidth tekshirib) qo'yilardi, shuning uchun sahifa birinchi
       chizilganda bir zumga bitta ustun ko'rinib, keyin "sakrab" ikkiga
       bo'linardi (FOUC), va oyna o'lchami keyin o'zgarsa umuman moslashmasdi.
       Endi oddiy CSS media query — darhol to'g'ri, resize'da ham ishlaydi. */
    @media (min-width: 768px) {
        #kcProfileGrid { grid-template-columns: 300px 1fr; }
        #kcProfileGrid > :first-child { position: sticky; top: 1.5rem; align-self: start; }
    }
</style>
@endpush

@section('content')
<div class="kc-page-surface" style="min-height:80dvh;padding:2rem 0;background:#f8fafc;">
    <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
        
        <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;" id="kcProfileGrid">
            <!-- User Info Card -->
            <div class="kc-panel-card">
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
                    <div style="width:4.5rem;height:4.5rem;border-radius:9999px;background:var(--color-tima-100);color:var(--color-tima-500);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;flex-shrink:0;">
                        {{ strtoupper(substr($user->name ?: $user->phone_number, 0, 1)) }}
                    </div>
                    <div>
                        <h2 style="font-size:1.25rem;font-weight:800;color:#0f172a;margin:0 0 0.25rem;">
                            {{ $user->name ?: 'Foydalanuvchi' }}
                        </h2>
                        <p style="color:#64748b;font-size:0.875rem;margin:0;">
                            +{{ $user->phone_number }}
                        </p>
                    </div>
                </div>

                <hr style="border:0;border-top:1px solid #f1f5f9;margin:1.25rem 0;">

                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    <form action="{{ route('web.auth.logout') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" 
                                style="width:100%;display:flex;align-items:center;justify-content:center;gap:0.5rem;padding:0.75rem 1.25rem;background:#fef2f2;color:#ef4444;border:none;border-radius:9999px;font-weight:700;font-size:0.875rem;cursor:pointer;transition:all 0.2s;"
                                onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Tizimdan chiqish
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column -->
            <div style="display:flex;flex-direction:column;gap:1.5rem;min-width:0;">
                
                <!-- Addresses / Locations -->
                <div class="kc-panel-card">
                    <h3 style="font-size:1.125rem;font-weight:800;color:#0f172a;margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        Mening manzillarim
                    </h3>

                    @if(count($locations) > 0)
                        <div style="display:flex;flex-direction:column;gap:0.75rem;margin-bottom:1.5rem;">
                            @foreach($locations as $loc)
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem;border:1px solid {{ $user->mainAddressID == $loc->id ? 'var(--color-tima-500)' : '#e2e8f0' }};border-radius:0.75rem;background:{{ $user->mainAddressID == $loc->id ? '#eff6ff' : '#fff' }};">
                                    <div style="display:flex;align-items:center;gap:0.75rem;flex:1;">
                                        <svg width="20" height="20" fill="none" stroke="{{ $user->mainAddressID == $loc->id ? 'var(--color-tima-500)' : '#94a3b8' }}" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                        <div style="font-size:0.875rem;color:#334155;">{{ $loc->fullAddress }}</div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:0.5rem;">
                                        @if($user->mainAddressID != $loc->id)
                                            <button onclick="setMainLocation({{ $loc->id }})" style="padding:0.375rem 0.75rem;font-size:0.75rem;font-weight:600;color:var(--color-tima-600);background:#eff6ff;border:none;border-radius:0.375rem;cursor:pointer;">Asosiy qilish</button>
                                            <button onclick="deleteLocation({{ $loc->id }})" style="padding:0.375rem 0.75rem;font-size:0.75rem;font-weight:600;color:#ef4444;background:#fef2f2;border:none;border-radius:0.375rem;cursor:pointer;">O'chirish</button>
                                        @else
                                            <span style="font-size:0.75rem;font-weight:700;color:var(--color-tima-500);background:#dbeafe;padding:0.25rem 0.5rem;border-radius:9999px;">Asosiy</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Add New Location Form -->
                    <div style="border:1px dashed #cbd5e1;border-radius:0.75rem;padding:1.25rem;background:#f8fafc;">
                        <h4 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 1rem;">Yangi manzil qo'shish</h4>
                        <div id="yandex-map" style="width:100%;height:300px;border-radius:0.5rem;background:#e2e8f0;margin-bottom:1rem;overflow:hidden;"></div>
                        <form id="newLocationForm" style="display:flex;flex-direction:column;gap:0.75rem;margin:0;">
                            <input type="hidden" id="locLat" name="lat">
                            <input type="hidden" id="locLon" name="lon">
                            <div>
                                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#475569;margin-bottom:0.25rem;">Manzil to'liq nomi</label>
                                <input type="text" id="locAddress" name="fullAddress" required
                                    style="width:100%;padding:0.75rem 1rem;border:1px solid #cbd5e1;border-radius:0.5rem;font-size:0.875rem;outline:none;" 
                                    placeholder="Xaritadan tanlang yoki o'zingiz kiriting">
                            </div>
                            <button type="submit" class="kc-primary-btn" style="width:100%;padding:0.75rem;font-size:0.875rem;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;gap:0.5rem;">
                                Manzilni saqlash
                            </button>
                        </form>
                    </div>
                </div>

            <!-- Orders History -->
            <div class="kc-panel-card">
                <h3 style="font-size:1.125rem;font-weight:800;color:#0f172a;margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
                    </svg>
                    Mening buyurtmalarim ({{ count($orders) }})
                </h3>

                @if(count($orders) === 0)
                    <div style="text-align:center;padding:3rem 1rem;">
                        <div class="kc-icon-empty" aria-hidden="true">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
                            </svg>
                        </div>
                        <h4 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">Sizda hozircha buyurtmalar yo'q</h4>
                        <p style="color:#64748b;font-size:0.875rem;margin:0 0 1.5rem;">Katalogdan o'zingizga yoqqan kitoblarni xarid qilishingiz mumkin.</p>
                        <a href="{{ route('web.catalog') }}" class="kc-primary-btn">
                            Katalogga o'tish
                        </a>
                    </div>
                @else
                    <div style="display:flex;flex-direction:column;gap:1rem;">
                        @foreach($orders as $order)
                            <div style="padding:1.25rem;border:1px solid #f1f5f9;border-radius:0.875rem;background:#fafafa;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.5rem;">
                                    <div>
                                        <span style="font-weight:800;color:#0f172a;">Buyurtma #{{ $order->order_number ?? $order->id }}</span>
                                        <span style="color:#64748b;font-size:0.8125rem;margin-left:0.5rem;">
                                            {{ optional($order->created_at)->format('d.m.Y, H:i') }}
                                        </span>
                                    </div>
                                    <span style="padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.75rem;font-weight:700;
                                        @if($order->paymentStatus == 2) background:#dcfce7;color:#15803d;
                                        @elseif($order->paymentStatus == 1) background:#fef9c3;color:#a16207;
                                        @else background:#f1f5f9;color:#475569; @endif">
                                        @if($order->paymentStatus == 2) Muvaffaqiyatli to'langan
                                        @elseif($order->paymentStatus == 1) Kutilmoqda
                                        @else Qabul qilindi @endif
                                    </span>
                                </div>

                                <div style="font-size:0.875rem;color:#334155;margin-bottom:0.75rem;">
                                    <strong>Manzil:</strong> {{ $order->address ?? $order->city ?? 'Belgilanmagan' }}
                                </div>

                                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e2e8f0;padding-top:0.75rem;">
                                    <span style="font-size:0.875rem;color:#64748b;">Jami summa:</span>
                                    <span style="font-size:1.125rem;font-weight:800;color:var(--color-tima-500);">
                                        {{ number_format($order->summa ?? $order->price ?? 0, 0, ',', ' ') }} so'm
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
// Yandex Maps Logic
@if(config('services.yandex_maps.key'))
ymaps.ready(initYandexMap);

function initYandexMap() {
    const map = new ymaps.Map("yandex-map", {
        center: [41.311081, 69.240562], // Tashkent
        zoom: 12,
        controls: ['zoomControl', 'searchControl']
    });

    let placemark = null;

    map.events.add('click', function (e) {
        const coords = e.get('coords');
        updatePlacemark(coords);
    });

    function updatePlacemark(coords) {
        document.getElementById('locLat').value = coords[0];
        document.getElementById('locLon').value = coords[1];

        if (placemark) {
            placemark.geometry.setCoordinates(coords);
        } else {
            placemark = new ymaps.Placemark(coords, {}, { preset: 'islands#redDotIcon', draggable: true });
            map.geoObjects.add(placemark);
            placemark.events.add('dragend', function () {
                updatePlacemark(placemark.geometry.getCoordinates());
            });
        }

        // Reverse geocoding
        ymaps.geocode(coords).then(function (res) {
            const firstGeoObject = res.geoObjects.get(0);
            if (firstGeoObject) {
                document.getElementById('locAddress').value = firstGeoObject.getAddressLine();
            }
        });
    }
}
@endif

// Location AJAX Actions
document.getElementById('newLocationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const lat = document.getElementById('locLat').value;
    const lon = document.getElementById('locLon').value;
    const address = document.getElementById('locAddress').value;

    if(!lat || !lon) {
        alert("Iltimos xaritadan manzilni belgilang!");
        return;
    }

    fetch("{{ route('web.profile.location.add') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ lat: lat, lon: lon, fullAddress: address })
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            window.location.reload();
        } else {
            alert(data.message || "Xatolik yuz berdi");
        }
    })
    .catch(err => console.error(err));
});

function deleteLocation(id) {
    if(!confirm("Manzilni o'chirmoqchimisiz?")) return;
    fetch(`/profile/location/${id}`, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') window.location.reload();
        else alert(data.message || "Xatolik");
    });
}

function setMainLocation(id) {
    fetch(`/profile/location/${id}/main`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') window.location.reload();
        else alert(data.message || "Xatolik");
    });
}
</script>
@if(config('services.yandex_maps.key'))
<script src="https://api-maps.yandex.ru/2.1/?apikey={{ config('services.yandex_maps.key') }}&lang={{ config('services.yandex_maps.lang', 'ru_RU') }}"></script>
@endif
@endpush
