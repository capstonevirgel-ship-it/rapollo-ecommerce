<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePurchaseStore } from '~/stores/purchase'

// Define page meta
definePageMeta({
  layout: 'default'
})

const purchaseStore = usePurchaseStore()
const isLoading = ref(true)
const paymentStatus = ref('')
const paymentIntentId = ref('')

onMounted(async () => {
  // Get payment intent ID from URL params
  const route = useRoute()
  paymentIntentId.value = route.query.payment_intent_id as string

  if (paymentIntentId.value) {
    try {
      const result = await purchaseStore.retrievePaymentIntent(paymentIntentId.value)
      paymentStatus.value = result.updated_status
    } catch (error) {
      console.error('Error retrieving payment status:', error)
      paymentStatus.value = 'failed'
    }
  } else {
    paymentStatus.value = 'failed'
  }
  
  isLoading.value = false
})

const goToOrders = () => {
  navigateTo('/orders')
}

const goToShop = () => {
  navigateTo('/shop')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow-sm p-8 text-center">
        <div v-if="isLoading" class="space-y-4">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
          <p class="text-gray-600">Checking payment status...</p>
        </div>

        <div v-else-if="paymentStatus === 'paid'" class="space-y-6">
          <!-- Success Icon -->
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>

          <!-- Success Message -->
          <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h1>
            <p class="text-gray-600">
              Your payment has been processed successfully. You will receive a confirmation email shortly.
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button
              @click="goToOrders"
              class="px-6 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              View Orders
            </button>
            <button
              @click="goToShop"
              class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              Continue Shopping
            </button>
          </div>
        </div>

        <div v-else class="space-y-6">
          <!-- Error Icon -->
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100">
            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </div>

          <!-- Error Message -->
          <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Failed</h1>
            <p class="text-gray-600">
              Unfortunately, your payment could not be processed. Please try again or contact support if the problem persists.
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button
              @click="() => navigateTo('/checkout')"
              class="px-6 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              Try Again
            </button>
            <button
              @click="goToShop"
              class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              Continue Shopping
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
