<script setup lang="ts">
import { onMounted, computed, ref, watch } from "vue"
import { useProductStore } from "@/stores/product"
import Pagination from "@/components/Pagination.vue"
import { getImageUrl } from "@/utils/imageHelper"

definePageMeta({
  layout: "default"
})

// ---------------------
// Store
// ---------------------
const productStore = useProductStore()

// ---------------------
// State
// ---------------------
const currentPage = ref(1)
const perPage = 12 // show 12 products per page
const isLoading = ref(false)

// Pagination metadata (local, not from store)
const pagination = ref({
  total: 0,
  per_page: perPage,
  current_page: 1,
  total_pages: 1
})

const handlePageChange = (page: number) => {
  currentPage.value = page
}

// ---------------------
// Fetch on mount & when page changes
// ---------------------
const fetchData = async () => {
  try {
    isLoading.value = true
    const response: any = await productStore.fetchProducts({
      page: currentPage.value,
      per_page: perPage
    })

    // If API returns meta, set pagination
    if (response?.meta) {
      pagination.value = {
        total: response.meta.total,
        per_page: response.meta.per_page,
        current_page: response.meta.current_page,
        total_pages: response.meta.last_page
      }
    } else {
      // fallback if no meta (just one page)
      pagination.value = {
        total: response?.length || 0,
        per_page: perPage,
        current_page: currentPage.value,
        total_pages: 1
      }
    }
  } catch (err) {
    console.error("Failed to fetch products", err)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchData)
watch(currentPage, fetchData)

// ---------------------
// Products
// ---------------------
const products = computed(() => productStore.products)
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Shop</h1>

    <!-- Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
      <!-- Skeleton Loader -->
      <div
        v-if="isLoading"
        v-for="n in perPage"
        :key="n"
        class="border rounded-lg shadow-sm bg-white animate-pulse"
      >
        <div class="w-full h-48 bg-gray-200 rounded-t-lg"></div>
        <div class="p-4 space-y-2">
          <div class="h-4 bg-gray-200 rounded w-3/4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/2"></div>
        </div>
      </div>

      <!-- Products -->
      <NuxtLink
        v-else
        v-for="product in products"
        :key="product.id"
        :to="`/shop/${product.subcategory?.category?.slug}/${product.subcategory?.slug}/${product.slug}`"
        class="border rounded-lg shadow-sm hover:shadow-md transition bg-white block"
      >
        <!-- Product image -->
        <img
          :src="getImageUrl(product.images?.find(img => img.is_primary)?.url || null)"
          alt="Product"
          class="w-full h-48 object-cover rounded-t-lg"
        />

        <!-- Product info -->
        <div class="p-4">
          <h2 class="text-sm font-medium text-gray-900 truncate">
            {{ product.name }}
          </h2>
          <p class="text-primary-600 font-semibold">
            ₱{{ product.variants?.[0]?.price?.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
          </p>
        </div>
      </NuxtLink>
    </div>

    <!-- Empty state (only when not loading) -->
    <p v-if="!isLoading && products.length === 0" class="text-gray-500 mt-6">
      No products available.
    </p>

    <!-- Pagination -->
    <Pagination
      v-if="!isLoading && pagination.total_pages > 1"
      :current-page="pagination.current_page"
      :total-pages="pagination.total_pages"
      @page-change="handlePageChange"
    />
  </div>
</template>
