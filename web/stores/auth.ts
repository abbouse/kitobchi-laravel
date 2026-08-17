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
    // AuthController::store() — verifyCode yo'q bo'lsa SMS yuboradi
    return await $fetch<any>(`${config.public.apiBase}/auth`, {
      method: 'POST',
      body: { phone_number: phone }
    })
  }

  async function verifyOtp(phone: string, code: string) {
    const res = await $fetch<any>(`${config.public.apiBase}/auth`, {
      method: 'POST',
      body: {
        phone_number: phone,
        verifyCode: code
      }
    })

    const payload = res?.data || res
    const authToken = payload?.token || res?.token

    if (authToken) {
      token.value = authToken
      user.value = {
        id: payload.id || 1,
        name: payload.name || null,
        phone_number: payload.phone_number || phone,
        avatar: payload.photo || payload.avatar || undefined,
        role: payload.staff_role || payload.position || undefined
      }
      isAuthModalOpen.value = false
      // Shuningdek, to'liq user profilini yuklab olish
      await fetchUser().catch(() => {})
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
