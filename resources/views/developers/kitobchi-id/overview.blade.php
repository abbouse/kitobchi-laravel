@php($kidIss = \App\Services\KitobchiId\TokenService::issuer())
<h2 class="doc-h2" id="overview">Kitobchi ID nima?</h2>
<p class="doc-p"><strong>Kitobchi ID</strong> — foydalanuvchilarga Kitobchi akkaunti bilan boshqa sayt va ilovalarga bir tugma orqali kirish imkonini beruvchi yagona identifikatsiya tizimi. U ochiq standartlarga asoslangan: <strong>OAuth 2.1</strong> (authorization code + PKCE) va <strong>OpenID Connect 1.0</strong>. Shu sababli istalgan standart OIDC kutubxonasi (AppAuth, openid-client, league/oauth2-client, Spring Security, NextAuth/Auth.js va boshqalar) bilan qo'shimcha kodsiz ishlaydi.</p>
<p class="doc-p">Foydalanuvchi uchun: parol yo'q — telefon raqamiga keladigan SMS kod bilan kiradi, qaysi ma'lumotlar ulashilishini o'zi ko'radi va tasdiqlaydi. Siz uchun: tasdiqlangan telefon raqami, ism va profil rasmi, SMS va akkaunt tiklash bilan shug'ullanmasdan.</p>

<div class="note">
  <strong>Issuer:</strong> <span class="ic">{{ $kidIss }}</span><br>
  Barcha endpointlarni qo'lda yozmang — ularni discovery hujjatidan oling: <span class="ic">{{ $kidIss }}/.well-known/openid-configuration</span>
</div>

<hr class="divider">
<h2 class="doc-h2" id="flow">Kirish oqimi</h2>
<div class="steps">
  <div class="step"><div class="step-num">1</div><div>
    <h3>Ilova foydalanuvchini Kitobchi ID'ga yuboradi</h3>
    <p><span class="ic">/authorize</span> ga <span class="ic">state</span>, <span class="ic">nonce</span> va PKCE <span class="ic">code_challenge</span> bilan yo'naltirasiz. Mobil ilovada — tizim brauzeri (Custom Tabs / ASWebAuthenticationSession), WebView emas.</p>
  </div></div>
  <div class="step"><div class="step-num">2</div><div>
    <h3>Foydalanuvchi kiradi va rozilik beradi</h3>
    <p>Telefon raqam → SMS kod (SMS'da ilovangiz nomi ko'rsatiladi). Birinchi marta ulanishda so'ralgan ma'lumotlar ro'yxati va ilovangiz domeni ko'rsatiladi. Keyingi kirishlarda — bir bosishda.</p>
  </div></div>
  <div class="step"><div class="step-num">3</div><div>
    <h3>Ilova bir martalik kod oladi</h3>
    <p><span class="ic">redirect_uri?code=…&amp;state=…&amp;iss=…</span> — <span class="ic">state</span> va <span class="ic">iss</span> ni albatta tekshirasiz. Kod 5 daqiqa amal qiladi va faqat bir marta ishlatiladi.</p>
  </div></div>
  <div class="step"><div class="step-num">4</div><div>
    <h3>Server kodni tokenlarga almashtiradi</h3>
    <p><span class="ic">/token</span> ga <span class="ic">code</span> + <span class="ic">code_verifier</span> yuborib <strong>access token</strong> (10 daqiqa), <strong>ID token</strong> (1 soat) va kerak bo'lsa <strong>refresh token</strong> olasiz. ID token imzosini JWKS orqali tekshirib, foydalanuvchini <span class="ic">sub</span> bo'yicha tanib olasiz.</p>
  </div></div>
</div>

<hr class="divider">
<h2 class="doc-h2" id="endpoints">Endpointlar</h2>
<table class="doc-table">
  <thead><tr><th>Endpoint</th><th>Usul</th><th>Vazifasi</th></tr></thead>
  <tbody>
    <tr><td><span class="ic">/.well-known/openid-configuration</span></td><td>GET</td><td>Discovery — barcha manzillar va imkoniyatlar (1 soat keshlang)</td></tr>
    <tr><td><span class="ic">/authorize</span></td><td>GET (brauzer)</td><td>Kirish va rozilik sahifasi, kod qaytaradi</td></tr>
    <tr><td><span class="ic">/token</span></td><td>POST (form)</td><td>Kodni yoki refresh tokenni tokenlarga almashtirish</td></tr>
    <tr><td><span class="ic">/userinfo</span></td><td>GET/POST + Bearer</td><td>Joriy foydalanuvchi ma'lumotlari (scope bo'yicha)</td></tr>
    <tr><td><span class="ic">/jwks</span></td><td>GET</td><td>ID va access tokenlarni tekshirish uchun ochiq kalitlar (RS256, <span class="ic">kid</span>)</td></tr>
    <tr><td><span class="ic">/revoke</span></td><td>POST (form)</td><td>Token bekor qilish (RFC 7009) — chiqishda chaqiring</td></tr>
    <tr><td><span class="ic">/logout</span></td><td>GET (brauzer)</td><td>Kitobchi ID sessiyasini yopish (RP-initiated logout)</td></tr>
  </tbody>
</table>
<p class="doc-p">Barcha manzillar issuer'ga nisbatan: <span class="ic">{{ $kidIss }}/token</span> va hokazo.</p>

<hr class="divider">
<h2 class="doc-h2" id="scopes">Scope va claim'lar</h2>
<p class="doc-p">Faqat haqiqatan kerakli ma'lumotni so'rang — har bir qo'shimcha scope rozilik oynasida alohida qator bo'lib chiqadi va konversiyani pasaytiradi. Ilovangizga ruxsat berilgan scope'lar ro'yxatdan o'tishda belgilanadi.</p>
<table class="doc-table">
  <thead><tr><th>Scope</th><th>Claim'lar</th><th>Izoh</th></tr></thead>
  <tbody>
    <tr><td><span class="ic">openid</span></td><td><span class="ic">sub</span></td><td><span class="chip chip-req">majburiy</span> Foydalanuvchining doimiy identifikatori</td></tr>
    <tr><td><span class="ic">profile</span></td><td><span class="ic">name</span>, <span class="ic">given_name</span>, <span class="ic">family_name</span>, <span class="ic">preferred_username</span>, <span class="ic">picture</span>, <span class="ic">locale</span>, <span class="ic">updated_at</span></td><td>Bo'sh maydonlar javobda bo'lmaydi</td></tr>
    <tr><td><span class="ic">phone</span></td><td><span class="ic">phone_number</span> (E.164, <span class="ic">+998…</span>), <span class="ic">phone_number_verified</span></td><td>Kitobchi ID orqali kirgan raqam doim SMS bilan tasdiqlangan</td></tr>
    <tr><td><span class="ic">email</span></td><td><span class="ic">email</span>, <span class="ic">email_verified</span></td><td>Ixtiyoriy, ko'pincha tasdiqlanmagan — identifikator sifatida ishlatmang</td></tr>
    <tr><td><span class="ic">offline_access</span></td><td>—</td><td>Refresh token beriladi (foydalanuvchi ilovada bo'lmaganda ham sessiyani yangilash)</td></tr>
  </tbody>
</table>

<h3 class="doc-h3">sub — foydalanuvchi identifikatori</h3>
<p class="doc-p">Foydalanuvchini <strong>faqat <span class="ic">sub</span> bo'yicha</strong> bog'lang (bazada <span class="ic">issuer + sub</span> juftligini saqlang). Telefon raqam yoki email'ni kalit qilmang — raqamlar almashadi va qayta beriladi.</p>
<ul class="checklist">
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <div><strong>Hamkor ilovalar uchun pairwise sub</strong> — bitta foydalanuvchi har bir hamkor (domen) uchun boshqa <span class="ic">sub</span> oladi (43 belgili satr). Ikki hamkor o'z bazalarini birlashtirib foydalanuvchini kuzata olmaydi.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <div><strong>Barqaror</strong> — <span class="ic">sub</span> foydalanuvchi ismi, raqami yoki rasmi o'zgarganda o'zgarmaydi.</div></li>
</ul>

<hr class="divider">
<h2 class="doc-h2" id="register">Ilovani ulash</h2>
<p class="doc-p">Hozircha ilovalar Kitobchi jamoasi tomonidan qo'lda ro'yxatdan o'tkaziladi. Quyidagilarni yuboring:</p>
<table class="doc-table">
  <thead><tr><th>Ma'lumot</th><th>Misol</th><th>Talab</th></tr></thead>
  <tbody>
    <tr><td>Ilova nomi va logotipi (https, kvadrat)</td><td>«Hamkor do'kon»</td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td>Sayt manzili</td><td><span class="ic">https://hamkor.uz</span></td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td>Redirect URI'lar (aniq, to'liq)</td><td><span class="ic">https://hamkor.uz/auth/kitobchi/callback</span></td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td>Ilova turi</td><td>server (confidential) yoki SPA/mobil (public)</td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td>Kerakli scope'lar va sababi</td><td><span class="ic">openid profile phone</span></td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td>Chiqishdan keyingi URI</td><td><span class="ic">https://hamkor.uz/</span></td><td><span class="chip chip-opt">ixtiyoriy</span></td></tr>
    <tr><td>Texnik aloqa e-pochtasi</td><td><span class="ic">dev@hamkor.uz</span></td><td><span class="chip chip-req">majburiy</span></td></tr>
  </tbody>
</table>
<p class="doc-p">Javoban <span class="ic">client_id</span> (<span class="ic">kid_…</span>) va server ilovalar uchun <span class="ic">client_secret</span> (<span class="ic">kid_cs_…</span>) beriladi. Secret faqat bir marta ko'rsatiladi; bizda faqat uning hash'i saqlanadi — yo'qolsa, yangisi chiqariladi.</p>

<h3 class="doc-h3">Redirect URI qoidalari</h3>
<ul class="checklist">
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Faqat https</strong>. Istisno: lokal ishlab chiqish uchun <span class="ic">http://127.0.0.1:PORT/…</span> va <span class="ic">http://localhost/…</span>.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Aniq moslik</strong> — wildcard (<span class="ic">*</span>), prefiks va fragment (<span class="ic">#</span>) yo'q. Har bir qaytish manzili alohida ro'yxatdan o'tadi.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Mobil ilovalar</strong> — imkon qadar App Links / Universal Links (https). Custom scheme faqat reverse-DNS ko'rinishida (<span class="ic">uz.hamkor.app:/oauth</span>) va faqat public client uchun.</div></li>
</ul>
