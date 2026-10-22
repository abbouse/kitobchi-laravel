// "+998 90 123 45 67" ko'rinishidagi telefon maskasi (hamkor va kuryer arizalari uchun).
export function formatUzPhone(raw: string): string {
  let d = (raw || '').replace(/\D/g, '')
  if (d.startsWith('998')) d = d.slice(3)
  d = d.slice(0, 9)
  const parts = [d.slice(0, 2), d.slice(2, 5), d.slice(5, 7), d.slice(7, 9)].filter(Boolean)
  return '+998' + (parts.length ? ' ' + parts.join(' ') : ' ')
}

export function isFullUzPhone(value: string): boolean {
  return (value || '').replace(/\D/g, '').length === 12
}

export const UZ_REGIONS = [
  'Toshkent', 'Toshkent viloyati', 'Andijon', 'Buxoro', "Farg'ona", 'Jizzax', 'Namangan', 'Navoiy',
  'Qashqadaryo', 'Samarqand', 'Sirdaryo', 'Surxondaryo', 'Xorazm', "Qoraqalpog'iston",
]

export const PARTNER_APP_LINKS = {
  business: {
    appStore: 'https://apps.apple.com/uz/app/kitobchi-business/id6753881071',
    googlePlay: 'https://play.google.com/store/apps/details?id=com.kitobchi.business.kitobchibusiness',
  },
  express: {
    appStore: 'https://apps.apple.com/uz/app/kitobchi-express/id6754616396',
    googlePlay: 'https://play.google.com/store/apps/details?id=com.kitobchi.express.kitobchiexpress',
  },
}
