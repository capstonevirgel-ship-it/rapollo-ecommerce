<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <!-- Error Icon -->
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 mb-6">
          <svg class="h-12 w-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        
        <!-- Error Message -->
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment Failed</h1>
        <p class="text-lg text-gray-600 mb-8">
          {{ errorMessage || 'We encountered an issue processing your payment. Please try again.' }}
        </p>
        
        <!-- Error Details -->
        <div v-if="errorData" class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Error Details</h3>
          
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Error Code:</span>
              <span class="font-medium text-red-600">{{ errorData.code || 'UNKNOWN_ERROR' }}</span>
            </div>
            
            <div class="flex justify-between">
              <span class="text-gray-600">Attempted Amount:</span>
              <span class="font-medium text-gray-900">₱{{ errorData.amount?.toFixed(2) || '0.00' }}</span>
            </div>
            
            <div class="flex justify-between">
              <span class="text-gray-600">Payment Method:</span>
              <span class="font-medium text-gray-900">
                {{ errorData.paymentMethod === 'cod' ? 'Cash on Delivery' : 'Credit/Debit Card' }}
              </span>
            </div>
            
            <div class="flex justify-between">
              <span class="text-gray-600">Time:</span>
              <span class="font-medium text-gray-900">{{ new Date().toLocaleString() }}</span>
            </div>
          </div>
        </div>
        
        <!-- Common Solutions -->
        <div class="bg-yellow-50 rounded-lg p-6 mb-8">
          <h3 class="text-lg font-semibold text-yellow-900 mb-3">What you can do:</h3>
          <ul class="text-sm text-yellow-800 space-y-2">
            <li class="flex items-start">
              <svg class="h-4 w-4 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
              </svg>
              Check your payment method details
            </li>
            <li class="flex items-start">
              <svg class="h-4 w-4 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
              </svg>
              Ensure sufficient funds are available
            </li>
            <li class="flex items-start">
              <svg class="h-4 w-4 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
              </svg>
              Try a different payment method
            </li>
            <li class="flex items-start">
              <svg class="h-4 w-4 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
              </svg>
              Contact your bank if the issue persists
            </li>
          </ul>
        </div>
        
        <!-- Action Buttons -->
        <div class="space-y-4">
          <button
            @click="retryPayment"
            class="w-full bg-blue-600 text-white py-3 px-4 rounded-md font-medium hover:bg-blue-700 transition"
          >
            Try Again
          </button>
          
          <button
            @click="goToCart"
            class="w-full bg-gray-600 text-white py-3 px-4 rounded-md font-medium hover:bg-gray-700 transition"
          >
            Back to Cart
          </button>
          
          <button
            @click="contactSupport"
            class="w-full bg-green-600 text-white py-3 px-4 rounded-md font-medium hover:bg-green-700 transition"
          >
            Contact Support
          </button>
        </div>
        
        <!-- Support Information -->
        <div class="mt-8 pt-6 border-t border-gray-200">
          <p class="text-sm text-gray-500">
            Need help? Contact our support team:
          </p>
          <div class="mt-2 space-y-1 text-sm text-gray-600">
            <p>📧 support@rapollo.com</p>
            <p>📞 +1 (555) 123-4567</p>
            <p>🕒 Mon-Fri 9AM-6PM EST</p>
          </div>
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
const errorMessage = ref('')
const errorData = ref<any>(null)

// Get error data from session storage or route params
onMounted(() => {
  // Try to get error data from session storage first
  const storedErrorData = sessionStorage.getItem('paymentError')
  if (storedErrorData) {
    const parsed = JSON.parse(storedErrorData)
    errorData.value = parsed
    errorMessage.value = parsed.message || 'Payment processing failed'
    // Clear the stored data after retrieving it
    sessionStorage.removeItem('paymentError')
  }
  
  // If no stored data, try to get from route query params
  const route = useRoute()
  if (route.query.error && !errorData.value) {
    errorMessage.value = route.query.error as string
    errorData.value = {
      code: route.query.code as string,
      amount: route.query.amount ? parseFloat(route.query.amount as string) : null,
      paymentMethod: route.query.paymentMethod as string
    }
  }
})

const retryPayment = () => {
  // Go back to checkout with the same data
  const checkoutData = sessionStorage.getItem('checkoutData')
  if (checkoutData) {
    router.push({
      path: '/checkout/retry',
      query: { data: checkoutData }
    })
  } else {
    router.push('/cart')
  }
}

const goToCart = () => {
  router.push('/cart')
}

const contactSupport = () => {
  // Open email client or redirect to support page
  const subject = encodeURIComponent(`Payment Failed - Order ${errorData.value?.orderId || 'Unknown'}`)
  const body = encodeURIComponent(`
Error Details:
- Error Code: ${errorData.value?.code || 'Unknown'}
- Amount: ₱${errorData.value?.amount?.toFixed(2) || '0.00'}
- Payment Method: ${errorData.value?.paymentMethod || 'Unknown'}
- Time: ${new Date().toLocaleString()}

Please describe the issue you encountered:
  `.trim())
  
  window.open(`mailto:support@rapollo.com?subject=${subject}&body=${body}`)
}
</script>
