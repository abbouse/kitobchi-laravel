import { defineStore, skipHydrate } from 'pinia'

export interface FavoriteItem {
  id: number
  productId: number
  type: 'book' | 'stationery'
  name: string
  price: number
  discountPrice?: number
  image: string
}

function extractImg(p: any): string {
  if (!p) return '/images/logo/logo_blue.png'
  const raw = p.medium_images?.[0]
    || p.thumb_images?.[0]
    || p.image_urls?.[0]
    || (Array.isArray(p.images) ? p.images[0] : null)
    || p.first_image
    || p.image
  if (!raw) return '/images/logo/logo_blue.png'
  if (raw.startsWith('http') || raw.startsWith('data:')) return raw
  return `/storage/${raw}`
}

export const useFavoritesStore = defineStore('favorites', () => {
  // cart.ts'dagi izohga qarang: `useLocalStorage` + Pinia SSR hydration
  // to'qnashuvi sababli, sahifani yangilashda "Sevimlilar" ro'yxati ham
  // bo'shab qolishi (va localStorage'dagi haqiqiy ma'lumot o'chib ketishi)
  // mumkin edi. `skipHydrate()` buni oldini oladi.
  const items = skipHydrate(useLocalStorage<FavoriteItem[]>('kc_favorite_items', []))

  const count = computed(() => items.value.length)

  function isFavorited(productId: number, type: 'book' | 'stationery' = 'book') {
    return items.value.some(i => i.productId === productId && i.type === type)
  }

  function toggleFavorite(product: any, type: 'book' | 'stationery' = 'book') {
    const idx = items.value.findIndex(i => i.productId === product.id && i.type === type)
    if (idx !== -1) {
      items.value.splice(idx, 1)
      return false
    } else {
      items.value.push({
        id: Date.now(),
        productId: product.id,
        type,
        name: product.name,
        price: Number(product.price),
        discountPrice: product.discountPrice || product.discount_price,
        image: extractImg(product)
      })
      return true
    }
  }

  function removeFavorite(productId: number, type: 'book' | 'stationery' = 'book') {
    items.value = items.value.filter(i => !(i.productId === productId && i.type === type))
  }

  return {
    items,
    count,
    isFavorited,
    toggleFavorite,
    removeFavorite
  }
})
