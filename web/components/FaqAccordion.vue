<template>
  <div v-if="sections.length > 1" class="divide-y divide-neutral-100 border-t border-b border-neutral-100">
    <div v-for="(sec, idx) in sections" :key="idx">
      <button
        type="button"
        class="w-full flex items-center justify-between gap-4 py-5 text-left border-none bg-transparent cursor-pointer"
        @click="toggle(idx)"
      >
        <span class="font-semibold text-base md:text-xl text-primary">{{ sec.heading }}</span>
        <svg
          class="w-5 h-5 text-primary shrink-0 transition-transform duration-300"
          :class="openIndex === idx ? 'rotate-180' : ''"
          viewBox="0 0 20 20" fill="currentColor"
        >
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
      </button>
      <div v-show="openIndex === idx" class="kb-prose pb-5">
        <div v-html="sec.bodyHtml"></div>
      </div>
    </div>
  </div>

  <!-- Sarlavhalar orqali bo'lakларга ajratib bo'lmadi (masalan admin
       oddiy matn yozgan) — oddiy prose sifatida ko'rsatamiz. -->
  <div v-else class="kb-prose" v-html="html"></div>
</template>

<script setup lang="ts">
const props = defineProps<{
  html: string
}>()

const openIndex = ref(0)

function toggle(idx: number) {
  openIndex.value = openIndex.value === idx ? -1 : idx
}

const sections = computed(() => {
  if (typeof window === 'undefined' || !props.html) return []
  try {
    const parser = new DOMParser()
    const doc = parser.parseFromString(props.html, 'text/html')
    const nodes = Array.from(doc.body.childNodes)
    const result: { heading: string; bodyHtml: string }[] = []
    let current: { heading: string; bodyHtml: string } | null = null

    for (const node of nodes) {
      const el = node as HTMLElement
      if (el.nodeType === 1 && /^H[1-4]$/.test(el.tagName)) {
        if (current) result.push(current)
        current = { heading: el.textContent?.trim() || '', bodyHtml: '' }
      } else if (current) {
        current.bodyHtml += (el as any).outerHTML ?? el.textContent ?? ''
      }
    }
    if (current) result.push(current)
    return result
  } catch {
    return []
  }
})
</script>
