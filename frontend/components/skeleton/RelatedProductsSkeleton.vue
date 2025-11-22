<template>
  <div class="mt-12">
    <!-- Title Skeleton -->
    <div class="h-8 bg-gray-200 rounded w-48 mb-4 animate-pulse"></div>
    
    <!-- Products Grid Skeleton -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="n in 3" 
        :key="n" 
        class="bg-white rounded-lg shadow-sm overflow-hidden"
      >
        <!-- Image Skeleton -->
        <div class="w-full h-48 bg-gray-200 animate-pulse"></div>
        
        <!-- Content Skeleton -->
        <div class="p-4 space-y-2">
          <div class="h-5 bg-gray-200 rounded animate-pulse w-3/4"></div>
          <div class="h-6 bg-gray-200 rounded animate-pulse w-1/2"></div>
        </div>
      </div>
    </div>
  </div>
</template>

