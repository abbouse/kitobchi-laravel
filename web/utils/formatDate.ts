// Backendning ba'zi joylarida (masalan Sold::purchaseList) Carbon
// isoFormat() orqali "formatted_created_at" qaytadi, lekin bu server
// lokalizatsiyasi sababli RUS tilidagi oy nomlari bilan keladi (masalan
// "1 мая 2026, 07:05" — jonli production API'da tasdiqlangan). Boshqa
// joylarda (masalan BookClub postlari) bunday tayyor maydon UMUMAN
// yo'q — faqat xom ISO vaqt belgisi (`created_at`) qaytadi. Ilova
// to'liq o'zbek tilida bo'lgani uchun HAR IKKALA holatda ham sanani
// o'zimiz, xom `created_at`dan, o'zbekcha oy nomlari bilan formatlaymiz
// — bu HAQIQIY ma'lumot (asl vaqt belgisi), faqat ko'rinishi o'zgaradi.
const UZ_MONTHS = [
  'yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun',
  'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr',
]

export function formatUzDate(raw?: string | null): string {
  if (!raw) return ''
  const d = new Date(raw)
  if (Number.isNaN(d.getTime())) return raw
  const day = d.getDate()
  const month = UZ_MONTHS[d.getMonth()]
  const year = d.getFullYear()
  const hh = String(d.getHours()).padStart(2, '0')
  const mm = String(d.getMinutes()).padStart(2, '0')
  return `${day}-${month}, ${year} ${hh}:${mm}`
}
