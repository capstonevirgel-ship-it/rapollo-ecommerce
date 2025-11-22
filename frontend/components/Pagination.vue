<script setup lang="ts">
import { computed } from "vue"

interface Props {
  currentPage: number
  totalPages: number
}
const props = defineProps<Props>()
const emit = defineEmits(["page-change"])

// Build pages list (with ellipsis if large)
const pages = computed(() => {
  const result: (number | string)[] = []
  const { currentPage, totalPages } = props

  if (totalPages <= 7) {
    for (let i = 1; i <= totalPages; i++) result.push(i)
  } else {
    result.push(1)
    if (currentPage > 4) result.push("...")
    const start = Math.max(2, currentPage - 1)
    const end = Math.min(totalPages - 1, currentPage + 1)
    for (let i = start; i <= end; i++) result.push(i)
    if (currentPage < totalPages - 3) result.push("...")
    result.push(totalPages)
  }

  return result
})

const changePage = (page: number | string) => {
  if (typeof page === "number" && page !== props.currentPage) {
    emit("page-change", page)
  }
}
</script>

<template>
  <div class="flex items-center justify-center mt-12 mb-8 gap-1">
    <!-- Previous Button -->
    <button
      :disabled="props.currentPage === 1"
      @click="changePage(props.currentPage - 1)"
      class="flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white text-gray-700 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 hover:border-gray-400 active:scale-95"
      :class="props.currentPage === 1 ? 'cursor-not-allowed' : 'cursor-pointer'"
      aria-label="Previous page"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <!-- Page Numbers -->
    <div class="flex items-center gap-1">
      <button
        v-for="(page, i) in pages"
        :key="i"
        @click="changePage(page)"
        :disabled="page === '...'"
        class="flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg font-medium text-sm transition-all duration-200"
        :class="[
          page === props.currentPage
            ? 'bg-zinc-800 text-white shadow-md scale-105 cursor-default'
            : page === '...'
            ? 'text-gray-400 cursor-default pointer-events-none'
            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 hover:border-gray-400 active:scale-95 cursor-pointer'
        ]"
        :aria-label="page === '...' ? 'More pages' : `Go to page ${page}`"
        :aria-current="page === props.currentPage ? 'page' : undefined"
      >
        {{ page }}
      </button>
    </div>

    <!-- Next Button -->
    <button
      :disabled="props.currentPage === props.totalPages"
      @click="changePage(props.currentPage + 1)"
      class="flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white text-gray-700 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 hover:border-gray-400 active:scale-95"
      :class="props.currentPage === props.totalPages ? 'cursor-not-allowed' : 'cursor-pointer'"
      aria-label="Next page"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </button>
  </div>
</template>
