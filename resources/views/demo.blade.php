<!DOCTYPE html>
<html lang="uz" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kitobchi — Kitob va kanselyariya savdosi uchun to‘liq ekotizim: Marketplace, Express, Business, POS va AI. Pitch Day 3.0 taqdimoti.">
    <title>Kitobchi — Pitch Day 3.0 Taqdimoti</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon.ico">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

    <style>
        :root {
            --bg-body: #000000;
            --bg-card: #121215;
            --bg-card-hover: #1c1c21;
            --bg-card-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f5f5f7;
            --text-secondary: #a1a1a6;
            --text-muted: #6e6e73;
            --apple-blue: #2997ff;
            --apple-blue-hover: #147ce5;
            --apple-green: #30d158;
            --apple-purple: #bf5af2;
            --apple-orange: #ff9f0a;
            --apple-gradient: linear-gradient(135deg, #ffffff 0%, #a1a1a6 100%);
            --blue-gradient: linear-gradient(135deg, #2997ff 0%, #a259ff 100%);
            --card-radius: 24px;
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

        /* Glassmorphism Navigation */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 16px 32px;
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
            color: #fff;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -0.5px;
        }

        .nav-logo .logo-icon {
            width: 38px;
            height: 38px;
            background: var(--blue-gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
            box-shadow: 0 4px 14px rgba(41, 151, 255, 0.35);
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
            color: #fff;
        }

        .nav-cta {
            background: #fff;
            color: #000;
            padding: 8px 18px;
            border-radius: 980px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .nav-cta:hover {
            background: #e5e5ea;
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
            padding: 160px 0 90px;
            text-align: center;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 350px;
            background: radial-gradient(circle, rgba(41, 151, 255, 0.18) 0%, rgba(162, 89, 255, 0.08) 50%, rgba(0, 0, 0, 0) 80%);
            z-index: -1;
            pointer-events: none;
            filter: blur(40px);
        }

        .pitch-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 980px;
            font-size: 13px;
            font-weight: 600;
            color: var(--apple-blue);
            margin-bottom: 24px;
            backdrop-filter: blur(10px);
        }

        .pitch-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--apple-green);
            box-shadow: 0 0 10px var(--apple-green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
            100% { opacity: 1; transform: scale(1); }
        }

        .hero h1 {
            font-size: 68px;
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -2px;
            margin-bottom: 24px;
            background: linear-gradient(180deg, #ffffff 30%, #a1a1a6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 22px;
            color: var(--text-secondary);
            max-width: 780px;
            margin: 0 auto 40px;
            font-weight: 400;
            line-height: 1.5;
        }

        /* App Store Buttons */
        .app-buttons {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 60px;
        }

        .store-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 16px;
            text-decoration: none;
            color: #fff;
            transition: all 0.25s cubic-bezier(0.25, 1, 0.5, 1);
            backdrop-filter: blur(10px);
        }

        .store-btn:hover {
            background: rgba(255, 255, 255, 0.16);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.4);
        }

        .store-btn i {
            font-size: 28px;
        }

        .store-btn .store-text {
            text-align: left;
            line-height: 1.2;
        }

        .store-btn .store-text span {
            display: block;
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .store-btn .store-text strong {
            font-size: 16px;
            font-weight: 700;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--bg-card-border);
            border-radius: var(--card-radius);
            padding: 32px 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }

        .stat-card .number {
            font-size: 46px;
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1;
            margin-bottom: 8px;
            background: var(--blue-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-card .label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Section Headings */
        .section {
            padding: 100px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .section-header {
            text-align: center;
            margin-bottom: 56px;
        }

        .section-tag {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--apple-blue);
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 46px;
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1.15;
            margin-bottom: 16px;
        }

        .section-desc {
            font-size: 18px;
            color: var(--text-secondary);
            max-width: 680px;
            margin: 0 auto;
        }

        /* Problem & Solution Cards */
        .problem-solution-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
        }

        .ps-card {
            background: var(--bg-card);
            border: 1px solid var(--bg-card-border);
            border-radius: var(--card-radius);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .ps-card.problem {
            border-top: 4px solid #ff453a;
        }

        .ps-card.solution {
            border-top: 4px solid var(--apple-green);
        }

        .ps-card h3 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
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
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .ps-list li i {
            font-size: 20px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .ps-card.problem .ps-list li i {
            color: #ff453a;
        }

        .ps-card.solution .ps-list li i {
            color: var(--apple-green);
        }

        .ps-list li strong {
            color: #fff;
            display: block;
            margin-bottom: 2px;
        }

        /* Team Cards */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .team-card {
            background: var(--bg-card);
            border: 1px solid var(--bg-card-border);
            border-radius: var(--card-radius);
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s ease;
        }

        .team-card:hover {
            background: var(--bg-card-hover);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-4px);
        }

        .team-avatar {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.02));
            border: 2px solid rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 20px;
            color: #fff;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        .team-name {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .team-role {
            font-size: 13px;
            color: var(--apple-blue);
            font-weight: 600;
            margin-bottom: 16px;
        }

        .team-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .skill-tag {
            font-size: 11px;
            padding: 4px 10px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 980px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .team-links {
            display: flex;
            gap: 12px;
            margin-top: auto;
        }

        .team-links a {
            color: var(--text-muted);
            font-size: 18px;
            transition: color 0.2s;
            text-decoration: none;
        }

        .team-links a:hover {
            color: #fff;
        }

        /* Why Team Cards */
        .why-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .why-card {
            background: var(--bg-card);
            border: 1px solid var(--bg-card-border);
            border-radius: var(--card-radius);
            padding: 36px 28px;
            transition: all 0.3s ease;
        }

        .why-card:hover {
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }

        .why-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: rgba(41, 151, 255, 0.12);
            color: var(--apple-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 20px;
        }

        .why-card:nth-child(2) .why-icon {
            background: rgba(48, 209, 88, 0.12);
            color: var(--apple-green);
        }

        .why-card:nth-child(3) .why-icon {
            background: rgba(191, 90, 242, 0.12);
            color: var(--apple-purple);
        }

        .why-card h4 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .why-card p {
            font-size: 14.5px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Roadmap Timeline */
        .roadmap-timeline {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            position: relative;
        }

        .roadmap-step {
            background: var(--bg-card);
            border: 1px solid var(--bg-card-border);
            border-radius: 20px;
            padding: 28px 20px;
            position: relative;
            transition: all 0.3s ease;
        }

        .roadmap-step.active {
            border-color: var(--apple-blue);
            background: rgba(41, 151, 255, 0.05);
            box-shadow: 0 0 30px rgba(41, 151, 255, 0.12);
        }

        .roadmap-step.completed {
            border-color: rgba(48, 209, 88, 0.4);
        }

        .step-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 980px;
            margin-bottom: 14px;
        }

        .roadmap-step.completed .step-badge {
            background: rgba(48, 209, 88, 0.15);
            color: var(--apple-green);
        }

        .roadmap-step.active .step-badge {
            background: rgba(41, 151, 255, 0.2);
            color: var(--apple-blue);
        }

        .roadmap-step.planned .step-badge {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-muted);
        }

        .roadmap-step h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .roadmap-step p {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Implementation & Tech Architecture */
        .tech-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .tech-card {
            background: var(--bg-card);
            border: 1px solid var(--bg-card-border);
            border-radius: var(--card-radius);
            padding: 32px;
        }

        .tech-card h4 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tech-card ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .tech-card ul li {
            font-size: 14px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tech-card ul li i {
            color: var(--apple-blue);
            font-size: 18px;
        }

        /* Demo Video Section */
        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
            margin-bottom: 32px;
            background: #0a0a0c;
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
            border: 1px solid var(--bg-card-border);
            border-radius: var(--card-radius);
            padding: 32px;
            margin-bottom: 40px;
        }

        .video-desc-box h4 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .video-desc-box p {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        /* Prototype Live Cards */
        .proto-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .proto-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--bg-card-border);
            border-radius: 20px;
            padding: 24px;
            text-decoration: none;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all 0.3s ease;
        }

        .proto-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
        }

        .proto-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--blue-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .proto-card strong {
            font-size: 16px;
            display: block;
            margin-bottom: 2px;
        }

        .proto-card span {
            font-size: 13px;
            color: var(--text-secondary);
        }

        /* Screenshots Showcase (Apple Carousel) */
        .screenshots-wrap {
            display: flex;
            overflow-x: auto;
            gap: 24px;
            padding: 20px 0 40px;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .screenshots-wrap::-webkit-scrollbar {
            display: none;
        }

        .screenshot-item {
            flex: 0 0 280px;
            scroll-snap-align: center;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .screenshot-item:hover {
            transform: scale(1.04);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .screenshot-item img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Footer */
        footer {
            background: #08080a;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding: 60px 0 40px;
            text-align: center;
        }

        footer p {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        footer .pitch-tag {
            color: var(--text-secondary);
            font-weight: 600;
        }

        /* Responsive Breakpoints */
        @media (max-width: 992px) {
            .hero h1 { font-size: 48px; }
            .section-title { font-size: 36px; }
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
            .hero h1 { font-size: 38px; }
            .hero p { font-size: 18px; }
            .stats-grid { grid-template-columns: 1fr; }
            .team-grid { grid-template-columns: 1fr; }
            .stat-card .number { font-size: 38px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav>
        <a href="#hero" class="nav-logo">
            <div class="logo-icon">📚</div>
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
            <p>Marketplace • Express Yetkazib Berish • Nashriyot & Seller Boshqaruvi • Smart POS • AI Tavsiyalar</p>

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
                <a class="store-btn" href="https://kitobchi.com" target="_blank">
                    <i class="ti ti-world"></i>
                    <div class="store-text">
                        <span>Veb platforma</span>
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
                    <div class="number">15,000+</div>
                    <div class="label">Kitoblar Global Katalogi</div>
                </div>
                <div class="stat-card">
                    <div class="number">50+</div>
                    <div class="label">Hamkor Do'konlar & Nashriyotlar</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 1. MUAMMO → YECHIM -->
    <section class="section" id="problem">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">1. Tahlil & Yondashuv</div>
                <h2 class="section-title">Muammo → Yechim</h2>
                <p class="section-desc">Kitob va kanselyariya sanoatidagi asosiy tizimli muammolar va Kitobchi taqdim etayotgan zamonaviy raqamli yechimlar.</p>
            </div>

            <div class="problem-solution-grid">
                <!-- Problem Card -->
                <div class="ps-card problem">
                    <h3><i class="ti ti-alert-circle text-danger"></i> Mavjud Muammolar</h3>
                    <ul class="ps-list">
                        <li>
                            <i class="ti ti-x"></i>
                            <div>
                                <strong>Fragmentlashgan va tarqoq bozor</strong>
                                Kitobxon kerakli kitobni topish uchun o'nlab do'konlarga borishi yoki turli ijtimoiy tarmoq kanallarini qidirishga majbur.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-x"></i>
                            <div>
                                <strong>Do'konlar va nashriyotlarning raqamlashmaganligi</strong>
                                Aksariyat kichik kitob do'konlarida avtomatlashgan inventar, POS kassa va onlayn savdo boshqaruvi yo'q.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-x"></i>
                            <div>
                                <strong>Qimmat va uzoq logistika</strong>
                                Kitoblarni yetkazib berish 1–3 kun vaqt oladi, tezkor shahar ichi ekspress kuryerlik tizimi yo'lga qo'yilmagan.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-x"></i>
                            <div>
                                <strong>Yagona standartlashtirilgan katalog yo'qligi</strong>
                                ISBN, mualliflar va nashriyotlar bo'yicha yagona ma'lumotlar bazasi mavjud emasligi sababli narxlarni taqqoslash imkonsiz.
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
                                <strong>Yagona Global Marketplace & BuyBox</strong>
                                Barcha do'konlar takliflari bitta kitob kartasiga birlashtiriladi. Xaridor eng arzon va eng yaqin narxni tanlaydi.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-check"></i>
                            <div>
                                <strong>Kitobchi Express (1–2 soatda yetkazish)</strong>
                                Shahar ichida kitoblarni bir necha soat ichida yetkazib berish xizmati va viloyatlararo ishonchli pochta integratsiyasi.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-check"></i>
                            <div>
                                <strong>Kitobchi Business & Smart POS</strong>
                                Do'konlar va nashriyotlar uchun zamonaviy ERP/CRM: ombor hisobi, kassa, shtrix-kod skaner va avtomatik hisobotlar.
                            </div>
                        </li>
                        <li>
                            <i class="ti ti-check"></i>
                            <div>
                                <strong>AI Smart Qidiruv va Tavsiya Tizimi</strong>
                                Sun'iy intellekt orqali kitoblarni syujet, janr va qiziqishlar bo'yicha aniq topish hamda muqovalarni tiklash.
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
                    <div class="team-role">Founder & Lead Full Stack Architect</div>
                    <div class="team-skills">
                        <span class="skill-tag">Flutter</span>
                        <span class="skill-tag">Laravel / PHP</span>
                        <span class="skill-tag">MySQL</span>
                        <span class="skill-tag">DevOps & AWS</span>
                        <span class="skill-tag">AI Integration</span>
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
                    <div class="team-role">Co-Founder & Project / Operations Manager</div>
                    <div class="team-skills">
                        <span class="skill-tag">Project Management</span>
                        <span class="skill-tag">Seller Onboarding</span>
                        <span class="skill-tag">Logistics</span>
                        <span class="skill-tag">B2B Sales</span>
                    </div>
                    <div class="team-links">
                        <a href="https://t.me/kitobchi_support" target="_blank" title="Telegram"><i class="ti ti-brand-telegram"></i></a>
                    </div>
                </div>

                <!-- Member 3 -->
                <div class="team-card">
                    <div class="team-avatar">🎨</div>
                    <h3 class="team-name">Jonibek Fayzullayev</h3>
                    <div class="team-role">Lead Product & UI/UX Designer</div>
                    <div class="team-skills">
                        <span class="skill-tag">Figma</span>
                        <span class="skill-tag">Design Systems</span>
                        <span class="skill-tag">Mobile UI/UX</span>
                        <span class="skill-tag">Prototyping</span>
                    </div>
                    <div class="team-links">
                        <a href="https://t.me/kitobchi_support" target="_blank" title="Telegram"><i class="ti ti-brand-telegram"></i></a>
                    </div>
                </div>

                <!-- Member 4 -->
                <div class="team-card">
                    <div class="team-avatar">🚀</div>
                    <h3 class="team-name">Abuxusayn Mamatov</h3>
                    <div class="team-role">Growth & Marketing / SMM Specialist</div>
                    <div class="team-skills">
                        <span class="skill-tag">Targeting & Ads</span>
                        <span class="skill-tag">Community Growth</span>
                        <span class="skill-tag">Content Strategy</span>
                        <span class="skill-tag">Influencer Marketing</span>
                    </div>
                    <div class="team-links">
                        <a href="https://t.me/kitobchi_support" target="_blank" title="Telegram"><i class="ti ti-brand-telegram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. NEGA BIZNING JAMOA? -->
    <section class="section" id="why">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">3. Raqobatbardoshlik</div>
                <h2 class="section-title">Nima uchun jamoamiz bu muammoni hal qila oladi?</h2>
                <p class="section-desc">Biz shunchaki g'oya bosqichida emasmiz — soha ichida amaliy tajriba, real infratuzilma va tezkor texnik ustunlikka egamiz.</p>
            </div>

            <div class="why-grid">
                <div class="why-card">
                    <div class="why-icon"><i class="ti ti-bulb"></i></div>
                    <h4>Chuqur sohaviy domenga egalik</h4>
                    <p>Biz kitob bozori, do'konlar, nashriyotlar va xaridorlarning barcha og'riqli nuqtalarini bevosita 2+ yildan beri o'rganib, ularga moslashtirilgan vertikal yechim ishlab chiqdik.</p>
                </div>

                <div class="why-card">
                    <div class="why-icon"><i class="ti ti-device-mobile-check"></i></div>
                    <h4>100% ishlab turgan to'liq mahsulot</h4>
                    <p>App Store va Google Play'da mobil ilova, do'konlar uchun ERP boshqaruv paneli, avtomatlashgan global katalog va kuryerlik tizimi allaqachon real vaqtda ishlamoqda.</p>
                </div>

                <div class="why-card">
                    <div class="why-icon"><i class="ti ti-chart-arrows-vertical"></i></div>
                    <h4>Haqiqiy traksiya va o'sish dinamikasi</h4>
                    <p>10,000+ yuklab olish, 1,000+ muvaffaqiyatli buyurtma, 50+ ulangan do'konlar va barqaror daromad modeli bilan bozor talabi to'liq isbotlandi.</p>
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
                <p class="section-desc">Idea bosqichidan boshlab, MVP, ishga tushirish va kelajakdagi kengayish rejalari.</p>
            </div>

            <div class="roadmap-timeline">
                <!-- Step 1 -->
                <div class="roadmap-step completed">
                    <span class="step-badge">Bajarildi ✅</span>
                    <h4>1. Idea</h4>
                    <p>Bozor tahlili, 50+ do'konlar bilan intervyu va ekotizim arxitekturasi ishlab chiqildi.</p>
                </div>

                <!-- Step 2 -->
                <div class="roadmap-step completed">
                    <span class="step-badge">Bajarildi ✅</span>
                    <h4>2. Prototype</h4>
                    <p>Dastlabki mobil ilova va seller boshqaruv prototipi sinovdan muvaffaqiyatli o'tdi.</p>
                </div>

                <!-- Step 3 -->
                <div class="roadmap-step completed">
                    <span class="step-badge">Bajarildi ✅</span>
                    <h4>3. MVP</h4>
                    <p>Payme/Click to'lovlari, 10+ do'konlar integratsiyasi va birinchi yetkazib berishlar yo'lga qo'yildi.</p>
                </div>

                <!-- Step 4 -->
                <div class="roadmap-step active">
                    <span class="step-badge">Hozirgi Bosqich 🚀</span>
                    <h4>4. Launched</h4>
                    <p>10,000+ mijoz, 1,000+ buyurtma, App Store & Google Play, AI katalog va Express yetkazish faol.</p>
                </div>

                <!-- Step 5 -->
                <div class="roadmap-step planned">
                    <span class="step-badge">Kelgusi 📈</span>
                    <h4>5. Scale & POS</h4>
                    <p>Respublika bo'ylab Express tarmoq, Smart POS kassa apparatlari va B2B maktab/universitetlar ulanishi.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. AMALGA OSHIRISH & TEXNOLOGIYALAR & AI -->
    <section class="section" id="tech">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">5. Texnik Arxitektura</div>
                <h2 class="section-title">Yechimni qanday amalga oshiryapmiz?</h2>
                <p class="section-desc">Zamonaviy texnologiyalar staki, AI vositalari va yuqori yuklamalarga chidamli mikroservis arxitekturasi.</p>
            </div>

            <div class="tech-grid">
                <!-- Tech Stack -->
                <div class="tech-card">
                    <h4><i class="ti ti-stack-2 text-primary"></i> Texnologiyalar Staki</h4>
                    <ul>
                        <li><i class="ti ti-brand-flutter"></i> <strong>Mobile:</strong> Flutter (iOS & Android bitta kod bazasi)</li>
                        <li><i class="ti ti-brand-laravel"></i> <strong>Backend:</strong> Laravel 11 / PHP 8.3 RESTful API</li>
                        <li><i class="ti ti-database"></i> <strong>Database & Cache:</strong> MySQL + Redis Caching</li>
                        <li><i class="ti ti-brand-react"></i> <strong>Panel & Web:</strong> Inertia.js + React / TypeScript</li>
                        <li><i class="ti ti-cloud"></i> <strong>Infratuzilma:</strong> Docker, Nginx, Cloudflare CDN, AWS</li>
                    </ul>
                </div>

                <!-- AI Tools & Solutions -->
                <div class="tech-card">
                    <h4><i class="ti ti-sparkles text-warning"></i> AI Vositalari & Yechimlar</h4>
                    <ul>
                        <li><i class="ti ti-brain"></i> <strong>Gemini LLM:</strong> Kitoblar tavsifi, syujet tahlili va janrlarni avtomatik boyitish</li>
                        <li><i class="ti ti-search"></i> <strong>Vector Search:</strong> Ma'no va kontekst bo'yicha semantik qidiruv</li>
                        <li><i class="ti ti-photo-ai"></i> <strong>AI OCR & Muqova Tiklash:</strong> Skaner qilingan muqovalarni aniqlash va sifatini oshirish</li>
                        <li><i class="ti ti-smart-home"></i> <strong>Recommendation Engine:</strong> O'qish odatlariga asoslangan shaxsiy tavsiyalar</li>
                    </ul>
                </div>

                <!-- Business & POS System -->
                <div class="tech-card">
                    <h4><i class="ti ti-building-store text-success"></i> Biznes va Logistika</h4>
                    <ul>
                        <li><i class="ti ti-arrows-shuffle"></i> <strong>Smart BuyBox:</strong> Eng optimal narx va tezkor kuryerni tanlash</li>
                        <li><i class="ti ti-device-tablet"></i> <strong>Smart POS App:</strong> Do'konda kassa va shtrix-kod orqali savdo</li>
                        <li><i class="ti ti-truck-delivery"></i> <strong>Kitobchi Express:</strong> Kuryer ilovasi va buyurtmalarni jonli kuzatish</li>
                        <li><i class="ti ti-credit-card"></i> <strong>Fintech:</strong> Payme, Click, Uzum Pay va bo'lib to'lash tizimlari</li>
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
                <h2 class="section-title">🎬 Demo Video & Jonli Prototip</h2>
                <p class="section-desc">Kitobchi ekotizimining real hayotda ishlashi, mobil ilova va seller boshqaruv tizimi jarayoni.</p>
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
                <p>Ushbu videoda <strong>Kitobchi</strong> ekotizimining to'liq ishlash jarayoni amalda ko'rsatilgan:
                foydalanuvchi mobil ilova orqali kitoblarni qidirishi va buyurtma berishi, yagona katalog orqali do'konlar narxlarini taqqoslashi, do'konlar (sellerlar) uchun Boshqaruv ERP panelida buyurtmalarni qabul qilish va ombor hisobini yuritish, shuningdek Kitobchi Express orqali kuryerlik yetkazib berish mexanizmi batafsil namoyish etilgan.</p>
            </div>

            <!-- Working Prototype Links -->
            <div class="proto-grid">
                <a href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank" class="proto-card">
                    <div class="proto-icon"><i class="ti ti-brand-apple"></i></div>
                    <div>
                        <strong>App Store (iOS)</strong>
                        <span>Jonli ilovani yuklab olish &rarr;</span>
                    </div>
                </a>

                <a href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank" class="proto-card">
                    <div class="proto-icon"><i class="ti ti-brand-google-play"></i></div>
                    <div>
                        <strong>Google Play (Android)</strong>
                        <span>Jonli ilovani yuklab olish &rarr;</span>
                    </div>
                </a>

                <a href="https://kitobchi.com" target="_blank" class="proto-card">
                    <div class="proto-icon"><i class="ti ti-world"></i></div>
                    <div>
                        <strong>Veb Platforma</strong>
                        <span>kitobchi.com saytiga o'tish &rarr;</span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- 7. SKRINSHOTLAR SHOWCASE -->
    <section class="section" id="screenshots">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">Ilova Interfeysi</div>
                <h2 class="section-title">📱 Mobil Ilova Skrinshotlari</h2>
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
                    <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource221/v4/be/d9/cf/bed9cf03-5873-d921-e56c-9fa36a6bf7af/6_1-Frame-en-4-4.jpg/400x800bb.png" alt="Kitobchi — Seller paneli">
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
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 12px;">
                Barcha huquqlar himoyalangan. Toshkent, O'zbekiston.
            </p>
        </div>
    </footer>

</body>
</html>