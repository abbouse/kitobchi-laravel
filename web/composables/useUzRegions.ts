// Uzbekiston viloyat → tuman → qishloq/mahalla kaskad tanlovi uchun
// composable. Ma'lumotlar manbasi: MIMAXUZ/uzbekistan-regions-data
// (https://github.com/MIMAXUZ/uzbekistan-regions-data), `web/public/data/
// uz-regions/{regions,districts,villages}.json` sifatida loyihaga
// ko'chirilgan (xom holicha — trim qilinmagan, shuning uchun bu yerda
// backend'dagi asl kalitlar: `region_id`, `district_id`, `name_uz`
// ishlatiladi).
//
// MUHIM: Toshkent shahridagi ko'pgina tumanlarda (Uchtepa, Bektemir,
// Yunusobod, Mirobod, Shayxontohur, Olmazor, Sirg'ali, Yakkasaroy,
// Yashnobod, Chilonzor, Yangihayot) datasetda QISHLOQ/MAHALLA yozuvlari
// UMUMAN YO'Q (Mirzo Ulug'bek tumanida ham atigi 2 tasi bor). Shu sababli
// UzAddressPicker.vue komponenti "villagesFor()" bo'sh qaytarganda
// select o'rniga erkin matn kiritish maydoniga o'tadi (hybrid UI).
//
// `quarters.json` manba datasetda BO'SH (`[]`) — ishlatilmaydi.

export interface UzRegion {
  id: number
  name: string
}

export interface UzDistrict {
  id: number
  regionId: number
  name: string
}

export interface UzVillage {
  id: number
  districtId: number
  name: string
}

// Manba datasetdagi haqiqiy tuman bo'lmagan (guruh nomi bo'lib qolgan)
// yozuv: {"id":2993,"region_id":11,"name_uz":"Toshkent shahrining
// tumanlari", ...} — bu chinakam tuman emas, shuning uchun chiqarib
// tashlanadi.
const EXCLUDED_DISTRICT_IDS = new Set<number>([2993])

// Modul darajasidagi (singleton) holat — barcha komponent nusxalari bir
// xil keshlangan ma'lumotni ulashadi, har safar qayta fetch qilinmaydi.
const regions = ref<UzRegion[]>([])
const districts = ref<UzDistrict[]>([])
const villages = ref<UzVillage[]>([])
const isLoaded = ref(false)
const isLoading = ref(false)
const loadError = ref(false)

async function ensureUzRegionsLoaded() {
  if (isLoaded.value || isLoading.value) return
  isLoading.value = true
  loadError.value = false
  try {
    const [rawRegions, rawDistricts, rawVillages] = await Promise.all([
      $fetch<any[]>('/data/uz-regions/regions.json'),
      $fetch<any[]>('/data/uz-regions/districts.json'),
      $fetch<any[]>('/data/uz-regions/villages.json'),
    ])

    regions.value = (rawRegions || [])
      .map((r: any): UzRegion => ({ id: r.id, name: r.name_uz }))
      .sort((a, b) => a.name.localeCompare(b.name, 'uz'))

    districts.value = (rawDistricts || [])
      .filter((d: any) => !EXCLUDED_DISTRICT_IDS.has(d.id))
      .map((d: any): UzDistrict => ({ id: d.id, regionId: d.region_id, name: d.name_uz }))
      .sort((a, b) => a.name.localeCompare(b.name, 'uz'))

    villages.value = (rawVillages || [])
      .map((v: any): UzVillage => ({ id: v.id, districtId: v.district_id, name: v.name_uz }))
      .sort((a, b) => a.name.localeCompare(b.name, 'uz'))

    isLoaded.value = true
  } catch (e) {
    loadError.value = true
  } finally {
    isLoading.value = false
  }
}

export function useUzRegions() {
  function districtsFor(regionId: number | null | undefined): UzDistrict[] {
    if (!regionId) return []
    return districts.value.filter((d) => d.regionId === regionId)
  }

  function villagesFor(districtId: number | null | undefined): UzVillage[] {
    if (!districtId) return []
    return villages.value.filter((v) => v.districtId === districtId)
  }

  function regionName(regionId: number | null | undefined): string {
    return regions.value.find((r) => r.id === regionId)?.name || ''
  }

  function districtName(districtId: number | null | undefined): string {
    return districts.value.find((d) => d.id === districtId)?.name || ''
  }

  return {
    regions,
    districts,
    villages,
    isLoaded,
    isLoading,
    loadError,
    ensureUzRegionsLoaded,
    districtsFor,
    villagesFor,
    regionName,
    districtName,
  }
}
