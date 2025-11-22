<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { getImageUrl, getPrimaryImage } from '~/utils/imageHelper'
import TrendingSkeleton from '~/components/skeleton/TrendingSkeleton.vue'

interface Product {
  id: number
  name: string
  slug: string
  image?: string | null
  price?: number
  rating?: number
  total_ratings?: number
  category_slug?: string
  subcategory_slug?: string
  brand?: {
    name: string
  }
  // Legacy support
  images?: Array<{ url: string }>
  variants?: Array<{}>
  subcategory?: {
    slug: string
    category?: {
      slug: string
    }
  }
}

interface Props {
  products: Product[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const currentIndex = ref(0)
const isHovered = ref(false)

let intervalId: ReturnType<typeof setInterval> | null = null

const setProduct = (index: number) => {
  currentIndex.value = index
}

const startAutoplay = () => {
  // Clear existing interval
  if (intervalId) {
    clearInterval(intervalId)
  }
  
  // Start new interval if we have products
  if (props.products.length > 1) {
    intervalId = setInterval(() => {
      currentIndex.value = (currentIndex.value + 1) % props.products.length
    }, 5000)
  }
}

const stopAutoplay = () => {
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
}

const formatPrice = (price: number | undefined) => {
  if (!price) return '₱0.00'
  return `₱${price.toFixed(2)}`
}

const handleProductClick = (product: Product) => {
  const categorySlug = product.category_slug || product.subcategory?.category?.slug
  const subcategorySlug = product.subcategory_slug || product.subcategory?.slug
  navigateTo(`/shop/${categorySlug}/${subcategorySlug}/${product.slug}`)
}

// Watch for changes in products array
watch(() => props.products, (newProducts) => {
  if (newProducts && newProducts.length > 0) {
    currentIndex.value = 0 // Reset to first product
    startAutoplay()
  } else {
    stopAutoplay()
  }
}, { immediate: true })

// Watch for loading state changes
watch(() => props.loading, (isLoading) => {
  if (isLoading) {
    stopAutoplay()
  } else if (props.products.length > 0) {
    startAutoplay()
  }
})

onMounted(() => {
  if (props.products.length > 0) {
    startAutoplay()
  }
})

onUnmounted(() => {
  stopAutoplay()
})
</script>

<template>
  <div class="max-w-6xl mx-auto relative">
    <!-- Loading State -->
    <TrendingSkeleton v-if="loading" />

    <!-- No Products State -->
    <div v-else-if="products.length === 0" class="text-center py-12">
      <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No Trending Products</h3>
      <p class="text-gray-500">Check back later for hot items!</p>
    </div>

    <!-- Products Gallery -->
    <div v-else class="flex flex-col lg:flex-row gap-8 items-center justify-between mx-auto">
      <!-- Left Side - Gallery -->
      <div class="flex flex-col gap-4 w-full lg:w-1/2">
        <!-- Main Product Image -->
        <div 
          class="relative w-full aspect-square rounded-2xl overflow-hidden shadow-lg bg-white cursor-pointer group"
          @click="handleProductClick(products[currentIndex])"
        >

          <img
            :src="getImageUrl(products[currentIndex].image || getPrimaryImage(products[currentIndex].images) || null)"
            :alt="products[currentIndex].name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        </div>

        <!-- Product Thumbnails -->
        <div class="flex flex-row gap-3 w-full justify-center">
          <div
            v-for="(product, idx) in products.slice(0, 4)"
            :key="product.id"
            @click="setProduct(idx)"
            :class="[
              'relative w-1/4 aspect-square rounded-lg overflow-hidden cursor-pointer transition-all duration-300 group bg-white border-2',
              currentIndex === idx 
                ? 'border-gray-900 scale-105 shadow-lg' 
                : 'border-gray-200 hover:border-gray-400 hover:scale-105'
            ]"
          >
            <img
              :src="getImageUrl(product.image || getPrimaryImage(product.images) || null)"
              :alt="product.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
          </div>
        </div>
      </div>

      <!-- Right Side - Product Details -->
      <div class="flex-1 w-full lg:w-1/2 space-y-6 flex flex-col items-center lg:items-start">
        <!-- Product Name -->
        <div class="text-center lg:text-left">
          <h3 class="text-4xl font-winner-extra-bold text-gray-900 mb-2">{{ products[currentIndex].name }}</h3>
          <p v-if="products[currentIndex].brand" class="text-gray-600 text-lg">
            by {{ products[currentIndex].brand?.name }}
          </p>
        </div>

        <!-- Rating -->
        <div v-if="products[currentIndex].rating" class="flex items-center gap-2">
          <div class="flex items-center gap-1">
            <svg 
              v-for="star in 5" 
              :key="star"
              class="w-5 h-5"
              :class="star <= (products[currentIndex].rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
          <span class="text-gray-600 font-medium">{{ products[currentIndex].rating }}</span>
          <span class="text-gray-400 text-sm">({{ products[currentIndex].total_ratings || 0 }} reviews)</span>
        </div>

        <!-- Price -->
        <div class="border-t border-b border-gray-200 py-4 w-full text-center lg:text-left">
          <div class="flex items-baseline gap-2 justify-center lg:justify-start">
            <span class="text-5xl font-winner-extra-bold text-gray-900">
              {{ formatPrice(products[currentIndex].price || 0) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- View All Products Button -->
    <div class="text-center mt-4 md:mt-8">
      <NuxtLink
        to="/shop?is_hot=true"
        class="inline-flex items-center px-6 py-3 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors"
      >
        View All Trending Products
        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </NuxtLink>
    </div>
  </div>
</template>

