@php($kidIss = \App\Services\KitobchiId\TokenService::issuer())
<h2 class="doc-h2" id="authorize">1. Foydalanuvchini yo'naltirish</h2>
<p class="doc-p">Har bir kirish urinishi uchun serveringizda uchta tasodifiy qiymat yarating va foydalanuvchi sessiyasida (yoki qisqa muddatli httpOnly cookie'da) saqlang:</p>
<table class="doc-table">
  <thead><tr><th>Qiymat</th><th>Qanday yaratiladi</th><th>Nima uchun</th></tr></thead>
  <tbody>
    <tr><td><span class="ic">state</span></td><td>≥ 32 bayt tasodifiy, base64url</td><td>CSRF va javobni so'rovga bog'lash</td></tr>
    <tr><td><span class="ic">nonce</span></td><td>≥ 32 bayt tasodifiy</td><td>ID token qayta ishlatilishining oldini olish</td></tr>
    <tr><td><span class="ic">code_verifier</span></td><td>32 bayt tasodifiy → base64url (43 belgi)</td><td>PKCE — kod o'g'irlansa ham almashtirib bo'lmaydi</td></tr>
  </tbody>
</table>
<p class="doc-p"><span class="ic">code_challenge = BASE64URL(SHA256(code_verifier))</span>. Faqat <span class="ic">S256</span> qabul qilinadi; <span class="ic">plain</span> rad etiladi.</p>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">HTTP — brauzerni yo'naltirish</span><button class="copy-btn" type="button">Ko'chirish</button></div>
  <pre>GET {{ $kidIss }}/authorize
  ?response_type=code
  &amp;client_id=kid_xxxxxxxxxxxxxxxxxxxxxxxx
  &amp;redirect_uri=https%3A%2F%2Fhamkor.uz%2Fauth%2Fkitobchi%2Fcallback
  &amp;scope=openid%20profile%20phone
  &amp;state=Q2xpZW50U3RhdGVSYW5kb20xMjM0NTY3ODkw
  &amp;nonce=Tm9uY2VSYW5kb21WYWx1ZTEyMzQ1Njc4OTA
  &amp;code_challenge=E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM
  &amp;code_challenge_method=S256</pre>
</div>
<table class="doc-table">
  <thead><tr><th>Parametr</th><th>Tavsif</th><th>Talab</th></tr></thead>
  <tbody>
    <tr><td><span class="ic">response_type</span></td><td>Faqat <span class="ic">code</span></td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td><span class="ic">client_id</span></td><td>Sizga berilgan identifikator</td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td><span class="ic">redirect_uri</span></td><td>Ro'yxatdagilardan biri, belgi-belgi bir xil</td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td><span class="ic">scope</span></td><td>Bo'sh joy bilan ajratilgan, <span class="ic">openid</span> bo'lishi shart</td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td><span class="ic">state</span></td><td>1–512 belgi</td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td><span class="ic">nonce</span></td><td>1–255 belgi, ID token ichida qaytadi</td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td><span class="ic">code_challenge</span>, <span class="ic">code_challenge_method</span></td><td>PKCE, <span class="ic">S256</span></td><td><span class="chip chip-req">majburiy</span></td></tr>
    <tr><td><span class="ic">prompt</span></td><td><span class="ic">none</span> (jim tekshiruv), <span class="ic">login</span> (qayta kirish), <span class="ic">consent</span> (rozilikni qayta so'rash), <span class="ic">select_account</span></td><td><span class="chip chip-opt">ixtiyoriy</span></td></tr>
    <tr><td><span class="ic">max_age</span></td><td>Soniyalarda; oxirgi kirish bundan eski bo'lsa, qayta SMS so'raladi</td><td><span class="chip chip-opt">ixtiyoriy</span></td></tr>
    <tr><td><span class="ic">login_hint</span></td><td>Telefon raqam (<span class="ic">998901234567</span>) — maydon oldindan to'ldiriladi</td><td><span class="chip chip-opt">ixtiyoriy</span></td></tr>
  </tbody>
</table>

<hr class="divider">
<h2 class="doc-h2" id="callback">2. Callback'ni qabul qilish</h2>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">Muvaffaqiyat</span></div>
  <pre>https://hamkor.uz/auth/kitobchi/callback?code=SplxlOBeZQQYbYS6WxSbIA...&amp;state=Q2xpZW50...&amp;iss={{ urlencode($kidIss) }}</pre>
</div>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">Xato yoki rad etish</span></div>
  <pre>https://hamkor.uz/auth/kitobchi/callback?error=access_denied&amp;error_description=...&amp;state=Q2xpZW50...&amp;iss=...</pre>
</div>
<ul class="checklist">
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong><span class="ic">state</span> sessiyadagi qiymatga teng</strong> bo'lmasa — so'rovni to'xtating. Tekshirgandan keyin sessiyadan o'chiring (bir martalik).</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong><span class="ic">iss</span> = <span class="ic">{{ $kidIss }}</span></strong> ekanini tekshiring (RFC 9207 — bir nechta provayder ulangan bo'lsa «mix-up» hujumidan himoya).</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Kodni darhol almashtiring</strong> — 5 daqiqa amal qiladi. Callback sahifasida tashqi skript/analitika bo'lmasin, kod URL'dan <span class="ic">Referer</span> orqali sizib chiqmasin.</div></li>
</ul>

<hr class="divider">
<h2 class="doc-h2" id="token">3. Kodni tokenlarga almashtirish</h2>
<p class="doc-p">So'rov <span class="ic">application/x-www-form-urlencoded</span> bo'lishi shart (JSON qabul qilinmaydi). Server ilovalar <span class="ic">client_secret_basic</span> (tavsiya) yoki <span class="ic">client_secret_post</span>; public ilovalar faqat <span class="ic">client_id</span> + PKCE.</p>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">bash</span><button class="copy-btn" type="button">Ko'chirish</button></div>
  <pre>curl -X POST '{{ $kidIss }}/token' \
  -u 'kid_xxxxxxxxxxxxxxxxxxxxxxxx:kid_cs_xxxxxxxx' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'grant_type=authorization_code' \
  --data-urlencode 'code=SplxlOBeZQQYbYS6WxSbIA...' \
  --data-urlencode 'redirect_uri=https://hamkor.uz/auth/kitobchi/callback' \
  --data-urlencode 'code_verifier=dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk'</pre>
</div>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">JSON — 200 OK (Cache-Control: no-store)</span></div>
  <pre>{
  "access_token": "eyJ0eXAiOiJhdCtqd3QiLCJhbGciOiJSUzI1NiIsImtpZCI6Ii4uLiJ9...",
  "token_type": "Bearer",
  "expires_in": 600,
  "scope": "openid profile phone offline_access",
  "id_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6Ii4uLiJ9...",
  "refresh_token": "kid_rt_Wm9yQmlyb3JSYW5kb21Ub2tlbi4uLg"
}</pre>
</div>
<p class="doc-p"><span class="ic">refresh_token</span> faqat <span class="ic">offline_access</span> so'ralganda (va ilovaga ruxsat berilganda) qaytadi.</p>

<hr class="divider">
<h2 class="doc-h2" id="id-token">4. ID tokenni tekshirish</h2>
<p class="doc-p">ID token — RS256 bilan imzolangan JWT. Uni tayyor kutubxona bilan tekshiring; quyidagilarning <strong>barchasi</strong> bajarilishi shart:</p>
<ol class="doc-p">
  <li>Imzo — JWKS'dagi <span class="ic">kid</span> mos kalit bilan; <span class="ic">alg</span> faqat <span class="ic">RS256</span> (<span class="ic">none</span>/HS256 ni rad eting).</li>
  <li><span class="ic">iss</span> = <span class="ic">{{ $kidIss }}</span></li>
  <li><span class="ic">aud</span> = sizning <span class="ic">client_id</span>, <span class="ic">azp</span> ham shunga teng.</li>
  <li><span class="ic">exp</span> kelajakda (soat farqi uchun ≤ 60 s leeway), <span class="ic">iat</span> juda eski emas.</li>
  <li><span class="ic">nonce</span> sessiyadagi qiymatga teng.</li>
  <li>Kerak bo'lsa <span class="ic">auth_time</span> ni <span class="ic">max_age</span> bilan solishtiring.</li>
</ol>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">ID token payload — misol</span></div>
  <pre>{
  "iss": "{{ $kidIss }}",
  "sub": "f3Qx0rH9K1c4b7mZpLw2aVd8Yt6NsE5uRj0oGiBkCqA",
  "aud": "kid_xxxxxxxxxxxxxxxxxxxxxxxx",
  "azp": "kid_xxxxxxxxxxxxxxxxxxxxxxxx",
  "iat": 1792526400,
  "exp": 1792530000,
  "auth_time": 1792526380,
  "nonce": "Tm9uY2VSYW5kb21WYWx1ZTEyMzQ1Njc4OTA",
  "at_hash": "77QmUPtjPfzWtF2AnpK9RQ",
  "amr": ["otp"],
  "name": "Aziz Karimov",
  "given_name": "Aziz",
  "family_name": "Karimov",
  "picture": "https://kitobchi.com/storage/avatar/....jpg",
  "phone_number": "+998901234567",
  "phone_number_verified": true
}</pre>
</div>
<div class="note"><strong>Access tokenni o'qimang.</strong> Access token ham JWT, lekin u siz uchun «shaffof emas» deb hisoblang — undagi claim'lar ogohlantirishsiz o'zgarishi mumkin. Foydalanuvchi ma'lumotini ID tokendan yoki <span class="ic">/userinfo</span> dan oling.</div>

<hr class="divider">
<h2 class="doc-h2" id="userinfo">5. UserInfo</h2>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">bash</span><button class="copy-btn" type="button">Ko'chirish</button></div>
  <pre>curl '{{ $kidIss }}/userinfo' -H 'Authorization: Bearer eyJ0eXAiOiJhdCtqd3Qi...'</pre>
</div>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">JSON — 200 OK</span></div>
  <pre>{
  "sub": "f3Qx0rH9K1c4b7mZpLw2aVd8Yt6NsE5uRj0oGiBkCqA",
  "name": "Aziz Karimov",
  "phone_number": "+998901234567",
  "phone_number_verified": true
}</pre>
</div>
<p class="doc-p">Javobdagi <span class="ic">sub</span> ID tokendagi bilan bir xil bo'lishi shart — farq qilsa, javobni rad eting. Token yaroqsiz bo'lsa <span class="ic">401</span> va <span class="ic">WWW-Authenticate: Bearer error="invalid_token"</span>.</p>

<hr class="divider">
<h2 class="doc-h2" id="refresh">6. Refresh token (aylanuvchi)</h2>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">bash</span><button class="copy-btn" type="button">Ko'chirish</button></div>
  <pre>curl -X POST '{{ $kidIss }}/token' \
  -u 'kid_xxxxxxxxxxxxxxxxxxxxxxxx:kid_cs_xxxxxxxx' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'grant_type=refresh_token' \
  --data-urlencode 'refresh_token=kid_rt_Wm9yQmlyb3JSYW5kb21Ub2tlbi4uLg'</pre>
</div>
<ul class="checklist">
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Har safar yangi refresh token qaytadi</strong> — eskisini darhol yangisi bilan almashtiring (atomik). Eski token ikkinchi marta yuborilsa, bu o'g'irlik belgisi deb hisoblanadi va <strong>shu sessiyadagi barcha tokenlar bekor qilinadi</strong>; foydalanuvchi qayta kirishi kerak bo'ladi.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Muddat</strong>: oxirgi foydalanishdan 30 kun (sirpanuvchi), birinchi kirishdan maksimal 90 kun. Keyin qayta kirish talab qilinadi.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Parallel so'rovlar</strong>: bir nechta oqim bir vaqtda refresh qilmasin — bitta mutex/lock ostida bajaring.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Scope'ni toraytirish</strong> mumkin (<span class="ic">scope=openid</span>), kengaytirish mumkin emas (<span class="ic">invalid_scope</span>).</div></li>
</ul>

<hr class="divider">
<h2 class="doc-h2" id="logout">7. Chiqish va bekor qilish</h2>
<p class="doc-p">Foydalanuvchi ilovangizdan chiqsa, refresh tokenni bekor qiling (javob doim 200):</p>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">bash</span><button class="copy-btn" type="button">Ko'chirish</button></div>
  <pre>curl -X POST '{{ $kidIss }}/revoke' \
  -u 'kid_xxxxxxxxxxxxxxxxxxxxxxxx:kid_cs_xxxxxxxx' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'token=kid_rt_Wm9yQmlyb3JSYW5kb21Ub2tlbi4uLg' \
  --data-urlencode 'token_type_hint=refresh_token'</pre>
</div>
<p class="doc-p">Kitobchi ID sessiyasini ham yopish kerak bo'lsa (umumiy kompyuterlar), brauzerni yo'naltiring. <span class="ic">post_logout_redirect_uri</span> faqat ro'yxatdan o'tgan va faqat imzosi to'g'ri <span class="ic">id_token_hint</span> bilan ishlaydi:</p>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">HTTP</span></div>
  <pre>GET {{ $kidIss }}/logout?id_token_hint=eyJ...&amp;post_logout_redirect_uri=https%3A%2F%2Fhamkor.uz%2F&amp;state=xyz</pre>
</div>

<hr class="divider">
<h2 class="doc-h2" id="examples">Kod namunalari</h2>
<h3 class="doc-h3">Node.js (openid-client v6)</h3>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">JavaScript</span><button class="copy-btn" type="button">Ko'chirish</button></div>
  <pre>import * as client from 'openid-client'

const config = await client.discovery(
  new URL('{{ $kidIss }}'),
  process.env.KITOBCHI_CLIENT_ID,
  process.env.KITOBCHI_CLIENT_SECRET,
)

// /login
const code_verifier = client.randomPKCECodeVerifier()
const code_challenge = await client.calculatePKCECodeChallenge(code_verifier)
const state = client.randomState()
const nonce = client.randomNonce()
req.session.kid = { code_verifier, state, nonce }
res.redirect(client.buildAuthorizationUrl(config, {
  redirect_uri: 'https://hamkor.uz/auth/kitobchi/callback',
  scope: 'openid profile phone',
  code_challenge, code_challenge_method: 'S256', state, nonce,
}).href)

// /auth/kitobchi/callback
const { code_verifier, state, nonce } = req.session.kid
delete req.session.kid
const tokens = await client.authorizationCodeGrant(config, currentUrl, {
  pkceCodeVerifier: code_verifier, expectedState: state, expectedNonce: nonce,
})                      // iss, state, imzo, aud, exp, nonce — kutubxona tekshiradi
const claims = tokens.claims()   // claims.sub — foydalanuvchi kaliti</pre>
</div>

<h3 class="doc-h3">PHP (Laravel, firebase/php-jwt)</h3>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">PHP</span><button class="copy-btn" type="button">Ko'chirish</button></div>
  <pre>use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

$iss = '{{ $kidIss }}';
abort_unless(hash_equals(session()->pull('kid_state', ''), (string) $request->query('state')), 403);
abort_unless($request->query('iss') === $iss, 403);

$tokens = Http::asForm()
    ->withBasicAuth(config('services.kitobchi.client_id'), config('services.kitobchi.secret'))
    ->post("$iss/token", [
        'grant_type'    => 'authorization_code',
        'code'          => $request->query('code'),
        'redirect_uri'  => route('kitobchi.callback'),
        'code_verifier' => session()->pull('kid_verifier'),
    ])->throw()->json();

$jwks = Cache::remember('kid_jwks', 900, fn () => Http::get("$iss/jwks")->throw()->json());
JWT::$leeway = 60;
$claims = (array) JWT::decode($tokens['id_token'], JWK::parseKeySet($jwks, 'RS256'));

abort_unless($claims['iss'] === $iss && $claims['aud'] === config('services.kitobchi.client_id'), 403);
abort_unless(hash_equals(session()->pull('kid_nonce', ''), $claims['nonce'] ?? ''), 403);

$user = User::firstOrCreate(['kitobchi_sub' => $claims['sub']], [/* ... */]);</pre>
</div>

<h3 class="doc-h3">Mobil ilovalar (Android / iOS / Flutter)</h3>
<p class="doc-p"><strong>AppAuth</strong> kutubxonasidan foydalaning (Flutter: <span class="ic">flutter_appauth</span>). U discovery, PKCE va tizim brauzerini o'zi boshqaradi. Mobil ilova — <strong>public client</strong>: ilovaga <span class="ic">client_secret</span> joylashtirmang. WebView ichida kirish sahifasini ochish taqiqlanadi (foydalanuvchi manzil satrini ko'rmaydi, fishingga yo'l ochiladi).</p>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">Dart — flutter_appauth</span><button class="copy-btn" type="button">Ko'chirish</button></div>
  <pre>final result = await const FlutterAppAuth().authorizeAndExchangeCode(
  AuthorizationTokenRequest(
    'kid_xxxxxxxxxxxxxxxxxxxxxxxx',
    'uz.hamkor.app:/oauth',
    issuer: '{{ $kidIss }}',
    scopes: ['openid', 'profile', 'phone', 'offline_access'],
  ),
);
// result.idToken, result.accessToken, result.refreshToken
// refresh tokenni flutter_secure_storage'da saqlang</pre>
</div>
