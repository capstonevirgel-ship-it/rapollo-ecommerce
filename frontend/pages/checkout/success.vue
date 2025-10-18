<script setup lang="ts">
import { onMounted } from 'vue'
import { useCartStore } from '~/stores/cart'

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

// Clear cart on success
onMounted(() => {
  cartStore.clearCart()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <!-- Success Icon -->
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>

        <!-- Success Message -->
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Successful!</h1>
        <p class="text-lg text-gray-600 mb-8">
          Thank you for your purchase. Your order has been processed and you will receive a confirmation email shortly.
        </p>

        <!-- Order Details -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
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
              <p class="text-gray-700">We'll prepare your order for shipping</p>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-blue-600 text-sm font-medium">3</span>
              </div>
              <p class="text-gray-700">You'll receive tracking information once shipped</p>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <NuxtLink
            to="/shop"
            class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
          >
            Continue Shopping
          </NuxtLink>
          <NuxtLink
            to="/my-tickets"
            class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
          >
            View My Orders
          </NuxtLink>
        </div>

        <!-- Support Info -->
        <div class="mt-8 text-center">
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