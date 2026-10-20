@php($kidIss = \App\Services\KitobchiId\TokenService::issuer())
<h2 class="doc-h2" id="model">Xavfsizlik modeli</h2>
<p class="doc-p">Kitobchi ID OAuth 2.0 Security Best Current Practice (RFC 9700) va OAuth 2.1 talablariga mos qurilgan. Quyidagi himoyalar server tomonida <strong>majburiy</strong> va o'chirib bo'lmaydi:</p>
<table class="doc-table">
  <thead><tr><th>Himoya</th><th>Nimadan saqlaydi</th></tr></thead>
  <tbody>
    <tr><td>Faqat authorization code oqimi (implicit va password grant yo'q)</td><td>Tokenlarning URL va brauzer tarixiga tushishi</td></tr>
    <tr><td>PKCE S256 barcha ilovalar uchun majburiy</td><td>Kodni ushlab olish (code interception), kod in'eksiyasi</td></tr>
    <tr><td>Redirect URI aniq moslik bilan; noto'g'ri bo'lsa hech qayerga yo'naltirilmaydi</td><td>Ochiq redirect, kodni begona saytga oqizish</td></tr>
    <tr><td><span class="ic">state</span> va <span class="ic">nonce</span> majburiy; javobda <span class="ic">iss</span> (RFC 9207)</td><td>CSRF, ID token qayta ishlatilishi, mix-up hujumi</td></tr>
    <tr><td>Kod bir martalik, 5 daqiqa; qayta ishlatilsa shu koddan chiqqan barcha tokenlar bekor</td><td>O'g'irlangan kodni qayta ishlatish</td></tr>
    <tr><td>Har qanday muvaffaqiyatsiz almashtirish urinishi kodni yoqadi</td><td>PKCE verifier'ni tanlab topish</td></tr>
    <tr><td>Refresh token rotatsiyasi + qayta ishlatishni aniqlash (butun oila bekor)</td><td>O'g'irlangan refresh token</td></tr>
    <tr><td>Access token 10 daqiqa, <span class="ic">typ: at+jwt</span>; ID tokenni Bearer sifatida qabul qilinmaydi</td><td>Token almashtirish (token confusion)</td></tr>
    <tr><td>Kod, refresh token va client secret bazada faqat hash (SHA-256 / bcrypt)</td><td>Baza sizib chiqqanda tokenlardan foydalanish</td></tr>
    <tr><td>RS256 + <span class="ic">kid</span>, kalit rotatsiyasi bilan; <span class="ic">alg: none</span> va HS256 hech qachon</td><td>Imzoni soxtalashtirish, algoritm almashtirish</td></tr>
    <tr><td>Pairwise <span class="ic">sub</span> hamkor ilovalar uchun</td><td>Ilovalararo foydalanuvchini kuzatish</td></tr>
    <tr><td>SMS kod: HMAC bilan saqlanadi, 5 urinish, 60 s qayta yuborish, kuniga 10 ta; SMS'da ilova nomi; master-kod yo'q</td><td>Kodni tanlab topish, SMS-bombing, fishing</td></tr>
    <tr><td>Kirish sahifalari: <span class="ic">frame-ancestors 'none'</span>, CSRF, <span class="ic">no-store</span>, <span class="ic">no-referrer</span>, sessiya ID login'dan keyin yangilanadi</td><td>Clickjacking, CSRF, sessiya fiksatsiyasi</td></tr>
    <tr><td>Bloklangan yoki o'chirilgan akkaunt — refresh va userinfo darhol ishlamaydi</td><td>Bloklangan foydalanuvchining eski tokenlari</td></tr>
    <tr><td>IP bo'yicha so'rov limitlari (authorize 60/daq, SMS 5/daq, token 60/daq)</td><td>Brute-force va DoS</td></tr>
  </tbody>
</table>

<hr class="divider">
<h2 class="doc-h2" id="rp-rules">Integratsiya qiluvchi uchun qoidalar</h2>
<ul class="checklist">
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>client_secret faqat serverda</strong> — muhit o'zgaruvchisi yoki secret manager'da. Git, frontend, mobil ilova, log va xato xabarlariga tushmasin. Sizib chiqsa — darhol bizga yozing, secret almashtiriladi.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Tokenlarni brauzerga bermang</strong> — SPA bo'lsangiz ham «backend-for-frontend» (BFF) yondashuvi: tokenlar serverda, brauzerda faqat httpOnly + Secure + SameSite=Lax sessiya cookie'si.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Refresh tokenni shifrlab saqlang</strong> (mobil: Keychain / Android Keystore orqali secure storage). Hech qachon localStorage'da emas.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Tokenlarni log qilmang</strong> — so'rov loglarida <span class="ic">Authorization</span> header, <span class="ic">code</span>, <span class="ic">refresh_token</span> maskalansin.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Akkauntni faqat <span class="ic">sub</span> bo'yicha bog'lang</strong>. Mavjud akkauntni telefon raqami bo'yicha avtomatik birlashtirmang — foydalanuvchidan mavjud akkauntga kirib tasdiqlashni so'rang.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>JWKS'ni keshlang (≤ 15 daq)</strong>, noma'lum <span class="ic">kid</span> kelsa bir marta qayta yuklang — kalit rotatsiyasi shunday ishlaydi.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Mobil: WebView yo'q</strong> — faqat tizim brauzeri (Custom Tabs / ASWebAuthenticationSession) va imkon qadar App Links / Universal Links.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Faqat kerakli scope</strong> — kam ma'lumot so'ragan ilova ko'proq ishonch qozonadi. Telefon raqam kerak bo'lmasa <span class="ic">phone</span> so'ramang.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Shaxsga doir ma'lumotlar</strong> — olingan ma'lumotlarni O'zbekiston Respublikasining «Shaxsga doir ma'lumotlar to'g'risida»gi qonuniga muvofiq qayta ishlang, o'z maxfiylik siyosatingizda Kitobchi ID'ni ko'rsating va foydalanuvchi so'roviga ko'ra o'chiring.</div></li>
</ul>

<hr class="divider">
<h2 class="doc-h2" id="errors">Xato kodlari</h2>
<h3 class="doc-h3">/authorize (redirect_uri ga qaytadi)</h3>
<table class="doc-table">
  <thead><tr><th>error</th><th>Sabab</th><th>Nima qilish kerak</th></tr></thead>
  <tbody>
    <tr><td><span class="ic">invalid_request</span></td><td>state/nonce/PKCE yo'q yoki noto'g'ri, parametr takrorlangan</td><td>So'rovni tuzating</td></tr>
    <tr><td><span class="ic">unsupported_response_type</span></td><td><span class="ic">response_type</span> ≠ <span class="ic">code</span></td><td>Faqat code flow</td></tr>
    <tr><td><span class="ic">invalid_scope</span></td><td><span class="ic">openid</span> yo'q, noma'lum yoki ilovaga ruxsat berilmagan scope</td><td>Scope ro'yxatini tekshiring</td></tr>
    <tr><td><span class="ic">access_denied</span></td><td>Foydalanuvchi «Rad etish»ni bosdi</td><td>Odatiy holat — kirish sahifasiga qaytaring</td></tr>
    <tr><td><span class="ic">login_required</span>, <span class="ic">consent_required</span></td><td><span class="ic">prompt=none</span>, lekin kirish yoki rozilik kerak</td><td><span class="ic">prompt</span>'siz qayta yo'naltiring</td></tr>
    <tr><td><span class="ic">request_not_supported</span></td><td><span class="ic">request</span>/<span class="ic">request_uri</span> parametrlari</td><td>Oddiy query parametrlardan foydalaning</td></tr>
  </tbody>
</table>
<p class="doc-p">Agar <span class="ic">client_id</span> yoki <span class="ic">redirect_uri</span> noto'g'ri bo'lsa, xavfsizlik uchun foydalanuvchi ilovangizga <strong>qaytarilmaydi</strong> — Kitobchi ID xato sahifasini ko'radi.</p>
<h3 class="doc-h3">/token, /revoke (JSON)</h3>
<table class="doc-table">
  <thead><tr><th>HTTP</th><th>error</th><th>Sabab</th></tr></thead>
  <tbody>
    <tr><td>400</td><td><span class="ic">invalid_request</span></td><td>Parametr yo'q, Content-Type form emas, ikki xil client autentifikatsiyasi</td></tr>
    <tr><td>401</td><td><span class="ic">invalid_client</span></td><td>client_id/secret noto'g'ri, ilova o'chirilgan, public ilova secret yubordi</td></tr>
    <tr><td>400</td><td><span class="ic">invalid_grant</span></td><td>Kod/refresh token yaroqsiz, muddati o'tgan, ishlatilgan, PKCE yoki redirect_uri mos emas, akkaunt bloklangan</td></tr>
    <tr><td>400</td><td><span class="ic">invalid_scope</span></td><td>Refresh'da kengroq scope so'raldi</td></tr>
    <tr><td>400</td><td><span class="ic">unsupported_grant_type</span></td><td>Faqat <span class="ic">authorization_code</span> va <span class="ic">refresh_token</span></td></tr>
    <tr><td>429</td><td>—</td><td>So'rov limiti; <span class="ic">Retry-After</span> ga amal qiling</td></tr>
  </tbody>
</table>
<p class="doc-p"><span class="ic">invalid_grant</span> refresh paytida kelsa — lokal sessiyani yoping va foydalanuvchini qayta kirishga yuboring. Qayta-qayta urinmang.</p>

<hr class="divider">
<h2 class="doc-h2" id="lifetimes">Muddatlar</h2>
<table class="doc-table">
  <thead><tr><th>Element</th><th>Muddat</th></tr></thead>
  <tbody>
    <tr><td>Authorization code</td><td>5 daqiqa, bir martalik</td></tr>
    <tr><td>Access token</td><td>10 daqiqa</td></tr>
    <tr><td>ID token</td><td>1 soat (faqat kirish paytida tekshiriladi)</td></tr>
    <tr><td>Refresh token</td><td>30 kun sirpanuvchi, maksimal 90 kun</td></tr>
    <tr><td>Kitobchi ID brauzer sessiyasi</td><td>30 kungacha (<span class="ic">max_age</span> / <span class="ic">prompt=login</span> bilan qisqartirish mumkin)</td></tr>
    <tr><td>SMS kod</td><td>5 daqiqa, 5 urinish</td></tr>
  </tbody>
</table>

<hr class="divider">
<h2 class="doc-h2" id="checklist">Ishga tushirishdan oldin</h2>
<ul class="checklist">
  @foreach ([
    'state, nonce va code_verifier har urinish uchun yangi va tasodifiy',
    'Callback’da state va iss tekshiriladi, state bir martalik',
    'ID token: imzo (RS256), iss, aud, exp, nonce tekshiriladi',
    'Foydalanuvchi bazada (issuer, sub) bo‘yicha saqlanadi',
    'client_secret faqat serverda; mobil/SPA — public client',
    'Refresh token shifrlangan, rotatsiya atomik, invalid_grant’da qayta login',
    'Chiqishda /revoke chaqiriladi',
    'Kirish tugmasi brend qoidalariga mos',
  ] as $item)
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div>{{ $item }}</div></li>
  @endforeach
</ul>
<div class="note"><strong>Zaiflik topdingizmi?</strong> Iltimos, ommaga e'lon qilishdan oldin <span class="ic">toordaliev&#64;gmail.com</span> ga yoki Telegram: <span class="ic">&#64;kitobchi_support</span> ga yozing. Tasdiqlangan topilmalar uchun minnatdorchilik bildiramiz.</div>
