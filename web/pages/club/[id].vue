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
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-primary transition-colors no-underline px-3 py-1.5 rounded-full bg-white border border-neutral-200/80 shadow-2xs"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
          </svg>
          <span>Barcha fikrlar</span>
        </NuxtLink>
      </nav>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
        <!-- MAIN POST & COMMENTS (8 cols) -->
        <main class="lg:col-span-8 space-y-6">
          <!-- Post Article Card -->
          <article class="bg-white rounded-3xl p-5 sm:p-7 border border-neutral-200/70 shadow-2xs">
            <!-- Author Header -->
            <div class="flex items-center justify-between gap-3 mb-5">
              <div class="flex items-center gap-3">
                <!-- Avatar -->
                <div class="relative shrink-0">
                  <img
                    v-if="authorAvatar"
                    :src="authorAvatar"
                    :alt="authorName"
                    class="w-11 h-11 rounded-full object-cover border border-neutral-100 bg-neutral-100"
                  />
                  <div
                    v-else
                    class="w-11 h-11 rounded-full bg-gradient-to-br from-primary/10 to-primary/20 text-primary font-bold text-sm flex items-center justify-center border border-primary/10"
                  >
                    {{ authorInitials }}
                  </div>
                  <span
                    v-if="post?.user?.role_emoji"
                    class="absolute -bottom-1 -right-1 text-xs bg-white rounded-full px-0.5 shadow-2xs"
                  >
                    {{ post.user.role_emoji }}
                  </span>
                </div>

                <!-- Info -->
                <div class="min-w-0">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-bold text-sm sm:text-base text-neutral-900 truncate">
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
            <div class="text-neutral-900 text-sm sm:text-base leading-relaxed whitespace-pre-line mb-5 font-normal">
              {{ post?.text }}
            </div>

            <!-- Capped Images Gallery with Lightbox -->
            <div
              v-if="post?.images && post.images.length > 0"
              class="mb-5 rounded-2xl overflow-hidden"
            >
              <!-- Single Image: capped max height -->
              <div
                v-if="post.images.length === 1"
                @click="openGallery(0)"
                class="block max-h-72 sm:max-h-80 w-full bg-neutral-100 overflow-hidden group cursor-pointer rounded-2xl"
              >
                <img
                  :src="resolveImageUrl(post.images[0].image)"
                  alt="Post rasmi"
                  class="w-full h-full max-h-72 sm:max-h-80 object-cover group-hover:scale-102 transition-transform duration-300"
                  loading="lazy"
                />
              </div>

              <!-- Multiple Images (2 or more) -->
              <div
                v-else
                class="grid gap-2 max-h-72 sm:max-h-80 overflow-hidden rounded-2xl"
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

            <!-- Attached Product Showcase -->
            <NuxtLink
              v-if="linkedProduct"
              :to="`/books/${linkedProduct.id}`"
              class="p-3 sm:p-4 rounded-2xl bg-neutral-50/80 hover:bg-neutral-100/90 border border-neutral-200/80 transition-all mb-5 flex items-center justify-between gap-4 no-underline group/link"
            >
              <div class="flex items-center gap-3.5 min-w-0">
                <div class="shrink-0 w-11 h-15 rounded-lg overflow-hidden bg-white border border-neutral-200/80 shadow-2xs">
                  <img
                    :src="resolveProductImage(linkedProduct)"
                    :alt="linkedProduct.name || linkedProduct.title"
                    class="w-full h-full object-cover group-hover/link:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                </div>
                <div class="min-w-0">
                  <span class="text-[10px] uppercase font-bold text-neutral-400 tracking-wider block">Mavzu kitobi</span>
                  <div class="font-bold text-xs sm:text-sm text-neutral-900 group-hover/link:text-primary transition-colors truncate">
                    {{ linkedProduct.name || linkedProduct.title }}
                  </div>
                  <div v-if="linkedProduct.price" class="text-xs sm:text-sm font-extrabold text-primary mt-0.5">
                    {{ formatPrice(linkedProduct.price) }} so‘m
                  </div>
                </div>
              </div>

              <div class="shrink-0 inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full bg-primary hover:bg-primary-hover text-white font-bold text-xs transition-colors shadow-2xs">
                <span>Kitobni ko‘rish</span>
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
              </div>
            </NuxtLink>

            <!-- Post Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-neutral-100 text-xs sm:text-sm">
              <div class="flex items-center gap-5">
                <!-- Like Button -->
                <button
                  type="button"
                  @click="toggleLike"
                  :class="[
                    'inline-flex items-center gap-1.5 transition-colors cursor-pointer border-none bg-transparent p-0 font-semibold',
                    isLiked ? 'text-rose-600' : 'hover:text-rose-600 text-neutral-500'
                  ]"
                >
                  <svg
                    class="w-4 h-4 sm:w-5 sm:h-5 transition-transform active:scale-125"
                    :fill="isLiked ? 'currentColor' : 'none'"
                    stroke="currentColor"
                    stroke-width="1.8"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                  </svg>
                  <span>{{ likesCount }} ta yoqdi</span>
                </button>

                <!-- Comments Count -->
                <div class="inline-flex items-center gap-1.5 text-neutral-500 font-semibold">
                  <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                  </svg>
                  <span>{{ totalCommentsCount }} ta sharh</span>
                </div>
              </div>

              <!-- Share Button -->
              <button
                type="button"
                @click="sharePost"
                class="inline-flex items-center gap-1.5 text-neutral-500 hover:text-neutral-900 transition-colors border-none bg-transparent cursor-pointer p-0 font-semibold"
              >
                <span v-if="copied" class="text-emerald-600 font-bold">Nusxalandi!</span>
                <template v-else>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                  </svg>
                  <span>Ulashish</span>
                </template>
              </button>
            </div>
          </article>

          <!-- ====== COMMENTS THREAD (MATCHING APP "SHARHLAR" DESIGN) ====== -->
          <section class="bg-white rounded-3xl p-5 sm:p-7 border border-neutral-200/70 shadow-2xs">
            <!-- Top Summary Header (Matching screenshot 2) -->
            <div class="flex items-center justify-between pb-4 border-b border-neutral-100 mb-5">
              <div class="flex items-center gap-2">
                <!-- Stacked commenters avatars -->
                <div v-if="avatarStack.length > 0" class="flex -space-x-2 overflow-hidden shrink-0">
                  <img
                    v-for="(av, i) in avatarStack"
                    :key="i"
                    :src="av"
                    class="inline-block w-6 h-6 rounded-full ring-2 ring-white object-cover bg-neutral-100"
                  />
                </div>
                <h2 class="text-sm sm:text-base font-bold text-neutral-900 m-0">
                  {{ totalCommentsCount }} ta sharh
                </h2>
              </div>

              <!-- Sort Dropdown button -->
              <div class="relative">
                <select
                  v-model="currentSort"
                  @change="handleSortChange"
                  class="text-xs font-semibold text-primary bg-transparent border-none cursor-pointer focus:outline-hidden py-1 pr-1"
                >
                  <option value="interesting">Avval eng qiziq javoblar ko‘rinadi ⌵</option>
                  <option value="newest">Avval yangi javoblar ko‘rinadi ⌵</option>
                  <option value="oldest">Avval eski javoblar ko‘rinadi ⌵</option>
                </select>
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
                  <!-- Commenter Avatar -->
                  <div class="shrink-0">
                    <img
                      v-if="comment.user?.avatar"
                      :src="resolveImageUrl(comment.user.avatar)"
                      :alt="comment.user?.name || 'Kitobxon'"
                      class="w-9 h-9 sm:w-10 sm:h-10 rounded-full object-cover border border-neutral-100 bg-neutral-100"
                    />
                    <div
                      v-else
                      class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center"
                    >
                      {{ (comment.user?.name || 'K').slice(0, 2).toUpperCase() }}
                    </div>
                  </div>

                  <!-- Comment Body -->
                  <div class="min-w-0 flex-1">
                    <!-- Name & Role Badge -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <span class="font-bold text-xs sm:text-sm text-neutral-900">
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
                        <!-- Reply Avatar -->
                        <div class="shrink-0">
                          <img
                            v-if="reply.user?.avatar"
                            :src="resolveImageUrl(reply.user.avatar)"
                            :alt="reply.user?.name || 'Kitobxon'"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-full object-cover border border-neutral-100 bg-neutral-100"
                          />
                          <div
                            v-else
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-primary/10 text-primary font-bold text-[10px] sm:text-xs flex items-center justify-center"
                          >
                            {{ (reply.user?.name || 'K').slice(0, 2).toUpperCase() }}
                          </div>
                        </div>

                        <!-- Reply Body -->
                        <div class="min-w-0 flex-1">
                          <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-bold text-xs text-neutral-900">
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

        <!-- RIGHT: SIDEBAR (4 cols) -->
        <aside class="lg:col-span-4 space-y-6">
          <!-- Back to Club Banner -->
          <div class="bg-white rounded-3xl p-6 border border-neutral-200/70 shadow-2xs">
            <h3 class="font-bold text-base text-neutral-900 mb-2">Boshqa muhokamalar</h3>
            <p class="text-xs text-neutral-500 leading-relaxed mb-4">
              Kitobxonlar klubida minglab o‘quvchilar yangi asarlar haqida fikr almashmoqda.
            </p>
            <NuxtLink
              to="/club"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-neutral-100 hover:bg-neutral-200/80 text-neutral-900 font-bold text-xs transition-colors no-underline"
            >
              Klub lentasiga qaytish
            </NuxtLink>
          </div>

          <!-- Product Details Widget (if attached) -->
          <div
            v-if="linkedProduct"
            class="bg-white rounded-3xl p-6 border border-neutral-200/70 shadow-2xs text-center"
          >
            <div class="w-24 h-36 mx-auto mb-3 rounded-xl overflow-hidden bg-white border border-neutral-200 shadow-xs">
              <img
                :src="resolveProductImage(linkedProduct)"
                :alt="linkedProduct.name || linkedProduct.title"
                class="w-full h-full object-cover"
              />
            </div>
            <h4 class="font-bold text-sm text-neutral-900 mb-1">
              {{ linkedProduct.name || linkedProduct.title }}
            </h4>
            <div v-if="linkedProduct.price" class="text-sm font-extrabold text-primary mb-4">
              {{ formatPrice(linkedProduct.price) }} so‘m
            </div>
            <NuxtLink
              :to="`/books/${linkedProduct.id}`"
              class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-full bg-primary hover:bg-primary-hover text-white font-bold text-xs transition-colors no-underline"
            >
              Kitobni sotib olish
            </NuxtLink>
          </div>
        </aside>
      </div>
    </div>

    <!-- ====== BOTTOM FLOATING INPUT BAR (MATCHING APP SCREENSHOT) ====== -->
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-neutral-200/80 py-2.5 px-4 shadow-lg">
      <div class="max-w-2xl mx-auto flex items-center gap-2.5">
        <!-- Current User Avatar -->
        <div class="shrink-0">
          <img
            v-if="currentUserAvatar"
            :src="currentUserAvatar"
            :alt="currentUserDisplayName"
            class="w-9 h-9 rounded-full object-cover border border-neutral-100 bg-neutral-100"
          />
          <div
            v-else
            class="w-9 h-9 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center"
          >
            {{ currentUserInitials }}
          </div>
        </div>

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

          <!-- Expanding Input -->
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

// Current Logged-in User
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

// Author details
const authorName = computed(() => {
  const u = post.value?.user
  if (!u) return 'Kitobxon'
  if (u.name && u.lastname) return `${u.name} ${u.lastname}`
  return u.name || u.username || 'Kitobxon'
})

const authorInitials = computed(() => {
  const name = authorName.value.trim()
  const parts = name.split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
})

const authorAvatar = computed(() => {
  const av = post.value?.user?.avatar
  if (!av) return null
  return resolveImageUrl(av)
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

// Comments Stats & Stack
const totalCommentsCount = computed(() => {
  let count = comments.value.length
  comments.value.forEach((c) => {
    count += c.replies_count || c.preview_replies?.length || 0
  })
  return count
})

const avatarStack = computed(() => {
  const avatars: string[] = []
  comments.value.forEach((c) => {
    if (c.user?.avatar && avatars.length < 3 && !avatars.includes(resolveImageUrl(c.user.avatar))) {
      avatars.push(resolveImageUrl(c.user.avatar))
    }
  })
  return avatars
})

// Sort handling
const currentSort = ref('interesting')

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

      // Find parent comment and append reply
      const parent = comments.value.find((c) => c.id === parentId)
      if (parent) {
        if (!parent.preview_replies) {
          parent.preview_replies = []
        }
        if (res?.data) {
          parent.preview_replies.push(res.data)
        } else {
          // Fallback refresh comments
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

      // Refresh comments
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
  const schema: any = {
    '@context': 'https://schema.org',
    '@type': 'DiscussionForumPosting',
    '@id': `https://kitobchi.com/club/${post.value?.id}`,
    headline: postSnippet.value.slice(0, 100),
    articleBody: post.value?.text || '',
    datePublished: post.value?.created_at,
    author: {
      '@type': 'Person',
      name: authorName.value,
      image: authorAvatar.value || undefined
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
    comment: comments.value.map((c: any) => ({
      '@type': 'Comment',
      text: c.content,
      dateCreated: c.created_at,
      author: {
        '@type': 'Person',
        name: c.user?.name || 'Kitobxon'
      }
    }))
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
