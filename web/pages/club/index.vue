<template>
  <div class="py-4 sm:py-6 md:py-8">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Mobile Only Page Header (Hidden on Desktop) -->
      <div class="lg:hidden flex items-center justify-between gap-4 mb-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-extrabold text-neutral-900 tracking-tight m-0">
            Kitobxonlar Klubi
          </h1>
          <p class="text-xs text-neutral-500 mt-0.5 m-0">
            Samimiy taassurotlar va kitob muhokamalari
          </p>
        </div>
      </div>

      <!-- Mobile Only Themes / Category Pills (Hidden on Desktop) -->
      <div class="lg:hidden mb-4">
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1 -mx-4 px-4 sm:mx-0 sm:px-0">
          <button
            type="button"
            @click="selectTheme(null)"
            :class="[
              'shrink-0 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer border flex items-center gap-1',
              !activeThemeSlug
                ? 'bg-brand-gradient text-white border-transparent shadow-xs'
                : 'bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 border-neutral-200/80'
            ]"
          >
            Barchasi
          </button>

          <button
            v-for="t in trendingThemes"
            :key="t.id"
            type="button"
            @click="selectTheme(t)"
            :class="[
              'shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer border flex items-center gap-1',
              activeThemeSlug === (t.slug || String(t.id))
                ? 'bg-brand-gradient text-white border-transparent shadow-xs'
                : 'bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 border-neutral-200/80'
            ]"
          >
            <span :class="activeThemeSlug === (t.slug || String(t.id)) ? 'text-white/80' : 'text-primary/70'">#</span>
            <span>{{ t.name }}</span>
            <span v-if="t.usage_count" :class="activeThemeSlug === (t.slug || String(t.id)) ? 'text-white/80' : 'text-neutral-400'" class="text-[10px]">
              {{ t.usage_count }}
            </span>
            <AppEmoji v-if="t.firework || (t.usage_count && t.usage_count >= 5)" name="fire" :size="12" class="ml-0.5" />
          </button>
        </div>
      </div>

      <!-- MAIN CONTENT: 2 COLUMNS (Feed + Right Sidebar with Post Composer) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
        <!-- LEFT: FEED (8 cols) -->
        <main class="lg:col-span-8 space-y-4">
          <!-- MOBILE COMPOSER BAR (Matching Mobile App Screenshot) -->
          <div
            class="lg:hidden bg-white rounded-3xl p-3 border border-neutral-200/80 shadow-2xs transition-shadow"
            @click="!authStore.isAuthenticated && authStore.openAuthModal()"
          >
            <div class="flex items-center gap-2.5">
              <!-- Current User Avatar on Left -->
              <AppAvatar
                :src="authStore.user?.avatar || authStore.user?.photo"
                :name="currentUserDisplayName"
                :user-id="authStore.user?.id"
                size="w-9 h-9 text-xs"
              />

              <!-- Pill Input container (Grows up to 4 lines) -->
              <div class="flex-1 min-w-0 bg-neutral-100/90 rounded-full py-1.5 px-3 flex items-center gap-2 transition-all focus-within:ring-2 focus-within:ring-primary/20 focus-within:bg-white border border-transparent focus-within:border-primary/30">
                <textarea
                  v-model="composerText"
                  ref="mobileTextareaRef"
                  @input="handleAutoResize"
                  :disabled="!authStore.isAuthenticated"
                  rows="1"
                  placeholder="Bu asarni o'qishga arziydimi?"
                  class="w-full bg-transparent border-none text-xs sm:text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-hidden resize-none py-1 leading-normal max-h-24 overflow-y-auto"
                ></textarea>

                <!-- Photo button (Inside pill, matching app screenshot) -->
                <label
                  class="shrink-0 p-1 text-primary hover:text-primary-hover cursor-pointer transition-colors"
                  title="Rasm biriktirish"
                  @click.stop="!authStore.isAuthenticated && authStore.openAuthModal()"
                >
                  <input
                    v-if="authStore.isAuthenticated"
                    type="file"
                    accept="image/*"
                    multiple
                    class="hidden"
                    @change="handleImageSelect"
                  />
                  <svg class="w-5 h-5 text-neutral-400 hover:text-primary transition-colors" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                  </svg>
                </label>
              </div>

              <!-- Send Button (Active / Visible when text or image exists) -->
              <button
                type="button"
                @click.stop="submitPost"
                :disabled="isSubmitting || (!composerText.trim() && selectedImages.length === 0)"
                class="shrink-0 w-9 h-9 rounded-full bg-primary hover:bg-primary-hover text-white flex items-center justify-center transition-all disabled:opacity-40 disabled:hover:bg-primary border-none cursor-pointer shadow-2xs"
                title="Yuborish"
              >
                <svg v-if="isSubmitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg v-else class="w-4 h-4 transform rotate-45 -mr-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
              </button>
            </div>

            <!-- Attached image thumbnail row for mobile -->
            <div v-if="imagePreviews.length > 0" class="flex items-center gap-2 mt-2 pt-2 border-t border-neutral-100 overflow-x-auto no-scrollbar">
              <div
                v-for="(img, idx) in imagePreviews"
                :key="idx"
                class="relative w-14 h-14 rounded-xl overflow-hidden shrink-0 border border-neutral-200"
              >
                <img :src="img" class="w-full h-full object-cover" />
                <button
                  type="button"
                  @click.stop="removeImage(idx)"
                  class="absolute top-0.5 right-0.5 w-4 h-4 rounded-full bg-neutral-900/80 text-white flex items-center justify-center text-[10px] border-none cursor-pointer"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>

          <!-- Active filter banner if selected -->
          <div v-if="activeThemeName" class="flex items-center justify-between px-1 mb-2">
            <div class="flex items-center gap-2 text-xs sm:text-sm text-neutral-600">
              <span>Mavzu:</span>
              <span class="font-bold text-neutral-900">#{{ activeThemeName }}</span>
            </div>
            <button
              type="button"
              @click="selectTheme(null)"
              class="text-xs text-primary hover:text-primary-hover underline bg-transparent border-none cursor-pointer p-0"
            >
              Barcha fikrlarni ko‘rish
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="isLoading" class="space-y-4">
            <div
              v-for="n in 3"
              :key="'shimmer-' + n"
              class="bg-white rounded-3xl p-6 border border-neutral-200/60 animate-pulse space-y-4"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-neutral-200"></div>
                <div class="space-y-2 flex-1">
                  <div class="h-3.5 bg-neutral-200 rounded w-1/4"></div>
                  <div class="h-3 bg-neutral-100 rounded w-1/6"></div>
                </div>
              </div>
              <div class="space-y-2">
                <div class="h-3.5 bg-neutral-100 rounded w-full"></div>
                <div class="h-3.5 bg-neutral-100 rounded w-4/5"></div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div
            v-else-if="posts.length === 0"
            class="bg-white rounded-3xl p-10 text-center border border-neutral-200/70"
          >
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-neutral-100 text-neutral-400 flex items-center justify-center">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
              </svg>
            </div>
            <h3 class="text-base font-bold text-neutral-900 mb-1">Bu mavzuda hozircha fikrlar yo‘q</h3>
            <p class="text-xs text-neutral-500 max-w-sm mx-auto mb-4">
              Birinchi bo‘lib o‘z taassurotingizni qoldiring!
            </p>
          </div>

          <!-- Posts Feed -->
          <div v-else class="space-y-4">
            <BookClubCard
              v-for="post in posts"
              :key="post.id"
              :post="post"
            />
          </div>

          <!-- Load More -->
          <div v-if="hasMore && !isLoading" class="text-center pt-3">
            <button
              type="button"
              @click="loadMore"
              :disabled="isLoadingMore"
              class="px-6 py-2.5 rounded-full bg-white hover:bg-neutral-100 text-neutral-800 font-bold text-xs sm:text-sm border border-neutral-200/80 transition-all cursor-pointer shadow-2xs inline-flex items-center gap-2"
            >
              <svg
                v-if="isLoadingMore"
                class="animate-spin -ml-1 mr-1 h-4 w-4 text-primary"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ isLoadingMore ? 'Yuklanmoqda...' : 'Ko‘proq yuklash' }}</span>
            </button>
          </div>
        </main>

        <!-- RIGHT: SIDEBAR (4 cols) with NEW POST COMPOSER FOR DESKTOP -->
        <aside class="lg:col-span-4 space-y-4 lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto no-scrollbar">
          <!-- DESKTOP POST COMPOSER CARD -->
          <div
            class="hidden lg:block bg-white rounded-3xl p-5 border border-neutral-200/70 shadow-2xs relative transition-all"
            :class="{ 'cursor-pointer hover:border-primary/40': !authStore.isAuthenticated }"
            @click="!authStore.isAuthenticated && authStore.openAuthModal()"
          >
            <!-- Author Header -->
            <div class="flex items-center gap-3 mb-3">
              <AppAvatar
                :src="authStore.user?.avatar || authStore.user?.photo"
                :name="currentUserDisplayName"
                :user-id="authStore.user?.id"
                size="w-10 h-10 text-sm"
              />

              <div class="min-w-0 flex-1">
                <div class="font-bold text-sm text-neutral-900 truncate">
                  {{ authStore.user ? currentUserDisplayName : 'Yangi post yozish' }}
                </div>
                <div class="text-[11px] text-neutral-400">
                  {{ authStore.user ? 'Kitobxonlar bilan samimiy fikr ulashing' : 'Fikr bildirish uchun tizimga kiring' }}
                </div>
              </div>
            </div>

            <!-- Post Textarea -->
            <div class="mb-3">
              <textarea
                v-model="composerText"
                :disabled="!authStore.isAuthenticated"
                rows="3"
                placeholder="Qaysi kitobni o‘qiyapsiz? Fikrlaringiz va tavsiyalaringizni yozing..."
                class="w-full rounded-2xl border border-neutral-200/90 p-3 text-sm focus:outline-hidden focus:border-primary resize-none bg-neutral-50/50 focus:bg-white transition-colors"
              ></textarea>
            </div>

            <!-- Image Previews Grid -->
            <div v-if="imagePreviews.length > 0" class="flex flex-wrap gap-2 mb-3">
              <div
                v-for="(img, idx) in imagePreviews"
                :key="idx"
                class="relative w-16 h-16 rounded-xl overflow-hidden border border-neutral-200 group"
              >
                <img :src="img" class="w-full h-full object-cover" />
                <button
                  type="button"
                  @click.stop="removeImage(idx)"
                  class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/70 text-white flex items-center justify-center text-xs hover:bg-black border-none cursor-pointer"
                  title="O‘chirish"
                >
                  ✕
                </button>
              </div>
            </div>

            <!-- Composer Actions Toolbar -->
            <div class="flex items-center justify-between pt-2 border-t border-neutral-100">
              <label
                class="inline-flex items-center gap-1.5 text-xs text-neutral-600 hover:text-primary cursor-pointer px-2.5 py-1.5 rounded-xl hover:bg-neutral-50 transition-colors font-medium"
                @click.stop="!authStore.isAuthenticated && authStore.openAuthModal()"
              >
                <input
                  v-if="authStore.isAuthenticated"
                  type="file"
                  accept="image/*"
                  multiple
                  class="hidden"
                  @change="handleImageSelect"
                />
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <span>Rasm biriktirish</span>
              </label>

              <button
                type="button"
                @click.stop="submitPost"
                :disabled="isSubmitting || (!composerText.trim() && selectedImages.length === 0)"
                class="px-5 py-2 rounded-full bg-primary hover:bg-primary-hover text-white text-xs font-bold disabled:opacity-40 transition-colors border-none cursor-pointer shadow-2xs inline-flex items-center gap-1.5"
              >
                <svg v-if="isSubmitting" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>E'lon qilish</span>
              </button>
            </div>
          </div>

          <!-- Active Members / Faol kitobxonlar Card -->
          <div v-if="activeMembers.length > 0" class="bg-white rounded-3xl p-5 sm:p-6 border border-neutral-200/70 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-bold text-sm sm:text-base text-neutral-900 m-0 flex items-center gap-2">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span>Faol kitobxonlar</span>
              </h3>
            </div>

            <div class="space-y-3">
              <div
                v-for="member in activeMembers.slice(0, 5)"
                :key="member.id"
                class="flex items-center justify-between gap-3"
              >
                <NuxtLink
                  :to="`/club/user/${member.id}`"
                  class="flex items-center gap-2.5 min-w-0 no-underline group/mem"
                >
                  <AppAvatar
                    :src="member.avatar"
                    :name="member.name"
                    :user-id="member.id"
                    size="w-9 h-9 text-xs"
                    :badge="member.role_emoji"
                  />

                  <div class="min-w-0">
                    <div class="flex items-center gap-1">
                      <span class="font-bold text-xs sm:text-sm text-neutral-900 group-hover/mem:text-primary transition-colors truncate">
                        {{ member.name }} {{ member.lastname || '' }}
                      </span>
                      <svg
                        v-if="member.isVerified"
                        class="w-3.5 h-3.5 text-primary shrink-0"
                        viewBox="0 0 24 24" fill="currentColor"
                      >
                        <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div class="text-[11px] text-neutral-400 truncate">
                      {{ member.role_title || member.position || (member.posts_count ? `${member.posts_count} ta fikr` : 'Kitobxon') }}
                    </div>
                  </div>
                </NuxtLink>

                <span v-if="member.posts_count" class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">
                  {{ member.posts_count }} post
                </span>
              </div>
            </div>
          </div>

          <!-- Trending Topics / Ommabop mavzular Card -->
          <div v-if="trendingThemes.length > 0" class="bg-white rounded-3xl p-5 sm:p-6 border border-neutral-200/70 shadow-2xs">
            <div class="flex items-center justify-between mb-3.5">
              <h3 class="font-bold text-sm sm:text-base text-neutral-900 m-0 flex items-center gap-2">
                <AppEmoji name="fire" :size="16" />
                <span>Ommabop mavzular</span>
              </h3>
              <button
                v-if="activeThemeSlug"
                type="button"
                @click="selectTheme(null)"
                class="text-[11px] font-semibold text-primary hover:underline bg-transparent border-none cursor-pointer p-0"
              >
                Tozalash
              </button>
            </div>

            <!-- Professional Chips Grid -->
            <div class="flex flex-wrap gap-2">
              <button
                v-for="t in trendingThemes"
                :key="'side-' + t.id"
                type="button"
                @click="selectTheme(t)"
                :class="[
                  'group inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer border',
                  activeThemeSlug === (t.slug || String(t.id))
                    ? 'bg-primary text-white border-primary shadow-xs'
                    : 'bg-neutral-50/80 hover:bg-primary/5 text-neutral-700 hover:text-primary border-neutral-200/80 hover:border-primary/30'
                ]"
              >
                <span
                  :class="[
                    'font-bold transition-colors',
                    activeThemeSlug === (t.slug || String(t.id)) ? 'text-white/80' : 'text-primary/70 group-hover:text-primary'
                  ]"
                >
                  #
                </span>
                <span>{{ t.name }}</span>
                <span
                  v-if="t.usage_count"
                  :class="[
                    'text-[10px] ml-0.5 font-normal transition-colors',
                    activeThemeSlug === (t.slug || String(t.id)) ? 'text-white/80' : 'text-neutral-400 group-hover:text-primary/70'
                  ]"
                >
                  {{ t.usage_count }}
                </span>
                <AppEmoji
                  v-if="t.firework || (t.usage_count && t.usage_count >= 5)"
                  name="fire"
                  :size="12"
                  class="ml-0.5"
                />
              </button>
            </div>
          </div>

          <!-- App banner -->
          <div class="bg-white rounded-3xl p-5 border border-neutral-200/70 shadow-2xs">
            <span class="text-[10px] uppercase font-bold text-primary tracking-wider block mb-1">Kitobchi ilovasi</span>
            <h4 class="font-bold text-sm text-neutral-900 mb-1">Mobil ilovada o‘qing</h4>
            <p class="text-xs text-neutral-500 leading-relaxed mb-3">
              Klubda xabarlar almashing, yangi kitoblardan xabardor bo‘ling.
            </p>
            <div class="flex items-center gap-2">
              <a
                href="https://play.google.com/store/apps/details?id=uz.kitobchi.app"
                target="_blank"
                rel="noopener"
                class="px-3 py-1.5 rounded-xl bg-neutral-900 text-white font-semibold text-xs no-underline hover:bg-neutral-800 transition-colors"
              >
                Google Play
              </a>
              <a
                href="https://apps.apple.com/uz/app/kitobchi/id6474136979"
                target="_blank"
                rel="noopener"
                class="px-3 py-1.5 rounded-xl bg-neutral-900 text-white font-semibold text-xs no-underline hover:bg-neutral-800 transition-colors"
              >
                App Store
              </a>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { resolveImageUrl } from '~/utils/productImage'

const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Current logged in user details
const currentUserDisplayName = computed(() => {
  const u = authStore.user
  if (!u) return 'Kitobxon'
  if (u.name && u.lastname) return `${u.name} ${u.lastname}`
  return u.name || u.username || 'Kitobxon'
})

const currentUserAvatar = computed(() => {
  const av = authStore.user?.avatar || authStore.user?.photo
  if (!av) return null
  return resolveImageUrl(av)
})

const currentUserInitials = computed(() => {
  const name = currentUserDisplayName.value.trim()
  const parts = name.split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
})

// Unified Composer State
const composerText = ref('')
const selectedImages = ref<File[]>([])
const imagePreviews = ref<string[]>([])
const isSubmitting = ref(false)
const mobileTextareaRef = ref<HTMLTextAreaElement | null>(null)

function handleAutoResize(e: Event) {
  const target = e.target as HTMLTextAreaElement
  target.style.height = 'auto'
  target.style.height = `${Math.min(target.scrollHeight, 120)}px`
}

function handleImageSelect(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files || input.files.length === 0) return
  const files = Array.from(input.files)
  selectedImages.value = [...selectedImages.value, ...files]
  files.forEach((file) => {
    imagePreviews.value.push(URL.createObjectURL(file))
  })
  input.value = ''
}

function removeImage(idx: number) {
  selectedImages.value.splice(idx, 1)
  imagePreviews.value.splice(idx, 1)
}

// Active Theme State
const activeThemeSlug = ref<string | null>((route.query.theme as string) || null)

// SSR Fetch: Feed + Themes + Active Members
const { data: initialData } = await useAsyncData('book-club-page', async () => {
  const [feedRes, themesRes, membersRes] = await Promise.all([
    $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club`, {
      query: {
        per_page: 15,
        theme: activeThemeSlug.value || undefined
      }
    }).catch(() => ({ data: [], meta: {} })),
    $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/themes`).catch(() => ({ data: [] })),
    $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/suggested-users`).catch(() => ({ data: [] }))
  ])

  return {
    posts: feedRes?.data || [],
    meta: feedRes?.meta || {},
    themes: themesRes?.data || [],
    activeMembers: membersRes?.data || []
  }
})

const posts = ref<any[]>(initialData.value?.posts || [])
const themes = computed(() => initialData.value?.themes || [])
const activeMembers = computed(() => initialData.value?.activeMembers || [])

// Trending themes limited to top 8 most popular/active
const trendingThemes = computed(() => {
  const all = themes.value || []
  const sorted = [...all].sort((a: any, b: any) => (b.usage_count || 0) - (a.usage_count || 0))
  const withUsage = sorted.filter((t: any) => (t.usage_count || 0) > 0)
  const source = withUsage.length >= 3 ? withUsage : sorted
  return source.slice(0, 8)
})

const currentPage = ref(initialData.value?.meta?.current_page || 1)
const lastPage = ref(initialData.value?.meta?.last_page || 1)
const hasMore = computed(() => currentPage.value < lastPage.value)

const activeThemeName = computed(() => {
  if (!activeThemeSlug.value) return null
  const found = themes.value.find((t: any) => t.slug === activeThemeSlug.value || String(t.id) === activeThemeSlug.value)
  return found?.name || activeThemeSlug.value
})

const isLoading = ref(false)
const isLoadingMore = ref(false)

async function selectTheme(theme: any | null) {
  if (!theme) {
    activeThemeSlug.value = null
    router.push({ query: {} })
  } else {
    activeThemeSlug.value = theme.slug || String(theme.id)
    router.push({ query: { theme: activeThemeSlug.value } })
  }

  isLoading.value = true
  try {
    const url = activeThemeSlug.value
      ? `${config.public.apiBase}/v1/kitobchi/book_club/themes/${activeThemeSlug.value}/posts`
      : `${config.public.apiBase}/v1/kitobchi/book_club`

    const res: any = await $fetch(url, {
      query: { per_page: 15 }
    })

    posts.value = res?.data || []
    currentPage.value = res?.meta?.current_page || 1
    lastPage.value = res?.meta?.last_page || 1
  } catch {
    posts.value = []
  } finally {
    isLoading.value = false
  }
}

async function loadMore() {
  if (isLoadingMore.value || !hasMore.value) return
  isLoadingMore.value = true

  try {
    const nextPage = currentPage.value + 1
    const url = activeThemeSlug.value
      ? `${config.public.apiBase}/v1/kitobchi/book_club/themes/${activeThemeSlug.value}/posts`
      : `${config.public.apiBase}/v1/kitobchi/book_club`

    const res: any = await $fetch(url, {
      query: { per_page: 15, page: nextPage }
    })

    const newItems = res?.data || []
    posts.value = [...posts.value, ...newItems]
    currentPage.value = nextPage
    lastPage.value = res?.meta?.last_page || lastPage.value
  } finally {
    isLoadingMore.value = false
  }
}

async function submitPost() {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }

  const text = composerText.value.trim()
  if (!text && selectedImages.value.length === 0) return
  if (isSubmitting.value) return

  isSubmitting.value = true

  try {
    const token = useCookie('kc_token').value
    const formData = new FormData()
    formData.append('post_text', text)
    formData.append('text', text)
    formData.append('product_type', 'book')
    selectedImages.value.forEach((file) => {
      formData.append('images[]', file)
    })

    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/new`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: formData
    })

    composerText.value = ''
    selectedImages.value = []
    imagePreviews.value = []
    if (mobileTextareaRef.value) {
      mobileTextareaRef.value.style.height = 'auto'
    }

    if (res?.data) {
      posts.value = [res.data, ...posts.value]
    } else {
      await selectTheme(null)
    }
  } catch (err: any) {
    alert(err?.data?.message || 'Xatolik yuz berdi, qayta urinib ko‘ring.')
  } finally {
    isSubmitting.value = false
  }
}

useSeoMeta({
  title: 'Kitobxonlar Klubi — Kitob taqrizlari va fikrlar | Kitobchi',
  description: 'Kitobxonlar hamjamiyati: kitoblar bo‘yicha samimiy taqrizlar, baholar, iqtiboslar va jonli muhokamalar. Sevimli asaringiz haqida fikr bildiring.',
  ogTitle: 'Kitobxonlar Klubi — Kitobchi',
  ogDescription: 'Kitobxonlar hamjamiyati: samimiy taqrizlar, tavsiyalar va muhokamalar.',
  ogImage: 'https://kitobchi.com/images/og-image.png',
  ogType: 'website'
})

useHead({
  link: [
    { rel: 'canonical', href: 'https://kitobchi.com/club' }
  ]
})
</script>
