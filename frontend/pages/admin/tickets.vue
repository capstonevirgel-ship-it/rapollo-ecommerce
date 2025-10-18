<script setup lang="ts">
import type { Ticket, TicketStatistics } from '~/types'
import AdminActionButton from '@/components/AdminActionButton.vue'
import DataTable from '@/components/DataTable.vue'
import Select from '@/components/Select.vue'

// Use admin layout
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Tickets Management - Admin - Rapollo E-commerce',
  meta: [
    { name: 'description', content: 'Manage event tickets and bookings in your Rapollo E-commerce store.' }
  ]
})

const ticketStore = useTicketStore()
const authStore = useAuthStore()

// Redirect if not admin
if (!authStore.isAuthenticated || authStore.user?.role !== 'admin') {
  await navigateTo('/login')
}

// Fetch all tickets and statistics on client side
onMounted(async () => {
  await Promise.all([
    ticketStore.fetchAllTickets(),
    ticketStore.fetchTicketStatistics()
  ])
})

const statistics = ref<TicketStatistics | null>(null)

// Define columns for DataTable
const ticketColumns = [
  { label: 'Ticket', key: 'ticket_number' },
  { label: 'Event', key: 'event_title' },
  { label: 'Customer', key: 'customer' },
  { label: 'Price', key: 'price' },
  { label: 'Status', key: 'status' },
  { label: 'Booked At', key: 'booked_at' },
  { label: 'Actions', key: 'actions' }
]

// Helper function to safely format revenue
const formatRevenue = (revenue: number | null | undefined): string => {
  const value = revenue || 0
  return typeof value === 'number' ? value.toFixed(2) : '0.00'
}

// Helper function to safely format price
const formatPrice = (price: any): string => {
  if (price === null || price === undefined) return '0.00'
  const numPrice = typeof price === 'string' ? parseFloat(price) : Number(price)
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2)
}
const selectedStatus = ref('all')

// Status options for the select
const statusOptions = [
  { value: 'all', label: 'All Status' },
  { value: 'pending', label: 'Pending' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'used', label: 'Used' }
]

// Fetch statistics
const fetchStats = async () => {
  try {
    statistics.value = await ticketStore.fetchTicketStatistics()
  } catch (error) {
    console.error('Error fetching statistics:', error)
  }
}

await fetchStats()

const filteredTickets = computed(() => {
  let filtered = ticketStore.tickets

  // Filter by status
  if (selectedStatus.value !== 'all') {
    filtered = filtered.filter(ticket => ticket.status === selectedStatus.value)
  }

  return filtered.map(ticket => ({
    ...ticket,
    event_title: ticket.event?.title,
    customer: ticket.user?.user_name
  }))
})

const getStatusColor = (status: string) => {
  switch (status) {
    case 'confirmed':
      return 'text-green-600 bg-green-100'
    case 'pending':
      return 'text-yellow-600 bg-yellow-100'
    case 'cancelled':
      return 'text-red-600 bg-red-100'
    case 'used':
      return 'text-blue-600 bg-blue-100'
    default:
      return 'text-gray-600 bg-gray-100'
  }
}

const getStatusText = (status: string) => {
  switch (status) {
    case 'confirmed':
      return 'Confirmed'
    case 'pending':
      return 'Pending'
    case 'cancelled':
      return 'Cancelled'
    case 'used':
      return 'Used'
    default:
      return status
  }
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

const updateTicketStatus = async (ticketId: number, newStatus: string) => {
  try {
    await ticketStore.updateTicketStatus(ticketId, newStatus)
    // Refresh statistics
    await fetchStats()
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
      ticket.booked_at
    ].join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'tickets-export.csv'
  a.click()
  window.URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="p-4">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Manage Tickets</h1>
        <p class="mt-2 text-gray-600">Manage and monitor all event ticket bookings</p>
      </div>

      <!-- Statistics Cards -->
      <div v-if="statistics" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Tickets</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ statistics.total_tickets }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Confirmed</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ statistics.confirmed_tickets }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ statistics.pending_tickets }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                  <dd class="text-lg font-medium text-gray-900">₱{{ formatRevenue(statistics.total_revenue) }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Additional Filters and Actions -->
      <div class="bg-white shadow rounded-lg mb-6">
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
          <div class="text-sm text-gray-900">
            {{ row.event?.title }}
          </div>
        </template>

        <!-- Customer -->
        <template #cell-customer="{ row }">
          <div>
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
          <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusColor(row.status)]">
            {{ getStatusText(row.status) }}
          </span>
        </template>

        <!-- Booked At -->
        <template #cell-booked_at="{ row }">
          <div class="text-sm text-gray-500">
            {{ formatDate(row.booked_at) }}
          </div>
        </template>

        <!-- Actions -->
        <template #cell-actions="{ row }">
          <div class="flex space-x-2">
            <Select
              :model-value="row.status"
              :options="statusOptions.filter(opt => opt.value !== 'all')"
              size="sm"
              @update:model-value="(value) => updateTicketStatus(row.id, value as string)"
            />
          </div>
        </template>
      </DataTable>
  </div>
</template>
