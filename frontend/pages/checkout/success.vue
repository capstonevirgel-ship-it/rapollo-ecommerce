<script setup lang="ts">
import { onMounted } from 'vue'
import { useCartStore } from '~/stores/cart'

// Define page meta
definePageMeta({
  layout: 'default'
})

// Stores
const cartStore = useCartStore()

// Clear cart after successful order
onMounted(() => {
  cartStore.clearCart()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
      <!-- Success Icon -->
      <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100">
        <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
      </div>

      <!-- Success Message -->
      <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h1>
        <p class="text-lg text-gray-600 mb-6">
          Thank you for your purchase. We've sent you a confirmation email with your order details.
        </p>
      </div>

      <!-- Order Details -->
      <div class="bg-white rounded-lg shadow-sm p-6 text-left">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">What's Next?</h2>
        <div class="space-y-3 text-sm text-gray-600">
          <div class="flex items-center">
            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3">
              <span class="text-blue-600 font-semibold text-xs">1</span>
            </div>
            <span>You'll receive an order confirmation email shortly</span>
          </div>
          <div class="flex items-center">
            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3">
              <span class="text-blue-600 font-semibold text-xs">2</span>
            </div>
            <span>We'll prepare your order for shipping</span>
          </div>
          <div class="flex items-center">
            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3">
              <span class="text-blue-600 font-semibold text-xs">3</span>
            </div>
            <span>You'll get tracking information once shipped</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="space-y-4">
        <NuxtLink
          to="/"
          class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
        >
          Continue Shopping
        </NuxtLink>
        
        <NuxtLink
          to="/my-orders"
          class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
        >
          View My Orders
        </NuxtLink>
      </div>

      <!-- Support Information -->
      <div class="text-sm text-gray-500">
        <p>Need help? Contact our support team at</p>
        <p class="font-medium text-primary-600">support@rapollo.com</p>
      </div>
    </div>
  </div>
</template>
