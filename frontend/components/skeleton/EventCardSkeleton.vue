<script setup lang="ts">
const props = withDefaults(defineProps<{
  count?: number
}>(), {
  count: 6
})
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div
      v-for="index in props.count"
      :key="index"
      class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100"
    >
      <div class="h-48 w-full bg-gray-200 animate-pulse" />

      <div class="p-6 space-y-4">
        <div class="h-6 w-3/4 bg-gray-200 rounded animate-pulse" />
        <div class="space-y-2">
          <div class="h-4 w-full bg-gray-200 rounded animate-pulse" />
          <div class="h-4 w-5/6 bg-gray-200 rounded animate-pulse" />
        </div>

        <div class="space-y-3">
          <div class="h-5 w-32 bg-gray-200 rounded animate-pulse" />
          <div class="h-5 w-40 bg-gray-200 rounded animate-pulse" />
        </div>

        <div class="flex gap-2">
          <div class="h-10 flex-1 bg-gray-200 rounded-lg animate-pulse" />
          <div class="h-10 flex-1 bg-gray-200 rounded-lg animate-pulse" />
        </div>
      </div>
    </div>
  </div>
</template>

