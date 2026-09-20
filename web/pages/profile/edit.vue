<template>
  <div class="py-3 md:py-6 min-h-dvh bg-[#f0f2f5] grow">
    <!-- ====== MOBILE STICKY TOP BAR ====== -->
    <div class="md:hidden py-3 rounded-b-2xl mb-4 bg-white sticky top-0 z-40 transition-all duration-300 shadow-xs">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="grid grid-cols-5 items-center gap-2">
          <div class="col-span-1">
            <button
              type="button"
              @click="$router.back()"
              class="font-medium inline-flex items-center text-base gap-2 text-primary p-2 rounded-full bg-secondary-100 hover:bg-primary/10 transition-colors border-none cursor-pointer"
              aria-label="Orqaga"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
          </div>
          <div class="col-span-3">
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Tahrirlash</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 mb-6 max-md:hidden">
        <NuxtLink to="/profile/info" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-400 shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-600 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <NuxtLink to="/profile" class="hover:text-neutral-600 transition-colors">Profil</NuxtLink>
          <span class="text-gray-300">/</span>
          <NuxtLink to="/profile/info" class="hover:text-neutral-600 transition-colors">Ma'lumotlarim</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Tahrirlash</span>
        </nav>
      </div>

      <!-- Auth Gate -->
      <div v-if="!authStore.isAuthenticated" class="text-center py-20 bg-white rounded-3xl p-8 shadow-xs">
        <h2 class="text-2xl font-bold text-neutral-800 mb-2">Tizimga kiring</h2>
        <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">Shaxsiy ma'lumotlaringizni tahrirlash uchun tizimga kiring.</p>
        <button
          type="button"
          @click="authStore.openAuthModal()"
          class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm border-none cursor-pointer shadow-md hover:bg-primary/90 transition-colors"
        >
          Kirish
        </button>
      </div>

      <div v-else class="lg:flex lg:items-start lg:gap-5">
        <ProfileSidebar active="info" />

        <div class="flex-1 min-w-0 max-w-2xl">
          <div class="bg-white rounded-[20px] p-5 sm:p-7 shadow-xs">
            <h2 class="text-xl font-bold text-neutral-900 mb-5 max-md:hidden">Profilni tahrirlash</h2>

            <form @submit.prevent="handleSave" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Ism</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="w-full rounded-2xl bg-secondary-50 px-4 py-3.5 border border-neutral-100 outline-none focus:border-primary focus:bg-white transition-all font-medium text-neutral-900 text-sm"
                  placeholder="Ismingiz"
                  required
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Familiya</label>
                <input
                  v-model="form.lastname"
                  type="text"
                  class="w-full rounded-2xl bg-secondary-50 px-4 py-3.5 border border-neutral-100 outline-none focus:border-primary focus:bg-white transition-all font-medium text-neutral-900 text-sm"
                  placeholder="Familiyangiz"
                />
              </div>

              <!-- MUHIM: piyola'dagi kabi tab-tugma (Erkak/Ayol) — oldin
                   <select> dropdown edi. -->
              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Jins</label>
                <div class="inline-flex bg-secondary-50 rounded-2xl p-1 border border-neutral-100 w-full">
                  <button
                    v-for="opt in SEX_TOGGLE_OPTIONS"
                    :key="opt.value"
                    type="button"
                    @click="form.sex = opt.value"
                    :class="[
                      'flex-1 text-sm font-medium py-2.5 rounded-xl transition-colors border-none cursor-pointer',
                      form.sex === opt.value ? 'bg-primary text-white shadow-xs' : 'bg-transparent text-neutral-500 hover:text-neutral-800'
                    ]"
                  >
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- MUHIM: piyola'dagi "Ma'lumotlarim" formasi bilan
                   funksional parallellik uchun qo'shildi — Tug'ilgan sana
                   va Elektron pochta oldin bu formada UMUMAN yo'q edi. -->
              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Tug'ilgan sana</label>
                <input
                  v-model="form.birthdate"
                  type="date"
                  :max="todayIso"
                  class="w-full rounded-2xl bg-secondary-50 px-4 py-3.5 border border-neutral-100 outline-none focus:border-primary focus:bg-white transition-all font-medium text-neutral-900 text-sm"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Telefon raqam</label>
                <input
                  :value="'+' + (authStore.user?.phone_number || '')"
                  disabled
                  type="text"
                  class="w-full rounded-2xl bg-neutral-100 px-4 py-3.5 border border-neutral-200/60 text-neutral-400 font-medium cursor-not-allowed text-sm"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Elektron pochta</label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="Elektron pochta"
                  class="w-full rounded-2xl bg-secondary-50 px-4 py-3.5 border border-neutral-100 outline-none focus:border-primary focus:bg-white transition-all font-medium text-neutral-900 text-sm"
                />
              </div>

              <div v-if="saveError" class="p-3 rounded-xl bg-red-50 text-red-600 text-xs font-medium">
                {{ saveError }}
              </div>

              <div class="pt-3 flex flex-col sm:flex-row gap-3">
                <button
                  type="submit"
                  :disabled="saving"
                  class="w-full sm:flex-1 py-3.5 rounded-2xl bg-primary text-white font-semibold text-base border-none cursor-pointer hover:bg-primary/90 transition-colors shadow-md disabled:opacity-75"
                >
                  {{ saving ? 'Saqlanmoqda...' : 'Saqlash' }}
                </button>
                <button
                  type="button"
                  @click="$router.push('/profile/info')"
                  :disabled="saving"
                  class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-secondary-100 text-neutral-700 font-semibold text-base border-none cursor-pointer hover:bg-secondary-200 transition-colors"
                >
                  Bekor qilish
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const config = useRuntimeConfig()
const router = useRouter()

const userAny = computed(() => authStore.user as any)

const SEX_OPTIONS = [
  { value: '', label: "Ko'rsatilmagan" },
  { value: 'erkak', label: 'Erkak' },
  { value: 'ayol', label: 'Ayol' },
]
const SEX_TOGGLE_OPTIONS = SEX_OPTIONS.filter(o => o.value)
const todayIso = computed(() => new Date().toISOString().slice(0, 10))

const form = reactive({
  name: '',
  lastname: '',
  sex: '',
  birthdate: '',
  email: '',
})

const saving = ref(false)
const saveError = ref('')

onMounted(() => {
  if (authStore.isAuthenticated) {
    form.name = authStore.user?.name || ''
    form.lastname = userAny.value?.lastname || ''
    form.sex = userAny.value?.sex || ''
    form.birthdate = userAny.value?.birthdate ? String(userAny.value.birthdate).slice(0, 10) : ''
    form.email = userAny.value?.email || ''
  }
})

async function handleSave() {
  saving.value = true
  saveError.value = ''
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/settings`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        name: form.name,
        lastname: form.lastname,
        sex: form.sex,
        birthdate: form.birthdate || '',
        email: form.email || '',
      }
    })
    authStore.updateUser({
      name: form.name,
      lastname: form.lastname,
      sex: form.sex,
      birthdate: form.birthdate || null,
      email: form.email || null,
    })
    router.push('/profile/info')
  } catch (e: any) {
    saveError.value = e?.data?.message || "Saqlashda xatolik yuz berdi"
  } finally {
    saving.value = false
  }
}

useSeoMeta({
  title: "Profilni tahrirlash — Kitobchi",
  description: "Foydalanuvchi ma'lumotlarini tahrirlash sahifasi."
})
</script>
