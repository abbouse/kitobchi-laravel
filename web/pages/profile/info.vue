<template>
  <div class="py-3 md:py-6 min-h-dvh bg-secondary-300 md:bg-gray-50 grow">
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
            <h1 class="text-xl sm:text-2xl text-primary font-semibold text-center m-0">Ma'lumotlarim</h1>
          </div>
          <div class="col-span-1 flex justify-end"></div>
        </div>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumb (Desktop) -->
      <div class="flex items-center gap-2 mb-6 max-md:hidden">
        <NuxtLink to="/profile" class="rounded-full w-9 h-9 flex items-center justify-center transition-colors text-primary bg-secondary-200 hover:bg-secondary-400 shrink-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        </NuxtLink>
        <nav class="flex items-center gap-2 text-sm text-[#8F8FA1]">
          <NuxtLink to="/" class="hover:text-neutral-600 transition-colors">Asosiy</NuxtLink>
          <span class="text-gray-300">/</span>
          <NuxtLink to="/profile" class="hover:text-neutral-600 transition-colors">Profil</NuxtLink>
          <span class="text-gray-300">/</span>
          <span class="text-neutral-900 font-semibold">Ma'lumotlarim</span>
        </nav>
      </div>

      <!-- Auth Gate -->
      <div v-if="!authStore.isAuthenticated" class="text-center py-20 bg-white rounded-3xl p-8 shadow-xs">
        <h2 class="text-2xl font-bold text-neutral-800 mb-2">Tizimga kiring</h2>
        <p class="text-sm text-neutral-500 mb-6 max-w-sm mx-auto">Shaxsiy ma'lumotlaringizni ko'rish uchun tizimga kiring.</p>
        <button
          type="button"
          @click="authStore.openAuthModal()"
          class="inline-flex items-center px-8 py-3.5 rounded-2xl bg-primary text-white font-bold text-sm border-none cursor-pointer shadow-md hover:bg-primary/90 transition-colors"
        >
          Kirish
        </button>
      </div>

      <div v-else class="lg:flex lg:items-start lg:gap-5">
        <!-- Desktop Sidebar -->
        <ProfileSidebar active="info" />

        <div class="flex-1 min-w-0 space-y-4">
          <!-- Shaxsiy ma'lumotlar kartasi -->
          <div class="bg-white rounded-[20px] p-5 sm:p-6 shadow-xs border border-neutral-100/60">
            <div class="flex items-center justify-between mb-5">
              <h2 class="text-xl font-bold text-neutral-900 m-0">Ma'lumotlarim</h2>
              
              <!-- Mobile: Sahifaga o'tish (/profile/edit) -->
              <NuxtLink
                to="/profile/edit"
                class="md:hidden inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary/10 text-primary text-sm font-semibold transition-colors hover:bg-primary/20"
              >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5v4.875c0 .621-.504 1.125-1.125 1.125H5.625a1.125 1.125 0 01-1.125-1.125V6.375c0-.621.504-1.125 1.125-1.125h9.75"/></svg>
                Tahrirlash
              </NuxtLink>

              <!-- PC / Desktop: Modal oynasini ochish -->
              <button
                type="button"
                @click="openEditModal"
                class="hidden md:inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary/10 text-primary text-sm font-semibold border-none cursor-pointer hover:bg-primary/20 transition-colors"
              >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5v4.875c0 .621-.504 1.125-1.125 1.125H5.625a1.125 1.125 0 01-1.125-1.125V6.375c0-.621.504-1.125 1.125-1.125h9.75"/></svg>
                Tahrirlash
              </button>
            </div>

            <div class="grid grid-cols-2 gap-x-4 gap-y-5">
              <div class="flex flex-col gap-1">
                <span class="text-xs text-neutral-400 font-medium">Ism</span>
                <span class="text-sm font-semibold text-neutral-900 truncate">{{ authStore.user?.name || 'Kiritilmagan' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-xs text-neutral-400 font-medium">Familiya</span>
                <span class="text-sm font-semibold text-neutral-900 truncate">{{ userAny?.lastname || 'Kiritilmagan' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-xs text-neutral-400 font-medium">Jins</span>
                <span class="text-sm font-semibold text-neutral-900 truncate">{{ sexLabel }}</span>
              </div>
              <!-- MUHIM: piyola'dagi "Ma'lumotlarim" sahifasiga funksional
                   parallellik uchun qo'shildi — Tug'ilgan sana va Elektron
                   pochta maydonlari oldin bu sahifada UMUMAN ko'rsatilmas
                   edi (backend'da ham `birthdate` ustuni yo'q edi, endi
                   2026_08_18_130000 migratsiyasi bilan qo'shildi). -->
              <div class="flex flex-col gap-1">
                <span class="text-xs text-neutral-400 font-medium">Tug'ilgan sana</span>
                <span class="text-sm font-semibold text-neutral-900 truncate">{{ birthdateLabel }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-xs text-neutral-400 font-medium">Telefon raqam</span>
                <span class="text-sm font-semibold text-neutral-900 truncate">+{{ authStore.user?.phone_number || 'Kiritilmagan' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-xs text-neutral-400 font-medium">Elektron pochta</span>
                <span class="text-sm font-semibold text-neutral-900 truncate">{{ userAny?.email || 'Kiritilmagan' }}</span>
              </div>
            </div>
          </div>

          <!-- Saqlangan manzillar kartasi -->
          <div class="bg-white rounded-[20px] p-5 sm:p-6 shadow-xs border border-neutral-100/60">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-bold text-neutral-900 m-0">Saqlangan manzillar</h2>

              <!-- Mobile: Sahifaga o'tish (/profile/address/create) -->
              <NuxtLink
                to="/profile/address/create"
                class="md:hidden inline-flex items-center gap-1 text-sm font-semibold text-primary"
              >
                + Qo'shish
              </NuxtLink>

              <!-- PC / Desktop: Modal oynasini ochish -->
              <button
                type="button"
                @click="openAddressModal"
                class="hidden md:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-primary/10 text-primary text-xs font-semibold border-none cursor-pointer hover:bg-primary/20 transition-colors"
              >
                + Yangi manzil
              </button>
            </div>

            <div v-if="addressesLoading" class="space-y-3">
              <div class="shimmer h-16 w-full rounded-2xl"></div>
            </div>

            <template v-else>
              <div v-if="!addresses.length" class="rounded-2xl bg-secondary-50 p-6 flex flex-col gap-3 items-center text-center border border-neutral-100/60">
                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-neutral-400 shadow-xs">
                  <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                </div>
                <div>
                  <h3 class="text-base font-semibold text-neutral-800 m-0">Saqlangan manzillar mavjud emas</h3>
                  <p class="text-xs text-neutral-500 m-0 mt-1">Buyurtmalarni tez rasmiylashtirish uchun yetkazib berish manzilini qo'shing</p>
                </div>

                <!-- Mobile Button -->
                <NuxtLink
                  to="/profile/address/create"
                  class="md:hidden inline-flex items-center px-6 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm shadow-md hover:bg-primary/90 transition-colors"
                >
                  Yangi manzil qo'shish
                </NuxtLink>

                <!-- Desktop Button -->
                <button
                  type="button"
                  @click="openAddressModal"
                  class="hidden md:inline-flex items-center px-6 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm border-none cursor-pointer shadow-md hover:bg-primary/90 transition-colors"
                >
                  Yangi manzil qo'shish
                </button>
              </div>

              <div v-else class="space-y-2.5">
                <div
                  v-for="loc in addresses"
                  :key="loc.id"
                  class="bg-secondary-50 border border-neutral-100/80 rounded-2xl p-4 flex items-start justify-between gap-3 transition-colors hover:bg-secondary-100/50"
                >
                  <div class="flex items-start gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-primary shadow-xs shrink-0 mt-0.5">
                      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-semibold text-neutral-900 leading-snug m-0">{{ loc.fullAddress }}</p>
                      <div class="flex items-center gap-2 mt-1.5">
                        <span v-if="loc.id === mainAddressId" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-primary text-white">
                          Asosiy manzil
                        </span>
                        <button
                          v-else
                          type="button"
                          @click="makeMain(loc)"
                          class="text-xs text-neutral-500 hover:text-primary transition-colors border-none bg-transparent cursor-pointer p-0 underline"
                        >
                          Asosiy qilish
                        </button>
                      </div>
                    </div>
                  </div>

                  <button
                    type="button"
                    @click="removeAddress(loc)"
                    class="text-neutral-400 hover:text-red-500 hover:bg-white rounded-xl p-2 shrink-0 border-none bg-transparent cursor-pointer transition-colors"
                    aria-label="O'chirish"
                  >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!--  DESKTOP MODAL 1: PROFILNI TAHRIRLASH (PC Modal)                          -->
    <!-- ========================================================================= -->
    <div
      v-if="isEditModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="isEditModalOpen = false"
    >
      <div class="bg-white rounded-3xl w-full max-w-lg p-6 sm:p-7 shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-5">
          <h3 class="text-lg font-bold text-neutral-900 m-0">Profilni tahrirlash</h3>
          <button
            type="button"
            @click="isEditModalOpen = false"
            class="p-1.5 rounded-full hover:bg-secondary-100 text-neutral-400 border-none bg-transparent cursor-pointer transition-colors"
            aria-label="Yopish"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="handleSaveEditModal" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Ism</label>
            <input
              v-model="editForm.name"
              type="text"
              class="w-full rounded-2xl bg-secondary-50 px-4 py-3 border border-neutral-100 outline-none focus:border-primary focus:bg-white transition-all font-medium text-neutral-900 text-sm"
              placeholder="Ismingiz"
              required
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Familiya</label>
            <input
              v-model="editForm.lastname"
              type="text"
              class="w-full rounded-2xl bg-secondary-50 px-4 py-3 border border-neutral-100 outline-none focus:border-primary focus:bg-white transition-all font-medium text-neutral-900 text-sm"
              placeholder="Familiyangiz"
            />
          </div>

          <!-- MUHIM: piyola'dagi "Ma'lumotlarni tahrirlash" oynasida Jins
               tab-tugma (Erkak/Ayol) shaklida — oldin bu yerda <select>
               dropdown edi, jonli piyola desktop modali bilan solishtirilib
               moslashtirildi. -->
          <div>
            <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Jins</label>
            <div class="inline-flex bg-secondary-50 rounded-2xl p-1 border border-neutral-100 w-full">
              <button
                v-for="opt in SEX_TOGGLE_OPTIONS"
                :key="opt.value"
                type="button"
                @click="editForm.sex = opt.value"
                :class="[
                  'flex-1 text-sm font-medium py-2.5 rounded-xl transition-colors border-none cursor-pointer',
                  editForm.sex === opt.value ? 'bg-primary text-white shadow-xs' : 'bg-transparent text-neutral-500 hover:text-neutral-800'
                ]"
              >
                {{ opt.label }}
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Tug'ilgan sana</label>
              <input
                v-model="editForm.birthdate"
                type="date"
                :max="todayIso"
                class="w-full rounded-2xl bg-secondary-50 px-4 py-3 border border-neutral-100 outline-none focus:border-primary focus:bg-white transition-all font-medium text-neutral-900 text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Telefon raqam</label>
              <input
                :value="'+' + (authStore.user?.phone_number || '')"
                disabled
                type="text"
                class="w-full rounded-2xl bg-neutral-100 px-4 py-3 border border-neutral-200/60 text-neutral-400 font-medium cursor-not-allowed text-sm"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-neutral-600 mb-1.5">Elektron pochta</label>
            <input
              v-model="editForm.email"
              type="email"
              placeholder="Elektron pochta"
              class="w-full rounded-2xl bg-secondary-50 px-4 py-3 border border-neutral-100 outline-none focus:border-primary focus:bg-white transition-all font-medium text-neutral-900 text-sm"
            />
          </div>

          <div v-if="editModalError" class="p-3 rounded-xl bg-red-50 text-red-600 text-xs font-medium">
            {{ editModalError }}
          </div>

          <div class="flex items-center gap-3 pt-3">
            <button
              type="submit"
              :disabled="savingEdit"
              class="flex-1 py-3 rounded-2xl bg-primary text-white font-semibold text-sm border-none cursor-pointer hover:bg-primary/90 transition-colors shadow-md disabled:opacity-75"
            >
              {{ savingEdit ? 'Saqlanmoqda...' : 'Saqlash' }}
            </button>
            <button
              type="button"
              @click="isEditModalOpen = false"
              :disabled="savingEdit"
              class="px-6 py-3 rounded-2xl bg-secondary-100 text-neutral-700 font-semibold text-sm border-none cursor-pointer hover:bg-secondary-200 transition-colors"
            >
              Bekor qilish
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!--  DESKTOP MODAL 2: MANZIL QO'SHISH (PC Modal)                              -->
    <!-- ========================================================================= -->
    <div
      v-if="isAddressModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="isAddressModalOpen = false"
    >
      <div class="bg-white rounded-3xl w-full max-w-lg p-6 sm:p-7 shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-5">
          <h3 class="text-lg font-bold text-neutral-900 m-0">Yangi manzil qo'shish</h3>
          <button
            type="button"
            @click="isAddressModalOpen = false"
            class="p-1.5 rounded-full hover:bg-secondary-100 text-neutral-400 border-none bg-transparent cursor-pointer transition-colors"
            aria-label="Yopish"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="handleSaveAddressModal" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-neutral-600 mb-1.5">To'liq manzil</label>
            <textarea
              v-model="modalAddressText"
              rows="3"
              placeholder="Shahar/Tuman, ko'cha, uy va xonadon raqami..."
              class="w-full rounded-2xl bg-secondary-50 px-4 py-3 border border-neutral-100 outline-none focus:border-primary focus:bg-white transition-all font-medium text-neutral-900 text-sm resize-none"
              required
            ></textarea>
          </div>

          <!-- Geolocation Detector -->
          <div class="p-3.5 rounded-2xl bg-secondary-50 border border-neutral-100 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-8 h-8 rounded-lg bg-white shadow-xs flex items-center justify-center text-primary shrink-0">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
              </div>
              <div class="min-w-0">
                <p v-if="modalCoords" class="text-xs text-emerald-600 font-medium truncate m-0">Joylashuv aniqlandi ✓</p>
                <p v-else class="text-xs text-neutral-400 truncate m-0">Joriy joylashuvni aniqlash</p>
              </div>
            </div>

            <button
              type="button"
              @click="captureModalLocation"
              :disabled="modalLocating"
              class="px-3 py-1.5 rounded-xl bg-white border border-neutral-200 text-xs font-semibold text-neutral-700 hover:border-primary/40 hover:text-primary transition-all cursor-pointer disabled:opacity-75 shrink-0"
            >
              {{ modalLocating ? 'Aniqlanmoqda...' : (modalCoords ? 'Qayta' : 'Aniqlash') }}
            </button>
          </div>

          <div v-if="modalGeoError" class="p-2.5 rounded-xl bg-amber-50 text-amber-700 text-xs font-medium">
            {{ modalGeoError }}
          </div>

          <div v-if="modalAddressError" class="p-2.5 rounded-xl bg-red-50 text-red-600 text-xs font-medium">
            {{ modalAddressError }}
          </div>

          <div class="flex items-center gap-3 pt-3">
            <button
              type="submit"
              :disabled="!modalAddressText.trim() || savingModalAddress"
              class="flex-1 py-3 rounded-2xl bg-primary text-white font-semibold text-sm border-none cursor-pointer hover:bg-primary/90 transition-colors shadow-md disabled:opacity-75"
            >
              {{ savingModalAddress ? 'Saqlanmoqda...' : 'Saqlash' }}
            </button>
            <button
              type="button"
              @click="isAddressModalOpen = false"
              :disabled="savingModalAddress"
              class="px-6 py-3 rounded-2xl bg-secondary-100 text-neutral-700 font-semibold text-sm border-none cursor-pointer hover:bg-secondary-200 transition-colors"
            >
              Bekor qilish
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const config = useRuntimeConfig()

const userAny = computed(() => authStore.user as any)

const SEX_OPTIONS = [
  { value: '', label: "Ko'rsatilmagan" },
  { value: 'erkak', label: 'Erkak' },
  { value: 'ayol', label: 'Ayol' },
]

// Desktop modaldagi tab-tugma uchun — piyoladagi kabi bo'sh variantsiz,
// faqat Erkak/Ayol.
const SEX_TOGGLE_OPTIONS = SEX_OPTIONS.filter(o => o.value)

const sexLabel = computed(() => {
  const found = SEX_OPTIONS.find(o => o.value === (userAny.value?.sex || ''))
  return found && found.value ? found.label : 'Kiritilmagan'
})

// MUHIM: piyola'dagi kabi "DD.MM.YYYY" formatida ko'rsatiladi. Backend
// `birthdate`ni "YYYY-MM-DD" (Laravel `date` ustuni) shaklida qaytaradi.
const birthdateLabel = computed(() => {
  const raw = userAny.value?.birthdate
  if (!raw) return 'Kiritilmagan'
  const datePart = String(raw).slice(0, 10)
  const [y, m, d] = datePart.split('-')
  if (!y || !m || !d) return 'Kiritilmagan'
  return `${d}.${m}.${y}`
})

const todayIso = computed(() => new Date().toISOString().slice(0, 10))

// ── Desktop Edit Modal Holatlari ────────────────────────────────────
const isEditModalOpen = ref(false)
const savingEdit = ref(false)
const editModalError = ref('')

const editForm = reactive({
  name: '',
  lastname: '',
  sex: '',
  birthdate: '',
  email: '',
})

function openEditModal() {
  editForm.name = authStore.user?.name || ''
  editForm.lastname = userAny.value?.lastname || ''
  editForm.sex = userAny.value?.sex || ''
  editForm.birthdate = userAny.value?.birthdate ? String(userAny.value.birthdate).slice(0, 10) : ''
  editForm.email = userAny.value?.email || ''
  editModalError.value = ''
  isEditModalOpen.value = true
}

async function handleSaveEditModal() {
  savingEdit.value = true
  editModalError.value = ''
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/settings`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        name: editForm.name,
        lastname: editForm.lastname,
        sex: editForm.sex,
        birthdate: editForm.birthdate || '',
        email: editForm.email || '',
      }
    })
    authStore.updateUser({
      name: editForm.name,
      lastname: editForm.lastname,
      sex: editForm.sex,
      birthdate: editForm.birthdate || null,
      email: editForm.email || null,
    })
    isEditModalOpen.value = false
  } catch (e: any) {
    editModalError.value = e?.data?.message || "Saqlashda xatolik yuz berdi"
  } finally {
    savingEdit.value = false
  }
}

// ── Saqlangan manzillar ro'yxati ────────────────────────────────────
const addresses = ref<any[]>([])
const addressesLoading = ref(true)
const mainAddressId = computed(() => userAny.value?.mainAddressID ?? null)

async function fetchAddresses() {
  addressesLoading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    addresses.value = res?.data || []
  } catch (e) {
    addresses.value = []
  } finally {
    addressesLoading.value = false
  }
}

async function makeMain(loc: any) {
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/select/${loc.id}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    authStore.updateUser({ mainAddressID: loc.id })
  } catch (e) {
    //
  }
}

async function removeAddress(loc: any) {
  try {
    await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/delete/${loc.id}`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    addresses.value = addresses.value.filter((a) => a.id !== loc.id)
  } catch (e) {
    //
  }
}

// ── Desktop Address Modal Holatlari ─────────────────────────────────
const isAddressModalOpen = ref(false)
const modalAddressText = ref('')
const modalCoords = ref<{ lat: number; lon: number } | null>(null)
const modalLocating = ref(false)
const modalGeoError = ref('')
const savingModalAddress = ref(false)
const modalAddressError = ref('')

function openAddressModal() {
  modalAddressText.value = ''
  modalCoords.value = null
  modalGeoError.value = ''
  modalAddressError.value = ''
  isAddressModalOpen.value = true
}

function captureModalLocation() {
  if (!('geolocation' in navigator)) {
    modalGeoError.value = 'Brauzeringiz joylashuvni aniqlay olmaydi'
    return
  }
  modalLocating.value = true
  modalGeoError.value = ''
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      modalCoords.value = { lat: pos.coords.latitude, lon: pos.coords.longitude }
      modalLocating.value = false
    },
    () => {
      modalCoords.value = { lat: 41.2995, lon: 69.2401 }
      modalGeoError.value = "Joylashuvga ruxsat berilmadi. Standart shahar koordinatasi olindi."
      modalLocating.value = false
    },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

async function handleSaveAddressModal() {
  if (!modalAddressText.value.trim()) return
  if (!modalCoords.value) {
    modalCoords.value = { lat: 41.2995, lon: 69.2401 }
  }
  savingModalAddress.value = true
  modalAddressError.value = ''
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/locations/new`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        lat: modalCoords.value.lat,
        lon: modalCoords.value.lon,
        fullAddress: modalAddressText.value.trim(),
      }
    })
    if (res?.location_id) {
      authStore.updateUser({ mainAddressID: res.location_id })
    }
    await fetchAddresses()
    isAddressModalOpen.value = false
  } catch (e: any) {
    modalAddressError.value = e?.data?.message || "Manzilni saqlashda xatolik yuz berdi"
  } finally {
    savingModalAddress.value = false
  }
}

onMounted(() => {
  if (authStore.isAuthenticated) {
    fetchAddresses()
  } else {
    addressesLoading.value = false
  }
})

useSeoMeta({ title: "Ma'lumotlarim — Kitobchi" })
</script>
