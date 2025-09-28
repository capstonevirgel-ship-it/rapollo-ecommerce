<template>
  <div class="space-y-6">
    <!-- Order Summary -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
      
      <div class="space-y-4">
        <div v-for="item in cartItems" :key="item.id" class="flex items-center space-x-4 py-3 border-b border-gray-200 last:border-b-0">
          <img 
            :src="item.image" 
            :alt="item.product.name || 'Product'"
            class="w-16 h-16 object-cover rounded-md"
          />
          <div class="flex-1 min-w-0">
            <h4 class="text-sm font-medium text-gray-900 truncate">
              {{ item.product.name || 'Product' }}
            </h4>
            <p class="text-sm text-gray-500">
              {{ item.variant.size?.name || '' }} {{ item.variant.color?.name || '' }}
            </p>
            <p class="text-sm text-gray-500">Quantity: {{ item.quantity }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm font-medium text-gray-900">
              ₱{{ (item.price * item.quantity).toFixed(2) }}
            </p>
            <p class="text-xs text-gray-500">₱{{ item.price.toFixed(2) }} each</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Billing Information Review -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Billing Information</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Contact Details</h4>
          <div class="space-y-1 text-sm text-gray-600">
            <p>{{ billingData.firstName }} {{ billingData.lastName }}</p>
            <p>{{ billingData.email }}</p>
            <p>{{ billingData.phone }}</p>
          </div>
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Billing Address</h4>
          <div class="space-y-1 text-sm text-gray-600">
            <p>{{ billingData.address }}</p>
            <p>{{ billingData.city }}, {{ billingData.state }} {{ billingData.zipCode }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Method Review -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Method</h3>
      
      <div class="flex items-center space-x-3">
        <div v-if="billingData.paymentMethod === 'cod'" class="flex items-center">
          <svg class="h-6 w-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
          </svg>
          <span class="text-sm font-medium text-gray-700">Cash on Delivery</span>
        </div>
        
        <div v-else-if="billingData.paymentMethod === 'card'" class="flex items-center">
          <svg class="h-6 w-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          <span class="text-sm font-medium text-gray-700">Credit/Debit Card</span>
        </div>
        
        <span class="text-xs text-gray-500">
          {{ billingData.paymentMethod === 'cod' ? 'Pay when delivered' : 'Processed securely' }}
        </span>
      </div>
    </div>

    <!-- Order Totals -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Totals</h3>
      
      <div class="space-y-3">
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

    <!-- Action Buttons -->
    <div class="flex space-x-4">
      <button
        @click="$emit('back')"
        class="flex-1 bg-gray-600 text-white py-3 px-4 rounded-md font-medium hover:bg-gray-700 transition"
      >
        Back to Billing
      </button>
      
      <button
        @click="handleConfirmOrder"
        :disabled="isProcessing"
        class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-md font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="isProcessing">Processing...</span>
        <span v-else>Confirm Order</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { getImageUrl } from '~/utils/imageHelper'

interface BillingData {
  firstName: string
  lastName: string
  email: string
  phone: string
  address: string
  city: string
  state: string
  zipCode: string
  paymentMethod: string
}

interface CartItem {
  id: number
  variant: any
  product: any
  quantity: number
  price: number
  image: string
}

const props = defineProps<{
  cartItems: CartItem[]
  billingData: BillingData
  subtotal: number
  shipping: number
  tax: number
  total: number
  isProcessing?: boolean
}>()

const emit = defineEmits<{
  back: []
  confirm: []
}>()

const handleConfirmOrder = () => {
  emit('confirm')
}
</script>
