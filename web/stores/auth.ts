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
    } catch {
      token.value = null
      user.value = null
    }
  }

  async function sendOtp(phone: string) {
    return await $fetch<any>(`${config.public.apiBase}/sendSms`, {
      method: 'POST',
      body: { phone_number: phone }
    })
  }

  async function verifyOtp(phone: string, code: string) {
    const res = await $fetch<any>(`${config.public.apiBase}/auth`, {
      method: 'POST',
      body: {
        phone_number: phone,
        code: code
      }
    })

    if (res?.token) {
      token.value = res.token
      user.value = res.user || {
        id: res.user_id || 1,
        name: res.name || null,
        phone_number: phone
      }
      isAuthModalOpen.value = false
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
