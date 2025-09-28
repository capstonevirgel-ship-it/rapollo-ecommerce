<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRatingStore } from '~/stores/rating'
import { useAlert } from '~/composables/useAlert'
import { getImageUrl } from '~/utils/imageHelper'
import type { ReviewableProduct } from '~/types'

definePageMeta({ layout: 'default' })

const ratingStore = useRatingStore()
const { success, error } = useAlert()

const isLoading = ref(false)

const purchasedProducts = computed(() => ratingStore.reviewableProducts)
const hasPurchasedProducts = computed(() => purchasedProducts.value.length > 0)

onMounted(async () => {
  isLoading.value = true
  try {
    await ratingStore.fetchReviewableProducts()
  } catch (err: any) {
    error('Failed to Load Products', err.message || 'Unable to load your purchased products.')
  } finally {
    isLoading.value = false
  }
})

const getProductImage = (product: any) => {
  // This would need to be implemented based on your product image structure
  return getImageUrl(null, 'product')
}

const navigateToProduct = (product: ReviewableProduct) => {
  navigateTo(`/shop/${product.product.slug}`)
}
</script>

<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">My Reviews</h1>
      <p class="text-gray-600">Review products you've purchased</p>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-zinc-900"></div>
    </div>

    <!-- No Purchased Products -->
    <div v-else-if="!hasPurchasedProducts" class="text-center py-16">
      <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-gray-900 mt-4 mb-2">No Purchased Products</h2>
      <p class="text-gray-600 mb-6">You haven't purchased any products yet. Start shopping to leave reviews!</p>
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
            <div v-if="item.has_rated" class="flex items-center space-x-2">
              <div class="flex items-center space-x-1">
                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
              <span class="text-sm font-medium text-green-600">Reviewed</span>
            </div>
            <div v-else class="flex items-center space-x-2">
              <div class="flex items-center space-x-1">
                <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
              <span class="text-sm font-medium text-orange-600">Pending Review</span>
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
              {{ item.has_rated ? 'Update Review' : 'Write Review' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
