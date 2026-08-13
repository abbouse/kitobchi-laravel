<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Читай-город uslubidagi Kitobchi marketpleysi')</title>
    
    @stack('meta')

    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    @vite(['resources/css/kitobchi-marketplace.css'])
    
    @stack('styles')
</head>
<body class="kc-cg-body">

    <!-- Chitai-Gorod Top Location & Utility Bar -->
    <div class="cg-top-bar d-none d-md-block">
        <div class="container">
            <div class="cg-top-bar-inner">
                <div class="d-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>Toshkent bo'ylab yetkazib berish</span>
                </div>
                <div class="cg-top-links">
                    <a href="{{ route('web.catalog') }}" class="cg-top-link">Kataloglar</a>
                    <a href="{{ route('legal.terms') }}" class="cg-top-link">Yetkazib berish va to'lov</a>
                    <a href="{{ route('contact.index') }}" class="cg-top-link">Yordam</a>
                    <a href="tel:+998712000000" class="cg-top-link fw-bold">+998 71 200 00 00</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Chitai-Gorod Main Navigation Sticky Header -->
    <header class="cg-header">
        <div class="container">
            <div class="cg-header-main">
                
                <!-- Logo -->
                <a href="{{ url('/') }}" class="cg-logo-wrap">
                    <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi Logo" class="cg-logo-img">
                </a>

                <!-- Catalog Button (Solid Primary) -->
                <a href="{{ route('web.catalog') }}" class="cg-catalog-btn d-none d-md-inline-flex">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    <span>Katalog</span>
                </a>

                <!-- Search Input -->
                <div class="cg-search-box">
                    <form action="{{ route('web.catalog') }}" method="GET" id="kcSearchForm">
                        <input type="text" name="search" class="cg-search-input" id="kcSearchInput" placeholder="Muallif, nom yoki janr bo'yicha qidirish..." value="{{ request('search') }}" autocomplete="off">
                        <button type="submit" class="cg-search-btn" aria-label="Qidirish">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        </button>
                    </form>
                    <!-- Instant Live Search Dropdown Popup -->
                    <div class="kc-search-results-popup" id="kcSearchPopup"></div>
                </div>

                <!-- Right Controls (Chitai-Gorod Style Control Icons) -->
                <div class="cg-controls">
                    <a href="{{ route('web.catalog') }}" class="cg-control-btn d-none d-sm-flex">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        <span>Sevimlilar</span>
                    </a>

                    <button type="button" class="cg-control-btn" onclick="toggleCartDrawer(true)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        <span>Savatcha</span>
                        <span class="cg-badge" id="kcCartCountBadge">0</span>
                    </button>
                </div>

            </div>
        </div>
    </header>

    <!-- Chitai-Gorod Horizontal Category Pills Strip -->
    <div class="cg-pills-bar">
        <div class="container">
            <div class="d-flex align-items-center gap-2 overflow-x-auto">
                <a href="{{ route('web.catalog') }}" class="cg-pill-item {{ !request('category') ? 'active' : '' }}">
                    🔥 Barcha kitoblar
                </a>

                @php
                    try {
                        $topCategories = Cache::remember('web_top_categories_pills', 600, function() {
                            return \App\Models\BookCategories::where('status', true)->orderBy('name')->take(10)->get();
                        });
                    } catch (\Throwable $e) {
                        $topCategories = collect();
                    }
                @endphp

                @foreach($topCategories as $cat)
                    <a href="{{ route('web.catalog', ['category' => $cat->id]) }}" class="cg-pill-item {{ request('category') == $cat->id ? 'active' : '' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="kc-cg-main py-4">
        @yield('content')
    </main>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="cg-mobile-bar">
        <a href="{{ url('/') }}" class="cg-mobile-link {{ request()->is('/') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            <span>Bosh sahifa</span>
        </a>
        <a href="{{ route('web.catalog') }}" class="cg-mobile-link {{ request()->is('catalog*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>Katalog</span>
        </a>
        <a href="javascript:void(0)" onclick="toggleCartDrawer(true)" class="cg-mobile-link">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span>Savatcha</span>
        </a>
    </nav>

    <!-- Cart Drawer Slide-over -->
    <div class="kc-drawer-bg" id="kcCartDrawerOverlay" onclick="if(event.target === this) toggleCartDrawer(false)">
        <div class="kc-drawer-box">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="fw-black text-dark mb-0">Savatcha</h5>
                <button type="button" class="btn-close" onclick="toggleCartDrawer(false)"></button>
            </div>
            <div class="p-3 flex-grow-1 overflow-y-auto" id="kcCartDrawerBody">
                <div class="text-center text-muted py-5">Savatchangiz bo'sh</div>
            </div>
            <div class="p-3 border-top bg-white">
                <div class="d-flex justify-content-between h5 fw-black text-dark mb-3">
                    <span>Jami:</span>
                    <span class="text-primary" id="kcCartTotalSum">0 UZS</span>
                </div>
                <a href="{{ route('web.checkout') }}" class="btn btn-primary btn-lg w-100 rounded-3 fw-bold">
                    Buyurtmani rasmiylashtirish &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Chitai-Gorod Style Footer -->
    <footer class="bg-white border-top mt-5 py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <a href="{{ url('/') }}" class="cg-logo-wrap mb-3">
                        <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi Logo" class="cg-logo-img">
                    </a>
                    <p class="text-muted small" style="line-height: 1.6;">
                        Читай-город uslubidagi original kitoblar va o'quv qurollari internet-do'koni. O'zbekiston bo'ylab tezkor yetkazib berish.
                    </p>
                </div>

                <div class="col-6 col-lg-3">
                    <h6 class="fw-bold text-dark mb-3">Kataloglar</h6>
                    <ul class="list-unstyled text-muted small" style="line-height: 2;">
                        <li><a href="{{ route('web.catalog') }}" class="text-muted text-decoration-none">Barcha kitoblar</a></li>
                        <li><a href="{{ route('legal.terms') }}" class="text-muted text-decoration-none">Yetkazib berish va to'lov</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-3">
                    <h6 class="fw-bold text-dark mb-3">Ma'lumotlar</h6>
                    <ul class="list-unstyled text-muted small" style="line-height: 2;">
                        <li><a href="{{ route('legal.privacy') }}" class="text-muted text-decoration-none">Maxfiylik siyosati</a></li>
                        <li><a href="{{ route('legal.terms') }}" class="text-muted text-decoration-none">Foydalanish shartlari</a></li>
                        <li><a href="{{ route('contact.index') }}" class="text-muted text-decoration-none">Kontaktlar</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-top mt-4 pt-3 text-center text-muted small">
                &copy; {{ date('Y') }} Kitobchi. Barcha huquqlar himoyalangan.
            </div>
        </div>
    </footer>

    <!-- Client Interactive JS -->
    <script>
        let kcCartState = JSON.parse(localStorage.getItem('kc_web_cart') || '[]');

        function saveCartState() {
            localStorage.setItem('kc_web_cart', JSON.stringify(kcCartState));
            updateCartCounter();
            renderCartDrawer();
        }

        function updateCartCounter() {
            const totalCount = kcCartState.reduce((sum, item) => sum + item.quantity, 0);
            const badge = document.getElementById('kcCartCountBadge');
            if (badge) badge.innerText = totalCount;
        }

        function addToCart(productId, name, price, image) {
            const existing = kcCartState.find(item => item.id === productId);
            if (existing) {
                existing.quantity += 1;
            } else {
                kcCartState.push({ id: productId, name, price, image, quantity: 1 });
            }
            saveCartState();
            toggleCartDrawer(true);
        }

        function updateCartQty(productId, delta) {
            const index = kcCartState.findIndex(item => item.id === productId);
            if (index !== -1) {
                kcCartState[index].quantity += delta;
                if (kcCartState[index].quantity <= 0) {
                    kcCartState.splice(index, 1);
                }
                saveCartState();
            }
        }

        function toggleCartDrawer(open) {
            const drawer = document.getElementById('kcCartDrawerOverlay');
            if (drawer) {
                if (open) {
                    renderCartDrawer();
                    drawer.classList.add('active');
                } else {
                    drawer.classList.remove('active');
                }
            }
        }

        function renderCartDrawer() {
            const body = document.getElementById('kcCartDrawerBody');
            const totalSumEl = document.getElementById('kcCartTotalSum');
            if (!body) return;

            if (kcCartState.length === 0) {
                body.innerHTML = `<div class="text-center text-muted py-5">
                    <div style="font-size: 40px;">🛒</div>
                    <div class="fw-bold mt-2">Savatchangiz bo'sh</div>
                </div>`;
                if (totalSumEl) totalSumEl.innerText = '0 UZS';
                return;
            }

            let html = '';
            let total = 0;

            kcCartState.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                html += `
                    <div class="d-flex align-items-center gap-3 p-2 bg-light rounded-3 mb-2">
                        <img src="${item.image}" alt="${item.name}" style="width: 50px; height: 68px; object-fit: cover; border-radius: 6px;">
                        <div class="flex-grow-1 min-width-0">
                            <h6 class="fw-bold text-dark mb-1 text-truncate" style="font-size: 13px;">${item.name}</h6>
                            <div class="text-primary fw-bold" style="font-size: 13.5px;">${new Intl.NumberFormat().format(item.price)} so'm</div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="updateCartQty(${item.id}, -1)">-</button>
                                <span class="fw-bold small">${item.quantity}</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="updateCartQty(${item.id}, 1)">+</button>
                            </div>
                        </div>
                    </div>
                `;
            });

            body.innerHTML = html;
            if (totalSumEl) totalSumEl.innerText = new Intl.NumberFormat().format(total) + ' so\'m';
        }

        // Live Search
        const searchInput = document.getElementById('kcSearchInput');
        const searchPopup = document.getElementById('kcSearchPopup');

        if (searchInput && searchPopup) {
            let searchTimeout = null;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                if (query.length < 2) {
                    searchPopup.classList.remove('active');
                    searchPopup.innerHTML = '';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`/catalog?search=${encodeURIComponent(query)}&ajax=1`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.items && data.items.length > 0) {
                                let popupHtml = '';
                                data.items.forEach(item => {
                                    popupHtml += `
                                        <a href="${item.url}" class="kc-search-item">
                                            <img src="${item.image}" alt="${item.name}">
                                            <div class="kc-search-item-info">
                                                <div class="kc-search-item-name">${item.name}</div>
                                                <div class="kc-search-item-meta">${item.author || ''} • ${new Intl.NumberFormat().format(item.price)} so'm</div>
                                            </div>
                                        </a>
                                    `;
                                });
                                searchPopup.innerHTML = popupHtml;
                                searchPopup.classList.add('active');
                            } else {
                                searchPopup.classList.remove('active');
                            }
                        }).catch(() => searchPopup.classList.remove('active'));
                }, 250);
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchPopup.contains(e.target)) {
                    searchPopup.classList.remove('active');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateCartCounter();
        });
    </script>

    @stack('scripts')
</body>
</html>
