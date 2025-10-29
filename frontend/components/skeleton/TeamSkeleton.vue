<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <div v-for="i in 3" :key="i" class="bg-white rounded-lg shadow-sm p-6 text-center animate-pulse">
      <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-4"></div>
      <div class="h-6 bg-gray-200 rounded mb-2"></div>
      <div class="h-4 bg-gray-200 rounded mb-3"></div>
      <div class="h-4 bg-gray-200 rounded"></div>
    </div>
  </div>
</template>
