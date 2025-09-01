<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

import { onMounted, computed } from 'vue'
import DataTable from '@/components/DataTable.vue'
import { useProductStore } from '@/stores/product'
import { getImageUrl } from '@/utils/imageHelper'

// ---------------------
// Pinia store
// ---------------------
const productStore = useProductStore()

// ---------------------
// Columns
// ---------------------
const columns = [
  { label: 'Product Image', key: 'image' }, // new first column
  { label: 'ID', key: 'id' },
  { label: 'Name', key: 'name' },
  { label: 'Price', key: 'price' },
  { label: 'Category', key: 'category' },
  { label: 'Subcategory', key: 'subcategory' },
  { label: 'Status', key: 'status' },
  { label: 'Actions', key: 'actions' }
]

// ---------------------
// Computed rows
// Map products from store to DataTable format
// ---------------------

const products = computed(() =>
  productStore.products.map(p => {
    const firstVariant = p.variants?.[0]; // first variant
    const primaryProductImage = p.images?.find(img => img.is_primary)?.url;
    const primaryVariantImage = firstVariant?.images?.find(img => img.is_primary)?.url;

    return {
      id: p.id,
      name: p.name,
      price: firstVariant?.price ? `₱${Number(firstVariant.price).toLocaleString()}` : '₱0',
      category: p.subcategory?.category?.name || 'N/A',
      subcategory: p.subcategory?.name || 'N/A',
      status: p.is_active ? 'active' : 'inactive',
      image: getImageUrl(primaryProductImage || primaryVariantImage || null),
      slug: p.slug
    }
  })
)

// ---------------------
// Fetch products on mount
// ---------------------
onMounted(async () => {
  try {
    await productStore.fetchProducts({ per_page: 20 })
  } catch (err) {
    console.error('Failed to fetch products', err)
  }
})

const selectedIds = ref<number[]>([])

</script>

<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-4">Products</h1>

    <DataTable
      :columns="columns"
      :rows="products"
      v-model:selected="selectedIds"
      :rows-per-page="5"
    >
      <!-- Slot for Product Image -->
      <template #cell-image="{ row }">
        <img
          :src="row.image"
          alt="Product Image"
          class="w-12 h-12 object-cover rounded"
        />
      </template>

      <!-- Slot for status -->
      <template #cell-status="{ row }">
        <span
          :class="[
            'text-xs font-semibold px-2 py-1 rounded uppercase',
            row.status === 'active'
              ? 'bg-green-100 text-green-700'
              : 'bg-red-100 text-red-700'
          ]"
        >
          {{ row.status }}
        </span>
      </template>

      <!-- Slot for action buttons -->
      <template #cell-actions="{ row }">
        <div class="flex gap-2">
          <button class="text-blue-600 hover:text-blue-800" @click="console.log('Edit', row.slug)">
            <Icon name="mdi:pencil" />
          </button>
          <button class="text-red-600 hover:text-red-800" @click="console.log('Delete', row.slug)">
            <Icon name="mdi:delete" />
          </button>
        </div>
      </template>
    </DataTable>
  </div>
</template>
