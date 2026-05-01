<!DOCTYPE html>
<html lang="uz" class="">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Dashboard') — Kitobchi Admin</title>
  <style>[x-cloak]{display:none!important}</style>
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

  @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/a122-admin.css', 'resources/js/a122-admin.js'])
  <script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>

  @stack('styles')
  @stack('head')
</head>
<body class="text-gray-900 dark:text-gray-100 antialiased overflow-x-hidden">
  <div
    id="a122-shell"
    data-sidebar-shell
    class="min-h-screen a122-shell sidebar-expanded">
    @include('a122.partials.sidebar')
    <div class="flex-1 min-w-0 flex flex-col">
      @include('a122.partials.topbar')
      <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6">
        <div class="mx-auto w-full max-w-[1680px] space-y-4">
        @yield('content')
        </div>
      </main>
      <footer class="px-6 py-4 text-center text-xs text-gray-400 dark:text-gray-600 border-t border-gray-100/70 dark:border-white/5">
        © {{ date('Y') }} Kitobchi Admin. Barcha huquqlar himoyalangan.
      </footer>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  @stack('vendor_scripts')
  @stack('scripts')
</body>
</html>
