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
  <div class="flex justify-center mt-8 space-x-2">
    <!-- Prev -->
    <button
      class="px-3 py-1 rounded-md border text-sm"
      :disabled="props.currentPage === 1"
      @click="changePage(props.currentPage - 1)"
    >
      Prev
    </button>

    <!-- Pages -->
    <button
      v-for="(page, i) in pages"
      :key="i"
      class="px-3 py-1 rounded-md border text-sm"
      :class="[
        page === props.currentPage
          ? 'bg-primary-600 text-white border-primary-600'
          : page === '...'
          ? 'cursor-default text-gray-400 border-gray-200'
          : 'hover:bg-gray-100'
      ]"
      :disabled="page === '...'"
      @click="changePage(page)"
    >
      {{ page }}
    </button>

    <!-- Next -->
    <button
      class="px-3 py-1 rounded-md border text-sm"
      :disabled="props.currentPage === props.totalPages"
      @click="changePage(props.currentPage + 1)"
    >
      Next
    </button>
  </div>
</template>
