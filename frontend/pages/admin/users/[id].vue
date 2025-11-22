<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAdminUsersStore } from '~/stores/admin-users'
import { useAlert } from '~/composables/useAlert'
import StatusBadge from '@/components/StatusBadge.vue'
import Dialog from '@/components/Dialog.vue'
import DataTable from '@/components/DataTable.vue'
import { getImageUrl } from '~/utils/imageHelper'

definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'User Details - Admin | RAPOLLO',
  meta: [
    { name: 'description', content: 'View and manage user details in your Rapollo E-commerce store.' }
  ]
})

const route = useRoute()
const adminUsersStore = useAdminUsersStore()
const { success, error, warning } = useAlert()
const { selectedUser, userTransactions, loading } = storeToRefs(adminUsersStore)

const userId = computed(() => parseInt(route.params.id as string))
const showSuspendDialog = ref(false)
const showUnsuspendDialog = ref(false)
const suspensionReason = ref('')
const isProcessing = ref(false)
const currentTransactionPage = ref(1)

// Columns for transactions
const productPurchaseColumns = [
  { label: 'Order ID', key: 'order_id', width: 15 },
  { label: 'Total', key: 'total', width: 15 },
  { label: 'Status', key: 'status', width: 15 },
  { label: 'Date', key: 'date', width: 20 },
  { label: 'Items', key: 'items', width: 35 }
]

const ticketPurchaseColumns = [
  { label: 'Purchase ID', key: 'purchase_id', width: 15 },
  { label: 'Event', key: 'event', width: 25 },
  { label: 'Total', key: 'total', width: 15 },
  { label: 'Date', key: 'date', width: 15 },
  { label: 'Tickets', key: 'tickets', width: 30 }
]

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
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatAddress = (profile: any) => {
  if (!profile) return 'N/A'
  
  const parts = []
  
  if (profile.street && profile.street !== 'n/a') {
    parts.push(profile.street)
  }
  if (profile.barangay) {
    parts.push(profile.barangay)
  }
  if (profile.city) {
    parts.push(profile.city)
  }
  if (profile.province) {
    parts.push(profile.province)
  }
  if (profile.zipcode) {
    parts.push(profile.zipcode)
  }
  if (profile.country) {
    parts.push(profile.country)
  }
  
  if (parts.length === 0) {
    return profile.complete_address || 'N/A'
  }
  
  return parts.join(', ')
}

const goBack = () => {
  navigateTo('/admin/users')
}

const fetchUser = async () => {
  try {
    await adminUsersStore.fetchUser(userId.value)
  } catch (err: any) {
    error('Failed to load user', err.message || 'An error occurred')
  }
}

const fetchTransactions = async () => {
  try {
    await adminUsersStore.fetchUserTransactions(userId.value, currentTransactionPage.value)
  } catch (err: any) {
    error('Failed to load transactions', err.message || 'An error occurred')
  }
}

const handleSuspend = () => {
  suspensionReason.value = ''
  showSuspendDialog.value = true
}

const handleUnsuspend = () => {
  showUnsuspendDialog.value = true
}

const confirmSuspend = async () => {
  if (!selectedUser.value) return
  
  isProcessing.value = true
  try {
    await adminUsersStore.suspendUser(userId.value, suspensionReason.value || undefined)
    success('User Suspended', `User ${selectedUser.value.user_name} has been suspended successfully.`)
    showSuspendDialog.value = false
    suspensionReason.value = ''
    await fetchUser()
  } catch (err: any) {
    error('Failed to suspend user', err.message || 'An error occurred')
  } finally {
    isProcessing.value = false
  }
}

const confirmUnsuspend = async () => {
  if (!selectedUser.value) return
  
  isProcessing.value = true
  try {
    await adminUsersStore.unsuspendUser(userId.value)
    success('User Unsuspended', `User ${selectedUser.value.user_name} has been unsuspended successfully.`)
    showUnsuspendDialog.value = false
    await fetchUser()
  } catch (err: any) {
    error('Failed to unsuspend user', err.message || 'An error occurred')
  } finally {
    isProcessing.value = false
  }
}

const productPurchaseRows = computed(() => {
  if (!userTransactions.value?.product_purchases?.data) return []
  
  return userTransactions.value.product_purchases.data.map((purchase: any) => ({
    id: purchase.id,
    order_id: `#${purchase.id.toString().padStart(4, '0')}`,
    total: formatCurrency(purchase.total),
    status: purchase.status,
    date: formatDate(purchase.created_at),
    items: purchase.items?.map((item: any) => item.variant?.product?.name || 'N/A').join(', ') || 'N/A',
    raw_purchase: purchase
  }))
})

const ticketPurchaseRows = computed(() => {
  if (!userTransactions.value?.ticket_purchases?.data) return []
  
  return userTransactions.value.ticket_purchases.data.map((purchase: any) => ({
    id: purchase.id,
    purchase_id: `#${purchase.id.toString().padStart(4, '0')}`,
    event: purchase.event?.title || 'N/A',
    total: formatCurrency(purchase.total),
    date: formatDate(purchase.created_at),
    tickets: `${purchase.tickets?.length || 0} ticket(s)`,
    raw_purchase: purchase
  }))
})

onMounted(async () => {
  await fetchUser()
  await fetchTransactions()
})
</script>

<template>
  <div class="space-y-8 sm:space-y-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">User Details</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">{{ selectedUser?.user_name || 'Loading...' }}</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          v-if="selectedUser && !selectedUser.is_suspended"
          @click="handleSuspend"
          class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors"
        >
          Suspend User
        </button>
        <button
          v-else-if="selectedUser && selectedUser.is_suspended"
          @click="handleUnsuspend"
          class="px-4 py-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors"
        >
          Unsuspend User
        </button>
        <button
          @click="goBack"
          class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to Users
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !selectedUser" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
      <p class="mt-2 text-gray-600">Loading user details...</p>
    </div>

    <!-- User Information -->
    <div v-if="selectedUser" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- User Details Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">User Information</h2>
        <div class="space-y-4">
          <div class="flex items-center space-x-4 mb-4">
            <img
              :src="selectedUser.profile?.avatar_url ? getImageUrl(selectedUser.profile.avatar_url, 'default') : '/uploads/avatar_placeholder.png'"
              :alt="selectedUser.user_name"
              class="w-16 h-16 rounded-full object-cover flex-shrink-0"
            />
            <div>
              <label class="text-sm font-medium text-gray-500">Username</label>
              <p class="text-gray-900 mt-1">{{ selectedUser.user_name }}</p>
            </div>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Email</label>
            <p class="text-gray-900 mt-1">{{ selectedUser.email }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Status</label>
            <div class="mt-1">
              <StatusBadge
                :status="selectedUser.is_suspended ? 'suspended' : 'active'"
                :variant="selectedUser.is_suspended ? 'danger' : 'success'"
              />
            </div>
          </div>
          <div v-if="selectedUser.is_suspended && selectedUser.suspension_reason">
            <label class="text-sm font-medium text-gray-500">Suspension Reason</label>
            <p class="text-gray-900 mt-1">{{ selectedUser.suspension_reason }}</p>
          </div>
          <div v-if="selectedUser.suspended_at">
            <label class="text-sm font-medium text-gray-500">Suspended At</label>
            <p class="text-gray-900 mt-1">{{ formatDate(selectedUser.suspended_at) }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Member Since</label>
            <p class="text-gray-900 mt-1">{{ formatDate(selectedUser.created_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Profile Details Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Information</h2>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Full Name</label>
            <p class="text-gray-900 mt-1">{{ selectedUser.profile?.full_name || 'N/A' }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Phone</label>
            <p class="text-gray-900 mt-1">{{ selectedUser.profile?.phone || 'N/A' }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Address</label>
            <p class="text-gray-900 mt-1">{{ formatAddress(selectedUser.profile) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Transaction Statistics -->
    <div v-if="selectedUser" class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Transaction Statistics</h2>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-medium text-gray-500">Product Purchases</label>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ selectedUser.product_purchases_count || 0 }}</p>
        </div>
        <div>
          <label class="text-sm font-medium text-gray-500">Ticket Purchases</label>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ selectedUser.ticket_purchases_count || 0 }}</p>
        </div>
        <div>
          <label class="text-sm font-medium text-gray-500">Cancellations</label>
          <p class="text-2xl font-bold text-red-600 mt-1">{{ selectedUser.cancellation_count || 0 }}</p>
        </div>
      </div>
    </div>

    <!-- Transaction History -->
    <div v-if="selectedUser" class="space-y-6">
      <!-- Product Purchases -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
          <h2 class="text-lg font-semibold text-gray-900">Product Purchases</h2>
        </div>
        <div class="p-6">
          <DataTable
            v-if="productPurchaseRows.length > 0"
            :columns="productPurchaseColumns"
            :rows="productPurchaseRows"
            :loading="loading"
            :pagination="userTransactions?.product_purchases"
            @page-change="(page) => { currentTransactionPage = page; fetchTransactions() }"
          >
            <template #cell-status="{ row }">
              <StatusBadge :status="row.status" />
            </template>
          </DataTable>
          <div v-else class="text-center py-12 text-gray-500">
            No product purchases found.
          </div>
        </div>
      </div>

      <!-- Ticket Purchases -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
          <h2 class="text-lg font-semibold text-gray-900">Ticket Purchases</h2>
        </div>
        <div class="p-6">
          <DataTable
            v-if="ticketPurchaseRows.length > 0"
            :columns="ticketPurchaseColumns"
            :rows="ticketPurchaseRows"
            :loading="loading"
            :pagination="userTransactions?.ticket_purchases"
            @page-change="(page) => { currentTransactionPage = page; fetchTransactions() }"
          />
          <div v-else class="text-center py-12 text-gray-500">
            No ticket purchases found.
          </div>
        </div>
      </div>
    </div>

    <!-- Suspend Dialog -->
    <Dialog
      v-model="showSuspendDialog"
      title="Suspend User"
      width="500px"
    >
      <div class="space-y-4">
        <p class="text-gray-600">
          Are you sure you want to suspend <strong>{{ selectedUser?.user_name }}</strong>?
        </p>
        <div>
          <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
            Reason (optional)
          </label>
          <textarea
            id="reason"
            v-model="suspensionReason"
            rows="3"
            placeholder="Enter reason for suspension..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-transparent"
          />
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button
            @click="showSuspendDialog = false; suspensionReason = ''"
            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="confirmSuspend"
            :disabled="isProcessing"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
          >
            {{ isProcessing ? 'Suspending...' : 'Suspend' }}
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Unsuspend Dialog -->
    <Dialog
      v-model="showUnsuspendDialog"
      title="Unsuspend User"
      width="500px"
    >
      <div class="space-y-4">
        <p class="text-gray-600">
          Are you sure you want to unsuspend <strong>{{ selectedUser?.user_name }}</strong>?
        </p>
        <p class="text-sm text-gray-500">
          The user will be able to proceed with checkout and purchase tickets again.
        </p>
        <div class="flex justify-end gap-3 pt-4">
          <button
            @click="showUnsuspendDialog = false"
            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="confirmUnsuspend"
            :disabled="isProcessing"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
          >
            {{ isProcessing ? 'Unsuspending...' : 'Unsuspend' }}
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>
