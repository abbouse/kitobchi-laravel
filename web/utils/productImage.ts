// Mahsulot rasmini turli backend javoblaridan (ProductCard, qidiruv,
// savat, sevimlilar va h.k.) bir xilda aniqlash uchun YAGONA manba.
//
// Muhim: haqiqiy backend javobida `first_image` degan maydon UMUMAN
// YO'Q — faqat `medium_images` / `thumb_images` / `image_urls` / `images`
// ro'yxatlari qaytadi. Oldin savat va sevimlilar shu yo'q maydonga
// tayanib doim logo-placeholder ko'rsatib kelgan edi (first_image har
// doim undefined bo'lgani uchun). Endi hammasi shu faylga tayanadi —
// birortasida tuzatilgan xato boshqasida qayta paydo bo'lmaydi.

// MUHIM: `/` bilan boshlanadigan yo'l (masalan `/images/logo/...` yoki
// allaqachon `/storage/...`) ham "hal qilingan" deb hisoblanadi va
// o'zgarishsiz qaytariladi. Buning sababi: favorites/index.vue kabi
// joylarda avval BIR MARTA hal qilingan rasm (stores/favorites.ts'da
// saqlangan `item.image`) ProductCard'ga qayta `first_image` sifatida
// uzatiladi va shu funksiyadan YANA o'tadi — agar faqat `http`/`data:`
// tekshirilsa, `/images/logo/logo_blue.png` kabi qiymat noto'g'ri
// `/storage//images/logo/logo_blue.png`ga aylanib, rasm sinib qolardi.
export function resolveImageUrl(src?: string | null): string {
  if (!src) return ''
  if (src.startsWith('http') || src.startsWith('data:') || src.startsWith('/')) return src
  return `/storage/${src}`
}

export const FALLBACK_PRODUCT_IMAGE = '/images/logo/logo_blue.png'

// Mahsulotning barcha rasmlari (karusel uchun) — birinchi mavjud va
// bo'sh bo'lmagan ro'yxat ustunlik qiladi.
export function resolveProductImages(product: any): string[] {
  const p = product || {}
  const lists = [p.medium_images, p.thumb_images, p.image_urls, p.images]
  for (const list of lists) {
    if (Array.isArray(list) && list.length > 0) {
      const resolved = list.map(resolveImageUrl).filter(Boolean)
      if (resolved.length > 0) return resolved
    }
  }
  // Eski/zaxira maydon — ba'zi eski javoblarda uchrashi mumkin.
  if (p.first_image) {
    const single = resolveImageUrl(p.first_image)
    if (single) return [single]
  }
  return [FALLBACK_PRODUCT_IMAGE]
}

// Faqat bitta (asosiy) rasm kerak bo'lgan joylar uchun (savat, sevimlilar
// qatorlari, va h.k.).
export function resolveProductImage(product: any): string {
  return resolveProductImages(product)[0] || FALLBACK_PRODUCT_IMAGE
}
