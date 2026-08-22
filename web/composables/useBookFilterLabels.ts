// Backend (SearchController::bookFilterOptions) `langType`/`coverType`
// ustunlarining XOM (raw) qiymatlarini ("latin", "cyrillic", "soft", "hard")
// to'g'ridan-to'g'ri qaytaradi — checkbox ro'yxatida foydalanuvchiga
// inglizcha texnik so'z sifatida ko'rinib qolmasligi uchun bu yerda
// o'zbekcha, tushunarli nomlarga o'giriladi. Kelajakda DBda kutilmagan
// yangi qiymat paydo bo'lsa (masalan boshqa yozuv turi) — xom qiymatning
// o'zi fallback sifatida ko'rsatiladi (checkbox "yo'qolib qolmaydi").
const LANG_TYPE_LABELS: Record<string, string> = {
  latin: 'Lotin',
  cyrillic: 'Kirill',
}

const COVER_TYPE_LABELS: Record<string, string> = {
  soft: 'Yumshoq muqova',
  hard: 'Qattiq muqova',
}

export function useBookFilterLabels() {
  function langTypeLabel(value: string): string {
    return LANG_TYPE_LABELS[value] ?? value
  }

  function coverTypeLabel(value: string): string {
    return COVER_TYPE_LABELS[value] ?? value
  }

  return { langTypeLabel, coverTypeLabel }
}
