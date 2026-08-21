// MUHIM — "auth qilgan userning sessiyasi negadir hozir uzoqqa
// bormayapti" muammosi bo'yicha YECHIM (2026-08-21 tekshiruvi):
//
// stores/auth.ts'dagi useCookie('kc_token', {maxAge: 30 kun}) va
// useCookie('kc_user', {maxAge: 30 kun}) FAQAT foydalanuvchi LOGIN
// qilgan paytda (verifyOtp) yoziladi. Shundan keyingi HAR QANDAY sahifa
// tashrifi — hatto foydalanuvchi saytni har kuni ochsa ham — bu
// cookie'larning muddatini (Max-Age) UZAYTIRMAYDI. Sababi: Nuxt'ning
// useCookie() composable'i (node_modules/nuxt/dist/app/composables/
// cookie.js, callback() funksiyasi) cookie'ni FAQAT qiymat HAQIQATDA
// o'zgarganda qayta yozadi — `isEqual(cookie.value, cookies[name])`
// true bo'lsa, yozish shunchaki o'tkazib yuboriladi. Demak login'dan
// roppa-rosa 30 kun o'tgach, foydalanuvchi shu kunlar davomida HAR KUNI
// faol foydalansa ham, sessiya "bexosdan" tugab, header hech qanday
// ogohlantirishsiz jimgina "Kirish" holatiga qaytadi.
//
// Bu aynan jonli tekshiruvda tasdiqlandi: kc_device_id (1 yillik
// cookie) saqlanib qoldi, lekin kc_token/kc_user (30 kunlik
// cookie'lar) document.cookie'dan butunlay yo'qolgan edi — garchi
// foydalanuvchi ilgari xuddi shu brauzerda muvaffaqiyatli login qilgan
// va sayt bilan faol ishlagan bo'lsa-da.
//
// Yechim: har bir sahifa yuklanishida va client-side navigatsiyada
// (agar foydalanuvchi HALI HAM login qilgan bo'lsa) shu cookie'larning
// Max-Age'ini joriy vaqtdan yana to'liq muddatga "surib qo'yamiz" —
// Pinia store'dagi reaktiv `token`/`user` ref'lariga TEGINMASDAN
// (ularni qayta yozish `isEqual` tekshiruvi tufayli baribir yozishni
// keltirib chiqarmaydi, va agar aylanma yo'l bilan majburlansa ham —
// masalan avval null qilib keyin qaytarish — bu UI'da bir lahzalik
// "chiqib ketish" effektini keltirib chiqarishi mumkin edi), balki
// document.cookie orqali TO'G'RIDAN-TO'G'RI, MAVJUD (allaqachon
// kodlangan) qiymatni bir harf ham o'zgartirmasdan, faqat muddatini
// yangilab qo'yamiz. Natija: FAOL foydalanuvchi sessiyasi HECH QACHON
// "bexosdan" tugamaydi — faqat cookie muddati (30 kun / 1 yil)
// DAVOMIDA saytga umuman kirmagan foydalanuvchigina chiqib qoladi —
// bu xavfsiz va kutilgan xulq-atvor.

const SESSION_COOKIES: Array<{ name: string; maxAgeSeconds: number }> = [
  { name: 'kc_token', maxAgeSeconds: 60 * 60 * 24 * 30 },
  { name: 'kc_user', maxAgeSeconds: 60 * 60 * 24 * 30 },
  { name: 'kc_device_id', maxAgeSeconds: 60 * 60 * 24 * 365 },
]

function readRawCookieValue(name: string): string | null {
  const row = document.cookie.split('; ').find((entry) => entry.startsWith(`${name}=`))
  return row ? row.slice(name.length + 1) : null
}

function touchCookie(name: string, maxAgeSeconds: number) {
  const rawValue = readRawCookieValue(name)
  // Cookie umuman mavjud bo'lmasa (mehmon foydalanuvchi yoki allaqachon
  // muddati tugagan) — hech narsa qilinmaydi. Faqat qiymatga
  // tegilmasdan (u allaqachon to'g'ri kodlangan), Max-Age'ni yangilaymiz.
  if (rawValue === null) return
  document.cookie = `${name}=${rawValue}; path=/; max-age=${maxAgeSeconds}`
}

function touchAllSessionCookies() {
  for (const { name, maxAgeSeconds } of SESSION_COOKIES) {
    touchCookie(name, maxAgeSeconds)
  }
}

export default defineNuxtPlugin(() => {
  // Faqat brauzerda ishlaydi — document.cookie serverda mavjud emas
  // (fayl nomidagi `.client.ts` qo'shimchasi buni Nuxt darajasida ham
  // ta'minlaydi, bu tekshiruv qo'shimcha xavfsizlik uchun).
  if (import.meta.server) return

  // Ilova birinchi marta ochilganda darhol.
  touchAllSessionCookies()

  // Va foydalanuvchi sahifalar orasida SPA (client-side) navigatsiya
  // qilgan sayin — bu "faol ishlatilayotgan sessiya hech qachon
  // tugamaydi" degan sirg'anuvchi (sliding) muddatni ta'minlaydi.
  const router = useRouter()
  router.afterEach(() => {
    touchAllSessionCookies()
  })
})
