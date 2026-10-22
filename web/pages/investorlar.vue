<template>
  <main class="inv max-md:pb-[71px]">
    <section class="inv-hero">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-14 md:py-24 grid lg:grid-cols-[1.2fr_0.8fr] gap-10 items-center">
        <div>
          <span class="inline-flex rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-xs font-semibold text-[#A5CCF5]">Investorlar uchun</span>
          <h1 class="mt-5 text-[2rem] leading-[1.1] sm:text-5xl font-extrabold tracking-tight text-white">O'zbekistonda kitob savdosining raqamli infratuzilmasi</h1>
          <p class="mt-4 text-base sm:text-lg text-[#CEE3FB]/85 leading-relaxed max-w-2xl">
            Kitobchi — kitob va kanselyariya marketpleysi: xaridorlar ilovasi, do'konlar uchun Kitobchi Business,
            o'z kuryerlik xizmatimiz Kitobchi Express va saralash hablari bitta ekotizimda ishlaydi.
          </p>
          <div class="mt-7 flex flex-wrap gap-3">
            <a v-if="kitobchiEmail" :href="`mailto:${kitobchiEmail}?subject=Investitsiya`" class="btn-primary">Bog'lanish</a>
            <a :href="SITE_LINKS.telegram" target="_blank" rel="noopener" class="btn-dark">Telegram</a>
          </div>
        </div>
        <img src="/images/3d/chart.png" alt="" class="w-48 lg:w-64 mx-auto drop-shadow-2xl" />
      </div>
    </section>

    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto py-14 md:py-20">
      <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-neutral-900 m-0">Ekotizim</h2>
      <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article v-for="p in parts" :key="p.title" class="card">
          <img :src="`/images/3d/${p.icon}.png`" alt="" class="w-12 h-12" loading="lazy" />
          <h3 class="mt-3 font-bold text-neutral-900">{{ p.title }}</h3>
          <p class="mt-1 text-sm text-neutral-600 leading-relaxed">{{ p.text }}</p>
        </article>
      </div>
      <div class="mt-10 rounded-3xl bg-[#F0F6FD] border border-[#DCEAF9] p-6 sm:p-8 flex flex-col sm:flex-row gap-5 sm:items-center justify-between">
        <div>
          <div class="text-lg font-bold text-neutral-900">Investitsiya taqdimoti va ko'rsatkichlar</div>
          <p class="text-sm text-neutral-600 mt-1 m-0">Moliyaviy ko'rsatkichlar va taqdimotni so'rov bo'yicha yuboramiz.</p>
        </div>
        <a v-if="kitobchiEmail" :href="`mailto:${kitobchiEmail}?subject=Investitsiya%20taqdimoti`" class="btn-primary shrink-0">So'rov yuborish</a>
        <NuxtLink v-else to="/contacts" class="btn-primary shrink-0">Aloqa</NuxtLink>
      </div>
    </section>
  </main>
</template>

<script setup lang="ts">
const { kitobchiEmail } = useSiteSettings()

const parts = [
  { icon: 'books', title: 'Kitobchi', text: "Xaridorlar uchun ilova va sayt: kitoblar, kanselyariya, kitobxonlar klubi." },
  { icon: 'store', title: 'Kitobchi Business', text: "Do'konlar va mualliflar uchun savdo, buyurtma va hisobot ilovasi." },
  { icon: 'truck', title: 'Kitobchi Express', text: "O'z kuryerlik tarmog'imiz va saralash hablari." },
  { icon: 'laptop', title: 'Kitobchi ID va API', text: "Hamkorlar uchun yagona kirish va ochiq API." },
]

useSeoMeta({
  title: 'Investorlar uchun — Kitobchi',
  description: "Kitobchi ekotizimi: marketpleys, do'konlar ilovasi, kuryerlik xizmati va hamkorlar uchun API. Investitsiya bo'yicha bog'laning.",
})
useHead({ link: [{ rel: 'canonical', href: 'https://kitobchi.com/investorlar' }] })
</script>

<style scoped>
.inv-hero { background: radial-gradient(900px 400px at 90% 0%, rgba(41,128,221,.35) 0%, transparent 60%), linear-gradient(180deg, #10263E 0%, #081523 100%); }
.btn-primary { display: inline-flex; align-items: center; padding: .8rem 1.3rem; border-radius: 14px; font-weight: 700; color: #fff; text-decoration: none; background: linear-gradient(180deg, #65A4E7 0%, #2980DD 100%); }
.btn-dark { display: inline-flex; align-items: center; padding: .8rem 1.3rem; border-radius: 14px; font-weight: 700; color: #fff; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.18); text-decoration: none; }
.card { background: #fff; border: 1px solid #E8EEF5; border-radius: 20px; padding: 1.25rem; }
</style>
