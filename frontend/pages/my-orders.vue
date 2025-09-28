<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Orders</h1>
        <p class="text-gray-600 mt-2">Track and manage your orders</p>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-zinc-900"></div>
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
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
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
          class="bg-white rounded-lg shadow-sm overflow-hidden"
        >
          <!-- Order Header -->
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-medium text-gray-900">Order #{{ order.id }}</h3>
                <p class="text-sm text-gray-500">Placed on {{ formatDate(order.created_at) }}</p>
              </div>
              <div class="text-right">
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getStatusColor(order.status)
                ]">
                  {{ order.status }}
                </span>
                <p class="text-lg font-semibold text-gray-900 mt-1">₱{{ formatPrice(order.total) }}</p>
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div class="px-6 py-4">
            <div class="space-y-3">
              <div
                v-for="item in order.items"
                :key="item.id"
                class="flex items-center space-x-4"
              >
                <img
                  :src="getImageUrl(item.variant?.images?.[0]?.url || item.variant?.product?.images?.[0]?.url || '')"
                  :alt="item.variant?.product?.name || 'Product'"
                  class="w-12 h-12 object-cover rounded-md"
                />
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-gray-900 truncate">
                    {{ item.variant?.product?.name || 'Product' }}
                  </h4>
                  <p class="text-sm text-gray-500">
                    {{ item.variant?.size?.name || '' }} {{ item.variant?.color?.name || '' }}
                  </p>
                  <p class="text-sm text-gray-500">Quantity: {{ item.quantity }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium text-gray-900">
                    ₱{{ formatPrice((item.price || 0) * (item.quantity || 0)) }}
                  </p>
                  <p class="text-xs text-gray-500">₱{{ formatPrice(item.price) }} each</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Actions -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-500">
                <p>Payment: {{ order.payment?.payment_method || 'N/A' }}</p>
                <p v-if="order.payment?.status">Status: {{ order.payment.status }}</p>
              </div>
              <div class="flex space-x-3">
                <button
                  class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  View Details
                </button>
                <button
                  v-if="order.status === 'processing' || order.status === 'completed'"
                  class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
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
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { usePurchaseStore } from '~/stores/purchase'
import { getImageUrl } from '~/utils/imageHelper'

// Define page meta
definePageMeta({
  layout: 'default',
  middleware: 'auth'
})

const authStore = useAuthStore()
const purchaseStore = usePurchaseStore()
const orders = ref<any[]>([])
const isLoading = ref(false)

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

const getStatusColor = (status: string) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'processing':
      return 'bg-yellow-100 text-yellow-800'
    case 'pending':
      return 'bg-blue-100 text-blue-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

onMounted(async () => {
  isLoading.value = true
  try {
    // Fetch real orders from the API
    const response = await $fetch('/api/purchases')
    orders.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch orders:', error)
    // Fallback to empty array if API fails
    orders.value = []
  } finally {
    isLoading.value = false
  }
})
</script>
