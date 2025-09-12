<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { getImageUrl } from '~/utils/imageHelper'

// Stores
const cartStore = useCartStore()
const authStore = useAuthStore()
const { cart, guestVersion } = storeToRefs(cartStore)

// Load cart data on page load
onMounted(async () => {
  if (authStore.isAuthenticated) {
    await cartStore.index()
  }
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
      variant: { id: item.variant_id, price: item.variant?.price || 0 },
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

// Handlers
const increaseQty = (item: any) => {
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

    <div class="flex flex-col lg:flex-row gap-8">
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
                 </div>
               </div>

               <!-- Price -->
               <div class="col-span-2 text-center">
                 <span class="text-lg font-semibold text-gray-900">${{ (item.variant.price ?? 0).toFixed(2) }}</span>
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
                 <span class="text-lg font-bold text-gray-900">${{ ((item.variant.price ?? 0) * item.quantity).toFixed(2) }}</span>
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
              <span class="font-medium">${{ subtotal.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Shipping</span>
              <span class="font-medium">${{ shipping.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Tax</span>
              <span class="font-medium">${{ tax.toFixed(2) }}</span>
            </div>
            <div class="border-t border-gray-200 pt-4 flex justify-between">
              <span class="text-lg font-bold text-gray-900">Total</span>
              <span class="text-lg font-bold text-gray-900">${{ total.toFixed(2) }}</span>
            </div>
          </div>

           <button
             class="mt-6 w-full bg-zinc-900 text-white py-3 px-4 rounded-md font-medium hover:bg-zinc-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
             :disabled="!authStore.isAuthenticated"
             @click="() => { if (!authStore.isAuthenticated) return navigateTo('/login'); navigateTo('/checkout') }"
           >
             {{ authStore.isAuthenticated ? 'Checkout' : 'Login to Checkout' }}
           </button>

          <p class="mt-4 text-center text-sm text-gray-500">
            or <NuxtLink to="/shop" class="text-black underline">Continue Shopping</NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
