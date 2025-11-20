<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '~/stores/cart'
import { usePurchaseStore } from '~/stores/purchase'
import { useTicketStore } from '~/stores/ticket'
import Carousel from '~/components/Carousel.vue'
import { getImageUrl } from '~/utils/imageHelper'

// Define page meta
definePageMeta({
  layout: 'default'
})

// Set page title
useHead({
  title: 'Order Successful | RAPOLLO',
  meta: [
    { name: 'description', content: 'Your order has been successfully placed at Rapollo E-commerce.' }
  ]
})

// Stores
const cartStore = useCartStore()
const purchaseStore = usePurchaseStore()
const ticketStore = useTicketStore()
const route = useRoute()

// Reactive data
const purchase = ref<any>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const isFetching = ref(false) // Guard to prevent recursive calls

// Computed properties
const isTicketPurchase = computed(() => purchase.value?.type === 'ticket')
const orderNumber = computed(() => {
  if (!purchase.value) return ''
  if (purchase.value.type === 'ticket') {
    return `TKT-${purchase.value.id}`
  }
  return purchase.value.order_number || `ORD-${purchase.value.id}`
})

const toNumber = (value: unknown) => {
  if (typeof value === 'number' && Number.isFinite(value)) return value
  if (typeof value === 'string') {
    const parsed = parseFloat(value)
    return Number.isFinite(parsed) ? parsed : 0
  }
  return 0
}

const formatCurrency = (value: unknown) => {
  const num = toNumber(value)
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const totalAmount = computed(() => toNumber(purchase.value?.total))
const purchaseDate = computed(() => {
  if (!purchase.value?.created_at) return ''
  try {
    return new Date(purchase.value.created_at).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
  } catch {
    return ''
  }
})

// Fetch purchase details
const fetchPurchaseDetails = async () => {
  // Prevent recursive calls
  if (isFetching.value) {
    console.warn('fetchPurchaseDetails already in progress, skipping...')
    return
  }
  
  try {
    isFetching.value = true
    loading.value = true
    error.value = null
    
    // Get purchase_id from URL params or session storage
    // Check both product purchase and ticket purchase IDs
    const productPurchaseId = route.query.purchase_id || route.query.product_purchase_id || sessionStorage.getItem('last_purchase_id')
    const ticketPurchaseId = route.query.ticket_purchase_id || sessionStorage.getItem('last_ticket_purchase_id')
    
    let purchaseData: any = null
    
    // Try ticket purchase first if ticket_purchase_id is available
    if (ticketPurchaseId) {
      try {
        const response = await $fetch(`/api/ticket-purchases/${ticketPurchaseId}`) as { data: any }
        purchaseData = response.data
        purchaseData.type = 'ticket' // Mark as ticket purchase
        
        // Use tickets from purchase data instead of fetching all tickets
        // This prevents unnecessary API calls and recursive issues
        if (purchaseData.tickets && purchaseData.tickets.length > 0) {
          // Update ticket store with only the tickets for this purchase
          ticketStore.tickets = purchaseData.tickets
        }
        
        // Clear ticket purchase ID from session
        sessionStorage.removeItem('last_ticket_purchase_id')
      } catch (err: any) {
        console.warn('Failed to fetch ticket purchase, trying product purchase:', err)
      }
    }
    
    // If no ticket purchase found, try product purchase
    if (!purchaseData && productPurchaseId) {
      purchaseData = await purchaseStore.fetchPurchaseById(Number(productPurchaseId))
      purchaseData.type = 'product' // Mark as product purchase
    }
    
    if (!purchaseData) {
      throw new Error('No purchase ID found')
    }
    
    purchase.value = purchaseData
    
    // Selectively clear only purchased items
    // 1) Build set of purchased variant ids
    const purchasedVariantIds = new Set<number>(
      (purchaseData.items || []).map((it: any) => it.variant_id).filter((id: any) => typeof id === 'number')
    )
    // 2) Fallback to session storage list if API items missing
    if (purchasedVariantIds.size === 0 && typeof window !== 'undefined') {
      try {
        const raw = sessionStorage.getItem('purchased_variant_ids')
        const arr = raw ? JSON.parse(raw) : []
        arr.forEach((id: any) => { if (typeof id === 'number') purchasedVariantIds.add(id) })
      } catch {}
    }
    
    // 3) If we have purchased variant ids, remove only those from the server cart
    if (purchasedVariantIds.size > 0) {
      try {
        // Refresh cart to get current cart item ids
        await cartStore.index().catch(() => {})
        const toRemove = cartStore.cart.filter((ci: any) => purchasedVariantIds.has(ci.variant_id))
        for (const ci of toRemove) {
          await cartStore.removeFromCart(ci.id, true)
        }
      } catch (e) {
        console.warn('Selective cart cleanup failed, falling back to full clear')
        cartStore.clearCart()
      }
    } else {
      // If we cannot identify which items were purchased, do not nuke remaining items
      // but ensure we at least refresh cart state
      await cartStore.index().catch(() => {})
    }
    
    // Clear session storage (only if it was a product purchase)
    if (purchase.value?.type === 'product') {
      sessionStorage.removeItem('last_purchase_id')
      sessionStorage.removeItem('purchased_variant_ids')
    }
    
  } catch (err: any) {
    error.value = err.message || 'Failed to fetch order details'
    console.error('Error fetching purchase details:', err)
  } finally {
    loading.value = false
    isFetching.value = false
  }
}

// Clear cart on success
onMounted(() => {
  fetchPurchaseDetails()
})

const ticketsForPurchase = computed(() => {
  if (purchase.value?.type !== 'ticket' || !purchase.value?.id) return []
  
  // Prefer tickets directly from purchase data (already filtered and included in API response)
  // This prevents unnecessary filtering and reactive loops
  if (purchase.value.tickets && Array.isArray(purchase.value.tickets) && purchase.value.tickets.length > 0) {
    return purchase.value.tickets
  }
  
  // Fallback to filtering from ticket store if purchase data doesn't have tickets
  const purchaseId = purchase.value.id // Capture ID to avoid reactive access in filter
  return (ticketStore.tickets || []).filter((ticket: any) => 
    ticket.ticket_purchase_id === purchaseId || ticket.purchase_id === purchaseId
  )
})

const hasTicketData = computed(() => ticketsForPurchase.value.length > 0)
const ticketLoading = computed(() => ticketStore.loading)
const ticketError = computed(() => ticketStore.error)

const ticketEventInfo = computed(() => {
  if (purchase.value?.type !== 'ticket') return null
  const event = purchase.value?.event || ticketsForPurchase.value[0]?.event
  if (!event) return null
  const dateSource = event.date || event.event_date

  const eventDate = dateSource ? new Date(dateSource) : null

  return {
    title: event.title,
    location: event.location,
    posterUrl: event.poster_url,
    formattedDate: eventDate
      ? eventDate.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      : null,
    formattedTime: eventDate
      ? eventDate.toLocaleTimeString('en-US', {
          hour: '2-digit',
          minute: '2-digit'
        })
      : null
  }
})

const displayTickets = computed(() => {
  if (purchase.value?.type !== 'ticket') return []
  if (!ticketEventInfo.value) return []

  const base = ticketsForPurchase.value.length
    ? ticketsForPurchase.value
    : [
        {
          ticket_number: purchase.value?.ticket_number || orderNumber.value,
          event: purchase.value?.event
        }
      ]

  return base.map((ticket: any, index: number) => ({
    id: ticket.id || index + 1,
    ticketNumber: ticket.ticket_number || `TKT-${index + 1}`,
    orderNumber: orderNumber.value,
    event: ticketEventInfo.value
  }))
})

const totalTicketQuantity = computed(() => {
  if (purchase.value?.type !== 'ticket') return 0
  const fromTickets = ticketsForPurchase.value.reduce((sum: number, ticket: any) => sum + (toNumber(ticket.quantity) || 1), 0)
  if (fromTickets > 0) return fromTickets
  if (purchase.value?.quantity) return toNumber(purchase.value.quantity)
  return ticketsForPurchase.value.length || 1
})

const ticketUnitPrice = computed(() => {
  if (purchase.value?.type !== 'ticket') return 0
  const ticket = ticketsForPurchase.value[0]
  const value =
    (ticket && (toNumber(ticket.price) || toNumber(ticket.event?.ticket_price))) ||
    toNumber(purchase.value?.event?.ticket_price)

  if (value) return value
  return totalTicketQuantity.value ? totalAmount.value / totalTicketQuantity.value : totalAmount.value || 0
})

const getProductItemUnitPrice = (item: any) => {
  const candidates = [
    item?.final_unit_price,
    item?.finalUnitPrice,
    item?.unit_price,
    item?.unitPrice,
    item?.price,
  ]

  for (const candidate of candidates) {
    if (candidate === undefined || candidate === null) continue
    const numeric = typeof candidate === 'string' ? parseFloat(candidate) : Number(candidate)
    if (!Number.isNaN(numeric)) {
      return numeric
    }
  }

  return 0
}

const getProductItemTotalPrice = (item: any) => {
  const candidates = [
    item?.final_total_price,
    item?.finalTotalPrice,
    item?.total_price,
    item?.totalPrice,
  ]

  for (const candidate of candidates) {
    if (candidate === undefined || candidate === null) continue
    const numeric = typeof candidate === 'string' ? parseFloat(candidate) : Number(candidate)
    if (!Number.isNaN(numeric)) {
      return numeric
    }
  }

  const unit = getProductItemUnitPrice(item)
  const quantity = typeof item?.quantity === 'number' ? item.quantity : parseInt(item?.quantity ?? 0, 10)
  return unit * (Number.isNaN(quantity) ? 0 : quantity)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center">
        <LoadingSpinner 
          size="xl" 
          color="primary" 
          text="Loading Order Details..." 
          :show-text="true"
        />
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-4">Unable to Load Order Details</h1>
        <p class="text-lg text-gray-600 mb-8">{{ error }}</p>
        <NuxtLink
          to="/my-orders"
          class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 transition"
        >
          View My Orders
        </NuxtLink>
      </div>

      <!-- Success State -->
      <div v-else-if="purchase" class="space-y-8">
        <!-- Success Header -->
      <div class="text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          </div>
          <h1 class="text-3xl font-bold text-gray-900 mb-4">
            {{ isTicketPurchase ? 'Tickets Purchased Successfully!' : 'Order Successful!' }}
          </h1>
          <p class="text-lg text-gray-600 mb-8">
            Thank you for your {{ isTicketPurchase ? 'ticket purchase' : 'purchase' }}. 
            {{ isTicketPurchase ? 'Your tickets have been confirmed' : 'Your order has been processed' }} and you will receive a confirmation email shortly.
          </p>
        </div>

        <!-- Ticket Summary -->
        <div v-if="isTicketPurchase" class="space-y-6">
          <div v-if="ticketLoading" class="bg-white border border-gray-200 rounded-lg p-6 text-center text-gray-600 shadow-sm">
            Fetching your tickets...
          </div>
          <div v-else-if="ticketError" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center text-red-700 shadow-sm">
            {{ ticketError }}
          </div>
          <div v-else class="space-y-6">
            <!-- Single ticket (no slider) -->
            <div v-if="ticketEventInfo && displayTickets.length === 1" class="relative bg-white border border-gray-200 rounded-lg shadow-sm overflow-visible">
              <div class="relative">
                <div class="h-64 w-full">
                  <img
                    :src="getImageUrl(displayTickets[0].event.posterUrl)"
                    :alt="displayTickets[0].event.title"
                    class="h-full w-full object-cover rounded-t-3xl"
                  />
                </div>
              </div>

              <div class="p-6 space-y-6">
                <div class="space-y-2">
                  <h2 class="text-2xl font-winner-extra-bold leading-tight text-gray-900">
                    {{ displayTickets[0].event.title }}
                  </h2>
                  <p v-if="displayTickets[0].event.location" class="text-sm font-medium text-gray-600">
                    {{ displayTickets[0].event.location }}
                  </p>
                </div>

                <div class="flex flex-wrap gap-3">
                  <div
                    v-if="displayTickets[0].event.formattedDate"
                    class="px-5 py-2 rounded-full border border-zinc-900 text-sm font-semibold text-zinc-900 uppercase tracking-wide"
                  >
                    {{ displayTickets[0].event.formattedDate }}
                  </div>
                  <div
                    v-if="displayTickets[0].event.formattedTime"
                    class="px-5 py-2 rounded-full border border-zinc-900 text-sm font-semibold text-zinc-900 uppercase tracking-wide"
                  >
                    {{ displayTickets[0].event.formattedTime }}
                  </div>
                  <div class="px-5 py-2 rounded-full border border-zinc-900 text-sm font-semibold text-zinc-900 uppercase tracking-wide">
                    Price: ₱{{ formatCurrency(ticketUnitPrice) }}
                  </div>
                </div>
              </div>

              <div class="relative bg-gray-50 px-6 py-6 border-t-2 border-dashed border-gray-300 rounded-b-3xl">
                <div class="absolute left-0 top-0 -translate-x-1/2 -translate-y-1/2">
                  <div class="w-12 h-12 bg-gray-50 border-2 border-gray-200 rounded-full [clip-path:inset(0_0_0_22px)]"></div>
                </div>
                <div class="absolute right-0 top-0 translate-x-1/2 -translate-y-1/2">
                  <div class="w-12 h-12 bg-gray-50 border-2 border-gray-200 rounded-full [clip-path:inset(0_22px_0_0)]"></div>
                </div>
                <div class="flex flex-col items-center text-center space-y-3">
                  <span class="text-xl font-winner-extra-bold tracking-[0.2em] text-gray-900">
                    {{ displayTickets[0].ticketNumber }}
                  </span>
                  <div class="w-full h-px bg-[repeating-linear-gradient(90deg,#111111 0px,#111111 2px,transparent 2px,transparent 4px)]"></div>
                </div>
              </div>
            </div>

            <!-- Multiple tickets (with slider) -->
            <Carousel
              v-else-if="ticketEventInfo && displayTickets.length > 1"
              :items="displayTickets"
              :items-to-show="1"
              :items-to-scroll="1"
              :autoplay="false"
              :show-arrows="true"
            >
              <template #item="{ item }">
                <div class="relative bg-white border border-gray-200 rounded-lg shadow-sm overflow-visible">
                  <div class="relative">
                    <div class="h-64 w-full">
                      <img
                        :src="getImageUrl(item.event.posterUrl)"
                        :alt="item.event.title"
                        class="h-full w-full object-cover rounded-t-3xl"
                      />
                    </div>
                  </div>

                  <div class="p-6 space-y-6">
                    <div class="space-y-2">
                      <h2 class="text-2xl font-winner-extra-bold leading-tight text-gray-900">
                        {{ item.event.title }}
                      </h2>
                      <p v-if="item.event.location" class="text-sm font-medium text-gray-600">
                        {{ item.event.location }}
                      </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                      <div
                        v-if="item.event.formattedDate"
                        class="px-5 py-2 rounded-full border border-zinc-900 text-sm font-semibold text-zinc-900 uppercase tracking-wide"
                      >
                        {{ item.event.formattedDate }}
                      </div>
                      <div
                        v-if="item.event.formattedTime"
                        class="px-5 py-2 rounded-full border border-zinc-900 text-sm font-semibold text-zinc-900 uppercase tracking-wide"
                      >
                        {{ item.event.formattedTime }}
                      </div>
                      <div class="px-5 py-2 rounded-full border border-zinc-900 text-sm font-semibold text-zinc-900 uppercase tracking-wide">
                        Price: ₱{{ formatCurrency(ticketUnitPrice) }}
                      </div>
                    </div>
                  </div>

                  <div class="relative bg-gray-50 px-6 py-6 border-t-2 border-dashed border-gray-300 rounded-b-3xl">
                    <div class="absolute left-0 top-0 -translate-x-1/2 -translate-y-1/2">
                      <div class="w-12 h-12 bg-gray-50 border-2 border-gray-200 rounded-full [clip-path:inset(0_0_0_22px)]"></div>
                    </div>
                    <div class="absolute right-0 top-0 translate-x-1/2 -translate-y-1/2">
                      <div class="w-12 h-12 bg-gray-50 border-2 border-gray-200 rounded-full [clip-path:inset(0_22px_0_0)]"></div>
                    </div>
                    <div class="flex flex-col items-center text-center space-y-3">
                      <span class="text-xl font-winner-extra-bold tracking-[0.2em] text-gray-900">
                        {{ item.ticketNumber }}
                      </span>
                      <div class="w-full h-px bg-[repeating-linear-gradient(90deg,#111111 0px,#111111 2px,transparent 2px,transparent 4px)]"></div>
                    </div>
                  </div>
                </div>
              </template>
            </Carousel>

            <div
              v-if="displayTickets.length === 0"
              class="bg-white border border-gray-200 rounded-lg p-6 text-center text-gray-600 shadow-sm"
            >
              Ticket records are being prepared. Please refresh this page shortly to view your tickets.
            </div>
          </div>
        </div>

        <!-- Product Order Summary -->
        <div v-else class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Summary</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Order Number</h3>
              <p class="mt-1 text-lg font-semibold text-gray-900">{{ orderNumber }}</p>
            </div>
            <div>
              <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Order Date</h3>
              <p class="mt-1 text-lg font-semibold text-gray-900">{{ purchaseDate }}</p>
            </div>
            <div>
              <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Amount</h3>
              <p class="mt-1 text-lg font-semibold text-gray-900">₱{{ formatCurrency(totalAmount) }}</p>
            </div>
            <div>
              <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Status</h3>
              <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                {{ purchase.status }}
              </span>
            </div>
          </div>

          <div v-if="purchase.items && purchase.items.length > 0" class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Order Items</h3>
            <div class="space-y-4">
              <div 
                v-for="item in purchase.items" 
                :key="item.id"
                class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg"
              >
                <div class="flex-shrink-0">
                  <img 
                    :src="getImageUrl(item.variant?.images?.[0]?.url || item.variant?.product?.images?.[0]?.url || '')"
                    :alt="item.variant?.product?.name || 'Product'"
                    class="w-16 h-16 object-cover rounded-lg border border-gray-200"
                    @error="(e) => { const target = e.target as HTMLImageElement; if (target) target.src = getImageUrl('', 'product') }"
                  />
                </div>

                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-gray-900 truncate">
                    {{ item.variant?.product?.name || 'Product' }}
                  </h4>
                  <div v-if="item.variant?.size || item.variant?.color" class="mt-1 text-sm text-gray-500">
                    <span v-if="item.variant?.size">{{ item.variant.size.name }}</span>
                    <span v-if="item.variant?.size && item.variant?.color">, </span>
                    <span v-if="item.variant?.color">{{ item.variant.color.name }}</span>
                  </div>
                  <div class="mt-1 text-sm text-gray-500">
                    Quantity: {{ item.quantity }}
                  </div>
        </div>

                <div class="text-right">
                  <p class="text-sm font-medium text-gray-900">
                    ₱{{ formatCurrency(getProductItemTotalPrice(item)) }}
                  </p>
                  <p class="text-xs text-gray-500">
                    ₱{{ formatCurrency(getProductItemUnitPrice(item)) }} each
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">What's Next?</h2>
          <div class="space-y-3 text-left">
            <div class="flex items-center space-x-3">
              <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-blue-600 text-sm font-medium">1</span>
              </div>
              <p class="text-gray-700">You will receive an order confirmation email</p>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-blue-600 text-sm font-medium">2</span>
              </div>
              <p class="text-gray-700">
                {{ isTicketPurchase ? 'Your tickets are ready for download' : 'We\'ll prepare your order for shipping' }}
              </p>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-blue-600 text-sm font-medium">3</span>
              </div>
              <p class="text-gray-700">
                {{ isTicketPurchase ? 'Show your tickets at the event entrance' : 'You\'ll receive tracking information once shipped' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <NuxtLink
            :to="isTicketPurchase ? '/my-tickets' : '/shop'"
            class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 transition"
          >
            {{ isTicketPurchase ? 'View My Tickets' : 'Continue Shopping' }}
          </NuxtLink>
          <NuxtLink
            :to="isTicketPurchase ? '/events' : '/my-orders'"
            class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
          >
            {{ isTicketPurchase ? 'Browse More Events' : 'View My Orders' }}
          </NuxtLink>
        </div>

        <!-- Support Info -->
        <div class="text-center">
          <p class="text-sm text-gray-500">
            Need help? 
            <NuxtLink to="/contact" class="text-blue-600 hover:text-blue-500 underline">
              Contact our support team
            </NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>