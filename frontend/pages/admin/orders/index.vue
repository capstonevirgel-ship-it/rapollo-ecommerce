<script setup lang="ts">
import { onMounted, computed, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useOrderStore } from '~/stores/order'
import { useAlert } from '~/composables/useAlert'
import Select from '@/components/Select.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import DataTable from '@/components/DataTable.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { useRouter } from 'vue-router'

definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Orders Management - Admin | RAPOLLO',
  meta: [
    { name: 'description', content: 'Manage customer orders in your Rapollo E-commerce store.' }
  ]
})

// Stores
const orderStore = useOrderStore()
const { success, error } = useAlert()
const { orders, pagination, loading, error: storeError } = storeToRefs(orderStore)
const router = useRouter()

// State
const selectedStatus = ref('all')
const currentPage = ref(1)
const perPage = ref(20)
const selectedIds = ref<number[]>([])

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

// Columns for DataTable
const columns = [
  { label: 'Order', key: 'order_id', width: 10 },
  { label: 'Customer', key: 'customer', width: 25 },
  { label: 'Amount', key: 'amount', width: 12 },
  { label: 'Status', key: 'status', width: 16.1667 },
  { label: 'Date', key: 'date', width: 16.8333 },
  { label: 'Actions', key: 'actions', width: 20 }
]

// Computed - transform orders for DataTable
const orderRows = computed(() => {
  return orders.value.map(order => ({
    id: order.id,
    order_id: getOrderId(order),
    customer: getCustomerName(order),
    customer_email: getCustomerEmail(order),
    amount: formatCurrency(order.total),
    status: order.status,
    date: formatDate(order.created_at),
    raw_order: order // Keep reference to original order object
  }))
})

const filteredOrders = computed(() => {
  // Filter by status if needed (orders are already filtered to products only by the API)
  if (selectedStatus.value !== 'all') {
    return orderRows.value.filter(row => row.status === selectedStatus.value)
  }
  
  return orderRows.value
})

// Methods
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

const exportOrders = () => {
  // Simple CSV export
  const headers = ['Order ID', 'Customer', 'Email', 'Amount', 'Status', 'Date']
  const csvContent = [
    headers.join(','),
    ...filteredOrders.value.map(order => [
      order.order_id,
      `"${order.customer}"`,
      `"${order.customer_email}"`,
      order.amount.replace(/[₱,]/g, ''), // Remove currency symbols and commas
      order.status,
      order.date
    ].join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'orders-export.csv'
  a.click()
  window.URL.revokeObjectURL(url)
}

const updateOrderStatus = async (order: any, newStatus: string | number | null) => {
  if (!newStatus) return
  try {
    await orderStore.updateOrderStatus(order.id, newStatus as string)
    success('Status Updated', `Order ${getOrderId(order)} status updated to ${newStatus}`)
  } catch (err) {
    console.error('Failed to update order status:', err)
    error('Update Failed', 'Failed to update order status. Please try again.')
  }
}

// Fetch data
const fetchOrders = async () => {
  try {
    await orderStore.fetchOrders({
      status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
      per_page: perPage.value
    })
  } catch (err) {
    console.error('Failed to fetch orders:', err)
    error('Failed to Load Orders', 'Unable to fetch orders. Please try again.')
  }
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
  <div class="space-y-8 sm:space-y-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Orders</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Manage and track all customer orders</p>
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 lg:gap-10">
      <StatCard
        title="Total Orders"
        :value="orders.length"
        icon="mdi:clipboard-list"
      />

      <StatCard
        title="Completed"
        :value="orders.filter(o => o.status === 'completed').length"
        icon="mdi:check-circle"
      />

      <StatCard
        title="Pending"
        :value="orders.filter(o => o.status === 'pending').length"
        icon="mdi:clock-outline"
      />

      <StatCard
        title="Total Revenue"
        :value="formatCurrency(orders.reduce((sum, order) => sum + order.total, 0))"
        icon="mdi:currency-usd"
      />
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-6 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <div class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
            <!-- Status Filter -->
            <div class="w-full sm:w-48">
              <Select
                v-model="selectedStatus"
                :options="statusOptions"
                placeholder="Filter by status"
                size="md"
              />
            </div>
          </div>

          <!-- Actions -->
          <div class="flex space-x-2">
            <AdminActionButton
              icon="mdi:download"
              text="Export CSV"
              variant="secondary"
              @click="exportOrders"
            />
          </div>
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

    <!-- DataTable -->
    <DataTable
      v-else
      :columns="columns"
      :rows="filteredOrders"
      v-model:selected="selectedIds"
      :rows-per-page="perPage"
      title="Orders"
      :show-checkboxes="false"
    >
      <!-- Order ID -->
      <template #cell-order_id="{ row }">
        <div class="text-sm font-medium text-gray-900">{{ row.order_id }}</div>
      </template>

      <!-- Customer -->
      <template #cell-customer="{ row }">
        <div>
          <div class="text-sm font-medium text-gray-900">{{ row.customer }}</div>
          <div class="text-sm text-gray-500">{{ row.customer_email }}</div>
        </div>
      </template>

      <!-- Amount -->
      <template #cell-amount="{ row }">
        <div class="text-sm font-medium text-gray-900">{{ row.amount }}</div>
      </template>

      <!-- Status -->
      <template #cell-status="{ row }">
        <div class="w-32">
          <Select
            :model-value="row.status"
            :options="statusUpdateOptions"
            @update:model-value="updateOrderStatus(row.raw_order, $event)"
          >
            <!-- Custom selected value with badge -->
            <template #selected="{ option, placeholder }">
              <StatusBadge v-if="option" :status="String(option.value)" type="purchase" />
              <span v-else class="text-gray-500">{{ placeholder }}</span>
            </template>
            
            <!-- Custom option with badge -->
            <template #option="{ option }">
              <StatusBadge :status="String(option.value)" type="purchase" />
            </template>
          </Select>
        </div>
      </template>

      <!-- Date -->
      <template #cell-date="{ row }">
        <div class="text-sm text-gray-500">{{ row.date }}</div>
      </template>

      <!-- Actions -->
      <template #cell-actions="{ row }">
        <div class="flex items-center space-x-2" @click.stop>
          <AdminActionButton
            icon="mdi:eye"
            text="View"
            variant="primary"
            size="sm"
            @click="viewOrder(row.raw_order)"
          />
          <AdminActionButton
            icon="mdi:download"
            text="Invoice"
            variant="success"
            size="sm"
            @click="downloadInvoice(row.raw_order)"
          />
        </div>
      </template>
    </DataTable>
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
