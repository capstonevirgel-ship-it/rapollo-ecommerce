<script setup lang="ts">
import type { Ticket, TicketStatistics } from '~/types'
import AdminActionButton from '@/components/AdminActionButton.vue'
import DataTable from '@/components/DataTable.vue'
import Select from '@/components/Select.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { storeToRefs } from 'pinia'

// Use admin layout
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Tickets Management - Admin | RAPOLLO',
  meta: [
    { name: 'description', content: 'Manage event tickets and bookings in your Rapollo E-commerce store.' }
  ]
})

const ticketStore = useTicketStore()
const authStore = useAuthStore()
const { tickets, loading, error: storeError } = storeToRefs(ticketStore)

// Redirect if not admin
if (!authStore.isAuthenticated || authStore.user?.role !== 'admin') {
  await navigateTo('/login')
}

const statistics = ref<TicketStatistics | null>(null)

// Define columns for DataTable
const ticketColumns = [
  { label: 'Ticket', key: 'ticket_number', width: 16.1667 },
  { label: 'Event', key: 'event_title', width: 16.1667 },
  { label: 'Customer', key: 'customer', width: 16.1667 },
  { label: 'Price', key: 'price'},
  { label: 'Status', key: 'status', width: 22},
  { label: 'Booked At', key: 'booked_at', width: 16.1667 }
]

// State
const selectedStatus = ref('all')

// Status options for the filter select
const statusOptions = [
  { value: 'all', label: 'All Status' },
  { value: 'pending', label: 'Pending' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'used', label: 'Used' }
]

// Status options for the update select (without 'all')
const statusUpdateOptions = [
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'used', label: 'Used' },
  { value: 'failed', label: 'Failed' }
]

// Fetch statistics
const fetchStats = async () => {
  try {
    statistics.value = await ticketStore.fetchTicketStatistics()
  } catch (error) {
    console.error('Error fetching statistics:', error)
  }
}

// Fetch all tickets (let DataTable handle pagination client-side)
const fetchTickets = async () => {
  try {
    await ticketStore.fetchAllTickets({
      per_page: 1000 // Fetch a large number to get all tickets for client-side filtering
    })
  } catch (err) {
    console.error('Failed to fetch tickets:', err)
  }
}

// No watchers needed since filtering is handled client-side

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchTickets(),
    fetchStats()
  ])
})

const filteredTickets = computed(() => {
  let filtered = tickets.value

  // Filter by status client-side
  if (selectedStatus.value !== 'all') {
    filtered = filtered.filter(ticket => ticket.status === selectedStatus.value)
  }

  return filtered.map(ticket => ({
    ...ticket,
    event_title: ticket.event?.title,
    customer: ticket.user?.user_name
  }))
})

// Helper functions
const formatRevenue = (revenue: number | null | undefined): string => {
  const value = revenue || 0
  return typeof value === 'number' ? value.toFixed(2) : '0.00'
}

const formatPrice = (price: any): string => {
  if (price === null || price === undefined) return '0.00'
  const numPrice = typeof price === 'string' ? parseFloat(price) : Number(price)
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2)
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

const formatDateForCSV = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}`
}

const getDownloadDate = () => {
  const today = new Date()
  const year = today.getFullYear()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  const day = String(today.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const updateTicketStatus = async (ticket: any, newStatus: string | number | null) => {
  if (!newStatus) return
  try {
    await ticketStore.updateTicketStatus(ticket.id, newStatus as string)
    // Refresh data
    await Promise.all([
      fetchTickets(),
      fetchStats()
    ])
  } catch (error) {
    console.error('Error updating ticket status:', error)
  }
}

const exportTickets = () => {
  // Simple CSV export
  const headers = ['Ticket Number', 'Event', 'Customer', 'Email', 'Price', 'Status', 'Booked At']
  const csvContent = [
    headers.join(','),
    ...filteredTickets.value.map(ticket => [
      ticket.ticket_number,
      `"${ticket.event?.title || ''}"`,
      `"${ticket.user?.user_name || ''}"`,
      `"${ticket.user?.email || ''}"`,
      ticket.price,
      ticket.status,
      formatDateForCSV(ticket.booked_at)
    ].join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `tickets-export-${getDownloadDate()}.csv`
  a.click()
  window.URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="space-y-8 sm:space-y-10">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Manage Tickets</h1>
          <p class="text-sm sm:text-base text-gray-600 mt-1">Manage and monitor all event ticket bookings</p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div v-if="statistics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 lg:gap-10">
        <StatCard
          title="Total Tickets"
          :value="statistics.total_tickets"
          icon="mdi:ticket"
        />

        <StatCard
          title="Confirmed"
          :value="statistics.confirmed_tickets"
          icon="mdi:check-circle"
        />

        <StatCard
          title="Pending"
          :value="statistics.pending_tickets"
          icon="mdi:clock-outline"
        />

        <StatCard
          title="Total Revenue"
          :value="`₱${formatRevenue(statistics.total_revenue)}`"
          icon="mdi:currency-usd"
        />
      </div>

      <!-- Additional Filters and Actions -->
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
                @click="exportTickets"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="ticketStore.loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="ticketStore.error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
        <div class="flex">
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Error</h3>
            <div class="mt-2 text-sm text-red-700">
              {{ ticketStore.error }}
            </div>
          </div>
        </div>
      </div>

      <!-- Tickets Table -->
      <DataTable
        v-else
        :columns="ticketColumns"
        :rows="filteredTickets"
        title="Tickets"
        :show-checkboxes="false"
        :rows-per-page="20"
      >
        <!-- Ticket Number -->
        <template #cell-ticket_number="{ row }">
          <div class="text-sm font-medium text-gray-900">
            {{ row.ticket_number }}
          </div>
        </template>

        <!-- Event -->
        <template #cell-event_title="{ row }">
          <div class="text-sm text-gray-900 whitespace-normal break-words">
            {{ row.event?.title }}
          </div>
        </template>

        <!-- Customer -->
        <template #cell-customer="{ row }">
          <div class="whitespace-normal break-words">
            <div class="font-medium text-sm text-gray-900">{{ row.user?.user_name }}</div>
            <div class="text-sm text-gray-500">{{ row.user?.email }}</div>
          </div>
        </template>

        <!-- Price -->
        <template #cell-price="{ row }">
          <div class="text-sm text-gray-900">
            ₱{{ formatPrice(row.price) }}
          </div>
        </template>

        <!-- Status -->
        <template #cell-status="{ row }">
          <div class="w-40">
            <Select
              :model-value="row.status"
              :options="statusUpdateOptions"
              @update:model-value="updateTicketStatus(row, $event)"
            >
              <!-- Custom selected value with badge -->
              <template #selected="{ option, placeholder }">
                <StatusBadge v-if="option" :status="String(option.value)" type="ticket" />
                <span v-else class="text-gray-500">{{ placeholder }}</span>
              </template>
              
              <!-- Custom option with badge -->
              <template #option="{ option }">
                <StatusBadge :status="String(option.value)" type="ticket" />
              </template>
            </Select>
          </div>
        </template>

        <!-- Booked At -->
        <template #cell-booked_at="{ row }">
          <div class="text-sm text-gray-500 whitespace-normal break-words">
            {{ formatDate(row.booked_at) }}
          </div>
        </template>
      </DataTable>
  </div>
</template>
