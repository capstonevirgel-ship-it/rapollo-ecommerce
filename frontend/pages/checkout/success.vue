<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '~/stores/cart'
import { usePurchaseStore } from '~/stores/purchase'
import { getImageUrl } from '~/utils/imageHelper'

// Define page meta
definePageMeta({
  layout: 'default'
})

// Set page title
useHead({
  title: 'Order Successful - Rapollo E-commerce',
  meta: [
    { name: 'description', content: 'Your order has been successfully placed at Rapollo E-commerce.' }
  ]
})

// Stores
const cartStore = useCartStore()
const purchaseStore = usePurchaseStore()
const route = useRoute()

// Reactive data
const purchase = ref<any>(null)
const loading = ref(true)
const error = ref<string | null>(null)

// Computed properties
const isTicketPurchase = computed(() => purchase.value?.type === 'ticket')
const orderNumber = computed(() => purchase.value?.order_number || `ORD-${purchase.value?.id}`)
const totalAmount = computed(() => purchase.value?.total || 0)
const purchaseDate = computed(() => {
  if (!purchase.value?.created_at) return ''
  return new Date(purchase.value.created_at).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
})

// Fetch purchase details
const fetchPurchaseDetails = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Get purchase_id from URL params or session storage
    const purchaseId = route.query.purchase_id || sessionStorage.getItem('last_purchase_id')
    
    if (!purchaseId) {
      throw new Error('No purchase ID found')
    }
    
    const purchaseData = await purchaseStore.fetchPurchaseById(Number(purchaseId))
    purchase.value = purchaseData
    
    // Clear cart on success
    cartStore.clearCart()
    
    // Clear session storage
    sessionStorage.removeItem('last_purchase_id')
    
  } catch (err: any) {
    error.value = err.message || 'Failed to fetch order details'
    console.error('Error fetching purchase details:', err)
  } finally {
    loading.value = false
  }
}

// Clear cart on success
onMounted(() => {
  fetchPurchaseDetails()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-gray-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-4">Loading Order Details...</h1>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-4">Unable to Load Order Details</h1>
        <p class="text-lg text-gray-600 mb-8">{{ error }}</p>
        <NuxtLink
          to="/my-orders"
          class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
        >
          View My Orders
        </NuxtLink>
      </div>

      <!-- Success State -->
      <div v-else-if="purchase" class="space-y-8">
        <!-- Success Header -->
      <div class="text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          </div>
          <h1 class="text-3xl font-bold text-gray-900 mb-4">
            {{ isTicketPurchase ? 'Tickets Purchased Successfully!' : 'Order Successful!' }}
          </h1>
          <p class="text-lg text-gray-600 mb-8">
            Thank you for your {{ isTicketPurchase ? 'ticket purchase' : 'purchase' }}. 
            {{ isTicketPurchase ? 'Your tickets have been confirmed' : 'Your order has been processed' }} and you will receive a confirmation email shortly.
          </p>
        </div>

        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Summary</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Order Number</h3>
              <p class="mt-1 text-lg font-semibold text-gray-900">{{ orderNumber }}</p>
            </div>
            <div>
              <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Order Date</h3>
              <p class="mt-1 text-lg font-semibold text-gray-900">{{ purchaseDate }}</p>
            </div>
            <div>
              <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Amount</h3>
              <p class="mt-1 text-lg font-semibold text-gray-900">₱{{ totalAmount.toLocaleString() }}</p>
            </div>
            <div>
              <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Status</h3>
              <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                {{ purchase.status }}
              </span>
            </div>
          </div>

          <!-- Event Details (for tickets) -->
          <div v-if="isTicketPurchase && purchase.event" class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Event Details</h3>
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="font-semibold text-gray-900">{{ purchase.event.title }}</h4>
              <p v-if="purchase.event.description" class="text-gray-600 mt-2">{{ purchase.event.description }}</p>
              <div v-if="purchase.event.event_date" class="mt-2 text-sm text-gray-500">
                Event Date: {{ new Date(purchase.event.event_date).toLocaleDateString('en-US', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit'
                }) }}
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div v-if="purchase.items && purchase.items.length > 0" class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Order Items</h3>
            <div class="space-y-4">
              <div 
                v-for="item in purchase.items" 
                :key="item.id"
                class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg"
              >
                <!-- Product Image -->
                <div v-if="item.variant?.images?.[0]" class="flex-shrink-0">
                  <img 
                    :src="getImageUrl(item.variant.images[0].url)" 
                    :alt="item.variant.product?.name || 'Product'"
                    class="w-16 h-16 object-cover rounded-lg"
                  />
                </div>
                <div v-else class="flex-shrink-0 w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>

                <!-- Product Details -->
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-gray-900 truncate">
                    {{ item.variant?.product?.name || 'Product' }}
                  </h4>
                  <div v-if="item.variant?.size || item.variant?.color" class="mt-1 text-sm text-gray-500">
                    <span v-if="item.variant?.size">{{ item.variant.size.name }}</span>
                    <span v-if="item.variant?.size && item.variant?.color">, </span>
                    <span v-if="item.variant?.color">{{ item.variant.color.name }}</span>
                  </div>
                  <div class="mt-1 text-sm text-gray-500">
                    Quantity: {{ item.quantity }}
                  </div>
        </div>

                <!-- Price -->
                <div class="text-right">
                  <p class="text-sm font-medium text-gray-900">
                    ₱{{ (item.price * item.quantity).toLocaleString() }}
                  </p>
                  <p class="text-xs text-gray-500">
                    ₱{{ item.price.toLocaleString() }} each
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">What's Next?</h2>
          <div class="space-y-3 text-left">
            <div class="flex items-center space-x-3">
              <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-blue-600 text-sm font-medium">1</span>
              </div>
              <p class="text-gray-700">You will receive an order confirmation email</p>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-blue-600 text-sm font-medium">2</span>
              </div>
              <p class="text-gray-700">
                {{ isTicketPurchase ? 'Your tickets are ready for download' : 'We\'ll prepare your order for shipping' }}
              </p>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-blue-600 text-sm font-medium">3</span>
              </div>
              <p class="text-gray-700">
                {{ isTicketPurchase ? 'Show your tickets at the event entrance' : 'You\'ll receive tracking information once shipped' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <NuxtLink
            :to="isTicketPurchase ? '/events' : '/shop'"
            class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
          >
            {{ isTicketPurchase ? 'Browse Events' : 'Continue Shopping' }}
          </NuxtLink>
          <NuxtLink
            :to="isTicketPurchase ? '/my-tickets' : '/my-orders'"
            class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
          >
            View My {{ isTicketPurchase ? 'Tickets' : 'Orders' }}
          </NuxtLink>
        </div>

        <!-- Support Info -->
        <div class="text-center">
          <p class="text-sm text-gray-500">
            Need help? 
            <NuxtLink to="/contact" class="text-blue-600 hover:text-blue-500 underline">
              Contact our support team
            </NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>