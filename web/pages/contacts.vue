<template>
  <main class="min-h-screen bg-[#F8FAFC] pb-16 max-md:pb-[80px]">
    <!-- Breadcrumb -->
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto pt-4 md:pt-6">
      <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs text-neutral-400 mb-6 overflow-x-auto whitespace-nowrap py-1">
        <NuxtLink to="/" class="hover:text-primary transition-colors no-underline">Bosh sahifa</NuxtLink>
        <svg class="w-3.5 h-3.5 shrink-0 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        <span class="text-neutral-700 font-medium truncate">Aloqa</span>
      </nav>
    </div>

    <!-- Header Section -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto mb-8 sm:mb-10">
      <div class="max-w-3xl">
        <span class="text-xs font-bold uppercase tracking-wider text-primary mb-2 block">Mijozlarni qo‘llab-quvvatlash</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 tracking-tight leading-tight mb-3">
          Biz bilan bog‘laning
        </h1>
        <p class="text-sm sm:text-base text-neutral-600 leading-relaxed m-0">
          Savollaringiz, takliflaringiz yoki hamkorlik bo‘yicha murojaatlaringiz bormi? Biz har doim yordamga tayyormiz.
        </p>
      </div>
    </section>

    <!-- Main Grid: Contact Cards + Feedback Form -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto mb-12 sm:mb-16">
      <div class="grid lg:grid-cols-12 gap-8 items-start">
        <!-- Chap tomondagi Aloqa Kartalari (5 ustun) -->
        <div class="lg:col-span-5 space-y-4">
          <!-- Telefon Kartasi -->
          <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-2xs border border-neutral-100 flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-primary flex items-center justify-center shrink-0">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
              </svg>
            </div>
            <div>
              <span class="text-xs font-semibold text-neutral-400 uppercase tracking-wider block mb-1">Yagona aloqa markazi</span>
              <a :href="`tel:${resolvedPhoneClean}`" class="text-lg font-bold text-neutral-900 hover:text-primary transition-colors block no-underline mb-1">
                {{ resolvedPhone }}
              </a>
              <p class="text-xs text-neutral-500 m-0">Har kuni 09:00 dan 21:00 gacha qo‘ng‘iroqlarga javob beramiz</p>
            </div>
          </div>

          <!-- Telegram & Online Support -->
          <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-2xs border border-neutral-100 flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#2AABEE]/10 text-[#2AABEE] flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
            </div>
            <div>
              <span class="text-xs font-semibold text-neutral-400 uppercase tracking-wider block mb-1">Telegram qo‘llab-quvvatlash</span>
              <a :href="SITE_LINKS.telegram" target="_blank" rel="noopener" class="text-base font-bold text-neutral-900 hover:text-[#2AABEE] transition-colors block no-underline mb-1">
                @kitobchi_market
              </a>
              <p class="text-xs text-neutral-500 m-0">Telegram orqali tezkor savol-javob va buyurtma ma'lumotlari</p>
            </div>
          </div>

          <!-- Email Kartasi -->
          <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-2xs border border-neutral-100 flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
              </svg>
            </div>
            <div>
              <span class="text-xs font-semibold text-neutral-400 uppercase tracking-wider block mb-1">Elektron pochta</span>
              <a :href="`mailto:${resolvedEmail}`" class="text-base font-bold text-neutral-900 hover:text-primary transition-colors block no-underline mb-1">
                {{ resolvedEmail }}
              </a>
              <p class="text-xs text-neutral-500 m-0">Takliflar va rasmiy murojaatlar uchun</p>
            </div>
          </div>

          <!-- Manzil va Ish vaqti Kartasi -->
          <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-2xs border border-neutral-100 flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
              </svg>
            </div>
            <div>
              <span class="text-xs font-semibold text-neutral-400 uppercase tracking-wider block mb-1">Bosh ofis manzili</span>
              <p class="text-base font-bold text-neutral-900 m-0 mb-1">Toshkent shahri, O‘zbekiston</p>
              <p class="text-xs text-neutral-500 m-0">Onlayn marketpleys — yetkazib berish butun O‘zbekiston bo‘ylab amalga oshiriladi</p>
            </div>
          </div>
        </div>

        <!-- O'ng tomondagi Qayta Aloqa Formasi (7 ustun) -->
        <div class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-10 shadow-2xs border border-neutral-100">
          <div class="mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900 m-0 mb-1.5">Xabar yuborish</h2>
            <p class="text-xs sm:text-sm text-neutral-500 m-0">Formani to‘ldiring, mutaxassislarimiz qisqa fursatda siz bilan bog‘lanishadi.</p>
          </div>

          <div v-if="isSent" class="p-6 rounded-2xl bg-emerald-50 text-emerald-900 text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            </div>
            <h3 class="text-lg font-bold m-0">Xabaringiz qabul qilindi!</h3>
            <p class="text-xs sm:text-sm text-emerald-700 m-0">Murojaatingiz uchun tashakkur. Tez orada ko‘rsatilgan aloqa vositasi orqali sizga javob beramiz.</p>
            <button
              type="button"
              @click="resetForm"
              class="mt-2 px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs border-none cursor-pointer transition-colors"
            >
              Yangi xabar yozish
            </button>
          </div>

          <form v-else @submit.prevent="submitContactForm" class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <!-- Ism -->
              <div>
                <label for="contact-name" class="block text-xs font-semibold text-neutral-700 mb-1.5">Ismingiz *</label>
                <input
                  id="contact-name"
                  v-model="form.name"
                  type="text"
                  required
                  placeholder="Masalan: Azizbek"
                  class="w-full px-4 py-3 rounded-2xl bg-neutral-50 border border-neutral-200/80 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                />
              </div>

              <!-- Telefon -->
              <div>
                <label for="contact-phone" class="block text-xs font-semibold text-neutral-700 mb-1.5">Telefon raqamingiz *</label>
                <input
                  id="contact-phone"
                  v-model="form.phone"
                  type="tel"
                  required
                  placeholder="+998 90 123 45 67"
                  class="w-full px-4 py-3 rounded-2xl bg-neutral-50 border border-neutral-200/80 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                />
              </div>
            </div>

            <!-- Murojaat mavzusi -->
            <div>
              <label for="contact-subject" class="block text-xs font-semibold text-neutral-700 mb-1.5">Mavzu</label>
              <select
                id="contact-subject"
                v-model="form.subject"
                class="w-full px-4 py-3 rounded-2xl bg-neutral-50 border border-neutral-200/80 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all cursor-pointer"
              >
                <option value="buyurtma">Buyurtma va yetkazib berish bo‘yicha</option>
                <option value="tolov">To‘lov va hisob-kitoblar</option>
                <option value="hamkorlik">Nashriyot yoki do‘kon hamkorligi</option>
                <option value="taklif">Taklif va mulohazalar</option>
                <option value="boshqa">Boshqa masala</option>
              </select>
            </div>

            <!-- Xabar matni -->
            <div>
              <label for="contact-message" class="block text-xs font-semibold text-neutral-700 mb-1.5">Xabaringiz *</label>
              <textarea
                id="contact-message"
                v-model="form.message"
                required
                rows="4"
                placeholder="Savolingiz yoki fikringizni batafsil yozib qoldiring..."
                class="w-full px-4 py-3 rounded-2xl bg-neutral-50 border border-neutral-200/80 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-y"
              ></textarea>
            </div>

            <button
              type="submit"
              :disabled="isSubmitting"
              class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-primary hover:bg-primary-600 disabled:opacity-50 text-white font-bold text-sm transition-all border-none cursor-pointer shadow-sm hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2"
            >
              <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
              <span>{{ isSubmitting ? 'Yuborilmoqda...' : 'Xabarni yuborish' }}</span>
            </button>
          </form>
        </div>
      </div>
    </section>

    <!-- FAQ va Qo'shimcha havolalar -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <div class="rounded-3xl p-8 bg-white border border-neutral-100 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
          <h3 class="text-xl font-bold text-neutral-900 m-0 mb-1.5">Tez-tez beriladigan savollar bormi?</h3>
          <p class="text-xs sm:text-sm text-neutral-500 m-0">
            Yetkazib berish, to‘lov tartibi, buyurtmani bekor qilish yoki qaytarish bo‘yicha javoblarni FAQ bo‘limidan topishingiz mumkin.
          </p>
        </div>
        <NuxtLink
          to="/faq"
          class="shrink-0 px-6 py-3 rounded-2xl bg-neutral-100 hover:bg-neutral-200 text-neutral-800 font-bold text-sm transition-colors no-underline"
        >
          Savol-javoblarni ko‘rish
        </NuxtLink>
      </div>
    </section>
  </main>
</template>

<script setup lang="ts">
import { SITE_LINKS, useSiteSettings } from '~/composables/useSiteSettings'

const { kitobchiPhone, kitobchiEmail } = useSiteSettings()
const snackbar = useSnackbar()

const resolvedPhone = computed(() => kitobchiPhone.value || '+998 50 030 30 33')
const resolvedPhoneClean = computed(() => resolvedPhone.value.replace(/\s+/g, ''))
const resolvedEmail = computed(() => kitobchiEmail.value || 'info@kitobchi.com')

const form = reactive({
  name: '',
  phone: '',
  subject: 'buyurtma',
  message: ''
})

const isSubmitting = ref(false)
const isSent = ref(false)

function submitContactForm() {
  if (!form.name || !form.phone || !form.message) return
  isSubmitting.value = true

  setTimeout(() => {
    isSubmitting.value = false
    isSent.value = true
    snackbar.add({
      title: 'Muvaffaqiyatli yuborildi',
      description: 'Murojaatingiz uchun tashakkur! Tez orada siz bilan bog‘lanamiz.',
      color: 'success'
    })
  }, 600)
}

function resetForm() {
  form.name = ''
  form.phone = ''
  form.subject = 'buyurtma'
  form.message = ''
  isSent.value = false
}

useSeoMeta({
  title: 'Aloqa — Kitobchi mijozlar xizmati',
  description: 'Kitobchi bilan bog‘lanish: yagona aloqa markazi, telegram qo‘llab-quvvatlash, email va manzil.'
})

useHead({
  link: [{ rel: 'canonical', href: 'https://kitobchi.com/contacts' }],
})
</script>
