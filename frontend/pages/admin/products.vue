<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Products Management - Admin - Rapollo E-commerce',
  meta: [
    { name: 'description', content: 'Manage products in your Rapollo E-commerce store.' }
  ]
})

import { onMounted, computed } from 'vue'
import DataTable from '@/components/DataTable.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import AdminAddButton from '@/components/AdminAddButton.vue'
import { useProductStore } from '@/stores/product'
import { useAlert } from '@/composables/useAlert'
import { getImageUrl } from '@/utils/imageHelper'

// ---------------------
// Pinia store
// ---------------------
const productStore = useProductStore()
const { success, error, info } = useAlert()

// ---------------------
// Columns
// ---------------------
const columns = [
  { label: 'Product Image', key: 'image' }, // new first column
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
    error('Failed to Load Products', 'Unable to fetch products. Please try again.')
  }
})

const selectedIds = ref<number[]>([])

// Methods
const editProduct = (slug: string) => {
  // Navigate to edit page
  navigateTo(`/admin/edit-product/${slug}`)
}

const deleteProduct = async (slug: string, name: string) => {
  if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
    try {
      await productStore.deleteProduct(slug)
      success('Product Deleted', `"${name}" has been deleted successfully`)
    } catch (err) {
      console.error('Failed to delete product:', err)
      error('Delete Failed', 'Failed to delete product. Please try again.')
    }
  }
}

</script>

<template>
  <div class="p-4">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Products</h1>
      <AdminAddButton text="Add Product" @click="navigateTo('/admin/add-product')" />
    </div>

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
        <div class="flex gap-2 justify-center">
          <AdminActionButton
            icon="mdi:pencil"
            text="Edit"
            variant="primary"
            @click="editProduct(row.slug)"
          />
          <AdminActionButton
            icon="mdi:delete"
            text="Delete"
            variant="danger"
            @click="deleteProduct(row.slug, row.name)"
          />
        </div>
      </template>
    </DataTable>
  </div>
</template>
