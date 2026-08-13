<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Kitobchi — Kitoblar va Kanselyariya Marketpleysi')</title>
    
    @stack('meta')

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    @vite(['resources/css/kitobchi-marketplace.css'])
    
    @stack('styles')
</head>
<body class="kc-mk-body">

    <!-- Top Announcement Bar -->
    <div class="kc-top-bar">
        <div class="container d-flex justify-content-between align-items-center">
            <div>🚀 O'zbekiston bo'ylab 1-3 kunda tezkor va kafolatlangan yetkazib berish!</div>
            <div class="d-none d-md-flex align-items-center gap-3">
                <a href="{{ route('legal.terms') }}">Xizmat ko'rsatish shartlari</a>
                <span>•</span>
                <a href="tel:+998712000000">📞 Yordam markazi</a>
            </div>
        </div>
    </div>

    <!-- Header & Navigation -->
    <header class="kc-header-sticky">
        <div class="container">
            <div class="kc-nav-wrapper">
                
                <!-- Logo -->
                <a href="{{ url('/') }}" class="kc-logo">
                    <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi Logo">
                    <span>Kitobchi</span>
                </a>

                <!-- Category Drawer Button -->
                <a href="{{ route('web.catalog') }}" class="kc-cat-btn d-none d-lg-inline-flex">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    <span>Katalog</span>
                </a>

                <!-- Live Search Form -->
                <div class="kc-search-wrap">
                    <form action="{{ route('web.catalog') }}" method="GET" class="kc-search-form" id="kcSearchForm">
                        <input type="text" name="search" class="kc-search-input" id="kcSearchInput" placeholder="Kitob, muallif yoki artikul qidiring..." value="{{ request('search') }}" autocomplete="off">
                        <button type="submit" class="kc-search-submit" aria-label="Qidirish">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        </button>
                    </form>
                    <!-- Instant Live Search Dropdown Popup -->
                    <div class="kc-search-results-popup" id="kcSearchPopup"></div>
                </div>

                <!-- Header Action Buttons -->
                <div class="kc-nav-actions">
                    <!-- Wishlist -->
                    <a href="{{ route('web.catalog') }}" class="kc-action-icon-btn d-none d-sm-inline-flex" title="Sevimlilar">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </a>

                    <!-- Cart Trigger Button -->
                    <button type="button" class="kc-cart-trigger-btn" onclick="toggleCartDrawer(true)" id="kcCartTriggerBtn">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        <span class="d-none d-sm-inline">Savat</span>
                        <span class="badge rounded-pill bg-danger" id="kcCartCountBadge">0</span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Quick Categories Strip -->
        <div class="kc-cat-quick-bar">
            <div class="container">
                <div class="kc-cat-chips">
                    <a href="{{ route('web.catalog') }}" class="kc-chip {{ !request('category') ? 'active' : '' }}">Barcha mahsulotlar</a>
                    @php
                        try {
                            $topCategories = Cache::remember('web_top_categories', 600, function() {
                                return \App\Models\BookCategories::where('status', true)->orderBy('name')->take(8)->get();
                            });
                        } catch (\Throwable $e) {
                            $topCategories = collect();
                        }
                    @endphp
                    @foreach($topCategories as $cat)
                        <a href="{{ route('web.catalog', ['category' => $cat->id]) }}" class="kc-chip {{ request('category') == $cat->id ? 'active' : '' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="kc-mk-main u-my-l">
        @yield('content')
    </main>

    <!-- Cart Drawer Slide-over -->
    <div class="kc-drawer-overlay" id="kcCartDrawerOverlay" onclick="if(event.target === this) toggleCartDrawer(false)">
        <div class="kc-drawer-panel">
            <div class="kc-drawer-header">
                <h2 class="kc-drawer-title">Savat</h2>
                <button type="button" class="kc-drawer-close" onclick="toggleCartDrawer(false)">&times;</button>
            </div>
            <div class="kc-drawer-body" id="kcCartDrawerBody">
                <div class="text-center text-muted py-5">Savat bo'sh</div>
            </div>
            <div class="kc-drawer-footer">
                <div class="kc-drawer-summary-row">
                    <span>Jami summasi:</span>
                    <span id="kcCartTotalSum">0 UZS</span>
                </div>
                <a href="{{ route('web.checkout') }}" class="kc-checkout-btn">Rasmiylashtirishga o'tish &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Marketplace Footer -->
    <footer class="bg-white border-top u-mt-xl py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <a href="{{ url('/') }}" class="kc-logo u-mb-s">
                        <img src="{{ asset('images/logo/logo_blue.png') }}" alt="Kitobchi Logo">
                        <span>Kitobchi</span>
                    </a>
                    <p class="text-muted" style="font-size: 14px; line-height: 1.6;">
                        O'zbekistondagi eng yirik onlayn kitoblar va kanselyariya marketpleysi. Original mahsulotlar va tezkor yetkazib berish.
                    </p>
                    <div class="d-flex gap-2 u-mt-m">
                        <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill">App Store</a>
                        <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill">Google Play</a>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="fw-bold text-dark mb-3">Xaridorlarga</h6>
                    <ul class="list-unstyled text-muted" style="font-size: 14px; line-height: 2;">
                        <li><a href="{{ route('web.catalog') }}" class="text-muted text-decoration-none">Katalog</a></li>
                        <li><a href="{{ route('legal.terms') }}" class="text-muted text-decoration-none">Yetkazib berish</a></li>
                        <li><a href="{{ route('legal.terms') }}" class="text-muted text-decoration-none">Qaytarish va kafolat</a></li>
                        <li><a href="{{ route('careers.index') }}" class="text-muted text-decoration-none">Vakansiyalar</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-3">
                    <h6 class="fw-bold text-dark mb-3">Hujjatlar</h6>
                    <ul class="list-unstyled text-muted" style="font-size: 14px; line-height: 2;">
                        <li><a href="{{ route('legal.privacy') }}" class="text-muted text-decoration-none">Maxfiylik siyosati</a></li>
                        <li><a href="{{ route('legal.terms') }}" class="text-muted text-decoration-none">Foydalanish shartlari</a></li>
                        <li><a href="{{ route('contact.index') }}" class="text-muted text-decoration-none">Kontaktlar</a></li>
                    </ul>
                </div>

                <div class="col-lg-3">
                    <h6 class="fw-bold text-dark mb-3">To'lov tizimlari</h6>
                    <p class="text-muted" style="font-size: 13px;">Barcha xavfsiz to'lov usullarini qo'llab-quvvatlaymiz:</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark border">Payme</span>
                        <span class="badge bg-light text-dark border">Click</span>
                        <span class="badge bg-light text-dark border">Uzum Pay</span>
                        <span class="badge bg-light text-dark border">Naqd / Karta</span>
                    </div>
                </div>
            </div>

            <div class="border-top mt-4 pt-3 text-center text-muted" style="font-size: 13px;">
                &copy; {{ date('Y') }} Kitobchi.com — Barcha huquqlar himoyalangan.
            </div>
        </div>
    </footer>

    <!-- Interactive Client JS Script -->
    <script>
        // Web Cart State Management
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
                    <div class="fw-bold mt-2">Savatingiz bo'sh</div>
                    <small>Katalogdan mahsulotlarni tanlang</small>
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
                    <div class="kc-cart-item">
                        <img src="${item.image}" alt="${item.name}" class="kc-cart-item-img">
                        <div class="kc-cart-item-info">
                            <h4 class="kc-cart-item-title">${item.name}</h4>
                            <div class="kc-cart-item-price">${new Intl.NumberFormat().format(item.price)} UZS</div>
                            <div class="kc-qty-control">
                                <button type="button" class="kc-qty-btn" onclick="updateCartQty(${item.id}, -1)">-</button>
                                <span class="kc-qty-val">${item.quantity}</span>
                                <button type="button" class="kc-qty-btn" onclick="updateCartQty(${item.id}, 1)">+</button>
                            </div>
                        </div>
                    </div>
                `;
            });

            body.innerHTML = html;
            if (totalSumEl) totalSumEl.innerText = new Intl.NumberFormat().format(total) + ' UZS';
        }

        // Live Instant Search AJAX
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
                                                <div class="kc-search-item-meta">${item.author || ''} • ${new Intl.NumberFormat().format(item.price)} UZS</div>
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

        // Init on DOM load
        document.addEventListener('DOMContentLoaded', () => {
            updateCartCounter();
        });
    </script>

    @stack('scripts')
</body>
</html>
