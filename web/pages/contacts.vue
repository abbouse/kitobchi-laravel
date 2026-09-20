<template>
  <main class="min-h-screen bg-[#F8FAFC] pb-16 max-md:pb-[80px]">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto pt-4 md:pt-6">
      <!-- Breadcrumbs -->
      <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs text-neutral-400 mb-6 overflow-x-auto whitespace-nowrap py-1">
        <NuxtLink to="/" class="hover:text-primary transition-colors">Bosh sahifa</NuxtLink>
        <svg class="w-3.5 h-3.5 shrink-0 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        <span class="text-neutral-700 font-medium truncate">Aloqa</span>
      </nav>

      <!-- Main Content Card -->
      <div class="max-w-4xl mx-auto bg-white rounded-2xl border border-neutral-200/60 p-6 md:p-10 shadow-xs">
        <div class="border-b border-neutral-100 pb-6 mb-8">
          <span class="inline-block text-xs font-semibold text-primary uppercase tracking-wider mb-1.5">Mijozlar xizmati</span>
          <h1 class="text-2xl md:text-3xl font-bold text-neutral-900 tracking-tight">Biz bilan bog'lanish</h1>
          <p class="text-sm text-neutral-500 mt-1">Savollaringiz yoki takliflaringiz bo'lsa, quyidagi aloqa kanallari orqali murojaat qilishingiz mumkin.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 md:gap-12">
          <div class="space-y-6">
            <!-- Manzil -->
            <div class="flex items-start gap-4">
              <div class="w-11 h-11 rounded-xl bg-primary/5 text-primary flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
              </div>
              <div>
                <h2 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1">Manzil</h2>
                <p class="text-base font-medium text-neutral-800">Toshkent shahri, O'zbekiston</p>
              </div>
            </div>

            <!-- Telefon -->
            <div class="flex items-start gap-4">
              <div class="w-11 h-11 rounded-xl bg-primary/5 text-primary flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
              </div>
              <div>
                <h2 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1">Telefon</h2>
                <div v-if="pending" class="h-6 w-36 bg-neutral-100 rounded animate-pulse"></div>
                <a v-else-if="kitobchiPhone" :href="`tel:${kitobchiPhone}`" class="text-base font-medium text-neutral-800 hover:text-primary transition-colors">
                  {{ kitobchiPhone }}
                </a>
                <span v-else class="text-base text-neutral-400">Ko'rsatilmagan</span>
              </div>
            </div>

            <!-- Email -->
            <div class="flex items-start gap-4">
              <div class="w-11 h-11 rounded-xl bg-primary/5 text-primary flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
              </div>
              <div>
                <h2 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1">Email</h2>
                <div v-if="pending" class="h-6 w-44 bg-neutral-100 rounded animate-pulse"></div>
                <a v-else-if="kitobchiEmail" :href="`mailto:${kitobchiEmail}`" class="text-base font-medium text-neutral-800 hover:text-primary transition-colors">
                  {{ kitobchiEmail }}
                </a>
                <span v-else class="text-base text-neutral-400">Ko'rsatilmagan</span>
              </div>
            </div>
          </div>

          <!-- Ijtimoiy tarmoqlar & Ish vaqti -->
          <div class="space-y-6 md:border-l md:border-neutral-100 md:pl-10">
            <div>
              <h2 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">Ijtimoiy tarmoqlar</h2>
              <div class="flex gap-3">
                <a
                  :href="SITE_LINKS.telegram" target="_blank" rel="noopener"
                  class="w-11 h-11 rounded-xl bg-neutral-50 border border-neutral-200/60 flex items-center justify-center text-neutral-700 hover:text-white hover:bg-[#229ED9] hover:border-[#229ED9] transition-all"
                  aria-label="Telegram"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                </a>
                <a
                  :href="SITE_LINKS.instagram" target="_blank" rel="noopener"
                  class="w-11 h-11 rounded-xl bg-neutral-50 border border-neutral-200/60 flex items-center justify-center text-neutral-700 hover:text-white hover:bg-[#E4405F] hover:border-[#E4405F] transition-all"
                  aria-label="Instagram"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
              </div>
            </div>

            <div class="pt-4 border-t border-neutral-100">
              <h2 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Ish tartibi</h2>
              <p class="text-sm text-neutral-700 leading-relaxed">
                Onlayn buyurtmalar: 24/7 qabul qilinadi<br />
                Mijozlarni qo'llab-quvvatlash: Har kuni 09:00 - 21:00
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
const { kitobchiPhone, kitobchiEmail, pending } = useSiteSettings()

useSeoMeta({
  title: 'Aloqa — Kitobchi',
  description: 'Kitobchi mijozlar xizmati bilan bog‘lanish: telefon, email va ijtimoiy tarmoqlar.'
})

useHead({
  link: [{ rel: 'canonical', href: 'https://kitobchi.com/contacts' }],
})
</script>
