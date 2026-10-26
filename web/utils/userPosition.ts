// Kitobxonlar klubi darajalari (backend: App\Services\UserPositionService::POSITIONS).
// API kod qaytaradi ("active_reader") — saytda xom kod ko'rinib qolmasin.
const POSITION_LABELS: Record<string, string> = {
  reader: 'Kitobxon',
  active_reader: 'Faol kitobxon',
  book_lover: 'Kitob muxlisi',
  reviewer: 'Sharhlovchi',
  collector: "Kitob yig'uvchi",
  book_club_star: 'Book Club yulduzi',
  market_explorer: 'Market kashfiyotchisi',
  literary_mentor: "Adabiy yo'lboshchi",
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
