<script setup lang="ts">
import Breadcrumbs from '@/components/navigation/Breadcrumbs.vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useCategoryStore } from '~/stores/category'
import { getImageUrl } from '~/utils/imageHelper'
import { onMounted, ref } from 'vue'

const route = useRoute()
const categorySlug = route.params.category as string

// Store
const categoryStore = useCategoryStore()
const { category, loading, error } = storeToRefs(categoryStore)

// Local state for data fetching
const isDataLoaded = ref(false)

// Fetch category data on component mount
onMounted(async () => {
  console.log('Category page mounted, fetching category:', categorySlug)
  try {
    await categoryStore.fetchCategoryBySlug(categorySlug as string)
    console.log('Category fetched successfully:', category.value?.name)
    isDataLoaded.value = true
  } catch (error) {
    console.error('Failed to fetch category:', error)
    isDataLoaded.value = true // Set to true even on error to stop skeleton
  }
})

// Set page title with meta_title from backend
useHead(() => {
  if (category.value) {
    const title = category.value.meta_title || category.value.name
    return {
      title: `${title} | RAPOLLO`,
      meta: [
        { name: 'description', content: category.value.meta_description || `Shop ${category.value.name} products at Rapollo E-commerce` }
      ]
    }
  }
  return {
    title: 'Category | RAPOLLO'
  }
})
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <Breadcrumbs />
    
    <!-- Loading skeleton -->
    <div v-if="!isDataLoaded || loading" class="space-y-4 animate-pulse">
      <div class="h-12 bg-gray-200 rounded w-1/3"></div>
      <div class="h-6 bg-gray-200 rounded w-1/2"></div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="i in 4" :key="i" class="h-32 bg-gray-200 rounded"></div>
      </div>
    </div>

    <!-- Error -->
    <p v-else-if="error" class="text-red-500 text-center py-20">{{ error }}</p>

    <!-- Category content -->
    <div v-else-if="category && isDataLoaded">
      <div class="mb-12">
        <h1 class="text-4xl font-bold text-gray-900">{{ category.name }}</h1>
        <p class="mt-3 text-lg text-gray-600">Explore our {{ category.name.toLowerCase() }}</p>
      </div>

      <div v-if="category.subcategories && category.subcategories.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <NuxtLink
          v-for="sub in category.subcategories"
          :key="sub.slug"
          :to="`/shop/${category.slug}/${sub.slug}`"
          class="group relative block p-8 border border-gray-200 rounded-lg bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)]"
        >
          <div class="flex flex-col items-start">
            <h2 class="text-xl font-semibold text-gray-900 group-hover:text-black transition-colors relative pb-1">
              <span class="relative z-10">{{ sub.name }}</span>
              <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-black group-hover:w-full transition-all duration-300 ease-out"></span>
            </h2>
            <div class="mt-6 w-full flex items-center justify-between">
              <span class="text-sm font-medium text-gray-500 group-hover:text-gray-700 transition-colors">
                View products
              </span>
              <svg 
                class="h-5 w-5 text-gray-400 group-hover:text-black group-hover:translate-x-1 transition-all duration-300" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>
        </NuxtLink>
      </div>

      <p v-else class="text-gray-500 text-center py-20">No subcategories found for this category.</p>
    </div>

    <!-- Not found -->
    <p v-else class="text-red-500 text-center py-20">Category not found.</p>
  </div>
</template>