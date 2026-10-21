<template>
  <Teleport to="body">
    <div
      aria-live="polite"
      class="fixed z-90 pointer-events-none inset-x-0 top-4 sm:top-6 flex flex-col items-center gap-2 px-4"
    >
      <TransitionGroup
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="-translate-y-4 opacity-0 scale-95"
        enter-to-class="translate-y-0 opacity-100 scale-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="-translate-y-2 opacity-0 scale-95"
      >
        <div
          v-for="item in snackbars"
          :key="item.id"
          class="pointer-events-auto w-full max-w-sm sm:max-w-md rounded-2xl p-3 sm:p-3.5 shadow-xl flex items-center justify-between gap-3 backdrop-blur-md border transition-all"
          :class="getStyleClasses(item.type)"
          role="alert"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <!-- Icon -->
            <div
              class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
              :class="getIconWrapClasses(item.type)"
            >
              <!-- Success -->
              <svg
                v-if="item.type === 'success'"
                class="w-4 h-4"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                  clip-rule="evenodd"
                />
              </svg>

              <!-- Warning -->
              <svg
                v-else-if="item.type === 'warning'"
                class="w-4 h-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                />
              </svg>

              <!-- Error -->
              <svg
                v-else-if="item.type === 'error'"
                class="w-4 h-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                />
              </svg>

              <!-- Info -->
              <svg
                v-else
                class="w-4 h-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"
                />
              </svg>
            </div>

            <!-- Content -->
            <div class="min-w-0">
              <div v-if="item.title" class="text-xs font-bold leading-tight mb-0.5">
                {{ item.title }}
              </div>
              <div class="text-xs font-medium leading-snug">
                {{ item.message }}
              </div>
            </div>
          </div>

          <!-- Close button -->
          <button
            type="button"
            @click="removeSnackbar(item.id)"
            class="p-1 rounded-lg opacity-60 hover:opacity-100 transition-opacity border-none bg-transparent cursor-pointer shrink-0"
            aria-label="Yopish"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { useSnackbar, type SnackbarType } from '~/composables/useSnackbar'

const { snackbars, removeSnackbar } = useSnackbar()

function getStyleClasses(type: SnackbarType) {
  switch (type) {
    case 'success':
      return 'bg-emerald-900/90 text-white border-emerald-700/60 shadow-emerald-950/20'
    case 'warning':
      return 'bg-amber-900/90 text-white border-amber-700/60 shadow-amber-950/20'
    case 'error':
      return 'bg-rose-900/90 text-white border-rose-700/60 shadow-rose-950/20'
    case 'info':
    default:
      return 'bg-neutral-900/90 text-white border-neutral-700/60 shadow-black/20'
  }
}

function getIconWrapClasses(type: SnackbarType) {
  switch (type) {
    case 'success':
      return 'bg-emerald-500/25 text-emerald-300'
    case 'warning':
      return 'bg-amber-500/25 text-amber-300'
    case 'error':
      return 'bg-rose-500/25 text-rose-300'
    case 'info':
    default:
      return 'bg-primary/25 text-blue-300'
  }
}
</script>
