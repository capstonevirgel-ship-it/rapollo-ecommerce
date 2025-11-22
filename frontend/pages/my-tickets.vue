<script setup lang="ts">
import type { Ticket } from '~/types'
import Dialog from '@/components/Dialog.vue'

// Use default layout for customer tickets
definePageMeta({
  layout: 'default'
})

// Set page title
useHead({
  title: 'My Tickets | RAPOLLO',
  meta: [
    { name: 'description', content: 'View and manage your event tickets at Rapollo E-commerce.' }
  ]
})

const ticketStore = useTicketStore()
const authStore = useAuthStore()
const { success, error } = useAlert()

const showCancelDialog = ref(false)
const cancellingTicket = ref<{ id: number; ticket_number: string } | null>(null)
const isCancelling = ref(false)

// Check authentication and fetch user if needed
if (!authStore.isAuthenticated) {
  try {
    await authStore.fetchUser()
    if (!authStore.isAuthenticated) {
      await navigateTo('/login')
    }
  } catch {
    await navigateTo('/login')
  }
}

// Fetch user's tickets on client side
onMounted(() => {
  ticketStore.fetchTickets()
})


const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Helper function to safely format price
const formatPrice = (price: any): string => {
  if (price === null || price === undefined) return '0.00'
  const numPrice = typeof price === 'string' ? parseFloat(price) : Number(price)
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2)
}

const openCancelDialog = (ticket: Ticket) => {
  cancellingTicket.value = { id: ticket.id, ticket_number: ticket.ticket_number }
  showCancelDialog.value = true
}

const closeCancelDialog = () => {
  showCancelDialog.value = false
  cancellingTicket.value = null
}

const cancelTicket = async () => {
  if (!cancellingTicket.value) return

  isCancelling.value = true
  const ticketToCancel = { ...cancellingTicket.value }

  try {
    await ticketStore.cancelTicket(ticketToCancel.id)
    success('Ticket Cancelled', 'Your ticket has been cancelled successfully.')
    await ticketStore.fetchTickets()
  } catch (err: any) {
    console.error('Error cancelling ticket:', err)
    const errorMessage = err?.data?.message || err?.data?.error || err?.message || 'Failed to cancel ticket. Please try again.'
    error('Cancellation Failed', errorMessage)
  } finally {
    isCancelling.value = false
    closeCancelDialog()
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Tickets</h1>
        <p class="mt-2 text-gray-600">View and manage your event tickets</p>
      </div>

      <!-- Content with Sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="lg:sticky lg:top-8 lg:self-start">
            <CustomerSidebar />
          </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-3">
          <!-- Loading State -->
          <div v-if="ticketStore.loading">
            <LoadingSpinner 
              size="lg" 
              color="secondary" 
              text="Loading tickets..." 
              :show-text="true"
            />
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

          <!-- Empty State -->
          <div v-else-if="ticketStore.tickets.length === 0" class="text-center py-12">
            <div class="mx-auto h-24 w-24 text-gray-400">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
              </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No tickets found</h3>
            <p class="mt-1 text-sm text-gray-500">You haven't booked any rap battle tickets yet.</p>
            <div class="mt-6">
              <NuxtLink to="/events" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-zinc-900 hover:bg-zinc-800">
                Browse Events
              </NuxtLink>
            </div>
          </div>

          <!-- Tickets List -->
          <div v-else class="space-y-6">
            <div v-for="ticket in ticketStore.tickets" :key="ticket.id" class="bg-white shadow rounded-lg overflow-hidden">
              <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <h3 class="text-lg font-medium text-gray-900">{{ ticket.event?.title }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ ticket.event?.location }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ formatDate(ticket.event?.date || '') }}</p>
                  </div>
                  <div class="flex items-center space-x-4">
                    <div class="text-right">
                      <p class="text-lg font-semibold text-gray-900">₱{{ formatPrice(ticket.price) }}</p>
                      <p class="text-sm text-gray-500">Ticket #{{ ticket.ticket_number }}</p>
                    </div>
                    <StatusBadge :status="ticket.status" type="ticket" />
                  </div>
                </div>
                
                <div class="mt-4 flex items-center justify-between">
                  <div class="text-sm text-gray-500">
                    <p>Booked on: {{ formatDate(ticket.booked_at) }}</p>
                  </div>
                  
                  <div class="flex space-x-2">
                    <button
                      v-if="ticket.status === 'confirmed'"
                      @click="openCancelDialog(ticket)"
                      class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                      Cancel Ticket
                    </button>
                    
                    <button
                      @click="() => {}"
                      class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500"
                    >
                      View Details
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation Dialog -->
    <Dialog v-model="showCancelDialog" title="Cancel Ticket">
      <div class="flex flex-col items-center text-center">
        <!-- Warning Icon -->
        <div class="mb-4 flex items-center justify-center w-20 h-20 rounded-full bg-yellow-100">
          <Icon name="mdi:alert" class="text-[3rem] text-yellow-600" />
        </div>

        <!-- Confirmation Message -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          Are you sure you want to cancel this ticket?
        </h3>
        <p class="text-sm text-gray-600 mb-6">
          This action cannot be undone. Your ticket will be cancelled and you may be subject to our cancellation policy.
        </p>

        <!-- Action Buttons -->
        <div class="flex gap-3 w-full">
          <button
            @click="closeCancelDialog"
            :disabled="isCancelling"
            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Cancel
          </button>
          <button
            @click="cancelTicket"
            :disabled="isCancelling"
            class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
          >
            <span v-if="isCancelling">Cancelling...</span>
            <span v-else>Cancel Ticket</span>
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>
