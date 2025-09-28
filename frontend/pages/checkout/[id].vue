<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { usePurchaseStore } from '~/stores/purchase'
import { useAlert } from '~/composables/useAlert'
import { getImageUrl } from '~/utils/imageHelper'
import BillingForm from '~/components/BillingForm.vue'
import OrderReview from '~/components/OrderReview.vue'

// Define page meta
definePageMeta({
  layout: 'default'
})

// Stores
const cartStore = useCartStore()
const authStore = useAuthStore()
const purchaseStore = usePurchaseStore()
const { success, error } = useAlert()
const { cart } = storeToRefs(cartStore)

// State
const isLoading = ref(false)
const currentStep = ref(1) // 1: billing, 2: review, 3: processing
const billingData = ref<any>(null)
const paymentStatus = ref('pending') // pending, processing, success, failed
const paymentData = ref<any>(null)

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

// Handle billing form submission
const handleBillingSubmit = (data: any) => {
  billingData.value = data
  currentStep.value = 2 // Move to review step
}

// Handle order confirmation
const handleConfirmOrder = async () => {
  currentStep.value = 3 // Move to processing step
  isLoading.value = true

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
    
    // Create purchase
    const purchase = await purchaseStore.createPurchase(cart.value)
    console.log('Purchase created:', purchase)
    
    // Create payment
    const payment = await purchaseStore.createPayment(purchase.id, total.value, billingData.value.paymentMethod)
    paymentData.value = payment
    console.log('Payment created:', payment)
    
    // Show success message
    success('Order Confirmed', 'Your order has been placed successfully!')
    
    // Store order data for success page
    const orderData = {
      orderId: purchase.id || 'RAP-' + Date.now(),
      total: total.value,
      paymentMethod: billingData.value.paymentMethod,
      billingData: billingData.value,
      purchase: purchase,
      payment: payment
    }
    
    sessionStorage.setItem('orderData', JSON.stringify(orderData))
    
    // Navigate to success page
    await navigateTo('/payment/success')
    
  } catch (error: any) {
    console.error('Checkout failed:', error)
    
    // Show error message
    error('Checkout Failed', error?.message || 'Unable to process your order. Please try again.')
    
    // Store error data for failure page
    const errorData = {
      message: error?.message || 'An unknown error occurred',
      code: error?.code || 'CHECKOUT_ERROR',
      amount: total.value,
      paymentMethod: billingData.value.paymentMethod,
      orderId: 'FAILED-' + Date.now()
    }
    
    sessionStorage.setItem('paymentError', JSON.stringify(errorData))
    
    // Navigate to failure page
    await navigateTo('/payment/failure')
  } finally {
    isLoading.value = false
  }
}

// Go back to billing step
const goBackToBilling = () => {
  currentStep.value = 1
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
        <p class="text-gray-600 mt-2">Complete your purchase in a few simple steps</p>
        
        <!-- Progress Steps -->
        <div class="mt-6">
          <nav aria-label="Progress">
            <ol class="flex items-center space-x-5">
              <li class="flex items-center">
                <div class="flex items-center">
                  <div :class="[
                    'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                    currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-500'
                  ]">
                    <span class="text-sm font-medium">1</span>
                  </div>
                  <span class="ml-3 text-sm font-medium text-gray-900">Billing</span>
                </div>
              </li>
              
              <li class="flex items-center">
                <div class="flex items-center">
                  <div :class="[
                    'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                    currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-500'
                  ]">
                    <span class="text-sm font-medium">2</span>
                  </div>
                  <span class="ml-3 text-sm font-medium text-gray-900">Review</span>
                </div>
              </li>
              
              <li class="flex items-center">
                <div class="flex items-center">
                  <div :class="[
                    'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                    currentStep >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-500'
                  ]">
                    <span class="text-sm font-medium">3</span>
                  </div>
                  <span class="ml-3 text-sm font-medium text-gray-900">Payment</span>
                </div>
              </li>
            </ol>
          </nav>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
          <!-- Step 1: Billing Form -->
          <div v-if="currentStep === 1">
            <BillingForm @submit="handleBillingSubmit" />
          </div>
          
          <!-- Step 2: Order Review -->
          <div v-else-if="currentStep === 2">
            <OrderReview
              :cart-items="cartItems"
              :billing-data="billingData"
              :subtotal="subtotal"
              :shipping="shipping"
              :tax="tax"
              :total="total"
              :is-processing="false"
              @back="goBackToBilling"
              @confirm="handleConfirmOrder"
            />
            </div>

          <!-- Step 3: Processing -->
          <div v-else-if="currentStep === 3" class="bg-white rounded-lg shadow-sm p-8">
            <div class="text-center">
              <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-blue-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </div>
              <h3 class="text-xl font-semibold text-gray-900 mb-2">Processing Your Order</h3>
              <p class="text-gray-600">Please wait while we process your payment and create your order...</p>
            </div>
          </div>
        </div>

        <!-- Order Summary Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
            
            <div v-if="cartItems.length === 0" class="text-center py-4">
              <p class="text-gray-500">Your cart is empty</p>
            </div>
            
            <div v-else>
            <!-- Cart Items -->
              <div class="space-y-3 mb-6">
                <div v-for="item in cartItems.slice(0, 3)" :key="item.id" class="flex items-center space-x-3">
                <img
                  :src="item.image"
                    :alt="item.product.name || 'Product'"
                    class="w-12 h-12 object-cover rounded-md"
                />
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-gray-900 truncate">
                      {{ item.product.name || 'Product' }}
                    </h4>
                    <p class="text-xs text-gray-500">Qty: {{ item.quantity }}</p>
                </div>
                  <p class="text-sm font-medium text-gray-900">
                    ₱{{ (item.price * item.quantity).toFixed(2) }}
                  </p>
                </div>
                
                <div v-if="cartItems.length > 3" class="text-center">
                  <p class="text-sm text-gray-500">+{{ cartItems.length - 3 }} more items</p>
              </div>
            </div>

              <!-- Totals -->
              <div class="space-y-2 border-t pt-4">
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
                <div class="flex justify-between text-lg font-bold border-t pt-2">
                <span class="text-gray-900">Total</span>
                <span class="text-gray-900">₱{{ total.toFixed(2) }}</span>
              </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

