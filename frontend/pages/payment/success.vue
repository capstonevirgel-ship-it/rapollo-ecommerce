<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <!-- Success Icon -->
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
          <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        
        <!-- Success Message -->
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment Successful!</h1>
        <p class="text-lg text-gray-600 mb-8">
          Your order has been confirmed and is being processed.
        </p>
        
        <!-- Order Details -->
        <div v-if="orderData" class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Details</h3>
          
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Order ID:</span>
              <span class="font-medium text-gray-900">#{{ orderData.orderId || 'RAP-' + Date.now() }}</span>
            </div>
            
            <div class="flex justify-between">
              <span class="text-gray-600">Payment Method:</span>
              <span class="font-medium text-gray-900">
                {{ orderData.paymentMethod === 'cod' ? 'Cash on Delivery' : 'Credit/Debit Card' }}
              </span>
            </div>
            
            <div class="flex justify-between">
              <span class="text-gray-600">Total Amount:</span>
              <span class="font-medium text-gray-900">₱{{ orderData.total?.toFixed(2) || '0.00' }}</span>
            </div>
            
            <div class="flex justify-between">
              <span class="text-gray-600">Estimated Delivery:</span>
              <span class="font-medium text-gray-900">3-5 business days</span>
            </div>
          </div>
        </div>
        
        <!-- Customer Information -->
        <div v-if="orderData?.billingData" class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Delivery Information</h3>
          
          <div class="text-sm text-gray-600 space-y-1">
            <p class="font-medium text-gray-900">
              {{ orderData.billingData.firstName }} {{ orderData.billingData.lastName }}
            </p>
            <p>{{ orderData.billingData.email }}</p>
            <p>{{ orderData.billingData.phone }}</p>
            <p>{{ orderData.billingData.address }}</p>
            <p>{{ orderData.billingData.city }}, {{ orderData.billingData.state }} {{ orderData.billingData.zipCode }}</p>
          </div>
        </div>
        
        <!-- Next Steps -->
        <div class="bg-blue-50 rounded-lg p-6 mb-8">
          <h3 class="text-lg font-semibold text-blue-900 mb-3">What's Next?</h3>
          <ul class="text-sm text-blue-800 space-y-2">
            <li class="flex items-start">
              <svg class="h-4 w-4 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
              </svg>
              You'll receive an email confirmation shortly
            </li>
            <li class="flex items-start">
              <svg class="h-4 w-4 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
              </svg>
              We'll prepare your order for shipping
            </li>
            <li class="flex items-start">
              <svg class="h-4 w-4 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
              </svg>
              Tracking information will be sent to your email
            </li>
            <li v-if="orderData?.paymentMethod === 'cod'" class="flex items-start">
              <svg class="h-4 w-4 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
              </svg>
              Payment will be collected upon delivery
            </li>
          </ul>
        </div>
        
        <!-- Action Buttons -->
        <div class="space-y-4">
          <button
            @click="goToHome"
            class="w-full bg-blue-600 text-white py-3 px-4 rounded-md font-medium hover:bg-blue-700 transition"
          >
            Continue Shopping
          </button>
          
          <button
            @click="viewOrders"
            class="w-full bg-gray-600 text-white py-3 px-4 rounded-md font-medium hover:bg-gray-700 transition"
          >
            View My Orders
          </button>
          
          <button
            @click="downloadReceipt"
            class="w-full bg-green-600 text-white py-3 px-4 rounded-md font-medium hover:bg-green-700 transition"
          >
            Download Receipt
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

// Define page meta
definePageMeta({
  layout: 'default'
})

const router = useRouter()
const orderData = ref<any>(null)

// Get order data from session storage or route params
onMounted(() => {
  // Try to get order data from session storage first
  const storedOrderData = sessionStorage.getItem('orderData')
  if (storedOrderData) {
    orderData.value = JSON.parse(storedOrderData)
    // Clear the stored data after retrieving it
    sessionStorage.removeItem('orderData')
  }
  
  // If no stored data, try to get from route query params
  const route = useRoute()
  if (route.query.orderId && !orderData.value) {
    orderData.value = {
      orderId: route.query.orderId,
      total: route.query.total,
      paymentMethod: route.query.paymentMethod
    }
  }
})

const goToHome = () => {
  router.push('/')
}

const viewOrders = () => {
  router.push('/my-orders')
}

const downloadReceipt = () => {
  // Generate a simple receipt
  const receiptContent = `
RAPOLLO E-COMMERCE
Receipt

Order ID: ${orderData.value?.orderId || 'N/A'}
Date: ${new Date().toLocaleDateString()}

Customer: ${orderData.value?.billingData?.firstName} ${orderData.value?.billingData?.lastName}
Email: ${orderData.value?.billingData?.email}
Phone: ${orderData.value?.billingData?.phone}

Billing Address:
${orderData.value?.billingData?.address}
${orderData.value?.billingData?.city}, ${orderData.value?.billingData?.state} ${orderData.value?.billingData?.zipCode}

Payment Method: ${orderData.value?.paymentMethod === 'cod' ? 'Cash on Delivery' : 'Credit/Debit Card'}

Total Amount: ₱${orderData.value?.total?.toFixed(2) || '0.00'}

Thank you for your purchase!
  `.trim()
  
  // Create and download the receipt
  const blob = new Blob([receiptContent], { type: 'text/plain' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `receipt-${orderData.value?.orderId || 'order'}.txt`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}
</script>
