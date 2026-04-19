<?php $__env->startSection('title', 'Foydalanish Shartlari'); ?>

<?php $__env->startSection('additional_styles'); ?>
<style>
    .language-switcher {
        position: fixed;
        top: 90px;
        right: 24px;
        z-index: 99;
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .lang-btn {
        padding: 10px 16px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        color: var(--text);
        transition: all 0.2s;
        width: 100%;
        text-align: left;
    }

    .lang-btn:hover {
        background: var(--bg-subtle);
    }

    .lang-btn.active {
        background: var(--accent);
        color: white;
    }

    .lang-content {
        display: none;
    }

    .lang-content.active {
        display: block;
    }

    @media print {
        .language-switcher {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .language-switcher {
            position: static;
            margin: 20px 0;
            display: flex;
        }
        
        .lang-btn {
            flex: 1;
            text-align: center;
        }
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Language Switcher -->
    <div class="language-switcher no-print">
        <button class="lang-btn active" onclick="switchLanguage('uz')" id="btn-uz">
            <i class="fas fa-globe"></i> O'zbekcha
        </button>
        <button class="lang-btn" onclick="switchLanguage('ru')" id="btn-ru">
            <i class="fas fa-globe"></i> Русский
        </button>
    </div>

    <article class="l-doc-card">
    <div class="l-doc-body kc-doc-static">

    <!-- UZBEK VERSION -->
    <div class="lang-content active" id="content-uz">
        <!-- Document Header -->
        <div class="doc-header">
            <h1 class="doc-title">Foydalanish Shartlari</h1>
            <p class="doc-subtitle">Kitobchi mobil ilovasi va platformasidan foydalanish qoidalari</p>
            <div class="doc-meta">
                <span class="meta-item">
                    <i class="fas fa-calendar"></i>
                    Oxirgi yangilanish: <?php echo e(date('d.m.Y')); ?>

                </span>
                <span class="meta-item">
                    <i class="fas fa-file-contract"></i>
                    Ommaviy Oferta
                </span>
                <span class="meta-item">
                    <i class="fas fa-building"></i>
                    "Kitobchi" MChJ
                </span>
            </div>
        </div>

        <!-- Section 1: Umumiy qoidalar -->
        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">1</span>
                UMUMIY QOIDALAR
            </h2>
            
            <p class="doc-paragraph">
                <strong>1.1.</strong> Ushbu Foydalanish shartlari (keyingi o'rinlarda — «Shartnoma» yoki «Oferta») <strong>"Kitobchi" MChJ</strong> (STIR: [sizning STIR raqamingiz]) (keyingi o'rinlarda — «Platforma Egasi», «Kompaniya», «biz») tomonidan jismoniy va yuridik shaxslarga (keyingi o'rinlarda — «Foydalanuvchi», «siz») o'z xizmatlarini ko'rsatish bo'yicha shartnoma tuzish taklifidir.
            </p>

            <p class="doc-paragraph">
                <strong>1.2.</strong> Ushbu Oferta O'zbekiston Respublikasi Fuqarolik kodeksining 366-moddasiga muvofiq ommaviy oferta hisoblanadi va cheklanmagan shaxslar doirasiga moljallangan.
            </p>

            <p class="doc-paragraph">
                <strong>1.3.</strong> Ushbu Oferta quyidagi platformalarga taalluqlidir:
            </p>

            <ul class="doc-list">
                <li><strong>Veb-sayt:</strong> www.kitobchi.uz</li>
                <li><strong>Mobil ilova:</strong> "Kitobchi" (iOS va Android)</li>
                <li><strong>Boshqa platformalar:</strong> Kompaniya tomonidan taqdim etiladigan barcha raqamli xizmatlar</li>
            </ul>

            <p class="doc-paragraph">
                <strong>1.4.</strong> Ofertaning aksepti (qabul qilish):
            </p>

            <ul class="doc-list">
                <li>Platformada ro'yxatdan o'tish;</li>
                <li>Platformadan foydalanishni boshlash;</li>
                <li>Har qanday xarid yoki obuna rasmiylashtirish;</li>
                <li>"Roziman" tugmasini bosish orqali amalga oshiriladi.</li>
            </ul>

            <div class="important-box">
                <div class="box-title">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Muhim!</strong>
                </div>
                <p class="doc-paragraph" style="margin-bottom: 0;">
                    Agar siz ushbu Foydalanish shartlariga rozi bo'lmasangiz, Platformadan foydalanmang. Platformadan foydalanishni davom ettirish ushbu Shartlarning barcha qoidalariga to'liq rozilik bildirish hisoblanadi.
                </p>
            </div>
        </div>

        <!-- Section 2: Atamalar va ta'riflar -->
        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">2</span>
                ATAMALAR VA TA'RIFLAR
            </h2>

            <p class="doc-paragraph">
                <strong>2.1. Platforma Egasi</strong> — "Kitobchi" MChJ, O'zbekiston Respublikasi qonunchiligiga muvofiq ro'yxatdan o'tgan yuridik shaxs, Platformaning egasi va operatori.
            </p>

            <p class="doc-paragraph">
                <strong>2.2. Platforma</strong> — "Kitobchi" interaktiv onlayn platforma bo'lib, kitoblar, kanselyariya mahsulotlarini sotib olish, premium xizmatlardan foydalanish va boshqa raqamli xizmatlarni olish imkonini beradi.
            </p>

            <p class="doc-paragraph">
                <strong>2.3. Foydalanuvchi</strong> — Platformadan foydalanadigan, ro'yxatdan o'tgan yoki o'tmagan har qanday jismoniy yoki yuridik shaxs.
            </p>

            <p class="doc-paragraph">
                <strong>2.4. Kontent</strong> — Platformada mavjud bo'lgan barcha ma'lumotlar, matnlar, rasmlar, videolar, ovoz yozuvlari, dasturiy kod va boshqa materiallar.
            </p>

            <p class="doc-paragraph">
                <strong>2.5. Obuna</strong> — Foydalanuvchiga ma'lum muddat davomida Platformaning premium funktsiyalaridan foydalanish huquqini beruvchi pullik xizmat.
            </p>

            <p class="doc-paragraph">
                <strong>2.6. Akkaunt</strong> — Foydalanuvchining shaxsiy kabineti bo'lib, ro'yxatdan o'tish orqali yaratiladi va login hamda parol bilan himoyalangan.
            </p>
        </div>

        <!-- Section 3: Ofertaning predmeti -->
        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">3</span>
                SHARTNOMANING PREDMETI
            </h2>

            <p class="doc-paragraph">
                <strong>3.1.</strong> Ushbu Shartnoma bo'yicha Platforma Egasi Foydalanuvchiga quyidagi xizmatlarni ko'rsatadi:
            </p>

            <div class="info-box">
                <div class="box-title">
                    <i class="fas fa-book-reader"></i>
                    <strong>Asosiy xizmatlar:</strong>
                </div>
                <ul class="doc-list" style="margin-bottom: 0;">
                    <li>Kitoblar va kanselyariya mahsulotlarini ko'rish va xarid qilish;</li>
                    <li>Premium obuna xizmatlaridan foydalanish;</li>
                    <li>Bolalar uchun ta'limiy kontent (reels, chat-bot);</li>
                    <li>Keshbek dasturida ishtirok etish;</li>
                    <li>Shaxsiy kabinetni boshqarish;</li>
                    <li>Buyurtmalar tarixini ko'rish;</li>
                    <li>Maxsus aksiyalar va chegirmalarda ishtirok etish;</li>
                    <li>Qo'llab-quvvatlash xizmatlaridan foydalanish.</li>
                </ul>
            </div>

            <p class="doc-paragraph">
                <strong>3.2.</strong> Platformada taqdim etiladigan ba'zi xizmatlar bepul, ba'zilari esa pullik asosda ko'rsatiladi. Har bir xizmatning narxi Platformada alohida ko'rsatilgan.
            </p>

            <p class="doc-paragraph">
                <strong>3.3.</strong> Platforma Egasi xizmatlar ro'yxatini, narxlarni va funktsiyalarni bir tomonlama tartibda o'zgartirish huquqiga ega, bunda Foydalanuvchilar oldindan xabardor qilinadi.
            </p>
        </div>

        <!-- Section 4: Ro'yxatdan o'tish va akkaunt -->
        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">4</span>
                RO'YXATDAN O'TISH VA AKKAUNT
            </h2>

            <h3 class="subsection-title">4.1. Ro'yxatdan o'tish tartibi:</h3>

            <ul class="doc-list">
                <li>Telefon raqami yoki email manzilini taqdim etish;</li>
                <li>Tasdiqlash kodini kiritish;</li>
                <li>Shaxsiy ma'lumotlarni to'ldirish (F.I.Sh., tug'ilgan sana);</li>
                <li>Parol o'rnatish;</li>
                <li>Foydalanish shartlari va Maxfiylik siyosatiga rozilik berish.</li>
            </ul>

            <h3 class="subsection-title">4.2. Akkaunt talablari:</h3>

            <div class="warning-box">
                <div class="box-title">
                    <i class="fas fa-user-shield"></i>
                    <strong>Akkaunt xavfsizligi:</strong>
                </div>
                <ul class="doc-list" style="margin-bottom: 0;">
                    <li>Har bir Foydalanuvchi faqat BITTA akkaunt yaratishi mumkin;</li>
                    <li>Login va parolni uchinchi shaxslarga berish taqiqlanadi;</li>
                    <li>Parol murakkab bo'lishi kerak (kamida 8 belgi, harflar va raqamlar);</li>
                    <li>Akkauntda shubhali faoliyat aniqlanganda darhol parolni o'zgartirish kerak;</li>
                    <li>Akkaunt faqat shaxsiy foydalanish uchun mo'ljallangan.</li>
                </ul>
            </div>

            <p class="doc-paragraph">
                <strong>4.3.</strong> Foydalanuvchi o'z akkauntida sodir bo'ladigan barcha harakatlar uchun to'liq javobgardir.
            </p>

            <p class="doc-paragraph">
                <strong>4.4.</strong> Akkauntni o'chirish Ilova sozlamalarida "Akkauntni o'chirish" bo'limida amalga oshiriladi. O'chirilgan akkaunt 30 kun ichida qayta tiklanishi mumkin, keyin esa butunlay o'chiriladi.
            </p>
        </div>

        <!-- Section 5: Foydalanuvchining huquq va majburiyatlari -->
        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">5</span>
                FOYDALANUVCHINING HUQUQ VA MAJBURIYATLARI
            </h2>

            <h3 class="subsection-title">5.1. Foydalanuvchining huquqlari:</h3>

            <ul class="doc-list">
                <li>Platformadan to'liq va to'sqinliksiz foydalanish;</li>
                <li>Xizmatlar sifati haqida shikoyat qilish;</li>
                <li>Shaxsiy ma'lumotlarini ko'rish, tuzatish va o'chirish;</li>
                <li>Obunani istalgan vaqtda bekor qilish;</li>
                <li>Qo'llab-quvvatlash xizmatidan yordam olish;</li>
                <li>Aksiyalar va chegirmalarda ishtirok etish;</li>
                <li>Maxfiylik va ma'lumotlar himoyasi kafolatini olish.</li>
            </ul>

            <h3 class="subsection-title">5.2. Foydalanuvchining majburiyatlari:</h3>

            <div class="important-box">
                <div class="box-title">
                    <i class="fas fa-tasks"></i>
                    <strong>Foydalanuvchi majburiyatlari:</strong>
                </div>
                <ul class="doc-list" style="margin-bottom: 0;">
                    <li><strong>Haqiqiy ma'lumotlar berish:</strong> Ro'yxatdan o'tishda to'g'ri va to'liq ma'lumotlar taqdim etish;</li>
                    <li><strong>Qonunlarga rioya qilish:</strong> Platformadan faqat qonuniy maqsadlarda foydalanish;</li>
                    <li><strong>Intellektual mulkni hurmat qilish:</strong> Mualliflik huquqlarini buzmaslik;</li>
                    <li><strong>Akkaunt xavfsizligi:</strong> Login va parolni himoya qilish;</li>
                    <li><strong>To'lovlarni amalga oshirish:</strong> Xizmatlar uchun o'z vaqtida to'lash;</li>
                    <li><strong>Taqiqlangan harakatlardan qochish:</strong> Spam, viruslar, hacking urinishlari va boshqa noqonuniy faoliyat bilan shug'ullanmaslik.</li>
                </ul>
            </div>

            <h3 class="subsection-title">5.3. Taqiqlangan harakatlar:</h3>

            <div class="warning-box">
                <div class="box-title">
                    <i class="fas fa-ban"></i>
                    <strong>Qat'iyan taqiqlanadi:</strong>
                </div>
                <ul class="doc-list" style="margin-bottom: 0;">
                    <li>Boshqa foydalanuvchilarning akkauntidan ruxsatsiz foydalanish;</li>
                    <li>Zararli dasturlar, viruslar yoki malware tarqatish;</li>
                    <li>Platformaning ishlashiga xalaqit beruvchi harakatlar qilish;</li>
                    <li>Yolg'on ma'lumotlar bilan ro'yxatdan o'tish;</li>
                    <li>Bir nechta soxta akkaunt yaratish;</li>
                    <li>Kontent va mahsulotlarni noqonuniy tarqatish;</li>
                    <li>To'lov tizimini aldash urinishlari;</li>
                    <li>Spam, reklama yoki keraksiz xabarlar yuborish;</li>
                    <li>Platformani qayta ishlab chiqarish yoki kopyalash.</li>
                </ul>
            </div>

            <p class="doc-paragraph">
                <strong>5.4.</strong> Yuqoridagi qoidalarni buzgan taqdirda, Platforma Egasi Foydalanuvchi akkauntini ogohlantirishsiz bloklash huquqiga ega.
            </p>
        </div>

        <!-- Section 6: Platforma Egasining huquq va majburiyatlari -->
        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">6</span>
                PLATFORMA EGASINING HUQUQ VA MAJBURIYATLARI
            </h2>

            <h3 class="subsection-title">6.1. Platforma Egasining huquqlari:</h3>

            <ul class="doc-list">
                <li>Platformani istalgan vaqtda yangilash, o'zgartirish yoki yaxshilash;</li>
                <li>Xizmatlar ro'yxati va narxlarni bir tomonlama o'zgartirish (oldindan xabardor qilgan holda);</li>
                <li>Qoidabuzar foydalanuvchilarni ogohlantirish yoki bloklash;</li>
                <li>Kontentni moderatsiya qilish va nomaqbul materiallarni o'chirish;</li>
                <li>Texnik ishlar olib borishda vaqtinchalik xizmatlarni to'xtatib turish;</li>
                <li>Shubhali tranzaksiyalarni tekshirish va to'xtatib turish;</li>
                <li>Uchinchi tomon xizmatlari bilan integratsiya qilish.</li>
            </ul>

            <h3 class="subsection-title">6.2. Platforma Egasining majburiyatlari:</h3>

            <ul class="doc-list">
                <li>Platformaning barqaror va uzluksiz ishlashini ta'minlash;</li>
                <li>Foydalanuvchi ma'lumotlarini himoya qilish va maxfiyligini saqlash;</li>
                <li>Murojaatlarga 24-48 soat ichida javob berish;</li>
                <li>To'lovlarni xavfsiz qayta ishlash;</li>
                <li>Sifatli xizmat va mahsulotlar taqdim etish;</li>
                <li>Texnik yordam ko'rsatish;</li>
                <li>Foydalanuvchilarni muhim o'zgarishlar haqida xabardor qilish.</li>
            </ul>
        </div>

        <!-- Section 7: Kontentdan foydalanish qoidalari -->
        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">7</span>
                KONTENTDAN FOYDALANISH QOIDALARI
            </h2>

            <p class="doc-paragraph">
                <strong>7.1.</strong> Platformadagi barcha kontent (matnlar, rasmlar, videolar, audio, dizayn, dasturiy kod) Platforma Egasi yoki huquq egalari tomonidan himoyalangan intellektual mulk hisoblanadi.
            </p>

            <div class="important-box">
                <div class="box-title">
                    <i class="fas fa-copyright"></i>
                    <strong>Mualliflik huquqlari:</strong>
                </div>
                <ul class="doc-list" style="margin-bottom: 0;">
                    <li>Kontent faqat shaxsiy, tijorat bo'lmagan maqsadlarda foydalanish uchun beriladi;</li>
                    <li>Kontentni ko'chirish, yuklab olish, qayta nashr etish, sotish yoki tarqatish TAQIQLANADI;</li>
                    <li>Kontentni o'zgartirish, tarjima qilish yoki qayta ishlash ruxsat etilmaydi;</li>
                    <li>Platformadagi materiallarni boshqa veb-sayt yoki ilovalarda joylashtirish mumkin emas;</li>
                    <li>Har qanday mualliflik huquqini buzish qonuniy javobgarlikka olib keladi.</li>
                </ul>
            </div>

            <p class="doc-paragraph">
                <strong>7.2.</strong> Foydalanuvchi tomonidan yuklangan kontent (sharh, fikr-mulohazalar) Foydalanuvchiga tegishli bo'lib, u bu kontentni Platformada joylashtirish uchun barcha zarur huquqlarga ega ekanligini kafolatlaydi.
            </p>

            <p class="doc-paragraph">
                <strong>7.3.</strong> Foydalanuvchi o'z kontentini Platformada joylashtirish orqali Platforma Egasiga ushbu kontentdan platformada foydalanish uchun cheklanmagan litsenziya beradi.
            </p>
        </div>

        <!-- Continue in next message due to length... -->
        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">8</span>
                TO'LOV TARTIBI
            </h2>

            <p class="doc-paragraph">
                <strong>8.1.</strong> Platformadagi ba'zi xizmatlar va mahsulotlar pullik asosda taqdim etiladi. Narxlar Platformada O'zbekiston Respublikasining milliy valyutasida (so'mda) ko'rsatiladi.
            </p>

            <p class="doc-paragraph">
                <strong>8.2.</strong> To'lovlar quyidagi usullar orqali amalga oshiriladi:
            </p>

            <ul class="doc-list">
                <li>Bank kartalari orqali (Humo, Uzcard, Visa, MasterCard);</li>
                <li>Payme to'lov tizimi orqali;</li>
                <li>Boshqa davlat tomonidan ruxsat etilgan to'lov tizimlari.</li>
            </ul>

            <p class="doc-paragraph">
                <strong>8.3.</strong> Barcha to'lovlar xavfsiz PCI DSS standartlariga muvofiq qayta ishlanadi.
            </p>

            <p class="doc-paragraph">
                <strong>8.4.</strong> Premium obuna uchun avtomatik to'lov (avtospisanie) shartlari alohida <a href="<?php echo e(route('legal.subscription')); ?>">Premium Obuna Ofertasida</a> belgilangan.
            </p>
        </div>

        <!-- Continue other sections... -->
        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">9</span>
                QAYTARISH SIYOSATI
            </h2>

            <p class="doc-paragraph">
                <strong>9.1.</strong> Raqamli xizmatlar (premium obuna) va elektron mahsulotlar uchun qaytarish siyosati <a href="<?php echo e(route('legal.subscription')); ?>">Premium Obuna Ofertasida</a> batafsil bayon etilgan.
            </p>

            <p class="doc-paragraph">
                <strong>9.2.</strong> Jismoniy mahsulotlar (kitoblar, kanselyariya) uchun:
            </p>

            <ul class="doc-list">
                <li>Mahsulot yetkazib berilganidan keyin 14 kun ichida qaytarish mumkin;</li>
                <li>Mahsulot asl holatida va qadoqda bo'lishi kerak;</li>
                <li>Chek yoki to'lov tasdiqnomasi bo'lishi shart;</li>
                <li>Yetkazib berish xarajatlari Foydalanuvchi tomonidan qoplanadi.</li>
            </ul>

            <p class="doc-paragraph">
                <strong>9.3.</strong> Qaytarish uchun support@kitobchi.uz manziliga murojaat qiling.
            </p>
        </div>

        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">10</span>
                JAVOBGARLIKNI CHEKLASH
            </h2>

            <div class="warning-box">
                <div class="box-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Muhim ogohlantirish:</strong>
                </div>
                <p class="doc-paragraph" style="margin-bottom: 0;">
                    Platformadagi xizmatlar "qanday bo'lsa, shunday" tamoyili asosida taqdim etiladi. Biz maksimal sifat va xavfsizlikni ta'minlashga intilamiz, lekin texnik nosozliklar, uchinchi tomon xizmatlari muammolari yoki boshqa nazoratimizdan tashqarida bo'lgan holatlar uchun javobgar emasmiz.
                </p>
            </div>

            <p class="doc-paragraph">
                <strong>10.1.</strong> Platforma Egasi quyidagilar uchun javobgar emas:
            </p>

            <ul class="doc-list">
                <li>Internet aloqasining yo'qligi yoki sifatsizligi;</li>
                <li>Foydalanuvchi qurilmasining texnik muammolari;</li>
                <li>Uchinchi tomon xizmatlari (to'lov operatorlari, yetkazib berish xizmatlari) tomonidan kelib chiqqan muammolar;</li>
                <li>Foydalanuvchining akkaunt ma'lumotlarini boshqalarga berishi natijasida yuzaga kelgan zararlar;</li>
                <li>Fors-major holatlari (tabiiy ofatlar, harbiy harakatlar, qonunchilik o'zgarishlari).</li>
            </ul>

            <p class="doc-paragraph">
                <strong>10.2.</strong> Platforma Egasining maksimal javobgarligi Foydalanuvchi oxirgi 3 oy ichida to'lagan summa bilan cheklangan.
            </p>
        </div>

        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">11</span>
                NIZOLARNI HAL QILISH
            </h2>

            <p class="doc-paragraph">
                <strong>11.1.</strong> Ushbu Shartnomadan kelib chiqadigan barcha nizo va kelishmovchiliklar Tomonlar o'rtasida muzokaralar yo'li bilan hal qilinadi.
            </p>

            <p class="doc-paragraph">
                <strong>11.2.</strong> Pretenziya tartibida hal qilish majburiy hisoblanadi. Pretenziya yozma shaklda support@kitobchi.uz manziliga yuboriladi va 10 ish kuni ichida ko'rib chiqiladi.
            </p>

            <p class="doc-paragraph">
                <strong>11.3.</strong> Kelishmovchilikni muzokaralar yo'li bilan hal qilib bo'lmagan taqdirda, nizo O'zbekiston Respublikasi qonunchiligiga muvofiq sudga topshiriladi.
            </p>
        </div>

        <div class="doc-section">
            <h2 class="section-title">
                <span class="section-number">12</span>
                YAKUNIY QOIDALAR
            </h2>

            <p class="doc-paragraph">
                <strong>12.1.</strong> Ushbu Shartnoma noma'lum muddatga tuziladi va Tomonlardan biri uni bekor qilguncha amal qiladi.
            </p>

            <p class="doc-paragraph">
                <strong>12.2.</strong> Platforma Egasi Shartnoma shartlarini bir tomonlama o'zgartirish huquqiga ega. O'zgarishlar Platformada e'lon qilingan va Foydalanuvchilarga bildirishnoma yuborilganidan keyin 7 kun o'tgach kuchga kiradi.
            </p>

            <p class="doc-paragraph">
                <strong>12.3.</strong> Savollar va murojaatlar uchun: <a href="mailto:support@kitobchi.uz">support@kitobchi.uz</a>. <a href="<?php echo e(route('legal.privacy')); ?>">Maxfiylik siyosati</a> bilan birga o'qilish tavsiya etiladi.
            </p>
        </div>
    </div>

    <div class="lang-content" id="content-ru">
        <div class="doc-header">
            <h1 class="doc-title">Условия использования</h1>
            <p class="doc-subtitle">Полный текст на узбекском языке. Русская версия в подготовке.</p>
            <div class="doc-meta">
                <span class="meta-item">
                    <i class="fas fa-envelope"></i>
                    support@kitobchi.uz
                </span>
            </div>
        </div>
        <p class="doc-paragraph">
            Чтобы прочитать все разделы (1–12), переключитесь на
            <a href="#" onclick="switchLanguage('uz'); return false;">o‘zbekcha</a>.
        </p>
    </div>

    </div>
    </article>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function switchLanguage(lang) {
    document.querySelectorAll('.lang-content').forEach(function (el) {
        el.classList.toggle('active', el.id === 'content-' + lang);
    });
    var btnUz = document.getElementById('btn-uz');
    var btnRu = document.getElementById('btn-ru');
    if (btnUz) btnUz.classList.toggle('active', lang === 'uz');
    if (btnRu) btnRu.classList.toggle('active', lang === 'ru');
    document.documentElement.lang = lang === 'ru' ? 'ru' : 'uz';
}
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('legal.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/legal/terms.blade.php ENDPATH**/ ?>