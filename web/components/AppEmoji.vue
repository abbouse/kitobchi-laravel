<template>
  <span class="inline-flex items-center justify-center align-middle select-none shrink-0" :class="wrapperClass">
    <!-- High-Resolution Apple 3D PNG Emoji -->
    <img
      v-if="emojiSrc && !hasError"
      :src="emojiSrc"
      :alt="altText"
      :style="sizeStyle"
      class="object-contain inline-block pointer-events-none"
      loading="lazy"
      @error="hasError = true"
    />
    <!-- Native Text Emoji Fallback -->
    <span v-else class="leading-none text-current" :class="fallbackSizeClass">
      {{ fallbackText }}
    </span>
  </span>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    name?: string | null
    char?: string | null
    size?: number | string
    alt?: string
  }>(),
  {
    name: null,
    char: null,
    size: 16,
    alt: ''
  }
)

const hasError = ref(false)

const EMOJI_MAP: Record<string, string> = {
  '🔥': '/emojis/fire.png',
  'fire': '/emojis/fire.png',
  '📚': '/emojis/books.png',
  '📖': '/emojis/books.png',
  'books': '/emojis/books.png',
  'book': '/emojis/books.png',
  '💼': '/emojis/briefcase.png',
  'briefcase': '/emojis/briefcase.png',
  '👑': '/emojis/crown.png',
  'crown': '/emojis/crown.png',
  '✨': '/emojis/sparkles.png',
  'sparkles': '/emojis/sparkles.png',
  '⭐': '/emojis/star.png',
  'star': '/emojis/star.png',
  '💎': '/emojis/gem.png',
  'gem': '/emojis/gem.png',
  '🎯': '/emojis/target.png',
  'target': '/emojis/target.png',
  '⚡': '/emojis/lightning.png',
  'lightning': '/emojis/lightning.png',
  '🎓': '/emojis/graduation.png',
  'graduation': '/emojis/graduation.png',
  '🤍': '/emojis/white_heart.png',
  'white_heart': '/emojis/white_heart.png',
  '🚀': '/emojis/rocket.png',
  'rocket': '/emojis/rocket.png',
  '❤️': '/emojis/heart.png',
  '❤': '/emojis/heart.png',
  'heart': '/emojis/heart.png',
  '👍': '/emojis/thumbsup.png',
  'thumbsup': '/emojis/thumbsup.png',
  '👏': '/emojis/clap.png',
  'clap': '/emojis/clap.png',
  '💬': '/emojis/speech_balloon.png',
  'speech_balloon': '/emojis/speech_balloon.png',
  '💡': '/emojis/bulb.png',
  'bulb': '/emojis/bulb.png',
  '📝': '/emojis/memo.png',
  'memo': '/emojis/memo.png',
  '📌': '/emojis/pin.png',
  'pin': '/emojis/pin.png',
  '🔍': '/emojis/mag.png',
  '🔎': '/emojis/mag.png',
  'mag': '/emojis/mag.png',
  'search': '/emojis/mag.png',
  '✅': '/emojis/check.png',
  'check': '/emojis/check.png',
  '🎉': '/emojis/party.png',
  'party': '/emojis/party.png',
  '👀': '/emojis/eyes.png',
  'eyes': '/emojis/eyes.png'
}

function emojiToHex(emoji: string): string {
  const comp: string[] = []
  for (let i = 0; i < emoji.length; i++) {
    const code = emoji.codePointAt(i)
    if (!code) continue
    if (code > 0xffff) i++
    if (code !== 0xfe0f && code !== 0xfe0e) {
      comp.push(code.toString(16).toLowerCase())
    }
  }
  return comp.join('-')
}

const key = computed(() => {
  return (props.name || props.char || '').trim()
})

const emojiSrc = computed(() => {
  const k = key.value
  if (!k) return null
  if (EMOJI_MAP[k]) return EMOJI_MAP[k]

  // If it's a character emoji, attempt CDN lookup
  if (k.length <= 4) {
    const hex = emojiToHex(k)
    if (hex) {
      return `https://raw.githubusercontent.com/iamcal/emoji-data/master/img-apple-160/${hex}.png`
    }
  }

  return null
})

const fallbackText = computed(() => {
  return props.char || props.name || ''
})

const altText = computed(() => {
  return props.alt || props.name || props.char || 'emoji'
})

const wrapperClass = computed(() => {
  return 'align-[-0.1em]'
})

const sizeStyle = computed(() => {
  const s = props.size
  if (typeof s === 'number') {
    return { width: `${s}px`, height: `${s}px`, minWidth: `${s}px`, minHeight: `${s}px` }
  }
  if (/^\d+$/.test(s)) {
    return { width: `${s}px`, height: `${s}px`, minWidth: `${s}px`, minHeight: `${s}px` }
  }
  return {}
})

const fallbackSizeClass = computed(() => {
  const s = props.size
  const num = typeof s === 'number' ? s : parseInt(s, 10) || 16
  if (num <= 14) return 'text-[11px]'
  if (num <= 18) return 'text-xs'
  if (num <= 24) return 'text-sm'
  return 'text-base'
})
</script>
