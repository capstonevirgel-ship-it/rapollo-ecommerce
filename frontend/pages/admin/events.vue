<script setup lang="ts">
import type { Event } from '~/types'
import { getImageUrl } from '~/helpers/imageHelper'
import { useTaxStore } from '~/stores/tax'

// Use admin layout
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Events Management - Admin | monogram',
  meta: [
    { name: 'description', content: 'Manage events and tickets in your monogram E-commerce store.' }
  ]
})

const eventStore = useEventStore()
const authStore = useAuthStore()
const taxStore = useTaxStore()

// Computed function to calculate final ticket price with tax
const getFinalTicketPrice = (basePrice: number | string) => {
  const price = typeof basePrice === 'string' ? parseFloat(basePrice) : basePrice
  if (price <= 0) return 0
  const totalTaxRate = taxStore.totalTaxRate
  return price * (1 + totalTaxRate / 100)
}

// Redirect if not admin
if (!authStore.isAuthenticated || authStore.user?.role !== 'admin') {
  await navigateTo('/login')
}

// Fetch events on client side
onMounted(() => {
  eventStore.fetchEvents()
})

const deleteEvent = async (eventId: number) => {
  if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
    try {
      await eventStore.deleteEvent(eventId)
    } catch (error) {
      console.error('Error deleting event:', error)
    }
  }
}

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
</script>

<template>
  <div class="space-y-8 sm:space-y-10">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Manage Events</h1>
          <p class="text-sm sm:text-base text-gray-600 mt-1">Create and manage events</p>
        </div>
        <NuxtLink
          to="/admin/add-event"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500"
        >
          <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Create Event
        </NuxtLink>
      </div>

      <!-- Loading State -->
      <div v-if="eventStore.loading">
        <LoadingSpinner 
          size="lg" 
          color="secondary" 
          text="Loading events..." 
          :show-text="true"
        />
      </div>

      <!-- Error State -->
      <div v-else-if="eventStore.error" class="bg-red-50 border border-red-200 rounded-lg p-4">
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
        <h3 class="mt-2 text-sm font-medium text-gray-900">No events found</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating your first event.</p>
        <div class="mt-8">
          <NuxtLink
            to="/admin/add-event"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-zinc-900 hover:bg-zinc-800"
          >
            Create Event
          </NuxtLink>
        </div>
      </div>

      <!-- Events Grid -->
      <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="event in eventStore.events" :key="event.id" class="bg-white rounded-lg shadow-md overflow-hidden">
          <!-- Event Image -->
          <div v-if="event.poster_url" class="h-48 w-full bg-gray-200">
            <img :src="getImageUrl(event.poster_url)" :alt="event.title" class="h-full w-full object-cover">
          </div>
          <div v-else class="h-48 w-full bg-gray-200 flex items-center justify-center">
            <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>

          <!-- Event Content -->
          <div class="p-6">
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ event.title }}</h3>
            
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
              <div class="flex items-center justify-between mb-2">
                <span class="text-2xl font-bold text-gray-900">₱{{ typeof event.ticket_price === 'string' ? parseFloat(event.ticket_price).toFixed(2) : event.ticket_price.toFixed(2) }}</span>
              </div>
              <!-- Stock Badge -->
              <div v-if="event.max_tickets" class="flex items-center gap-2">
                <span class="text-sm text-gray-600">
                  {{ event.remaining_tickets || 0 }} / {{ event.max_tickets }} available
                </span>
                <span 
                  v-if="(event.remaining_tickets || 0) === 0"
                  class="text-xs font-medium px-2 py-1 rounded-full bg-red-100 text-red-700"
                >
                  Sold Out
                </span>
                <span 
                  v-else-if="(event.remaining_tickets || 0) <= 10"
                  class="text-xs font-medium px-2 py-1 rounded-full bg-orange-100 text-orange-700"
                >
                  Low Stock
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-2">
              <button
                @click="navigateTo(`/admin/edit-event/${event.id}`)"
                class="flex-1 bg-zinc-900 text-white px-4 py-2 rounded-lg hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2"
              >
                Edit
              </button>
              <button
                @click="deleteEvent(event.id)"
                class="px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
