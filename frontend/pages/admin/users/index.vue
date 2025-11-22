<script setup lang="ts">
import { onMounted, computed, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAdminUsersStore } from '~/stores/admin-users'
import { useAlert } from '~/composables/useAlert'
import Select from '@/components/Select.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import DataTable from '@/components/DataTable.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import Dialog from '@/components/Dialog.vue'
import { getImageUrl } from '~/utils/imageHelper'

definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Users Management - Admin | RAPOLLO',
  meta: [
    { name: 'description', content: 'Manage customers in your Rapollo E-commerce store.' }
  ]
})

// Stores
const adminUsersStore = useAdminUsersStore()
const { success, error } = useAlert()
const { users, loading } = storeToRefs(adminUsersStore)

// State
const suspensionFilter = ref('all')
const showSuspendDialog = ref(false)
const showUnsuspendDialog = ref(false)
const selectedUser = ref<{ id: number; user_name: string; email: string } | null>(null)
const suspensionReason = ref('')
const isProcessing = ref(false)

// Options
const filterOptions = [
  { value: 'all', label: 'All Users' },
  { value: 'false', label: 'Active' },
  { value: 'true', label: 'Suspended' }
]

// Columns for DataTable
const columns = [
  { label: 'User Name', key: 'user_name', width: 20 },
  { label: 'Email', key: 'email', width: 25 },
  { label: 'Status', key: 'status', width: 20 },
  { label: 'Created At', key: 'created_at', width: 20 },
  { label: 'Actions', key: 'actions', width: 15 }
]

// Computed - transform users for DataTable
const userRows = computed(() => {
  return users.value.map(user => ({
    id: user.id,
    user_name: user.user_name,
    email: user.email,
    status: user.is_suspended ? 'suspended' : 'active',
    is_suspended: user.is_suspended,
    cancellation_count: user.cancellation_count || 0,
    suspension_reason: user.suspension_reason,
    suspended_at: user.suspended_at,
    created_at: user.created_at,
    avatar_url: user.profile?.avatar_url || null,
    raw_user: user
  }))
})

// Filter users client-side
const filteredUsers = computed(() => {
  let filtered = userRows.value

  // Filter by status
  if (suspensionFilter.value !== 'all') {
    const isSuspended = suspensionFilter.value === 'true'
    filtered = filtered.filter(user => user.is_suspended === isSuspended)
  }

  return filtered
})

// Fetch all users (let DataTable handle pagination client-side)
const fetchUsers = async () => {
  try {
    await adminUsersStore.fetchUsers({
      per_page: 1000 // Fetch a large number to get all users for client-side filtering
    })
  } catch (err: any) {
    error('Failed to load users', err.message || 'An error occurred')
  }
}

const handleSuspend = (user: any) => {
  selectedUser.value = {
    id: user.id,
    user_name: user.user_name,
    email: user.email
  }
  suspensionReason.value = ''
  showSuspendDialog.value = true
}

const handleUnsuspend = (user: any) => {
  selectedUser.value = {
    id: user.id,
    user_name: user.user_name,
    email: user.email
  }
  showUnsuspendDialog.value = true
}

const confirmSuspend = async () => {
  if (!selectedUser.value) return
  
  isProcessing.value = true
  try {
    await adminUsersStore.suspendUser(selectedUser.value.id, suspensionReason.value || undefined)
    success('User Suspended', `User ${selectedUser.value.user_name} has been suspended successfully.`)
    showSuspendDialog.value = false
    suspensionReason.value = ''
    selectedUser.value = null
    await fetchUsers()
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
    await adminUsersStore.unsuspendUser(selectedUser.value.id)
    success('User Unsuspended', `User ${selectedUser.value.user_name} has been unsuspended successfully.`)
    showUnsuspendDialog.value = false
    selectedUser.value = null
    await fetchUsers()
  } catch (err: any) {
    error('Failed to unsuspend user', err.message || 'An error occurred')
  } finally {
    isProcessing.value = false
  }
}

const viewUser = (userId: number) => {
  navigateTo(`/admin/users/${userId}`)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateForCSV = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const getDownloadDate = () => {
  const today = new Date()
  const year = today.getFullYear()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  const day = String(today.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const exportUsers = () => {
  // Simple CSV export
  const headers = ['User Name', 'Email', 'Status', 'Suspended At', 'Suspension Reason', 'Cancellations', 'Created At']
  const csvContent = [
    headers.join(','),
    ...filteredUsers.value.map(user => [
      user.user_name,
      `"${user.email}"`,
      user.is_suspended ? 'Suspended' : 'Active',
      user.suspended_at ? formatDateForCSV(user.suspended_at) : '',
      user.suspension_reason ? `"${user.suspension_reason.replace(/"/g, '""')}"` : '',
      user.cancellation_count || 0,
      formatDateForCSV(user.created_at)
    ].join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `users-export-${getDownloadDate()}.csv`
  a.click()
  window.URL.revokeObjectURL(url)
}

// Lifecycle
onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div class="space-y-8 sm:space-y-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Users Management</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Manage and monitor customer accounts</p>
      </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-6 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <div class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
            <!-- Status Filter -->
            <div class="w-full sm:w-48">
              <Select
                v-model="suspensionFilter"
                :options="filterOptions"
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
              @click="exportUsers"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-zinc-600"></div>
    </div>

    <!-- Users Table -->
    <DataTable
      v-else
      :columns="columns"
      :rows="filteredUsers"
      title="Users"
      :show-checkboxes="false"
      :rows-per-page="20"
    >
      <template #cell-user_name="{ row }">
        <div class="flex items-center space-x-3">
          <img
            :src="row.avatar_url ? getImageUrl(row.avatar_url, 'default') : '/uploads/avatar_placeholder.png'"
            :alt="row.user_name"
            class="w-8 h-8 rounded-full object-cover flex-shrink-0"
          />
          <div class="font-medium text-gray-900">{{ row.user_name }}</div>
        </div>
      </template>

      <template #cell-email="{ row }">
        <div class="text-gray-600">{{ row.email }}</div>
      </template>

      <template #cell-status="{ row }">
        <StatusBadge
          :status="row.is_suspended ? 'suspended' : 'active'"
          :variant="row.is_suspended ? 'danger' : 'success'"
        />
        <div v-if="row.is_suspended && row.suspension_reason" class="text-xs text-gray-500 mt-1 max-w-xs truncate">
          {{ row.suspension_reason }}
        </div>
        <div v-if="row.cancellation_count > 0" class="text-xs text-red-600 mt-1">
          {{ row.cancellation_count }} cancellation{{ row.cancellation_count !== 1 ? 's' : '' }}
        </div>
      </template>

      <template #cell-created_at="{ row }">
        <div class="text-sm text-gray-500">{{ formatDate(row.created_at) }}</div>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex gap-2 justify-center">
          <AdminActionButton
            icon="mdi:eye"
            text="View"
            variant="primary"
            @click="viewUser(row.id)"
          />
          <AdminActionButton
            v-if="!row.is_suspended"
            icon="mdi:account-off"
            text="Suspend"
            variant="danger"
            @click="handleSuspend(row)"
          />
          <AdminActionButton
            v-else
            icon="mdi:account-check"
            text="Unsuspend"
            variant="success"
            @click="handleUnsuspend(row)"
          />
        </div>
      </template>
    </DataTable>

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
            @click="showSuspendDialog = false; suspensionReason = ''; selectedUser = null"
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
            @click="showUnsuspendDialog = false; selectedUser = null"
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