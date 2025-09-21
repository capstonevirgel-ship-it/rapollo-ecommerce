<script setup lang="ts">
import { computed, ref, onMounted, nextTick } from 'vue'
import { storeToRefs } from 'pinia'
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { usePurchaseStore, type PaymentResponse } from '~/stores/purchase'
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

// Form state
const currentStep = ref(1)
const isLoading = ref(false)

// Purchase data from URL
const route = useRoute()

// Function to get purchase ID
const getPurchaseId = () => {
  const id = route.params.id
  console.log('Getting purchase ID - route.params.id:', id)
  console.log('Route params:', route.params)
  return id as string
}

// Form data
const formData = ref({
  // Billing Information
  billing: {
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zipCode: '',
    country: 'Philippines'
  },
  // Payment Method Selection
  paymentMethod: 'cod' // cod, card, gcash, paymaya
})

// Computed properties
const cartItems = computed(() => {
  return cart.value.map(item => ({
    id: item.id,
    variant: item.variant || {},
    product: item.variant?.product || {},
    quantity: item.quantity,
            image: getImageUrl(item.variant?.images?.[0]?.url || item.variant?.product?.images?.[0]?.url || '', 'product'),
    price: item.variant?.price || 0
  }))
})

const subtotal = computed(() => 
  cartItems.value.reduce((sum, item) => sum + (item.price * item.quantity), 0)
)

const shipping = computed(() => 5.99)
const tax = computed(() => subtotal.value * 0.08)
const total = computed(() => subtotal.value + shipping.value + tax.value)

const steps = [
  { id: 1, title: 'Billing Details', description: 'Enter your information' },
  { id: 2, title: 'Payment Method', description: 'Select payment method' },
  { id: 3, title: 'Complete', description: 'Finish order' }
]

// Methods
const validatePurchaseId = () => {
  const currentPurchaseId = getPurchaseId()
  const parsedPurchaseId = parseInt(currentPurchaseId)
  
  console.log('Validating purchase ID:', {
    currentPurchaseId,
    parsedPurchaseId,
    type: typeof currentPurchaseId
  })
  
  if (!currentPurchaseId || isNaN(parsedPurchaseId) || parsedPurchaseId <= 0) {
      console.error('Invalid purchase ID:', {
      original: currentPurchaseId,
        parsed: parsedPurchaseId,
      type: typeof currentPurchaseId
      })
      throw new Error('Invalid purchase ID. Please try again.')
    }

  return parsedPurchaseId
}

const nextStep = async () => {
  if (currentStep.value === 1) {
    // Validate billing details
    if (!formData.value.billing.firstName || !formData.value.billing.lastName || 
        !formData.value.billing.email || !formData.value.billing.phone ||
        !formData.value.billing.address || !formData.value.billing.city ||
        !formData.value.billing.state || !formData.value.billing.zipCode) {
      alert('Please fill in all billing details')
      return
    }
    currentStep.value = 2
  } else if (currentStep.value === 2) {
    await processPayment()
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const processPayment = async () => {
  isLoading.value = true
  try {
    // Validate purchase ID
    const purchaseId = validatePurchaseId()
    
    // Create payment using the existing simple payment method
    const paymentResult = await purchaseStore.createPayment(
      purchaseId,
      total.value,
      formData.value.paymentMethod
    )

    console.log('Payment result:', paymentResult)

    if (paymentResult.payment_status === 'paid' || paymentResult.payment_status === 'pending') {
      currentStep.value = 3
      // Cart is cleared by the backend after successful payment
      await cartStore.index()
    } else {
      alert(`Payment failed: ${paymentResult.payment_status}`)
    }

  } catch (error: any) {
    console.error('Payment processing failed:', error)
    alert(`Payment failed: ${error.message}`)
  } finally {
    isLoading.value = false
  }
}

const completeCheckout = () => {
  navigateTo('/checkout/success')
}

// Load cart data on mount
onMounted(async () => {
  // Wait for route to be fully loaded
  await nextTick()
  
  // Debug logging
  console.log('Checkout - Route params:', route.params)
  console.log('Checkout - Route params.id:', route.params.id)
  console.log('Checkout - Route full path:', route.fullPath)
  
  const currentPurchaseId = getPurchaseId()
  console.log('Checkout - Purchase ID:', currentPurchaseId)
  console.log('Checkout - Purchase ID type:', typeof currentPurchaseId)
  console.log('Checkout - Parsed purchase ID:', parseInt(currentPurchaseId))
  
  if (authStore.isAuthenticated && !currentPurchaseId) {
    await cartStore.index()
  }
  
  // If no valid purchase ID, redirect back to cart
  if (!currentPurchaseId || isNaN(parseInt(currentPurchaseId))) {
    console.error('No valid purchase ID found, redirecting to cart')
    console.error('Current route params:', route.params)
    console.error('Current purchase ID:', currentPurchaseId)
    await navigateTo('/cart')
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Secure Checkout</h1>
        <p class="mt-2 text-gray-600">Complete your payment securely</p>
      </div>

      <!-- Progress Steps -->
      <div class="mb-8">
        <div class="flex items-center justify-center">
          <div class="flex items-center space-x-4">
            <div
              v-for="(step, index) in steps"
              :key="step.id"
              class="flex items-center"
            >
              <!-- Step Circle -->
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium',
                  currentStep >= step.id
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-200 text-gray-600'
                ]"
              >
                {{ step.id }}
              </div>
              
              <!-- Step Info -->
              <div class="ml-3">
                <p
                  :class="[
                    'text-sm font-medium',
                    currentStep >= step.id ? 'text-blue-600' : 'text-gray-500'
                  ]"
                >
                  {{ step.title }}
                </p>
                <p class="text-xs text-gray-500">{{ step.description }}</p>
              </div>
              
              <!-- Connector Line -->
              <div
                v-if="index < steps.length - 1"
                :class="[
                  'w-16 h-0.5 mx-4',
                  currentStep > step.id ? 'bg-blue-600' : 'bg-gray-200'
                ]"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow-sm p-6">
            
            <!-- Step 1: Billing Information -->
            <div v-if="currentStep === 1" class="space-y-6">
              <h2 class="text-xl font-semibold text-gray-900">Billing Information</h2>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    First Name *
                  </label>
                  <input
                    v-model="formData.billing.firstName"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Last Name *
                  </label>
                  <input
                    v-model="formData.billing.lastName"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Email Address *
                  </label>
                  <input
                    v-model="formData.billing.email"
                    type="email"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Phone Number *
                  </label>
                  <input
                    v-model="formData.billing.phone"
                    type="tel"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Address *
                </label>
                <input
                  v-model="formData.billing.address"
                  type="text"
                  required
                  placeholder="Street address, building, unit number"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    City *
                  </label>
                  <input
                    v-model="formData.billing.city"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    State/Province *
                  </label>
                  <input
                    v-model="formData.billing.state"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    ZIP Code *
                  </label>
                  <input
                    v-model="formData.billing.zipCode"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Country *
                </label>
                <select
                  v-model="formData.billing.country"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="Philippines">Philippines</option>
                  <option value="United States">United States</option>
                  <option value="Canada">Canada</option>
                </select>
              </div>
            </div>

            <!-- Step 3: Payment Method -->
            <div v-if="currentStep === 3" class="space-y-6">
              <h2 class="text-xl font-semibold text-gray-900">Payment Method</h2>
              
              <!-- Payment Method Selection -->
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <label class="relative">
                    <input
                      v-model="formData.paymentMethod"
                      type="radio"
                      value="cod"
                      class="sr-only"
                    />
                    <div
                      :class="[
                        'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                        formData.paymentMethod === 'cod'
                          ? 'border-green-500 bg-green-50'
                          : 'border-gray-200 hover:border-gray-300'
                      ]"
                    >
                      <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-600 rounded flex items-center justify-center mr-3">
                          <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                          </svg>
                        </div>
                        <div>
                          <p class="font-medium text-gray-900">Cash on Delivery</p>
                          <p class="text-sm text-gray-500">Pay when delivered</p>
                        </div>
                      </div>
                    </div>
                  </label>

                  <label class="relative">
                    <input
                      v-model="formData.paymentMethod"
                      type="radio"
                      value="card"
                      class="sr-only"
                    />
                    <div
                      :class="[
                        'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                        formData.paymentMethod === 'card'
                          ? 'border-blue-500 bg-blue-50'
                          : 'border-gray-200 hover:border-gray-300'
                      ]"
                    >
                      <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center mr-3">
                          <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/>
                          </svg>
                        </div>
                        <div>
                          <p class="font-medium text-gray-900">Credit/Debit Card</p>
                          <p class="text-sm text-gray-500">Coming Soon</p>
                        </div>
                      </div>
                    </div>
                  </label>

                  <label class="relative">
                    <input
                      v-model="formData.paymentMethod"
                      type="radio"
                      value="gcash"
                      class="sr-only"
                    />
                    <div
                      :class="[
                        'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                        formData.paymentMethod === 'gcash'
                          ? 'border-blue-500 bg-blue-50'
                          : 'border-gray-200 hover:border-gray-300'
                      ]"
                    >
                      <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-500 rounded flex items-center justify-center mr-3">
                          <span class="text-white font-bold text-sm">G</span>
                        </div>
                        <div>
                          <p class="font-medium text-gray-900">GCash</p>
                          <p class="text-sm text-gray-500">Mobile payment</p>
                        </div>
                      </div>
                    </div>
                  </label>

                  <label class="relative">
                    <input
                      v-model="formData.paymentMethod"
                      type="radio"
                      value="paymaya"
                      class="sr-only"
                    />
                    <div
                      :class="[
                        'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                        formData.paymentMethod === 'paymaya'
                          ? 'border-blue-500 bg-blue-50'
                          : 'border-gray-200 hover:border-gray-300'
                      ]"
                    >
                      <div class="flex items-center">
                        <div class="w-8 h-8 bg-purple-600 rounded flex items-center justify-center mr-3">
                          <span class="text-white font-bold text-sm">P</span>
                        </div>
                        <div>
                          <p class="font-medium text-gray-900">PayMaya</p>
                          <p class="text-sm text-gray-500">Mobile payment</p>
                        </div>
                      </div>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Card Payment Form (Simple) -->
              <div v-if="formData.paymentMethod === 'card'" class="space-y-4 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900">Card Information</h3>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-yellow-800">
                      Card payments are temporarily unavailable. Please select Cash on Delivery or contact support.
                    </p>
                  </div>
                </div>
              </div>

              <!-- E-wallet Information -->
              <div v-if="formData.paymentMethod === 'gcash' || formData.paymentMethod === 'paymaya'" class="space-y-4 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900">{{ formData.paymentMethod === 'gcash' ? 'GCash' : 'PayMaya' }} Information</h3>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-blue-800">
                      You will be redirected to {{ formData.paymentMethod === 'gcash' ? 'GCash' : 'PayMaya' }} to complete your payment securely.
                    </p>
              </div>
                </div>
              </div>
            </div>

            <!-- Step 4: Success -->
            <div v-if="currentStep === 4" class="text-center py-12">
              <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <h2 class="text-xl font-semibold text-gray-900 mb-2">Payment Successful!</h2>
              <p class="text-gray-600 mb-6">Your order has been processed and you will receive a confirmation email shortly.</p>
              <button
                @click="completeCheckout"
                class="px-8 py-3 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
              >
                Continue to Success Page
              </button>
            </div>

            <!-- Navigation Buttons -->
            <div v-if="currentStep < 4" class="flex justify-between pt-6 border-t">
              <button
                v-if="currentStep > 1"
                @click="prevStep"
                class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                Previous
              </button>
              <div v-else></div>

              <button
                @click="nextStep"
                :disabled="isLoading"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isLoading">Processing...</span>
                <span v-else-if="currentStep === 3">Complete Payment</span>
                <span v-else>Next</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Order Summary Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
            
            <!-- Cart Items -->
            <div class="space-y-4 mb-6">
              <div
                v-for="item in cartItems"
                :key="item.id"
                class="flex items-center space-x-3"
              >
                <img
                  :src="item.image"
                  :alt="item.product.name"
                  class="w-16 h-16 object-cover rounded"
                />
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-gray-900 truncate">
                    {{ item.product.name }}
                  </h4>
                  <p class="text-sm text-gray-500">
                    Qty: {{ item.quantity }}
                  </p>
                  <p class="text-sm font-medium text-gray-900">
                    ₱{{ (item.price * item.quantity).toFixed(2) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Order Totals -->
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
              <div class="flex justify-between text-lg font-semibold border-t pt-2">
                <span class="text-gray-900">Total</span>
                <span class="text-gray-900">₱{{ total.toFixed(2) }}</span>
              </div>
            </div>

            <!-- Security Badge -->
            <div class="mt-6 text-center">
              <div class="flex items-center justify-center space-x-2 text-sm text-gray-500">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                <span>Secure checkout</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom styles for better UX */
input[type="radio"]:checked + div {
  border-color: rgb(59 130 246);
  background-color: rgb(239 246 255);
}

/* Smooth transitions */
.transition-colors {
  transition: all 0.2s ease-in-out;
}

/* Payment Elements styling */
#card-number-element,
#card-expiry-element,
#card-cvc-element {
  background: white;
}

#card-number-element:focus,
#card-expiry-element:focus,
#card-cvc-element:focus {
  outline: 2px solid rgb(59 130 246);
  outline-offset: 2px;
}
</style>
