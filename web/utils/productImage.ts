// Mahsulot rasmini turli backend javoblaridan (ProductCard, qidiruv,
// savat, sevimlilar, sahifalar va h.k.) bir xilda aniqlash uchun YAGONA manba.

export function resolveImageUrl(src?: any): string {
  if (!src) return ''
  if (typeof src === 'object') {
    src = src.url || src.path || src.image || src.medium || src.original || ''
  }
  if (typeof src !== 'string') return ''
  const trimmed = src.trim()
  if (!trimmed) return ''
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:') || trimmed.startsWith('//')) {
    return trimmed
  }
  if (trimmed.startsWith('/storage/')) {
    return trimmed
  }
  if (trimmed.startsWith('storage/')) {
    return `/${trimmed}`
  }
  if (trimmed.startsWith('/')) {
    return trimmed
  }
  return `/storage/${trimmed}`
}

export const FALLBACK_PRODUCT_IMAGE = '/images/logo/logo_blue.png'

// Mahsulotning barcha rasmlari (karusel uchun) — birinchi mavjud va
// bo'sh bo'lmagan ro'yxat ustunlik qiladi.
export function resolveProductImages(product: any): string[] {
  const p = product || {}
  const lists = [p.medium_images, p.image_urls, p.thumb_images, p.images]
  for (const list of lists) {
    if (Array.isArray(list) && list.length > 0) {
      const resolved = list.map(resolveImageUrl).filter(Boolean)
      if (resolved.length > 0) return resolved
    }
  }
  // Eski/zaxira maydonlar
  if (p.first_image) {
    const single = resolveImageUrl(p.first_image)
    if (single) return [single]
  }
  if (p.image) {
    const single = resolveImageUrl(p.image)
    if (single) return [single]
  }
  return [FALLBACK_PRODUCT_IMAGE]
}

// Faqat bitta (asosiy) rasm kerak bo'lgan joylar uchun (savat, sevimlilar
// qatorlari, va h.k.).
export function resolveProductImage(product: any): string {
  return resolveProductImages(product)[0] || FALLBACK_PRODUCT_IMAGE
}
