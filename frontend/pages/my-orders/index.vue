<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { usePurchaseStore } from '~/stores/purchase'
import { useRatingStore } from '~/stores/rating'
import { getImageUrl } from '~/utils/imageHelper'

// Define page meta
definePageMeta({
  layout: 'default',
  middleware: 'auth'
})

// Set page title
useHead({
  title: 'My Orders | RAPOLLO',
  meta: [
    { name: 'description', content: 'Track and manage your orders at Rapollo E-commerce. View order history and status updates.' }
  ]
})

const authStore = useAuthStore()
const purchaseStore = usePurchaseStore()
const ratingStore = useRatingStore()
const orders = ref<any[]>([])
const isLoading = ref(false)
const reviewedVariants = ref<Set<number>>(new Set())

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPrice = (price: any) => {
  if (price === null || price === undefined) return '0.00'
  const numPrice = typeof price === 'string' ? parseFloat(price) : Number(price)
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2)
}

const getItemUnitPriceValue = (item: any) => {
  const candidates = [
    item?.final_unit_price,
    item?.finalUnitPrice,
    item?.unit_price,
    item?.unitPrice,
    item?.price,
  ]

  for (const candidate of candidates) {
    if (candidate === undefined || candidate === null) continue
    const numeric = typeof candidate === 'string' ? parseFloat(candidate) : Number(candidate)
    if (!Number.isNaN(numeric)) {
      return numeric
    }
  }

  return 0
}

const getItemTotalPriceValue = (item: any) => {
  const candidates = [
    item?.final_total_price,
    item?.finalTotalPrice,
    item?.total_price,
    item?.totalPrice,
  ]

  for (const candidate of candidates) {
    if (candidate === undefined || candidate === null) continue
    const numeric = typeof candidate === 'string' ? parseFloat(candidate) : Number(candidate)
    if (!Number.isNaN(numeric)) {
      return numeric
    }
  }

  const unitPrice = getItemUnitPriceValue(item)
  const quantity = typeof item?.quantity === 'number' ? item.quantity : parseInt(item?.quantity ?? 0, 10)
  return unitPrice * (Number.isNaN(quantity) ? 0 : quantity)
}


const isVariantReviewed = (variantId: number) => {
  return reviewedVariants.value.has(variantId)
}

const navigateToProduct = (item: any) => {
  const product = item.variant?.product
  if (product?.subcategory?.category?.slug && product?.subcategory?.slug) {
    navigateTo(`/shop/${product.subcategory.category.slug}/${product.subcategory.slug}/${product.slug}`)
  } else {
    // Fallback to just product slug if category/subcategory info is not available
    navigateTo(`/shop/${product?.slug}`)
  }
}

const navigateToOrderDetails = (orderId: number) => {
  navigateTo(`/my-orders/${orderId}`)
}

onMounted(async () => {
  isLoading.value = true
  try {
    // Fetch real orders from the API
    const response = await $fetch('/api/product-purchases') as { data: any[] }
    orders.value = response.data || []
    
    // Fetch reviewed products to check which variants have been reviewed
    try {
      const reviewedProducts = await ratingStore.fetchReviewedProducts()
      reviewedVariants.value = new Set(reviewedProducts.map(p => p.variant_id))
    } catch (error) {
      console.error('Failed to fetch reviewed products:', error)
    }
  } catch (error) {
    console.error('Failed to fetch orders:', error)
    // Fallback to empty array if API fails
    orders.value = []
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Orders</h1>
        <p class="text-gray-600 mt-2">Track and manage your orders</p>
      </div>

      <!-- Content with Sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="lg:sticky lg:top-8 lg:self-start">
            <CustomerSidebar />
          </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-3">
          <!-- Loading State -->
          <div v-if="isLoading">
            <LoadingSpinner 
              size="md" 
              color="primary" 
              text="Loading orders..." 
              :show-text="true"
            />
          </div>

          <!-- Orders List -->
          <div v-else-if="orders.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No orders yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by placing your first order.</p>
            <div class="mt-6">
              <NuxtLink
                to="/shop"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-zinc-900 hover:bg-zinc-800"
              >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Start Shopping
              </NuxtLink>
            </div>
          </div>

          <div v-else class="space-y-6">
            <div
              v-for="order in orders"
              :key="order.id"
              class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden hover:shadow-xl transition-shadow duration-300"
            >
              <!-- Order Header -->
              <div class="px-6 py-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="text-xl font-bold text-gray-900">Order #{{ order.id }}</h3>
                    <p class="text-sm text-gray-600 mt-1">Placed on {{ formatDate(order.created_at) }}</p>
                  </div>
                  <div class="text-right">
                    <StatusBadge :status="order.status" type="purchase" />
                    <p class="text-2xl font-bold text-gray-900 mt-2">₱{{ formatPrice(order.total) }}</p>
                  </div>
                </div>
              </div>

              <!-- Order Items -->
              <div class="px-6 py-4">
                <div class="space-y-3">
                  <div
                    v-for="item in order.items"
                    :key="item.id"
                    class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                  >
                    <div class="relative">
                      <img
                        :src="getImageUrl(item.variant?.images?.[0]?.url || item.variant?.product?.images?.[0]?.url || '')"
                        :alt="item.variant?.product?.name || 'Product'"
                        class="w-20 h-20 object-cover rounded-lg shadow-sm border border-gray-200"
                        @error="(e) => { const target = e.target as HTMLImageElement; if (target) target.src = getImageUrl('', 'product') }"
                      />
                    </div>
                    <div class="flex-1 min-w-0">
                      <h4 class="text-base font-semibold text-gray-900 truncate mb-1">
                        {{ item.variant?.product?.name || 'Product' }}
                      </h4>
                      <div class="flex flex-wrap gap-2 text-sm text-gray-500 mb-2">
                        <span v-if="item.variant?.size?.name" class="inline-flex items-center px-2 py-1 bg-white rounded-md text-xs">
                          Size: {{ item.variant.size.name }}
                        </span>
                        <span v-if="item.variant?.color?.name" class="inline-flex items-center px-2 py-1 bg-white rounded-md text-xs">
                          Color: {{ item.variant.color.name }}
                        </span>
                      </div>
                      <p class="text-sm text-gray-600 font-medium">Quantity: {{ item.quantity }}</p>
                    </div>
                    <div class="text-right">
                      <p class="text-lg font-bold text-gray-900">
                        ₱{{ formatPrice(getItemTotalPriceValue(item)) }}
                      </p>
                      <p class="text-sm text-gray-500">₱{{ formatPrice(getItemUnitPriceValue(item)) }} each</p>
                      
                      <!-- Review Button for Completed Orders -->
                      <div v-if="order.status === 'completed' && item.variant?.id" class="mt-3">
                        <button
                          v-if="!isVariantReviewed(item.variant.id)"
                          @click="navigateToProduct(item)"
                          class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors shadow-sm"
                        >
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                          </svg>
                          Write Review
                        </button>
                        <div v-else class="flex items-center text-sm text-green-600 bg-green-50 px-3 py-2 rounded-lg">
                          <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                          </svg>
                          Reviewed
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Order Actions -->
              <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-500">
                      <p>Payment Status:</p>
                    </div>
                    <StatusBadge :status="order.payment?.status || 'pending'" type="payment" />
                  </div>
                  <div class="flex space-x-3">
                    <button
                      @click="navigateToOrderDetails(order.id)"
                      class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                      View Details
                    </button>
                    <button
                      v-if="order.status === 'processing' || order.status === 'completed'"
                      class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                      Track Package
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

