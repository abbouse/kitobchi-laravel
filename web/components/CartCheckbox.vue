<template>
  <!-- Piyoladagi savatcha belgisi bilan bir xil: to'ldirilgan to'q rangli
       kvadrat + oq belgi (tanlanganda), yengil chegarali bo'sh kvadrat
       (tanlanmaganda) — brauzerning standart checkbox ko'rinishi emas. -->
  <span
    class="w-5 h-5 rounded-md flex items-center justify-center shrink-0 transition-colors"
    :class="checked ? 'bg-primary' : 'bg-white border border-gray-200'"
  >
    <svg v-if="checked" class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
    </svg>
  </span>
</template>

<script setup lang="ts">
defineProps<{ checked: boolean }>()
</script>
