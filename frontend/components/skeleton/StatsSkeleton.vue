<template>
  <div class="flex flex-wrap gap-4">
    <div v-for="i in 3" :key="i" class="bg-white p-4 rounded-lg shadow-sm animate-pulse">
      <div class="h-6 bg-gray-200 rounded mb-2 w-16"></div>
      <div class="h-4 bg-gray-200 rounded w-24"></div>
    </div>
  </div>
</template>
