<template>
  <main class="max-md:pb-[71px]">
    <div class="py-10 md:py-20">
      <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        <div class="max-w-4xl mx-auto">
          <h1 class="text-3xl md:text-4xl font-bold mb-10 text-primary">Aloqa</h1>
          <div class="grid md:grid-cols-2 gap-12">
            <div class="space-y-8">
              <div>
                <h2 class="text-sm font-semibold text-neutral-400 uppercase tracking-wider mb-2">Manzil</h2>
                <p class="text-lg text-neutral-800">Toshkent shahri, O'zbekiston</p>
              </div>

              <div v-if="pending">
                <div class="h-3 w-16 bg-secondary-100 rounded mb-3 animate-pulse"></div>
                <div class="h-5 w-40 bg-secondary-100 rounded animate-pulse"></div>
              </div>
              <div v-else-if="kitobchiPhone">
                <h2 class="text-sm font-semibold text-neutral-400 uppercase tracking-wider mb-2">Telefon</h2>
                <a :href="`tel:${kitobchiPhone}`" class="text-lg text-neutral-800 hover:text-primary/75 transition-colors">{{ kitobchiPhone }}</a>
              </div>

              <div v-if="kitobchiEmail">
                <h2 class="text-sm font-semibold text-neutral-400 uppercase tracking-wider mb-2">Email</h2>
                <a :href="`mailto:${kitobchiEmail}`" class="text-lg text-neutral-800 hover:text-primary/75 transition-colors">{{ kitobchiEmail }}</a>
              </div>

              <div>
                <h2 class="text-sm font-semibold text-neutral-400 uppercase tracking-wider mb-2">Ijtimoiy tarmoqlar</h2>
                <div class="flex gap-4">
                  <a
                    :href="SITE_LINKS.telegram" target="_blank" rel="noopener"
                    class="w-10 h-10 rounded-full bg-secondary-100 flex items-center justify-center hover:bg-secondary-400 transition-colors"
                    aria-label="Telegram"
                  >
                    <svg class="w-5 h-5 text-neutral-800" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                  </a>
                  <a
                    :href="SITE_LINKS.instagram" target="_blank" rel="noopener"
                    class="w-10 h-10 rounded-full bg-secondary-100 flex items-center justify-center hover:bg-secondary-400 transition-colors"
                    aria-label="Instagram"
                  >
                    <svg class="w-5 h-5 text-neutral-800" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                  </a>
                </div>
              </div>
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
</script>
