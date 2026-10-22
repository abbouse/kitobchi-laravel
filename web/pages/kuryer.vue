<template>
  <main class="courier max-md:pb-[71px]">
    <!-- HERO -->
    <section class="courier-hero relative overflow-hidden">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto relative z-10 pt-8 pb-14 md:pt-16 md:pb-24">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
          <div>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/80 border border-[#BFEBD6] px-3 py-1.5 text-xs font-semibold text-[#138A55]">
              <img src="/images/apps/express.png" alt="" class="w-4 h-4 rounded-[4px]" />
              Kitobchi Express
            </span>
            <h1 class="mt-5 text-[2rem] leading-[1.1] sm:text-5xl lg:text-[3.4rem] font-extrabold tracking-tight text-neutral-900">
              Kitob yetkazing — <span class="text-accent">o'zingizga qulay vaqtda</span>
            </h1>
            <p class="mt-4 text-base sm:text-lg text-neutral-600 leading-relaxed max-w-xl">
              Kitobchi kuryeri bo'ling: do'konlardan kitob va kanselyariya buyurtmalarini olib, mijozlarga yetkazing.
              Har bir buyurtma uchun daromad — ilovada shaffof hisob-kitob.
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
              <a href="#ariza" class="btn-accent">Kuryer bo'lish
                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
              </a>
              <a href="#talablar" class="btn-ghost">Talablar</a>
            </div>
            <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-neutral-600 list-none p-0">
              <li class="flex items-center gap-2"><span class="tick"></span>Erkin grafik</li>
              <li class="flex items-center gap-2"><span class="tick"></span>Yengil yuklar</li>
              <li class="flex items-center gap-2"><span class="tick"></span>Pul kartangizga</li>
            </ul>
          </div>

          <!-- Ilova ko'rinishi (Kitobchi Express uslubida) -->
          <div class="relative mx-auto w-full max-w-[400px]" aria-hidden="true">
            <img src="/images/3d/scooter.png" alt="" class="float-icon w-20 sm:w-28 -left-4 top-1/3 lg:-left-20" />
            <img src="/images/3d/moneybag.png" alt="" class="float-icon float-icon--slow w-16 sm:w-20 -right-3 -bottom-6 lg:-right-10" />
            <div class="mock">
              <div class="px-5 pt-5 pb-3 text-xl font-extrabold text-neutral-900">Mening buyurtmalarim</div>
              <div class="mx-4 mb-3 grid grid-cols-3 rounded-2xl bg-neutral-100 p-1 text-center text-[12px] font-semibold text-neutral-500">
                <span class="rounded-xl bg-white py-1.5 text-neutral-900 shadow-sm">Barchasi</span><span class="py-1.5">Do'konda</span><span class="py-1.5">Yo'lda</span>
              </div>
              <div v-for="o in mockOrders" :key="o.id" class="mock__order">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-neutral-900">Buyurtma #{{ o.id }}</span>
                  <span v-if="o.multi" class="pill">MULTI {{ o.multi }}</span>
                </div>
                <div class="route">
                  <div><b>{{ o.from }}</b><span>{{ o.fromAddr }}</span></div>
                  <div><b>{{ o.to }}</b><span>{{ o.toAddr }}</span></div>
                </div>
                <div class="flex items-center justify-between text-[13px] pt-2 border-t border-neutral-100">
                  <span class="font-bold text-[#138A55]">{{ o.status }}</span>
                  <span class="text-neutral-600">{{ o.km }} km</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- AFZALLIKLAR -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-14 md:py-20">
      <div class="max-w-2xl">
        <h2 class="section-title">Nega Kitobchi Express?</h2>
        <p class="section-sub">Kitoblar yengil, manzillar aniq, hisob-kitob shaffof.</p>
      </div>
      <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <article v-for="b in benefits" :key="b.title" class="benefit flex gap-4 sm:block">
          <img :src="`/images/3d/${b.icon}.png`" alt="" class="w-12 h-12 sm:w-14 sm:h-14 shrink-0" loading="lazy" />
          <div>
            <h3 class="sm:mt-4 text-base sm:text-lg font-bold text-neutral-900">{{ b.title }}</h3>
            <p class="mt-1 sm:mt-1.5 text-sm text-neutral-600 leading-relaxed">{{ b.text }}</p>
          </div>
        </article>
      </div>
    </section>

    <!-- TALABLAR VA BOSQICHLAR -->
    <section id="talablar" class="reqs scroll-mt-24">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-14 md:py-20 grid lg:grid-cols-2 gap-10">
        <div>
          <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white m-0">Nima kerak?</h2>
          <ul class="mt-6 space-y-3 list-none p-0">
            <li v-for="r in requirements" :key="r" class="req">
              <span class="req__tick"></span><span>{{ r }}</span>
            </li>
          </ul>
          <div class="mt-6 rounded-2xl bg-white/5 border border-white/10 p-4 flex gap-3 items-start">
            <img src="/images/3d/scooter.png" alt="" class="w-12 h-12 shrink-0" loading="lazy" />
            <p class="text-sm text-[#CEE3FB]/85 m-0 leading-relaxed">
              <b class="text-white">Transportingiz yo'qmi?</b> Kitobchi skuterni haftalik ijaraga berishi mumkin —
              shartlar shartnomada yozilgan. <a :href="scooterContractUrl" rel="nofollow" class="text-[#7BE0B3] font-semibold">Namunani ko'rish</a>
            </p>
          </div>
        </div>
        <div>
          <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white m-0">Qanday boshlanadi?</h2>
          <ol class="mt-6 space-y-3 list-none p-0">
            <li v-for="(s, i) in steps" :key="s.title" class="stepd">
              <span class="stepd__num">{{ i + 1 }}</span>
              <div>
                <div class="font-bold text-white">{{ s.title }}</div>
                <div class="text-sm text-[#CEE3FB]/80 mt-0.5">{{ s.text }}</div>
              </div>
            </li>
          </ol>
        </div>
      </div>
    </section>

    <!-- ARIZA -->
    <section id="ariza" class="apply scroll-mt-24">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-14 md:py-20">
        <div class="grid lg:grid-cols-2 gap-10 items-center">
          <div>
            <img src="/images/3d/truck.png" alt="" class="w-20 h-20" />
            <h2 class="section-title mt-4">Kuryer bo'lish uchun ariza</h2>
            <p class="section-sub">Uchta maydon — va tamom. Ariza ko'rib chiqilgach, Kitobchi Express ilovasiga kirib ishni boshlaysiz.</p>
            <div class="mt-6">
              <div class="text-sm font-semibold text-neutral-800 mb-2">Ilovani oldindan yuklab qo'ying</div>
              <div class="flex flex-wrap gap-2">
                <a :href="PARTNER_APP_LINKS.express.appStore" target="_blank" rel="noopener" class="store-btn">App Store</a>
                <a :href="PARTNER_APP_LINKS.express.googlePlay" target="_blank" rel="noopener" class="store-btn">Google Play</a>
              </div>
            </div>
          </div>

          <div class="form-card">
            <div v-if="done" class="text-center py-6">
              <img src="/images/3d/handshake.png" alt="" class="w-20 h-20 mx-auto" />
              <h3 class="mt-4 text-2xl font-extrabold text-neutral-900">{{ doneTitle }}</h3>
              <p class="mt-2 text-neutral-600">{{ doneText }}</p>
              <div class="mt-5 flex justify-center gap-2">
                <a :href="PARTNER_APP_LINKS.express.appStore" target="_blank" rel="noopener" class="store-btn">App Store</a>
                <a :href="PARTNER_APP_LINKS.express.googlePlay" target="_blank" rel="noopener" class="store-btn">Google Play</a>
              </div>
            </div>
            <form v-else novalidate class="space-y-3" @submit.prevent="submit">
              <h3 class="form-title">Ma'lumotlaringiz</h3>
              <label class="field"><span>Ism va familiya</span>
                <input v-model="form.name" autocomplete="name" maxlength="100" placeholder="Aziz Karimov" />
              </label>
              <label class="field"><span>Telefon</span>
                <input :value="form.phone" inputmode="tel" autocomplete="tel" @input="onPhone" />
              </label>
              <label class="field"><span>Viloyat</span>
                <select v-model="form.region">
                  <option value="" disabled>Tanlang</option>
                  <option v-for="r in UZ_REGIONS" :key="r" :value="r">{{ r }}</option>
                </select>
              </label>
              <label class="check pt-1">
                <input v-model="form.terms" type="checkbox" />
                <span>Ma'lumotlarim ariza ko'rib chiqilishi uchun ishlatilishiga roziman (<NuxtLink to="/privacy" class="text-accent">maxfiylik siyosati</NuxtLink>)</span>
              </label>
              <p v-if="error" class="rounded-xl bg-red-50 text-red-600 text-sm px-3 py-2 m-0">{{ error }}</p>
              <button type="submit" class="btn-accent w-full justify-center mt-2" :disabled="sending">
                {{ sending ? 'Yuborilmoqda…' : 'Ariza yuborish' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- SAVOLLAR -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-3xl mx-auto py-14 md:py-20">
      <h2 class="section-title text-center">Ko'p beriladigan savollar</h2>
      <div class="mt-8 space-y-2">
        <details v-for="(f, i) in faq" :key="i" class="faq" :open="i === 0">
          <summary>{{ f.q }}</summary>
          <p>{{ f.a }}</p>
        </details>
      </div>
    </section>
  </main>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const scooterContractUrl = `${config.public.apiBase}/v1/kitobchi/contracts/skuter-ijarasi/download`

const mockOrders = [
  { id: 508, from: 'Book.Uz', fromAddr: "Alisher Navoiy ko'chasi, 40", to: 'Toshkent Markaziy', toAddr: 'Olmazor tumani', status: "Do'konda", km: '6.4', multi: 0 },
  { id: 495, from: 'Book.Uz', fromAddr: "Beruniy ko'chasi, 48", to: 'Toshkent Markaziy', toAddr: 'Olmazor tumani', status: "Yo'lda", km: '8.1', multi: 2 },
]

const benefits = [
  { icon: 'clock', title: 'Erkin grafik', text: "Qachon ishlashni o'zingiz hal qilasiz — o'qish yoki asosiy ish bilan bemalol qo'shib olib boriladi." },
  { icon: 'moneybag', title: 'Har bir buyurtma uchun', text: "Har bir yetkazilgan buyurtma uchun to'lov va qo'shimcha bonuslar. Hammasi ilovada ko'rinib turadi." },
  { icon: 'card', title: 'Pulni kartaga yechish', text: "Ishlagan pulingizni ilovadan so'rov yuborib, kartangizga yechib olasiz." },
  { icon: 'package', title: 'Yengil va ixcham', text: "Kitob va kanselyariya — og'ir yuk emas. Bir yo'nalishdagi bir nechta buyurtmani birga olib ketish mumkin." },
  { icon: 'map', title: "O'z hududingizda", text: "Buyurtmalar o'zingiz tanlagan viloyatda — manzil va masofa oldindan ko'rinadi." },
  { icon: 'phone', title: 'Hammasi bitta ilovada', text: "Kitobchi Express: buyurtmalar, yo'nalish, daromad va to'lovlar tarixi." },
]

const requirements = [
  '18 yoshdan kattaligingiz va shaxsingizni tasdiqlovchi hujjat (pasport yoki ID-karta)',
  'Android yoki iPhone smartfon va mobil internet',
  'Transport vositasi — yoki Kitobchi skuter ijarasi',
  "Mas'uliyat va mijozlarga xushmuomalalik",
]

const steps = [
  { title: 'Ariza qoldiring', text: 'Ism, telefon va viloyatingizni yozing — 1 daqiqa.' },
  { title: "Tasdiqlash", text: "Administrator arizani ko'rib chiqib, siz bilan bog'lanadi." },
  { title: 'Ilovaga kiring', text: "Kitobchi Express ilovasiga telefon raqamingiz bilan kirasiz." },
  { title: 'Yetkazishni boshlang', text: 'Buyurtmalarni qabul qiling va daromad oling.' },
]

const faq = [
  { q: 'Qancha ishlab topish mumkin?', a: "Daromad yetkazilgan buyurtmalar soni va masofaga bog'liq. Har bir buyurtma narxi uni qabul qilishdan oldin ilovada ko'rinadi." },
  { q: "To'liq kun ishlashim shartmi?", a: "Yo'q. O'zingizga qulay vaqtda ishlaysiz — ilovani ochib, buyurtmalarni qabul qilasiz." },
  { q: "Transportim bo'lmasa-chi?", a: "Kitobchi skuterni haftalik ijaraga berishi mumkin. Shartlar skuter ijarasi shartnomasida yozilgan — ariza ko'rib chiqilganda menejer batafsil aytadi." },
  { q: 'Pulimni qanday olaman?', a: "Ilovadagi balansdan kartangizga yechib olish so'rovini yuborasiz." },
]

const form = reactive({ name: '', phone: '+998 ', region: '', terms: false })
const sending = ref(false)
const done = ref(false)
const doneTitle = ref('Ariza yuborildi!')
const doneText = ref('')
const error = ref('')

function onPhone(e: Event) {
  const el = e.target as HTMLInputElement
  form.phone = formatUzPhone(el.value)
  el.value = form.phone
}

async function submit() {
  error.value = ''
  if (form.name.trim().length < 3) return (error.value = 'Ism va familiyangizni kiriting')
  if (!isFullUzPhone(form.phone)) return (error.value = "Telefon raqamini to'liq kiriting")
  if (!form.region) return (error.value = 'Viloyatni tanlang')
  if (!form.terms) return (error.value = 'Shartlarga rozilik bildiring')

  sending.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/courier/contact-request`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Accept-Language': 'uz' },
      body: { phone_number: form.phone, region: form.region, name: form.name.trim() },
    })
    doneText.value = res?.message || "Administrator arizangizni ko'rib chiqib, siz bilan bog'lanadi."
    done.value = true
  } catch (e: any) {
    error.value = e?.data?.message || "Arizani yuborib bo'lmadi. Birozdan so'ng qayta urinib ko'ring."
  } finally {
    sending.value = false
  }
}

useSeoMeta({
  title: "Kuryer bo'lish — Kitobchi Express",
  description: "Kitobchi kuryeri bo'ling: erkin grafik, har bir buyurtma uchun to'lov, pulni kartaga yechish. Ariza 1 daqiqa.",
  ogTitle: "Kitobchi kuryeri bo'ling",
})
useHead({ link: [{ rel: 'canonical', href: 'https://kitobchi.com/kuryer' }] })
</script>

<style scoped>
.courier { --accent: #22B978; --accent-2: #13985E; background: #FAFDFB; }
.text-accent { color: var(--accent-2); }
.courier-hero { background: radial-gradient(1200px 500px at 85% -10%, #CDF3E1 0%, transparent 60%), linear-gradient(180deg, #ECFAF3 0%, #FAFDFB 100%); }
.tick { width: 18px; height: 18px; border-radius: 999px; background: var(--accent) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='white'%3E%3Cpath d='M8.1 13.6 4.8 10.3l1.1-1.1 2.2 2.2 5.9-5.9 1.1 1.1z'/%3E%3C/svg%3E") center/14px no-repeat; }
.btn-accent { display: inline-flex; align-items: center; gap: .5rem; padding: .8rem 1.3rem; border-radius: 14px; font-weight: 700; color: #fff; text-decoration: none; border: 0; cursor: pointer;
  background: linear-gradient(180deg, #34CC8A 0%, var(--accent-2) 100%); box-shadow: 0 8px 20px -8px rgba(19, 152, 94, .6); transition: transform .15s, box-shadow .15s; }
.btn-accent:hover { transform: translateY(-1px); box-shadow: 0 12px 24px -8px rgba(19, 152, 94, .7); }
.btn-accent:disabled { opacity: .6; cursor: wait; transform: none; }
.btn-ghost { display: inline-flex; align-items: center; padding: .8rem 1.3rem; border-radius: 14px; font-weight: 700; color: #1f2937; background: #fff; border: 1px solid #E5E7EB; text-decoration: none; }
.btn-ghost:hover { border-color: #A7E5C8; }

.float-icon { position: absolute; z-index: 2; filter: drop-shadow(0 12px 18px rgba(0,0,0,.15)); animation: bob 4s ease-in-out infinite; }
.float-icon--slow { animation-duration: 5.5s; animation-delay: -1s; }
@keyframes bob { 0%, 100% { transform: translateY(0) } 50% { transform: translateY(-10px) } }
@media (prefers-reduced-motion: reduce) { .float-icon { animation: none; } }

.mock { position: relative; border-radius: 32px; overflow: hidden; background: #F6F7F8; padding-bottom: .6rem; box-shadow: 0 30px 60px -24px rgba(19, 152, 94, .45), 0 0 0 8px rgba(255,255,255,.7); }
.mock > div:first-child { background: #fff; }
.mock__order { background: #fff; margin: .6rem .8rem 0; border-radius: 18px; padding: .9rem 1rem; }
.pill { font-size: 11px; font-weight: 800; padding: .2rem .55rem; border-radius: 999px; background: #E6F8EF; color: #138A55; border: 1px solid #BFEBD6; }
.route { position: relative; margin: .7rem 0 .6rem; padding-left: 1.3rem; display: grid; gap: .55rem; }
.route::before { content: ''; position: absolute; left: 5px; top: 7px; bottom: 22px; width: 2px; background: #D1D5DB; }
.route > div { position: relative; display: flex; flex-direction: column; font-size: 13px; }
.route > div::before { content: ''; position: absolute; left: -1.3rem; top: 3px; width: 12px; height: 12px; border-radius: 999px; background: #138A55; }
.route > div:last-child::before { background: #fff; border: 3px solid #138A55; }
.route b { color: #111827; }
.route span { color: #6B7280; font-size: 12px; }

.section-title { font-size: clamp(1.6rem, 3vw, 2.4rem); font-weight: 800; letter-spacing: -.02em; color: #111827; margin: 0; }
.section-sub { margin-top: .5rem; color: #6B7280; font-size: 1rem; }
.benefit { background: #fff; border: 1px solid #E7F2EC; border-radius: 22px; padding: 1.4rem; transition: transform .2s, box-shadow .2s; }
.benefit:hover { transform: translateY(-3px); box-shadow: 0 18px 30px -18px rgba(19, 152, 94, .35); }

.reqs { background: linear-gradient(180deg, #10263E 0%, #081523 100%); }
.req { display: flex; gap: .75rem; align-items: flex-start; color: #E0EFFF; padding: .9rem 1rem; border-radius: 16px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); }
.req__tick { width: 20px; height: 20px; flex-shrink: 0; border-radius: 999px; background: var(--accent) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='white'%3E%3Cpath d='M8.1 13.6 4.8 10.3l1.1-1.1 2.2 2.2 5.9-5.9 1.1 1.1z'/%3E%3C/svg%3E") center/15px no-repeat; }
.stepd { display: flex; gap: .9rem; align-items: flex-start; padding: .9rem 1rem; border-radius: 16px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); }
.stepd__num { width: 32px; height: 32px; flex-shrink: 0; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; color: #fff; background: linear-gradient(180deg, #34CC8A, var(--accent-2)); }

.apply { background: linear-gradient(180deg, #ECFAF3 0%, #FAFDFB 100%); }
.form-card { background: #fff; border-radius: 26px; padding: 1.4rem; box-shadow: 0 30px 60px -30px rgba(17, 24, 39, .25); border: 1px solid #E7F2EC; }
@media (min-width: 640px) { .form-card { padding: 2rem; } }
.form-title { font-size: 1.35rem; font-weight: 800; color: #111827; margin: 0 0 .5rem; }
.field { display: block; }
.field > span { display: block; font-size: .8rem; font-weight: 600; color: #4B5563; margin-bottom: .35rem; }
.field input, .field select { width: 100%; height: 48px; border-radius: 14px; border: 1px solid #E5E7EB; background: #FAFAFA; padding: 0 .9rem; font-size: 1rem; color: #111827; outline: none; }
.field input:focus, .field select:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 4px rgba(34, 185, 120, .15); }
.check { display: flex; gap: .6rem; align-items: flex-start; font-size: .9rem; color: #374151; cursor: pointer; }
.check input { margin-top: .2rem; width: 18px; height: 18px; accent-color: var(--accent); flex-shrink: 0; }
.store-btn { display: inline-flex; align-items: center; padding: .55rem 1rem; border-radius: 12px; background: #111827; color: #fff; font-size: .85rem; font-weight: 700; text-decoration: none; }
.store-btn:hover { background: #000; }

.faq { background: #fff; border: 1px solid #E7F2EC; border-radius: 16px; padding: 0 1.1rem; }
.faq summary { cursor: pointer; list-style: none; padding: 1rem 0; font-weight: 700; color: #111827; display: flex; justify-content: space-between; gap: 1rem; }
.faq summary::-webkit-details-marker { display: none; }
.faq summary::after { content: '+'; color: var(--accent-2); font-size: 1.3rem; line-height: 1; }
.faq[open] summary::after { content: '–'; }
.faq p { margin: 0 0 1rem; color: #4B5563; line-height: 1.6; }
</style>
