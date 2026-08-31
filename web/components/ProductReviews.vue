<template>
  <section class="mt-12 pt-8 border-t border-gray-100">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
      <div class="flex items-center gap-3">
        <h2 class="text-xl sm:text-2xl font-bold text-neutral-900 m-0">
          Kitobxonlar fikrlari va taqrizlar
        </h2>
        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary-100 text-primary">
          {{ reviews.length }}
        </span>
      </div>

      <button
        type="button"
        @click="handleOpenForm"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-secondary-200 hover:bg-secondary-400 text-primary transition-all border-none cursor-pointer"
      >
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
        </svg>
        <span>Fikr bildirish</span>
      </button>
    </div>

    <!-- Review Form (expandable) -->
    <div v-if="isFormOpen" class="mb-8 p-5 sm:p-6 rounded-3xl bg-secondary-100 border border-secondary-200">
      <h3 class="text-base font-bold text-neutral-900 mb-3">
        Ushbu {{ type === 'stationery' ? 'mahsulot' : 'kitob' }} haqida taqrizingiz
      </h3>

      <div v-if="!authStore.isAuthenticated" class="text-center py-6 bg-white rounded-2xl p-4">
        <p class="text-sm text-neutral-600 mb-3">Fikr yoki taqriz qoldirish uchun profilingizga kiring</p>
        <button
          type="button"
          @click="authStore.isAuthModalOpen = true"
          class="px-6 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold border-none cursor-pointer hover:bg-primary-600 transition-colors"
        >
          Kirish
        </button>
      </div>

      <form v-else @submit.prevent="submitReview" class="space-y-4">
        <div>
          <textarea
            v-model="newReviewText"
            rows="3"
            required
            placeholder="Kitob haqida taassurotlaringiz, qaysi qismlari yoqqani yoki foydasi haqida yozing..."
            class="w-full p-4 rounded-2xl bg-white border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary outline-none text-sm text-neutral-800 transition-all resize-y" style="min-height: 100px;"
          ></textarea>
        </div>

        <div class="flex items-center justify-between flex-wrap gap-3">
          <p class="text-xs text-neutral-400">
            Fikringiz Book Club hamjamiyatiga ham joylanadi.
          </p>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="isFormOpen = false"
              class="px-4 py-2 rounded-xl text-sm font-medium text-neutral-500 hover:bg-secondary-200 transition-colors border-none bg-transparent cursor-pointer"
            >
              Bekor qilish
            </button>
            <button
              type="submit"
              :disabled="isSubmitting || !newReviewText.trim()"
              class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-600 disabled:opacity-50 text-white text-sm font-semibold border-none cursor-pointer transition-colors flex items-center gap-2"
            >
              <svg v-if="isSubmitting" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
              </svg>
              <span>{{ isSubmitting ? 'Yuborilmoqda...' : 'Yuborish' }}</span>
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Loading Shimmer State -->
    <div v-if="isReviewsLoading" class="space-y-4">
      <div v-for="n in 2" :key="'rev-skel-' + n" class="p-5 sm:p-6 rounded-3xl bg-secondary-100 border border-gray-100">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 rounded-full shimmer shrink-0"></div>
          <div class="space-y-1.5 flex-1">
            <div class="h-4 w-32 rounded shimmer"></div>
            <div class="h-3 w-20 rounded shimmer"></div>
          </div>
        </div>
        <div class="space-y-2 mt-2">
          <div class="h-3.5 w-full rounded shimmer"></div>
          <div class="h-3.5 w-4/5 rounded shimmer"></div>
        </div>
      </div>
    </div>

    <!-- Reviews List -->
    <div v-else-if="reviews.length > 0" class="space-y-4">
      <div
        v-for="item in reviews"
        :key="item.id"
        class="p-5 sm:p-6 rounded-3xl bg-secondary-100 border border-gray-100 transition-all"
      >
        <div class="flex items-start justify-between gap-3 mb-3">
          <div class="flex items-center gap-3">
            <!-- User Avatar -->
            <div class="w-10 h-10 rounded-full bg-primary-100 text-primary font-bold flex items-center justify-center overflow-hidden shrink-0">
              <img
                v-if="item.user?.avatar || item.user_avatar"
                :src="resolveImg(item.user?.avatar || item.user_avatar)"
                :alt="item.user?.name || 'Foydalanuvchi'"
                class="w-full h-full object-cover"
              />
              <span v-else class="text-sm">
                {{ getInitials(item.user?.name || item.user_name || 'Kitobxon') }}
              </span>
            </div>

            <div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-neutral-900">
                  {{ item.user?.name || item.user_name || 'Kitobxon' }}
                </span>
                <span v-if="item.user?.is_vip || item.user_is_vip" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                  VIP
                </span>
              </div>
              <span class="text-xs text-neutral-400">
                {{ formatDate(item.created_at) }}
              </span>
            </div>
          </div>

          <!-- Like button -->
          <button
            type="button"
            @click="likeReview(item)"
            class="flex items-center gap-1.5 text-xs text-neutral-400 hover:text-red-500 transition-colors border-none bg-transparent cursor-pointer p-1"
          >
            <svg class="w-4 h-4" :fill="item.is_liked ? '#ef4444' : 'none'" :stroke="item.is_liked ? '#ef4444' : 'currentColor'" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
            </svg>
            <span v-if="item.likes_count || item.likes?.length">{{ item.likes_count || item.likes?.length }}</span>
          </button>
        </div>

        <!-- Review Body -->
        <p class="text-sm text-neutral-700 leading-relaxed m-0 whitespace-pre-line">
          {{ item.text }}
        </p>

        <!-- Attached Images -->
        <div v-if="item.images?.length" class="flex items-center gap-2 mt-3 overflow-x-auto">
          <img
            v-for="(img, idx) in item.images"
            :key="idx"
            :src="resolveImg(img.image_url || img.image || img)"
            alt="Review attachment"
            class="w-16 h-16 rounded-xl object-cover border border-gray-200"
          />
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12 px-4 rounded-3xl bg-secondary-100 border border-gray-100">
      <div class="w-14 h-14 rounded-full bg-secondary-200 text-neutral-400 mx-auto flex items-center justify-center mb-3">
        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
        </svg>
      </div>
      <h3 class="text-base font-bold text-neutral-800 mb-1">
        Hozircha fikrlar mavjud emas
      </h3>
      <p class="text-sm text-neutral-500 max-w-md mx-auto mb-4">
        Ushbu {{ type === 'stationery' ? 'mahsulot' : 'kitob' }} haqida birinchi bo‘lib o‘z fikringiz va taassurotlaringizni qoldiring!
      </p>
      <button
        type="button"
        @click="handleOpenForm"
        class="px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold border-none cursor-pointer hover:bg-primary-600 transition-colors"
      >
        Fikr bildirish
      </button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const props = withDefaults(
  defineProps<{
    productId: number | string
    type?: 'book' | 'stationery'
  }>(),
  {
    type: 'book'
  }
)

const config = useRuntimeConfig()
const authStore = useAuthStore()

const isFormOpen = ref(false)
const newReviewText = ref('')
const isSubmitting = ref(false)

// Fetch reviews for product
const { data: reviewsRes, pending: isReviewsLoading, refresh } = await useFetch<any>(
  () => `${config.public.apiBase}/v1/kitobchi/product_comments/${props.productId}/${props.type}`,
  {
    lazy: true
  }
)

const reviews = computed(() => {
  return reviewsRes.value?.data || []
})

function handleOpenForm() {
  if (!authStore.isAuthenticated) {
    authStore.isAuthModalOpen = true
  } else {
    isFormOpen.value = !isFormOpen.value
  }
}

function resolveImg(src: string) {
  if (!src) return ''
  return src.startsWith('http') || src.startsWith('data:') ? src : `/storage/${src}`
}

function getInitials(name: string) {
  return (name || 'K')
    .split(' ')
    .slice(0, 2)
    .map(w => w[0]?.toUpperCase())
    .join('')
}

function formatDate(dateStr: string) {
  if (!dateStr) return ''
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long', year: 'numeric' })
  } catch {
    return dateStr
  }
}

async function submitReview() {
  if (!newReviewText.value.trim() || isSubmitting.value) return
  isSubmitting.value = true

  try {
    const token = useCookie('kc_token').value
    await $fetch<any>(`${config.public.apiBase}/v1/kitobchi/new`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`
      },
      body: {
        text: newReviewText.value.trim(),
        product_id: props.productId,
        product_type: props.type
      }
    })

    newReviewText.value = ''
    isFormOpen.value = false
    await refresh()
  } catch (err: any) {
    alert(err?.data?.message || 'Izoh yuborishda xatolik yuz berdi')
  } finally {
    isSubmitting.value = false
  }
}

async function likeReview(item: any) {
  if (!authStore.isAuthenticated) {
    authStore.isAuthModalOpen = true
    return
  }

  try {
    const token = useCookie('kc_token').value
    item.is_liked = !item.is_liked
    item.likes_count = (item.likes_count || 0) + (item.is_liked ? 1 : -1)

    await $fetch<any>(`${config.public.apiBase}/v1/kitobchi/like`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`
      },
      body: {
        post_id: item.id
      }
    })
  } catch {
    // revert
    item.is_liked = !item.is_liked
    item.likes_count = (item.likes_count || 0) + (item.is_liked ? 1 : -1)
  }
}
</script>
