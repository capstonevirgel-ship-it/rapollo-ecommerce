<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { usePurchaseStore } from '~/stores/purchase'
import { usePayMongoStore } from '~/stores/paymongo'
import { getImageUrl } from '~/utils/imageHelper'
import PayMongoBranding from '@/components/PayMongoBranding.vue'

// PayMongo global types
declare global {
  interface Window {
    PayMongo: {
      createPaymentMethod: (options: any) => Promise<any>
      confirmPayment: (paymentIntentId: string, paymentMethodId: string) => Promise<any>
    }
  }
}

// Stores
const cartStore = useCartStore()
const authStore = useAuthStore()
const purchaseStore = usePurchaseStore()
const payMongoStore = usePayMongoStore()
const { cart, guestVersion } = storeToRefs(cartStore)

// Loading state
const isLoading = ref(false)
const isProcessingPayment = ref(false)
const paymentError = ref('')


// Load cart data on page load
onMounted(async () => {
  if (authStore.isAuthenticated) {
    await cartStore.index()
  } else {
    // Load guest cart into store for display
    cartStore.loadGuestCartIntoStore()
  }
})

// Set page title
useHead({
  title: 'Shopping Cart | RAPOLLO',
  meta: [
    { name: 'description', content: 'Review your selected items and proceed to checkout at Rapollo E-commerce.' }
  ]
})

// Computed cart items for template
const cartItems = computed(() => {
  if (authStore.isAuthenticated) {
    // Logged-in: cart from store
    return cart.value.map(item => ({
      id: item.id, // Include the cart item ID for updates
      variant: item.variant || {},
      product: item.variant?.product || {},
      quantity: item.quantity,
      image: getImageUrl(item.variant?.images?.[0]?.url || item.variant?.product?.images?.[0]?.url || '')
    }))
  } else {
    // Guest: cart from localStorage (Cart[])
    // Depend on guestVersion so UI reacts to localStorage updates
    void guestVersion.value
    const guestCart = cartStore.loadGuestCart()
    return guestCart.map(item => ({
      id: item.id, // Include the cart item ID (will be 0 for guest items)
      variant: { 
        id: item.variant_id, 
        price: item.variant?.price || 0,
        stock: item.variant?.stock || 0
      },
      product: item.variant?.product || {},
      quantity: item.quantity,
      image: getImageUrl(item.variant?.images?.[0]?.url || item.variant?.product?.images?.[0]?.url || '')
    }))
  }
})

// Computed totals
const subtotal = computed(() => cartItems.value.reduce((sum, item) => sum + ((item.variant.price ?? 0) * item.quantity), 0))
const shipping = computed(() => 5.99) // example
const tax = computed(() => subtotal.value * 0.08)
const total = computed(() => subtotal.value + shipping.value + tax.value)

// Process PayMongo payment directly
const proceedToCheckout = async () => {
  if (!authStore.isAuthenticated) {
    await navigateTo('/login')
    return
  }
  
  if (!cart.value || cart.value.length === 0) {
    alert('Your cart is empty')
    return
  }
  
  try {
    isProcessingPayment.value = true
    paymentError.value = ''
    
    // Create purchase first
    const purchase = await purchaseStore.createPurchase(cart.value)
    
    // Store purchase ID for success page
    cartStore.setLastPurchaseId(purchase.id)
    
    // Create PayMongo payment intent
    const paymentIntent = await payMongoStore.createPaymentIntent(
      total.value,
      purchase.id,
      {
        order_number: `ORD-${purchase.id}`,
        items_count: cart.value.length
      }
    )
    
    // Redirect to PayMongo payment page
    if (paymentIntent.payment_url) {
      window.location.href = paymentIntent.payment_url
    } else {
      throw new Error('Payment URL not received')
    }
    
  } catch (error: any) {
    console.error('Payment initialization error:', error)
    paymentError.value = error.data?.message || error.message || 'Failed to initialize payment'
    isProcessingPayment.value = false
  }
}


// Check if cart has stock issues
const hasStockIssues = computed(() => {
  return cartItems.value.some(item => {
    const availableStock = item.variant.stock || 0
    return item.quantity > availableStock
  })
})

// Get stock status for an item
const getItemStockStatus = (item: any) => {
  const stock = item.variant.stock || 0
  if (stock === 0) return { hasIssue: true, message: 'Out of stock', class: 'text-red-600' }
  if (item.quantity > stock) return { hasIssue: true, message: `Only ${stock} available`, class: 'text-red-600' }
  if (stock <= 5) return { hasIssue: false, message: `Only ${stock} left`, class: 'text-orange-600' }
  return { hasIssue: false, message: '', class: '' }
}

// Handlers
const increaseQty = (item: any) => {
  // Check stock before increasing
  const stock = item.variant.stock || 0
  if (item.quantity >= stock) {
    alert(`Cannot add more. Only ${stock} items available in stock.`)
    return
  }

  if (authStore.isAuthenticated) {
    cartStore.updateCart(item.id, item.quantity + 1, true)
  } else {
    cartStore.updateCart(item.variant.id, item.quantity + 1, false)
  }
}

const decreaseQty = (item: any) => {
  if (item.quantity <= 1) return
  if (authStore.isAuthenticated) {
    cartStore.updateCart(item.id, item.quantity - 1, true)
  } else {
    cartStore.updateCart(item.variant.id, item.quantity - 1, false)
  }
}
</script>

<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Your Cart</h1>

    <!-- Empty Cart State -->
    <div v-if="cartItems.length === 0" class="text-center py-16">
      <div class="max-w-md mx-auto">
        <!-- Shopping Cart Icon -->
        <div class="mb-8">
          <svg class="mx-auto h-24 w-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5 6m0 0h9m-9 0a2 2 0 100 4 2 2 0 000-4zm9 0a2 2 0 100 4 2 2 0 000-4z" />
          </svg>
        </div>
        
        <!-- Simple Message -->
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Your cart is empty</h2>
        <p class="text-lg text-gray-600 mb-8">Add some items to get started with your order.</p>
        
        <!-- Action Button -->
        <div class="space-y-4">
          <NuxtLink 
            to="/shop" 
            class="inline-flex items-center justify-center px-8 py-3 bg-zinc-900 text-white font-medium rounded-lg hover:bg-zinc-800 transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            Start Shopping
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Cart with Items -->
    <div v-else class="flex flex-col lg:flex-row gap-8">
      <!-- Cart Items -->
      <div class="lg:w-3/4">
        <div class="bg-white rounded-lg shadow-sm divide-y divide-gray-200">
           <!-- Header -->
           <div class="hidden md:grid grid-cols-12 gap-4 p-4 bg-gray-50 rounded-t-lg">
             <div class="col-span-5 font-medium text-gray-500">Product</div>
             <div class="col-span-2 font-medium text-gray-500 text-center">Price</div>
             <div class="col-span-2 font-medium text-gray-500 text-center">Quantity</div>
             <div class="col-span-2 font-medium text-gray-500 text-center">Subtotal</div>
             <div class="col-span-1 font-medium text-gray-500 text-center">Action</div>
           </div>

           <!-- Items -->
           <div v-for="(item, index) in cartItems" :key="index" class="p-4 border-b border-gray-100 last:border-b-0">
             <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
               <!-- Product -->
               <div class="col-span-5 flex items-center space-x-4">
                 <img 
                   :src="getImageUrl(item.image)" 
                   :alt="item.product.name"
                   class="w-16 h-16 object-cover rounded-lg"
                 />
                 <div class="min-w-0 flex-1">
                   <h3 class="text-lg font-medium text-gray-900 truncate">{{ item.product.name }}</h3>
                   <!-- Stock Warning -->
                   <p v-if="getItemStockStatus(item).message" :class="['text-xs font-medium mt-1', getItemStockStatus(item).class]">
                     <Icon v-if="getItemStockStatus(item).hasIssue" name="mdi:alert-circle" class="inline-block" />
                     <Icon v-else name="mdi:information" class="inline-block" />
                     {{ getItemStockStatus(item).message }}
                   </p>
                 </div>
               </div>

               <!-- Price -->
               <div class="col-span-2 text-center">
                 <span class="text-lg font-semibold text-gray-900">₱{{ (item.variant.price ?? 0).toFixed(2) }}</span>
               </div>

               <!-- Quantity -->
               <div class="col-span-2 flex justify-center">
                 <div class="flex items-center border border-gray-300 rounded-lg">
                   <button 
                     class="px-3 py-2 text-gray-600 hover:bg-gray-100 transition rounded-l-lg"
                     @click="decreaseQty(item)"
                   >
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                     </svg>
                   </button>
                   <span class="px-4 py-2 text-gray-900 font-medium min-w-[3rem] text-center">{{ item.quantity }}</span>
                   <button 
                     class="px-3 py-2 text-gray-600 hover:bg-gray-100 transition rounded-r-lg"
                     @click="increaseQty(item)"
                   >
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                     </svg>
                   </button>
                 </div>
               </div>

               <!-- Subtotal -->
               <div class="col-span-2 text-center">
                 <span class="text-lg font-bold text-gray-900">₱{{ ((item.variant.price ?? 0) * item.quantity).toFixed(2) }}</span>
               </div>

               <!-- Delete Button -->
               <div class="col-span-1 flex justify-center">
                 <button
                   class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full transition-all duration-200"
                   aria-label="Remove item"
                   @click="() => {
                     if (authStore.isAuthenticated) {
                       cartStore.removeFromCart(item.id)
                     } else {
                       cartStore.removeFromCart(item.variant.id, false)
                     }
                   }"
                 >
                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                   </svg>
                 </button>
               </div>
             </div>
           </div>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="lg:w-1/4">
        <div class="bg-white rounded-lg shadow-sm p-6 sticky top-8">
          <h2 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h2>

          <div class="space-y-4">
            <div class="flex justify-between">
              <span class="text-gray-600">Subtotal</span>
              <span class="font-medium">₱{{ subtotal.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Shipping</span>
              <span class="font-medium">₱{{ shipping.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Tax</span>
              <span class="font-medium">₱{{ tax.toFixed(2) }}</span>
            </div>
            <div class="border-t border-gray-200 pt-4 flex justify-between">
              <span class="text-lg font-bold text-gray-900">Total</span>
              <span class="text-lg font-bold text-gray-900">₱{{ total.toFixed(2) }}</span>
            </div>
          </div>

           <!-- Payment Error Display -->
           <div v-if="paymentError" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
             <p class="text-sm text-red-600">{{ paymentError }}</p>
           </div>

           <!-- Stock Issues Warning -->
           <div v-if="hasStockIssues" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
             <p class="text-sm text-red-600 font-medium flex items-center">
               <Icon name="mdi:alert-circle" class="mr-2" />
               Some items exceed available stock. Please adjust quantities.
             </p>
           </div>

           <LoadingButton
             :loading="isProcessingPayment"
             :disabled="!authStore.isAuthenticated || isProcessingPayment || hasStockIssues"
             loading-text="Processing Payment..."
             :normal-text="authStore.isAuthenticated ? 'Proceed to Checkout' : 'Login to Checkout'"
             variant="primary"
             size="lg"
             class="w-full mt-6"
             @click="proceedToCheckout"
           />

          <p class="mt-4 text-center text-sm text-gray-500">
            or <NuxtLink to="/shop" class="text-black underline">Continue Shopping</NuxtLink>
          </p>
          
          <!-- PayMongo Branding -->
          <div class="mt-4 flex justify-center">
            <PayMongoBranding />
          </div>
        </div>
      </div>
    </div>


  </div>
</template>
