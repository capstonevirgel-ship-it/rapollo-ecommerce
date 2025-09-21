<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { usePurchaseStore } from '~/stores/purchase'
import { getImageUrl } from '~/utils/imageHelper'

// Define page meta
definePageMeta({
  layout: 'default'
})

// Stores
const cartStore = useCartStore()
const authStore = useAuthStore()
const purchaseStore = usePurchaseStore()
const { cart } = storeToRefs(cartStore)

// State
const isLoading = ref(false)
const paymentStatus = ref('pending') // pending, processing, success, failed
const paymentData = ref(null)

// Computed properties
const cartItems = computed(() => {
  return cart.value.map(item => ({
    id: item.id,
    variant: item.variant || {},
    product: item.variant?.product || {},
    quantity: item.quantity,
    image: getImageUrl(item.variant?.images?.[0]?.url || item.variant?.product?.images?.[0]?.url || ''),
    price: item.variant?.price || 0
  }))
})

const subtotal = computed(() => 
  cartItems.value.reduce((sum, item) => sum + (item.price * item.quantity), 0)
)

const shipping = computed(() => 5.99)
const tax = computed(() => subtotal.value * 0.08)
const total = computed(() => subtotal.value + shipping.value + tax.value)

// PayMongo checkout flow
const proceedToPayMongo = async () => {
  isLoading.value = true
  paymentStatus.value = 'processing'

  try {
    // Check if user is authenticated
    if (!authStore.isAuthenticated) {
      throw new Error('User not authenticated')
    }
    
    // Ensure cart is loaded with relationships
    await cartStore.index()
    
    // Check if cart is empty
    if (!cart.value || cart.value.length === 0) {
      throw new Error('Cart is empty')
    }
    
    // Prepare cart data for PayMongo
    const cartData = {
      cart_data: {
        items: cart.value.map((item) => {
          if (!item.variant) {
            throw new Error(`Variant not loaded for cart item ${item.id}`)
          }
          
          if (!item.variant.price) {
            throw new Error(`Price not found for variant ${item.variant_id}`)
          }
          
          return {
            product_variant_id: item.variant_id,
            quantity: item.quantity,
            price: item.variant.price
          }
        })
      }
    }
    
    console.log('Creating PayMongo payment...', cartData)
    
    // Create PayMongo payment intent
    const payment = await purchaseStore.createPayMongoPayment(cartData)
    paymentData.value = payment
    console.log('PayMongo payment created:', payment)
    
    // Redirect to PayMongo gateway
    window.location.href = payment.redirect_url
    
  } catch (error) {
    console.error('PayMongo checkout failed:', error)
    paymentStatus.value = 'failed'
    alert(`Checkout failed: ${error.message}`)
  } finally {
    isLoading.value = false
  }
}

// Load cart data on mount
onMounted(async () => {
  if (authStore.isAuthenticated) {
    await cartStore.index()
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
        <p class="mt-2 text-gray-600">Review your order and complete payment</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Order Summary -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Summary</h2>
            
            <!-- Cart Items -->
            <div class="space-y-4">
              <div
                v-for="item in cartItems"
                :key="item.id"
                class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg"
              >
                <img
                  :src="item.image"
                  :alt="item.product.name"
                  class="w-20 h-20 object-cover rounded-lg"
                />
                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-medium text-gray-900 truncate">
                    {{ item.product.name }}
                  </h3>
                  <p class="text-sm text-gray-500">
                    Quantity: {{ item.quantity }}
                  </p>
                  <p class="text-sm text-gray-500">
                    Price: ₱{{ item.price.toFixed(2) }} each
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-lg font-semibold text-gray-900">
                    ₱{{ (item.price * item.quantity).toFixed(2) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Order Totals -->
            <div class="mt-6 space-y-3 border-t pt-6">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal</span>
                <span class="text-gray-900">₱{{ subtotal.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Shipping</span>
                <span class="text-gray-900">₱{{ shipping.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Tax</span>
                <span class="text-gray-900">₱{{ tax.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold border-t pt-3">
                <span class="text-gray-900">Total</span>
                <span class="text-gray-900">₱{{ total.toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Section -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment</h3>
            
            <!-- Payment Status -->
            <div v-if="paymentStatus === 'pending'" class="text-center py-8">
              <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
              </div>
              <h4 class="text-lg font-medium text-gray-900 mb-2">Ready to Pay</h4>
              <p class="text-gray-600 mb-6">Click the button below to proceed to secure payment</p>
              
              <button
                @click="proceedToPayMongo"
                :disabled="isLoading || !authStore.isAuthenticated"
                class="w-full bg-blue-600 text-white py-3 px-4 rounded-md font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isLoading">Processing...</span>
                <span v-else-if="!authStore.isAuthenticated">Login Required</span>
                <span v-else>Pay with PayMongo</span>
              </button>
            </div>

            <!-- Processing Status -->
            <div v-else-if="paymentStatus === 'processing'" class="text-center py-8">
              <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-yellow-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </div>
              <h4 class="text-lg font-medium text-gray-900 mb-2">Processing Payment</h4>
              <p class="text-gray-600">Redirecting to PayMongo...</p>
            </div>

            <!-- Success Status -->
            <div v-else-if="paymentStatus === 'success'" class="text-center py-8">
              <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <h4 class="text-lg font-medium text-gray-900 mb-2">Payment Successful!</h4>
              <p class="text-gray-600 mb-6">Your order has been processed successfully.</p>
              <NuxtLink
                to="/checkout/success"
                class="inline-block w-full bg-green-600 text-white py-3 px-4 rounded-md font-medium hover:bg-green-700 transition"
              >
                View Order Details
              </NuxtLink>
            </div>

            <!-- Failed Status -->
            <div v-else-if="paymentStatus === 'failed'" class="text-center py-8">
              <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </div>
              <h4 class="text-lg font-medium text-gray-900 mb-2">Payment Failed</h4>
              <p class="text-gray-600 mb-6">There was an issue processing your payment. Please try again.</p>
              <button
                @click="paymentStatus = 'pending'"
                class="w-full bg-red-600 text-white py-3 px-4 rounded-md font-medium hover:bg-red-700 transition"
              >
                Try Again
              </button>
            </div>

            <!-- Security Badge -->
            <div class="mt-6 text-center">
              <div class="flex items-center justify-center space-x-2 text-sm text-gray-500">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                <span>Secure payment by PayMongo</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

