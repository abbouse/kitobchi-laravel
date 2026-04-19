@extends('legal.layouts.app')

@section('title', 'Maxfiylik Siyosati')

@section('content')
<article class="l-doc-card">
<div class="l-doc-body kc-doc-static">
    <!-- Document Header -->
    <div class="doc-header">
        <h1 class="doc-title">Maxfiylik Siyosati</h1>
        <p class="doc-subtitle">Shaxsiy ma'lumotlarni qayta ishlash va himoya qilish</p>
        <div class="doc-meta">
            <span class="meta-item">
                <i class="fas fa-calendar"></i>
                Oxirgi yangilanish: {{ date('d.m.Y') }}
            </span>
            <span class="meta-item">
                <i class="fas fa-shield-alt"></i>
                Versiya 2.0
            </span>
            <span class="meta-item">
                <i class="fas fa-file-contract"></i>
                Hujjat №PRIV-2025-001
            </span>
        </div>
    </div>

    <!-- Section 1: Kirish -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">1</span>
            KIRISH
        </h2>
        
        <p class="doc-paragraph">
            <strong>1.1.</strong> Mazkur Maxfiylik siyosati (keyingi o'rinlarda — «Siyosat») <strong>"Kitobchi" MChJ</strong> (STIR: [sizning STIR raqamingiz]) (keyingi o'rinlarda — «Kompaniya», «biz», «bizning») tomonidan Foydalanuvchilarning shaxsiy ma'lumotlarini yig'ish, qayta ishlash, saqlash va himoya qilish tamoyillarini belgilaydi.
        </p>

        <p class="doc-paragraph">
            <strong>1.2.</strong> Ushbu Siyosat quyidagi qonunchilik hujjatlariga muvofiq ishlab chiqilgan:
        </p>

        <ul class="doc-list">
            <li>O'zbekiston Respublikasining "Shaxsga doir ma'lumotlar to'g'risida"gi Qonuni (2019-yil 2-iyul);</li>
            <li>O'zbekiston Respublikasining "Elektron tijorat to'g'risida"gi Qonuni;</li>
            <li>O'zbekiston Respublikasining "Reklama to'g'risida"gi Qonuni;</li>
            <li>GDPR (Yevropa Ittifoqi umumiy ma'lumotlarni himoya qilish reglamenti) ning asosiy tamoyillari.</li>
        </ul>

        <p class="doc-paragraph">
            <strong>1.3.</strong> "Kitobchi" mobil ilovasidan (keyingi o'rinlarda — «Ilova») yoki veb-saytimizdan foydalanish orqali siz mazkur Maxfiylik siyosatiga rozilik bildirasiz. Agar siz ushbu Siyosatga rozi bo'lmasangiz, Ilovadan foydalanmang.
        </p>

        <div class="important-box">
            <div class="box-title">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Muhim!</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Biz sizning maxfiyligingizni jiddiy qabul qilamiz va shaxsiy ma'lumotlaringizni eng yuqori standartlar bo'yicha himoya qilishga intilamiz. Ushbu siyosat shaffof va tushunarli bo'lishi uchun yaratilgan.
            </p>
        </div>
    </div>

    <!-- Section 2: Ta'riflar -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">2</span>
            TA'RIFLAR
        </h2>

        <p class="doc-paragraph">
            <strong>2.1. Shaxsiy ma'lumotlar</strong> — ma'lum yoki aniqlanishi mumkin bo'lgan jismoniy shaxsga tegishli bo'lgan har qanday ma'lumot.
        </p>

        <p class="doc-paragraph">
            <strong>2.2. Qayta ishlash</strong> — shaxsiy ma'lumotlar bilan bajariladigan har qanday amal yoki amallar to'plami, jumladan: yig'ish, ro'yxatga olish, tashkil etish, saqlash, o'zgartirish, foydalanish, uzatish, bloklash va yo'q qilish.
        </p>

        <p class="doc-paragraph">
            <strong>2.3. Foydalanuvchi</strong> — Ilovadan yoki veb-saytimizdan foydalanadigan har qanday shaxs.
        </p>

        <p class="doc-paragraph">
            <strong>2.4. Cookie-fayllar</strong> — foydalanuvchi qurilmasida saqlanadigan kichik matnli fayllar bo'lib, veb-sayt yoki ilovadan foydalanish tajribasini yaxshilash uchun ishlatiladi.
        </p>

        <p class="doc-paragraph">
            <strong>2.5. Uchinchi tomon xizmatlari</strong> — biz bilan hamkorlik qiladigan va ma'lum funktsiyalarni ta'minlovchi tashqi kompaniyalar (to'lov operatorlari, tahlil xizmatlari va h.k.).
        </p>
    </div>

    <!-- Section 3: Yig'iladigan ma'lumotlar -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">3</span>
            BIZ YIG'ADIGAN MA'LUMOTLAR
        </h2>

        <h3 class="subsection-title">3.1. Siz tomonidan taqdim etiladigan ma'lumotlar:</h3>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-user"></i>
                <strong>Ro'yxatdan o'tish va akkaunt ma'lumotlari:</strong>
            </div>
            <ul class="doc-list" style="margin-bottom: 0;">
                <li>Ism, familiya va otangizning ismi (F.I.Sh.)</li>
                <li>Elektron pochta manzili</li>
                <li>Telefon raqami</li>
                <li>Tug'ilgan sana (ixtiyoriy)</li>
                <li>Parol (shifrlangan holda saqlanadi)</li>
            </ul>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-shopping-cart"></i>
                <strong>Buyurtma va yetkazib berish ma'lumotlari:</strong>
            </div>
            <ul class="doc-list" style="margin-bottom: 0;">
                <li>Yetkazib berish manzili (ko'cha, uy, xonadon raqami)</li>
                <li>Shahar/tuman</li>
                <li>Qabul qiluvchi ismi va telefon raqami</li>
                <li>Buyurtma tarixi va tafsilotlari</li>
            </ul>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-credit-card"></i>
                <strong>To'lov ma'lumotlari:</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 8px;">
                <strong>MUHIM:</strong> To'lov kartasi ma'lumotlari (karta raqami, amal qilish muddati, CVV kodi) bizning serverlarimizda SAQLANMAYDI.
            </p>
            <ul class="doc-list" style="margin-bottom: 0;">
                <li>To'lov ma'lumotlari <strong>Payme</strong> to'lov operatori tomonidan PCI DSS xavfsizlik standartlariga muvofiq qayta ishlanadi</li>
                <li>Biz faqat to'lov tokenini (shifrlangan identifikator) olamiz, bu premium obunani avtomatik davom ettirish uchun ishlatiladi</li>
                <li>To'lov tarixi va tranzaksiya ma'lumotlari saqlanadi (summa, sana, holat)</li>
            </ul>
        </div>

        <h3 class="subsection-title">3.2. Avtomatik yig'iladigan ma'lumotlar:</h3>

        <table class="doc-table">
            <thead>
                <tr>
                    <th>Ma'lumot turi</th>
                    <th>Tavsif</th>
                    <th>Maqsad</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Qurilma ma'lumotlari</strong></td>
                    <td>Qurilma modeli, operatsion tizim, versiya, ekran o'lchami</td>
                    <td>Ilova mosligini ta'minlash</td>
                </tr>
                <tr>
                    <td><strong>IP manzil</strong></td>
                    <td>Internetga ulanish IP manzili</td>
                    <td>Xavfsizlik va joylashuv tahlili</td>
                </tr>
                <tr>
                    <td><strong>Joylashuv ma'lumotlari</strong></td>
                    <td>Shahar/mintaqa (aniq GPS emas)</td>
                    <td>Yetkazib berish xizmatini yaxshilash</td>
                </tr>
                <tr>
                    <td><strong>Foydalanish ma'lumotlari</strong></td>
                    <td>Qaysi sahifalarni ko'rganingiz, qancha vaqt sarflaganingiz</td>
                    <td>Tajribani yaxshilash, tahlil</td>
                </tr>
                <tr>
                    <td><strong>Cookie va identifikatorlar</strong></td>
                    <td>Brauzer cookie-lari, qurilma ID</td>
                    <td>Sessiyani saqlash, personalizatsiya</td>
                </tr>
            </tbody>
        </table>

        <h3 class="subsection-title">3.3. Uchinchi tomonlardan olingan ma'lumotlar:</h3>

        <ul class="doc-list">
            <li><strong>Ijtimoiy tarmoqlar:</strong> Agar siz Google, Facebook yoki boshqa ijtimoiy tarmoqlar orqali ro'yxatdan o'tsangiz, biz ulardan sizning profil ma'lumotlaringizni (ism, email, profil rasmi) olishimiz mumkin</li>
            <li><strong>Tahlil xizmatlari:</strong> Google Analytics, Firebase Analytics kabi xizmatlardan foydalanish statistikasi</li>
            <li><strong>Kuryerlik xizmatlari:</strong> Buyurtma yetkazib berilganligi haqida holat ma'lumotlari</li>
        </ul>
    </div>

    <!-- Section 4: Ma'lumotlardan foydalanish maqsadlari -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">4</span>
            MA'LUMOTLARDAN FOYDALANISH MAQSADLARI
        </h2>

        <p class="doc-paragraph">
            <strong>4.1.</strong> Biz sizning shaxsiy ma'lumotlaringizdan quyidagi maqsadlarda foydalanamiz:
        </p>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-check-circle"></i>
                <strong>Asosiy xizmatlarni ko'rsatish:</strong>
            </div>
            <ul class="doc-list" style="margin-bottom: 0;">
                <li>Akkauntingizni yaratish va boshqarish</li>
                <li>Buyurtmalaringizni qayta ishlash va yetkazib berish</li>
                <li>To'lovlarni qayta ishlash (Payme orqali)</li>
                <li>Premium obuna xizmatlarini taqdim etish</li>
                <li>Mijozlarga yordam ko'rsatish va savollaringizga javob berish</li>
            </ul>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-chart-line"></i>
                <strong>Xizmatni yaxshilash:</strong>
            </div>
            <ul class="doc-list" style="margin-bottom: 0;">
                <li>Ilovaning ishlashini tahlil qilish va optimallash</li>
                <li>Foydalanuvchi tajribasini personalizatsiya qilish</li>
                <li>Yangi funktsiyalarni ishlab chiqish</li>
                <li>Texnik muammolarni tuzatish</li>
            </ul>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-bell"></i>
                <strong>Muloqot va marketing:</strong>
            </div>
            <ul class="doc-list" style="margin-bottom: 0;">
                <li>Buyurtma holati haqida bildirishnomalar yuborish</li>
                <li>Maxsus takliflar va chegirmalar haqida xabardor qilish (faqat rozilik bilan)</li>
                <li>Yangiliklar va yangi mahsulotlar haqida ma'lumot berish</li>
                <li>So'rovnomalar va fikr-mulohazalarni yig'ish</li>
            </ul>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-shield-alt"></i>
                <strong>Xavfsizlik va qonuniylik:</strong>
            </div>
            <ul class="doc-list" style="margin-bottom: 0;">
                <li>Firibgarlik va suiiste'mollikni aniqlash va oldini olish</li>
                <li>Xavfsizlik tadbirlarini amalga oshirish</li>
                <li>Qonuniy majburiyatlarni bajarish</li>
                <li>Nizolarni hal qilish</li>
            </ul>
        </div>
    </div>

    <!-- Section 5: Ma'lumotlarni ulashish -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">5</span>
            MA'LUMOTLARNI UCHINCHI TOMONLAR BILAN ULASHISH
        </h2>

        <div class="warning-box">
            <div class="box-title">
                <i class="fas fa-info-circle"></i>
                <strong>Asosiy tamoyil:</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Biz sizning shaxsiy ma'lumotlaringizni HECH QACHON sotmaymiz va tijorat maqsadlarida uchinchi tomonlarga bermaymiz. Ma'lumotlar faqat quyidagi hollarda va faqat zarur hajmda ulashiladi:
            </p>
        </div>

        <h3 class="subsection-title">5.1. Xizmat ko'rsatuvchi hamkorlar:</h3>

        <table class="doc-table">
            <thead>
                <tr>
                    <th>Xizmat</th>
                    <th>Hamkor</th>
                    <th>Qanday ma'lumotlar</th>
                    <th>Maqsad</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>To'lov qayta ishlash</strong></td>
                    <td>Payme</td>
                    <td>To'lov ma'lumotlari, buyurtma summasi</td>
                    <td>To'lovlarni xavfsiz qayta ishlash</td>
                </tr>
                <tr>
                    <td><strong>Yetkazib berish</strong></td>
                    <td>Kuryerlik xizmatlari</td>
                    <td>Ism, telefon, yetkazib berish manzili</td>
                    <td>Buyurtmani yetkazib berish</td>
                </tr>
                <tr>
                    <td><strong>Tahlil</strong></td>
                    <td>Google Analytics, Firebase</td>
                    <td>Qurilma ma'lumotlari, foydalanish statistikasi</td>
                    <td>Ilovani yaxshilash</td>
                </tr>
                <tr>
                    <td><strong>Push bildirishnomalar</strong></td>
                    <td>Firebase Cloud Messaging</td>
                    <td>Qurilma tokeni</td>
                    <td>Bildirishnomalarni yuborish</td>
                </tr>
                <tr>
                    <td><strong>Cloud hosting</strong></td>
                    <td>AWS / Google Cloud</td>
                    <td>Barcha saqlangan ma'lumotlar</td>
                    <td>Ma'lumotlarni xavfsiz saqlash</td>
                </tr>
            </tbody>
        </table>

        <h3 class="subsection-title">5.2. Qonuniy talablar:</h3>

        <p class="doc-paragraph">
            Biz shaxsiy ma'lumotlarni quyidagi hollarda oshkor qilishimiz mumkin:
        </p>

        <ul class="doc-list">
            <li>Sud qarorlari va qonuniy talablarga javob sifatida</li>
            <li>Davlat organlari rasmiy so'roviga binoan</li>
            <li>O'z huquqlarimizni, mulkimizni yoki xavfsizligimizni himoya qilish uchun</li>
            <li>Firibgarlik yoki boshqa jinoyat harakatlarini tekshirish uchun</li>
        </ul>

        <h3 class="subsection-title">5.3. Biznes o'tkazmalari:</h3>

        <p class="doc-paragraph">
            Agar kompaniyamiz qo'shilish, sotib olinish yoki aktivlarni sotish jarayonida bo'lsa, sizning shaxsiy ma'lumotlaringiz o'tkazilishi mumkin. Bunday holda, biz sizni oldindan xabardor qilamiz va yangi siyosat haqida ma'lumot beramiz.
        </p>
    </div>

    <!-- Section 6: Ma'lumotlarni saqlash -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">6</span>
            MA'LUMOTLARNI SAQLASH VA HIMOYA QILISH
        </h2>

        <h3 class="subsection-title">6.1. Saqlash muddati:</h3>

        <table class="doc-table">
            <thead>
                <tr>
                    <th>Ma'lumot turi</th>
                    <th>Saqlash muddati</th>
                    <th>Sabab</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Akkaunt ma'lumotlari</td>
                    <td>Akkaunt faol bo'lguncha</td>
                    <td>Xizmat ko'rsatish</td>
                </tr>
                <tr>
                    <td>Buyurtma tarixi</td>
                    <td>5 yil</td>
                    <td>Buxgalteriya va qonuniy talablar</td>
                </tr>
                <tr>
                    <td>To'lov ma'lumotlari</td>
                    <td>3 yil</td>
                    <td>Moliyaviy hisobotlar</td>
                </tr>
                <tr>
                    <td>Marketing rozilik</td>
                    <td>Bekor qilinguncha</td>
                    <td>Marketing maqsadlari</td>
                </tr>
                <tr>
                    <td>Texnik loglar</td>
                    <td>90 kun</td>
                    <td>Xavfsizlik va tizimni optimallashtirish</td>
                </tr>
            </tbody>
        </table>

        <p class="doc-paragraph">
            <strong>6.2.</strong> Akkauntingizni o'chirsangiz, sizning shaxsiy ma'lumotlaringiz 30 kun ichida tizimdan o'chiriladi, faqat qonuniy talablarga muvofiq saqlanishi kerak bo'lgan ma'lumotlar bundan mustasno.
        </p>

        <h3 class="subsection-title">6.3. Xavfsizlik choralari:</h3>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-lock"></i>
                <strong>Biz qo'llayotgan texnik va tashkiliy choralar:</strong>
            </div>
            <ul class="doc-list" style="margin-bottom: 0;">
                <li><strong>Shifrlash:</strong> Barcha sezgir ma'lumotlar SSL/TLS protokoli orqali shifrlangan holda uzatiladi</li>
                <li><strong>Parol xavfsizligi:</strong> Parollar bcrypt algoritmi bilan shifrlangan holda saqlanadi</li>
                <li><strong>Xavfsiz serverlar:</strong> Ma'lumotlar ISO 27001 sertifikatiga ega ma'lumotlar markazlarida saqlanadi</li>
                <li><strong>Kirish nazorati:</strong> Faqat vakolatli xodimlar kerakli ma'lumotlarga kirish huquqiga ega</li>
                <li><strong>Doimiy monitoring:</strong> Tizim xavfsizligi 24/7 kuzatiladi</li>
                <li><strong>Muntazam tekshiruvlar:</strong> Xavfsizlik auditlari yiliga kamida 2 marta o'tkaziladi</li>
                <li><strong>Zaxira nusxalari:</strong> Ma'lumotlar muntazam ravishda zaxiralanadi</li>
                <li><strong>Firewall va DDoS himoyasi:</strong> Tashqi tajovuzlardan himoya</li>
            </ul>
        </div>

        <div class="warning-box">
            <div class="box-title">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Muhim ogohlantirish:</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Internetda 100% xavfsizlikni kafolatlash mumkin emas. Biz maksimal xavfsizlikni ta'minlash uchun sa'y qilsak ham, siz ham o'z akkauntingiz xavfsizligiga mas'ulsiz. Parolingizni hech kimga aytmang va murakkab parollar ishlating. Agar akkauntingiz buzilganidan shubhalansangiz, darhol bizga xabar bering.
            </p>
        </div>
    </div>

    <!-- Section 7: Sizning huquqlaringiz -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">7</span>
            SIZNING HUQUQLARINGIZ
        </h2>

        <p class="doc-paragraph">
            <strong>7.1.</strong> O'zbekiston Respublikasi qonunchiligiga muvofiq, siz quyidagi huquqlarga egasiz:
        </p>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-user-check"></i>
                <strong>1. Ma'lumotga kirish huquqi</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Siz biz haqingizda qanday shaxsiy ma'lumotlarni saqlayotganimizni bilish va ularning nusxasini olish huquqiga egasiz. Ilova sozlamalarida "Mening ma'lumotlarim" bo'limiga kiring yoki support@kitobchi.uz manziliga murojaat qiling.
            </p>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-edit"></i>
                <strong>2. Tuzatish huquqi</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Agar sizning ma'lumotlaringiz noto'g'ri yoki to'liq bo'lmasa, siz ularni tuzatishni talab qilishingiz mumkin. Aksariyat ma'lumotlarni Ilova sozlamalarida o'zingiz o'zgartirishingiz mumkin.
            </p>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-trash"></i>
                <strong>3. O'chirish huquqi ("unutilish huquqi")</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Siz ma'lum hollarda shaxsiy ma'lumotlaringizni o'chirishni talab qilishingiz mumkin. Akkauntni o'chirish Ilova sozlamalarida "Akkauntni o'chirish" orqali amalga oshiriladi. Qonuniy saqlash majburiyatlari mavjud ma'lumotlar bundan mustasno.
            </p>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-ban"></i>
                <strong>4. Qayta ishlashni cheklash huquqi</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Siz ma'lum hollarda ma'lumotlaringizni qayta ishlashni cheklashni so'rashingiz mumkin. Masalan, marketing maqsadlari uchun ma'lumotlardan foydalanishni to'xtatishni talab qilishingiz mumkin.
            </p>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-file-export"></i>
                <strong>5. Ma'lumotlarni ko'chirish huquqi</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Siz o'zingiz taqdim etgan shaxsiy ma'lumotlarni strukturlangan, keng qo'llaniladigan va mashinada o'qiladigan formatda (JSON, CSV) olish huquqiga egasiz.
            </p>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-times-circle"></i>
                <strong>6. Rozilikning bekor qilish huquqi</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Siz istalgan vaqtda roziliklaringizni (masalan, marketing xabarlar) bekor qilishingiz mumkin. Bu orqaga qarab ta'sir qilmaydi, ya'ni oldingi qayta ishlashni qonunga xilof qilmaydi.
            </p>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-exclamation"></i>
                <strong>7. E'tiroz bildirish huquqi</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Siz ma'lum maqsadlar uchun (xususan, marketing uchun) shaxsiy ma'lumotlaringizni qayta ishlashga e'tiroz bildirishingiz mumkin.
            </p>
        </div>

        <h3 class="subsection-title">7.2. Huquqlaringizni amalga oshirish:</h3>

        <p class="doc-paragraph">
            Yuqoridagi huquqlardan foydalanish uchun:
        </p>

        <ul class="doc-list">
            <li>Ilovadagi "Sozlamalar" > "Maxfiylik va xavfsizlik" bo'limiga kiring</li>
            <li>Yoki email yuboring: <strong>privacy@kitobchi.uz</strong></li>
            <li>Yoki telefon qiling: <strong>+998 XX XXX-XX-XX</strong></li>
        </ul>

        <p class="doc-paragraph">
            Biz sizning so'rovingizga 10 (o'n) ish kuni ichida javob beramiz. Murakkabroq hollarda bu muddat 30 kungacha uzaytirilishi mumkin, bunda siz xabardor qilinasiz.
        </p>
    </div>

    <!-- Section 8: Cookie va tracking -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">8</span>
            COOKIE VA KUZATUV TEXNOLOGIYALARI
        </h2>

        <h3 class="subsection-title">8.1. Cookie nima va ulardan qanday foydalanamiz?</h3>

        <p class="doc-paragraph">
            Cookie-fayllar — veb-sayt yoki ilova sizning qurilmangizga saqlaydigan kichik matnli fayllar. Ular sizning foydalanish tajribangizni yaxshilash, sozlamalaringizni eslab qolish va qulayroq xizmat ko'rsatish uchun ishlatiladi.
        </p>

        <table class="doc-table">
            <thead>
                <tr>
                    <th>Cookie turi</th>
                    <th>Maqsad</th>
                    <th>Muddati</th>
                    <th>Majburiylik</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Zaruriy cookie</strong></td>
                    <td>Ilovaning asosiy funktsiyalari uchun (sessiya, xavfsizlik)</td>
                    <td>Sessiya tugagunga qadar</td>
                    <td>Majburiy</td>
                </tr>
                <tr>
                    <td><strong>Funksional cookie</strong></td>
                    <td>Sozlamalarni eslab qolish (til, mavzu)</td>
                    <td>1 yil</td>
                    <td>Ixtiyoriy</td>
                </tr>
                <tr>
                    <td><strong>Tahliliy cookie</strong></td>
                    <td>Foydalanishni tahlil qilish, xizmatni yaxshilash</td>
                    <td>2 yil</td>
                    <td>Ixtiyoriy</td>
                </tr>
                <tr>
                    <td><strong>Marketing cookie</strong></td>
                    <td>Maqsadli reklama ko'rsatish</td>
                    <td>6 oy - 1 yil</td>
                    <td>Ixtiyoriy</td>
                </tr>
            </tbody>
        </table>

        <h3 class="subsection-title">8.2. Biz foydalanadigan asosiy cookie va texnologiyalar:</h3>

        <ul class="doc-list">
            <li><strong>Google Analytics:</strong> Foydalanuvchilar soni, sahifalar ko'rinishlari, sessiya davomiyligi va h.k.ni tahlil qilish</li>
            <li><strong>Firebase Analytics:</strong> Mobil ilovada foydalanuvchi xatti-harakatlarini kuzatish</li>
            <li><strong>Facebook Pixel:</strong> Marketing samaradorligini o'lchash (faqat rozilik bilan)</li>
            <li><strong>Hotjar:</strong> Issiqlik xaritalari va foydalanuvchi tajribasini yaxshilash</li>
        </ul>

        <h3 class="subsection-title">8.3. Cookie-larni boshqarish:</h3>

        <p class="doc-paragraph">
            Siz cookie-larni quyidagi usullar bilan boshqarishingiz mumkin:
        </p>

        <ul class="doc-list">
            <li>Ilovada: Sozlamalar > Maxfiylik > Cookie sozlamalari</li>
            <li>Brauzerda: Brauzer sozlamalarida cookie-larni bloklash yoki o'chirish</li>
            <li>Mobil qurilmada: Qurilma sozlamalarida reklama identifikatorini cheklash</li>
        </ul>

        <div class="warning-box">
            <div class="box-title">
                <i class="fas fa-info-circle"></i>
                <strong>Eslatma:</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Agar siz zaruriy cookie-larni o'chirsangiz, Ilova to'g'ri ishlamasligi mumkin. Tahliliy va marketing cookie-larni o'chirish asosiy funktsiyalarga ta'sir qilmaydi.
            </p>
        </div>
    </div>

    <!-- Section 9: Bolalar maxfiyligi -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">9</span>
            BOLALAR MAXFIYLIGI
        </h2>

        <div class="important-box">
            <div class="box-title">
                <i class="fas fa-child"></i>
                <strong>14 yoshgacha bo'lgan bolalar</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 8px;">
                Bizning xizmatlarimiz 14 yoshdan kichik bolalar uchun mo'ljallanmagan. Biz ongli ravishda 14 yoshgacha bo'lgan bolalardan shaxsiy ma'lumotlarni yig'maymiz.
            </p>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Agar siz 14 yoshdan kichik bo'lgan bolaning shaxsiy ma'lumotlarini biz yig'ganimizni bilsangiz, darhol bizga xabar bering: <strong>privacy@kitobchi.uz</strong>. Biz bunday ma'lumotlarni zudlik bilan o'chiramiz.
            </p>
        </div>

        <div class="info-box">
            <div class="box-title">
                <i class="fas fa-users"></i>
                <strong>14-18 yosh oralig'idagi voyaga yetmaganlar</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                14 yoshdan 18 yoshgacha bo'lgan voyaga yetmaganlar bizning xizmatlarimizdan foydalanishlari mumkin, lekin faqat ota-onalari yoki qonuniy vakillari roziligiga ega holda. Biz ota-onalardan bolalarining onlayn faoliyatini kuzatishni so'raymiz.
            </p>
        </div>

        <p class="doc-paragraph">
            <strong>9.3.</strong> Premium obuna ichidagi bolalar uchun kontent (reels, chat-bot) yoshga mos va ta'limiy maqsadlarga yo'naltirilgan. Biz bolalar xavfsizligini ta'minlash uchun barcha zarur choralarni ko'ramiz.
        </p>
    </div>

    <!-- Section 10: Xalqaro ma'lumotlar uzatish -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">10</span>
            XALQARO MA'LUMOTLAR UZATISH
        </h2>

        <p class="doc-paragraph">
            <strong>10.1.</strong> Sizning shaxsiy ma'lumotlaringiz asosan O'zbekiston Respublikasi hududidagi serverlarda saqlanadi.
        </p>

        <p class="doc-paragraph">
            <strong>10.2.</strong> Biroq, ba'zi xizmat ko'rsatuvchi hamkorlarimiz (Google Cloud, AWS, Firebase) xalqaro kompaniyalar bo'lib, sizning ma'lumotlaringiz ularning O'zbekistondan tashqaridagi serverlariga uzatilishi mumkin.
        </p>

        <p class="doc-paragraph">
            <strong>10.3.</strong> Bunday uzatishlar faqat quyidagi shartlarda amalga oshiriladi:
        </p>

        <ul class="doc-list">
            <li>Qabul qiluvchi davlat yetarli darajada ma'lumotlarni himoya qilishi (masalan, Yevropa Ittifoqi)</li>
            <li>Xizmat ko'rsatuvchi standart shartnoma shartlarini qo'llaydi (EU Standard Contractual Clauses)</li>
            <li>Ma'lumotlar shifrlangan holda uzatiladi va saqlanadi</li>
            <li>Siz bunday uzatishga rozilik bildirgan bo'lasiz</li>
        </ul>
    </div>

    <!-- Section 11: Ma'lumotlar buzilishi -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">11</span>
            MA'LUMOTLAR BUZILISHI HAQIDA XABARDOR QILISH
        </h2>

        <p class="doc-paragraph">
            <strong>11.1.</strong> Agar biz shaxsiy ma'lumotlaringiz xavfsizligi buzilganini (data breach) aniqlasak, biz quyidagi choralarni ko'ramiz:
        </p>

        <ul class="doc-list">
            <li>Hodisani darhol tekshiramiz va zararni kamaytirish choralarini ko'ramiz</li>
            <li>Tegishli davlat organlariga 72 soat ichida xabar beramiz</li>
            <li>Agar buzilish sizga yuqori xavf tug'dirsa, sizni email, SMS yoki push bildirishnoma orqali xabardor qilamiz</li>
            <li>Qanday ma'lumotlar ta'sirlangani, qanday choralar ko'rilgani va qanday himoya qilish kerakligi haqida batafsil ma'lumot beramiz</li>
        </ul>

        <div class="important-box">
            <div class="box-title">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Agar akkauntingiz buzilganidan shubhalansangiz:</strong>
            </div>
            <ul class="doc-list" style="margin-bottom: 0;">
                <li>Darhol parolingizni o'zgartiring</li>
                <li>Ikki bosqichli autentifikatsiyani yoqing</li>
                <li>Bizga xabar bering: <strong>security@kitobchi.uz</strong></li>
                <li>So'nggi akkaunt faoliyatini tekshiring</li>
            </ul>
        </div>
    </div>

    <!-- Section 12: Uchinchi tomon havolalar -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">12</span>
            UCHINCHI TOMON HAVOLALAR VA XIZMATLAR
        </h2>

        <p class="doc-paragraph">
            <strong>12.1.</strong> Bizning Ilova va veb-saytimizda uchinchi tomon veb-saytlariga havolalar bo'lishi mumkin (masalan, nashriyotlar, mualliflar, ijtimoiy tarmoqlar).
        </p>

        <div class="warning-box">
            <div class="box-title">
                <i class="fas fa-external-link-alt"></i>
                <strong>Muhim:</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Biz uchinchi tomon veb-saytlarining maxfiylik amaliyotlari uchun javobgar emasmiz. Ularning veb-saytiga kirishdan oldin, ularning maxfiylik siyosatini o'qishingizni tavsiya qilamiz. Ushbu Maxfiylik siyosati faqat bizning xizmatlarimizga taalluqlidir.
            </p>
        </div>

        <p class="doc-paragraph">
            <strong>12.2.</strong> Agar siz uchinchi tomon xizmatlari orqali ro'yxatdan o'tsangiz (Google, Facebook), biz faqat siz ruxsat bergan ma'lumotlarni olamiz. Bu xizmatlarning maxfiylik siyosatlari ularga tegishli.
        </p>
    </div>

    <!-- Section 13: Siyosatdagi o'zgarishlar -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">13</span>
            MAXFIYLIK SIYOSATIDAGI O'ZGARISHLAR
        </h2>

        <p class="doc-paragraph">
            <strong>13.1.</strong> Biz vaqti-vaqti bilan ushbu Maxfiylik siyosatini yangilaymiz. O'zgarishlar qonunchilik talablari, yangi xizmatlar yoki texnologiyalar bilan bog'liq bo'lishi mumkin.
        </p>

        <p class="doc-paragraph">
            <strong>13.2.</strong> Muhim o'zgarishlar kiritilganda, biz sizni quyidagi usullar bilan xabardor qilamiz:
        </p>

        <ul class="doc-list">
            <li>Ilovada push bildirishnoma orqali</li>
            <li>Email xabarnoma orqali</li>
            <li>Veb-saytda va Ilovada e'lon qilish orqali</li>
        </ul>

        <p class="doc-paragraph">
            <strong>13.3.</strong> Yangilangan siyosat e'lon qilingan sanadan 7 (yetti) kun o'tgach kuchga kiradi. O'zgarishlardan keyin Ilovadan foydalanishni davom ettirish yangi siyosatga rozilik hisoblanadi.
        </p>

        <p class="doc-paragraph">
            <strong>13.4.</strong> Biz barcha o'zgarishlarning tarixini saqlаymiz. Oldingi versiyalarni ko'rish uchun <strong>privacy@kitobchi.uz</strong> manziliga murojaat qiling.
        </p>
    </div>

    <!-- Section 14: Bog'lanish -->
    <div class="doc-section">
        <h2 class="section-title">
            <span class="section-number">14</span>
            BIZ BILAN BOG'LANISH
        </h2>

        <p class="doc-paragraph">
            Agar sizda maxfiylik bilan bog'liq savollar, shikoyatlar yoki so'rovlar bo'lsa, biz bilan bog'lanishingiz mumkin:
        </p>

        <div class="contact-section">
            <div class="contact-grid">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-details">
                        <h5>Maxfiylik bo'yicha</h5>
                        <p>privacy@kitobchi.uz</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h5>Xavfsizlik bo'yicha</h5>
                        <p>security@kitobchi.uz</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="contact-details">
                        <h5>Qo'llab-quvvatlash</h5>
                        <p>support@kitobchi.uz</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-details">
                        <h5>Telefon</h5>
                        <p>+998 XX XXX-XX-XX</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h5>Manzil</h5>
                        <p>[Sizning to'liq manzilingiz]<br>Toshkent, O'zbekiston</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="contact-details">
                        <h5>Yuridik shaxs</h5>
                        <p>"Kitobchi" MChJ<br>STIR: [STIR raqami]</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="info-box" style="margin-top: 32px;">
            <div class="box-title">
                <i class="fas fa-balance-scale"></i>
                <strong>Shikoyat qilish huquqi:</strong>
            </div>
            <p class="doc-paragraph" style="margin-bottom: 0;">
                Agar siz bizning shaxsiy ma'lumotlaringizni qayta ishlashimiz to'g'risida tashvishlaringiz bo'lsa va biz bilan hal qila olmagan bo'lsak, siz O'zbekiston Respublikasi tegishli nazorat organlariga (Raqamli texnologiyalar vazirligi, Iste'molchilar huquqlarini himoya qilish qo'mitasi) shikoyat qilish huquqiga egasiz.
            </p>
        </div>
    </div>

    <!-- Final section -->
    <div class="important-box" style="margin-top: 48px;">
        <div class="box-title">
            <i class="fas fa-check-circle"></i>
            <strong>YAKUNIY QOIDALAR</strong>
        </div>
        <p class="doc-paragraph" style="margin-bottom: 12px;">
            <strong>Ushbu Maxfiylik siyosatini qabul qilish orqali, siz quyidagilarni tasdiqlaysiz:</strong>
        </p>
        <ul class="doc-list" style="margin-bottom: 0;">
            <li>Men mazkur Maxfiylik siyosatini to'liq o'qidim va tushundim</li>
            <li>Men shaxsiy ma'lumotlarimni yuqorida tavsiflangan maqsadlarda qayta ishlashga rozilik beraman</li>
            <li>Men cookie-fayllardan foydalanilishiga rozilik beraman (zaruriy cookie-lardan tashqari sozlamalarda o'zgartirish mumkin)</li>
            <li>Men uchinchi tomon xizmat ko'rsatuvchilar bilan ma'lumotlarimni ulashishga rozilik beraman</li>
            <li>Men o'z huquqlarim va ularni qanday amalga oshirish kerakligi haqida xabardorman</li>
            <li>Men 14 yoshdan kataman yoki ota-onamning roziligiga egaman</li>
        </ul>
    </div>

    <!-- Document Footer -->
    <div class="doc-footer">
        <p style="font-weight: 600; margin-bottom: 12px;">
            <strong>Siyosat e'lon qilingan sana:</strong> {{ date('d.m.Y') }}
        </p>
        <p>
            <strong>Oxirgi yangilanish:</strong> {{ date('d.m.Y') }}
        </p>
        <p>
            <strong>Versiya:</strong> 2.0
        </p>
        <p style="margin-top: 20px; font-style: italic; color: var(--text-light);">
            Ushbu Maxfiylik siyosati yuridik kuchga ega rasmiy hujjat hisoblanadi va "Kitobchi" MChJ tomonidan qo'llaniladigan maxfiylik standartlarini belgilaydi.
        </p>
    </div>
</div>
</article>
@endsection