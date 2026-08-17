// Boshqaruv panelida (A122\PolicyController) yaratilgan huquqiy/kontent
// sahifalarini (Biz haqimizda, Savol-javoblar, Maxfiylik siyosati va h.k.)
// yuklaydi. Backend: Api\LegalController (routes/api_user.php: legal/{slug}).
//
// MUHIM: agar shu slug bo'yicha hali Policy yaratilmagan bo'lsa, bu yerda
// HECH QANDAY o'ylab topilgan matn ko'rsatilmaydi — sahifa "hali mavjud
// emas" holatini ko'rsatadi va admin uni boshqaruv panelida ("Siyosatlar"
// bo'limi) qo'shishi bilan avtomatik chiqa boshlaydi.
export function usePolicy(slug: string | Ref<string>) {
  const config = useRuntimeConfig()
  const slugRef = isRef(slug) ? slug : ref(slug)

  // lazy: false — bu kontent sahifalari (About/Privacy/Terms va h.k.)
  // uchun SEO muhim, shuning uchun SSR paytida bloklanib, qidiruv
  // botlariga tayyor HTML bilan yetib boradi.
  const { data, pending, error } = useFetch<any>(
    () => `${config.public.apiBase}/v1/kitobchi/legal/${slugRef.value}`,
    {
      key: () => `policy-${slugRef.value}`,
      lazy: false
    }
  )

  const policy = computed(() => {
    if (data.value?.status !== 'success') return null
    return data.value?.data || null
  })

  const notFound = computed(() => {
    return !pending.value && (error.value != null || !policy.value)
  })

  return { policy, pending, notFound }
}
