<template>
  <div class="relative w-full">
    <!-- Carousel Skeleton -->
    <div class="flex gap-4 overflow-hidden">
      <div 
        v-for="n in 3" 
        :key="n" 
        class="flex-shrink-0 p-4"
        :class="n === 1 ? 'w-full md:w-1/2 lg:w-1/3' : 'hidden md:block md:w-1/2 lg:w-1/3'"
      >
        <div class="bg-gray-50 rounded-lg overflow-hidden shadow-sm h-full flex flex-col">
          <!-- Image Skeleton -->
          <div class="aspect-square bg-gray-200 animate-pulse"></div>
          
          <!-- Content Skeleton -->
          <div class="p-4 space-y-2">
            <div class="h-6 bg-gray-200 rounded animate-pulse w-3/4"></div>
            <div class="h-5 bg-gray-200 rounded animate-pulse w-1/2"></div>
          </div>
          
          <!-- Button Skeleton -->
          <div class="p-4">
            <div class="h-10 bg-gray-200 rounded-md animate-pulse w-full"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dots Indicator Skeleton -->
    <div class="flex justify-center mt-4 space-x-2">
      <div v-for="n in 5" :key="n" class="w-3 h-3 rounded-full bg-gray-200 animate-pulse"></div>
    </div>
  </div>
</template>

