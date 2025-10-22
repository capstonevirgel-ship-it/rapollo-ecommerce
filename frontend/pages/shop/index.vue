<script setup lang="ts">
import { onMounted, computed, ref, watch, nextTick } from "vue"
import { useProductStore } from "@/stores/product"
import { useBrandStore } from "@/stores/brand"
import ProductFilters from "@/components/ProductFilters.vue"
import Pagination from "@/components/Pagination.vue"
import { getImageUrl } from "@/utils/imageHelper"

definePageMeta({
  layout: "default"
})

// ---------------------
// Stores
// ---------------------
const productStore = useProductStore()
const brandStore = useBrandStore()

// ---------------------
// Route & Query
// ---------------------
const route = useRoute()
const router = useRouter()
const brandSlug = computed(() => route.query.brand as string)
const currentBrand = computed(() => {
  if (!brandSlug.value) return null
  return brandStore.brands.find(brand => brand.slug === brandSlug.value)
})

// ---------------------
// State
// ---------------------
const currentPage = ref(1)
const perPage = 9 // show 9 products per page (3 columns x 3 rows)
const isLoading = ref(false)
const showFilters = ref(false)
const isUpdatingFilters = ref(false) // Flag to prevent recursive calls

// Filter state
interface ProductFilters {
  search: string
  is_featured: boolean
  is_hot: boolean
  is_new: boolean
  min_price: number | null
  max_price: number | null
  categories: string[]
  subcategories: string[]
  brands: string[]
}

const filters = ref<ProductFilters>({
  search: '',
  is_featured: false,
  is_hot: false,
  is_new: false,
  min_price: null,
  max_price: null,
  categories: [],
  subcategories: [],
  brands: []
})

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
  // Prevent multiple simultaneous API calls
  if (isLoading.value) {
    console.log('API call already in progress, skipping...')
    return
  }
  
  try {
    isLoading.value = true
    
    // Build query parameters
    const params: any = {
      page: currentPage.value,
      per_page: perPage
    }
    
    // Add brand filter from URL if present (overrides filter state)
    if (brandSlug.value) {
      params.brand = brandSlug.value
    }
    
    // Add all other filters
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.is_featured) params.is_featured = true
    if (filters.value.is_hot) params.is_hot = true
    if (filters.value.is_new) params.is_new = true
    if (filters.value.min_price) params.min_price = filters.value.min_price
    if (filters.value.max_price) params.max_price = filters.value.max_price
    if (filters.value.categories.length > 0) params.category = filters.value.categories[0] // Backend only supports one category
    if (filters.value.subcategories.length > 0) params.subcategory = filters.value.subcategories[0] // Backend only supports one subcategory
    if (filters.value.brands.length > 0 && !brandSlug.value) params.brand = filters.value.brands[0] // Backend only supports one brand
    
    console.log('Fetching products with params:', params)
    const response: any = await productStore.fetchProducts(params)

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

// ---------------------
// Computed
// ---------------------
const products = computed(() => productStore.products)
const pageTitle = computed(() => {
  if (currentBrand.value) {
    return `${currentBrand.value.name} Products`
  }
  return "Shop"
})

// Set page title
useHead(() => {
  if (currentBrand.value) {
    const title = currentBrand.value.meta_title || `${currentBrand.value.name} Products`
    return {
      title: `${title} - Rapollo E-commerce`,
      meta: [
        { name: 'description', content: currentBrand.value.meta_description || `Shop ${currentBrand.value.name} products at Rapollo E-commerce` }
      ]
    }
  }
  return {
    title: 'Shop - Rapollo E-commerce',
    meta: [
      { name: 'description', content: 'Shop premium products at Rapollo E-commerce. Find the best merchandise and event tickets.' }
    ]
  }
})

const hasActiveFilters = computed(() => {
  return filters.value.search ||
         filters.value.is_featured ||
         filters.value.is_hot ||
         filters.value.is_new ||
         filters.value.min_price ||
         filters.value.max_price ||
         filters.value.categories.length > 0 ||
         filters.value.subcategories.length > 0 ||
         filters.value.brands.length > 0
})

// ---------------------
// Methods
// ---------------------
const updateFilters = (newFilters: ProductFilters) => {
  // Only update if the filters actually changed to prevent recursive calls
  if (JSON.stringify(filters.value) !== JSON.stringify(newFilters)) {
    isUpdatingFilters.value = true
    filters.value = newFilters
    nextTick(() => {
      isUpdatingFilters.value = false
    })
  }
}

const clearFilters = () => {
  isUpdatingFilters.value = true
  filters.value = {
    search: '',
    is_featured: false,
    is_hot: false,
    is_new: false,
    min_price: null,
    max_price: null,
    categories: [],
    subcategories: [],
    brands: []
  }
  nextTick(() => {
    isUpdatingFilters.value = false
  })
}

const toggleFilters = () => {
  showFilters.value = !showFilters.value
}

// ---------------------
// Watchers
// ---------------------
onMounted(async () => {
  // Fetch brands first to get brand info
  await brandStore.fetchBrands()
  await fetchData()
})

watch(currentPage, fetchData)

// Single watcher with debouncing and recursion prevention
let debounceTimeout: NodeJS.Timeout
watch(filters, () => {
  if (isUpdatingFilters.value) return // Prevent recursive calls
  
  clearTimeout(debounceTimeout)
  debounceTimeout = setTimeout(() => {
    currentPage.value = 1 // Reset to first page when filters change
    fetchData()
  }, 300) // 300ms debounce for all filter changes
}, { deep: true })

watch(brandSlug, () => {
  currentPage.value = 1 // Reset to first page when brand changes
  fetchData()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Page Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <h1 class="text-3xl font-bold">{{ pageTitle }}</h1>
          
          <!-- Filter Toggle Button (Mobile) -->
          <button
            @click="toggleFilters"
            class="lg:hidden flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
            </svg>
            <span>Filters</span>
            <span v-if="hasActiveFilters" class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full">!</span>
          </button>
        </div>
        
        <!-- Brand Filter Info -->
        <div v-if="currentBrand" class="mt-2 flex items-center space-x-2">
          <span class="text-sm text-gray-600">Filtering by brand:</span>
          <div class="flex items-center space-x-2 bg-gray-100 rounded-full px-3 py-1">
            <img 
              v-if="currentBrand.logo_url"
              :src="getImageUrl(currentBrand.logo_url, 'brand')"
              :alt="currentBrand.name"
              class="w-4 h-4 object-contain"
            />
            <span class="text-sm font-medium text-gray-900">{{ currentBrand.name }}</span>
            <NuxtLink 
              to="/shop" 
              class="text-gray-400 hover:text-gray-600 transition-colors"
              title="Clear brand filter"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </NuxtLink>
          </div>
        </div>
        
        <!-- Active Filters -->
        <div v-if="hasActiveFilters && !currentBrand" class="mt-2 flex flex-wrap items-center gap-2">
          <span class="text-sm text-gray-600">Active filters:</span>
          <div class="flex flex-wrap gap-2">
            <span
              v-if="filters.search"
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
              Search: "{{ filters.search }}"
              <button @click="filters.search = ''" class="ml-1 text-blue-600 hover:text-blue-800">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </span>
            <span
              v-if="filters.is_featured"
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
            >
              Featured
              <button @click="filters.is_featured = false" class="ml-1 text-green-600 hover:text-green-800">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </span>
            <span
              v-if="filters.is_hot"
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
            >
              Hot
              <button @click="filters.is_hot = false" class="ml-1 text-red-600 hover:text-red-800">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </span>
            <span
              v-if="filters.is_new"
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
            >
              New
              <button @click="filters.is_new = false" class="ml-1 text-purple-600 hover:text-purple-800">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </span>
            <span
              v-if="filters.min_price || filters.max_price"
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
            >
              Price: ₱{{ filters.min_price || 0 }} - ₱{{ filters.max_price || '∞' }}
              <button @click="filters.min_price = null; filters.max_price = null" class="ml-1 text-yellow-600 hover:text-yellow-800">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </span>
            <button
              v-if="hasActiveFilters"
              @click="clearFilters"
              class="text-xs text-gray-500 hover:text-gray-700 underline"
            >
              Clear all
            </button>
          </div>
        </div>
        
        <!-- Results Count -->
        <p v-if="!isLoading" class="text-sm text-gray-500 mt-1">
          {{ pagination.total }} product{{ pagination.total !== 1 ? 's' : '' }} found
        </p>
      </div>

      <!-- Main Content -->
      <div class="flex flex-col lg:flex-row gap-6">
        <!-- Filters Sidebar -->
        <div class="lg:w-80 flex-shrink-0">
          <!-- Desktop Filters -->
          <div class="hidden lg:block">
            <ProductFilters v-model="filters" @update:modelValue="updateFilters" />
          </div>
          
          <!-- Mobile Filters (Collapsible) -->
          <div v-if="showFilters" class="lg:hidden mb-4">
            <ProductFilters v-model="filters" @update:modelValue="updateFilters" />
          </div>
        </div>

        <!-- Products Grid -->
        <div class="flex-1">
          <!-- Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Skeleton Loader -->
        <div
          v-if="isLoading"
          v-for="n in perPage"
          :key="n"
          class="bg-white rounded-lg shadow-sm animate-pulse overflow-hidden"
        >
          <div class="w-full h-48 bg-gray-200"></div>
          <div class="p-4 space-y-2">
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-5 bg-gray-200 rounded w-1/2"></div>
          </div>
        </div>

        <!-- Products -->
        <NuxtLink
          v-else
          v-for="product in products"
          :key="product.id"
          :to="`/shop/${product.subcategory?.category?.slug}/${product.subcategory?.slug}/${product.slug}`"
          class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 block group overflow-hidden"
        >
          <!-- Product image -->
          <div class="relative overflow-hidden">
            <img
              :src="getImageUrl(product.images?.find(img => img.is_primary)?.url || null, 'product')"
              :alt="product.name"
              class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-200"
            />
            <!-- Overlay on hover -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-200"></div>
          </div>

          <!-- Product info -->
          <div class="p-4">
            <h2 class="text-sm font-medium text-gray-900 truncate mb-2">
              {{ product.name }}
            </h2>
            <p class="text-primary-600 font-semibold text-lg">
              ₱{{ product.variants?.[0]?.price?.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </p>
          </div>
        </NuxtLink>
      </div>

          <!-- Empty state (only when not loading) -->
          <div v-if="!isLoading && products.length === 0" class="text-center py-12">
            <div class="max-w-md mx-auto">
              <svg class="mx-auto h-24 w-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
              <h3 class="mt-4 text-lg font-medium text-gray-900">
                {{ currentBrand ? `No ${currentBrand.name} products found` : 'No products available' }}
              </h3>
              <p class="mt-2 text-gray-500">
                {{ currentBrand ? `We couldn't find any products from ${currentBrand.name}. Try browsing other brands or categories.` : 'Check back later for new products.' }}
              </p>
              <div class="mt-6">
                <NuxtLink 
                  v-if="currentBrand"
                  to="/shop" 
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500"
                >
                  View All Products
                </NuxtLink>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <Pagination
            v-if="!isLoading && pagination.total_pages > 1"
            :current-page="pagination.current_page"
            :total-pages="pagination.total_pages"
            @page-change="handlePageChange"
          />
        </div>
      </div>
    </div>
  </div>
</template>
