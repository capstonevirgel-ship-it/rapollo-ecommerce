<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Products Management - Admin | monogram',
  meta: [
    { name: 'description', content: 'Manage products in your monogram E-commerce store.' }
  ]
})

import { onMounted, computed, ref, watch } from 'vue'
import DataTable from '@/components/DataTable.vue'
import Dialog from '@/components/Dialog.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import AdminAddButton from '@/components/AdminAddButton.vue'
import ActiveInactiveToggle from '@/components/ActiveInactiveToggle.vue'
import Select from '@/components/Select.vue'
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
  { label: '', key: 'image', width: 10 }, // new first column
  { label: 'Name', key: 'name', width: 20 },
  { label: 'Price', key: 'price', width: 10 },
  { label: 'Status', key: 'status', width: 10 },
  { label: 'Category', key: 'category', width: 15 },
  { label: 'Subcategory', key: 'subcategory', width: 15 },
  { label: 'Actions', key: 'actions', width: 20 }
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
      price: p.price ? `₱${Number(p.price).toLocaleString()}` : '₱0',
      status: p.is_active ? 'active' : 'inactive',
      category: p.subcategory?.category?.name || 'N/A',
      subcategory: p.subcategory?.name || 'N/A',
      is_active: Boolean(p.is_active),
      image: getImageUrl(primaryProductImage || primaryVariantImage || null),
      slug: p.slug
    }
  })
)

const selectedIds = ref<number[]>([])
const showDeleteDialog = ref(false)
const showSingleDeleteDialog = ref(false)
const deletingProduct = ref<{ slug: string; name: string } | null>(null)
const isDeleting = ref(false)
const togglingProducts = ref<Set<string>>(new Set())
const statusFilter = ref<string | null>(null)
const isUpdatingBulkStatus = ref(false)

// Status filter options
const statusFilterOptions = [
  { value: 'all', label: 'All' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
]

// Computed
const selectedCount = computed(() => selectedIds.value.length)
const hasSelected = computed(() => selectedIds.value.length > 0)

// Get selected products with their status
const selectedProducts = computed(() => {
  return selectedIds.value
    .map(id => productStore.products.find(p => p.id === id))
    .filter(Boolean) as Array<{ id: number; is_active: number; slug: string; name: string }>
})

// Check if selected products have mixed states
const hasMixedStates = computed(() => {
  if (selectedProducts.value.length === 0) return false
  const activeCount = selectedProducts.value.filter(p => p.is_active).length
  return activeCount > 0 && activeCount < selectedProducts.value.length
})

// Check if all selected are active
const allSelectedActive = computed(() => {
  if (selectedProducts.value.length === 0) return false
  return selectedProducts.value.every(p => p.is_active)
})

// Check if all selected are inactive
const allSelectedInactive = computed(() => {
  if (selectedProducts.value.length === 0) return false
  return selectedProducts.value.every(p => !p.is_active)
})

// Note: Products are filtered on the backend via fetchProducts

// ---------------------
// Fetch products on mount and when filter changes
// ---------------------
const fetchProducts = async () => {
  try {
    const params: any = { per_page: 20, all: true }
    if (statusFilter.value && statusFilter.value !== 'all') {
      params.is_active = statusFilter.value === 'active'
    }
    await (productStore as any).fetchProducts(params)
  } catch (err) {
    console.error('Failed to fetch products', err)
    error('Failed to Load Products', 'Unable to fetch products. Please try again.')
  }
}

onMounted(async () => {
  await fetchProducts()
})

// Watch status filter and refetch
watch(statusFilter, () => {
  fetchProducts()
})

// Methods
const editProduct = (slug: string) => {
  // Navigate to edit page
  navigateTo(`/admin/edit-product/${slug}`)
}

const openSingleDeleteDialog = (product: { slug: string; name: string }) => {
  deletingProduct.value = product
  showSingleDeleteDialog.value = true
}

const closeSingleDeleteDialog = () => {
  showSingleDeleteDialog.value = false
  deletingProduct.value = null
}

const deleteProduct = async () => {
  if (!deletingProduct.value) return

  isDeleting.value = true
  const productToDelete = { ...deletingProduct.value }

  try {
    await (productStore as any).deleteProduct(productToDelete.slug)
    success('Product Deleted', `"${productToDelete.name}" has been deleted successfully`)
    await fetchProducts()
  } catch (err: any) {
    console.error('Failed to delete product:', err)
    const errorMessage = err.data?.message || err.message || 'Failed to delete product. Please try again.'
    error('Delete Failed', errorMessage)
  } finally {
    isDeleting.value = false
    closeSingleDeleteDialog()
  }
}

const openDeleteDialog = () => {
  if (!hasSelected.value) {
    info('Nothing Selected', 'Please select products to delete.')
    return
  }
  showDeleteDialog.value = true
}

const closeDeleteDialog = () => {
  showDeleteDialog.value = false
}

const deleteSelectedProducts = async () => {
  if (!hasSelected.value) return

  isDeleting.value = true
  const idsToDelete = [...selectedIds.value]
  const productsToDelete = idsToDelete
    .map(id => {
      const product = productStore.products.find(p => p.id === id)
      return product ? { id, slug: product.slug, name: product.name } : null
    })
    .filter(Boolean) as Array<{ id: number; slug: string; name: string }>

  let successCount = 0
  let failCount = 0
  const failedProducts: string[] = []

  try {
    for (const product of productsToDelete) {
      try {
        await (productStore as any).deleteProduct(product.slug)
        successCount++
      } catch (err) {
        console.error(`Failed to delete product ${product.name}:`, err)
        failCount++
        failedProducts.push(product.name)
      }
    }

    // Clear selection after deletion
    selectedIds.value = []

    // Show results
    if (successCount > 0 && failCount === 0) {
      success(
        'Products Deleted',
        `${successCount} product${successCount > 1 ? 's' : ''} ${successCount > 1 ? 'have' : 'has'} been deleted successfully.`
      )
    } else if (successCount > 0 && failCount > 0) {
      error(
        'Partial Deletion',
        `${successCount} product${successCount > 1 ? 's' : ''} deleted, but ${failCount} failed: ${failedProducts.join(', ')}`
      )
    } else {
      error('Delete Failed', 'Failed to delete selected products. Please try again.')
    }

  } catch (err) {
    console.error('Bulk delete error:', err)
    error('Delete Failed', 'An error occurred while deleting products. Please try again.')
  } finally {
    isDeleting.value = false
    closeDeleteDialog()
  }
}

const toggleProductActive = async (slug: string, currentStatus: boolean) => {
  const newStatus = !currentStatus
  togglingProducts.value.add(slug)

  // Find product for optimistic update
  const product = productStore.products.find(p => p.slug === slug)

  try {
    // Optimistic update
    if (product) {
      product.is_active = newStatus ? 1 : 0
    }

    // Call API
    await (productStore as any).toggleProductActive(slug, newStatus)
    
    success(
      'Status Updated',
      `Product has been ${newStatus ? 'activated' : 'deactivated'} successfully.`
    )
  } catch (err) {
    console.error('Failed to toggle product status:', err)
    
    // Revert optimistic update
    if (product) {
      product.is_active = currentStatus ? 1 : 0
    }
    
    error(
      'Update Failed',
      `Failed to ${newStatus ? 'activate' : 'deactivate'} product. Please try again.`
    )
  } finally {
    togglingProducts.value.delete(slug)
  }
}

const bulkSetActive = async () => {
  if (!hasSelected.value || hasMixedStates.value) {
    info('Invalid Selection', 'Cannot set status: Selected products have mixed active/inactive states. Please select products with the same status.')
    return
  }

  if (allSelectedActive.value) {
    info('Already Active', 'All selected products are already active.')
    return
  }

  isUpdatingBulkStatus.value = true
  const productIds = selectedIds.value

  try {
    // Optimistic update
    productIds.forEach(id => {
      const product = productStore.products.find(p => p.id === id)
      if (product) {
        product.is_active = 1
      }
    })

    await (productStore as any).bulkUpdateActiveStatus(productIds, true)
    
    success(
      'Status Updated',
      `${productIds.length} product${productIds.length > 1 ? 's have' : ' has'} been activated successfully.`
    )
    
    // Clear selection
    selectedIds.value = []
  } catch (err) {
    console.error('Failed to bulk activate products:', err)
    
    // Revert optimistic update
    productIds.forEach(id => {
      const product = productStore.products.find(p => p.id === id)
      if (product) {
        product.is_active = 0
      }
    })
    
    error('Update Failed', 'Failed to activate selected products. Please try again.')
  } finally {
    isUpdatingBulkStatus.value = false
  }
}

const bulkSetInactive = async () => {
  if (!hasSelected.value || hasMixedStates.value) {
    info('Invalid Selection', 'Cannot set status: Selected products have mixed active/inactive states. Please select products with the same status.')
    return
  }

  if (allSelectedInactive.value) {
    info('Already Inactive', 'All selected products are already inactive.')
    return
  }

  isUpdatingBulkStatus.value = true
  const productIds = selectedIds.value

  try {
    // Optimistic update
    productIds.forEach(id => {
      const product = productStore.products.find(p => p.id === id)
      if (product) {
        product.is_active = 0
      }
    })

    await (productStore as any).bulkUpdateActiveStatus(productIds, false)
    
    success(
      'Status Updated',
      `${productIds.length} product${productIds.length > 1 ? 's have' : ' has'} been deactivated successfully.`
    )
    
    // Clear selection
    selectedIds.value = []
  } catch (err) {
    console.error('Failed to bulk deactivate products:', err)
    
    // Revert optimistic update
    productIds.forEach(id => {
      const product = productStore.products.find(p => p.id === id)
      if (product) {
        product.is_active = 1
      }
    })
    
    error('Update Failed', 'Failed to deactivate selected products. Please try again.')
  } finally {
    isUpdatingBulkStatus.value = false
  }
}

</script>

<template>
  <div class="space-y-8 sm:space-y-10">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Products</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Manage all products in your store</p>
      </div>
      <div class="flex gap-3">
        <AdminAddButton text="Add Product" @click="navigateTo('/admin/add-product')" />
      </div>
    </div>

    <!-- Filter and Bulk Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <!-- Status Filter -->
      <div class="flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700">Filter by Status:</label>
        <Select
          v-model="statusFilter"
          :options="statusFilterOptions"
          placeholder="All Products"
          size="sm"
          class="w-40"
        />
      </div>

      <!-- Bulk Actions -->
      <div v-if="hasSelected" class="flex flex-wrap gap-2">
        <button
          @click="bulkSetActive"
          :disabled="isUpdatingBulkStatus || hasMixedStates || allSelectedActive"
          class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          :title="hasMixedStates ? 'Cannot set status: Selected products have mixed states' : allSelectedActive ? 'All selected products are already active' : 'Set selected products as active'"
        >
          <Icon name="mdi:check-circle" class="w-5 h-5" />
          Set Active ({{ selectedCount }})
        </button>
        <button
          @click="bulkSetInactive"
          :disabled="isUpdatingBulkStatus || hasMixedStates || allSelectedInactive"
          class="px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          :title="hasMixedStates ? 'Cannot set status: Selected products have mixed states' : allSelectedInactive ? 'All selected products are already inactive' : 'Set selected products as inactive'"
        >
          <Icon name="mdi:cancel" class="w-5 h-5" />
          Set Inactive ({{ selectedCount }})
        </button>
        <button
          @click="openDeleteDialog"
          :disabled="isUpdatingBulkStatus"
          class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Icon name="mdi:delete" class="w-5 h-5" />
          Delete ({{ selectedCount }})
        </button>
      </div>
    </div>

    <!-- Mixed States Warning -->
    <div v-if="hasSelected && hasMixedStates" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start gap-3">
      <Icon name="mdi:alert" class="text-yellow-600 text-xl flex-shrink-0 mt-0.5" />
      <div>
        <h4 class="text-sm font-medium text-yellow-800">Mixed Selection</h4>
        <p class="text-sm text-yellow-700 mt-1">
          You have selected products with different statuses (some active, some inactive). 
          Please select products with the same status to change them all at once.
        </p>
      </div>
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
        <div class="flex items-center justify-center">
          <ActiveInactiveToggle
            :model-value="row.is_active"
            :disabled="togglingProducts.has(row.slug)"
            @update:model-value="toggleProductActive(row.slug, row.is_active)"
          />
        </div>
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
            @click="openSingleDeleteDialog({ slug: row.slug, name: row.name })"
          />
        </div>
      </template>
    </DataTable>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model="showDeleteDialog" title="Delete Selected Products">
      <div class="flex flex-col items-center text-center">
        <!-- Warning Icon -->
        <div class="mb-4 flex items-center justify-center w-20 h-20 rounded-full bg-yellow-100">
          <Icon name="mdi:alert" class="text-[3rem] text-yellow-600" />
        </div>

        <!-- Confirmation Message -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          Are you sure you want to delete {{ selectedCount }} product{{ selectedCount > 1 ? 's' : '' }}?
        </h3>
        <p class="text-sm text-gray-600 mb-6">
          This action cannot be undone. All selected products and their associated data will be permanently deleted.
        </p>

        <!-- Action Buttons -->
        <div class="flex gap-3 w-full">
          <button
            @click="closeDeleteDialog"
            :disabled="isDeleting"
            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Cancel
          </button>
          <button
            @click="deleteSelectedProducts"
            :disabled="isDeleting"
            class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
          >
            <span v-if="isDeleting">Deleting...</span>
            <span v-else>Delete</span>
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Single Product Delete Confirmation Dialog -->
    <Dialog v-model="showSingleDeleteDialog" title="Delete Product">
      <div class="flex flex-col items-center text-center">
        <!-- Warning Icon -->
        <div class="mb-4 flex items-center justify-center w-20 h-20 rounded-full bg-yellow-100">
          <Icon name="mdi:alert" class="text-[3rem] text-yellow-600" />
        </div>

        <!-- Confirmation Message -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          Are you sure you want to delete "{{ deletingProduct?.name }}"?
        </h3>
        <p class="text-sm text-gray-600 mb-6">
          This action cannot be undone. The product and all its associated data will be permanently deleted.
        </p>

        <!-- Action Buttons -->
        <div class="flex gap-3 w-full">
          <button
            @click="closeSingleDeleteDialog"
            :disabled="isDeleting"
            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Cancel
          </button>
          <button
            @click="deleteProduct"
            :disabled="isDeleting"
            class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
          >
            <span v-if="isDeleting">Deleting...</span>
            <span v-else>Delete</span>
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>
