import { defineStore } from 'pinia'

// Bosh sahifa/header'dagi qidiruv panelini (piyola'dagi kabi to'liq
// ekranli overlay) boshqarish uchun kichik store — AuthModal bilan bir xil
// naqsh (isOpen + open/close), AppHeader'dagi ikkala (desktop/mobil)
// qidiruv qutisi shu bitta overlay'ni ochadi.
export const useSearchStore = defineStore('search', () => {
  const isOpen = ref(false)

  function open() {
    isOpen.value = true
  }

  function close() {
    isOpen.value = false
  }

  return { isOpen, open, close }
})
