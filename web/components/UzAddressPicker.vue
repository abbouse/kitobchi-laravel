<template>
  <div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
      <!-- Viloyat -->
      <div>
        <label class="block font-medium text-neutral-700 text-sm mb-1.5">Viloyat</label>
        <div class="relative">
          <select
            v-model.number="regionId"
            class="w-full appearance-none text-neutral-900 focus:outline-none text-sm sm:text-base rounded-2xl h-12 px-4 pr-10 bg-[#F5F6F8] hover:bg-[#EFF1F4] focus:bg-white border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all cursor-pointer"
          >
            <option :value="null" disabled>Viloyatni tanlang</option>
            <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }}</option>
          </select>
          <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-neutral-400">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
          </span>
        </div>
      </div>

      <!-- Tuman -->
      <div>
        <label class="block font-medium text-neutral-700 text-sm mb-1.5">Tuman / shahar</label>
        <div class="relative">
          <select
            v-model.number="districtId"
            :disabled="!regionId"
            class="w-full appearance-none text-neutral-900 focus:outline-none text-sm sm:text-base rounded-2xl h-12 px-4 pr-10 bg-[#F5F6F8] hover:bg-[#EFF1F4] focus:bg-white border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <option :value="null" disabled>{{ regionId ? 'Tumanni tanlang' : 'Avval viloyatni tanlang' }}</option>
            <option v-for="d in districtOptions" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
          <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-neutral-400">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
          </span>
        </div>
      </div>
    </div>

    <!-- Mahalla / Qishloq -->
    <div>
      <div class="flex items-center justify-between gap-2 mb-1.5">
        <label class="block font-medium text-neutral-700 text-sm m-0">Mahalla / qishloq</label>
        <button
          v-if="villageOptions.length > 0"
          type="button"
          @click="useFreeVillageText = !useFreeVillageText"
          class="text-xs font-semibold text-primary hover:underline bg-transparent border-none cursor-pointer p-0"
        >
          {{ useFreeVillageText ? "Ro'yxatdan tanlash" : "Ro'yxatda yo'q, o'zim kiritaman" }}
        </button>
      </div>

      <div v-if="villageOptions.length > 0 && !useFreeVillageText" class="relative">
        <select
          v-model.number="villageId"
          :disabled="!districtId"
          class="w-full appearance-none text-neutral-900 focus:outline-none text-sm sm:text-base rounded-2xl h-12 px-4 pr-10 bg-[#F5F6F8] hover:bg-[#EFF1F4] focus:bg-white border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <option :value="null" disabled>{{ districtId ? 'Mahalla/qishloqni tanlang' : 'Avval tumanni tanlang' }}</option>
          <option v-for="v in villageOptions" :key="v.id" :value="v.id">{{ v.name }}</option>
        </select>
        <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-neutral-400">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
        </span>
      </div>
      <input
        v-else
        v-model="villageText"
        type="text"
        :disabled="!districtId"
        placeholder="Masalan: Do'stlik MFY"
        class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none text-sm sm:text-base rounded-2xl h-12 px-4 bg-[#F5F6F8] hover:bg-[#EFF1F4] focus:bg-white border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
      >
    </div>

    <!-- Ko'cha, uy, xonadon -->
    <div>
      <label class="block font-medium text-neutral-700 text-sm mb-1.5">Ko'cha, uy, xonadon raqami</label>
      <input
        v-model="street"
        type="text"
        placeholder="Masalan: Navoiy ko'chasi, 12-uy, 5-xonadon"
        class="w-full appearance-none placeholder:text-neutral-400 text-neutral-900 focus:outline-none text-sm sm:text-base rounded-2xl h-12 px-4 bg-[#F5F6F8] hover:bg-[#EFF1F4] focus:bg-white border border-transparent focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all"
      >
    </div>
  </div>
</template>

<script setup lang="ts">
// Yandex/GPS-lokatsiya asosidagi manzil kiritish o'rniga: Viloyat → Tuman →
// Mahalla/qishloq (yoki, agar tumanda ro'yxat mavjud bo'lmasa — erkin
// matn) kaskad tanlovi. Ma'lumot manbai: composables/useUzRegions.ts.
//
// Har qanday maydon o'zgarganda `update` hodisasi to'liq xulosa (summary)
// bilan chiqariladi — ota komponent shu obyektni saqlab, backendga
// yuborishi mumkin (`regionName`/`districtName`/`cityName`/`fullAddress`).
const { regions, districtsFor, villagesFor, ensureUzRegionsLoaded } = useUzRegions()

const regionId = ref<number | null>(null)
const districtId = ref<number | null>(null)
const villageId = ref<number | null>(null)
const villageText = ref('')
const useFreeVillageText = ref(false)
const street = ref('')

const districtOptions = computed(() => districtsFor(regionId.value))
const villageOptions = computed(() => villagesFor(districtId.value))

// Tuman o'zgarsa — tanlangan mahalla va viloyat mos kelmay qolmasligi
// uchun pastki tanlovlar tozalanadi.
watch(regionId, () => {
  districtId.value = null
  villageId.value = null
  villageText.value = ''
  useFreeVillageText.value = false
})

watch(districtId, () => {
  villageId.value = null
  villageText.value = ''
  // Agar yangi tumanda ro'yxat umuman bo'lmasa (masalan Toshkent
  // shahridagi ko'pgina tumanlar), avtomatik erkin matn rejimiga
  // o'tkaziladi.
  useFreeVillageText.value = villagesFor(districtId.value).length === 0
})

const selectedRegionName = computed(() => regions.value.find((r) => r.id === regionId.value)?.name || '')
const selectedDistrictName = computed(() => districtOptions.value.find((d) => d.id === districtId.value)?.name || '')
const selectedVillageName = computed(() => {
  if (useFreeVillageText.value || villageOptions.value.length === 0) return villageText.value.trim()
  return villageOptions.value.find((v) => v.id === villageId.value)?.name || ''
})

const fullAddress = computed(() => {
  const parts = [selectedRegionName.value, selectedDistrictName.value, selectedVillageName.value, street.value.trim()]
  return parts.filter(Boolean).join(', ')
})

const isValid = computed(() => {
  return !!regionId.value && !!districtId.value && !!selectedVillageName.value && street.value.trim().length > 0
})

const emit = defineEmits<{
  update: [summary: {
    regionId: number | null
    regionName: string
    districtId: number | null
    districtName: string
    village: string
    street: string
    fullAddress: string
    isValid: boolean
  }]
}>()

watch([regionId, districtId, villageId, villageText, street, useFreeVillageText], () => {
  emit('update', {
    regionId: regionId.value,
    regionName: selectedRegionName.value,
    districtId: districtId.value,
    districtName: selectedDistrictName.value,
    village: selectedVillageName.value,
    street: street.value.trim(),
    fullAddress: fullAddress.value,
    isValid: isValid.value,
  })
}, { immediate: true })

function reset() {
  regionId.value = null
  districtId.value = null
  villageId.value = null
  villageText.value = ''
  useFreeVillageText.value = false
  street.value = ''
}

defineExpose({ reset })

onMounted(() => {
  ensureUzRegionsLoaded()
})
</script>
