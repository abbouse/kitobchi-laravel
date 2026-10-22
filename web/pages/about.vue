<template>
  <main class="min-h-screen bg-[#F8FAFC] pb-16 max-md:pb-[80px]">
    <!-- Breadcrumb -->
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto pt-4 md:pt-6">
      <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs text-neutral-400 mb-6 overflow-x-auto whitespace-nowrap py-1">
        <NuxtLink to="/" class="hover:text-primary transition-colors no-underline">Bosh sahifa</NuxtLink>
        <svg class="w-3.5 h-3.5 shrink-0 text-neutral-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        <span class="text-neutral-700 font-medium truncate">Biz haqimizda</span>
      </nav>
    </div>

    <!-- 1. Hero Section -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto mb-10 md:mb-14">
      <div class="relative overflow-hidden rounded-3xl p-8 sm:p-12 md:p-16 text-white shadow-lg"
           style="background: linear-gradient(135deg, #0d2744 0%, #174575 50%, #2062a4 100%);">
        <!-- Background decorative elements -->
        <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/5 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-primary/20 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-3xl">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-xs text-[#A5CCF5] text-xs font-semibold mb-6 border border-white/10">
            <svg class="w-4 h-4 fill-current text-[#60C2FF]" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5v-9l6 4.5-6 4.5z"/></svg>
            <span>Kitobchi platformasi</span>
          </div>
          <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-tight mb-5">
            Mutolaa madaniyatini har bir xonadonga eltuvchi zamonaviy marketpleys
          </h1>
          <p class="text-base sm:text-lg text-[#CEE3FB]/90 leading-relaxed max-w-2xl font-normal">
            Kitobchi — kitobxonlar, yetakchi nashriyotlar va rasmiy sotuvchilarni birlashtiruvchi O‘zbekistondagi ixtisoslashgan kitob va kanselyariya ekotizimidir.
          </p>

          <div class="flex flex-wrap gap-3 mt-8">
            <NuxtLink
              to="/catalog"
              class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white text-neutral-900 font-bold text-sm hover:bg-[#EBF3FC] transition-all no-underline shadow-sm hover:scale-105"
            >
              <span>Kitoblar katalogi</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </NuxtLink>
            <NuxtLink
              to="/club"
              class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-semibold text-sm backdrop-blur-xs transition-all no-underline border border-white/20"
            >
              <span>Kitobxonlar klubi</span>
            </NuxtLink>
          </div>
        </div>
      </div>
    </section>

    <!-- 2. Trust Numbers / Statistics Grid -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto mb-12 md:mb-16">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-2xs border border-neutral-100 flex flex-col justify-between">
          <span class="text-3xl sm:text-4xl font-extrabold text-primary tracking-tight">10 000+</span>
          <p class="text-xs sm:text-sm font-semibold text-neutral-800 mt-2 m-0">Kitob va kanselyariya tovarlari</p>
          <span class="text-xs text-neutral-400 mt-1">Barcha janrlar va yo‘nalishlar</span>
        </div>

        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-2xs border border-neutral-100 flex flex-col justify-between">
          <span class="text-3xl sm:text-4xl font-extrabold text-primary tracking-tight">100+</span>
          <p class="text-xs sm:text-sm font-semibold text-neutral-800 mt-2 m-0">Yetakchi nashriyotlar</p>
          <span class="text-xs text-neutral-400 mt-1">Rasmiy va ishonchli do'konlar</span>
        </div>

        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-2xs border border-neutral-100 flex flex-col justify-between">
          <span class="text-3xl sm:text-4xl font-extrabold text-primary tracking-tight">50 000+</span>
          <p class="text-xs sm:text-sm font-semibold text-neutral-800 mt-2 m-0">Faol kitobxonlar</p>
          <span class="text-xs text-neutral-400 mt-1">Doimiy foydalanuvchilarimiz</span>
        </div>

        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-2xs border border-neutral-100 flex flex-col justify-between">
          <span class="text-3xl sm:text-4xl font-extrabold text-primary tracking-tight">100%</span>
          <p class="text-xs sm:text-sm font-semibold text-neutral-800 mt-2 m-0">Butun O‘zbekiston</p>
          <span class="text-xs text-neutral-400 mt-1">Barcha hududlarga yetkazib berish</span>
        </div>
      </div>
    </section>

    <!-- 3. Bizning Maqsadimiz va Missiyamiz -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto mb-12 md:mb-16">
      <div class="bg-white rounded-3xl p-6 sm:p-10 md:p-12 shadow-2xs border border-neutral-100">
        <div class="max-w-3xl">
          <span class="text-xs font-bold uppercase tracking-wider text-primary mb-2 block">Bizning Missiyamiz</span>
          <h2 class="text-2xl sm:text-3xl font-bold text-neutral-900 tracking-tight leading-snug mb-5">
            Har bir kitobxon o‘zi izlagan durdona asarni oson, tez va qulay narxda topa olishi kerak
          </h2>
          <p class="text-sm sm:text-base text-neutral-600 leading-relaxed mb-4">
            Biz Kitobchi loyihasini kitob xarid qilish jarayonini zamonaviylashtirish, o‘quvchilarni sifatli adabiyotlar bilan ta'minlash hamda mahalliy noshirlar uchun o‘z mahsulotlarini keng ommaga yetkazishning eng samarali maydonini yaratish maqsadida yo‘lga qo‘ydik.
          </p>
          <p class="text-sm sm:text-base text-neutral-600 leading-relaxed m-0">
            Platformamizda nafaqat xarid qilish, balki mutolaa zavqini boshqalar bilan baham ko‘rish, taassurotlar qoldirish, iqtiboslarni saqlash va boshqa kitobsevarlar bilan muloqot qilish uchun qulay imkoniyatlar yaratilgan.
          </p>
        </div>
      </div>
    </section>

    <!-- 4. Nega aynan Kitobchi? (Afzalliklar) -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto mb-12 md:mb-16">
      <div class="text-center max-w-2xl mx-auto mb-8 sm:mb-10">
        <span class="text-xs font-bold uppercase tracking-wider text-primary mb-2 block">Afzalliklarimiz</span>
        <h2 class="text-2xl sm:text-3xl font-bold text-neutral-900 tracking-tight">Nega kitobxonlar bizni tanlashadi?</h2>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Feature 1 -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-neutral-100 flex flex-col items-start">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-primary flex items-center justify-center mb-5 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-neutral-900 mb-2">Original va sifatli kitoblar</h3>
          <p class="text-sm text-neutral-600 leading-relaxed m-0">
            Biz faqat rasmiy nashriyotlar va ishonchli do‘konlar bilan hamkorlik qilamiz. Har bir kitob sifati kafolatlangan.
          </p>
        </div>

        <!-- Feature 2 -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-neutral-100 flex flex-col items-start">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-primary flex items-center justify-center mb-5 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75a1.125 1.125 0 0 0-1.125-1.125h-9A1.125 1.125 0 0 0 2.25 3.75v10.5c0 .621.504 1.125 1.125 1.125h1.5" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-neutral-900 mb-2">Tezkor yetkazib berish</h3>
          <p class="text-sm text-neutral-600 leading-relaxed m-0">
            Toshkent bo‘ylab bir necha soatda "Tezkor yetkazish", viloyatlarga esa qisqa muddatlarda xavfsiz yetkazib beramiz.
          </p>
        </div>

        <!-- Feature 3 -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-neutral-100 flex flex-col items-start">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-primary flex items-center justify-center mb-5 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-neutral-900 mb-2">Qulay to‘lov va Nasiya</h3>
          <p class="text-sm text-neutral-600 leading-relaxed m-0">
            Payme, Click, naqd pul yoki Uzum Nasiya orqali ortiqcha foizlarsiz qulay muddatli to‘lov imkoniyati.
          </p>
        </div>

        <!-- Feature 4 -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-neutral-100 flex flex-col items-start">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-primary flex items-center justify-center mb-5 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.979-3.199a4.5 4.5 0 1 1 9.04 0M15 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-neutral-900 mb-2">Kitobxonlar hamjamiyati</h3>
          <p class="text-sm text-neutral-600 leading-relaxed m-0">
            Kitobxonlar klubida o‘z sevimli iqtiboslaringizni ulashing, sharhlar yozing va yangi kitob do‘stlari orttiring.
          </p>
        </div>

        <!-- Feature 5 -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-neutral-100 flex flex-col items-start">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-primary flex items-center justify-center mb-5 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-neutral-900 mb-2">Zamonaviy mobil ilova</h3>
          <p class="text-sm text-neutral-600 leading-relaxed m-0">
            iOS va Android uchun Kitobchi ilovasi orqali istalgan joyda birgina teginish bilan buyurtma bering.
          </p>
        </div>

        <!-- Feature 6 -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xs border border-neutral-100 flex flex-col items-start">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 text-primary flex items-center justify-center mb-5 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-neutral-900 mb-2">AI aqlli tavsiyalar</h3>
          <p class="text-sm text-neutral-600 leading-relaxed m-0">
            Sun'iy intellekt sizning qiziqishlaringizga mos keluvchi eng saralangan kitoblarni avtomatik taklif etadi.
          </p>
        </div>
      </div>
    </section>

    <!-- 5. Ekotizim va Hamkorlik -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto mb-12 md:mb-16">
      <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-3xl p-8 border border-neutral-100 shadow-2xs flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72m-13.5 0c.315.207.69.33 1.091.33.4 0 .776-.123 1.091-.33m0 0c.315.207.69.33 1.091.33.4 0 .776-.123 1.091-.33m5.455 0c.315.207.69.33 1.091.33.4 0 .776-.123 1.091-.33m0 0c.315.207.69.33 1.091.33.4 0 .776-.123 1.091-.33" /></svg>
            </div>
            <h3 class="text-xl font-bold text-neutral-900 mb-2">Nashriyot va sotuvchilar uchun</h3>
            <p class="text-sm text-neutral-600 leading-relaxed mb-6">
              Kitoblaringizni butun respublika bo‘ylab sotmoqchimisiz? Kitobchi platformasida o‘z do‘koningizni oching, minglab kitobxonlarga erishing va savdoni avtomatlashtiring.
            </p>
          </div>
          <NuxtLink to="/hamkor" class="inline-flex items-center gap-2 text-primary font-bold text-sm hover:underline no-underline">
            <span>Hamkor bo‘lish haqida batafsil</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
          </NuxtLink>
        </div>

        <div class="bg-white rounded-3xl p-8 border border-neutral-100 shadow-2xs flex flex-col justify-between">
          <div>
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
            </div>
            <h3 class="text-xl font-bold text-neutral-900 mb-2">Kuryerlar va logistika</h3>
            <p class="text-sm text-neutral-600 leading-relaxed mb-6">
              Biz bilan erkin grafikda daromad toping. Shahar bo‘ylab kitoblarni kitobxonlarga o‘z vaqtida yetkazib bering va yuqori daromadga ega bo‘ling.
            </p>
          </div>
          <NuxtLink to="/kuryer" class="inline-flex items-center gap-2 text-primary font-bold text-sm hover:underline no-underline">
            <span>Kuryer bo‘lish haqida batafsil</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- 6. Call to Action / Bog'lanish -->
    <section class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <div class="rounded-3xl p-8 sm:p-10 bg-neutral-900 text-white flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="max-w-xl text-center md:text-left">
          <h2 class="text-2xl sm:text-3xl font-bold mb-2">Savollaringiz yoki takliflaringiz bormi?</h2>
          <p class="text-sm sm:text-base text-neutral-400 m-0">
            Biz har doim aloqadamiz. Mijozlarni qo‘llab-quvvatlash jamoamiz sizga mamnuniyat bilan yordam beradi.
          </p>
        </div>
        <div class="flex flex-wrap gap-3 shrink-0">
          <NuxtLink
            to="/contacts"
            class="px-6 py-3 rounded-2xl bg-primary hover:bg-primary-600 text-white font-bold text-sm transition-all no-underline shadow-sm"
          >
            Aloqaga chiqish
          </NuxtLink>
          <a
            :href="SITE_LINKS.telegram"
            target="_blank"
            rel="noopener"
            class="px-6 py-3 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-semibold text-sm transition-all no-underline border border-white/15 flex items-center gap-2"
          >
            <svg class="w-4 h-4 fill-current text-[#2AABEE]" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
            <span>Telegram orqali</span>
          </a>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup lang="ts">
import { SITE_LINKS } from '~/composables/useSiteSettings'

useSeoMeta({
  title: 'Biz haqimizda — Kitobchi platformasi',
  description: 'Kitobchi — O‘zbekistondagi kitobxonlar va nashriyotlarni birlashtiruvchi zamonaviy kitoblar va kanselyariya marketpleysi.'
})

useHead({
  link: [{ rel: 'canonical', href: 'https://kitobchi.com/about' }],
})
</script>
