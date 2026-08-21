import { defineStore, skipHydrate } from 'pinia'

export interface CartItem {
  id: number
  productId: number
  slug?: string
  type: 'book' | 'stationery'
  name: string
  price: number
  originalPrice: number
  image: string
  quantity: number
  selected: boolean
}

export const useCartStore = defineStore('cart', () => {
  // MUHIM: `useLocalStorage` bilan yaratilgan ref Pinia'ning SSR state
  // serialization/hydration mexanizmi bilan to'qnashadi — Nuxt SSR paytida
  // server tomonida `localStorage` yo'q, shu sababli bu ref `[]` (bo'sh
  // massiv) bilan boshlanadi va shu holat Nuxt payload orqali clientga
  // yuboriladi. Client tomonida Pinia hydration paytida `useLocalStorage`
  // haqiqiy saqlangan qiymatni o'qib ulguradi, lekin darhol keyin Pinia
  // uni yana serverdan kelgan bo'sh `[]` bilan almashtiradi — natijada
  // foydalanuvchi savatga mahsulot qo'shib, keyin sahifani yangilasa
  // (yoki to'g'ridan-to'g'ri /cart manziliga o'tsa) savat "bo'sh" bo'lib
  // ko'rinadi va bu bo'sh holat localStorage'ga ham yozilib, haqiqiy
  // ma'lumotni butunlay o'chirib yuboradi. `skipHydrate()` shu refni
  // Pinia SSR hydration'idan chiqarib tashlaydi — endi u faqat o'zining
  // manbasidan (localStorage) client tomonda to'g'ri tiklanadi.
  const items = skipHydrate(useLocalStorage<CartItem[]>('kc_cart_items', []))

  const totalCount = computed(() => {
    return items.value.reduce((sum, item) => sum + item.quantity, 0)
  })

  const selectedItems = computed(() => {
    return items.value.filter(item => item.selected)
  })

  const selectedCount = computed(() => {
    return selectedItems.value.reduce((sum, item) => sum + item.quantity, 0)
  })

  const originalTotalAmount = computed(() => {
    return selectedItems.value.reduce((sum, item) => sum + (item.originalPrice || item.price) * item.quantity, 0)
  })

  const totalAmount = computed(() => {
    return selectedItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
  })

  const totalDiscount = computed(() => {
    return selectedItems.value.reduce((sum, item) => {
      const diff = (item.originalPrice || item.price) - item.price
      return sum + (diff > 0 ? diff * item.quantity : 0)
    }, 0)
  })

  const isAllSelected = computed(() => {
    return items.value.length > 0 && items.value.every(item => item.selected)
  })

  function isSelected(id: number): boolean {
    const item = items.value.find(i => i.id === id)
    return item ? Boolean(item.selected) : false
  }

  function addItem(product: any, type: 'book' | 'stationery' = 'book', quantity = 1) {
    const existing = items.value.find(i => i.productId === product.id && i.type === type)
    if (existing) {
      existing.quantity += quantity
    } else {
      const isDisc = type === 'book'
        ? (product.discountPrice > 0 && product.discountPrice < product.price)
        : (product.discount_price > 0 && product.discount_price < product.price)
      const currentPrice = isDisc
        ? (type === 'book' ? product.discountPrice : product.discount_price)
        : product.price

      items.value.push({
        id: Date.now() + Math.random(),
        productId: product.id,
        slug: product.slug || `${product.id}`,
        type,
        name: product.name,
        price: Number(currentPrice),
        originalPrice: Number(product.price),
        image: resolveProductImage(product),
        quantity,
        selected: true
      })
    }
  }

  function updateQuantity(id: number, quantity: number) {
    const item = items.value.find(i => i.id === id)
    if (item) {
      if (quantity <= 0) {
        removeItem(id)
      } else {
        item.quantity = quantity
      }
    }
  }

  function removeItem(id: number) {
    items.value = items.value.filter(i => i.id !== id)
  }

  function removeSelected() {
    items.value = items.value.filter(i => !i.selected)
  }

  function toggleSelect(id: number) {
    const item = items.value.find(i => i.id === id)
    if (item) {
      item.selected = !item.selected
    }
  }

  function toggleSelectAll() {
    const nextState = !isAllSelected.value
    items.value.forEach(i => (i.selected = nextState))
  }

  function clearCart() {
    items.value = []
  }

  return {
    items,
    totalCount,
    selectedItems,
    selectedCount,
    originalTotalAmount,
    totalAmount,
    totalDiscount,
    isAllSelected,
    isSelected,
    addItem,
    updateQuantity,
    removeItem,
    removeSelected,
    toggleSelect,
    toggleSelectAll,
    clearCart
  }
})
