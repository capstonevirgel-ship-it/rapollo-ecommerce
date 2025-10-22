<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRatingStore } from '~/stores/rating'
import { useAlert } from '~/composables/useAlert'
import { getImageUrl } from '~/utils/imageHelper'
import type { ReviewableProduct } from '~/types'

definePageMeta({ layout: 'default' })

// Set page title
useHead({
  title: 'My Reviews - Rapollo E-commerce',
  meta: [
    { name: 'description', content: 'View and manage your product reviews at Rapollo E-commerce.' }
  ]
})

const ratingStore = useRatingStore()
const { success, error } = useAlert()

const isLoading = ref(false)

const purchasedProducts = computed(() => ratingStore.reviewableProducts)
const hasPurchasedProducts = computed(() => purchasedProducts.value.length > 0)

onMounted(async () => {
  isLoading.value = true
  try {
    await ratingStore.fetchReviewedProducts()
  } catch (err: any) {
    error('Failed to Load Products', err.message || 'Unable to load your reviewed products.')
  } finally {
    isLoading.value = false
  }
})

const getProductImage = (product: any) => {
  // This would need to be implemented based on your product image structure
  return getImageUrl(null, 'product')
}

const navigateToProduct = (product: ReviewableProduct) => {
  // Check if we have category and subcategory information
  if (product.product.subcategory?.category?.slug && product.product.subcategory?.slug) {
    navigateTo(`/shop/${product.product.subcategory.category.slug}/${product.product.subcategory.slug}/${product.product.slug}`)
  } else {
    // Fallback to just product slug if category/subcategory info is not available
    navigateTo(`/shop/${product.product.slug}`)
  }
}
</script>

<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">My Reviews</h1>
      <p class="text-gray-600">Products you've reviewed</p>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <LoadingSpinner 
        size="md" 
        color="primary" 
        text="Loading reviews..." 
        :show-text="true"
      />
    </div>

    <!-- No Reviewed Products -->
    <div v-else-if="!hasPurchasedProducts" class="text-center py-16">
      <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-gray-900 mt-4 mb-2">No Reviews Yet</h2>
      <p class="text-gray-600 mb-6">You haven't reviewed any products yet. Start shopping and leave reviews!</p>
      <NuxtLink 
        to="/shop" 
        class="inline-flex items-center justify-center px-6 py-3 bg-zinc-900 text-white font-medium rounded-lg hover:bg-zinc-800 transition-colors"
      >
        Start Shopping
      </NuxtLink>
    </div>

    <!-- Purchased Products Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="item in purchasedProducts"
        :key="item.variant_id"
        class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
      >
        <!-- Product Image -->
        <div class="aspect-w-16 aspect-h-12 bg-gray-200">
          <img
            :src="getProductImage(item.product)"
            :alt="item.product.name"
            class="w-full h-48 object-cover"
          />
        </div>

        <!-- Product Info -->
        <div class="p-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
            {{ item.product.name }}
          </h3>
          
          <div class="text-sm text-gray-500 mb-3">
            <p>Purchased on {{ new Date(item.purchased_at).toLocaleDateString() }}</p>
            <p>Quantity: {{ item.quantity }}</p>
          </div>

          <!-- Review Status -->
          <div class="mb-4">
            <div class="flex items-center space-x-2">
              <div class="flex items-center space-x-1">
                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
              <span class="text-sm font-medium text-green-600">Reviewed</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex space-x-2">
            <button
              @click="navigateToProduct(item)"
              class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            >
              View Product
            </button>
            <button
              @click="navigateToProduct(item)"
              class="flex-1 px-3 py-2 text-sm font-medium text-white bg-zinc-900 rounded-md hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 transition-colors"
            >
              Update Review
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
