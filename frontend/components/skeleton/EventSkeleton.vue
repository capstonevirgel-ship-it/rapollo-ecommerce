<template>
  <div class="flex flex-col md:flex-row gap-6 items-start">
    <!-- Main Event Display Skeleton -->
    <div class="w-full h-[400px] bg-gray-200 rounded-2xl animate-pulse"></div>
    
    <!-- Event Thumbnails Skeleton -->
    <div class="flex flex-row md:flex-col gap-2 w-full md:w-[120px]">
      <div v-for="n in 4" :key="n" class="w-full h-20 bg-gray-200 rounded-lg animate-pulse"></div>
    </div>
  </div>
</template>

