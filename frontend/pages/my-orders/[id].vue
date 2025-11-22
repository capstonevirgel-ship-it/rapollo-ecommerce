<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { usePurchaseStore } from '~/stores/purchase'
import { useTaxStore } from '~/stores/tax'
import { useOrderStore } from '~/stores/order'
import { useAlert } from '~/composables/useAlert'
import { getImageUrl, getPrimaryVariantImage } from '~/utils/imageHelper'
import PayMongoBranding from '@/components/PayMongoBranding.vue'
import Dialog from '@/components/Dialog.vue'

definePageMeta({
  layout: 'default',
  middleware: 'auth'
})

// Set page title
useHead({
  title: 'Order Details | RAPOLLO',
  meta: [
    { name: 'description', content: 'View your order details at Rapollo E-commerce.' }
  ]
})

const route = useRoute()
const purchaseStore = usePurchaseStore()
const taxStore = useTaxStore()
const orderStore = useOrderStore()
const { success, error: showError } = useAlert()
const order = ref<any>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const showCancelDialog = ref(false)
const isCancelling = ref(false)

const orderId = computed(() => route.params.id as string)

const formatPrice = (price: any) => {
  if (price === null || price === undefined) return '0.00'
  const numPrice = typeof price === 'string' ? parseFloat(price) : Number(price)
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2)
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
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

const formatShippingAddress = (shippingAddress: any) => {
  if (!shippingAddress) return 'N/A'
  
  if (typeof shippingAddress === 'string') {
    try {
      shippingAddress = JSON.parse(shippingAddress)
    } catch {
      return shippingAddress
    }
  }
  
  const parts = []
  if (shippingAddress.street) parts.push(shippingAddress.street)
  if (shippingAddress.barangay) parts.push(shippingAddress.barangay)
  if (shippingAddress.city) parts.push(shippingAddress.city)
  if (shippingAddress.province) parts.push(shippingAddress.province)
  if (shippingAddress.zipcode) parts.push(shippingAddress.zipcode)
  
  if (parts.length > 0) {
    return parts.join(', ')
  }
  
  return shippingAddress.complete_address || 'N/A'
}

const goBack = () => {
  navigateTo('/my-orders')
}

const fetchOrder = async () => {
  loading.value = true
  error.value = null
  
  try {
    const orderData = await purchaseStore.fetchPurchaseById(Number(orderId.value))
    order.value = orderData
  } catch (err: any) {
    console.error('Failed to fetch order:', err)
    error.value = err.message || 'Failed to load order details'
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
    success('Order Cancelled', 'Your order has been cancelled successfully.')
    // Refresh order
    await fetchOrder()
  } catch (err: any) {
    console.error('Error cancelling order:', err)
    const errorMessage = err?.data?.message || err?.data?.error || err?.message || 'Failed to cancel order. Please try again.'
    showError('Cancellation Failed', errorMessage)
  } finally {
    isCancelling.value = false
    closeCancelDialog()
  }
}

// Get tax amount from order (stored when order was created)
const getTaxAmount = () => {
  if (order.value?.shipping_address?.tax_amount !== undefined) {
    return order.value.shipping_address.tax_amount
  }
  // Fallback: calculate from current tax rate if not stored
  const subtotal = order.value.total - (order.value.shipping_address?.shipping_amount || 0) - (order.value.shipping_address?.tax_amount || 0)
  return subtotal * (taxStore.totalTaxRate / 100)
}

// Get subtotal (excluding tax and shipping)
const getSubtotal = () => {
  const shippingAmount = order.value?.shipping_address?.shipping_amount || 0
  const taxAmount = getTaxAmount()
  return order.value.total - shippingAmount - taxAmount
}

onMounted(async () => {
  try {
    await taxStore.fetchTaxPrices()
  } catch (err) {
    console.error('Failed to fetch tax prices:', err)
  }
  fetchOrder()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Content with Sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="lg:sticky lg:top-8 lg:self-start">
            <CustomerSidebar />
          </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-3">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">Order Details</h1>
              <p class="text-gray-600 mt-1">Order #{{ orderId }}</p>
            </div>
            <button 
              @click="goBack" 
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 transition-colors"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back to Orders
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="flex justify-center items-center py-12">
            <LoadingSpinner 
              size="md" 
              color="primary" 
              text="Loading order details..." 
              :show-text="true"
            />
          </div>

          <!-- Error State -->
          <div v-else-if="error || (!loading && !order)" class="bg-red-50 border border-red-200 rounded-lg p-6">
            <div class="flex">
              <svg class="w-5 h-5 text-red-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
              <div>
                <h3 class="text-sm font-medium text-red-800">Order Not Found</h3>
                <div class="mt-2 text-sm text-red-700">
                  <p>{{ error || 'The order you\'re looking for doesn\'t exist or you don\'t have permission to view it.' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Details -->
          <div v-else class="space-y-6">
            <!-- Order Summary -->
            <div class="bg-white rounded-xl shadow border border-gray-200 p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Order Summary</h2>
                <button
                  v-if="order.status === 'pending'"
                  @click="openCancelDialog"
                  class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                >
                  Cancel Order
                </button>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div> 
                  <p class="text-sm text-gray-500">Status</p>
                  <div class="mt-1">
                    <StatusBadge :status="order.status" type="purchase" />
                  </div>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Total Amount</p>
                  <p class="font-medium text-lg">₱{{ formatPrice(order.total) }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Order Date</p>
                  <p class="font-medium">{{ formatDate(order.created_at) }}</p>
                </div>
              </div>
            </div>

            <!-- Shipping Address -->
            <div v-if="order.shipping_address" class="bg-white rounded-xl shadow border border-gray-200 p-6">
              <h2 class="text-xl font-semibold text-gray-900 mb-4">Shipping Address</h2>
              <div class="text-gray-700">
                <p class="font-medium">{{ formatShippingAddress(order.shipping_address) }}</p>
                <p v-if="order.shipping_address?.shipping_amount" class="text-sm text-gray-500 mt-2">
                  Shipping Fee: ₱{{ formatPrice(order.shipping_address.shipping_amount) }}
                </p>
              </div>
            </div>

            <!-- Order Items -->
            <div class="bg-white rounded-xl shadow border border-gray-200 p-6">
              <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Items</h2>
              <div v-if="order.items && order.items.length > 0" class="space-y-4">
                <div v-for="item in order.items" :key="item.id" class="border border-gray-200 rounded-lg p-4">
                  <div class="flex items-start space-x-4">
                    <!-- Product Image -->
                    <div class="flex-shrink-0">
                      <img 
                        :src="getImageUrl(getPrimaryVariantImage(item.variant, item.variant?.product) || '', 'product')"
                        :alt="item.variant?.product?.name || 'Product'"
                        class="w-20 h-20 object-cover rounded-lg border border-gray-200"
                        @error="(e) => { const target = e.target as HTMLImageElement; if (target) target.src = getImageUrl('', 'product') }"
                      />
                    </div>
                    
                    <!-- Product Details -->
                    <div class="flex-1 min-w-0">
                      <h3 class="text-lg font-medium text-gray-900">{{ item.variant?.product?.name || 'Unknown Product' }}</h3>
                      <div class="flex flex-wrap gap-2 text-sm text-gray-500 mt-1">
                        <span v-if="item.variant?.size?.name" class="inline-flex items-center px-2 py-1 bg-gray-100 rounded-md text-xs">
                          Size: {{ item.variant.size.name }}
                        </span>
                        <span v-if="item.variant?.color?.name" class="inline-flex items-center px-2 py-1 bg-gray-100 rounded-md text-xs">
                          Color: {{ item.variant.color.name }}
                        </span>
                      </div>
                      <div class="flex items-center justify-between mt-3">
                        <div class="flex items-center space-x-4">
                          <span class="text-sm text-gray-600">Quantity: {{ item.quantity }}</span>
                          <span class="text-sm text-gray-600">Price: ₱{{ formatPrice(item.price) }}</span>
                        </div>
                        <div class="text-right">
                          <p class="text-lg font-semibold text-gray-900">
                            ₱{{ formatPrice((item.price || 0) * (item.quantity || 0)) }}
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
            <div v-if="order.payment" class="bg-white rounded-xl shadow border border-gray-200 p-6">
              <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment Information</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-500">Payment Method</p>
                  <div class="flex flex-col items-start mt-1">
                    <p class="font-medium">{{ getPaymentMethod(order.payment) }}</p>
                    <div class="mt-1">
                      <PayMongoBranding />
                    </div>
                  </div>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Transaction ID</p>
                  <p class="font-medium font-mono text-sm mt-1">{{ order.payment.transaction_id || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Payment Status</p>
                  <div class="mt-1">
                    <StatusBadge :status="order.payment.status || 'pending'" type="payment" />
                  </div>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Payment Date</p>
                  <p class="font-medium mt-1">{{ order.payment.payment_date ? formatDate(order.payment.payment_date) : 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Order Totals -->
            <div class="bg-white rounded-xl shadow border border-gray-200 p-6">
              <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Subtotal</span>
                  <span class="font-medium">₱{{ formatPrice(getSubtotal()) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Tax</span>
                  <span class="font-medium">₱{{ formatPrice(getTaxAmount()) }}</span>
                </div>
                <div v-if="order.shipping_address?.shipping_amount" class="flex justify-between">
                  <span class="text-gray-600">Shipping</span>
                  <span class="font-medium">₱{{ formatPrice(order.shipping_address.shipping_amount) }}</span>
                </div>
                <div v-else class="flex justify-between">
                  <span class="text-gray-600">Shipping</span>
                  <span class="font-medium">Free</span>
                </div>
                <div class="border-t pt-2 mt-2">
                  <div class="flex justify-between">
                    <span class="text-lg font-semibold text-gray-900">Total</span>
                    <span class="text-lg font-semibold text-gray-900">₱{{ formatPrice(order.total) }}</span>
                  </div>
                </div>
              </div>
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
          This action cannot be undone. Your order will be cancelled and you may be subject to our cancellation policy.
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

