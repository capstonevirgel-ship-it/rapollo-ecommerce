<template>
  <div class="bg-white rounded-lg shadow-sm p-6 space-y-6">
    <!-- Search -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Search Products</label>
      <div class="relative">
          <input
            v-model="localFilters.search"
            type="text"
            placeholder="Search by name, brand..."
            class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Special Filters -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-3">Special</label>
      <div class="space-y-2">
        <label class="flex items-center">
          <input
            v-model="localFilters.is_featured"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
          />
          <span class="ml-2 text-sm text-gray-700">Featured</span>
        </label>
        <label class="flex items-center">
          <input
            v-model="localFilters.is_hot"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
          />
          <span class="ml-2 text-sm text-gray-700">Hot Items</span>
        </label>
        <label class="flex items-center">
          <input
            v-model="localFilters.is_new"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
          />
          <span class="ml-2 text-sm text-gray-700">New Arrivals</span>
        </label>
      </div>
    </div>

    <!-- Price Range -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-3">Price Range</label>
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-2">
          <input
            v-model="localFilters.min_price"
            type="number"
            placeholder="Min"
            min="0"
            step="0.01"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
          />
          <input
            v-model="localFilters.max_price"
            type="number"
            placeholder="Max"
            min="0"
            step="0.01"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
          />
        </div>
        <div class="text-xs text-gray-500">
          Range: ₱{{ minPrice || 0 }} - ₱{{ maxPrice || '∞' }}
        </div>
      </div>
    </div>

    <!-- Categories -->
    <div v-if="categories.length > 0">
      <label class="block text-sm font-medium text-gray-700 mb-3">Categories</label>
      <div class="space-y-2 max-h-40 overflow-y-auto">
        <label
          v-for="category in categories"
          :key="category.id"
          class="flex items-center"
        >
          <input
            v-model="localFilters.categories"
            :value="category.slug"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
          />
          <span class="ml-2 text-sm text-gray-700">{{ category.name }}</span>
        </label>
      </div>
    </div>

    <!-- Subcategories -->
    <div v-if="subcategories.length > 0">
      <label class="block text-sm font-medium text-gray-700 mb-3">Subcategories</label>
      <div class="space-y-2 max-h-40 overflow-y-auto">
        <label
          v-for="subcategory in subcategories"
          :key="subcategory.id"
          class="flex items-center"
        >
          <input
            v-model="localFilters.subcategories"
            :value="subcategory.slug"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
          />
          <span class="ml-2 text-sm text-gray-700">{{ subcategory.name }}</span>
        </label>
      </div>
    </div>

    <!-- Brands -->
    <div v-if="brands.length > 0">
      <label class="block text-sm font-medium text-gray-700 mb-3">Brands</label>
      <div class="space-y-2 max-h-40 overflow-y-auto">
        <label
          v-for="brand in brands"
          :key="brand.id"
          class="flex items-center"
        >
          <input
            v-model="localFilters.brands"
            :value="brand.slug"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
          />
          <span class="ml-2 text-sm text-gray-700">{{ brand.name }}</span>
        </label>
      </div>
    </div>

    <!-- Clear Filters -->
    <div class="pt-4 border-t border-gray-200">
      <button
        @click="clearAllFilters"
        class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
      >
        Clear All Filters
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useCategoryStore } from '~/stores/category'
import { useSubcategoryStore } from '~/stores/subcategory'
import { useBrandStore } from '~/stores/brand'
import { getImageUrl } from '~/utils/imageHelper'

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

const props = defineProps<{
  modelValue: ProductFilters
}>()

const emit = defineEmits<{
  'update:modelValue': [value: ProductFilters]
}>()

// Stores
const categoryStore = useCategoryStore()
const subcategoryStore = useSubcategoryStore()
const brandStore = useBrandStore()

// Local filters state
const localFilters = ref<ProductFilters>({ ...props.modelValue })

// Computed properties
const categories = computed(() => categoryStore.categories)
const subcategories = computed(() => subcategoryStore.subcategories)
const brands = computed(() => brandStore.brands)

const minPrice = computed(() => localFilters.value.min_price)
const maxPrice = computed(() => localFilters.value.max_price)

// Watch for all changes and emit immediately
watch(localFilters, (newValue) => {
  // Only emit if the value actually changed
  if (JSON.stringify(props.modelValue) !== JSON.stringify(newValue)) {
    emit('update:modelValue', { ...newValue })
  }
}, { deep: true })

// Clear all filters
const clearAllFilters = () => {
  localFilters.value = {
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
  emit('update:modelValue', { ...localFilters.value })
}

// Fetch data on mount
onMounted(async () => {
  try {
    await Promise.all([
      categoryStore.fetchCategories(),
      subcategoryStore.fetchSubcategories(),
      brandStore.fetchBrands()
    ])
  } catch (error) {
    console.error('Error fetching filter data:', error)
  }
})

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localFilters.value = { ...newValue }
}, { deep: true })
</script>
