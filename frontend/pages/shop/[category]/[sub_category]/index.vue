<script setup lang="ts">
import Breadcrumbs from '@/components/navigation/Breadcrumbs.vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useSubcategoryStore } from '~/stores/subcategory'
import { getImageUrl, getPrimaryImage } from '~/utils/imageHelper'
import { onMounted, ref } from 'vue'

const route = useRoute()
const categorySlug = route.params.category as string
const subcategorySlug = route.params['sub_category'] as string

// Store
const subcategoryStore = useSubcategoryStore()
const { subcategory, loading, error } = storeToRefs(subcategoryStore)

// Local state for data fetching
const isDataLoaded = ref(false)

// Fetch subcategory data on component mount
onMounted(async () => {
  try {
    await subcategoryStore.fetchSubcategoryBySlug(subcategorySlug as string)
    isDataLoaded.value = true
  } catch (error) {
    console.error('Failed to fetch subcategory:', error)
    isDataLoaded.value = true // Set to true even on error to stop skeleton
  }
})

// Set page title with meta_title from backend
useHead(() => {
  if (subcategory.value) {
    const title = subcategory.value.meta_title || subcategory.value.name
    return {
      title: `${title} | monogram`,
      meta: [
        { name: 'description', content: subcategory.value.meta_description || `Shop ${subcategory.value.name} products at monogram E-commerce` }
      ]
    }
  }
  return {
    title: 'Subcategory | monogram'
  }
})

// Helper function to format price safely
const formatPrice = (price: number | undefined | null): string => {
  if (price === null || price === undefined || isNaN(Number(price))) {
    return '0.00'
  }
  return Number(price).toFixed(2)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <Breadcrumbs />
    
    <!-- Loading skeleton -->
    <div v-if="!isDataLoaded || loading" class="space-y-4 animate-pulse">
      <div class="h-10 bg-gray-200 rounded w-1/3"></div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="bg-white rounded-lg shadow-sm animate-pulse overflow-hidden">
          <div class="w-full h-48 bg-gray-200"></div>
          <div class="p-4 space-y-2">
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-5 bg-gray-200 rounded w-1/2"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error -->
    <p v-else-if="error" class="text-red-500 text-center py-20">{{ error }}</p>

    <!-- Subcategory content -->
    <div v-else-if="subcategory && isDataLoaded">
      <h1 class="text-3xl font-bold mb-6">{{ subcategory.name }}</h1>

      <div v-if="subcategory.products && subcategory.products.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <NuxtLink
          v-for="product in subcategory.products"
          :key="product.slug"
          :to="`/shop/${categorySlug}/${subcategorySlug}/${product.slug}`"
          class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 block group overflow-hidden"
        >
          <!-- Product image -->
          <div class="relative overflow-hidden">
            <img
              :src="getImageUrl(getPrimaryImage(product.images) || null, 'product')"
              :alt="product.name"
              class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-200"
            />
            <!-- Overlay on hover -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-200"></div>
          </div>

          <!-- Product info -->
          <div class="p-4">
            <h2 class="text-sm font-medium text-gray-900 truncate mb-2">
              {{ product.name }}
            </h2>
            <p class="text-primary-600 font-semibold text-lg">
              ₱{{ formatPrice(product.price ?? 0) }}
            </p>
          </div>
        </NuxtLink>
      </div>

      <p v-else class="text-gray-500 text-center py-20">No products found in this subcategory.</p>
    </div>

    <!-- Not found -->
    <p v-else class="text-red-500 text-center py-20">Subcategory not found.</p>
    </div>
  </div>
</template>