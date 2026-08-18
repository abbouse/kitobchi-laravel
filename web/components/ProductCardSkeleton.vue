<template>
  <div class="flex flex-col rounded-xl bg-white border border-gray-100 overflow-hidden shadow-xs">
    <!-- Product Cover Aspect 3:4 Shimmer -->
    <div class="relative w-full rounded-xl shimmer overflow-hidden" style="aspect-ratio: 3 / 4;">
      <!-- Heart placeholder button -->
      <div class="absolute top-2 right-2 w-8 h-8 rounded-2xl bg-white/70 backdrop-blur-xs"></div>
    </div>

    <!-- Details Block -->
    <div class="p-2.5 md:p-3 flex flex-col flex-1 justify-between gap-2">
      <div class="space-y-1.5">
        <!-- Monthly installment tag shimmer -->
        <div class="h-4 w-24 rounded-md shimmer"></div>

        <!-- Price shimmer -->
        <div class="h-5 w-28 rounded-md shimmer mt-1"></div>

        <!-- Title 2-lines shimmer -->
        <div class="h-3.5 w-full rounded shimmer mt-1.5"></div>
        <div class="h-3.5 w-3/4 rounded shimmer"></div>
      </div>

      <!-- Rating & Reviews shimmer -->
      <div class="flex items-center gap-2 mt-1">
        <div class="h-3 w-12 rounded shimmer"></div>
        <div class="h-3 w-16 rounded shimmer"></div>
      </div>

      <!-- Buy / Cart Button shimmer -->
      <div class="h-9 md:h-10 w-full rounded-xl shimmer mt-1"></div>
    </div>
  </div>
</template>
