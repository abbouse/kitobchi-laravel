import { defineStore } from 'pinia'

export interface FavoriteItem {
  id: number
  productId: number
  type: 'book' | 'stationery'
  name: string
  price: number
  discountPrice?: number
  image: string
}

export const useFavoritesStore = defineStore('favorites', () => {
  const items = useLocalStorage<FavoriteItem[]>('kc_favorite_items', [])

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
        image: product.first_image ? `/storage/${product.first_image}` : '/images/logo/logo_blue.png'
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
