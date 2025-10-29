<script setup lang="ts">
// Define page meta
definePageMeta({
  layout: 'default'
})

// Set page title
useHead({
  title: 'Payment Failed | RAPOLLO',
  meta: [
    { name: 'description', content: 'Your payment could not be processed. Please try again at Rapollo E-commerce.' }
  ]
})

const route = useRoute()
const authStore = useAuthStore()

// Check if user came from PayMongo error page
const fromPayMongo = computed(() => {
  return route.query.from === 'paymongo' || route.query.status === 'expired'
})

// Get failure reason from URL params
const failureReason = computed(() => {
  return route.query.reason as string || 'Payment checkout has expired'
})

const goToCheckout = () => {
  navigateTo('/checkout')
}

const goToShop = () => {
  navigateTo('/shop')
}

const goToEvents = () => {
  navigateTo('/events')
}

const goToMyTickets = () => {
  navigateTo('/my-tickets')
}

// Auto-redirect if coming from PayMongo
onMounted(() => {
  if (fromPayMongo.value) {
    // Show a brief message that we detected the PayMongo error
    console.log('Detected PayMongo payment failure - redirecting to our failed page')
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow-sm p-8 text-center">
        <!-- Error Icon -->
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
          <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>

        <!-- Error Message -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Failed</h1>
          <p class="text-gray-600 text-lg">
            We're sorry, but there was an issue processing your payment. Your transaction was not completed.
          </p>
          <p class="text-gray-500 mt-2">
            {{ failureReason }}
          </p>
          <p class="text-gray-500 mt-2">
            Please try again or contact our support team if you need assistance.
          </p>
        </div>

        <!-- PayMongo Detection Message -->
        <div v-if="fromPayMongo" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex items-center">
            <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-blue-800 text-sm">
              We detected you came from PayMongo's payment page. Your payment has been processed and cancelled tickets have been created.
            </p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            @click="goToEvents"
            class="px-8 py-3 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 font-medium"
          >
            View Events
          </button>
          <button
            @click="goToMyTickets"
            class="px-8 py-3 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 font-medium"
          >
            View My Tickets
          </button>
          <button
            @click="goToShop"
            class="px-8 py-3 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 font-medium"
          >
            Continue Shopping
          </button>
        </div>

        <!-- Help Section -->
        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
          <h3 class="font-medium text-gray-900 mb-2">Need Help?</h3>
          <p class="text-sm text-gray-600 mb-2">
            If you're experiencing issues with your payment, here are some common solutions:
          </p>
          <ul class="text-sm text-gray-600 space-y-1 text-left">
            <li>• Check that your payment information is correct</li>
            <li>• Ensure you have sufficient funds</li>
            <li>• Try a different payment method</li>
            <li>• Contact your bank if the issue persists</li>
          </ul>
          <p class="text-sm text-gray-600 mt-3">
            For additional support, please contact us at 
            <a href="mailto:support@rapollo.com" class="text-zinc-900 hover:underline font-medium">support@rapollo.com</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
