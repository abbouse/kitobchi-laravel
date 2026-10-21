<template>
  <div class="py-4 sm:py-6 md:py-8 pb-24 sm:pb-28">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
      <!-- Breadcrumbs & Back -->
      <nav aria-label="Breadcrumb" class="mb-4 sm:mb-6 flex items-center justify-between">
        <ol class="flex items-center gap-2 text-xs sm:text-sm text-neutral-500 list-none p-0 m-0">
          <li>
            <NuxtLink to="/" class="hover:text-primary transition-colors no-underline">Asosiy</NuxtLink>
          </li>
          <li class="text-neutral-300">/</li>
          <li>
            <NuxtLink to="/club" class="hover:text-primary transition-colors no-underline">Kitob klubi</NuxtLink>
          </li>
          <li class="text-neutral-300">/</li>
          <li class="text-neutral-900 font-semibold truncate max-w-[180px] sm:max-w-[240px]">
            Post #{{ route.params.id }}
          </li>
        </ol>

        <NuxtLink
          to="/club"
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-primary transition-colors no-underline px-3 py-1.5 rounded-full bg-white border-none shadow-2xs"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
          </svg>
          <span>Barcha fikrlar</span>
        </NuxtLink>
      </nav>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
        <!-- LEFT COLUMN: MAIN CONTENT (8 cols) -->
        <main class="lg:col-span-8 space-y-6">
          <!-- MOBILE-ONLY POST ARTICLE (Hidden on Desktop, moves to Sidebar) -->
          <article class="lg:hidden bg-white rounded-3xl p-5 border-none shadow-2xs">
            <!-- Author Header -->
            <div class="flex items-center justify-between gap-3 mb-4">
              <div class="flex items-center gap-3">
                <NuxtLink
                  v-if="post?.user?.id"
                  :to="`/club/user/${post.user.id}`"
                  class="no-underline shrink-0"
                >
                  <AppAvatar
                    :src="post?.user?.avatar"
                    :name="authorName"
                    :user-id="post?.user?.id"
                    size="w-11 h-11 text-sm"
                    :badge="post?.user?.role_emoji"
                  />
                </NuxtLink>
                <div v-else class="shrink-0">
                  <AppAvatar
                    :src="post?.user?.avatar"
                    :name="authorName"
                    :user-id="post?.user?.id"
                    size="w-11 h-11 text-sm"
                    :badge="post?.user?.role_emoji"
                  />
                </div>

                <div class="min-w-0">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <NuxtLink
                      v-if="post?.user?.id"
                      :to="`/club/user/${post.user.id}`"
                      class="font-bold text-sm text-neutral-900 hover:text-primary transition-colors no-underline truncate"
                    >
                      {{ authorName }}
                    </NuxtLink>
                    <span v-else class="font-bold text-sm text-neutral-900 truncate">
                      {{ authorName }}
                    </span>
                    <svg
                      v-if="post?.user?.isVerified || post?.is_verified_author"
                      class="w-4 h-4 text-primary shrink-0"
                      viewBox="0 0 24 24" fill="currentColor"
                      title="Tasdiqlangan kitobxon"
                    >
                      <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                    </svg>

                    <!-- Attached Book Link next to author name (Matching screenshot) -->
                    <template v-if="linkedProduct">
                      <span class="text-neutral-400 font-normal select-none">›</span>
                      <NuxtLink
                        :to="`/books/${linkedProduct.id}`"
                        class="font-semibold text-sm text-primary hover:underline truncate max-w-[160px] sm:max-w-[260px] inline-block no-underline"
                        :title="linkedProduct.name || linkedProduct.title"
                      >
                        {{ linkedProduct.name || linkedProduct.title }}
                      </NuxtLink>
                    </template>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-neutral-400 mt-0.5 truncate">
                    <span v-if="post?.user?.role_title || post?.user?.position">
                      {{ post.user?.role_title || post.user?.position }}
                    </span>
                    <span v-if="post?.user?.role_title || post?.user?.position">•</span>
                    <time :datetime="post?.created_at">{{ formattedDate }}</time>
                  </div>
                </div>
              </div>

              <!-- Theme Pill -->
              <NuxtLink
                v-if="post?.theme?.name"
                :to="`/club?theme=${post.theme.slug || post.theme.id}`"
                class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
              >
                #{{ post.theme.name }}
              </NuxtLink>
            </div>

            <!-- Full Post Text -->
            <div class="text-neutral-900 text-sm leading-relaxed whitespace-pre-line mb-4 font-normal">
              {{ post?.text }}
            </div>

            <!-- Images Gallery -->
            <div
              v-if="post?.images && post.images.length > 0"
              class="mb-4 rounded-2xl overflow-hidden"
            >
              <div
                v-if="post.images.length === 1"
                @click="openGallery(0)"
                class="block max-h-72 w-full bg-neutral-100 overflow-hidden group cursor-pointer rounded-2xl"
              >
                <img
                  :src="resolveImageUrl(post.images[0].image)"
                  alt="Post rasmi"
                  class="w-full h-full max-h-72 object-cover group-hover:scale-102 transition-transform duration-300"
                  loading="lazy"
                />
              </div>
              <div
                v-else
                class="grid gap-2 max-h-72 overflow-hidden rounded-2xl"
                :class="post.images.length === 2 ? 'grid-cols-2 aspect-[16/10]' : 'grid-cols-3 aspect-[16/10]'"
              >
                <div
                  v-for="(img, i) in post.images.slice(0, 3)"
                  :key="i"
                  @click="openGallery(i)"
                  class="relative bg-neutral-100 overflow-hidden group rounded-xl cursor-pointer h-full"
                >
                  <img
                    :src="resolveImageUrl(img.image)"
                    alt="Post rasmi"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                  <div
                    v-if="i === 2 && post.images.length > 3"
                    class="absolute inset-0 bg-neutral-900/60 text-white font-bold text-sm flex items-center justify-center backdrop-blur-xs"
                  >
                    +{{ post.images.length - 3 }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Post Actions -->
            <div class="flex items-center justify-between pt-3 border-t border-neutral-100 text-xs">
              <button
                type="button"
                @click="toggleLike"
                :class="[
                  'inline-flex items-center gap-1.5 transition-colors cursor-pointer border-none bg-transparent p-0 font-semibold',
                  isLiked ? 'text-rose-600' : 'hover:text-rose-600 text-neutral-500'
                ]"
              >
                <svg
                  class="w-4 h-4 transition-transform active:scale-125"
                  :fill="isLiked ? 'currentColor' : 'none'"
                  stroke="currentColor"
                  stroke-width="1.8"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
                <span>{{ likesCount }} ta yoqdi</span>
              </button>

              <button
                type="button"
                @click="sharePost"
                class="inline-flex items-center gap-1 text-neutral-500 hover:text-neutral-900 transition-colors border-none bg-transparent cursor-pointer p-0 font-semibold"
              >
                <span v-if="copied" class="text-emerald-600 font-bold">Nusxalandi!</span>
                <span v-else>Ulashish</span>
              </button>
            </div>
          </article>

          <!-- ====== COMMENTS THREAD (DESKTOP MAIN CONTENT) ====== -->
          <section class="bg-white rounded-3xl p-5 sm:p-7 border-none shadow-2xs">
            <!-- Top Summary Header (Matching screenshot 2) -->
            <div class="flex items-center justify-between pb-4 border-b border-neutral-100 mb-5">
              <div class="flex items-center gap-2.5">
                <!-- Stacked commenters avatars -->
                <div v-if="comments.length > 0" class="flex -space-x-2 overflow-hidden shrink-0">
                  <div
                    v-for="(c, i) in comments.slice(0, 3)"
                    :key="i"
                    class="inline-block ring-2 ring-white rounded-full"
                  >
                    <AppAvatar
                      :src="c.user?.avatar"
                      :name="c.user?.name || 'Kitobxon'"
                      :user-id="c.user?.id"
                      size="w-6 h-6 text-[9px]"
                    />
                  </div>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-neutral-900 m-0">
                  {{ totalCommentsCount }} ta sharh
                </h2>
              </div>

              <!-- Sort Dropdown button (Cart style 3-dots like dropdown) -->
              <div class="relative shrink-0" data-comment-sort-menu>
                <button
                  type="button"
                  @click="isSortMenuOpen = !isSortMenuOpen"
                  class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-700 hover:text-neutral-900 bg-neutral-100 hover:bg-neutral-200/80 px-3 py-1.5 rounded-xl border-none cursor-pointer transition-colors shadow-2xs"
                >
                  <span>{{ sortOptions.find(o => o.value === currentSort)?.label || 'Saralash' }}</span>
                  <svg class="w-3.5 h-3.5 transition-transform text-neutral-500" :class="{ 'rotate-180': isSortMenuOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>

                <div
                  v-if="isSortMenuOpen"
                  class="absolute right-0 top-full mt-1.5 z-30 w-56 rounded-2xl bg-white shadow-xl border-none py-1.5"
                >
                  <button
                    v-for="opt in sortOptions"
                    :key="opt.value"
                    type="button"
                    @click="selectSort(opt.value)"
                    class="w-full text-left px-3.5 py-2.5 text-xs font-medium transition-colors border-none bg-transparent cursor-pointer flex items-center justify-between hover:bg-neutral-50"
                    :class="currentSort === opt.value ? 'text-primary font-bold' : 'text-neutral-700'"
                  >
                    <span>{{ opt.label }}</span>
                    <svg v-if="currentSort === opt.value" class="w-4 h-4 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Comments List with Nested Replies -->
            <div v-if="comments.length > 0" class="space-y-6">
              <div
                v-for="comment in comments"
                :key="comment.id"
                class="group/comment"
              >
                <!-- Parent Comment Row -->
                <div class="flex items-start gap-3">
                  <!-- Commenter Avatar linking to Profile -->
                  <NuxtLink
                    v-if="comment.user?.id"
                    :to="`/club/user/${comment.user.id}`"
                    class="no-underline shrink-0"
                    :title="`${comment.user?.name || 'Kitobxon'} profilini ko‘rish`"
                  >
                    <AppAvatar
                      :src="comment.user?.avatar"
                      :name="comment.user?.name || 'Kitobxon'"
                      :user-id="comment.user?.id"
                      size="w-9 h-9 sm:w-10 sm:h-10 text-xs sm:text-sm"
                      :badge="comment.user?.role_emoji"
                    />
                  </NuxtLink>
                  <div v-else class="shrink-0">
                    <AppAvatar
                      :src="comment.user?.avatar"
                      :name="comment.user?.name || 'Kitobxon'"
                      :user-id="comment.user?.id"
                      size="w-9 h-9 sm:w-10 sm:h-10 text-xs sm:text-sm"
                      :badge="comment.user?.role_emoji"
                    />
                  </div>

                  <!-- Comment Body -->
                  <div class="min-w-0 flex-1">
                    <!-- Name & Role Badge -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <NuxtLink
                        v-if="comment.user?.id"
                        :to="`/club/user/${comment.user.id}`"
                        class="font-bold text-xs sm:text-sm text-neutral-900 hover:text-primary transition-colors no-underline"
                      >
                        {{ comment.user?.name || comment.user?.username || 'Kitobxon' }}
                        <span v-if="comment.user?.lastname">{{ comment.user.lastname }}</span>
                      </NuxtLink>
                      <span v-else class="font-bold text-xs sm:text-sm text-neutral-900">
                        {{ comment.user?.name || comment.user?.username || 'Kitobxon' }}
                        <span v-if="comment.user?.lastname">{{ comment.user.lastname }}</span>
                      </span>

                      <!-- Staff / Verified Icon -->
                      <svg
                        v-if="comment.user?.isVerified || comment.user?.staff_role"
                        class="w-3.5 h-3.5 text-neutral-800 shrink-0"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                      >
                        <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Zm3.094 8.016a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                      </svg>
                    </div>

                    <!-- Role subtitle (e.g., "Основатель • Kitobchi" or "Kitobxon") -->
                    <div class="flex items-center gap-1 text-[11px] text-neutral-400 mt-0.5">
                      <span class="inline-flex items-center gap-1">
                        <svg class="w-3 h-3 text-neutral-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                        </svg>
                        <span>{{ getRoleTitle(comment.user) }}</span>
                      </span>
                    </div>

                    <!-- Comment text -->
                    <p class="text-xs sm:text-sm text-neutral-800 leading-relaxed whitespace-pre-line mt-1.5 mb-2">
                      {{ comment.content }}
                    </p>

                    <!-- Comment Footer: Date, Reply button, Heart -->
                    <div class="flex items-center justify-between text-xs text-neutral-400">
                      <div class="flex items-center gap-3">
                        <time>{{ formatCommentTime(comment.created_at) }}</time>
                        <button
                          type="button"
                          @click="startReply(comment, comment.user)"
                          class="font-semibold text-neutral-500 hover:text-primary transition-colors bg-transparent border-none cursor-pointer p-0"
                        >
                          Javob berish
                        </button>
                      </div>

                      <!-- Heart Like Button -->
                      <button
                        type="button"
                        @click="toggleCommentLike(comment)"
                        :class="[
                          'inline-flex items-center gap-1 transition-colors border-none bg-transparent cursor-pointer p-1',
                          comment.liked ? 'text-rose-600' : 'text-neutral-400 hover:text-rose-500'
                        ]"
                        title="Yoqdi"
                      >
                        <svg
                          class="w-4 h-4 transition-transform active:scale-125"
                          :fill="comment.liked ? 'currentColor' : 'none'"
                          stroke="currentColor"
                          stroke-width="1.8"
                          viewBox="0 0 24 24"
                        >
                          <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                        <span v-if="comment.likes > 0" class="text-[11px]">{{ comment.likes }}</span>
                      </button>
                    </div>

                    <!-- ====== NESTED REPLIES ====== -->
                    <div
                      v-if="comment.preview_replies && comment.preview_replies.length > 0"
                      class="mt-3.5 ml-3 sm:ml-4 pl-3 sm:pl-4 border-l-2 border-neutral-100 space-y-3.5"
                    >
                      <div
                        v-for="reply in comment.preview_replies"
                        :key="reply.id"
                        class="flex items-start gap-2.5"
                      >
                        <!-- Reply Avatar linking to Profile -->
                        <NuxtLink
                          v-if="reply.user?.id"
                          :to="`/club/user/${reply.user.id}`"
                          class="no-underline shrink-0"
                        >
                          <AppAvatar
                            :src="reply.user?.avatar"
                            :name="reply.user?.name || 'Kitobxon'"
                            :user-id="reply.user?.id"
                            size="w-7 h-7 sm:w-8 sm:h-8 text-[10px] sm:text-xs"
                            :badge="reply.user?.role_emoji"
                          />
                        </NuxtLink>
                        <div v-else class="shrink-0">
                          <AppAvatar
                            :src="reply.user?.avatar"
                            :name="reply.user?.name || 'Kitobxon'"
                            :user-id="reply.user?.id"
                            size="w-7 h-7 sm:w-8 sm:h-8 text-[10px] sm:text-xs"
                            :badge="reply.user?.role_emoji"
                          />
                        </div>

                        <!-- Reply Body -->
                        <div class="min-w-0 flex-1">
                          <div class="flex items-center gap-1.5 flex-wrap">
                            <NuxtLink
                              v-if="reply.user?.id"
                              :to="`/club/user/${reply.user.id}`"
                              class="font-bold text-xs text-neutral-900 hover:text-primary transition-colors no-underline"
                            >
                              {{ reply.user?.name || reply.user?.username || 'Kitobxon' }}
                            </NuxtLink>
                            <span v-else class="font-bold text-xs text-neutral-900">
                              {{ reply.user?.name || reply.user?.username || 'Kitobxon' }}
                            </span>
                            <span class="text-[10px] text-neutral-400">
                              • {{ getRoleTitle(reply.user) }}
                            </span>
                          </div>

                          <p class="text-xs text-neutral-800 leading-relaxed whitespace-pre-line mt-1 mb-1.5">
                            <span
                              v-if="reply.reply_to_user?.display_name || reply.reply_to_user?.username"
                              class="font-semibold text-primary mr-1"
                            >
                              @{{ reply.reply_to_user.display_name || reply.reply_to_user.username }}
                            </span>
                            {{ reply.content }}
                          </p>

                          <div class="flex items-center justify-between text-[11px] text-neutral-400">
                            <div class="flex items-center gap-3">
                              <time>{{ formatCommentTime(reply.created_at) }}</time>
                              <button
                                type="button"
                                @click="startReply(comment, reply.user)"
                                class="font-semibold text-neutral-500 hover:text-primary transition-colors bg-transparent border-none cursor-pointer p-0"
                              >
                                Javob berish
                              </button>
                            </div>

                            <button
                              type="button"
                              @click="toggleCommentLike(reply)"
                              :class="[
                                'inline-flex items-center gap-1 transition-colors border-none bg-transparent cursor-pointer p-0.5',
                                reply.liked ? 'text-rose-600' : 'text-neutral-400 hover:text-rose-500'
                              ]"
                            >
                              <svg
                                class="w-3.5 h-3.5 transition-transform active:scale-125"
                                :fill="reply.liked ? 'currentColor' : 'none'"
                                stroke="currentColor"
                                stroke-width="1.8"
                                viewBox="0 0 24 24"
                              >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                              </svg>
                              <span v-if="reply.likes > 0">{{ reply.likes }}</span>
                            </button>
                          </div>
                        </div>
                      </div>

                      <!-- "Yana N ta javobni ko'rish" Button -->
                      <div v-if="comment.remaining_replies_count > 0" class="pt-1">
                        <button
                          type="button"
                          @click="loadMoreReplies(comment)"
                          :disabled="comment.isLoadingReplies"
                          class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:text-primary-hover bg-transparent border-none cursor-pointer p-0 transition-colors"
                        >
                          <svg
                            v-if="comment.isLoadingReplies"
                            class="animate-spin h-3.5 w-3.5 text-primary"
                            fill="none"
                            viewBox="0 0 24 24"
                          >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                          </svg>
                          <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                          </svg>
                          <span>Yana {{ comment.remaining_replies_count }} ta javobni ko‘rish</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty Comments: simple message -->
            <p v-else class="text-center py-8 text-neutral-400 text-xs sm:text-sm m-0">
              Hozircha izohlar yo‘q. Birinchi bo‘lib sharh qoldiring!
            </p>
          </section>
        </main>

        <!-- RIGHT COLUMN: SIDEBAR (4 cols) - MAIN POST & COMPOSER FOR DESKTOP -->
        <aside class="lg:col-span-4 space-y-5">
          <!-- MAIN POST CARD (DESKTOP) -->
          <article class="hidden lg:block bg-white rounded-3xl p-5 sm:p-6 border-none shadow-2xs">
            <!-- Author Header -->
            <div class="flex items-center justify-between gap-3 mb-4">
              <div class="flex items-center gap-3 min-w-0">
                <NuxtLink
                  v-if="post?.user?.id"
                  :to="`/club/user/${post.user.id}`"
                  class="no-underline shrink-0"
                  :title="`${authorName} profilini ko‘rish`"
                >
                  <AppAvatar
                    :src="post?.user?.avatar"
                    :name="authorName"
                    :user-id="post?.user?.id"
                    size="w-11 h-11 text-sm"
                    :badge="post?.user?.role_emoji"
                  />
                </NuxtLink>
                <div v-else class="shrink-0">
                  <AppAvatar
                    :src="post?.user?.avatar"
                    :name="authorName"
                    :user-id="post?.user?.id"
                    size="w-11 h-11 text-sm"
                    :badge="post?.user?.role_emoji"
                  />
                </div>

                <div class="min-w-0">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <NuxtLink
                      v-if="post?.user?.id"
                      :to="`/club/user/${post.user.id}`"
                      class="font-bold text-sm text-neutral-900 hover:text-primary transition-colors no-underline truncate"
                    >
                      {{ authorName }}
                    </NuxtLink>
                    <span v-else class="font-bold text-sm text-neutral-900 truncate">
                      {{ authorName }}
                    </span>
                    <svg
                      v-if="post?.user?.isVerified || post?.is_verified_author"
                      class="w-4 h-4 text-primary shrink-0"
                      viewBox="0 0 24 24" fill="currentColor"
                      title="Tasdiqlangan kitobxon"
                    >
                      <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                    </svg>

                    <!-- Attached Book Link next to author name (Matching screenshot) -->
                    <template v-if="linkedProduct">
                      <span class="text-neutral-400 font-normal select-none">›</span>
                      <NuxtLink
                        :to="`/books/${linkedProduct.id}`"
                        class="font-semibold text-sm text-primary hover:underline truncate max-w-[160px] sm:max-w-[240px] inline-block no-underline"
                        :title="linkedProduct.name || linkedProduct.title"
                      >
                        {{ linkedProduct.name || linkedProduct.title }}
                      </NuxtLink>
                    </template>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-neutral-400 mt-0.5 truncate">
                    <span v-if="post?.user?.role_title || post?.user?.position">
                      {{ post.user?.role_title || post.user?.position }}
                    </span>
                    <span v-if="post?.user?.role_title || post?.user?.position">•</span>
                    <time :datetime="post?.created_at">{{ formattedDate }}</time>
                  </div>
                </div>
              </div>

              <!-- Theme Pill -->
              <NuxtLink
                v-if="post?.theme?.name"
                :to="`/club?theme=${post.theme.slug || post.theme.id}`"
                class="shrink-0 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
              >
                #{{ post.theme.name }}
              </NuxtLink>
            </div>

            <!-- Truncated/Expanded Post Text with 'Davomi' Button -->
            <div class="text-neutral-900 text-sm leading-relaxed whitespace-pre-line mb-4 font-normal">
              <span>{{ displayedPostText }}</span>
              <button
                v-if="isTextLong"
                type="button"
                @click="isPostExpanded = !isPostExpanded"
                class="text-primary hover:text-primary-hover font-bold text-xs ml-1 bg-transparent border-none cursor-pointer p-0 underline"
              >
                {{ isPostExpanded ? 'Qisqartirish' : 'Davomi...' }}
              </button>
            </div>

            <!-- Images Gallery (Capped max height) -->
            <div
              v-if="post?.images && post.images.length > 0"
              class="mb-4 rounded-2xl overflow-hidden"
            >
              <div
                v-if="post.images.length === 1"
                @click="openGallery(0)"
                class="block max-h-60 w-full bg-neutral-100 overflow-hidden group cursor-pointer rounded-2xl"
              >
                <img
                  :src="resolveImageUrl(post.images[0].image)"
                  alt="Post rasmi"
                  class="w-full h-full max-h-60 object-cover group-hover:scale-102 transition-transform duration-300"
                  loading="lazy"
                />
              </div>
              <div
                v-else
                class="grid gap-1.5 max-h-60 overflow-hidden rounded-2xl"
                :class="post.images.length === 2 ? 'grid-cols-2 aspect-[16/10]' : 'grid-cols-3 aspect-[16/10]'"
              >
                <div
                  v-for="(img, i) in post.images.slice(0, 3)"
                  :key="i"
                  @click="openGallery(i)"
                  class="relative bg-neutral-100 overflow-hidden group rounded-xl cursor-pointer h-full"
                >
                  <img
                    :src="resolveImageUrl(img.image)"
                    alt="Post rasmi"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                  <div
                    v-if="i === 2 && post.images.length > 3"
                    class="absolute inset-0 bg-neutral-900/60 text-white font-bold text-xs flex items-center justify-center backdrop-blur-xs"
                  >
                    +{{ post.images.length - 3 }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Post Actions -->
            <div class="flex items-center justify-between pt-3 border-t border-neutral-100 text-xs">
              <button
                type="button"
                @click="toggleLike"
                :class="[
                  'inline-flex items-center gap-1.5 transition-colors cursor-pointer border-none bg-transparent p-0 font-semibold',
                  isLiked ? 'text-rose-600' : 'hover:text-rose-600 text-neutral-500'
                ]"
              >
                <svg
                  class="w-4 h-4 transition-transform active:scale-125"
                  :fill="isLiked ? 'currentColor' : 'none'"
                  stroke="currentColor"
                  stroke-width="1.8"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
                <span>{{ likesCount }} ta yoqdi</span>
              </button>

              <button
                type="button"
                @click="sharePost"
                class="inline-flex items-center gap-1 text-neutral-400 hover:text-neutral-700 transition-colors border-none bg-transparent cursor-pointer p-0 font-medium"
              >
                <span v-if="copied" class="text-emerald-600 font-bold">Nusxalandi!</span>
                <span v-else>Ulashish</span>
              </button>
            </div>
          </article>

          <!-- DESKTOP COMMENT COMPOSER CARD (Directly below post in Sidebar) -->
          <div
            class="hidden lg:block bg-white rounded-3xl p-5 border border-neutral-200/70 shadow-2xs relative transition-all"
            :class="{ 'cursor-pointer hover:border-primary/40': !authStore.isAuthenticated }"
            @click="!authStore.isAuthenticated && authStore.openAuthModal()"
          >
            <!-- Header with Active user avatar -->
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2.5">
                <AppAvatar
                  :src="authStore.user?.avatar || authStore.user?.photo"
                  :name="currentUserDisplayName"
                  :user-id="authStore.user?.id"
                  size="w-8 h-8 text-xs"
                />
                <span class="font-bold text-xs text-neutral-900">
                  {{ authStore.user ? currentUserDisplayName : 'Fikr bildirish' }}
                </span>
              </div>

              <!-- Replying To Indicator -->
              <div
                v-if="replyingTo"
                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-[11px] font-semibold"
              >
                <span>@{{ replyingTo.name }} ga javob</span>
                <button
                  type="button"
                  @click.stop="cancelReply"
                  class="hover:text-primary-hover border-none bg-transparent cursor-pointer p-0 text-xs"
                >
                  ✕
                </button>
              </div>
            </div>

            <!-- Textarea -->
            <div class="mb-3">
              <textarea
                v-model="commentInputText"
                :disabled="!authStore.isAuthenticated"
                rows="3"
                :placeholder="replyingTo ? 'Javobingizni yozing...' : 'Ushbu post haqida fikringiz yoki sharhingizni yozing...'"
                class="w-full rounded-2xl border border-neutral-200/90 p-3 text-xs sm:text-sm focus:outline-hidden focus:border-primary resize-none bg-neutral-50/50 focus:bg-white transition-colors"
              ></textarea>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
              <button
                type="button"
                @click.stop="submitCommentOrReply"
                :disabled="isSubmittingComment || !commentInputText.trim()"
                class="px-5 py-2 rounded-full bg-primary hover:bg-primary-hover text-white text-xs font-bold disabled:opacity-40 transition-colors border-none cursor-pointer shadow-2xs inline-flex items-center gap-1.5"
              >
                <svg v-if="isSubmittingComment" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>{{ replyingTo ? 'Javob berish' : 'Sharh qoldirish' }}</span>
              </button>
            </div>
          </div>

          <!-- Product Details Widget (if attached) -->
          <div
            v-if="linkedProduct"
            class="bg-white rounded-3xl p-5 border border-neutral-200/70 shadow-2xs text-center"
          >
            <div class="w-20 h-28 mx-auto mb-2 rounded-xl overflow-hidden bg-white border border-neutral-200 shadow-2xs">
              <img
                :src="resolveProductImage(linkedProduct)"
                :alt="linkedProduct.name || linkedProduct.title"
                class="w-full h-full object-cover"
              />
            </div>
            <h4 class="font-bold text-xs sm:text-sm text-neutral-900 mb-1 truncate">
              {{ linkedProduct.name || linkedProduct.title }}
            </h4>
            <div v-if="linkedProduct.price" class="text-xs font-extrabold text-primary mb-3">
              {{ formatPrice(linkedProduct.price) }} so‘m
            </div>
            <NuxtLink
              :to="`/books/${linkedProduct.id}`"
              class="w-full inline-flex items-center justify-center px-4 py-2 rounded-full bg-primary hover:bg-primary-hover text-white font-bold text-xs transition-colors no-underline"
            >
              Kitobni sotib olish
            </NuxtLink>
          </div>
        </aside>
      </div>
    </div>

    <!-- ====== MOBILE-ONLY BOTTOM FLOATING INPUT BAR ====== -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-neutral-200/80 py-2.5 px-4 shadow-lg">
      <div class="max-w-2xl mx-auto flex items-center gap-2.5">
        <!-- Current User Avatar with Telegram fallback -->
        <AppAvatar
          :src="authStore.user?.avatar || authStore.user?.photo"
          :name="currentUserDisplayName"
          :user-id="authStore.user?.id"
          size="w-9 h-9 text-xs"
        />

        <!-- Pill Input Box -->
        <div
          class="flex-1 min-w-0 bg-neutral-100/90 rounded-full py-1.5 px-3.5 flex items-center gap-2 transition-all focus-within:ring-2 focus-within:ring-primary/20 focus-within:bg-white border border-transparent focus-within:border-primary/30"
          :class="{ 'cursor-pointer': !authStore.isAuthenticated }"
          @click="!authStore.isAuthenticated && authStore.openAuthModal()"
        >
          <!-- Replying To Pill Badge -->
          <span
            v-if="replyingTo"
            class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[11px] font-semibold"
          >
            <span>@{{ replyingTo.name }}</span>
            <button
              type="button"
              @click.stop="cancelReply"
              class="hover:text-primary-hover text-xs border-none bg-transparent cursor-pointer p-0"
            >
              ✕
            </button>
          </span>

          <!-- Input field -->
          <input
            v-model="commentInputText"
            ref="inputBarRef"
            type="text"
            :disabled="!authStore.isAuthenticated"
            :placeholder="replyingTo ? 'Javob yozish...' : 'Sharh yozish...'"
            @keydown.enter.prevent="submitCommentOrReply"
            class="w-full bg-transparent border-none text-xs sm:text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-hidden py-1"
          />
        </div>

        <!-- Circular Blue Send Button (Arrow up) -->
        <button
          type="button"
          @click="submitCommentOrReply"
          :disabled="isSubmittingComment || !commentInputText.trim()"
          class="shrink-0 w-9 h-9 rounded-full bg-primary hover:bg-primary-hover text-white flex items-center justify-center transition-all disabled:opacity-40 disabled:hover:bg-primary border-none cursor-pointer shadow-2xs"
          title="Yuborish"
        >
          <svg v-if="isSubmittingComment" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Fullscreen Image Lightbox Modal -->
    <ImageGalleryModal
      :is-open="isGalleryOpen"
      :images="galleryImages"
      :initial-index="galleryIndex"
      @close="isGalleryOpen = false"
    />
  </div>
</template>

<script setup lang="ts">
import { resolveImageUrl, resolveProductImage } from '~/utils/productImage'

const route = useRoute()
const config = useRuntimeConfig()
const authStore = useAuthStore()
const postId = route.params.id

// Fetch Post & Comments in parallel
const { data: pageData } = await useAsyncData(`book-club-post-${postId}`, async () => {
  try {
    const [postRes, commentsRes] = await Promise.all([
      $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/posts/${postId}`).catch(() => null),
      $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/comments/${postId}`).catch(() => ({ data: [] }))
    ])

    return {
      post: postRes?.data || null,
      comments: commentsRes?.data || []
    }
  } catch {
    return { post: null, comments: [] }
  }
})

const post = ref<any>(pageData.value?.post)
const comments = ref<any[]>(pageData.value?.comments || [])

if (!post.value) {
  throw createError({
    statusCode: 404,
    statusMessage: 'Post topilmadi yoki o‘chirilgan',
    fatal: true
  })
}

// Truncated / Expanded post text for Desktop Sidebar
const isPostExpanded = ref(false)
const isTextLong = computed(() => {
  return (post.value?.text || '').length > 200
})

const displayedPostText = computed(() => {
  const text = post.value?.text || ''
  if (!isTextLong.value || isPostExpanded.value) {
    return text
  }
  return text.slice(0, 190) + '...'
})

// Current Logged-in User
const currentUserDisplayName = computed(() => {
  const u = authStore.user
  if (!u) return 'Kitobxon'
  if (u.name && u.lastname) return `${u.name} ${u.lastname}`
  return u.name || u.username || 'Kitobxon'
})

// Author details
const authorName = computed(() => {
  const u = post.value?.user
  if (!u) return 'Kitobxon'
  if (u.name && u.lastname) return `${u.name} ${u.lastname}`
  return u.name || u.username || 'Kitobxon'
})

const linkedProduct = computed(() => post.value?.product_snapshot || null)

// Date formatting
const formattedDate = computed(() => {
  if (!post.value?.created_at) return ''
  try {
    return new Date(post.value.created_at).toLocaleDateString('uz-UZ', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return ''
  }
})

function formatCommentTime(dateStr: string) {
  if (!dateStr) return ''
  try {
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000
    if (diff < 60) return 'Hozirgina'
    if (diff < 3600) return `${Math.floor(diff / 60)} daqiqa avval`
    if (diff < 86400) return `${Math.floor(diff / 3600)} soat avval`
    if (diff < 86400 * 7) return `${Math.floor(diff / 86400)} kun avval`
    if (diff < 86400 * 30) return `${Math.floor(diff / (86400 * 7))} hafta avval`
    return new Date(dateStr).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' })
  } catch {
    return ''
  }
}

function getRoleTitle(user: any) {
  if (!user) return 'Kitobxon'
  if (user.role_title) return user.role_title
  if (user.position) return user.position
  if (user.staff_role) return 'Основатель • Kitobchi'
  return 'Kitobxon'
}

// Post Likes
const isLiked = ref(post.value?.liked_by_me || false)
const likesCount = ref(post.value?.likes_count || 0)

async function toggleLike() {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }

  isLiked.value = !isLiked.value
  likesCount.value += isLiked.value ? 1 : -1

  try {
    const token = useCookie('kc_token').value
    await $fetch<any>(`${config.public.apiBase}/v1/kitobchi/book_club/like`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: { post_id: post.value.id }
    })
  } catch {
    isLiked.value = !isLiked.value
    likesCount.value += isLiked.value ? 1 : -1
  }
}

// Post Sharing
const copied = ref(false)
function sharePost() {
  if (process.client) {
    const url = window.location.href
    navigator.clipboard.writeText(url)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }
}

// Lightbox Gallery for Post Images
const isGalleryOpen = ref(false)
const galleryIndex = ref(0)
const galleryImages = computed(() => {
  if (!post.value?.images) return []
  return post.value.images
})

function openGallery(idx: number) {
  galleryIndex.value = idx
  isGalleryOpen.value = true
}

// Comments Stats
const totalCommentsCount = computed(() => {
  let count = comments.value.length
  comments.value.forEach((c) => {
    count += c.replies_count || c.preview_replies?.length || 0
  })
  return count
})

// Sort handling
const currentSort = ref('interesting')
const isSortMenuOpen = ref(false)
const sortOptions = [
  { value: 'interesting', label: 'Avval eng qiziq javoblar' },
  { value: 'newest', label: 'Avval yangi javoblar' },
  { value: 'oldest', label: 'Avval eski javoblar' }
]

function selectSort(val: string) {
  if (currentSort.value !== val) {
    currentSort.value = val
    handleSortChange()
  }
  isSortMenuOpen.value = false
}

function handleSortDocClick(e: MouseEvent) {
  if (isSortMenuOpen.value && !(e.target as HTMLElement).closest('[data-comment-sort-menu]')) {
    isSortMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleSortDocClick)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', handleSortDocClick)
})

async function handleSortChange() {
  try {
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/comments/${post.value.id}`, {
      query: { reply_sort: currentSort.value }
    })
    comments.value = res?.data || []
  } catch (err) {
    console.error('Failed to sort comments', err)
  }
}

// Comment Likes
async function toggleCommentLike(target: any) {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }

  const prevLiked = target.liked
  const prevCount = target.likes || 0

  target.liked = !prevLiked
  target.likes = prevLiked ? Math.max(0, prevCount - 1) : prevCount + 1

  try {
    const token = useCookie('kc_token').value
    await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/like/comment`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: { comment_id: target.id }
    })
  } catch {
    target.liked = prevLiked
    target.likes = prevCount
  }
}

// Reply Handling State
const replyingTo = ref<{
  parentId: number
  replyToUserId?: number
  name: string
} | null>(null)

const commentInputText = ref('')
const isSubmittingComment = ref(false)
const inputBarRef = ref<HTMLInputElement | null>(null)

function startReply(parentComment: any, targetUser: any) {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }

  replyingTo.value = {
    parentId: parentComment.id,
    replyToUserId: targetUser?.id,
    name: targetUser?.name || targetUser?.username || 'Kitobxon'
  }

  nextTick(() => {
    inputBarRef.value?.focus()
  })
}

function cancelReply() {
  replyingTo.value = null
}

// Load more replies for a parent comment
async function loadMoreReplies(parentComment: any) {
  if (parentComment.isLoadingReplies) return
  parentComment.isLoadingReplies = true

  try {
    const offset = parentComment.preview_replies ? parentComment.preview_replies.length : 0
    const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/comments/${parentComment.id}/replies`, {
      query: { offset, limit: 10, sort: currentSort.value }
    })

    const newReplies = res?.data || []
    if (!parentComment.preview_replies) {
      parentComment.preview_replies = []
    }
    parentComment.preview_replies.push(...newReplies)
    parentComment.remaining_replies_count = Math.max(0, (parentComment.replies_count || 0) - parentComment.preview_replies.length)
  } catch (err) {
    console.error('Failed to load more replies', err)
  } finally {
    parentComment.isLoadingReplies = false
  }
}

// Submit Comment or Reply
async function submitCommentOrReply() {
  if (!authStore.isAuthenticated) {
    authStore.openAuthModal()
    return
  }

  const text = commentInputText.value.trim()
  if (!text || isSubmittingComment.value) return

  isSubmittingComment.value = true

  try {
    const token = useCookie('kc_token').value

    if (replyingTo.value) {
      // Replying to comment
      const parentId = replyingTo.value.parentId
      const res: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/comments/${parentId}/reply`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
        body: {
          content: text,
          reply_to_user_id: replyingTo.value.replyToUserId
        }
      })

      const parent = comments.value.find((c) => c.id === parentId)
      if (parent) {
        if (!parent.preview_replies) {
          parent.preview_replies = []
        }
        if (res?.data) {
          parent.preview_replies.push(res.data)
        } else {
          const updated: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/comments/${post.value.id}`, {
            query: { reply_sort: currentSort.value }
          })
          comments.value = updated?.data || []
        }
        parent.replies_count = (parent.replies_count || 0) + 1
      }

      replyingTo.value = null
    } else {
      // Top-level comment
      await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/comments`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
        body: {
          post_id: post.value.id,
          content: text
        }
      })

      const updated: any = await $fetch(`${config.public.apiBase}/v1/kitobchi/book_club/comments/${post.value.id}`, {
        query: { reply_sort: currentSort.value }
      })
      comments.value = updated?.data || []
    }

    commentInputText.value = ''
  } catch (err: any) {
    alert(err?.data?.message || 'Xatolik yuz berdi')
  } finally {
    isSubmittingComment.value = false
  }
}

function formatPrice(val: number | string) {
  const num = typeof val === 'string' ? parseFloat(val) : val
  if (isNaN(num)) return '0'
  return Math.round(num).toLocaleString('uz-UZ').replace(/,/g, ' ')
}

// Rich SEO Metadata
const postSnippet = computed(() => {
  const t = post.value?.text || ''
  return t.length > 150 ? t.slice(0, 147) + '...' : t
})

const pageTitle = computed(() => {
  return `${authorName.value}: "${postSnippet.value.slice(0, 50)}" — Kitobxonlar Klubi | Kitobchi`
})

const shareImage = computed(() => {
  if (post.value?.images && post.value.images[0]) {
    return resolveImageUrl(post.value.images[0].image)
  }
  if (linkedProduct.value) {
    return resolveProductImage(linkedProduct.value)
  }
  return 'https://kitobchi.com/images/og-image.png'
})

useSeoMeta({
  title: pageTitle.value,
  description: postSnippet.value,
  ogTitle: pageTitle.value,
  ogDescription: postSnippet.value,
  ogImage: shareImage.value,
  ogType: 'article'
})

// Schema.org DiscussionForumPosting JSON-LD Structured Data
const jsonLd = computed(() => {
  const postUrl = `https://kitobchi.com/club/${post.value?.id || postId}`
  const authorProfileUrl = post.value?.user?.id
    ? `https://kitobchi.com/club/user/${post.value.user.id}`
    : postUrl

  const schema: any = {
    '@context': 'https://schema.org',
    '@type': 'DiscussionForumPosting',
    '@id': `${postUrl}#posting`,
    url: postUrl,
    mainEntityOfPage: postUrl,
    headline: postSnippet.value.slice(0, 100),
    articleBody: post.value?.text || '',
    datePublished: post.value?.created_at,
    author: {
      '@type': 'Person',
      name: authorName.value,
      url: authorProfileUrl,
      image: post.value?.user?.avatar ? resolveImageUrl(post.value.user.avatar) : undefined
    },
    interactionStatistic: [
      {
        '@type': 'InteractionCounter',
        interactionType: 'https://schema.org/LikeAction',
        userInteractionCount: likesCount.value
      },
      {
        '@type': 'InteractionCounter',
        interactionType: 'https://schema.org/CommentAction',
        userInteractionCount: totalCommentsCount.value
      }
    ],
    comment: comments.value.map((c: any) => {
      const commentAuthorUrl = c.user?.id
        ? `https://kitobchi.com/club/user/${c.user.id}`
        : (c.user_id ? `https://kitobchi.com/club/user/${c.user_id}` : postUrl)

      return {
        '@type': 'Comment',
        url: `${postUrl}#comment-${c.id}`,
        text: c.content,
        dateCreated: c.created_at,
        author: {
          '@type': 'Person',
          name: c.user?.name || 'Kitobxon',
          url: commentAuthorUrl
        }
      }
    })
  }

  if (linkedProduct.value) {
    schema.about = {
      '@type': 'Book',
      name: linkedProduct.value.name || linkedProduct.value.title,
      url: `https://kitobchi.com/books/${linkedProduct.value.id}`
    }
  }

  return schema
})

useHead({
  link: [
    { rel: 'canonical', href: `https://kitobchi.com/club/${postId}` }
  ],
  script: [
    {
      type: 'application/ld+json',
      children: JSON.stringify(jsonLd.value)
    }
  ]
})
</script>
