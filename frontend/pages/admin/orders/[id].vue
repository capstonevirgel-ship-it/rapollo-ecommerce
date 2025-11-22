<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useOrderStore } from '~/stores/order'
import { useAlert } from '~/composables/useAlert'
import PayMongoBranding from '@/components/PayMongoBranding.vue'
import Dialog from '@/components/Dialog.vue'

definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Order Details - Admin | RAPOLLO',
  meta: [
    { name: 'description', content: 'View and manage order details in your Rapollo E-commerce store.' }
  ]
})

const route = useRoute()
const orderStore = useOrderStore()
const { success, error } = useAlert()
const order = ref<any>(null)
const loading = ref(false)

const showCancelDialog = ref(false)
const isCancelling = ref(false)

const orderId = computed(() => route.params.id as string)

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getPaymentMethod = (payment: any) => {
  if (!payment?.metadata) return payment?.payment_method || 'N/A'
  
  try {
    const metadata = JSON.parse(payment.metadata)
    const source = metadata?.data?.attributes?.data?.attributes?.source
    if (source?.type === 'gcash') {
      return 'GCash'
    }
    return payment?.payment_method || 'N/A'
  } catch (error) {
    return payment?.payment_method || 'N/A'
  }
}

const goBack = () => {
  navigateTo('/admin/orders')
}

const fetchOrder = async () => {
  loading.value = true
  console.log('Fetching order details for ID:', orderId.value)
  
  try {
    const response = await $fetch(`/api/product-purchases/admin/${orderId.value}`) as { data: any }
    console.log('Order details response:', response)
    
    if (response && response.data) {
      order.value = response.data
    } else if (response) {
      // Fallback: if response is the data directly
      order.value = response
    }
  } catch (err) {
    console.error('Failed to fetch order:', err)
  } finally {
    loading.value = false
  }
}

const openCancelDialog = () => {
  showCancelDialog.value = true
}

const closeCancelDialog = () => {
  showCancelDialog.value = false
}

const cancelOrder = async () => {
  if (!order.value) return

  isCancelling.value = true

  try {
    await orderStore.cancelOrder(order.value.id)
    success('Order Cancelled', `Order #${order.value.id} has been cancelled successfully.`)
    // Refresh order
    await fetchOrder()
  } catch (err: any) {
    console.error('Error cancelling order:', err)
    const errorMessage = err?.data?.message || err?.data?.error || err?.message || 'Failed to cancel order. Please try again.'
    error('Cancellation Failed', errorMessage)
  } finally {
    isCancelling.value = false
    closeCancelDialog()
  }
}

onMounted(() => {
  fetchOrder()
})
</script>

<template>
  <div class="space-y-8 sm:space-y-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Order Details</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Order #{{ orderId }}</p>
      </div>
      <div class="flex items-center space-x-3">
        <button
          v-if="order && (order.status === 'pending' || order.status === 'processing')"
          @click="openCancelDialog"
          class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Cancel Order
        </button>
        <button 
          @click="goBack" 
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to Orders
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-zinc-600"></div>
      <p class="ml-4 text-gray-600">Loading order details...</p>
    </div>

    <!-- Order Not Found -->
    <div v-else-if="!order" class="bg-red-50 border border-red-200 rounded-md p-4">
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Order Not Found</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>The order you're looking for doesn't exist or you don't have permission to view it.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Details -->
    <div v-else class="space-y-8 sm:space-y-10">
      <!-- Order Summary -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div>
            <p class="text-sm text-gray-500">Order ID</p>
            <p class="font-medium">#{{ order.id }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Status</p>
            <span :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
              order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
              order.status === 'processing' ? 'bg-blue-100 text-blue-800' :
              order.status === 'shipped' ? 'bg-purple-100 text-purple-800' :
              order.status === 'delivered' ? 'bg-green-100 text-green-800' :
              'bg-gray-100 text-gray-800'
            ]">
              {{ order.status?.charAt(0).toUpperCase() + order.status?.slice(1) }}
            </span>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Amount</p>
            <p class="font-medium text-lg">{{ formatCurrency(parseFloat(order.total)) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Order Date</p>
            <p class="font-medium">{{ formatDate(order.created_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Customer Information -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-sm text-gray-500">Full Name</p>
            <p class="font-medium">{{ order.user?.profile?.full_name || order.user?.user_name || 'N/A' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Email</p>
            <p class="font-medium">{{ order.user?.email || 'N/A' }}</p>
          </div>
          <div v-if="order.user?.profile?.phone">
            <p class="text-sm text-gray-500">Phone</p>
            <p class="font-medium">{{ order.user.profile.phone }}</p>
          </div>
          <div v-if="order.user?.profile?.address">
            <p class="text-sm text-gray-500">Address</p>
            <p class="font-medium">{{ order.user.profile.address }}</p>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h2>
        <div v-if="order.items && order.items.length > 0" class="space-y-6">
          <div v-for="item in order.items" :key="item.id" class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-start space-x-4">
              <!-- Product Image -->
              <div class="flex-shrink-0">
                <img 
                  v-if="item.variant?.images && item.variant.images.length > 0"
                  :src="`/storage/${(item.variant.images.find((img: any) => img.is_primary === true) || item.variant.images[0])?.url}`"
                  :alt="item.variant?.product?.name || 'Product'"
                  class="w-20 h-20 object-cover rounded-lg"
                />
                <div v-else class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              
              <!-- Product Details -->
              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-medium text-gray-900">{{ item.variant?.product?.name || 'Unknown Product' }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                  {{ item.variant?.color?.name || 'N/A' }} - {{ item.variant?.size?.name || 'N/A' }}
                </p>
                <p class="text-sm text-gray-500">
                  Category: {{ item.variant?.product?.subcategory?.category?.name || 'N/A' }} > {{ item.variant?.product?.subcategory?.name || 'N/A' }}
                </p>
                <div class="flex items-center justify-between mt-3">
                  <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">Quantity: {{ item.quantity }}</span>
                    <span class="text-sm text-gray-500">Price: {{ formatCurrency(parseFloat(item.price)) }}</span>
                  </div>
                  <div class="text-right">
                    <p class="text-lg font-semibold text-gray-900">
                      {{ formatCurrency(parseFloat(item.price) * item.quantity) }}
                    </p>
                    <p class="text-sm text-gray-500">Subtotal</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500 text-center py-4">
          No items found
        </div>
      </div>

      <!-- Payment Information -->
      <div v-if="order.payment" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-sm text-gray-500">Payment Method</p>
            <div class="flex flex-col items-start">
              <p class="font-medium">{{ getPaymentMethod(order.payment) }}</p>
              <div class="mt-1">
                <PayMongoBranding />
              </div>
            </div>
          </div>
          <div>
            <p class="text-sm text-gray-500">Transaction ID</p>
            <p class="font-medium font-mono text-sm">{{ order.payment.transaction_id || 'N/A' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Payment Status</p>
            <span :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
              order.payment.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            ]">
              {{ order.payment.status?.charAt(0).toUpperCase() + order.payment.status?.slice(1) }}
            </span>
          </div>
          <div>
            <p class="text-sm text-gray-500">Payment Date</p>
            <p class="font-medium">{{ order.payment.payment_date ? new Date(order.payment.payment_date).toLocaleDateString() : 'N/A' }}</p>
          </div>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
        <div class="space-y-2">
          <div class="flex justify-between">
            <span class="text-gray-600">Subtotal</span>
            <span class="font-medium">{{ formatCurrency(parseFloat(order.total)) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Shipping</span>
            <span class="font-medium">Free</span>
          </div>
          <div class="border-t pt-2">
            <div class="flex justify-between">
              <span class="text-lg font-semibold text-gray-900">Total</span>
              <span class="text-lg font-semibold text-gray-900">{{ formatCurrency(parseFloat(order.total)) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation Dialog -->
    <Dialog v-model="showCancelDialog" title="Cancel Order">
      <div class="flex flex-col items-center text-center">
        <!-- Warning Icon -->
        <div class="mb-4 flex items-center justify-center w-20 h-20 rounded-full bg-yellow-100">
          <Icon name="mdi:alert" class="text-[3rem] text-yellow-600" />
        </div>

        <!-- Confirmation Message -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          Are you sure you want to cancel this order?
        </h3>
        <p class="text-sm text-gray-600 mb-6">
          This action cannot be undone. The order will be cancelled and the customer may be subject to our cancellation policy.
        </p>

        <!-- Action Buttons -->
        <div class="flex gap-3 w-full">
          <button
            @click="closeCancelDialog"
            :disabled="isCancelling"
            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Cancel
          </button>
          <button
            @click="cancelOrder"
            :disabled="isCancelling"
            class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
          >
            <span v-if="isCancelling">Cancelling...</span>
            <span v-else>Cancel Order</span>
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>

