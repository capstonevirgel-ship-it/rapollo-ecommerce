<script setup lang="ts">
import type { Event } from '~/types'

const eventStore = useEventStore()
const ticketStore = useTicketStore()
const authStore = useAuthStore()

// Fetch events on client side
onMounted(async () => {
  console.log('Events page mounted, fetching events...')
  await eventStore.fetchEvents()
  console.log('Events after fetch:', eventStore.events)
})

const selectedQuantity = ref(1)
const bookingEvent = ref<Event | null>(null)
const showBookingModal = ref(false)
const bookingLoading = ref(false)

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTime = (dateString: string) => {
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const openBookingModal = (event: Event) => {
  if (!authStore.isAuthenticated) {
    navigateTo('/login')
    return
  }
  
  bookingEvent.value = event
  selectedQuantity.value = 1
  showBookingModal.value = true
}

const closeBookingModal = () => {
  showBookingModal.value = false
  bookingEvent.value = null
  selectedQuantity.value = 1
}

const bookTickets = async () => {
  if (!bookingEvent.value) return
  
  bookingLoading.value = true
  try {
    const response = await ticketStore.bookTickets(bookingEvent.value.id, selectedQuantity.value)
    
    // Show success message
    alert(`Successfully booked ${selectedQuantity.value} ticket(s) for ${bookingEvent.value.title}!`)
    
    closeBookingModal()
    
    // Navigate to my tickets page
    navigateTo('/my-tickets')
  } catch (error: any) {
    alert(error.data?.message || 'Failed to book tickets')
  } finally {
    bookingLoading.value = false
  }
}

const getRemainingTickets = (event: Event) => {
  if (!event.max_tickets) return 0
  return event.max_tickets - (event.booked_tickets_count || 0)
}

const isEventFullyBooked = (event: Event) => {
  return getRemainingTickets(event) <= 0
}

const canBookTickets = (event: Event) => {
  return event.ticket_price && event.max_tickets && !isEventFullyBooked(event)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Events</h1>
        <p class="mt-2 text-gray-600">Discover and book tickets for upcoming rap battles</p>
      </div>

      <!-- Loading State -->
      <div v-if="eventStore.loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="eventStore.error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
        <div class="flex">
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Error</h3>
            <div class="mt-2 text-sm text-red-700">
              {{ eventStore.error }}
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="eventStore.events.length === 0" class="text-center py-12">
        <div class="mx-auto h-24 w-24 text-gray-400">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No rap battle events found</h3>
        <p class="mt-1 text-sm text-gray-500">There are no upcoming rap battle events at the moment.</p>
      </div>

      <!-- Events Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="event in eventStore.events" :key="event.id" class="bg-white rounded-lg shadow-md overflow-hidden">
          <!-- Event Image -->
          <div v-if="event.poster_url" class="h-48 w-full bg-gray-200">
            <img :src="event.poster_url" :alt="event.title" class="h-full w-full object-cover">
          </div>
          <div v-else class="h-48 w-full bg-gray-200 flex items-center justify-center">
            <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>

          <!-- Event Content -->
          <div class="p-6">
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ event.title }}</h3>
            <p class="text-gray-600 mb-4 line-clamp-3">{{ event.description }}</p>
            
            <!-- Event Details -->
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-500">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ formatDate(event.date) }} at {{ formatTime(event.date) }}
              </div>
              <div v-if="event.location" class="flex items-center text-sm text-gray-500">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ event.location }}
              </div>
            </div>

            <!-- Ticket Information -->
            <div v-if="event.ticket_price" class="mb-4">
              <div class="flex items-center justify-between">
                <span class="text-2xl font-bold text-gray-900">₱{{ typeof event.ticket_price === 'string' ? parseFloat(event.ticket_price).toFixed(2) : event.ticket_price.toFixed(2) }}</span>
                <span v-if="event.max_tickets" class="text-sm text-gray-500">
                  {{ getRemainingTickets(event) }} tickets left
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-2">
              <button
                v-if="canBookTickets(event)"
                @click="openBookingModal(event)"
                class="flex-1 bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2"
              >
                Book Tickets
              </button>
              <button
                v-else-if="isEventFullyBooked(event)"
                disabled
                class="flex-1 bg-gray-300 text-gray-500 px-4 py-2 rounded-md cursor-not-allowed"
              >
                Sold Out
              </button>
              <button
                v-else
                disabled
                class="flex-1 bg-gray-300 text-gray-500 px-4 py-2 rounded-md cursor-not-allowed"
              >
                No Tickets Available
              </button>
              
              <button
                @click="() => {}"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2"
              >
                View Details
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Booking Modal -->
    <div v-if="showBookingModal && bookingEvent" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Book Tickets</h3>
          
          <div class="mb-4">
            <h4 class="text-md font-semibold text-gray-700">{{ bookingEvent.title }}</h4>
            <p class="text-sm text-gray-500">{{ formatDate(bookingEvent.date) }} at {{ formatTime(bookingEvent.date) }}</p>
            <p v-if="bookingEvent.location" class="text-sm text-gray-500">{{ bookingEvent.location }}</p>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Tickets</label>
            <select v-model="selectedQuantity" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option v-for="i in Math.min(5, getRemainingTickets(bookingEvent))" :key="i" :value="i">{{ i }}</option>
            </select>
          </div>

          <div class="mb-4">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-700">Total Price:</span>
              <span class="text-lg font-bold text-gray-900">${{ (parseFloat(bookingEvent.ticket_price!.toString()) * selectedQuantity).toFixed(2) }}</span>
            </div>
          </div>

          <div class="flex space-x-3">
            <button
              @click="closeBookingModal"
              class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
            >
              Cancel
            </button>
            <button
              @click="bookTickets"
              :disabled="bookingLoading"
              class="flex-1 bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 disabled:opacity-50"
            >
              {{ bookingLoading ? 'Booking...' : 'Confirm Booking' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>