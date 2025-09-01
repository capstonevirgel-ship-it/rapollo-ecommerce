<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'
import { getImageUrl } from '~/utils/imageHelper'

// Stores
const cartStore = useCartStore()
const authStore = useAuthStore()
const { isAuthenticated } = storeToRefs(authStore)
const { cart } = storeToRefs(cartStore)

// Load cart data on page load
onMounted(async () => {
  if (isAuthenticated.value) {
    await cartStore.index()
  }
})

// Computed cart items for template
const cartItems = computed(() => {
  if (isAuthenticated.value) {
    // Logged-in: cart from store
    return cart.value.map(item => ({
      variant: item.variant,
      product: item.variant.product,
      quantity: item.quantity,
      image: item.variant.images?.[0]?.url || item.variant.product.images?.[0]?.url || ''
    }))
  } else {
    // Guest: cart from localStorage
    const guestCart = cartStore.loadGuestCart()
    return guestCart.map(item => ({
      variant: { id: item.variant_id, price: 0 },
      product: {
        name: 'Product',
        description: ''
      },
      quantity: item.quantity,
      image: ''
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
  if (isAuthenticated.value) {
    cartStore.updateCart(item.id, item.quantity + 1)
  } else {
    item.quantity++
    cartStore.addToCart({ 
      variant_id: item.variant.id, 
      quantity: 1 
    }, false)
  }
}

const decreaseQty = (item: any) => {
  if (item.quantity <= 1) return
  if (isAuthenticated.value) {
    cartStore.updateCart(item.id, item.quantity - 1)
  } else {
    item.quantity--
    cartStore.addToCart({ 
      variant_id: item.variant.id, 
      quantity: -1 
    }, false)
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
            <div class="col-span-6 font-medium text-gray-500">Product</div>
            <div class="col-span-2 font-medium text-gray-500 text-center">Price</div>
            <div class="col-span-2 font-medium text-gray-500 text-center">Quantity</div>
            <div class="col-span-2 font-medium text-gray-500 text-right">Subtotal</div>
          </div>

          <!-- Items -->
          <div v-for="(item, index) in cartItems" :key="index" class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
              <!-- Product -->
              <div class="col-span-6 flex items-center space-x-4">
                <img 
                  :src="getImageUrl(item.image)" 
                  :alt="item.product.name"
                  class="w-20 h-20 object-cover rounded"
                />
                <div>
                  <h3 class="text-lg font-medium text-gray-900">{{ item.product.name }}</h3>
                  <p class="text-sm text-gray-500">{{ item.product.description }}</p>
                </div>
              </div>

              <!-- Price -->
              <div class="col-span-2 text-center">
                ${{ (item.variant.price ?? 0).toFixed(2) }}
              </div>

              <!-- Quantity -->
              <div class="col-span-2 flex justify-center">
                <div class="flex items-center border border-gray-300 rounded-md">
                  <button 
                    class="px-3 py-1 text-gray-600 hover:bg-gray-100 transition"
                    @click="decreaseQty(item)"
                  >
                    <Icon name="mdi:minus" class="w-4 h-4" />
                  </button>
                  <span class="px-3 py-1 text-gray-900">{{ item.quantity }}</span>
                  <button 
                    class="px-3 py-1 text-gray-600 hover:bg-gray-100 transition"
                    @click="increaseQty(item)"
                  >
                    <Icon name="mdi:plus" class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Subtotal -->
              <div class="col-span-2 text-right">
                ${{ ((item.variant.price ?? 0) * item.quantity).toFixed(2) }}
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
            class="mt-6 w-full bg-black text-white py-3 px-4 rounded-md font-medium hover:bg-gray-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!isAuthenticated"
            @click="() => { if (!isAuthenticated) return navigateTo('/login'); navigateTo('/checkout') }"
          >
            {{ isAuthenticated ? 'Checkout' : 'Login to Checkout' }}
          </button>

          <p class="mt-4 text-center text-sm text-gray-500">
            or <NuxtLink to="/shop" class="text-black underline">Continue Shopping</NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
