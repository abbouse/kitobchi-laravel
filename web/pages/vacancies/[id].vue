<template>
  <main class="max-md:pb-[71px]">
    <div v-if="pending" class="py-20 text-center text-neutral-400">Yuklanmoqda...</div>

    <div v-else-if="!vacancy" class="py-20 text-center">
      <h1 class="text-lg font-bold text-neutral-800 mb-1">Vakansiya topilmadi</h1>
      <p class="text-sm text-neutral-500 mb-4">Ushbu vakansiya endi faol emas yoki mavjud emas.</p>
      <NuxtLink to="/vacancies" class="text-primary font-semibold hover:underline">Barcha vakansiyalar</NuxtLink>
    </div>

    <div v-else class="m-0">
      <!-- HERO HEADER (piyola 1:1 — vacancies-hero* qoidalari qayta ishlatildi) -->
      <header class="relative overflow-hidden vacancies-hero">
        <div class="vacancies-hero__grid"></div>
        <div class="vacancies-hero__orb vacancies-hero__orb--1"></div>
        <div class="vacancies-hero__orb vacancies-hero__orb--2"></div>
        <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto relative z-10 pt-4 md:pt-8 pb-14">
          <div class="max-w-6xl mx-auto">
            <NuxtLink to="/vacancies" class="inline-flex items-center gap-2 -ml-0.5 mb-4 md:mb-8 text-sm font-medium text-white/60 hover:text-white transition-colors duration-300">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
              Barcha vakansiyalar
            </NuxtLink>
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-4">
              <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-emerald-300">
                <span class="size-1.5 rounded-full bg-emerald-400"></span>
                Faol
              </span>
              <template v-if="vacancy.contract_type">
                <span class="text-white/30 text-sm">·</span>
                <span class="text-sm font-medium text-white/50">{{ vacancy.contract_type }}</span>
              </template>
            </div>
            <h1 class="max-w-3xl mb-4 md:mb-6 text-[1.375rem] sm:text-3xl md:text-4xl lg:text-[2.75rem] font-bold leading-snug text-white">
              {{ vacancy.title }}
            </h1>
            <div v-if="vacancy.location" class="flex flex-wrap gap-1.5 sm:gap-2">
              <div class="inline-flex max-w-full items-center gap-1.5 rounded-xl border border-white/15 bg-white/10 px-3.5 py-2 text-white backdrop-blur-sm">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                <span class="text-sm font-semibold">{{ vacancy.location }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="vacancies-hero__wave"></div>
      </header>

      <div class="relative pb-24 md:pb-24">
        <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto max-md:px-4">
          <div class="max-w-6xl mx-auto grid lg:grid-cols-[1fr_380px] gap-4 md:gap-6 lg:gap-10 items-start -mt-8 relative z-10">
            <!-- Lavozim haqida -->
            <section class="relative rounded-[14px] md:rounded-2xl border border-tima-200 bg-white px-4 py-5 shadow-sm md:px-8 md:py-9">
              <h2 class="mb-5 border-b border-gray-200 pb-4 text-lg font-bold text-primary">Lavozim haqida</h2>
              <div class="kb-prose" v-html="vacancy.description"></div>
            </section>

            <!-- Ariza topshirish -->
            <aside class="lg:sticky lg:top-24">
              <div class="rounded-[14px] md:rounded-2xl border border-tima-200 bg-white px-4 py-5 shadow-sm md:p-6">
                <div class="flex items-center gap-3.5">
                  <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-tima-50 text-primary">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126A59.77 59.77 0 0 1 21.485 12 59.77 59.77 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5" /></svg>
                  </div>
                  <div>
                    <h2 class="text-lg font-bold text-primary m-0">Ariza topshirish</h2>
                    <p class="text-xs text-neutral-400 mt-0.5 m-0">Jamoa a'zosi bo'lish uchun arizangizni yuboring</p>
                  </div>
                </div>

                <!-- Muvaffaqiyatli yuborildi -->
                <div v-if="submitState === 'success'" class="mt-6 rounded-xl bg-emerald-500/12 border border-emerald-500/25 px-4 py-5 text-center">
                  <div class="w-10 h-10 rounded-full bg-emerald-400 text-white mx-auto flex items-center justify-center mb-3">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                  </div>
                  <p class="text-sm font-semibold text-emerald-600">Arizangiz qabul qilindi. Tez orada aloqaga chiqamiz.</p>
                </div>

                <form v-else class="space-y-4 mt-6" @submit.prevent="submitApplication">
                  <div>
                    <label class="mb-2 block text-[13px] font-semibold text-[#4b4b5e]">Ism familiya</label>
                    <input
                      v-model="form.full_name"
                      type="text"
                      required
                      placeholder="Ismingizni kiriting"
                      class="w-full rounded-md border-0 px-3 py-2 text-sm text-neutral-900 bg-secondary-50 ring-1 ring-inset ring-secondary/25 outline-none transition-shadow"
                    />
                  </div>
                  <div>
                    <label class="mb-2 block text-[13px] font-semibold text-[#4b4b5e]">Email</label>
                    <input
                      v-model="form.email"
                      type="email"
                      required
                      placeholder="email@misol.com"
                      class="w-full rounded-md border-0 px-3 py-2 text-sm text-neutral-900 bg-secondary-50 ring-1 ring-inset ring-secondary/25 outline-none transition-shadow"
                    />
                  </div>
                  <div>
                    <label class="mb-2 block text-[13px] font-semibold text-[#4b4b5e]">Telegram foydalanuvchi nomi</label>
                    <div class="flex items-center gap-1.5 rounded-md bg-secondary-50 ring-1 ring-inset ring-secondary/25 px-3 py-2 transition-shadow">
                      <span class="text-neutral-400 text-sm shrink-0">@</span>
                      <input
                        v-model="form.telegram_username"
                        type="text"
                        required
                        placeholder="username"
                        class="flex-1 min-w-0 bg-transparent outline-none text-sm text-neutral-900 border-none p-0"
                      />
                    </div>
                  </div>
                  <div>
                    <label class="mb-2 block text-[13px] font-semibold text-[#4b4b5e]">Xabar (ixtiyoriy)</label>
                    <textarea
                      v-model="form.cover_message"
                      rows="3"
                      placeholder="O'zingiz haqingizda qisqacha..."
                      class="w-full rounded-md border-0 px-3 py-2 text-sm text-neutral-900 bg-secondary-50 ring-1 ring-inset ring-secondary/25 outline-none transition-shadow resize-none"
                    ></textarea>
                  </div>
                  <div>
                    <label class="mb-2 block text-[13px] font-semibold text-[#4b4b5e]">
                      Rezyume <span class="text-red-500">*</span>
                    </label>
                    <input ref="fileInput" type="file" accept=".pdf,.doc,.docx" class="hidden" @change="onFileChange" />
                    <button
                      type="button"
                      class="flex w-full items-center gap-3 rounded-xl border border-dashed border-tima-300 bg-secondary-50 px-4 py-3.5 text-gray-500 hover:border-primary/30 hover:bg-primary/10 hover:text-primary/75 transition-colors border-none cursor-pointer"
                      style="border-style: dashed; border-width: 1px;"
                      @click="fileInput?.click()"
                    >
                      <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                      <div class="text-left min-w-0">
                        <p class="text-sm font-semibold text-primary truncate m-0">{{ cvFile ? cvFile.name : 'Rezyume yuklash' }}</p>
                        <p class="text-xs text-neutral-400 mt-0.5 m-0">PDF, DOC yoki DOCX formatida (max 10MB)</p>
                      </div>
                    </button>
                  </div>

                  <p v-if="submitState === 'error'" class="text-sm text-red-700">{{ errorMessage }}</p>

                  <button
                    type="submit"
                    :disabled="submitState === 'loading'"
                    class="font-medium w-full text-white bg-primary hover:bg-primary/90 disabled:opacity-75 h-12 md:h-14 flex items-center justify-center rounded-2xl text-base transition-all border-none cursor-pointer mt-1"
                  >
                    {{ submitState === 'loading' ? 'Yuborilmoqda...' : 'Ariza topshirish' }}
                  </button>
                </form>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()

const { data, pending } = await useFetch<any>(`${config.public.apiBase}/v1/kitobchi/vacancies/${route.params.id}`, {
  lazy: false
})

const vacancy = computed(() => data.value?.status === 'success' ? data.value.data : null)

useSeoMeta({
  title: () => vacancy.value ? `${vacancy.value.title} — Vakansiyalar — Kitobchi` : 'Vakansiya — Kitobchi',
  description: () => vacancy.value?.description ? String(vacancy.value.description).replace(/<[^>]+>/g, '').slice(0, 160) : undefined
})

const form = reactive({
  full_name: '',
  email: '',
  telegram_username: '',
  cover_message: ''
})
const fileInput = ref<HTMLInputElement | null>(null)
const cvFile = ref<File | null>(null)
const submitState = ref<'idle' | 'loading' | 'success' | 'error'>('idle')
const errorMessage = ref('')

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  cvFile.value = target.files?.[0] || null
}

async function submitApplication() {
  if (!cvFile.value) {
    submitState.value = 'error'
    errorMessage.value = 'Iltimos, rezyume faylini yuklang.'
    return
  }
  submitState.value = 'loading'
  errorMessage.value = ''

  try {
    const fd = new FormData()
    fd.append('full_name', form.full_name)
    fd.append('email', form.email)
    fd.append('telegram_username', form.telegram_username)
    if (form.cover_message) fd.append('cover_message', form.cover_message)
    fd.append('cv', cvFile.value)

    await $fetch(`${config.public.apiBase}/v1/kitobchi/vacancies/${route.params.id}/apply`, {
      method: 'POST',
      body: fd
    })

    submitState.value = 'success'
  } catch (e: any) {
    submitState.value = 'error'
    errorMessage.value = e?.data?.message || "Xatolik yuz berdi. Iltimos, qaytadan urinib ko'ring."
  }
}
</script>
