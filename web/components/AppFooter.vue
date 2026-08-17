<template>
  <footer class="relative bg-primary text-white pt-12 md:pt-20 pb-28 md:pb-10 overflow-hidden">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto relative z-10">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
        <!-- 1. Umumiy -->
        <div class="space-y-4">
          <h3 class="text-lg font-bold text-white uppercase tracking-wider mb-3">Umumiy</h3>
          <ul class="space-y-2.5 text-sm text-gray-300">
            <li><NuxtLink to="/about" class="hover:text-white transition-colors">Biz haqimizda</NuxtLink></li>
            <li><NuxtLink to="/terms" class="hover:text-white transition-colors">Foydalanish shartlari</NuxtLink></li>
            <li><NuxtLink to="/privacy" class="hover:text-white transition-colors">Maxfiylik siyosati</NuxtLink></li>
            <li><NuxtLink to="/offer" class="hover:text-white transition-colors">Ommaviy oferta</NuxtLink></li>
          </ul>
        </div>

        <!-- 2. Kataloglar -->
        <div class="space-y-4">
          <h3 class="text-lg font-bold text-white uppercase tracking-wider mb-3">Kataloglar</h3>
          <ul class="space-y-2.5 text-sm text-gray-300">
            <li><NuxtLink to="/catalog?type=book" class="hover:text-white transition-colors">Barcha kitoblar</NuxtLink></li>
            <li><NuxtLink to="/catalog?sort=new" class="hover:text-white transition-colors">Yangi kelganlar</NuxtLink></li>
            <li><NuxtLink to="/catalog?sort=popular" class="hover:text-white transition-colors">Ommabop kitoblar</NuxtLink></li>
            <li><NuxtLink to="/catalog?type=stationery" class="hover:text-white transition-colors">Kanselyariya</NuxtLink></li>
          </ul>
        </div>

        <!-- 3. Mijozlar xizmati -->
        <div class="space-y-4">
          <h3 class="text-lg font-bold text-white uppercase tracking-wider mb-3">Mijozlar xizmati</h3>
          <ul class="space-y-2.5 text-sm text-gray-300">
            <li><NuxtLink to="/faq" class="hover:text-white transition-colors">Ko‘p beriladigan savollar</NuxtLink></li>
            <li><NuxtLink to="/delivery" class="hover:text-white transition-colors">Yetkazib berish va to‘lov</NuxtLink></li>
            <li><NuxtLink to="/contacts" class="hover:text-white transition-colors">Bog‘lanish</NuxtLink></li>
          </ul>
        </div>

        <!-- 4. Ijtimoiy tarmoqlar & Ilova -->
        <div class="space-y-4">
          <h3 class="text-lg font-bold text-white uppercase tracking-wider mb-3">Ijtimoiy tarmoqlar</h3>
          <div class="flex items-center gap-3 mb-4">
            <a href="https://t.me/kitobchi_com" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
            </a>
            <a href="https://instagram.com/kitobchi_com" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            </a>
          </div>
          <div class="text-sm text-gray-300">
            Telefon: <a href="tel:+998712000000" class="text-white font-semibold hover:underline">+998 (71) 200-00-00</a>
          </div>
        </div>
      </div>

      <div class="border-t border-white/10 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-400 gap-4">
        <div>© {{ new Date().getFullYear() }} Kitobchi. Barcha huquqlar himoyalangan.</div>
        <div class="flex items-center gap-4">
          <NuxtLink to="/privacy" class="hover:text-white transition-colors">Maxfiylik</NuxtLink>
          <NuxtLink to="/terms" class="hover:text-white transition-colors">Oferta</NuxtLink>
        </div>
      </div>
    </div>
  </footer>
</template>
