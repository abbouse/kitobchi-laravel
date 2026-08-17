import { defineStore } from 'pinia'

// Maydonlar AuthController::successUserResponse() (haqiqiy backend,
// app/Http/Controllers/Api/AuthController.php) javobidan TO'G'RIDAN-TO'G'RI
// tasdiqlangan — endi taxmin emas.
export interface User {
  id: number
  name: string | null
  lastname?: string | null
  phone_number: string
  username?: string | null
  sex?: string | null
  avatar?: string
  photo?: string | null
  role?: string
  cartItemCount?: number
  mainAddressID?: number
  isVerified?: boolean
  phoneVerified?: boolean
}

// MUHIM — YANA BIR HAQIQIY ROOT CAUSE (AuthController::store(), 2-bosqich,
// "Eskirgan tokenlarni tozalash" qismi) to'g'ridan-to'g'ri jonli production
// API'da (kitobchi.com) sinovdan o'tkazilib TASDIQLANDI:
//
//   $activeTokens = DB::table('connected_devices')
//       ->where('user_id', $user->id)->where('user_type', 'user')
//       ->pluck('token')->toArray();
//   $user->tokens()->whereNotIn('token', $activeTokens)->delete();
//
// Bu qism REQUEST'da `device_id` bo'lish-bo'lmasligidan QAT'IY NAZAR har doim
// ishga tushadi. Agar `device_id` yuborilmasa, yangi yaratilgan token
// `connected_devices` jadvaliga UMUMAN yozilmaydi — demak u $activeTokens
// ro'yxatida yo'q — demak shu YUQORIDAGI QATOR uni requestning o'zida,
// javob frontendga qaytmasidan OLDIN, DARHOL O'CHIRIB TASHLAYDI. Natijada
// login "muvaffaqiyatli" ko'rinadi (chunki foydalanuvchi ma'lumoti javobda
// to'g'ridan-to'g'ri qaytadi), lekin qaytgan token darhol ishlamay qoladi —
// keyingi HAR QANDAY autentifikatsiya talab qiladigan chaqiruv
// (buyurtmalar, sevimlilar, manzil qo'shish, profil sozlamalarini saqlash
// va h.k.) 401 "Unauthenticated" bilan qaytadi. Jonli tekshiruv:
// `device_id`siz verifyOtp → keyingi /v1/kitobchi/purchase/list = 401;
// xuddi shu foydalanuvchi, `device_id` bilan verifyOtp → xuddi shu so'rov
// = 200 va haqiqiy buyurtmalar ro'yxati. Demak yechim — har bir brauzer
// uchun barqaror (doimiy) `device_id`ni bir marta yaratib cookie'da saqlash
// va uni har safar verifyOtp so'rovida yuborish.
function getOrCreateDeviceId(): string {
  const cookie = useCookie<string | null>('kc_device_id', { maxAge: 60 * 60 * 24 * 365 })
  if (!cookie.value) {
    const random = typeof crypto !== 'undefined' && 'randomUUID' in crypto
      ? crypto.randomUUID()
      : `${Date.now()}-${Math.random().toString(36).slice(2)}`
    cookie.value = `web-${random}`
  }
  return cookie.value
}

export const useAuthStore = defineStore('auth', () => {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('kc_token', { maxAge: 60 * 60 * 24 * 30 })
  // MUHIM: `user` ENDI TOKEN KABI COOKIE'DA SAQLANADI (oldin oddiy `ref`
  // edi — sahifa yangilanganda/qayta ochilganda darhol yo'qolib qolardi).
  // Sabab jonli tekshirildi va 100% tasdiqlandi: `/api/user` (yagona
  // "joriy foydalanuvchini olish" yo'li) HAQIQIY, TO'G'RI tokenni ham
  // 401 bilan rad etadi — chunki u Laravel'ning standart `auth:sanctum`
  // guard'i bilan himoyalangan, lekin haqiqiy tizim `$user->createToken()`
  // orqali chiqargan bo'lsa-da, profilni o'qish HAMMA JOYDA alohida
  // `Auth::guard('user')` orqali ishlaydi (UserController) va bu prefiks
  // ostida "joriy foydalanuvchini olish" uchun ROUTE UMUMAN YO'Q
  // (`UserController::index()` mavjud, lekin hech qayerda route qilinmagan
  // — o'lik kod). Demak backendda foydalanuvchi ma'lumotini QAYTA OLISH
  // uchun ISHLAYDIGAN endpoint yo'q — shuning uchun uni faqat bir marta,
  // `/auth` orqali kod tasdiqlanganda olamiz va o'zimiz saqlaymiz.
  const user = useCookie<User | null>('kc_user', { maxAge: 60 * 60 * 24 * 30 })
  const isAuthModalOpen = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  // Backendda haqiqatan ishlaydigan "joriy foydalanuvchini qayta olish"
  // yo'li yo'qligi sababli, bu funksiya endi shunchaki mavjud (cookie'da
  // saqlangan) foydalanuvchi ma'lumotini LOKAL ravishda yangilaydi —
  // masalan profilni tahrirlagandan yoki asosiy manzilni
  // o'zgartirgandan keyin, backend javobidan olingan aniq qiymatlar bilan
  // (taxminiy emas — har bir chaqiruv joyida qaysi maydon o'zgarganini
  // biz bilamiz).
  function updateUser(patch: Partial<User>) {
    if (!user.value) return
    user.value = { ...user.value, ...patch }
  }

  // ESKIRGAN: haqiqiy backendda mos endpoint yo'qligi sababli bu funksiya
  // endi HECH NARSA QILMAYDI (xavfsiz no-op) — faqat eski chaqiruv
  // joylari (agar qolgan bo'lsa) sinmasligi uchun saqlab turilgan.
  async function fetchUser() {
    if (!token.value) {
      user.value = null
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

  // AuthController::successUserResponse() dan TASDIQLANGAN: token
  // `data.token` ichida keladi (HTTP 201). Boshqa variantlar — faqat
  // ehtiyot chorasi (kelajakda backend o'zgarsa ham ishlab tursin).
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
    // MUHIM — HAQIQIY (asosiy) ROOT CAUSE topildi: haqiqiy backend
    // (AuthController::store(), app/Http/Controllers/Api/AuthController.php)
    // ikkinchi bosqichni (kodni tekshirish) faqat `verifyCode` maydoni
    // MAVJUD bo'lsa ishga tushiradi — `code` degan maydonni UMUMAN
    // bilmaydi:
    //   if (strlen($phone_number) == 12 && is_null($request->verifyCode)) { ... "1-BOSQICH: SMS yuborish" ... }
    // Biz ilgari `code` deb yuborganimiz uchun `$request->verifyCode`
    // doim NULL edi — demak backend HAR SAFAR "1-bosqich" (SMS yuborish)
    // deb hisoblab, YANGI kod bilan YANGI SMS yuborardi va tokensiz
    // {status:'success', message:'Kod yuborildi'} qaytarardi. Foydalanuvchi
    // kodni QANCHALIK to'g'ri kiritmasin, tekshirish HECH QACHON sodir
    // bo'lmasdi — shuning uchun "kodni to'g'ri kiritsa ham auth
    // bo'lmayapti" edi. Manba: AuthController.php qatordagi haqiqiy
    // fayldan to'g'ridan-to'g'ri o'qildi (routes/api.php orqali tasdiqlangan).
    // MUHIM: `device_id` (va `device_name`/`platform`) shart — sababi
    // yuqoridagi izohda tushuntirilgan (aks holda backend yangi tokenni
    // o'zi yaratgan requestning ICHIDA darhol o'chirib tashlaydi).
    const res = await $fetch<any>(`${config.public.apiBase}/auth`, {
      method: 'POST',
      body: {
        phone_number: phone,
        verifyCode: code,
        device_id: getOrCreateDeviceId(),
        device_name: 'Kitobchi Web',
        platform: 'web'
      }
    })

    const foundToken = extractToken(res)
    if (foundToken) {
      token.value = foundToken
      // `/auth` (verifyCode bilan) javobi ALLAQACHON to'liq foydalanuvchi
      // ma'lumotini o'z ichiga oladi (id, name, lastname, sex, mainAddressID,
      // cartItemCount va h.k.) — qo'shimcha "profilni olish" chaqiruvi
      // shart emas (bunday ishlaydigan endpoint yo'qligi ham tasdiqlangan).
      user.value = extractUser(res, phone)
      isAuthModalOpen.value = false
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
    updateUser,
    sendOtp,
    verifyOtp,
    logout,
    openAuthModal,
    closeAuthModal
  }
})
