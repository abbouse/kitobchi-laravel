@php($brand = url('/developers/brand/kitobchi-id'))
<h2 class="doc-h2" id="logo">Logotip</h2>
<p class="doc-p">Kitobchi ID logotipi — Kitobchi ilova belgisi (ko'k fonda oq «K-kapalak») va yonidagi <strong>ID</strong> yozuvi. Logotipni o'zgartirmang: ranglarini almashtirmang, cho'zmang, soya yoki effekt qo'shmang, «ID» yozuvini boshqa shrift bilan qayta termang.</p>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin:16px 0">
  <div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:24px;display:flex;align-items:center;justify-content:center"><img src="{{ $brand }}/kitobchi-id-logo.svg" alt="Kitobchi ID" style="height:44px"></div>
  <div style="background:#0B1B33;border-radius:12px;padding:24px;display:flex;align-items:center;justify-content:center"><img src="{{ $brand }}/kitobchi-id-logo-white.svg" alt="Kitobchi ID" style="height:44px"></div>
  <div style="background:#F3F6FA;border:1px solid var(--border);border-radius:12px;padding:24px;display:flex;align-items:center;justify-content:center;gap:16px"><img src="{{ $brand }}/kitobchi-id-icon.svg" alt="" style="height:44px"><img src="{{ $brand }}/kitobchi-id-icon.svg" alt="" style="height:28px"><img src="{{ $brand }}/kitobchi-id-icon.svg" alt="" style="height:20px"></div>
</div>
<table class="doc-table">
  <thead><tr><th>Fayl</th><th>Qachon ishlatiladi</th></tr></thead>
  <tbody>
    <tr><td><a href="{{ $brand }}/kitobchi-id-logo.svg" download style="color:var(--link)">kitobchi-id-logo.svg</a> · <a href="{{ $brand }}/kitobchi-id-logo.png" download style="color:var(--link)">PNG</a></td><td>Yorug' fon</td></tr>
    <tr><td><a href="{{ $brand }}/kitobchi-id-logo-white.svg" download style="color:var(--link)">kitobchi-id-logo-white.svg</a> · <a href="{{ $brand }}/kitobchi-id-logo-white.png" download style="color:var(--link)">PNG</a></td><td>To'q fon</td></tr>
    <tr><td><a href="{{ $brand }}/kitobchi-id-icon.svg" download style="color:var(--link)">kitobchi-id-icon.svg</a> · <a href="{{ $brand }}/kitobchi-id-icon-512.png" download style="color:var(--link)">PNG 512</a></td><td>Faqat belgi: tugma, ikonka, kichik joylar</td></tr>
    <tr><td><a href="{{ $brand }}/kitobchi-id-mono.svg" download style="color:var(--link)">kitobchi-id-mono.svg</a></td><td>Ko'k (brend rangli) tugma ichida — oq kvadrat, ko'k K</td></tr>
  </tbody>
</table>
<table class="doc-table">
  <thead><tr><th>Rang</th><th>HEX</th></tr></thead>
  <tbody>
    <tr><td>Kitobchi ko'k (asosiy)</td><td><span class="ic">#1E6FD4</span></td></tr>
    <tr><td>Gradient boshi</td><td><span class="ic">#62B0FF</span> → <span class="ic">#1E6FD4</span> (yuqori-chapdan pastki-o'ngga)</td></tr>
    <tr><td>Matn (yorug' fon)</td><td><span class="ic">#0B1B33</span></td></tr>
  </tbody>
</table>
<ul class="checklist">
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Bo'sh joy</strong> — logotip atrofida kamida belgi balandligining 25% qadar bo'sh joy qoldiring.</div></li>
  <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><strong>Eng kichik o'lcham</strong> — to'liq logotip 24 px balandlik, faqat belgi 16 px.</div></li>
</ul>

<hr class="divider">
<h2 class="doc-h2" id="button">Kirish tugmasi</h2>
<p class="doc-p">Tugma matni: <strong>«Kitobchi ID bilan kirish»</strong> (ru: «Войти с Kitobchi ID», en: «Sign in with Kitobchi ID»). Qisqa joylarda: «Kitobchi ID». Tugma boshqa ijtimoiy kirish tugmalari bilan bir xil o'lcham va ustuvorlikda bo'lsin; balandlik 40–52 px, burchak radiusi saytingiz uslubiga mos.</p>
<div style="display:flex;flex-wrap:wrap;gap:12px;margin:16px 0">
  <a href="#" onclick="return false" style="display:inline-flex;align-items:center;gap:10px;height:48px;padding:0 20px;border-radius:12px;background:#1E6FD4;color:#fff;font-weight:700;text-decoration:none;font-size:15px"><img src="{{ $brand }}/kitobchi-id-mono.svg" alt="" width="24" height="24">Kitobchi ID bilan kirish</a>
  <a href="#" onclick="return false" style="display:inline-flex;align-items:center;gap:10px;height:48px;padding:0 20px;border-radius:12px;background:#fff;color:#0B1B33;border:1px solid #D5DEEA;font-weight:700;text-decoration:none;font-size:15px"><img src="{{ $brand }}/kitobchi-id-icon.svg" alt="" width="24" height="24">Kitobchi ID bilan kirish</a>
  <a href="#" onclick="return false" aria-label="Kitobchi ID bilan kirish" style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:12px;background:#fff;border:1px solid #D5DEEA"><img src="{{ $brand }}/kitobchi-id-icon.svg" alt="" width="26" height="26"></a>
</div>
<div class="codeblock">
  <div class="codeblock-header"><span class="codeblock-title">HTML</span><button class="copy-btn" type="button">Ko'chirish</button></div>
  <pre>&lt;a href="/auth/kitobchi" class="kid-btn"&gt;
  &lt;img src="{{ $brand }}/kitobchi-id-mono.svg" alt="" width="24" height="24"&gt;
  Kitobchi ID bilan kirish
&lt;/a&gt;

&lt;style&gt;
.kid-btn{display:inline-flex;align-items:center;gap:10px;height:48px;padding:0 20px;
  border-radius:12px;background:#1E6FD4;color:#fff;font:700 15px/1 system-ui,sans-serif;
  text-decoration:none}
.kid-btn:hover{background:#1A63BF}
.kid-btn:focus-visible{outline:3px solid #62B0FF;outline-offset:2px}
&lt;/style&gt;</pre>
</div>
<div class="note"><strong>Muhim:</strong> tugma sizning serveringizdagi marshrutga (<span class="ic">/auth/kitobchi</span>) olib borsin — u yerda <span class="ic">state</span>, <span class="ic">nonce</span> va PKCE yaratiladi va foydalanuvchi Kitobchi ID'ga yo'naltiriladi. Logotip fayllarini o'z serveringizga ko'chirib qo'yish tavsiya etiladi.</div>
