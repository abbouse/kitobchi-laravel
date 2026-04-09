<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    
    <title><?php echo e($title ?? 'Kitobchi'); ?></title>
    <meta name="description" content="<?php echo e($description ?? 'kitobchi.'); ?>">

    <meta property="og:title"       content="<?php echo e($title ?? 'kitobchi.'); ?>">
    <meta property="og:description" content="<?php echo e($description ?? 'Kitobchi ilovasida ko\'ring'); ?>">
    <meta property="og:image"       content="<?php echo e(asset('images/og-cover.png')); ?>">
    <meta property="og:url"         content="<?php echo e(request()->url()); ?>">
    <meta property="og:type"        content="website">

    
    <meta name="apple-itunes-app"   content="app-id=6753818078">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 24px;
            padding: 40px 32px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
        }

        .logo {
            width: 72px;
            height: 72px;
            background: #2E6BE6;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        p {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 32px;
        }

        .btn-primary {
            display: block;
            width: 100%;
            padding: 16px;
            background: #2E6BE6;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 12px;
            transition: opacity 0.2s;
        }

        .btn-primary:hover { opacity: 0.9; }

        .btn-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            background: #f3f4f6;
            color: #374151;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 10px;
            transition: background 0.2s;
        }

        .btn-secondary:hover { background: #e5e7eb; }

        .store-icon {
            width: 20px;
            height: 20px;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: #d1d5db;
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #f3f4f6;
        }

        .loading {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.4);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="card">
    <div class="logo">📚</div>

    <h1>Kitobchi</h1>
    <p><?php echo e($description ?? "Kitobchi ilovasida ko'rish uchun quyidagi tugmani bosing"); ?></p>

    
    <button class="btn-primary" id="openAppBtn" onclick="openApp()">
        <span class="loading" id="loadingSpinner"></span>
        Ilovada ochish
    </button>

    <div class="divider">yoki yuklab oling</div>

    
    <a href="https://apps.apple.com/app/id6753818078"
       class="btn-secondary"
       target="_blank">
        <svg class="store-icon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
        </svg>
        App Store dan yuklab oling
    </a>

    
    <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi"
       class="btn-secondary"
       target="_blank">
        <svg class="store-icon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3.18 23.76c.3.17.64.22.99.14l12.75-7.36-2.88-2.88-10.86 10.1zm-1.67-20.1c-.06.2-.1.42-.1.65v19.38c0 .23.04.45.1.65l.07.06 10.85-10.85v-.25L1.58 3.6l-.07.06zM20.56 10.4l-2.88-1.66-3.23 3.23 3.23 3.23 2.9-1.67c.83-.48.83-1.26-.02-1.73zm-18.3 12.24l12.75-7.36-2.88-2.88L2.38 22.49l-.12 1.15z"/>
        </svg>
        Google Play dan yuklab oling
    </a>
</div>

<script>
    // App scheme URL
    const APP_SCHEME = "<?php echo e($appScheme); ?>";
    // Fallback URLs
    const APP_STORE_URL = "https://apps.apple.com/app/id6753818078";
    const PLAY_STORE_URL = "https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi";

    let appOpened = false;

    function openApp() {
        // Tugmani disable qilamiz
        const btn = document.getElementById('openAppBtn');
        btn.disabled = true;

        // App schemega yo'naltiramiz
        window.location.href = APP_SCHEME;

        // 2 soniyadan keyin app ochilmagan bo'lsa store ga yo'naltiramiz
        setTimeout(() => {
            if (!appOpened && !document.hidden) {
                // iOS yoki Android ni aniqlash
                const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
                const isAndroid = /Android/.test(navigator.userAgent);

                if (isIOS) {
                    window.location.href = APP_STORE_URL;
                } else if (isAndroid) {
                    window.location.href = PLAY_STORE_URL;
                }
            }
            btn.disabled = false;
        }, 2000);
    }

    // Sahifa yashirinsa (app ochildi) — timer ni bekor qilamiz
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) appOpened = true;
    });

    // Sahifa yuklanganda avtomatik urinib ko'ramiz (faqat mobile da)
    window.addEventListener('load', () => {
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        if (isMobile) {
            // Kichik kechikish — sahifa to'liq yuklansin
            setTimeout(openApp, 600);
        }
    });
</script>

</body>
</html><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/share/redirect.blade.php ENDPATH**/ ?>