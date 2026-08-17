// Loyihaning umumiy sozlamalari (telefon/email/telegram va h.k.) — haqiqiy
// backend endpointidan olinadi: Api\ProjectSettingController::getVersions()
// (route: {apiBase}/appversion/check). Bu endpoint allaqachon ilova
// tomonidan ishlatiladi (versiya tekshirish), shu bilan birga haqiqiy
// kontakt ma'lumotlarini ham qaytaradi — shuning uchun frontend uchun
// alohida yangi endpoint kerak emas.
//
// AppFooter.vue va contacts.vue shu composable orqali bir xil (kesh
// qilingan) so'rovdan foydalanadi.

interface SiteContacts {
  kitobchi: { phone: string | null; email: string | null }
  business: { phone: string | null; email: string | null }
  courier: { phone: string | null; email: string | null }
}

interface SiteSettings {
  contacts: SiteContacts
  telegram: Record<string, any>
  ui_flags: Record<string, any>
}

export function useSiteSettings() {
  const config = useRuntimeConfig()

  const { data, pending, error } = useFetch<any>(`${config.public.apiBase}/appversion/check`, {
    key: 'site-settings-versions',
    server: true,
    lazy: true,
    default: () => null
  })

  const settings = computed<SiteSettings | null>(() => {
    const row = data.value?.data?.[0]
    if (!row) return null
    return {
      contacts: row.contacts,
      telegram: row.telegram,
      ui_flags: row.ui_flags
    }
  })

  const kitobchiPhone = computed(() => settings.value?.contacts?.kitobchi?.phone || null)
  const kitobchiEmail = computed(() => settings.value?.contacts?.kitobchi?.email || null)

  return { settings, kitobchiPhone, kitobchiEmail, pending, error }
}

// Ijtimoiy tarmoq va ilova havolalari — kodda tasdiqlangan haqiqiy
// manzillar (kitobchi-laravel/resources/views/partials/landing-footer.blade.php
// va kitobchi/lib/Config/Links.dart bilan bir xil):
export const SITE_LINKS = {
  telegram: 'https://t.me/kitobchi_market',
  instagram: 'https://instagram.com/kitobchi_market',
  googlePlay: 'https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi',
  appStore: 'https://apps.apple.com/uz/app/kitobchi/id6753818078'
}
