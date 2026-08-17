import { defineStore } from 'pinia'

export interface User {
  id: number
  name: string | null
  phone_number: string
  avatar?: string
  role?: string
}

export const useAuthStore = defineStore('auth', () => {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('kc_token', { maxAge: 60 * 60 * 24 * 30 })
  const user = ref<User | null>(null)
  const isAuthModalOpen = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  async function fetchUser() {
    if (!token.value) {
      user.value = null
      return
    }
    try {
      const data = await $fetch<any>(`${config.public.apiBase}/user`, {
        headers: {
          Authorization: `Bearer ${token.value}`
        }
      })
      user.value = data
    } catch (err) {
      // MUHIM (asosiy auth bugining ROOT CAUSE'i): bu yerda ilgari xato
      // bo'lganda `token.value = null` qilinardi — bu AYNAN "kodni to'g'ri
      // kiritsa ham auth bo'lmayapti" xatosining sababi edi.
      //
      // Sabab: `/api/user` — Laravel'ning standart, DEFOLT Sanctum guard
      // (`auth:sanctum`) bilan himoyalangan boilerplate yo'li (routes/api.php,
      // hech qachon o'zgartirilmagan). Lekin haqiqiy backend (UserController)
      // HAR QAYERDA `Auth::guard('user')` — alohida, custom guard —
      // ishlatadi (`routes/api_user.php`, `auth:user` middleware guruhi).
      // `v1/kitobchi` prefiksi ostida esa profilni olish uchun ishlaydigan
      // `/user` yo'li UMUMAN mavjud emas (jonli tekshirildi: 404). Demak
      // `/api/user` chaqiruvi — token qanchalik to'g'ri bo'lishidan qat'iy
      // nazar — DOIM 401 qaytaradi. Eski kod buni "token yaroqsiz" deb
      // talqin qilib, hozirgina muvaffaqiyatli olingan tokenni zudlik bilan
      // o'chirib tashlardi (foydalanuvchi kodni to'g'ri kiritsa ham,
      // login zumda "orqaga qaytardi").
      //
      // Endi bu yerda xato bo'lsa ham tokenni SAQLAB QOLAMIZ — profil
      // ma'lumotini shu yo'l orqali yangilab bo'lmasa ham (chunki bu
      // endpoint noto'g'ri), kirish holati buzilmaydi. Haqiqiy eskirgan/
      // yaroqsiz token bo'lsa, u boshqa haqiqiy `auth:user` yo'llarida
      // (savat, sozlamalar, manzillar va h.k.) tabiiy ravishda 401
      // qaytaradi va o'sha joylarda aniqlanadi.
      if (import.meta.dev) {
        console.warn('[auth] fetchUser muvaffaqiyatsiz — token saqlanadi:', err)
      }
    }
  }

  // MUHIM: bu yerda ilgari `/sendSms` (umumiy, OTP bilan bog'liq bo'lmagan
  // SMS yuborish controlleri) chaqirilardi — u butunlay boshqa maydonlar
  // (`phone` + majburiy `msg` matni) kutadi va login oqimi uchun mo'ljallanmagan.
  // Shu sabab "raqam yozilsa ham" kirish hech qachon ishlamasdi (backend
  // 422 xato qaytarardi, lekin frontend buni jimgina "xatolik" deb ko'rsatardi).
  // Haqiqiy OTP oqimi — bitta `/auth` endpointi: `code'siz` chaqirilsa kod
  // yuboradi, `code` bilan chaqirilsa tekshiradi (quyida verifyOtp). Bu
  // real backend (routes/api.php: Route::post('auth', [AuthController::class,
  // 'store'])) ustida to'g'ridan-to'g'ri sinovdan o'tkazilib tasdiqlandi.
  async function sendOtp(phone: string) {
    return await $fetch<any>(`${config.public.apiBase}/auth`, {
      method: 'POST',
      body: { phone_number: phone }
    })
  }

  // MUHIM: haqiqiy backend "kod tasdiqlandi" javobining ANIQ shaklini
  // (token qaysi maydonda kelishini) real SMS kodi bo'lmagani sababli
  // to'liq oxirigacha tasdiqlab bo'lmadi (faqat "kod yuborildi" bosqichi
  // jonli sinovdan o'tkazildi). Shu sababli bir nechta ehtimoliy joy
  // (token / access_token, ham tekis, ham data{} ichida) tekshiriladi —
  // haqiqiy javob qaysi shaklda kelishidan qat'iy nazar ishlashi uchun.
  function extractToken(res: any): string | null {
    return (
      res?.token ||
      res?.access_token ||
      res?.data?.token ||
      res?.data?.access_token ||
      res?.authorization?.token ||
      res?.authorization?.access_token ||
      null
    )
  }

  function extractUser(res: any, phone: string): User {
    const u = res?.user || res?.data?.user || (res?.data && typeof res.data === 'object' && res.data.id ? res.data : null)
    return u || {
      id: res?.user_id || res?.data?.user_id || 1,
      name: res?.name || null,
      phone_number: phone
    }
  }

  async function verifyOtp(phone: string, code: string) {
    const res = await $fetch<any>(`${config.public.apiBase}/auth`, {
      method: 'POST',
      body: {
        phone_number: phone,
        code: code
      }
    })

    const foundToken = extractToken(res)
    if (foundToken) {
      token.value = foundToken
      user.value = extractUser(res, phone)
      isAuthModalOpen.value = false
      // Profilni orqa fondan yangilashga urinib ko'ramiz (best-effort).
      // MUHIM: fetchUser() endi xato bo'lganda ham tokenni o'chirmaydi
      // (yuqoridagi izohga qarang) — shuning uchun bu yerda xavfsiz.
      // Asosiy kirish holati yuqoridagi ikki qatorda allaqachon o'rnatildi.
      await fetchUser()
    } else {
      // status "success" bo'lsa-da token topilmasa — javob kutilmagan
      // shaklda kelgan bo'lishi mumkin. Jimgina hech narsa qilmasdan
      // qolib ketmasligi uchun aniq xato tashlaymiz (AuthModal buni
      // errorMessage sifatida ko'rsatadi).
      if (res?.status === 'success' || res?.status === true) {
        throw new Error('Tizimga kirishda kutilmagan javob keldi. Iltimos, qayta urinib ko‘ring yoki qo‘llab-quvvatlash xizmatiga murojaat qiling.')
      }
    }
    return res
  }

  function logout() {
    token.value = null
    user.value = null
  }

  function openAuthModal() {
    isAuthModalOpen.value = true
  }

  function closeAuthModal() {
    isAuthModalOpen.value = false
  }

  return {
    token,
    user,
    isAuthenticated,
    isAuthModalOpen,
    fetchUser,
    sendOtp,
    verifyOtp,
    logout,
    openAuthModal,
    closeAuthModal
  }
})
