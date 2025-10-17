<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <!-- Success Icon -->
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
          <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        
        <!-- Success Message -->
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Successful! 🎉</h1>
        <p class="text-lg text-gray-600 mb-8">
          Thank you for your purchase! Your order has been confirmed and will be processed shortly.
        </p>
        
        <!-- Order Details -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-gray-600">Order Number:</span>
              <span class="font-medium">#{{ orderNumber }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Payment Method:</span>
              <div class="flex flex-col items-end">
                <span class="font-medium">GCash</span>
                <PayMongoBranding />
              </div>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Status:</span>
              <span class="font-medium text-green-600">Completed</span>
            </div>
          </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="space-y-4">
          <NuxtLink
            to="/my-orders"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500"
          >
            View My Orders
          </NuxtLink>
          
          <NuxtLink
            to="/shop"
            class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500"
          >
            Continue Shopping
          </NuxtLink>
        </div>
        
        <!-- Additional Info -->
        <div class="mt-8 text-sm text-gray-500">
          <p>You will receive an email confirmation shortly.</p>
          <p>If you have any questions, please contact our support team.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useCartStore } from '~/stores/cart'
import PayMongoBranding from '@/components/PayMongoBranding.vue'

// Stores
const cartStore = useCartStore()

// Generate a random order number for demo purposes
const orderNumber = ref('')

onMounted(() => {
  // Clear cart on successful payment
  cartStore.clearCart()
  
  // Generate a random order number
  orderNumber.value = 'ORD-' + Math.random().toString(36).substr(2, 9).toUpperCase()
})

// Set page title
useHead({
  title: 'Payment Successful - Rapollo E-commerce'
})
</script>