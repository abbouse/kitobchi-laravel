// Kitobxonlar klubi darajalari (backend: App\Services\UserPositionService::POSITIONS).
// API kod qaytaradi ("active_reader") — saytda xom kod ko'rinib qolmasin.
const POSITION_LABELS: Record<string, string> = {
  reader: 'Yangi varaq',
  active_reader: 'Sahifa sayyohi',
  book_lover: 'Kitob ishqibozi',
  bibliophile: 'Javon sohibi',
  literary_mentor: 'Kutubxona sultoni',
  // Eski klub darajalari (endi nishon) — qayta hisoblanguncha
  reviewer: 'Kitob ishqibozi',
  collector: 'Kitob ishqibozi',
  book_club_star: 'Kitob ishqibozi',
  market_explorer: 'Kitob ishqibozi',
}

export function positionLabel(code?: string | null): string {
  const key = String(code || '').trim().toLowerCase()
  return POSITION_LABELS[key] || POSITION_LABELS.reader
}

/** Profil/post ostidagi qisqa yozuv: o'zi tanlagan rol, bo'lmasa daraja. */
export function userRoleLabel(user: any): string {
  if (!user) return POSITION_LABELS.reader
  if (user.role_title) return user.role_title
  if (user.position) return positionLabel(user.position)
  if (user.staff_role) return 'Asoschi • Kitobchi'
  return POSITION_LABELS.reader
}
