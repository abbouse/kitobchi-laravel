<!DOCTYPE html>
<html lang="uz" class="">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Live Monitor') — Kitobchi Admin</title>
  <style>[x-cloak]{display:none!important}</style>
  <script>
    (function () {
      const saved = localStorage.getItem('a122-theme') || localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const dark = saved ? saved === 'dark' : prefersDark;
      document.documentElement.classList.toggle('dark', dark);
      document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    })();
  </script>
  @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/a122-admin.css', 'resources/css/a122-bootstrap-admin.css', 'resources/js/a122-admin.js'])
  @stack('styles')
</head>
<body class="min-h-screen bg-[var(--p-bg)] text-gray-900 dark:text-gray-100 antialiased">
  @yield('content')
  @stack('scripts')
</body>
</html>
