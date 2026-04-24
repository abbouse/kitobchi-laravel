<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kitobchi — Kitob va kanselyariya savdosi uchun to‘liq ekotizim: Marketplace, Express, Business, POS va AI.">
    <title>Kitobchi Ecosystem</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #fff;
            color: #111;
            line-height: 1.6;
        }
        nav {
            position: sticky;
            top: 0;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #eee;
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }
        nav h1 {
            font-size: 20px;
            font-weight: 600;
        }
        nav a {
            margin-left: 24px;
            text-decoration: none;
            color: #444;
            font-size: 14px;
        }
        .hero {
            text-align: center;
            padding: 120px 20px 80px;
            max-width: 900px;
            margin: auto;
        }
        .hero h1 {
            font-size: 64px;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 20px;
        }
        .hero p {
            font-size: 22px;
            color: #666;
            max-width: 700px;
            margin: auto;
        }
        .buttons {
            margin-top: 40px;
        }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            margin: 6px;
            background: #111;
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
        }
        .stats {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin-top: 60px;
            flex-wrap: wrap;
        }
        .stat h2 {
            font-size: 42px;
            font-weight: 700;
        }
        .stat p {
            color: #666;
            font-size: 15px;
        }
        .section {
            max-width: 900px;
            margin: auto;
            padding: 100px 20px;
        }
        .section h2 {
            font-size: 42px;
            margin-bottom: 30px;
            text-align: center;
        }
        .card {
            background: #f5f5f7;
            padding: 32px;
            border-radius: 18px;
            margin-bottom: 20px;
        }
        .team {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }
        .roadmap {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }
        .step {
            text-align: center;
            padding: 24px;
            background: #f5f5f7;
            border-radius: 16px;
        }
        .screenshots {
            display: flex; /* Bir qatorga terish */
            overflow-x: auto; /* Suriladigan qilish */
            gap: 25px;
            padding: 20px 0;
            scroll-snap-type: x mandatory; /* Apple-dek silliq to'xtash */
            scrollbar-width: none; /* Firefox uchun scrollbarni yashirish */
            -ms-overflow-style: none; /* IE uchun */
            -webkit-overflow-scrolling: touch; /* iOS da silliq scroll */
        }

        .screenshots::-webkit-scrollbar {
            display: none; /* Chrome/Safari uchun scrollbarni yashirish */
        }

        .screenshots img {
            flex: 0 0 auto; /* Rasmlar qisqarib ketmasligi uchun */
            width: 280px; /* Rasmlar o'lchami */
            height: auto;
            border-radius: 24px; /* Apple-style ko'proq yumaloqlik */
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            scroll-snap-align: center; /* Surilganda markazda to'xtash */
            transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
            border: 1px solid #eee;
        }

        .screenshots img:hover {
            transform: scale(1.05);
        }

        /* Konteynerni biroz kengaytirish */
        #screenshots {
            max-width: 100%; /* Ekran bo'ylab surilishi uchun */
            padding-left: 5%; 
            padding-right: 5%;
        }
        iframe {
            width: 100%;
            height: 420px;
            border-radius: 18px;
            border: none;
        }
        footer {
            text-align: center;
            padding: 40px 20px;
            background: #f5f5f7;
            color: #666;
            font-size: 14px;
        }
        @media (max-width: 700px) {
            .hero h1 {
                font-size: 42px;
            }
            .section h2 {
                font-size: 32px;
            }
            nav {
                padding: 16px 20px;
            }
        }
    </style>
</head>
<body>

    <!-- NAV -->
    <nav>
        <h1>📚 Kitobchi</h1>
        <div>
            <a href="#problem">Muammo</a>
            <a href="#team">Jamoa</a>
            <a href="#roadmap">Yo'l xaritasi</a>
            <a href="#demo">Demo</a>
            <a href="#screenshots">Skrinshotlar</a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <h1>Kitob savdosi uchun<br>yagona ekotizim</h1>
        <p>Marketplace • Express • Business • POS • AI</p>
        <div class="buttons">
            <a class="btn" href="https://apps.apple.com/uz/app/kitobchi/id6753818078" target="_blank">
                📱 App Store
            </a>
            <a class="btn" href="https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi" target="_blank">
                🤖 Play Market
            </a>
        </div>
        <div class="stats">
            <div class="stat">
                <h2>10+</h2>
                <p>Do'kon</p>
            </div>
            <div class="stat">
                <h2>10,000+</h2>
                <p>Mijoz</p>
            </div>
            <div class="stat">
                <h2>800+</h2>
                <p>Buyurtma</p>
            </div>
        </div>
    </section>

    <!-- PROBLEM -->
    <section class="section" id="problem">
        <h2>Muammo → Yechim</h2>
        <div class="card">
            <p>Kitob va kanselyariya mahsulotlari bozori yetarli darajada raqamlashtirilmagan.</p>
            <br>
            <p><strong>Kitobchi</strong> ushbu muammoni quyidagi to‘liq ekotizim orqali hal qiladi:</p>
            <ul style="margin-top:15px; padding-left:20px;">
                <li>✅ Kitobchi Market</li>
                <li>✅ Kitobchi Express</li>
                <li>✅ Kitobchi Business</li>
                <li>✅ POS tizimlari</li>
                <li>✅ AI analiz tizimlari</li>
            </ul>
        </div>
    </section>

    <!-- TEAM -->
    <section class="section" id="team">
        <h2>Jamoa</h2>
        <div class="team">
            <div class="card">
                <h3>Abbos Turdaliyev</h3>
                <p>Founder<br>Full Stack Developer</p>
            </div>
            <div class="card">
                <h3>Kamoliddin Hikmatov</h3>
                <p>Project Manager</p>
            </div>
            <div class="card">
                <h3>Jonibek Fayzullayev</h3>
                <p>UX/UI Developer</p>
            </div>
            <div class="card">
                <h3>Abuxusayn Mamatov</h3>
                <p>SMM Specialist</p>
            </div>
        </div>
    </section>

    <!-- ROADMAP -->
    <section class="section" id="roadmap">
        <h2>Yo'l xaritasi</h2>
        <div class="roadmap">
            <div class="step">
                <h3>Idea</h3>
                <p>Muammo aniqlangan</p>
            </div>
            <div class="step">
                <h3>Prototype</h3>
                <p>Dastlabki tizim ishlab chiqildi</p>
            </div>
            <div class="step">
                <h3>MVP</h3>
                <p>Platforma ishga tushdi</p>
            </div>
            <div class="step">
                <h3>Launched</h3>
                <p>Real foydalanuvchilar mavjud</p>
            </div>
        </div>
    </section>

    <!-- DEMO -->
    <section class="section" id="demo">
        <h2>🎬 Demo</h2>
        
        <div class="card">
            <h3>Demo Video</h3>
            <iframe src="https://www.youtube.com/embed/v1e7o5mcsJs" title="Kitobchi Demo Video" allowfullscreen loading="lazy"></iframe>
        </div>

        <div class="card">
            <h3>Kitobchi Business Video</h3>
            <iframe src="https://www.youtube.com/embed/Hva0gLLfL5Y" title="Kitobchi Business Video" allowfullscreen loading="lazy"></iframe>
        </div>

        <div class="card">
            <h3>Demo Tavsifi</h3>
            <p>Kitobchi ekotizimi ishlash jarayoni, buyurtma berish tizimi, seller boshqaruvi va delivery jarayoni videoda ko‘rsatilgan.</p>
        </div>

        <div class="card">
            <h3>Presentation</h3>
            <iframe src="https://docs.google.com/presentation/d/12-aZlcS0CEVpwNICWVqPK_kkcslN1CLN1RqonjozuuU/embed?start=false" title="Kitobchi Presentation" allowfullscreen loading="lazy"></iframe>
        </div>
    </section>

    <!-- SKRINSHOTLAR -->
    <section class="section" id="screenshots">
        <h2>📱 Ilova Skrinshotlari</h2>
        <div class="screenshots">
            <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource211/v4/36/64/8c/36648c98-a120-b8ff-1d7e-f931ad5c5b86/6_1-Frame-en-1-2.jpg/400x800bb.png" alt="Kitobchi — Bosh sahifa">
            <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource211/v4/c2/2a/e0/c22ae0ca-a8e2-89ae-8409-9350c9d13a42/6_1-Frame-en-2-2.jpg/400x800bb.png" alt="Kitobchi — Kitoblar katalogi">
            <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource211/v4/68/83/eb/6883eb2b-f0f9-95af-bf96-1de51fdd242b/6_1-Frame-en-3.jpg/400x800bb.png" alt="Kitobchi — Buyurtma berish">
            <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource221/v4/be/d9/cf/bed9cf03-5873-d921-e56c-9fa36a6bf7af/6_1-Frame-en-4-4.jpg/400x800bb.png" alt="Kitobchi — Seller paneli">
            <img src="https://is1-ssl.mzstatic.com/image/thumb/PurpleSource221/v4/9f/1b/bb/9f1bbbf5-6afc-c886-68a6-0ac31c90e68b/6_1-Frame-en-5.jpg/400x800bb.png" alt="Kitobchi — Express yetkazib berish">
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <p>&copy; 2026 Kitobchi. AIFU (Aniq va Ijtimoiy Fanlar Universiteti)</p>
        <p>Pitch day tanlovi uchun tayyorlandi</p>
    </footer>

</body>
</html><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/demo.blade.php ENDPATH**/ ?>