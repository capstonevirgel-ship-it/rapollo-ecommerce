<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useCartStore } from '~/stores/cart'

// Define page meta
definePageMeta({
  layout: 'default'
})

// Stores
const authStore = useAuthStore()
const cartStore = useCartStore()

// Cart is already cleared by the checkout process, no need to clear again

const continueShopping = () => {
  navigateTo('/shop')
}

const viewOrders = () => {
  navigateTo('/my-orders')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow-sm p-8 text-center">
        <!-- Success Icon -->
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>

        <!-- Success Message -->
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Successful!</h1>
        <p class="text-lg text-gray-600 mb-6">
          Thank you for your purchase. Your order has been processed successfully and you will receive a confirmation email shortly.
        </p>

        <!-- Order Details -->
        <div class="bg-gray-50 rounded-lg p-6 mb-8">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">What's Next?</h2>
          <div class="space-y-3 text-left">
            <div class="flex items-start">
              <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3 mt-0.5">
                <span class="text-blue-600 text-xs font-bold">1</span>
              </div>
              <div>
                <p class="font-medium text-gray-900">Order Confirmation</p>
                <p class="text-sm text-gray-600">You'll receive an email confirmation with your order details</p>
              </div>
            </div>
            <div class="flex items-start">
              <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3 mt-0.5">
                <span class="text-blue-600 text-xs font-bold">2</span>
              </div>
              <div>
                <p class="font-medium text-gray-900">Order Processing</p>
                <p class="text-sm text-gray-600">We'll prepare your items for shipment</p>
              </div>
            </div>
            <div class="flex items-start">
              <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3 mt-0.5">
                <span class="text-blue-600 text-xs font-bold">3</span>
              </div>
              <div>
                <p class="font-medium text-gray-900">Shipping Updates</p>
                <p class="text-sm text-gray-600">You'll receive tracking information once your order ships</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            @click="continueShopping"
            class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
          >
            Continue Shopping
          </button>
          <button
            @click="viewOrders"
            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
          >
            View My Orders
          </button>
        </div>

        <!-- Support Information -->
        <div class="mt-8 pt-6 border-t border-gray-200">
          <p class="text-sm text-gray-500 mb-2">Need help with your order?</p>
          <div class="flex justify-center space-x-6 text-sm">
            <a href="/contact" class="text-blue-600 hover:text-blue-700">Contact Support</a>
            <a href="/faq" class="text-blue-600 hover:text-blue-700">FAQ</a>
            <a href="/track-order" class="text-blue-600 hover:text-blue-700">Track Order</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom styles for better UX */
.transition-colors {
  transition: all 0.2s ease-in-out;
}
</style>