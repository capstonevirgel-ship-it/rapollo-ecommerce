<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { getImageUrl } from '~/utils/imageHelper'

// Define page meta
definePageMeta({
  layout: 'default'
})

// Stores
const cartStore = useCartStore()
const authStore = useAuthStore()
const { cart } = storeToRefs(cartStore)

// Form state
const currentStep = ref(1)
const isLoading = ref(false)
const formData = ref({
  // Shipping Information
  shipping: {
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
  // Billing Information
  billing: {
    sameAsShipping: true,
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
  // Payment Information
  payment: {
    method: 'card', // card, cod, gcash, paymaya
    cardNumber: '',
    expiryDate: '',
    cvv: '',
    cardName: '',
    gcashNumber: '',
    paymayaNumber: ''
  },
  // Order Information
  order: {
    notes: '',
    newsletter: false
  }
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
  { id: 1, title: 'Shipping', description: 'Delivery information' },
  { id: 2, title: 'Payment', description: 'Payment method' },
  { id: 3, title: 'Review', description: 'Order summary' }
]

// Methods
const nextStep = () => {
  if (currentStep.value < 3) {
    currentStep.value++
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const goToStep = (step: number) => {
  currentStep.value = step
}

const updateBilling = () => {
  if (formData.value.billing.sameAsShipping) {
    formData.value.billing = { ...formData.value.shipping, sameAsShipping: true }
  }
}

const formatCardNumber = (value: string) => {
  return value.replace(/\s/g, '').replace(/(.{4})/g, '$1 ').trim()
}

const formatExpiryDate = (value: string) => {
  return value.replace(/\D/g, '').replace(/(.{2})/, '$1/').trim()
}

const handleCardNumberInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  formData.value.payment.cardNumber = formatCardNumber(target.value)
}

const handleExpiryInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  formData.value.payment.expiryDate = formatExpiryDate(target.value)
}

const placeOrder = async () => {
  isLoading.value = true
  try {
    // TODO: Implement order placement logic
    console.log('Placing order:', formData.value)
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 2000))
    // Redirect to success page
    await navigateTo('/checkout/success')
  } catch (error) {
    console.error('Order placement failed:', error)
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
        <p class="mt-2 text-gray-600">Complete your order</p>
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
                    ? 'bg-primary-600 text-white'
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
                    currentStep >= step.id ? 'text-primary-600' : 'text-gray-500'
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
                  currentStep > step.id ? 'bg-primary-600' : 'bg-gray-200'
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
            <!-- Step 1: Shipping Information -->
            <div v-if="currentStep === 1" class="space-y-6">
              <h2 class="text-xl font-semibold text-gray-900">Shipping Information</h2>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    First Name *
                  </label>
                  <input
                    v-model="formData.shipping.firstName"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Last Name *
                  </label>
                  <input
                    v-model="formData.shipping.lastName"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Email Address *
                  </label>
                  <input
                    v-model="formData.shipping.email"
                    type="email"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Phone Number *
                  </label>
                  <input
                    v-model="formData.shipping.phone"
                    type="tel"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Address *
                </label>
                <input
                  v-model="formData.shipping.address"
                  type="text"
                  required
                  placeholder="Street address, building, unit number"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    City *
                  </label>
                  <input
                    v-model="formData.shipping.city"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    State/Province *
                  </label>
                  <input
                    v-model="formData.shipping.state"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    ZIP Code *
                  </label>
                  <input
                    v-model="formData.shipping.zipCode"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Country *
                </label>
                <select
                  v-model="formData.shipping.country"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option value="Philippines">Philippines</option>
                  <option value="United States">United States</option>
                  <option value="Canada">Canada</option>
                </select>
              </div>

              <!-- Billing Address Toggle -->
              <div class="border-t pt-6">
                <div class="flex items-center">
                  <input
                    id="sameAsShipping"
                    v-model="formData.billing.sameAsShipping"
                    type="checkbox"
                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    @change="updateBilling"
                  />
                  <label for="sameAsShipping" class="ml-2 block text-sm text-gray-900">
                    Billing address is the same as shipping address
                  </label>
                </div>
              </div>

              <!-- Billing Information (if different) -->
              <div v-if="!formData.billing.sameAsShipping" class="space-y-4 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900">Billing Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      First Name *
                    </label>
                    <input
                      v-model="formData.billing.firstName"
                      type="text"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
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
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
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
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
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
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
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
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
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
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
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
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
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
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 2: Payment Information -->
            <div v-if="currentStep === 2" class="space-y-6">
              <h2 class="text-xl font-semibold text-gray-900">Payment Method</h2>
              
              <!-- Payment Method Selection -->
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <label class="relative">
                    <input
                      v-model="formData.payment.method"
                      type="radio"
                      value="card"
                      class="sr-only"
                    />
                    <div
                      :class="[
                        'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                        formData.payment.method === 'card'
                          ? 'border-primary-500 bg-primary-50'
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
                          <p class="text-sm text-gray-500">Visa, Mastercard, American Express</p>
                        </div>
                      </div>
                    </div>
                  </label>

                  <label class="relative">
                    <input
                      v-model="formData.payment.method"
                      type="radio"
                      value="cod"
                      class="sr-only"
                    />
                    <div
                      :class="[
                        'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                        formData.payment.method === 'cod'
                          ? 'border-primary-500 bg-primary-50'
                          : 'border-gray-200 hover:border-gray-300'
                      ]"
                    >
                      <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-600 rounded flex items-center justify-center mr-3">
                          <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                          </svg>
                        </div>
                        <div>
                          <p class="font-medium text-gray-900">Cash on Delivery</p>
                          <p class="text-sm text-gray-500">Pay when you receive</p>
                        </div>
                      </div>
                    </div>
                  </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <label class="relative">
                    <input
                      v-model="formData.payment.method"
                      type="radio"
                      value="gcash"
                      class="sr-only"
                    />
                    <div
                      :class="[
                        'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                        formData.payment.method === 'gcash'
                          ? 'border-primary-500 bg-primary-50'
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
                      v-model="formData.payment.method"
                      type="radio"
                      value="paymaya"
                      class="sr-only"
                    />
                    <div
                      :class="[
                        'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                        formData.payment.method === 'paymaya'
                          ? 'border-primary-500 bg-primary-50'
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

              <!-- Card Payment Form -->
              <div v-if="formData.payment.method === 'card'" class="space-y-4 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900">Card Information</h3>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Card Number *
                  </label>
                  <input
                    v-model="formData.payment.cardNumber"
                    type="text"
                    required
                    placeholder="1234 5678 9012 3456"
                    maxlength="19"
                    @input="handleCardNumberInput"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Expiry Date *
                    </label>
                    <input
                      v-model="formData.payment.expiryDate"
                      type="text"
                      required
                      placeholder="MM/YY"
                      maxlength="5"
                      @input="handleExpiryInput"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      CVV *
                    </label>
                    <input
                      v-model="formData.payment.cvv"
                      type="text"
                      required
                      placeholder="123"
                      maxlength="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Name on Card *
                  </label>
                  <input
                    v-model="formData.payment.cardName"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- GCash Form -->
              <div v-if="formData.payment.method === 'gcash'" class="space-y-4 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900">GCash Information</h3>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    GCash Number *
                  </label>
                  <input
                    v-model="formData.payment.gcashNumber"
                    type="tel"
                    required
                    placeholder="09XX XXX XXXX"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- PayMaya Form -->
              <div v-if="formData.payment.method === 'paymaya'" class="space-y-4 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900">PayMaya Information</h3>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    PayMaya Number *
                  </label>
                  <input
                    v-model="formData.payment.paymayaNumber"
                    type="tel"
                    required
                    placeholder="09XX XXX XXXX"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
              </div>
            </div>

            <!-- Step 3: Review Order -->
            <div v-if="currentStep === 3" class="space-y-6">
              <h2 class="text-xl font-semibold text-gray-900">Review Your Order</h2>
              
              <!-- Shipping Information Review -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-2">Shipping Address</h3>
                <p class="text-sm text-gray-600">
                  {{ formData.shipping.firstName }} {{ formData.shipping.lastName }}<br>
                  {{ formData.shipping.address }}<br>
                  {{ formData.shipping.city }}, {{ formData.shipping.state }} {{ formData.shipping.zipCode }}<br>
                  {{ formData.shipping.country }}
                </p>
                <p class="text-sm text-gray-600 mt-1">
                  {{ formData.shipping.email }} • {{ formData.shipping.phone }}
                </p>
              </div>

              <!-- Payment Method Review -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-2">Payment Method</h3>
                <p class="text-sm text-gray-600">
                  <span v-if="formData.payment.method === 'card'">
                    Credit/Debit Card ending in {{ formData.payment.cardNumber.slice(-4) }}
                  </span>
                  <span v-else-if="formData.payment.method === 'cod'">
                    Cash on Delivery
                  </span>
                  <span v-else-if="formData.payment.method === 'gcash'">
                    GCash ({{ formData.payment.gcashNumber }})
                  </span>
                  <span v-else-if="formData.payment.method === 'paymaya'">
                    PayMaya ({{ formData.payment.paymayaNumber }})
                  </span>
                </p>
              </div>

              <!-- Order Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Order Notes (Optional)
                </label>
                <textarea
                  v-model="formData.order.notes"
                  rows="3"
                  placeholder="Special instructions for your order..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>

              <!-- Newsletter Signup -->
              <div class="flex items-center">
                <input
                  id="newsletter"
                  v-model="formData.order.newsletter"
                  type="checkbox"
                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                />
                <label for="newsletter" class="ml-2 block text-sm text-gray-900">
                  Subscribe to our newsletter for updates and special offers
                </label>
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between pt-6 border-t">
              <button
                v-if="currentStep > 1"
                @click="prevStep"
                class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                Previous
              </button>
              <div v-else></div>

              <button
                v-if="currentStep < 3"
                @click="nextStep"
                class="px-6 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                Next
              </button>
              <button
                v-else
                @click="placeOrder"
                :disabled="isLoading"
                class="px-8 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isLoading">Processing...</span>
                <span v-else>Place Order</span>
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
                  :src="getImageUrl(item.image)"
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
</style>
