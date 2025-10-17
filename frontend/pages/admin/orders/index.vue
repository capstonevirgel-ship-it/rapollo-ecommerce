<script setup lang="ts">
import { onMounted, computed, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { usePurchaseStore } from '~/stores/purchase'
import { useAlert } from '~/composables/useAlert'
import Select from '@/components/Select.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import { useRouter } from 'vue-router'

definePageMeta({
  layout: 'admin'
})

// Stores
const purchaseStore = usePurchaseStore()
const { success, error } = useAlert()
const { purchases, pagination, loading, error: storeError } = storeToRefs(purchaseStore)
const router = useRouter()

// State
const selectedStatus = ref('all')
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(20)

// Options
const statusOptions = [
  { value: 'all', label: 'All Orders' },
  { value: 'pending', label: 'Pending' },
  { value: 'shipped', label: 'Shipped' },
  { value: 'delivered', label: 'Delivered' }
]

const statusUpdateOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'shipped', label: 'Shipped' },
  { value: 'delivered', label: 'Delivered' }
]

// Computed
const orders = computed(() => purchases.value)

const filteredOrders = computed(() => {
  if (selectedStatus.value === 'all') {
    return orders.value
  }
  return orders.value.filter(order => {
    const statusMatch = selectedStatus.value === 'all' || order.status === selectedStatus.value
    return statusMatch
  })
})

// Methods
const getStatusColor = (status: string) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getOrderId = (purchase: any) => {
  return `#${purchase.id.toString().padStart(4, '0')}`
}

const getCustomerName = (purchase: any) => {
  return purchase.user?.profile?.full_name || purchase.user?.user_name || 'Unknown Customer'
}

const getCustomerEmail = (purchase: any) => {
  return purchase.user?.email || 'No email'
}

// Actions
const viewOrder = (purchase: any) => {
  router.push(`/admin/orders/${purchase.id}`)
}

const downloadInvoice = (purchase: any) => {
  // Download invoice
  console.log('Download invoice for order:', purchase.id)
}

const updateOrderStatus = async (order: any, newStatus: string | number | null) => {
  if (!newStatus) return
  try {
    // Update the order status in the backend
    await $fetch(`/api/purchases/${order.id}/status`, {
      method: 'PUT',
      body: { status: newStatus }
    })
    
    // Update the local state
    order.status = newStatus
    
    success('Status Updated', `Order ${getOrderId(order)} status updated to ${newStatus}`)
  } catch (err) {
    console.error('Failed to update order status:', err)
    error('Update Failed', 'Failed to update order status. Please try again.')
  }
}

// Fetch data
const fetchOrders = async () => {
  try {
    await purchaseStore.fetchAdminPurchases({
      status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
      type: 'product', // Only fetch product orders
      search: searchQuery.value || undefined,
      per_page: perPage.value
    })
  } catch (err) {
    console.error('Failed to fetch orders:', err)
    error('Failed to Load Orders', 'Unable to fetch orders. Please try again.')
  }
}

// Search with debounce
let searchTimeout: NodeJS.Timeout
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchOrders()
  }, 500)
}

// Watchers
watch([selectedStatus], () => {
  fetchOrders()
})

// Lifecycle
onMounted(() => {
  fetchOrders()
})
</script>

<template>
  <ClientOnly>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Orders</h1>
        <p class="text-gray-600 mt-1">Manage and track all customer orders</p>
      </div>
      <div class="flex items-center space-x-3">
        <button class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Export
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Total Orders</p>
            <p class="text-2xl font-bold text-gray-900">{{ orders.length }}</p>
          </div>
          <div class="p-3 bg-zinc-100 rounded-lg">
            <svg class="w-5 h-5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Completed</p>
            <p class="text-2xl font-bold text-green-600">{{ orders.filter(o => o.status === 'completed').length }}</p>
          </div>
          <div class="p-3 bg-green-100 rounded-lg">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Pending</p>
            <p class="text-2xl font-bold text-yellow-600">{{ orders.filter(o => o.status === 'pending').length }}</p>
          </div>
          <div class="p-3 bg-yellow-100 rounded-lg">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(orders.reduce((sum, order) => sum + order.total, 0)) }}</p>
          </div>
          <div class="p-3 bg-zinc-100 rounded-lg">
            <svg class="w-5 h-5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
            <Select 
              v-model="selectedStatus" 
              :options="statusOptions"
              placeholder="All Orders"
            />
          </div>
          <div class="w-64">
            <label class="block text-sm font-medium text-gray-700 mb-2">Search Customer</label>
            <input 
              v-model="searchQuery" 
              @input="handleSearch"
              type="text" 
              placeholder="Search by name or email..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-500"
            />
          </div>
        </div>
        <div class="text-sm text-gray-500">
          Showing {{ orders.length }} orders (Total: {{ pagination?.total || 0 }})
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-zinc-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="storeError" class="bg-red-50 border border-red-200 rounded-md p-4">
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error</h3>
          <div class="mt-2 text-sm text-red-700">
            {{ storeError }}
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="orders.length === 0" class="text-center py-12">
      <div class="mx-auto h-24 w-24 text-gray-400">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No orders found</h3>
      <p class="mt-1 text-sm text-gray-500">No orders match your current filters.</p>
    </div>

    <!-- Orders Table -->
    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto custom-scrollbar">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ getOrderId(order) }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ getCustomerName(order) }}</div>
                  <div class="text-sm text-gray-500">{{ getCustomerEmail(order) }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ formatCurrency(order.total) }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="w-32">
                  <Select
                    :model-value="order.status"
                    :options="statusUpdateOptions"
                    @update:model-value="updateOrderStatus(order, $event)"
                  >
                    <!-- Custom selected value with badge -->
                    <template #selected="{ option, placeholder }">
                      <span v-if="option" :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusColor(String(option.value))]">
                        {{ option.label }}
                      </span>
                      <span v-else class="text-gray-500">{{ placeholder }}</span>
                    </template>
                    
                    <!-- Custom option with badge -->
                    <template #option="{ option, selected }">
                      <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusColor(String(option.value))]">
                        {{ option.label }}
                </span>
                    </template>
                  </Select>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(order.created_at) }}
              </td>
                     <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                       <div class="flex items-center space-x-2">
                         <AdminActionButton
                           icon="mdi:eye"
                           text="View"
                           variant="primary"
                           size="sm"
                           @click="viewOrder(order)"
                         />
                         <AdminActionButton
                           icon="mdi:download"
                           text="Invoice"
                           variant="success"
                           size="sm"
                           @click="downloadInvoice(order)"
                         />
                       </div>
                     </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <template #fallback>
    <div class="p-6 space-y-6">
      <div class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-zinc-600"></div>
        <p class="ml-4 text-gray-600">Loading orders...</p>
      </div>
    </div>
  </template>
  </ClientOnly>
</template>
