<!DOCTYPE html>
<html lang="uz" class="h-full">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>@yield('title', 'Kirish') — kitobchi. Admin</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>

    <script>
    (function () {
        var sessionTheme = @json(session('theme', 'dark'));
        var ls = localStorage.getItem('kitobchi_theme');
        var t = (ls === 'light' || ls === 'dark') ? ls : sessionTheme;
        document.documentElement.classList.toggle('dark', t === 'dark');
        document.documentElement.setAttribute('data-bs-theme', t);
    })();
    </script>
    @stack('styles')
</head>
<body class="min-h-screen font-outfit antialiased bg-gray-50 text-gray-800 selection:bg-brand-500/20 selection:text-gray-900 dark:bg-gray-900 dark:text-white/90 dark:selection:bg-brand-400/25 dark:selection:text-white">
    @yield('content')
    @stack('scripts')
</body>
</html>
