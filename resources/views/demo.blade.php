<!DOCTYPE html>
<html lang="uz" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kitobchi — Kitob va kanselyariya savdosi uchun yagona ekotizim: Marketplace, Express, Business, POS va AI. Pitch Day 3.0 taqdimoti.">
    <title>Kitobchi — Pitch Day 3.0 Taqdimoti</title>
    
    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

    <style>
        :root {
            --bg-body: #ffffff;
            --bg-subtle: #f5f5f7;
            --bg-card: #ffffff;
            --bg-card-hover: #fafafa;
            --border-color: rgba(0, 0, 0, 0.08);
            --border-hover: rgba(0, 0, 0, 0.16);
            --text-primary: #1d1d1f;
            --text-secondary: #6e6e73;
            --text-muted: #86868b;
            --apple-blue: #0071e3;
            --apple-blue-hover: #0077ed;
            --apple-green: #34c759;
            --apple-purple: #af52de;
            --apple-orange: #ff9500;
            --apple-red: #ff3b30;
            --card-radius: 22px;
            --shadow-sm: 0 4px 14px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 12px 32px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 20px 48px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Glassmorphism Navigation (Apple Light Style) */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 14px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -0.5px;
        }

        .nav-logo .logo-img {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            object-fit: cover;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
            transition: transform 0.2s ease;
        }

        .nav-logo:hover .logo-img {
            transform: scale(1.05);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .nav-links a:hover {
            color: var(--apple-blue);
        }

        .nav-cta {
            background: #000000;
            color: #ffffff;
            padding: 8px 18px;
            border-radius: 980px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .nav-cta:hover {
            background: #2c2c2e;
            transform: scale(1.02);
        }

        /* Container */
        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Hero Section */
        .hero {
            padding: 150px 0 80px;
            text-align: center;
            position: relative;
            background: radial-gradient(circle at 50% 10%, rgba(0, 113, 227, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
        }

        .pitch-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(0, 113, 227, 0.08);
            border: 1px solid rgba(0, 113, 227, 0.18);
            border-radius: 980px;
            font-size: 13px;
            font-weight: 600;
            color: var(--apple-blue);
            margin-bottom: 24px;
        }

        .pitch-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--apple-green);
            box-shadow: 0 0 8px var(--apple-green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
            100% { opacity: 1; transform: scale(1); }
        }

        .hero h1 {
            font-size: 64px;
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -2px;
            margin-bottom: 20px;
            color: var(--text-primary);
        }

        .hero p {
            font-size: 21px;
            color: var(--text-secondary);
            max-width: 760px;
            margin: 0 auto 36px;
            font-weight: 400;
            line-height: 1.5;
        }

        /* App Store Buttons */
        .app-buttons {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 56px;
        }

        .store-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            background: #000000;
            color: #ffffff;
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.25, 1, 0.5, 1);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
        }

        .store-btn:hover {
            background: #2c2c2e;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
        }

        .store-btn.web-btn {
            background: #ffffff;
            color: #000000;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .store-btn.web-btn:hover {
            background: var(--bg-subtle);
            border-color: var(--border-hover);
        }

        .store-btn i {
            font-size: 26px;
        }

        .store-btn .store-text {
            text-align: left;
            line-height: 1.2;
        }

        .store-btn .store-text span {
            display: block;
            font-size: 11px;
            opacity: 0.75;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .store-btn .store-text strong {
            font-size: 15px;
            font-weight: 700;
        }

        /* Stats Grid (Apple Clean Cards) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-top: 10px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--card-radius);
            padding: 30px 18px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--border-hover);
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }

        .stat-card .number {
            font-size: 44px;
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1;
            margin-bottom: 8px;
            color: var(--apple-blue);
        }

        .stat-card .label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 600;
        }

        /* Section Headings */
        .section {
            padding: 90px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .section.subtle-bg {
            background-color: var(--bg-subtle);
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-tag {
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--apple-blue);
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1.15;
            margin-bottom: 14px;
            color: var(--text-primary);
        }

        .section-desc {
            font-size: 17px;
            color: var(--text-secondary);
            max-width: 680px;
            margin: 0 auto;
        }

        /* Problem & Solution Cards */
        .problem-solution-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .ps-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--card-radius);
            padding: 38px;
            box-shadow: var(--shadow-sm);
        }

        .ps-card.problem {
            border-top: 4px solid var(--apple-red);
        }

        .ps-card.solution {
            border-top: 4px solid var(--apple-green);
        }

        .ps-card h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ps-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .ps-list li {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            font-size: 14.5px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .ps-list li i {
            font-size: 20px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .ps-card.problem .ps-list li i {
            color: var(--apple-red);
        }

        .ps-card.solution .ps-list li i {
            color: var(--apple-green);
        }

        .ps-list li strong {
            color: var(--text-primary);
            display: block;
            margin-bottom: 2px;
            font-size: 15px;
        }

        /* Team Cards */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .team-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--card-radius);
            padding: 32px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .team-card:hover {
            border-color: var(--border-hover);
            box-shadow: var(--shadow-md);
            transform: translateY(-4px);
        }

        .team-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--bg-subtle);
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin-bottom: 18px;
            box-shadow: var(--shadow-sm);
        }

        .team-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .team-role {
            font-size: 12.5px;
            color: var(--apple-blue);
            font-weight: 600;
            margin-bottom: 14px;
            line-height: 1.3;
        }

        .team-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            margin-bottom: 18px;
        }

        .skill-tag {
            font-size: 11px;
            padding: 4px 9px;
            background: var(--bg-subtle);
            border: 1px solid var(--border-color);
            border-radius: 980px;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .team-links {
            display: flex;
            gap: 12px;
            margin-top: auto;
        }

        .team-links a {
            color: var(--text-muted);
            font-size: 19px;
            transition: color 0.2s;
            text-decoration: none;
        }

        .team-links a:hover {
            color: var(--apple-blue);
        }

        /* Why Team Cards */
        .why-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .why-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--card-radius);
            padding: 34px 26px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .why-card:hover {
            border-color: var(--border-hover);
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }

        .why-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(0, 113, 227, 0.08);
            color: var(--apple-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 18px;
        }

        .why-card:nth-child(2) .why-icon {
            background: rgba(52, 199, 89, 0.1);
            color: var(--apple-green);
        }

        .why-card:nth-child(3) .why-icon {
            background: rgba(175, 82, 222, 0.1);
            color: var(--apple-purple);
        }

        .why-card h4 {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .why-card p {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Roadmap Timeline */
        .roadmap-timeline {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
        }

        .roadmap-step {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 24px 18px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .roadmap-step.active {
            border: 2px solid var(--apple-blue);
            background: rgba(0, 113, 227, 0.02);
            box-shadow: var(--shadow-md);
        }

        .roadmap-step.completed {
            border-top: 3px solid var(--apple-green);
        }

        .step-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 9px;
            border-radius: 980px;
            margin-bottom: 12px;
        }

        .roadmap-step.completed .step-badge {
            background: rgba(52, 199, 89, 0.12);
            color: var(--apple-green);
        }

        .roadmap-step.active .step-badge {
            background: rgba(0, 113, 227, 0.12);
            color: var(--apple-blue);
        }

        .roadmap-step.planned .step-badge {
            background: var(--bg-subtle);
            color: var(--text-muted);
        }

        .roadmap-step h4 {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text-primary);
        }

        .roadmap-step p {
            font-size: 12.5px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Tech Architecture */
        .tech-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .tech-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--card-radius);
            padding: 30px;
            box-shadow: var(--shadow-sm);
        }

        .tech-card h4 {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }

        .tech-card ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .tech-card ul li {
            font-size: 13.5px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tech-card ul li i {
            color: var(--apple-blue);
            font-size: 17px;
        }

        /* Demo Video Section */
        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            margin-bottom: 28px;
            background: #000000;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .video-desc-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--card-radius);
            padding: 28px 32px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-sm);
        }

        .video-desc-box h4 {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }

        .video-desc-box p {
            font-size: 14.5px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Prototype Live Cards */
        .proto-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .proto-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 22px;
            text-decoration: none;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .proto-card:hover {
            border-color: var(--border-hover);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .proto-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #000000;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .proto-card.web-card .proto-icon {
            background: var(--apple-blue);
        }

        .proto-card strong {
            font-size: 15px;
            display: block;
            margin-bottom: 2px;
        }

        .proto-card span {
            font-size: 12.5px;
            color: var(--text-secondary);
        }

        /* Screenshots Showcase (Apple Carousel Light) */
        .screenshots-wrap {
            display: flex;
            overflow-x: auto;
            gap: 22px;
            padding: 10px 0 30px;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .screenshots-wrap::-webkit-scrollbar {
            display: none;
        }

        .screenshot-item {
            flex: 0 0 270px;
            scroll-snap-align: center;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            background: #ffffff;
            transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .screenshot-item:hover {
            transform: scale(1.03);
            box-shadow: var(--shadow-lg);
        }

        .screenshot-item img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Footer */
        footer {
            background: var(--bg-subtle);
            border-top: 1px solid var(--border-color);
            padding: 50px 0 30px;
            text-align: center;
        }

        footer p {
            font-size: 13.5px;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        footer .pitch-tag {
            color: var(--text-primary);
            font-weight: 700;
        }

        /* Responsive Breakpoints */
        @media (max-width: 992px) {
            .hero h1 { font-size: 46px; }
            .section-title { font-size: 34px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .problem-solution-grid { grid-template-columns: 1fr; }
            .team-grid { grid-template-columns: repeat(2, 1fr); }
            .why-grid { grid-template-columns: 1fr; }
            .roadmap-timeline { grid-template-columns: 1fr; }
            .tech-grid { grid-template-columns: 1fr; }
            .proto-grid { grid-template-columns: 1fr; }
            nav { padding: 14px 20px; }
            .nav-links { display: none; }
        }

        @media (max-width: 600px) {
            .hero h1 { font-size: 36px; }
            .hero p { font-size: 17px; }
            .stats-grid { grid-template-columns: 1fr; }
            .team-grid { grid-template-columns: 1fr; }
            .stat-card .number { font-size: 36px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav>
        <a href="#hero" class="nav-logo">
            <img src="/apple-touch-icon.png" alt="Kitobchi App" class="logo-img">
            <span>Kitobchi</span>
        </a>
        <div class="nav-links">
            <a href="#problem">Muammo & Yechim</a>
            <a href="#team">Jamoa</a>
            <a href="#why">Nega Biz?</a>
            <a href="#roadmap">Yo'l xaritasi</a>
            <a href="#tech">Amalga oshirish</a>
            <a href="#demo">Demo Video</a>
        </div>
        <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" class="nav-cta">
            <i class="ti ti-download"></i> Ilovani yuklash
        </a>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero" id="hero">
        <div class="container">
            <div class="pitch-badge">
                <span class="dot"></span>
                Pitch Day 3.0 • 1-bosqich Taqdimoti
            </div>
            <h1>Kitob va kanselyariya savdosi<br>uchun yagona ekotizim</h1>
            <p>Marketplace • Express Yetkazib Berish • Nashriyot & Do'konlar Boshqaruvi • Smart POS • AI Tavsiyalar</p>

            <!-- Store Badges -->
            <div class="app-buttons">
                <a class="store-btn" href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank">
                    <i class="ti ti-brand-apple"></i>
                    <div class="store-text">
                        <span>Yuklab oling</span>
                        <strong>App Store</strong>
                    </div>
                </a>
                <a class="store-btn" href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank">
                    <i class="ti ti-brand-google-play"></i>
                    <div class="store-text">
                        <span>Yuklab oling</span>
                        <strong>Google Play</strong>
                    </div>
                </a>
                <a class="store-btn web-btn" href="https://kitobchi.com" target="_blank">
                    <i class="ti ti-world"></i>
                    <div class="store-text">
                        <span>Veb sayt</span>
                        <strong>kitobchi.com</strong>
                    </div>
                </a>
            </div>

            <!-- Key Metrics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number">10,000+</div>
                    <div class="label">Faol Foydalanuvchilar</div>
                </div>
                <div class="stat-card">
                    <div class="number">1,000+</div>
                    <div class="label">Muvaffaqiyatli Savdolar</div>
                </div>
                <div class="stat-card">
                    <div class="number">7,000+</div>
                    <div class="label">Kitoblar Bazasi</div>
                </div>
                <div class="stat-card">
                    <div class="number">5+</div>
                    <div class="label">Hamkor Do'konlar & Nashriyotlar</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 1. MUAMMO → YECHIM -->
    <section class="section subtle-bg" id="problem">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">1. Tahlil & Yondashuv</div>
                <h2 class="section-title">Muammo → Yechim</h2>
                <p class="section-desc">Kitob va kanselyariya savdosidagi asosiy tizimli muammolar va Kitobchi taqdim etayotgan qulay raqamli yechimlar.</p>
            </div>

            <div class="problem-solution-grid">
                <!-- Problem Card -->
                <div class="ps-card problem">
                    <h3><i class="ti ti-alert-circle text-danger"></i> Mavjud Muammolar</h3>
                    <ul class="ps-list">
                        <li>
                            <i class="ti ti-x"></i>
                            <div>
                                <strong>Tarqoq va noqulay bozor</strong>
                                Xaridor kerakli kitobni topish uchun o'nlab do'konlarni aylanib chiqishga yoki turli ijtimoiy tarmoqlardan qidirishga majbur.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-x"></i>
                            <div>
                                <strong>Do'konlar va nashriyotlarning raqamlashmaganligi</strong>
                                Kichik kitob do'konlarida avtomatlashgan hisob-kitob, kassa dasturi va onlayn savdo boshqaruvi yo'q.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-x"></i>
                            <div>
                                <strong>Qimmat va uzoq vaqt oladigan yetkazib berish</strong>
                                Kitoblarni yetkazish bir necha kun vaqt oladi, shahar ichida tezkor yetkazish xizmati yo'lga qo'yilmagan.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-x"></i>
                            <div>
                                <strong>Yagona standartlashtirilgan baza yo'qligi</strong>
                                ISBN, mualliflar va nashriyotlar bo'yicha yagona ma'lumotlar bazasi yo'qligi sababli narxlarni taqqoslash imkoni yo'q.
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Solution Card -->
                <div class="ps-card solution">
                    <h3><i class="ti ti-circle-check text-success"></i> Kitobchi Yechimi</h3>
                    <ul class="ps-list">
                        <li>
                            <i class="ti ti-check"></i>
                            <div>
                                <strong>Yagona Kitoblar Bozori va Narxlar Solishtiruvi</strong>
                                Barcha do'konlarning takliflari bitta kitob sahifasida jamlanadi. Xaridor eng arzon va eng yaqin do'konni tanlay oladi.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-check"></i>
                            <div>
                                <strong>Kitobchi Express (1–2 soatda yetkazish)</strong>
                                Shahar ichida kitoblarni bir necha soat ichida eshikkacha yetkazib berish va viloyatlarga qulay pochta xizmati.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-check"></i>
                            <div>
                                <strong>Kitobchi Business & Smart POS</strong>
                                Do'konlar va nashriyotlar uchun bepul dastur: ombor hisobi, kassa, shtrix-kod skaner va avtomatik hisobotlar.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-check"></i>
                            <div>
                                <strong>Sun'iy Intellekt (AI) Tavsiya Tizimi</strong>
                                Sun'iy intellekt orqali kitoblarni syujet, janr va qiziqishlar bo'yicha aniq topish va muqovalarni sifatli tiklash.
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. JAMOA -->
    <section class="section" id="team">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">2. Bizning Kuchimiz</div>
                <h2 class="section-title">Loyiha Jamoasi</h2>
                <p class="section-desc">Mahsulotni g'oyadan boshlab 10,000+ faol foydalanuvchiga ega ekotizim darajasiga yetkazgan tajribali jamoa.</p>
            </div>

            <div class="team-grid">
                <!-- Member 1 -->
                <div class="team-card">
                    <div class="team-avatar">👨‍💻</div>
                    <h3 class="team-name">Abbos Turdaliyev</h3>
                    <div class="team-role">Loyiha Asoschisi & Bosh Dasturchi</div>
                    <div class="team-skills">
                        <span class="skill-tag">Flutter</span>
                        <span class="skill-tag">Laravel / PHP</span>
                        <span class="skill-tag">MySQL</span>
                        <span class="skill-tag">DevOps</span>
                        <span class="skill-tag">AI Integratsiya</span>
                    </div>
                    <div class="team-links">
                        <a href="https://github.com/abbouse" target="_blank" title="GitHub"><i class="ti ti-brand-github"></i></a>
                        <a href="https://t.me/abboust" target="_blank" title="Telegram"><i class="ti ti-brand-telegram"></i></a>
                    </div>
                </div>

                <!-- Member 2 -->
                <div class="team-card">
                    <div class="team-avatar">💼</div>
                    <h3 class="team-name">Kamoliddin Hikmatov</h3>
                    <div class="team-role">Hammuassis & Loyiha Boshqaruvchisi</div>
                    <div class="team-skills">
                        <span class="skill-tag">Boshqaruv</span>
                        <span class="skill-tag">Hamkorlar bilan ishlash</span>
                        <span class="skill-tag">Logistika</span>
                        <span class="skill-tag">B2B Savdo</span>
                    </div>
                    <div class="team-links">
                        <a href="https://t.me/kitobchi_support" target="_blank" title="Telegram"><i class="ti ti-brand-telegram"></i></a>
                    </div>
                </div>

                <!-- Member 3 -->
                <div class="team-card">
                    <div class="team-avatar">🎨</div>
                    <h3 class="team-name">Jonibek Fayzullayev</h3>
                    <div class="team-role">Bosh Mahsulot va UI/UX Dizayneri</div>
                    <div class="team-skills">
                        <span class="skill-tag">Figma</span>
                        <span class="skill-tag">Dizayn Tizimlari</span>
                        <span class="skill-tag">Mobil Interfeys</span>
                        <span class="skill-tag">Prototip</span>
                    </div>
                    <div class="team-links">
                        <a href="https://t.me/kitobchi_support" target="_blank" title="Telegram"><i class="ti ti-brand-telegram"></i></a>
                    </div>
                </div>

                <!-- Member 4 -->
                <div class="team-card">
                    <div class="team-avatar">🚀</div>
                    <h3 class="team-name">Abuxusayn Mamatov</h3>
                    <div class="team-role">Marketing va SMM Mutaxassisi</div>
                    <div class="team-skills">
                        <span class="skill-tag">Targeting</span>
                        <span class="skill-tag">SMM Strategiya</span>
                        <span class="skill-tag">Kontent</span>
                        <span class="skill-tag">Hamjamiyat</span>
                    </div>
                    <div class="team-links">
                        <a href="https://t.me/kitobchi_support" target="_blank" title="Telegram"><i class="ti ti-brand-telegram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. NEGA BIZNING JAMOA? -->
    <section class="section subtle-bg" id="why">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">3. Ustunliklarimiz</div>
                <h2 class="section-title">Nima uchun jamoamiz bu muammoni hal qila oladi?</h2>
                <p class="section-desc">Biz shunchaki g'oya bosqichida emasmiz — soha bo'yicha amaliy tajriba, ishlab turgan infratuzilma va kuchli texnik bilimga egamiz.</p>
            </div>

            <div class="why-grid">
                <div class="why-card">
                    <div class="why-icon"><i class="ti ti-bulb"></i></div>
                    <h4>Kitob bozorini chuqur tushunish</h4>
                    <p>Biz kitob do'konlari, nashriyotlar va kitobxonlarning barcha ehtiyojlarini bevosita o'rganib, ularga moslashtirilgan qulay milliy yechim ishlab chiqdik.</p>
                </div>

                <div class="why-card">
                    <div class="why-icon"><i class="ti ti-device-mobile-check"></i></div>
                    <h4>100% ishlab turgan tayyor mahsulot</h4>
                    <p>App Store va Google Play'da mobil ilova, do'konlar uchun boshqaruv dasturi, yagona kitoblar bazasi va yetkazib berish tizimi allaqachon ishlab turibdi.</p>
                </div>

                <div class="why-card">
                    <div class="why-icon"><i class="ti ti-chart-arrows-vertical"></i></div>
                    <h4>Haqiqiy natijalar va o'sish</h4>
                    <p>10,000+ yuklab olish, 1,000+ muvaffaqiyatli buyurtma, 7,000+ kitoblar bazasi va barqaror daromad modeli orqali loyihaga talab yuqori ekani isbotlandi.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. YO'L XARITASI -->
    <section class="section" id="roadmap">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">4. Rivojlanish Bosqichlari</div>
                <h2 class="section-title">Yo'l Xaritasi (Roadmap)</h2>
                <p class="section-desc">G'oyadan boshlab, dastlabki prototip, MVP, ishga tushirish va kelajakdagi kengayish rejalari.</p>
            </div>

            <div class="roadmap-timeline">
                <!-- Step 1 -->
                <div class="roadmap-step completed">
                    <span class="step-badge">Bajarildi ✅</span>
                    <h4>1. G'oya (Idea)</h4>
                    <p>Bozor tahlili, do'konlar bilan suhbatlar va tizim arxitekturasi ishlab chiqildi.</p>
                </div>

                <!-- Step 2 -->
                <div class="roadmap-step completed">
                    <span class="step-badge">Bajarildi ✅</span>
                    <h4>2. Prototip</h4>
                    <p>Dastlabki mobil ilova va do'konlar boshqaruv paneli sinovdan muvaffaqiyatli o'tdi.</p>
                </div>

                <!-- Step 3 -->
                <div class="roadmap-step completed">
                    <span class="step-badge">Bajarildi ✅</span>
                    <h4>3. MVP</h4>
                    <p>To'lov tizimlari (Payme/Click), birinchi hamkor do'konlar ulanishi va yetkazish yo'lga qo'yildi.</p>
                </div>

                <!-- Step 4 -->
                <div class="roadmap-step active">
                    <span class="step-badge">Hozirgi Bosqich 🚀</span>
                    <h4>4. Ishga Tushirildi</h4>
                    <p>10,000+ mijoz, 1,000+ buyurtma, App Store & Google Play'da faol, tezkor yetkazib berish xizmati.</p>
                </div>

                <!-- Step 5 -->
                <div class="roadmap-step planned">
                    <span class="step-badge">Kelgusi 📈</span>
                    <h4>5. Kengayish & POS</h4>
                    <p>Viloyatlarga tezkor yetkazish tarmog'i, qulay kassa apparatlari (POS) va B2B maktab/universitetlar integratsiyasi.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. AMALGA OSHIRISH & TEXNOLOGIYALAR & AI -->
    <section class="section subtle-bg" id="tech">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">5. Texnik Arxitektura</div>
                <h2 class="section-title">Yechimni qanday amalga oshiryapmiz?</h2>
                <p class="section-desc">Zamonaviy texnologiyalar, sun'iy intellekt vositalari va ishonchli backend infratuzilmasi.</p>
            </div>

            <div class="tech-grid">
                <!-- Tech Stack -->
                <div class="tech-card">
                    <h4><i class="ti ti-stack-2 text-primary"></i> Texnologiyalar</h4>
                    <ul>
                        <li><i class="ti ti-brand-flutter"></i> <strong>Mobil:</strong> Flutter (iOS & Android uchun yagona sifatli kod)</li>
                        <li><i class="ti ti-brand-laravel"></i> <strong>Server:</strong> Laravel 11 / PHP 8.3 RESTful API</li>
                        <li><i class="ti ti-database"></i> <strong>Ma'lumotlar bazasi:</strong> MySQL + Redis Kesh</li>
                        <li><i class="ti ti-brand-react"></i> <strong>Boshqaruv Paneli:</strong> Inertia.js + React / TypeScript</li>
                        <li><i class="ti ti-cloud"></i> <strong>Infratuzilma:</strong> Docker, Nginx, Cloudflare, Linux</li>
                    </ul>
                </div>

                <!-- AI Tools & Solutions -->
                <div class="tech-card">
                    <h4><i class="ti ti-sparkles text-warning"></i> Sun'iy Intellekt (AI)</h4>
                    <ul>
                        <li><i class="ti ti-brain"></i> <strong>Matn Tahlili (LLM):</strong> Kitoblar tavsifi, qisqacha mazmuni va janrlarni avtomatik to'ldirish</li>
                        <li><i class="ti ti-search"></i> <strong>Aqlli Qidiruv:</strong> Kitob mazmuni va ma'nosi bo'yicha semantik qidiruv</li>
                        <li><i class="ti ti-photo-ai"></i> <strong>Muqovalarni Tiklash:</strong> Sifatsiz kitob muqovalarini aniqlash va sifatini yaxshilash</li>
                        <li><i class="ti ti-smart-home"></i> <strong>Tavsiyalar:</strong> Kitobxonning qiziqishiga mos yangi kitoblarni tavsiya qilish</li>
                    </ul>
                </div>

                <!-- Business & POS System -->
                <div class="tech-card">
                    <h4><i class="ti ti-building-store text-success"></i> Biznes va Logistika</h4>
                    <ul>
                        <li><i class="ti ti-arrows-shuffle"></i> <strong>Narxlar Solishtiruvi:</strong> Xaridorga eng yaqin va eng arzon do'konni taklif qilish</li>
                        <li><i class="ti ti-device-tablet"></i> <strong>Smart POS Kassa:</strong> Do'konda shtrix-kod va chek chiqarish orqali savdo</li>
                        <li><i class="ti ti-truck-delivery"></i> <strong>Kitobchi Express:</strong> Kuryer ilovasi va xaridni xaritada jonli kuzatish</li>
                        <li><i class="ti ti-credit-card"></i> <strong>To'lovlar:</strong> Payme, Click, Uzum Pay va naqd to'lovlar</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. DEMO VIDEO VA ISHLAYOTGAN PROTOTIP -->
    <section class="section" id="demo">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">6. Taqdimot & Video</div>
                <h2 class="section-title">🎬 Demo Video & Jonli Mahsulot</h2>
                <p class="section-desc">Kitobchi ekotizimining amaldagi ishlashi: xarid qilish, do'konlar boshqaruvi va yetkazib berish jarayoni.</p>
            </div>

            <!-- YouTube Video Embed -->
            <div class="video-container">
                <iframe 
                    src="https://www.youtube.com/embed/RO6TQ7Hotek?rel=0&modestbranding=1" 
                    title="Kitobchi — Pitch Day 3.0 Demo Video" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    allowfullscreen>
                </iframe>
            </div>

            <!-- Video Description -->
            <div class="video-desc-box">
                <h4><i class="ti ti-info-circle text-primary"></i> Demo-videoning qisqacha tavsifi:</h4>
                <p>Ushbu videoda <strong>Kitobchi</strong> tizimining to'liq ishlash jarayoni amalda ko'rsatilgan:
                foydalanuvchi mobil ilovadan kitoblarni qidirishi, turli do'konlar narxlarini solishtirib buyurtma berishi, kitob do'konlari uchun Boshqaruv panelida buyurtmalarni qabul qilish va omborni nazorat qilish, shuningdek Kitobchi Express orqali kuryerlik yetkazib berish mexanizmi batafsil namoyish etilgan.</p>
            </div>

            <!-- Working Prototype Links -->
            <div class="proto-grid">
                <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" class="proto-card">
                    <div class="proto-icon"><i class="ti ti-brand-apple"></i></div>
                    <div>
                        <strong>App Store (iOS)</strong>
                        <span>Ilovani yuklab olish &rarr;</span>
                    </div>
                </a>

                <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" class="proto-card">
                    <div class="proto-icon"><i class="ti ti-brand-google-play"></i></div>
                    <div>
                        <strong>Google Play (Android)</strong>
                        <span>Ilovani yuklab olish &rarr;</span>
                    </div>
                </a>

                <a href="https://kitobchi.com" target="_blank" class="proto-card web-card">
                    <div class="proto-icon"><i class="ti ti-world"></i></div>
                    <div>
                        <strong>Veb Sayt</strong>
                        <span>kitobchi.com saytiga o'tish &rarr;</span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- 7. SKRINSHOTLAR SHOWCASE -->
    <section class="section subtle-bg" id="screenshots">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">Ilova Interfeysi</div>
                <h2 class="section-title">📱 Mobil Ilova Ko'rinishi</h2>
                <p class="section-desc">Foydalanuvchilar va do'konlar uchun qulay, zamonaviy va intuitiv iOS & Android dizayni.</p>
            </div>

            <div class="screenshots-wrap">
                <div class="screenshot-item">
                    <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource211/v4/36/64/8c/36648c98-a120-b8ff-1d7e-f931ad5c5b86/6_1-Frame-en-1-2.jpg/400x800bb.png" alt="Kitobchi — Bosh sahifa">
                </div>
                <div class="screenshot-item">
                    <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource211/v4/c2/2a/e0/c22ae0ca-a8e2-89ae-8409-9350c9d13a42/6_1-Frame-en-2-2.jpg/400x800bb.png" alt="Kitobchi — Kitoblar katalogi">
                </div>
                <div class="screenshot-item">
                    <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource211/v4/68/83/eb/6883eb2b-f0f9-95af-bf96-1de51fdd242b/6_1-Frame-en-3.jpg/400x800bb.png" alt="Kitobchi — Buyurtma berish">
                </div>
                <div class="screenshot-item">
                    <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource221/v4/be/d9/cf/bed9cf03-5873-d921-e56c-9fa36a6bf7af/6_1-Frame-en-4-4.jpg/400x800bb.png" alt="Kitobchi — Do'konlar paneli">
                </div>
                <div class="screenshot-item">
                    <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource221/v4/9f/1b/bb/9f1bbbf5-6afc-c886-68a6-0ac31c90e68b/6_1-Frame-en-5.jpg/400x800bb.png" alt="Kitobchi — Express yetkazib berish">
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p class="pitch-tag">Pitch Day 3.0 Tanlovi Uchun Maxsus Tayyorlandi</p>
            <p>&copy; 2026 Kitobchi Ekotizimi. AIFU (Aniq va Ijtimoiy Fanlar Universiteti)</p>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 10px;">
                Barcha huquqlar himoyalangan. Toshkent, O'zbekiston.
            </p>
        </div>
    </footer>

</body>
</html>