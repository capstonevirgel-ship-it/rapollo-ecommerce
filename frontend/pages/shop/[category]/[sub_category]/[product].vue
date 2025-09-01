<script setup lang="ts">
import Breadcrumbs from '@/components/navigation/Breadcrumbs.vue'
import Carousel from '@/components/Carousel.vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useProductStore } from '~/stores/product'
import { useSubcategoryStore } from '~/stores/subcategory'
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from "~/stores/auth"

import { getImageUrl } from '~/utils/imageHelper'

const route = useRoute()
const { category, sub_category: subSlug, product: productSlug } = route.params

// Stores
const productStore = useProductStore()
const subcategoryStore = useSubcategoryStore()
const cartStore = useCartStore()
const authStore = useAuthStore()

const { product, loading, error } = storeToRefs(productStore)

// Fetch product on page load
await productStore.fetchProduct(productSlug as string)

// Related products from same subcategory
let relatedProducts: any[] = []
if (product.value?.subcategory_id) {
  await subcategoryStore.fetchSubcategory(product.value.subcategory_id)
  relatedProducts = subcategoryStore.subcategory?.products
    ?.filter(p => p.slug !== productSlug)
    .map((p, index) => ({ ...p, id: index })) || []
}

const addToCart = () => {
  if (!product.value) return

  const variant = product.value.variants?.[0]
  if (!variant) return

  cartStore.addToCart(
    {
      variant_id: variant.id,
      quantity: 1,
    },
    authStore.isAuthenticated 
  )
}
</script>

<template>
  <div class="max-w-6xl mx-auto p-6">
    <Breadcrumbs />

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-4 animate-pulse">
      <div class="h-64 bg-gray-200 rounded"></div>
      <div class="h-6 bg-gray-200 rounded w-1/3"></div>
      <div class="h-4 bg-gray-200 rounded w-2/3"></div>
      <div class="h-10 bg-gray-300 rounded w-1/4"></div>
    </div>

    <!-- Error -->
    <p v-else-if="error" class="text-red-500">{{ error }}</p>

    <!-- Product detail -->
    <div v-else-if="product" class="space-y-8">
      <div class="flex flex-col md:flex-row gap-6">
        <img
          :src="getImageUrl(product.images?.[0]?.url)"
          alt=""
          class="w-full md:w-1/2 h-auto object-cover rounded"
        />

        <div class="md:w-1/2">
          <h1 class="text-3xl font-bold mb-2">{{ product.name }}</h1>
          <p class="text-primary-600 font-semibold">
            ₱{{ product.price?.toFixed(2) ?? product.variants?.[0]?.price?.toFixed(2) }}
          </p>
          <p class="text-gray-700 leading-relaxed mb-6">{{ product.description }}</p>

          <button
            class="px-6 py-2 bg-zinc-800 text-white font-medium rounded hover:bg-primary-700 transition cursor-pointer"
            @click="addToCart"
          >
            Add to Cart
          </button>
        </div>
      </div>

      <!-- Related Products -->
      <div v-if="relatedProducts.length" class="mt-12">
        <h2 class="text-2xl font-semibold mb-4">Related Products</h2>
        <Carousel
          :items="relatedProducts"
          :items-to-show="3"
          :autoplay="false"
          :show-arrows="true"
        >
          <template #item="{ item }">
            <NuxtLink
              :to="`/shop/${category}/${subSlug}/${item.slug}`"
              class="block rounded shadow transition"
            >
              <img
                :src="getImageUrl(item.images?.[0]?.url)"
                :alt="item.name"
                class="w-full h-48 object-cover rounded-t mb-3"
              />
              <div class="p-4">
                <h3 class="font-medium text-gray-900">{{ item.name }}</h3>
                <p class="text-primary-600 font-semibold">
                  ₱{{ item.price?.toFixed(2) ?? item.variants?.[0]?.price?.toFixed(2) }}
                </p>
              </div>
            </NuxtLink>
          </template>
        </Carousel>
      </div>
    </div>

    <!-- Not found -->
    <p v-else class="text-red-500">Product not found.</p>
  </div>
</template>
