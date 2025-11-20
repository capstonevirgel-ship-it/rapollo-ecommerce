<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Product Labels Management - Admin | RAPOLLO',
  meta: [
    { name: 'description', content: 'Manage product labels (Featured, Hot, New) in your Rapollo E-commerce store.' }
  ]
})

import { onMounted, computed, ref } from 'vue'
import DataTable from '@/components/DataTable.vue'
import { useProductStore } from '@/stores/product'
import { useAlert } from '@/composables/useAlert'
import { getImageUrl } from '@/utils/imageHelper'

// ---------------------
// Pinia store
// ---------------------
const productStore = useProductStore()
const { success, error } = useAlert()

// ---------------------
// State
// ---------------------
const isLoading = ref(false)
const isSaving = ref(false)
const localProductLabels = ref<Record<number, { is_featured: boolean; is_hot: boolean; is_new: boolean }>>({})

// ---------------------
// Columns
// ---------------------
const columns = [
  { label: 'Product Image', key: 'image', width: 10 },
  { label: 'Name', key: 'name', width: 25 },
  { label: 'Price', key: 'price', width: 10 },
  { label: 'Category', key: 'category', width: 15 },
  { label: 'Featured', key: 'is_featured', width: 10 },
  { label: 'Hot', key: 'is_hot', width: 10 },
  { label: 'New', key: 'is_new', width: 10 }
]

// ---------------------
// Computed rows
// ---------------------
const products = computed(() =>
  productStore.products.map(p => {
    const firstVariant = p.variants?.[0]
    const primaryProductImage = p.images?.find(img => img.is_primary)?.url
    const primaryVariantImage = firstVariant?.images?.find(img => img.is_primary)?.url

    // Initialize local state if not exists
    if (!localProductLabels.value[p.id]) {
      localProductLabels.value[p.id] = {
        is_featured: Boolean(p.is_featured),
        is_hot: Boolean(p.is_hot),
        is_new: Boolean(p.is_new)
      }
    }

    return {
      id: p.id,
      name: p.name,
      price: p.price ? `₱${Number(p.price).toLocaleString()}` : '₱0',
      category: p.subcategory?.category?.name || 'N/A',
      image: getImageUrl(primaryProductImage || primaryVariantImage || null),
      is_featured: localProductLabels.value[p.id]?.is_featured ?? Boolean(p.is_featured),
      is_hot: localProductLabels.value[p.id]?.is_hot ?? Boolean(p.is_hot),
      is_new: localProductLabels.value[p.id]?.is_new ?? Boolean(p.is_new)
    }
  })
)

// ---------------------
// Check if there are unsaved changes
// ---------------------
const hasUnsavedChanges = computed(() => {
  return productStore.products.some(p => {
    const local = localProductLabels.value[p.id]
    if (!local) return false
    return (
      local.is_featured !== Boolean(p.is_featured) ||
      local.is_hot !== Boolean(p.is_hot) ||
      local.is_new !== Boolean(p.is_new)
    )
  })
})

// ---------------------
// Methods
// ---------------------
const toggleLabel = (productId: number, label: 'is_featured' | 'is_hot' | 'is_new') => {
  if (!localProductLabels.value[productId]) {
    const product = productStore.products.find(p => p.id === productId)
    if (product) {
      localProductLabels.value[productId] = {
        is_featured: Boolean(product.is_featured),
        is_hot: Boolean(product.is_hot),
        is_new: Boolean(product.is_new)
      }
    }
  }
  if (localProductLabels.value[productId]) {
    localProductLabels.value[productId][label] = !localProductLabels.value[productId][label]
  }
}

const saveChanges = async () => {
  if (!hasUnsavedChanges.value) {
    return
  }

  isSaving.value = true
  try {
    // Build array of products with changes
    const productsToUpdate = productStore.products
      .filter(p => {
        const local = localProductLabels.value[p.id]
        if (!local) return false
        return (
          local.is_featured !== Boolean(p.is_featured) ||
          local.is_hot !== Boolean(p.is_hot) ||
          local.is_new !== Boolean(p.is_new)
        )
      })
      .map(p => {
        const local = localProductLabels.value[p.id]
        return {
          id: p.id,
          is_featured: local.is_featured,
          is_hot: local.is_hot,
          is_new: local.is_new
        }
      })

    await productStore.bulkUpdateLabels(productsToUpdate)
    success('Labels Updated', `Successfully updated labels for ${productsToUpdate.length} product(s)`)
  } catch (err: any) {
    console.error('Failed to update labels:', err)
    error('Update Failed', err.message || 'Failed to update product labels. Please try again.')
  } finally {
    isSaving.value = false
  }
}

// ---------------------
// Fetch products on mount
// ---------------------
onMounted(async () => {
  isLoading.value = true
  try {
    await productStore.fetchProducts({ per_page: 100 })
  } catch (err) {
    console.error('Failed to fetch products', err)
    error('Failed to Load Products', 'Unable to fetch products. Please try again.')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-8 sm:space-y-10">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Product Labels</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">
          Manage which products are labeled as Featured, Hot, and New
        </p>
      </div>
      <button
        v-if="hasUnsavedChanges"
        @click="saveChanges"
        :disabled="isSaving"
        class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        <svg
          v-if="isSaving"
          class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        {{ isSaving ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <div v-if="isLoading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
      <p class="mt-2 text-sm text-gray-600">Loading products...</p>
    </div>

    <DataTable
      v-else
      :columns="columns"
      :rows="products"
      :rows-per-page="10"
      :show-checkboxes="false"
    >
      <!-- Slot for Product Image -->
      <template #cell-image="{ row }">
        <img
          :src="row.image"
          alt="Product Image"
          class="w-12 h-12 object-cover rounded"
        />
      </template>

      <!-- Slot for Featured toggle -->
      <template #cell-is_featured="{ row }">
        <label class="relative inline-flex items-center cursor-pointer">
          <input
            type="checkbox"
            :checked="row.is_featured"
            @change="toggleLabel(row.id, 'is_featured')"
            class="sr-only peer"
          />
          <div
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
          ></div>
        </label>
      </template>

      <!-- Slot for Hot toggle -->
      <template #cell-is_hot="{ row }">
        <label class="relative inline-flex items-center cursor-pointer">
          <input
            type="checkbox"
            :checked="row.is_hot"
            @change="toggleLabel(row.id, 'is_hot')"
            class="sr-only peer"
          />
          <div
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"
          ></div>
        </label>
      </template>

      <!-- Slot for New toggle -->
      <template #cell-is_new="{ row }">
        <label class="relative inline-flex items-center cursor-pointer">
          <input
            type="checkbox"
            :checked="row.is_new"
            @change="toggleLabel(row.id, 'is_new')"
            class="sr-only peer"
          />
          <div
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"
          ></div>
        </label>
      </template>
    </DataTable>
  </div>
</template>

