<template>
  <div class="flex flex-col lg:flex-row gap-8 items-center justify-between mx-auto">
    <!-- Left Side - Gallery Skeleton -->
    <div class="flex flex-col gap-4 w-full lg:w-1/2">
      <!-- Main Product Image Skeleton -->
      <div class="relative w-full aspect-square rounded-2xl overflow-hidden bg-gray-200 animate-pulse"></div>
      
      <!-- Product Thumbnails Skeleton -->
      <div class="flex flex-row gap-3 w-full justify-center">
        <div v-for="n in 4" :key="n" class="relative w-1/4 aspect-square rounded-lg overflow-hidden bg-gray-200 animate-pulse"></div>
      </div>
    </div>

    <!-- Right Side - Product Details Skeleton -->
    <div class="flex-1 w-full lg:w-1/2 space-y-6 flex flex-col items-center lg:items-start">
      <!-- Product Name Skeleton -->
      <div class="text-center lg:text-left w-full space-y-2">
        <div class="h-10 bg-gray-200 rounded-lg animate-pulse w-3/4 mx-auto lg:mx-0"></div>
        <div class="h-6 bg-gray-200 rounded-lg animate-pulse w-1/2 mx-auto lg:mx-0"></div>
      </div>

      <!-- Rating Skeleton -->
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-1">
          <div v-for="n in 5" :key="n" class="w-5 h-5 bg-gray-200 rounded animate-pulse"></div>
        </div>
        <div class="h-4 w-8 bg-gray-200 rounded animate-pulse"></div>
        <div class="h-4 w-20 bg-gray-200 rounded animate-pulse"></div>
      </div>

      <!-- Price Skeleton -->
      <div class="border-t border-b border-gray-200 py-4 w-full text-center lg:text-left">
        <div class="h-12 bg-gray-200 rounded-lg animate-pulse w-1/2 mx-auto lg:mx-0"></div>
      </div>
    </div>
  </div>
</template>

