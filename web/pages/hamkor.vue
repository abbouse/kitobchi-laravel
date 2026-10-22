<template>
  <main class="partner max-md:pb-[71px]">
    <!-- HERO -->
    <section class="partner-hero relative overflow-hidden">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto relative z-10 pt-8 pb-14 md:pt-16 md:pb-24">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
          <div>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/80 border border-[#FFD9C2] px-3 py-1.5 text-xs font-semibold text-[#C2511A]">
              <img src="/images/apps/business.png" alt="" class="w-4 h-4 rounded-[4px]" />
              Kitobchi Business
            </span>
            <h1 class="mt-5 text-[2rem] leading-[1.1] sm:text-5xl lg:text-[3.4rem] font-extrabold tracking-tight text-neutral-900">
              Kitob do'koningizni <span class="text-accent">butun O'zbekistonga</span> oching
            </h1>
            <p class="mt-4 text-base sm:text-lg text-neutral-600 leading-relaxed max-w-xl">
              Kitob va kanselyariya do'konlari hamda mualliflar uchun marketpleys. Mahsulotlaringizni joylang —
              buyurtma, yetkazish va to'lovni biz o'z zimmamizga olamiz.
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
              <a href="#ariza" class="btn-accent">Ariza qoldirish
                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
              </a>
              <a href="#himoya" class="btn-ghost">Huquqiy himoya</a>
            </div>
            <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-neutral-600 list-none p-0">
              <li class="flex items-center gap-2"><span class="tick"></span>Ariza 2 daqiqa</li>
              <li class="flex items-center gap-2"><span class="tick"></span>Ulanish bepul</li>
              <li class="flex items-center gap-2"><span class="tick"></span>Yetkazish — bizdan</li>
            </ul>
          </div>

          <!-- Ilova ko'rinishi (Kitobchi Business uslubida) -->
          <div class="relative mx-auto w-full max-w-[400px]" aria-hidden="true">
            <img src="/images/3d/store.png" alt="" class="float-icon w-20 sm:w-24 -right-3 -top-10 lg:-right-12" />
            <img src="/images/3d/package.png" alt="" class="float-icon float-icon--slow w-16 sm:w-20 -left-3 -bottom-6 lg:-left-12" />
            <div class="mock">
              <div class="mock__head">
                <img src="/images/apps/business.png" alt="" class="w-11 h-11 rounded-full bg-white p-0.5" />
                <div>
                  <div class="text-[11px] text-white/80">Xush kelibsiz</div>
                  <div class="text-lg font-extrabold text-white leading-tight">Do'koningiz</div>
                </div>
              </div>
              <div class="mock__body">
                <div class="flex items-center gap-2 mb-3">
                  <span class="dot"></span><span class="font-bold text-neutral-900">So'nggi buyurtmalar</span>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                  <div class="mock__order">
                    <div class="text-[12px] font-semibold text-neutral-800">Buyurtma #524</div>
                    <div class="mt-1.5 font-extrabold text-neutral-900">57 000 UZS</div>
                    <span class="pill pill--violet mt-2">Yo'lda</span>
                  </div>
                  <div class="mock__order">
                    <div class="text-[12px] font-semibold text-neutral-800">Buyurtma #523</div>
                    <div class="mt-1.5 font-extrabold text-neutral-900">336 000 UZS</div>
                    <span class="pill pill--green mt-2">Yetkazildi</span>
                  </div>
                </div>
                <div class="mock__toast">
                  <img src="/images/3d/bell.png" alt="" class="w-8 h-8" />
                  <div class="min-w-0">
                    <div class="text-[13px] font-bold text-neutral-900">Yangi buyurtma!</div>
                    <div class="text-[12px] text-neutral-500 truncate">«Ozor berma» · 78 000 UZS</div>
                  </div>
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
        <h2 class="section-title">Nega Kitobchi?</h2>
        <p class="section-sub">Siz sotishga e'tibor qaratasiz — qolganini platforma bajaradi.</p>
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

    <!-- HUQUQIY HIMOYA -->
    <section id="himoya" class="protect scroll-mt-24">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-14 md:py-20">
        <div class="grid lg:grid-cols-[0.9fr_1.1fr] gap-10 lg:gap-14 items-start">
          <div class="lg:sticky lg:top-28">
            <img src="/images/3d/shield.png" alt="" class="w-20 h-20" loading="lazy" />
            <h2 class="mt-5 text-3xl sm:text-4xl font-extrabold tracking-tight text-white">Huquqiy himoya</h2>
            <p class="mt-3 text-[#CEE3FB]/85 text-base leading-relaxed">
              Taqiqqa tushgan kitoblar, moderatsiya va shartnoma — bularning hammasini biz bilan oson boshqarasiz.
              Ro'yxatlarni kuzatib, javonlarni qo'lda tozalab o'tirishingiz shart emas.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
              <a :href="contractUrl" class="btn-accent" rel="nofollow">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0 4-4m-4 4-4-4M4 20h16" /></svg>
                Shartnoma namunasi (.docx)
              </a>
              <NuxtLink to="/legal" class="btn-dark">Barcha hujjatlar</NuxtLink>
            </div>
          </div>
          <div class="grid gap-3">
            <article v-for="p in protections" :key="p.title" class="protect-card">
              <img :src="`/images/3d/${p.icon}.png`" alt="" class="w-12 h-12 shrink-0" loading="lazy" />
              <div>
                <h3 class="text-base sm:text-lg font-bold text-white">{{ p.title }}</h3>
                <p class="mt-1 text-sm text-[#CEE3FB]/80 leading-relaxed">{{ p.text }}</p>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <!-- QANDAY ISHLAYDI -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-14 md:py-20">
      <h2 class="section-title">Qanday boshlanadi?</h2>
      <ol class="mt-8 grid gap-3 sm:gap-4 grid-cols-2 md:grid-cols-4 list-none p-0">
        <li v-for="(s, i) in steps" :key="s.title" class="step">
          <span class="step__num">{{ i + 1 }}</span>
          <h3 class="mt-3 font-bold text-neutral-900">{{ s.title }}</h3>
          <p class="mt-1 text-sm text-neutral-600 leading-relaxed">{{ s.text }}</p>
        </li>
      </ol>
    </section>

    <!-- ARIZA -->
    <section id="ariza" class="apply scroll-mt-24">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-14 md:py-20">
        <div class="grid lg:grid-cols-[0.85fr_1.15fr] gap-10 items-start">
          <div>
            <h2 class="section-title">Hamkorlik arizasi</h2>
            <p class="section-sub">Ariza 2 daqiqa oladi. Menejer 1–2 ish kunida siz bilan bog'lanadi.</p>
            <div class="mt-6 rounded-2xl bg-white border border-[#FFE3D1] p-5">
              <div class="flex items-center gap-3">
                <img src="/images/3d/doc.png" alt="" class="w-10 h-10" />
                <div class="font-bold text-neutral-900">Shartnoma tuzishda kerak bo'ladi</div>
              </div>
              <ul class="mt-3 space-y-1.5 text-sm text-neutral-600 list-none p-0">
                <li><b class="text-neutral-800">Do'konlar:</b> guvohnoma (YaTT/MChJ), STIR, bank rekvizitlari</li>
                <li><b class="text-neutral-800">Mualliflar:</b> pasport yoki ID-karta, JShShIR, bank kartasi yoki hisob raqami</li>
              </ul>
              <p class="mt-3 text-xs text-neutral-500">Hozir hech narsa yuklash shart emas.</p>
            </div>
            <div class="mt-6">
              <div class="text-sm font-semibold text-neutral-800 mb-2">Do'konni ilovadan boshqarasiz</div>
              <div class="flex flex-wrap gap-2">
                <a :href="PARTNER_APP_LINKS.business.appStore" target="_blank" rel="noopener" class="store-btn">App Store</a>
                <a :href="PARTNER_APP_LINKS.business.googlePlay" target="_blank" rel="noopener" class="store-btn">Google Play</a>
              </div>
            </div>
          </div>

          <div class="form-card">
            <!-- Muvaffaqiyat -->
            <div v-if="done" class="text-center py-6">
              <img src="/images/3d/handshake.png" alt="" class="w-20 h-20 mx-auto" />
              <h3 class="mt-4 text-2xl font-extrabold text-neutral-900">Ariza qabul qilindi!</h3>
              <p class="mt-2 text-neutral-600">Menejer 1–2 ish kunida <b>{{ form.phone }}</b> raqamiga qo'ng'iroq qiladi.
                Shu orada Kitobchi Business ilovasini yuklab qo'ying.</p>
              <div class="mt-5 flex justify-center gap-2">
                <a :href="PARTNER_APP_LINKS.business.appStore" target="_blank" rel="noopener" class="store-btn">App Store</a>
                <a :href="PARTNER_APP_LINKS.business.googlePlay" target="_blank" rel="noopener" class="store-btn">Google Play</a>
              </div>
            </div>

            <form v-else novalidate @submit.prevent="onNext">
              <!-- Qadamlar -->
              <div class="flex items-center gap-1.5 mb-5" aria-hidden="true">
                <span v-for="i in 4" :key="i" class="h-1.5 flex-1 rounded-full transition-colors" :class="i - 1 <= step ? 'bg-[#FF7A32]' : 'bg-neutral-200'"></span>
              </div>
              <div class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">{{ step + 1 }} / 4</div>

              <!-- 0: Rol -->
              <div v-if="step === 0">
                <h3 class="form-title">Kim sifatida qo'shilasiz?</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                  <button v-for="r in roles" :key="r.key" type="button" class="role" :class="{ 'role--on': form.role === r.key }" @click="form.role = r.key">
                    <img :src="`/images/3d/${r.icon}.png`" alt="" class="w-11 h-11" />
                    <span class="font-bold text-neutral-900">{{ r.title }}</span>
                    <span class="text-sm text-neutral-500">{{ r.text }}</span>
                  </button>
                </div>
              </div>

              <!-- 1: Aloqa -->
              <div v-else-if="step === 1" class="space-y-3">
                <h3 class="form-title">Aloqa ma'lumotlari</h3>
                <label class="field"><span>Ism va familiya</span>
                  <input v-model="form.contactName" autocomplete="name" maxlength="100" placeholder="Aziz Karimov" />
                </label>
                <label class="field"><span>Telefon</span>
                  <input :value="form.phone" inputmode="tel" autocomplete="tel" @input="onPhone" />
                </label>
                <label class="field"><span>{{ isAuthor ? 'Kitobdagi ism yoki taxallus' : "Do'kon nomi" }}</span>
                  <input v-model="form.displayName" maxlength="55" :placeholder="isAuthor ? 'Masalan: Aziz Karim' : 'Masalan: Ziyo kitoblari'" />
                </label>
                <label class="field"><span>Viloyat</span>
                  <select v-model="form.region">
                    <option value="" disabled>Tanlang</option>
                    <option v-for="r in UZ_REGIONS" :key="r" :value="r">{{ r }}</option>
                  </select>
                </label>
              </div>

              <!-- 2: Do'kon / kitoblar haqida -->
              <div v-else-if="step === 2" class="space-y-4">
                <h3 class="form-title">{{ isAuthor ? 'Kitoblaringiz haqida' : "Do'kon haqida" }}</h3>
                <template v-if="!isAuthor">
                  <ChoiceGroup label="Nimalarni sotasiz?" :options="{ Kitob: 'Kitoblar', Kanstovar: 'Kanselyariya' }" :multi="true" v-model="form.activities" />
                  <ChoiceGroup label="Huquqiy shakl" optional :options="{ entrepreneur: 'YaTT', llc: 'MChJ', individual: 'Hali rasmiylashtirilmagan' }" v-model="form.legalType" />
                  <ChoiceGroup label="Nechta turdagi mahsulot bor?" optional :options="{ lt_100: '100 tagacha', '100_1000': '100–1000', gt_1000: '1000 dan ko\'p' }" v-model="form.assortment" />
                  <ChoiceGroup label="Offline do'koningiz bormi?" optional :options="{ yes: 'Ha', no: 'Yo\'q' }" v-model="form.hasStore" />
                </template>
                <template v-else>
                  <ChoiceGroup label="Kitoblaringiz qaysi bosqichda?" :options="{ published: 'Nashr etilgan', upcoming: 'Bosmaxonada', manuscript: 'Qo\'lyozma' }" v-model="form.booksStatus" />
                  <ChoiceGroup label="Nechta kitobingiz bor?" :options="{ '1': '1 ta', '2_5': '2–5 ta', '6_plus': '6 va ko\'p' }" v-model="form.titlesCount" />
                  <ChoiceGroup label="Kim nashr qiladi?" :options="{ self: 'O\'zim', publisher: 'Nashriyot' }" v-model="form.publishing" />
                  <label v-if="form.publishing === 'publisher'" class="field"><span>Nashriyot nomi <i>(ixtiyoriy)</i></span>
                    <input v-model="form.publisherName" maxlength="120" />
                  </label>
                  <ChoiceGroup label="Janrlar (5 tagacha)" optional :multi="true" :max="5" :options="genres" v-model="form.genres" />
                </template>
                <label class="field"><span>Sayt yoki Telegram kanal <i>(ixtiyoriy)</i></span>
                  <input v-model="form.link" maxlength="255" placeholder="t.me/..." />
                </label>
              </div>

              <!-- 3: Tekshirish -->
              <div v-else class="space-y-4">
                <h3 class="form-title">Arizani tekshiring</h3>
                <dl class="summary">
                  <div v-for="row in summary" :key="row.label" @click="step = row.step">
                    <dt>{{ row.label }}</dt><dd>{{ row.value }}</dd>
                  </div>
                </dl>
                <label v-if="isAuthor" class="check">
                  <input v-model="form.rightsConfirmed" type="checkbox" />
                  <span>Kitoblarimning mualliflik huquqi menga tegishli yoki ularni sotishga ruxsatim bor</span>
                </label>
                <label class="check">
                  <input v-model="form.termsAccepted" type="checkbox" />
                  <span>Ma'lumotlarim ariza ko'rib chiqilishi uchun ishlatilishiga roziman (<NuxtLink to="/privacy" class="text-accent">maxfiylik siyosati</NuxtLink>)</span>
                </label>
              </div>

              <p v-if="error" class="mt-4 rounded-xl bg-red-50 text-red-600 text-sm px-3 py-2">{{ error }}</p>

              <div class="mt-6 flex items-center gap-3">
                <button v-if="step > 0" type="button" class="btn-ghost" @click="back">Orqaga</button>
                <button type="submit" class="btn-accent flex-1 justify-center" :disabled="sending">
                  {{ step < 3 ? 'Davom etish' : (sending ? 'Yuborilmoqda…' : 'Ariza yuborish') }}
                </button>
              </div>
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
import { defineComponent, h } from 'vue'

const config = useRuntimeConfig()
const contractUrl = `${config.public.apiBase}/v1/kitobchi/contracts/dokon-shartnomasi/download`

/** Bir yoki bir nechta variantni tanlash uchun chiplar. */
const ChoiceGroup = defineComponent({
  props: {
    label: String,
    options: { type: Object as () => Record<string, string>, required: true },
    modelValue: { type: [String, Array, null] as any, default: null },
    multi: Boolean,
    optional: Boolean,
    max: { type: Number, default: 0 },
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const isOn = (k: string) => (props.multi ? (props.modelValue as string[] || []).includes(k) : props.modelValue === k)
    const toggle = (k: string) => {
      if (!props.multi) return emit('update:modelValue', props.modelValue === k ? null : k)
      const cur = [...((props.modelValue as string[]) || [])]
      const i = cur.indexOf(k)
      if (i >= 0) cur.splice(i, 1)
      else if (!props.max || cur.length < props.max) cur.push(k)
      emit('update:modelValue', cur)
    }
    return () => h('fieldset', { class: 'choice' }, [
      h('legend', {}, [props.label, props.optional ? h('i', {}, ' (ixtiyoriy)') : null]),
      h('div', { class: 'flex flex-wrap gap-2' }, Object.entries(props.options).map(([k, v]) =>
        h('button', { type: 'button', class: ['chip', isOn(k) && 'chip--on'], 'aria-pressed': isOn(k), onClick: () => toggle(k) }, v))),
    ])
  },
})

const benefits = [
  { icon: 'store', title: 'Tez ulanish', text: "Kitoblarni umumiy katalogdagi tayyor kartochkaga biriktirasiz — rasm va tavsifni noldan yozish shart emas." },
  { icon: 'truck', title: 'Yetkazish — bizdan', text: "Kitobchi kuryerlari buyurtmani do'koningizdan olib, mijozga O'zbekiston bo'ylab yetkazadi." },
  { icon: 'moneybag', title: "Pul hisobingizga", text: "Sotuvdan tushgan mablag'ni ilovadan yechib olasiz — shartnomadagi muddatlarda." },
  { icon: 'barchart', title: 'Aniq hisobotlar', text: "Sotuv, buyurtmalar va qoldiqlar bo'yicha hisobotlar har doim qo'l ostingizda." },
  { icon: 'phone', title: 'Hammasi bitta ilovada', text: "Buyurtmalar, mahsulotlar, xodimlar va to'lovlar — Kitobchi Business ilovasida." },
  { icon: 'books', title: 'Mualliflar uchun ham', text: "O'z kitobingizni nashriyotsiz ham sotishingiz mumkin — kitobxonlar sizni Kitobchida topadi." },
]

const protections = [
  { icon: 'prohibited', title: "Taqiqqa tushgan kitob — bir zumda hamma joydan", text: "Kitob rasmiy ravishda taqiqlansa, biz uni markaziy katalogdan olib tashlaymiz: barcha do'konlardagi takliflari sotuvdan olinadi, savat va sevimlilardan chiqariladi, qidiruvda ko'rinmaydi. Siz hech narsa qilishingiz shart emas." },
  { icon: 'clock', title: 'Taqiq bekor qilinsa — avtomatik qaytadi', text: "Kitob qayta ruxsat etilsa, sizning taklifingiz ham sotuvga qaytariladi — mahsulotni qaytadan qo'shish kerak emas." },
  { icon: 'robot', title: 'Har bir mahsulot tekshiruvdan o\'tadi', text: "Yangi mahsulotlar AI va moderator tekshiruvidan o'tadi: taqiqlangan kontent va noto'g'ri ma'lumot sotuvga chiqmasdan ushlanadi." },
  { icon: 'scale', title: 'Rasmiy komissiya shartnomasi', text: "Majburiyatlar, to'lov muddatlari va javobgarlik oldindan yozilgan. Namuna ochiq — imzolashdan oldin bemalol o'qib chiqing." },
]

const steps = [
  { title: 'Ariza', text: "Quyidagi formani to'ldiring — 2 daqiqa." },
  { title: "Qo'ng'iroq", text: "Menejer 1–2 ish kunida bog'lanib, savollaringizga javob beradi." },
  { title: 'Shartnoma', text: 'Hujjatlarni tekshiramiz va shartnoma imzolaymiz.' },
  { title: 'Sotuv', text: "Mahsulotlarni joylaysiz — birinchi buyurtmalar kelishni boshlaydi." },
]

const roles = [
  { key: 'seller', icon: 'store', title: "Do'kon", text: 'Kitob va kanselyariya sotaman' },
  { key: 'author', icon: 'books', title: 'Muallif', text: "O'z kitoblarimni sotaman" },
]

const genres = {
  fiction: 'Badiiy', children: 'Bolalar', nonfiction: 'Ilmiy-ommabop', business: 'Biznes', self_help: 'Shaxsiy rivojlanish',
  religion: 'Diniy', education: "O'quv", poetry: "She'riyat", other: 'Boshqa',
}

const faq = [
  { q: 'Ulanish pullikmi?', a: "Yo'q, ariza va ulanish bepul. Kitobchi faqat sotilgan mahsulotdan shartnomada belgilangan komissiyani oladi." },
  { q: "Offline do'konim yo'q bo'lsa-chi?", a: "Muammo emas: onlayn sotuvchilar, YaTT, MChJ va o'zini o'zi band qilganlar bilan ham ishlaymiz." },
  { q: 'Taqiqlangan kitoblarni qanday bilaman?', a: "Bilishingiz shart emas — taqiqqa tushgan kitob katalogdan avtomatik olib tashlanadi va sizning taklifingiz ham sotuvdan olinadi." },
  { q: 'Buyurtmani qanday topshiraman?', a: "Ilovada yangi buyurtma haqida xabar olasiz, mahsulotni tayyorlab qo'yasiz — kuryer o'zi olib ketadi." },
  { q: 'Pulimni qachon olaman?', a: "Buyurtma yetkazilgach, mablag' balansingizga tushadi va uni ilovadan yechib olasiz. Aniq muddat shartnomada ko'rsatiladi." },
]

// ── Forma ──────────────────────────────────────────────
const step = ref(0)
const sending = ref(false)
const done = ref(false)
const error = ref('')
const form = reactive({
  role: 'seller' as 'seller' | 'author',
  contactName: '', phone: '+998 ', displayName: '', region: '',
  activities: [] as string[], legalType: null as string | null, assortment: null as string | null, hasStore: null as string | null,
  booksStatus: null as string | null, titlesCount: null as string | null, publishing: null as string | null, publisherName: '', genres: [] as string[],
  link: '', rightsConfirmed: false, termsAccepted: false,
})
const isAuthor = computed(() => form.role === 'author')

function onPhone(e: Event) {
  const el = e.target as HTMLInputElement
  form.phone = formatUzPhone(el.value)
  el.value = form.phone
}

const labelOf = (map: Record<string, string>, k: string | null) => (k ? map[k] : '—')
const summary = computed(() => [
  { label: 'Kim', value: isAuthor.value ? 'Muallif' : "Do'kon", step: 0 },
  { label: 'Ism', value: form.contactName, step: 1 },
  { label: 'Telefon', value: form.phone, step: 1 },
  { label: isAuthor.value ? 'Taxallus' : "Do'kon", value: form.displayName, step: 1 },
  { label: 'Viloyat', value: form.region, step: 1 },
  isAuthor.value
    ? { label: 'Kitoblar', value: `${labelOf({ published: 'nashr etilgan', upcoming: 'bosmaxonada', manuscript: "qo'lyozma" }, form.booksStatus)} · ${labelOf({ '1': '1 ta', '2_5': '2–5 ta', '6_plus': "6 va ko'p" }, form.titlesCount)}`, step: 2 }
    : { label: 'Sotadi', value: form.activities.map((a) => (a === 'Kitob' ? 'Kitoblar' : 'Kanselyariya')).join(', '), step: 2 },
])

function stepError(s: number): string {
  if (s === 1) {
    if (form.contactName.trim().length < 3) return 'Ism va familiyangizni kiriting'
    if (!isFullUzPhone(form.phone)) return "Telefon raqamini to'liq kiriting"
    if (!form.displayName.trim()) return isAuthor.value ? 'Kitobda yoziladigan ismni kiriting' : "Do'kon nomini kiriting"
    if (!form.region) return 'Viloyatni tanlang'
  }
  if (s === 2) {
    if (isAuthor.value && (!form.booksStatus || !form.titlesCount || !form.publishing)) return 'Kitoblaringiz haqidagi savollarga javob bering'
    if (!isAuthor.value && !form.activities.length) return 'Nimalarni sotishingizni tanlang'
  }
  if (s === 3) {
    if (isAuthor.value && !form.rightsConfirmed) return 'Kitoblarni sotish huquqingizni tasdiqlang'
    if (!form.termsAccepted) return 'Shartlarga rozilik bildiring'
  }
  return ''
}

function back() {
  error.value = ''
  step.value = Math.max(0, step.value - 1)
}

async function onNext() {
  error.value = stepError(step.value)
  if (error.value) return
  if (step.value < 3) {
    step.value++
    return
  }
  sending.value = true
  try {
    const link = form.link.trim()
    const body: Record<string, any> = {
      business_role: form.role,
      contact_name: form.contactName.trim(),
      phone_number: form.phone,
      shop_name: form.displayName.trim(),
      region: form.region,
      terms_accepted: true,
      ...(link ? { link } : {}),
    }
    if (isAuthor.value) {
      Object.assign(body, {
        activity_types: ['Kitob'], books_status: form.booksStatus, titles_count: form.titlesCount, publishing: form.publishing,
        genres: form.genres, rights_confirmed: true,
        ...(form.publishing === 'publisher' && form.publisherName.trim() ? { publisher_name: form.publisherName.trim() } : {}),
      })
    } else {
      Object.assign(body, {
        activity_types: form.activities,
        ...(form.legalType ? { legal_type: form.legalType } : {}),
        ...(form.assortment ? { assortment: form.assortment } : {}),
        ...(form.hasStore ? { has_store: form.hasStore === 'yes' } : {}),
      })
    }
    await $fetch(`${config.public.apiBase}/v1/seller/contact`, { method: 'POST', body, headers: { Accept: 'application/json' } })
    done.value = true
  } catch (e: any) {
    error.value = e?.data?.message || "Arizani yuborib bo'lmadi. Birozdan so'ng qayta urinib ko'ring."
  } finally {
    sending.value = false
  }
}

useSeoMeta({
  title: "Hamkor bo'lish — Kitobchi",
  description: "Kitob va kanselyariya do'konlari hamda mualliflar uchun: Kitobchi marketpleysida soting. Yetkazish, to'lov va huquqiy himoya — bizdan.",
  ogTitle: "Kitobchi bilan hamkor bo'ling",
})
useHead({ link: [{ rel: 'canonical', href: 'https://kitobchi.com/hamkor' }] })
</script>

<style scoped>
.partner { --accent: #FF7A32; --accent-2: #FF5E1A; background: #FFFCFA; }
.text-accent { color: var(--accent); }
.partner-hero { background: radial-gradient(1200px 500px at 85% -10%, #FFE0CC 0%, transparent 60%), linear-gradient(180deg, #FFF3EA 0%, #FFFCFA 100%); }
.tick { width: 18px; height: 18px; border-radius: 999px; background: var(--accent) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='white'%3E%3Cpath d='M8.1 13.6 4.8 10.3l1.1-1.1 2.2 2.2 5.9-5.9 1.1 1.1z'/%3E%3C/svg%3E") center/14px no-repeat; }
.btn-accent { display: inline-flex; align-items: center; gap: .5rem; padding: .8rem 1.3rem; border-radius: 14px; font-weight: 700; color: #fff; text-decoration: none; border: 0; cursor: pointer;
  background: linear-gradient(180deg, #FF9A4D 0%, var(--accent-2) 100%); box-shadow: 0 8px 20px -8px rgba(255, 94, 26, .6); transition: transform .15s, box-shadow .15s; }
.btn-accent:hover { transform: translateY(-1px); box-shadow: 0 12px 24px -8px rgba(255, 94, 26, .7); }
.btn-accent:disabled { opacity: .6; cursor: wait; transform: none; }
.btn-ghost { display: inline-flex; align-items: center; padding: .8rem 1.3rem; border-radius: 14px; font-weight: 700; color: #1f2937; background: #fff; border: 1px solid #E5E7EB; text-decoration: none; cursor: pointer; }
.btn-ghost:hover { border-color: #FFC9A8; }
.btn-dark { display: inline-flex; align-items: center; padding: .8rem 1.3rem; border-radius: 14px; font-weight: 700; color: #fff; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.18); text-decoration: none; }
.btn-dark:hover { background: rgba(255,255,255,.14); }

.float-icon { position: absolute; z-index: 2; filter: drop-shadow(0 12px 18px rgba(0,0,0,.15)); animation: bob 4s ease-in-out infinite; }
.float-icon--slow { animation-duration: 5.5s; animation-delay: -1s; }
@keyframes bob { 0%, 100% { transform: translateY(0) } 50% { transform: translateY(-10px) } }
@media (prefers-reduced-motion: reduce) { .float-icon { animation: none; } }

.mock { position: relative; border-radius: 32px; overflow: hidden; background: #fff; box-shadow: 0 30px 60px -24px rgba(194, 81, 26, .45), 0 0 0 8px rgba(255,255,255,.7); }
.mock__head { display: flex; align-items: center; gap: .75rem; padding: 1.4rem 1.2rem 2.4rem; background: linear-gradient(180deg, #FF8A45 0%, #FFA266 100%); }
.mock__body { margin-top: -1.2rem; background: #fff; border-radius: 24px 24px 0 0; padding: 1.1rem; }
.mock__order { border: 1px solid #F1F1F1; border-radius: 16px; padding: .8rem; }
.mock__toast { margin-top: .9rem; display: flex; align-items: center; gap: .6rem; padding: .65rem .8rem; border-radius: 16px; background: #FFF6EF; border: 1px solid #FFE3D1; }
.dot { width: 9px; height: 9px; border-radius: 999px; background: var(--accent); }
.pill { display: inline-block; font-size: 11px; font-weight: 700; padding: .2rem .55rem; border-radius: 8px; }
.pill--violet { background: #EEEBFF; color: #5B47D6; }
.pill--green { background: #E6F8EF; color: #138A55; }

.section-title { font-size: clamp(1.6rem, 3vw, 2.4rem); font-weight: 800; letter-spacing: -.02em; color: #111827; margin: 0; }
.section-sub { margin-top: .5rem; color: #6B7280; font-size: 1rem; }
.benefit { background: #fff; border: 1px solid #F3EDE8; border-radius: 22px; padding: 1.4rem; transition: transform .2s, box-shadow .2s; }
.benefit:hover { transform: translateY(-3px); box-shadow: 0 18px 30px -18px rgba(194, 81, 26, .35); }

.protect { background: linear-gradient(180deg, #10263E 0%, #081523 100%); }
.protect-card { display: flex; gap: 1rem; align-items: flex-start; padding: 1.2rem; border-radius: 20px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); }

.step { position: relative; background: #fff; border: 1px solid #F3EDE8; border-radius: 20px; padding: 1.2rem; }
.step__num { display: inline-flex; width: 34px; height: 34px; align-items: center; justify-content: center; border-radius: 12px; font-weight: 800; color: #fff; background: linear-gradient(180deg, #FF9A4D, var(--accent-2)); }

.apply { background: linear-gradient(180deg, #FFF6EF 0%, #FFFCFA 100%); }
.form-card { background: #fff; border-radius: 26px; padding: 1.4rem; box-shadow: 0 30px 60px -30px rgba(17, 24, 39, .25); border: 1px solid #F3EDE8; }
@media (min-width: 640px) { .form-card { padding: 2rem; } }
.form-title { font-size: 1.35rem; font-weight: 800; color: #111827; margin: .35rem 0 1rem; }
.role { display: flex; flex-direction: column; align-items: flex-start; gap: .35rem; text-align: left; padding: 1rem; border-radius: 18px; border: 2px solid #F0F0F0; background: #fff; cursor: pointer; transition: border-color .15s, background .15s; }
.role--on { border-color: var(--accent); background: #FFF6EF; }
.field { display: block; }
.field > span { display: block; font-size: .8rem; font-weight: 600; color: #4B5563; margin-bottom: .35rem; }
.field i, :deep(.choice legend i) { font-style: normal; font-weight: 400; color: #9CA3AF; }
.field input, .field select { width: 100%; height: 48px; border-radius: 14px; border: 1px solid #E5E7EB; background: #FAFAFA; padding: 0 .9rem; font-size: 1rem; color: #111827; outline: none; }
.field input:focus, .field select:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 4px rgba(255, 122, 50, .15); }
:deep(.choice) { border: 0; padding: 0; margin: 0; }
:deep(.choice legend) { font-size: .9rem; font-weight: 700; color: #1F2937; margin-bottom: .5rem; }
:deep(.chip) { padding: .55rem .9rem; border-radius: 12px; border: 1px solid #E5E7EB; background: #fff; font-size: .9rem; font-weight: 600; color: #374151; cursor: pointer; transition: all .15s; }
:deep(.chip--on) { border-color: var(--accent); background: #FFF1E7; color: #C2511A; }
.summary { margin: 0; border: 1px solid #F0F0F0; border-radius: 16px; overflow: hidden; }
.summary > div { display: flex; justify-content: space-between; gap: 1rem; padding: .7rem .9rem; cursor: pointer; }
.summary > div + div { border-top: 1px solid #F3F4F6; }
.summary > div:hover { background: #FFF8F3; }
.summary dt { color: #6B7280; font-size: .9rem; }
.summary dd { margin: 0; font-weight: 600; color: #111827; text-align: right; font-size: .9rem; }
.check { display: flex; gap: .6rem; align-items: flex-start; font-size: .9rem; color: #374151; cursor: pointer; }
.check input { margin-top: .2rem; width: 18px; height: 18px; accent-color: var(--accent); flex-shrink: 0; }
.store-btn { display: inline-flex; align-items: center; padding: .55rem 1rem; border-radius: 12px; background: #111827; color: #fff; font-size: .85rem; font-weight: 700; text-decoration: none; }
.store-btn:hover { background: #000; }

.faq { background: #fff; border: 1px solid #F3EDE8; border-radius: 16px; padding: 0 1.1rem; }
.faq summary { cursor: pointer; list-style: none; padding: 1rem 0; font-weight: 700; color: #111827; display: flex; justify-content: space-between; gap: 1rem; }
.faq summary::-webkit-details-marker { display: none; }
.faq summary::after { content: '+'; color: var(--accent); font-size: 1.3rem; line-height: 1; }
.faq[open] summary::after { content: '–'; }
.faq p { margin: 0 0 1rem; color: #4B5563; line-height: 1.6; }
</style>
