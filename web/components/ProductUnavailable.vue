<template>
  <div class="min-h-dvh bg-[#f0f2f5] grow flex items-center justify-center px-4 py-16">
    <!-- Mahsulot topilmadi / do'kon yashirgan / sotuvdan olingan — hammasida bir xil
         matn, pastida sababni bildiruvchi kod (masalan KB-B20). Jadval:
         app/Support/ProductUnavailability.php -->
    <div class="w-full max-w-md bg-white rounded-3xl shadow-sm p-8 text-center">
      <div class="mx-auto mb-5 w-20 h-20 rounded-full bg-secondary-50 flex items-center justify-center text-4xl">📚</div>
      <h1 class="text-xl font-bold text-neutral-900 mb-2">{{ tr.title }}</h1>
      <p class="text-sm text-neutral-500 leading-relaxed mb-6">{{ tr.lead }}</p>
      <div class="flex flex-col sm:flex-row gap-2 justify-center">
        <NuxtLink to="/catalog" class="inline-flex items-center justify-center h-11 px-6 rounded-full bg-primary text-white font-semibold no-underline hover:opacity-90 transition">
          {{ tr.catalog }}
        </NuxtLink>
        <button type="button" class="inline-flex items-center justify-center h-11 px-6 rounded-full bg-secondary-50 text-neutral-700 font-semibold hover:bg-secondary-200 transition" @click="goBack">
          {{ tr.back }}
        </button>
      </div>
      <div v-if="code" class="mt-6 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-neutral-100 text-neutral-500 text-xs font-mono select-all" :title="tr.hint">
        {{ tr.code }}: {{ code }}<template v-if="productId"> · #{{ productId }}</template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ code?: string | null; productId?: string | number | null }>()

const { locale } = useLocale()
const dict = {
  uz: { title: 'Bunday mahsulot mavjud emas', lead: "Mahsulot sotuvda yo'q yoki havola eskirgan. Katalogdan o'xshash kitoblarni topishingiz mumkin.", catalog: "Katalogga o'tish", back: 'Orqaga', code: 'Kod', hint: "Qo'llab-quvvatlash xizmatiga murojaat qilsangiz, shu kodni yuboring" },
  ru: { title: 'Такого товара не существует', lead: 'Товар снят с продажи или ссылка устарела. Похожие книги можно найти в каталоге.', catalog: 'Перейти в каталог', back: 'Назад', code: 'Код', hint: 'При обращении в поддержку укажите этот код' },
  en: { title: 'This product does not exist', lead: 'The product is no longer available or the link is outdated. You can find similar books in the catalog.', catalog: 'Go to catalog', back: 'Back', code: 'Code', hint: 'Share this code if you contact support' },
  ja: { title: 'この商品は存在しません', lead: '販売終了したか、リンクが古くなっています。カタログで似た本を探せます。', catalog: 'カタログへ', back: '戻る', code: 'コード', hint: 'サポートへのお問い合わせ時にこのコードをお伝えください' },
} as const
const tr = computed(() => dict[(locale.value as keyof typeof dict)] || dict.uz)
const code = computed(() => props.code || null)

useHead({
  title: () => `${tr.value.title} — Kitobchi`,
  meta: [{ name: 'robots', content: 'noindex' }],
})

function goBack() {
  if (typeof window !== 'undefined' && window.history.length > 1) window.history.back()
  else navigateTo('/')
}
</script>
