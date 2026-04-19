<?php

/**
 * @see Database\Seeders\LegalPoliciesKitobchiFullSeeder
 * Namuna: Wildberries Oʻzbekiston isteʼmolchi ofertasi tuzilishi
 * @link https://legal.wildberries.uz/consumers-offer/country/uz/lang/ru/
 */

return [
    'slugs_to_replace' => [
        'foydalanish-shartlari',
        'maxfiylik-siyosati',
        'sotuvchi-shartlari',
        'yetkazib-berish-va-qaytarish',
        'tolov-va-qaytarish',
        'obuna-shartlari',
        'cookies-siyosati',
    ],

    'policies' => [
        [
            'slug' => 'foydalanish-shartlari',
            'sort_order' => 10,
            'title_uz' => 'Foydalanish shartlari (ommaviy oferta)',
            'title_en' => 'Terms of Use (Public Offer)',
            'content_uz' => <<<'UZDOC'
<h2>1. Umumiy qoidalar</h2>
<p>Ushbu hujjat <strong>Kitobchi</strong> mobil ilovasi va veb-sayti orqali taqdim etiladigan onlayn marketplace xizmatidan foydalanishning huquqiy asoslarini belgilaydi va <strong>ommaviy oferta</strong> (keyingi oʻrinlarda — Oferta) hisoblanadi.</p>
<p><strong>Geografik doira:</strong> xizmatlar va tovarlarni yetkazib berish <strong>faqat Oʻzbekiston Respublikasi hududi</strong> uchun moʻljallangan. Platforma chet eldan import yoki xalqaro yetkazib berishni taklif etmaydi (agar alohida yozilmagan boʻlsa).</p>
<p>Oferta Oʻzbekiston Respublikasining Fuqarolik kodeksi, “Elektron tijorat toʻgʻrisida”gi Qonun, isteʼmolchilarning huquqlarini himoya qilish toʻgʻrisidagi qonun hujjatlari hamda boshqa amaldagi normativ-huquqiy aktlarga muvofiq tuzilgan.</p>

<h2>2. Atamalar va taʼriflar</h2>
<ul>
<li><strong>Operator</strong> — marketplace xizmatini koʻrsatuvchi yakka tartibdagi tadbirkor: <strong>YATT Turdaliyev Abbos Erkin oʻgʻli</strong>, STIR <strong>51503025990027</strong>, aloqa telefoni: <strong>+998 50 030 30 33</strong>.</li>
<li><strong>Platforma</strong> — Kitobchi dasturiy mahsuloti, maʼlumotlar bazasi, interfeys va qoʻllab-quvvatlash jarayonlari yigʻindisi.</li>
<li><strong>Foydalanuvchi / Xaridor</strong> — roʻyxatdan oʻtgan yoki buyurtma berayotgan jismoniy yoki yuridik shaxs.</li>
<li><strong>Sotuvchi</strong> — Operator bilan hamkorlik qilib, oʻz nomidan tovar joylashtiruvchi tadbirkor yoki yuridik shaxs.</li>
<li><strong>Mahsulot</strong> — kitob, kanstovar va boshqa tovarlar, shuningdek raqamli kontent (agar taklif etilsa).</li>
<li><strong>Buyurtma</strong> — foydalanuvchining tanlangan mahsulot(lar) boʻyicha rasmiylashtirilgan soʻrovi.</li>
<li><strong>Shaxsiy kabinet</strong> — foydalanuvchining akkaunti va unga bogʻliq funksiyalar.</li>
<li><strong>Yetkazib berish</strong> — buyurtmani topshirish uchun logistik jarayonlar.</li>
</ul>

<h2>3. Ofertaning qabul qilinishi</h2>
<p>Roʻyxatdan oʻtish, buyurtmani rasmiylashtirish, toʻlovni boshlash yoki Platformadan foydalanishni davom ettirish Ofertaning shartlariga rozilik bildirish hisoblanadi. Rozilik elektron shaklda beriladi.</p>

<h2>4. Operatorning maqomi</h2>
<p>Operator <strong>elektron savdo platformasi (marketplace) operatori</strong> boʻlib, xaridor va sotuvchi oʻrtasidagi bitimni tashkil etish, toʻlov infratuzilmasi, axborot-kommunikatsiya va (agar koʻrsatilgan boʻlsa) yetkazib berishni muvofiqlashtirish funksiyalarini bajaradi. Operator sotuvchining shaxsiy mahsulot siyosatining toʻliq egasi emas; sotuvchi oʻz zaxirasi va tavsiflari uchun javobgar.</p>

<h2>5. Roʻyxatdan oʻtish va akkaunt</h2>
<p>Foydalanuvchi toʻgʻri, toʻliq va dolzarb maʼlumotlarni taqdim etadi. Bir shaxsga bir nechta akkaunt ochishni firibgarlik maqsadida ishlatish taqiqlanadi.</p>
<p>Login va parolni saqlash, qurilma xavfsizligi foydalanuvchi zimmasida. Shubhali faollik aniqlansa, Operator akkauntni vaqtincha bloklashi yoki qoʻshimcha tekshiruv talab qilishi mumkin.</p>

<h2>6. Mahsulot haqida maʼlumot va narx</h2>
<p>Mahsulot tavsifi, suratlar, narx va mavjudlik maʼlumotlari asosan <strong>sotuvchi</strong> tomonidan joylashtiriladi. Operator moderatsiya va texnik imkoniyatlar doirasida ularning toʻgʻriligini taʼminlashga intiladi, ammo barcha tavsiflar boʻyicha yakuniy javobgarlik sotuvchiga tegishli qoladi.</p>
<p>Narx Operator yoki toʻlov tizimi xatolari tufayli notoʻgʻri koʻrsatilgan boʻlsa, Operator yoki sotuvchi buyurtmani bekor qilish yoki toʻgʻrilash huquqiga ega (holat boʻyicha foydalanuvchiga xabar beriladi).</p>

<h2>7. Buyurtma va masofaviy shartnoma</h2>
<p>Buyurtma rasmiylashtirilganda <strong>xaridor va tegishli sotuvchi</strong> oʻrtasida masofaviy savdo shartnomasi tuziladi. Operator ushbu shartnomani amalga oshirish uchun texnik vosita beradi.</p>
<p>Buyurtma tarkibi, yetkazib berish manzili va kontakt maʼlumotlari foydalanuvchi tomonidan tasdiqlangan hisoblanadi.</p>

<h2>8. Toʻlov</h2>
<p>Toʻlovlar Platformada integratsiya qilingan elektron toʻlov vositalari (masalan, Payme va boshqalar) orqali amalga oshirilishi mumkin. Toʻlov muvaffaqiyatli yakunlanguncha buyurtma yakuniy deb hisoblanmaydi (platformadagi aniq mantiq dasturiy taʼminotga bogʻliq).</p>
<p>Foydalanuvchi toʻlov kartalari va hisoblarining qonuniyligi uchun javobgardir.</p>

<h2>9. Yetkazib berish</h2>
<p>Yetkazib berish muddati, narxi va zonalar buyurtma berish bosqichida, shuningdek ilovada koʻrsatiladi. Yetkazib berish Operator, sotuvchi yoki ularning kuryer/pochta hamkorlari orqali tashkil etilishi mumkin.</p>
<p>Manzil va kontakt maʼlumotlari notoʻgʻri kiritilganligi tufayli yetkazib berishning iloji boʻlmagan taqdirda, qoʻshimcha xarajatlar va javobgarlik qonun va ichki tartibga muvofiq ajratiladi.</p>

<h2>10. Tovarni qaytarish — amalga oshirilmaydi</h2>
<p><strong>10.1.</strong> Kitobchi marketplace modelida <strong>foydalanuvchining istagi bilan tovarni qaytarib olish (xohishmastlik, oʻlcham, rang, “yoqmadi”, dublikat xarid va hokazo)</strong> <strong>qabul qilinmaydi va amalga oshirilmaydi</strong>. Bu shart xaridor tomonidan Oferta bilan rozilik berilganda aniq tasdiqlangan hisoblanadi.</p>
<p><strong>10.2.</strong> Shuningdek, <strong>muqovasi ochilgan, belgilanmagan nuqsonlari yoʻq</strong> bosma nashrlar, shaxsiy gigiena va oʻxshash toifadagi mahsulotlar boʻyicha qaytarish umuman koʻrib chiqilmaydi (qonunda boshqacha majburiy tartib belgilangan boʻlsa, shu tartib qoʻllanadi).</p>
<p><strong>10.3.</strong> Agar mahsulot <strong>aniqlangan nuqson</strong> (ishlamaslik, sifat buzilishi), <strong>tavsifga sezilarli darajada mos kelmasligi</strong> yoki <strong>notoʻgʻri mahsulot yuborilganligi</strong> boʻyicha asoslantirsa, foydalanuvchi Operatorga yoki sotuvchiga murojaat qiladi; bunday holatlar <strong>alohida koʻrib chiqiladi</strong> va natija (masalan, almashtirish, chegirma yoki pulni qaytarish) <strong>har bir holat boʻyicha</strong> qonun hujjatlari, dalillar va ichki tartib asosida aniqlanadi. Ushbu band <strong>10.1</strong>-banddagi umumiy “xohish bilan qaytarish yoʻq” prinsipini avtomatik ravishda bekor qilmaydi.</p>
<p><strong>10.4.</strong> Pulni qaytarish faqat Platformada va qonunda nazarda tutilgan hollarda (masalan, buyurtma yuborilishidan oldin bekor qilish, toʻlov dublikati, 10.3 boʻyicha qaror) amalga oshirilishi mumkin.</p>

<h2>11. Kafolat va xizmat koʻrsatish</h2>
<p>Kafolat muddati va tartibi mahsulot turi hamda sotuvchi maʼlumotlariga muvofiq. Kafolat talablari boʻyicha nizolar avvalo sotuvchi bilan muzokara qilinadi; Operator qonun doirasida vositachilik qiladi.</p>

<h2>12. Intellektual mulk</h2>
<p>Platforma interfeysi, logotip, dastur kodi va kontentning ayrim qismlari Operator yoki uchinchi shaxslarning intellektual mulki boʻlishi mumkin. Foydalanuvchi ularni ruxsatsiz koʻchirish, tarqatish yoki tijorat maqsadida ishlatmasligi kerak.</p>

<h2>13. Foydalanuvchi kontenti va Book Club</h2>
<p>Foydalanuvchi joylashtirgan sharhlar, postlar va boshqa UGC uchun alohida qoidalar va moderatsiya tartibi Platformada qoʻllaniladi. Taqiqlangan kontent (nomaqbul til, spam, noqonuniy materiallar) olib tashlanishi mumkin.</p>

<h2>14. Taqiqlangan harakatlar</h2>
<ul>
<li>firibgarlik, soxta akkauntlar, boshqa foydalanuvchilarni aldash;</li>
<li>qonuniy taqiqlangan mahsulotlarni sotish yoki ularga qoʻngʻiroq qilish;</li>
<li>Platformaning barqarorligiga zarar yetkazish, skriptlar orqali haddan tashqari yuklash;</li>
<li>shaxsga doir maʼlumotlarni ruxsatsiz yigʻish va tarqatish.</li>
</ul>

<h2>15. Moderatsiya va cheklovlar</h2>
<p>Operator qonun va ichki qoidalarga muvofiq akkauntni cheklashi, buyurtmani bekor qilishi yoki kontentni moderatsiya qilishi mumkin.</p>

<h2>16. Uchinchi tomon xizmatlari</h2>
<p>Toʻlov, SMS, push-bildirishnomalar uchinchi tomon provayderlari orqali berilishi mumkin. Ular oʻz maxfiylik qoidalariga ega.</p>

<h2>17. Masʼuliyat</h2>
<p>Operatorning javobgarligi qonun bilan belgilangan chegaralar doirasida. Platforma “boricha” (as is) taʼminot prinsipi boʻyicha mavjud boʻlishi mumkin; texnik uzilishlar uchun Operator majburiyatlari qonun bilan cheklanadi.</p>

<h2>18. Ofertaga oʻzgartirishlar</h2>
<p>Operator Ofertani yangilashi mumkin. Muhim oʻzgarishlar ilova yoki veb-saytda eʼlon qilinadi. Keyingi buyurtmalar yangi shartlarga boʻysunadi (qonunda boshqacha tartib boʻlsa, shu tartib qoʻllaniladi).</p>

<h2>19. Nizolar</h2>
<p>Nizolar muzokara va daʼvo tartibida hal etiladi. Sud yurisdiksiyasi — Oʻzbekiston Respublikasi qonun hujjatlariga muvofiq.</p>

<h2>20. Operator rekvizitlari va aloqa</h2>
<p><strong>YATT Turdaliyev Abbos Erkin oʻgʻli</strong><br>STIR: 51503025990027<br>Telefon: +998 50 030 30 33<br>Bank: Trastbank, hisob raqami: <strong>555555555</strong> <em>(agar bu yerda namuna koʻrsatilgan boʻlsa, haqiqiy rekvizit bilan almashtiring)</em></p>
UZDOC,
            'content_en' => <<<'ENDOC'
<h2>1. General</h2>
<p>These Terms constitute a <strong>public offer</strong> governing use of the <strong>Kitobchi</strong> online marketplace (website and mobile app).</p>
<p><strong>Geography:</strong> services and delivery are intended <strong>only for the territory of the Republic of Uzbekistan</strong>. The platform does not offer cross-border import or international delivery unless expressly stated otherwise.</p>
<p>The offer is prepared in accordance with the Civil Code of the Republic of Uzbekistan, e-commerce legislation, consumer protection laws and other applicable regulations.</p>

<h2>2. Definitions</h2>
<ul>
<li><strong>Operator</strong> — individual entrepreneur <strong>Turdaliyev Abbos Erkin uli</strong>, TIN <strong>51503025990027</strong>, phone <strong>+998 50 030 30 33</strong>.</li>
<li><strong>Platform</strong> — Kitobchi software, databases, interfaces and support processes.</li>
<li><strong>User / Buyer</strong> — a natural or legal person using the Platform.</li>
<li><strong>Seller</strong> — a partner listing goods in its own name.</li>
<li><strong>Product</strong> — books, stationery and related goods.</li>
<li><strong>Order</strong> — a confirmed request to purchase selected goods.</li>
</ul>

<h2>3. Acceptance</h2>
<p>By registering, placing an order, initiating payment or continuing to use the Platform you accept this offer electronically.</p>

<h2>4. Role of the Operator</h2>
<p>The Operator acts as a <strong>marketplace operator</strong>, facilitating remote contracts between buyers and sellers, payment infrastructure and (where applicable) logistics coordination.</p>

<h2>5. Account</h2>
<p>You must provide accurate information and keep credentials secure. Fraudulent multi-accounting is prohibited.</p>

<h2>6. Listings and prices</h2>
<p>Descriptions, images, prices and stock are primarily provided by <strong>sellers</strong>. The Operator moderates where possible but final responsibility for listing accuracy lies with the seller.</p>

<h2>7. Orders</h2>
<p>Placing an order forms a remote sale contract between you and the relevant seller. The order content and delivery details you confirm are binding.</p>

<h2>8. Payments</h2>
<p>Payments are processed via integrated electronic payment methods (e.g. Payme). An order may not be final until payment succeeds, depending on product logic in the app.</p>

<h2>9. Delivery</h2>
<p>Delivery time, cost and zones are shown at checkout. Delivery may be performed by the Operator, seller or logistics partners. Incorrect address data may result in additional costs under applicable rules.</p>

<h2>10. Returns — not available for discretionary reasons</h2>
<p><strong>10.1.</strong> Under the Kitobchi model, <strong>returns based solely on the buyer’s preference</strong> (change of mind, size/colour dislike, “did not like”, duplicate purchase by mistake, etc.) <strong>are not accepted and are not performed</strong>.</p>
<p><strong>10.2.</strong> Categories such as goods with opened packaging where defects are not established may be excluded from any return consideration (unless mandatory law provides otherwise).</p>
<p><strong>10.3.</strong> If a product has a <strong>confirmed defect</strong>, a <strong>material mismatch with the description</strong> or a <strong>wrong item</strong> was shipped, you must contact the Operator or seller; such cases are reviewed <strong>individually</strong> and remedies (exchange, discount or refund) are determined <strong>per case</strong> under law, evidence and internal procedures. This clause does not automatically override clause 10.1 for discretionary returns.</p>
<p><strong>10.4.</strong> Refunds are only possible in cases permitted by the Platform and law (e.g. cancellation before dispatch, duplicate payment, outcomes under 10.3).</p>

<h2>11. Warranty</h2>
<p>Warranty period and procedures follow the product category and seller information. Warranty disputes are first addressed with the seller; the Operator facilitates within legal limits.</p>

<h2>12. Intellectual property</h2>
<p>Platform UI, logos and parts of the content are protected. Copying or commercial use without permission is prohibited.</p>

<h2>13. User content (reviews, Book Club)</h2>
<p>UGC is subject to community rules and moderation; prohibited content may be removed.</p>

<h2>14. Prohibited conduct</h2>
<p>Fraud, fake accounts, illegal goods, system abuse and unauthorised collection of personal data are prohibited.</p>

<h2>15. Moderation</h2>
<p>The Operator may restrict accounts, cancel orders or moderate content under law and internal rules.</p>

<h2>16. Third-party services</h2>
<p>Payments, SMS and push notifications may be provided by third parties with their own policies.</p>

<h2>17. Liability</h2>
<p>Liability is limited as permitted by law. The Platform may be provided on an “as is” basis; downtime may occur.</p>

<h2>18. Changes to the Terms</h2>
<p>The Operator may update these Terms; material changes will be published on the website/app.</p>

<h2>19. Disputes</h2>
<p>Disputes are resolved through negotiation and claims procedures; jurisdiction follows the laws of Uzbekistan.</p>

<h2>Contact</h2>
<p><strong>Individual Entrepreneur Turdaliyev Abbos Erkin uli</strong><br>TIN: 51503025990027<br>Phone: +998 50 030 30 33<br>Bank: Trastbank, account: <strong>555555555</strong> <em>(replace with your real account number if this is a placeholder)</em></p>
ENDOC,
        ],

        [
            'slug' => 'maxfiylik-siyosati',
            'sort_order' => 20,
            'title_uz' => 'Maxfiylik siyosati',
            'title_en' => 'Privacy Policy',
            'content_uz' => <<<'UZDOC'
<h2>1. Maqsad va qamrov</h2>
<p>Ushbu Maxfiylik siyosati Operatorning Kitobchi platformasi orqali <strong>shaxsga doir maʼlumotlarni</strong> qanday toʻplashi, saqlashi, ishlatishi va himoya qilishini tushuntiradi.</p>
<p>Maʼlumotlar asosan <strong>Oʻzbekiston Respublikasi hududi</strong>ida joylashgan foydalanuvchilar uchun qayta ishlanadi; xalqaro uzatishlar faqat texnik provayderlar (masalan, bulut xizmati) bilan shartnomada koʻrsatilgan hollarda amalga oshirilishi mumkin.</p>

<h2>2. Qayta ishlanadigan maʼlumotlar toifalari</h2>
<ul>
<li><strong>Identifikatsiya:</strong> ism, telefon raqami, elektron pochta (agar berilgan boʻlsa), tizimga kirish identifikatorlari.</li>
<li><strong>Yetkazib berish:</strong> manzil, viloyat/shahar, kuryer bilan bogʻlash uchun kontakt.</li>
<li><strong>Tranzaksiya:</strong> buyurtmalar, toʻlov holati, cheklar va toʻlov tizimidan kelgan texnik identifikatorlar (kartaning toʻliq raqami Operatorda saqlanmaydi, agar toʻlov provayderi boshqacha talab qilmasa).</li>
<li><strong>Texnik:</strong> qurilma turi, operatsion tizim, ilova versiyasi, IP-manzil, cookie va shunga oʻxshash fayllar (batafsil — Cookie siyosati).</li>
<li><strong>Muloqot:</strong> qoʻllab-quvvatlash chatlari, sharhlar, Book Club postlari (UGC).</li>
</ul>

<h2>3. Qayta ishlash maqsadlari</h2>
<ul>
<li>akkaunt va buyurtmalarni boshqarish;</li>
<li>toʻlov va yetkazib berishni taʼminlash;</li>
<li>xavfsizlik, firibgarlikning oldini olish;</li>
<li>qonuniy majburiyatlarni bajarish (davlat organlarining qonuniy soʻrovlari);</li>
<li>xizmat sifatini tahlil qilish va yaxshilash (anonimlashtirilgan statistika).</li>
</ul>

<h2>4. Huquqiy asos</h2>
<p>Qayta ishlash Oʻzbekiston Respublikasining amaldagi qonun hujjatlariga, shu jumladan shaxsga doir maʼlumotlarni himoya qilish talablariga muvofiq amalga oshiriladi.</p>

<h2>5. Cookie va shunga oʻxshash texnologiyalar</h2>
<p>Cookie fayllari sessiya, til tanlovi, autentifikatsiya va (agar yoqilgan boʻlsa) tahlil uchun ishlatiladi. Batafsil — alohida <strong>Cookie siyosati</strong> hujjatida.</p>

<h2>6. Uchinchi tomonlarga uzatish</h2>
<p>Maʼlumotlar quyidagilarga uzatilishi mumkin: toʻlov tizimlari, SMS/push provayderlari, yetkazib berish hamkorlari, hosting va texnik infratuzilma provayderlari — faqat xizmat koʻrsatish uchun zarur hajmda.</p>

<h2>7. Saqlash muddati</h2>
<p>Maʼlumotlar maqsad talab qiladigan muddat yoki qonunda belgilangan muddat davomida saqlanadi, keyin oʻchiriladi yoki anonimlashtiriladi (qonuniy arxivdan tashqari).</p>

<h2>8. Foydalanuvchi huquqlari</h2>
<p>Qonun doirasida maʼlumotlarga kirish, toʻgʻrilash, oʻchirish yoki qayta ishlashni cheklash boʻyicha soʻrov yuborish huquqi mavjud. Soʻrovlar: +998 50 030 30 33.</p>

<h2>9. Bolalar</h2>
<p>Platforma voyaga yetmaganlar uchun maxsus moʻljallanmagan boʻlishi mumkin. Voyaga yetmagan shaxsning maʼlumotlari qonuniy vakil roziligisiz toʻplanmasligi kerak.</p>

<h2>10. Xavfsizlik</h2>
<p>Operator maʼlumotlarni ruxsatsiz kirish, yoʻqotish va buzilishdan himoya qilish uchun texnik va tashkiliy choralar qoʻllaydi.</p>

<h2>11. Siyosatga oʻzgartirish</h2>
<p>Operator ushbu siyosatni yangilashi mumkin. Yangilangan versiya sayt/ilovada eʼlon qilinadi.</p>

<h2>12. Operator</h2>
<p>YATT Turdaliyev Abbos Erkin oʻgʻli, STIR 51503025990027, +998 50 030 30 33.</p>
UZDOC,
            'content_en' => <<<'ENDOC'
<h2>1. Purpose and scope</h2>
<p>This Privacy Policy explains how the Operator collects, stores, uses and protects <strong>personal data</strong> when you use the Kitobchi marketplace.</p>
<p>Processing primarily concerns users located in <strong>Uzbekistan</strong>. International transfers may occur only where technically required (e.g. cloud providers) under appropriate safeguards.</p>

<h2>2. Categories of data</h2>
<ul>
<li><strong>Identity:</strong> name, phone, email (if provided), login identifiers.</li>
<li><strong>Delivery:</strong> address and contact details for couriers.</li>
<li><strong>Transactions:</strong> orders, payment status, receipts and technical identifiers from payment systems (full card PAN is not stored by the Operator unless required otherwise by the payment provider).</li>
<li><strong>Technical:</strong> device type, OS, app version, IP address, cookies (see the Cookie Policy).</li>
<li><strong>Communications:</strong> support chats, reviews, Book Club posts (UGC).</li>
</ul>

<h2>3. Purposes</h2>
<p>Account management, payments and delivery, fraud prevention, legal compliance, service improvement (aggregated analytics).</p>

<h2>4. Legal basis</h2>
<p>Processing follows applicable law of the Republic of Uzbekistan, including personal data protection requirements.</p>

<h2>5. Cookies</h2>
<p>See the separate <strong>Cookie Policy</strong>.</p>

<h2>6. Recipients</h2>
<p>Data may be shared with payment providers, SMS/push services, delivery partners and infrastructure vendors strictly as needed to provide the service.</p>

<h2>7. Retention</h2>
<p>Data is kept as long as necessary for the purposes above or as required by law, then deleted or anonymised.</p>

<h2>8. Your rights</h2>
<p>Where the law allows, you may request access, correction, deletion or restriction. Contact: +998 50 030 30 33.</p>

<h2>9. Children</h2>
<p>The Platform is not directed to children; personal data of minors should not be collected without lawful guardian consent.</p>

<h2>10. Security</h2>
<p>The Operator applies technical and organisational measures to protect data.</p>

<h2>11. Changes</h2>
<p>This Policy may be updated; the new version will be published on the website/app.</p>

<h2>12. Controller</h2>
<p>IE Turdaliyev Abbos Erkin uli, TIN 51503025990027, +998 50 030 30 33.</p>
ENDOC,
        ],

        [
            'slug' => 'sotuvchi-shartlari',
            'sort_order' => 30,
            'title_uz' => 'Sotuvchi shartlari (hamkor ofertasi)',
            'title_en' => 'Seller (Partner) Terms',
            'content_uz' => <<<'UZDOC'
<h2>1. Tomonlar</h2>
<p><strong>Operator:</strong> YATT Turdaliyev Abbos Erkin oʻgʻli, STIR 51503025990027, +998 50 030 30 33.</p>
<p><strong>Sotuvchi</strong> — Kitobchi platformasida roʻyxatdan oʻtgan va mahsulot joylashtiruvchi yakka tartibdagi tadbirkor yoki yuridik shaxs.</p>

<h2>2. Hamkorlik modeli</h2>
<p>Operator elektron savdo platformasini taʼminlaydi: akkauntlar, katalog, buyurtma mexanizmi, toʻlov integratsiyasi (texnik jihatdan), axborot-kommunikatsiya vositalari va moderatsiya.</p>
<p>Sotuvchi oʻz nomidan mahsulot taklif qiladi, narx va zaxira uchun javobgardir.</p>

<h2>3. Mahsulot va tavsif</h2>
<p>Sotuvchi mahsulotning qonuniyligi, sertifikatlari (agar talab qilinsa), intellektual mulk huquqlarining buzilmasligi va tavsifning haqiqiyligi uchun toʻliq javobgar. Taqiqlangan mahsulotlar roʻyxati va moderatsiya qoidalari Operator tomonidan belgilanadi.</p>

<h2>4. Narx, aksiya va zaxira</h2>
<p>Narx va chegirmalar haqiqiy boʻlishi kerak. Zaxira tugagan holda buyurtmani qabul qilish taqiqlanadi (yoki darhol bekor qilish tartibi ilovada koʻrsatiladi).</p>

<h2>5. Buyurtmani bajarish va yetkazib berish</h2>
<p>Sotuvchi buyurtmani belgilangan muddatlarda bajarish, toʻgʻri mahsulotni yuborish va yetkazib berish hamkorlari bilan oʻz zimmasidagi logistikani taʼminlash uchun choralar koʻradi (Operator bilan kelishuvga muvofiq).</p>

<h2>6. Xaridor bilan munosabatlar</h2>
<p><strong>Tovarni xaridorning istagi bilan qaytarib olish</strong> Operator tomonidan isteʼmolchiga taklif etilmaydi — batafsil <strong>“Foydalanish shartlari”</strong> hujjatining 10-bandi. Sotuvchi ushbu modeldan xabardor boʻlishi va oʻz ichki siyosatini shunga moslashtirishi kerak. Nuqson/tavsifga zid holatlar boʻyicha daʼvolar alohida tartibda koʻrib chiqiladi.</p>

<h2>7. Komissiya va hisob-kitob</h2>
<p>Operatorning komissiyasi, hisob-kitob davri va toʻlov tartibi alohida shartnoma, ilovadagi tarif yoki elektron hujjatda belgilanadi. Ushbu hujjat umumiy asos boʻlib, moliyaviy shartlarni toʻliq almashtirmaydi.</p>

<h2>8. Shaxsga doir maʼlumotlar</h2>
<p>Sotuvchi buyurtmani bajarish uchun zarur boʻlmagan shaxsga doir maʼlumotlarni talab qilmasligi kerak. Operator bilan maʼlumot almashinuvi Maxfiylik siyosatiga muvofiq amalga oshiriladi.</p>

<h2>9. Reklama va brend</h2>
<p>Kitobchi nomi va logotipidan foydalanish Operatorning yozma ruxsatiga bogʻliq boʻlishi mumkin.</p>

<h2>10. Moderatsiya va toʻxtatish</h2>
<p>Operator qonun yoki qoidalarni buzgan roʻyxatlarni yashirish, cheklash yoki hamkorlikni toʻxtatishi mumkin.</p>

<h2>11. Nizolar</h2>
<p>Sotuvchi va Operator oʻrtasidagi nizolar muzokara, keyin sud tartibida hal etiladi (yurisdiksiya — Oʻzbekiston Respublikasi).</p>

<h2>12. Aloqa</h2>
<p>+998 50 030 30 33</p>
UZDOC,
            'content_en' => <<<'ENDOC'
<h2>1. Parties</h2>
<p><strong>Operator:</strong> IE Turdaliyev Abbos Erkin uli, TIN 51503025990027.</p>
<p><strong>Seller</strong> — a partner listing goods on Kitobchi.</p>

<h2>2. Marketplace model</h2>
<p>The Operator provides the platform: catalog, orders, payment integration (technical), communications and moderation. The Seller offers goods in its own name and is responsible for price and stock.</p>

<h2>3. Listings</h2>
<p>The Seller warrants legality, authenticity of descriptions, IP compliance and absence of prohibited goods.</p>

<h2>4. Pricing and stock</h2>
<p>Prices and promotions must be truthful. Accepting orders without stock is prohibited unless the app defines a cancellation flow.</p>

<h2>5. Fulfilment</h2>
<p>The Seller must fulfil orders on time, ship correct items and organise logistics within its scope as agreed with the Operator.</p>

<h2>6. Relationship with buyers</h2>
<p><strong>Discretionary returns are not offered</strong> under the consumer public offer. Defect/mismatch cases are handled individually under law and internal procedures.</p>

<h2>7. Fees</h2>
<p>Commission, settlement cycles and invoicing are defined in a separate agreement or in-app tariff.</p>

<h2>8. Personal data</h2>
<p>The Seller must not request unnecessary personal data. Data exchange follows the Privacy Policy.</p>

<h2>9. Branding</h2>
<p>Use of Kitobchi trademarks may require written permission.</p>

<h2>10. Moderation</h2>
<p>The Operator may hide listings or terminate cooperation for violations.</p>

<h2>11. Disputes</h2>
<p>Disputes between Seller and Operator are resolved by negotiation and then under Uzbekistan law.</p>
<p>Contact: +998 50 030 30 33</p>
ENDOC,
        ],

        [
            'slug' => 'yetkazib-berish-va-qaytarish',
            'sort_order' => 40,
            'title_uz' => 'Yetkazib berish va tovarni qaytarish tartibi',
            'title_en' => 'Delivery & returns policy',
            'content_uz' => <<<'UZDOC'
<h2>1. Yetkazib berish hududi</h2>
<p>Yetkazib berish <strong>faqat Oʻzbekiston Respublikasi</strong> boʻylab amalga oshiriladi. Xalqaro yuboruvlar mavjud emas (alohida yozma kelishuvsiz).</p>

<h2>2. Yetkazib berish usullari</h2>
<p>Yetkazib berish kuryer xizmati, punktlar yoki pochta orqali tashkil etilishi mumkin. Aniq usul, narx va muddat buyurtma berish bosqichida koʻrsatiladi.</p>

<h2>3. Manzil va qabul qilish</h2>
<p>Foydalanuvchi toʻgʻri manzil va aloqa raqamini kiritishga masʼul. Yetkazib berish paytida shaxsni tasdiqlovchi hujjat yoki kod talab qilinishi mumkin.</p>

<h2>4. Muddati</h2>
<p>Yetkazib berish muddati viloyat, yuklama va tanlangan xizmat turiga qarab oʻzgaradi. Force-majeure (ob-havo, blokirovka va hokazo) holatlarida muddat uzaytirilishi mumkin — Operator yoki sotuvchi xabar beradi.</p>

<h2>5. Topshirishdan bosh tortish</h2>
<p>Agar xaridor sababsiz topshirishdan bosh tortsa yoki aloqaga chiqmasa, buyurtma bekor qilinishi yoki qoʻshimcha xarajatlar undirilishi mumkin (ichki tartib va qonunga muvofiq).</p>

<h2>6. Tovarni qaytarish — umumiy qoida</h2>
<p><strong>6.1.</strong> Xaridorning istagi bilan (yoqmadi, oʻlcham, rang, xohishmastlik) <strong>tovarni qaytarib olish amalga oshirilmaydi</strong>.</p>
<p><strong>6.2.</strong> <strong>Nuqson</strong>, <strong>tavsifga sezilarli mos kelmaslik</strong> yoki <strong>notoʻgʻri mahsulot</strong> boʻyicha ariza alohida koʻrib chiqiladi; natija har bir holat boʻyicha aniqlanadi (qonun va dalillar asosida).</p>
<p><strong>6.3.</strong> Muqovasi ochilgan bosma nashr va shunga oʻxshash mahsulotlar boʻyicha <strong>qaytarish koʻrib chiqilmaydi</strong> (qonunda boshqacha boʻlsa, shu tartib).</p>

<h2>7. Aloqa</h2>
<p>Yetkazib berish va holatlar boʻyicha: +998 50 030 30 33, ilova ichidagi qoʻllab-quvvatlash.</p>
UZDOC,
            'content_en' => <<<'ENDOC'
<h2>1. Territory</h2>
<p>Delivery is performed <strong>only within the Republic of Uzbekistan</strong>. No international shipping unless expressly agreed otherwise.</p>

<h2>2. Methods</h2>
<p>Courier, pick-up points or postal services may be used. Method, price and ETA are shown at checkout.</p>

<h2>3. Address and handover</h2>
<p>You are responsible for correct address and contact details. ID or pickup codes may be required.</p>

<h2>4. Timelines</h2>
<p>Delivery times depend on region, load and service type. Force majeure may extend timelines with notice.</p>

<h2>5. Refusal to accept</h2>
<p>Unjustified refusal to accept delivery may lead to cancellation or extra fees under internal rules and law.</p>

<h2>6. Returns — general rule</h2>
<p><strong>6.1.</strong> <strong>No discretionary returns</strong> (change of mind, size/colour dislike).</p>
<p><strong>6.2.</strong> Claims for <strong>defects</strong>, <strong>material mismatch</strong> or <strong>wrong item</strong> are reviewed individually.</p>
<p><strong>6.3.</strong> Opened books and similar goods are generally <strong>non-returnable</strong> unless mandatory law provides otherwise.</p>

<h2>7. Contact</h2>
<p>+998 50 030 30 33; in-app support.</p>
ENDOC,
        ],

        [
            'slug' => 'tolov-va-qaytarish',
            'sort_order' => 50,
            'title_uz' => 'Toʻlov, chek va pulni qaytarish',
            'title_en' => 'Payments, receipts & refunds',
            'content_uz' => <<<'UZDOC'
<h2>1. Toʻlov usullari</h2>
<p>Platforma orqali elektron toʻlov (masalan, Payme) qabul qilinishi mumkin. Toʻlov provayderining oʻz qoidalari qoʻllanadi.</p>

<h2>2. Chek va hujjatlar</h2>
<p>Elektron chek yoki tranzaksiya identifikatori toʻlov tizimi va Operator tartibiga muvofiq saqlanadi.</p>

<h2>3. Pulni qaytarish holatlari</h2>
<p>Pul qaytarish quyidagi holatlarda koʻrib chiqiladi (roʻyxat yakuniy emas, qonun va platforma funksiyalariga bogʻliq):</p>
<ul>
<li>buyurtma <strong>yuborilishidan oldin</strong> bekor qilinganda (agar ilovada ushbu imkoniyat mavjud boʻlsa);</li>
<li>toʻlovning <strong>dublikati</strong> yoki texnik xato tufayli ortiqcha yechim;</li>
<li>Yetkazib berish va tovarni qaytarish siyosatining <strong>6.2</strong>-bandi boʻyicha alohida qaror qabul qilinganda (nuqson / mos kelmaslik / notoʻgʻri mahsulot).</li>
</ul>
<p><strong>Tovarni oddiy xarid asosida qaytarish yoʻqligi sababli pul qaytarish ham amalga oshirilmaydi</strong> (yuqoridagi holatlardan tashqari).</p>

<h2>4. Muddati</h2>
<p>Pul qaytarish muddati toʻlov tizimi va bank operatsiyalariga bogʻliq; odatda bir necha ish kuni ichida, lekin aniq muddat toʻlov provayderi qoidalariga muvofiq.</p>

<h2>5. Operator bank rekvizitlari</h2>
<p><strong>YATT Turdaliyev Abbos Erkin oʻgʻli</strong><br>Bank: Trastbank<br>Hisob raqami: <strong>555555555</strong> <em>(haqiqiy hisob raqami bilan almashtiring)</em><br>STIR: 51503025990027</p>

<h2>6. Savollar</h2>
<p>+998 50 030 30 33</p>
UZDOC,
            'content_en' => <<<'ENDOC'
<h2>1. Methods</h2>
<p>Electronic payments (e.g. Payme) may be available. Payment provider rules apply.</p>

<h2>2. Receipts</h2>
<p>Electronic receipts/transaction IDs are stored according to Operator and provider procedures.</p>

<h2>3. Refunds</h2>
<p>Refunds may be considered, among others, when:</p>
<ul>
<li>an order is <strong>cancelled before dispatch</strong> (if the app allows);</li>
<li>there is a <strong>duplicate payment</strong> or technical overcharge;</li>
<li>a separate decision is made under the Delivery & returns policy section <strong>6.2</strong> (defect / mismatch / wrong item).</li>
</ul>
<p><strong>No refunds for discretionary returns</strong> outside the above cases.</p>

<h2>4. Timing</h2>
<p>Refund timing depends on the payment provider and bank processing.</p>

<h2>5. Bank details</h2>
<p>IE Turdaliyev Abbos Erkin uli — Bank: Trastbank, account <strong>555555555</strong> <em>(replace with your real account)</em>, TIN 51503025990027.</p>

<h2>6. Contact</h2>
<p>+998 50 030 30 33</p>
ENDOC,
        ],

        [
            'slug' => 'obuna-shartlari',
            'sort_order' => 60,
            'title_uz' => 'Obuna va qoʻshimcha pullik xizmatlar shartlari',
            'title_en' => 'Subscription & paid add-ons',
            'content_uz' => <<<'UZDOC'
<h2>1. Obuna va pullik funksiyalar</h2>
<p>Agar Kitobchi obuna, premium-funksiya yoki boshqa pullik modullarni taklif qilsa, narxi, davri, avtomatik yangilanish va bekor qilish tartibi ilovada alohida koʻrsatiladi.</p>

<h2>2. Avtomatik toʻlov</h2>
<p>Obuna avtomatik yangilanishi foydalanuvchining roziligi va ulangan toʻlov usuliga asoslanadi. Foydalanuvchi istalgan vaqtda avtomatik yangilanishni o‘chirish imkoniyatiga ega boʻlishi kerak (agar xizmat mavjud boʻlsa).</p>

<h2>3. Bekor qilish</h2>
<p>Obuna bekor qilinganda keyingi toʻlov davri boshlanmaydi; toʻlangan davr tugaguncha xizmat qonun va ichki qoidalarga muvofiq davom etishi mumkin.</p>

<h2>4. Pulni qaytarish</h2>
<p>Obuna toʻlovlari boʻyicha pul qaytarish faqat Platformada yozilgan va qonunda ruxsat etilgan hollarda amalga oshiriladi.</p>

<h2>5. Operator</h2>
<p>YATT Turdaliyev Abbos Erkin oʻgʻli, STIR 51503025990027, +998 50 030 30 33.</p>
UZDOC,
            'content_en' => <<<'ENDOC'
<h2>1. Subscriptions and paid features</h2>
<p>If Kitobchi offers subscriptions or paid modules, pricing, period, auto-renewal and cancellation are described in the app.</p>

<h2>2. Auto-billing</h2>
<p>Auto-renewal is based on your consent and stored payment method. You must be able to disable auto-renewal where the feature exists.</p>

<h2>3. Cancellation</h2>
<p>After cancellation, the next billing cycle does not start; paid periods may continue until expiry under law and internal rules.</p>

<h2>4. Refunds</h2>
<p>Subscription refunds only where permitted by the Platform and law.</p>

<h2>5. Operator</h2>
<p>IE Turdaliyev Abbos Erkin uli, TIN 51503025990027, +998 50 030 30 33.</p>
ENDOC,
        ],

        [
            'slug' => 'cookies-siyosati',
            'sort_order' => 70,
            'title_uz' => 'Cookie va shunga oʻxshash texnologiyalar siyosati',
            'title_en' => 'Cookie & similar technologies',
            'content_uz' => <<<'UZDOC'
<h2>1. Cookie nima?</h2>
<p>Cookie — brauzer yoki ilova konteynerida saqlanadigan kichik maʼlumot fayllari bo‘lib, sessiya, til, autentifikatsiya va (agar yoqilgan boʻlsa) tahlil uchun ishlatiladi.</p>

<h2>2. Qanday cookie ishlatiladi</h2>
<ul>
<li><strong>Zarur texnik cookie</strong> — tizimga kirish, xavfsizlik, savat sessiyasi;</li>
<li><strong>Funksional</strong> — interfeys sozlamalari;</li>
<li><strong>Analitik</strong> — anonimlashtirilgan statistika (agar yoqilgan boʻlsa va rozilik berilgan boʻlsa).</li>
</ul>

<h2>3. Üchinchi tomon cookie</h2>
<p>Toʻlov oynasi yoki embed qilingan xizmatlar oʻz cookie fayllariga ega boʻlishi mumkin — ularning siyosatini oʻqing.</p>

<h2>4. Boshqarish</h2>
<p>Brauzer sozlamalaridan cookie ni oʻchirish yoki cheklash mumkin; bu Platformaning ayrim funksiyalariga taʼsir qilishi mumkin.</p>

<h2>5. Rozilik</h2>
<p>Analitik yoki marketing cookie (agar mavjud boʻlsa) uchun alohida rozilik soʻralishi mumkin.</p>

<h2>6. Operator</h2>
<p>YATT Turdaliyev Abbos Erkin oʻgʻli, +998 50 030 30 33.</p>
UZDOC,
            'content_en' => <<<'ENDOC'
<h2>1. What are cookies</h2>
<p>Cookies are small data files stored in your browser or app container for session, language, authentication and (if enabled) analytics.</p>

<h2>2. Types we use</h2>
<ul>
<li><strong>Strictly necessary</strong> — login, security, cart session;</li>
<li><strong>Functional</strong> — UI preferences;</li>
<li><strong>Analytics</strong> — aggregated statistics if enabled and consented.</li>
</ul>

<h2>3. Third-party cookies</h2>
<p>Payment windows or embedded services may set their own cookies — read their policies.</p>

<h2>4. Control</h2>
<p>You can restrict cookies in browser settings; some features may stop working.</p>

<h2>5. Consent</h2>
<p>Where required, analytics/marketing cookies are used only with consent.</p>

<h2>6. Controller</h2>
<p>IE Turdaliyev Abbos Erkin uli, +998 50 030 30 33.</p>
ENDOC,
        ],
    ],
];
