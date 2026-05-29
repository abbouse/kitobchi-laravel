<!DOCTYPE html>
<html lang="uz" class="kc-panel-root">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Dashboard') — Kitobchi Admin</title>
  <style>[x-cloak]{display:none!important}</style>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <script>
    (function () {
      const saved = localStorage.getItem('a122-theme') || localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const dark = saved ? saved === 'dark' : prefersDark;
      const sidebarExpanded = JSON.parse(localStorage.getItem('a122-sidebar-expanded') ?? 'true');
      document.documentElement.classList.toggle('dark', dark);
      document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
      document.documentElement.setAttribute('data-sidebar-expanded', sidebarExpanded ? 'true' : 'false');
    })();
  </script>

  @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/a122-admin.css', 'resources/css/a122-bootstrap-admin.css', 'resources/js/a122-admin.js'])
  @stack('styles')
  @stack('head')
</head>
<body class="kc-admin-body overflow-x-hidden">
  <div
    id="a122-shell"
    data-sidebar-shell
    class="kc-shell d-flex sidebar-expanded">
    @include('a122.partials.sidebar')
    <div class="kc-main flex-grow-1 d-flex flex-column">
      @include('a122.partials.topbar')
      <div class="kc-main__viewport flex-grow-1 d-flex flex-column">
        <main class="kc-content flex-grow-1">
          <div class="container-fluid px-3 px-lg-4 px-xxl-5">
            <div class="mx-auto kc-shell-width">
              @yield('content')
            </div>
          </div>
        </main>
        <footer class="kc-footer border-top bg-white bg-opacity-75">
          <div class="container-fluid px-3 px-lg-4 px-xxl-5 py-3">
            <div class="mx-auto kc-shell-width d-flex flex-wrap align-items-center justify-content-between gap-2 small text-secondary">
              <span>© {{ date('Y') }} Kitobchi Admin</span>
              <span>Operational dashboard workspace</span>
            </div>
          </div>
        </footer>
      </div>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  @stack('vendor_scripts')
  @stack('scripts')
</body>
</html>
