<script setup lang="ts">
import type { Event } from '~/types'

// Use admin layout
definePageMeta({
  layout: 'admin'
})

const eventStore = useEventStore()
const authStore = useAuthStore()

// Redirect if not admin
if (!authStore.isAuthenticated || authStore.user?.role !== 'admin') {
  await navigateTo('/login')
}

// Fetch events on client side
onMounted(() => {
  eventStore.fetchEvents()
})

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingEvent = ref<Event | null>(null)
const formData = ref({
  title: '',
  description: '',
  date: '',
  location: '',
  poster_url: '',
  ticket_price: '',
  max_tickets: ''
})

const resetForm = () => {
  formData.value = {
    title: '',
    description: '',
    date: '',
    location: '',
    poster_url: '',
    ticket_price: '',
    max_tickets: ''
  }
}

const openCreateModal = () => {
  resetForm()
  showCreateModal.value = true
}

const openEditModal = (event: Event) => {
  editingEvent.value = event
  formData.value = {
    title: event.title,
    description: event.description || '',
    date: event.date.split('T')[0], // Convert to date input format
    location: event.location || '',
    poster_url: event.poster_url || '',
    ticket_price: event.ticket_price?.toString() || '',
    max_tickets: event.max_tickets?.toString() || ''
  }
  showEditModal.value = true
}

const closeModals = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingEvent.value = null
  resetForm()
}

const createEvent = async () => {
  try {
    const eventData = {
      ...formData.value,
      ticket_price: formData.value.ticket_price ? parseFloat(formData.value.ticket_price) : null,
      max_tickets: formData.value.max_tickets ? parseInt(formData.value.max_tickets) : null
    }
    
    await eventStore.createEvent(eventData)
    closeModals()
  } catch (error) {
    console.error('Error creating event:', error)
  }
}

const updateEvent = async () => {
  if (!editingEvent.value) return
  
  try {
    const eventData = {
      ...formData.value,
      ticket_price: formData.value.ticket_price ? parseFloat(formData.value.ticket_price) : null,
      max_tickets: formData.value.max_tickets ? parseInt(formData.value.max_tickets) : null
    }
    
    await eventStore.updateEvent(editingEvent.value.id, eventData)
    closeModals()
  } catch (error) {
    console.error('Error updating event:', error)
  }
}

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
  <div class="p-4">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Manage Events</h1>
            <p class="mt-2 text-gray-600">Create and manage events</p>
          </div>
          <button
            @click="openCreateModal"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500"
          >
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Create Event
          </button>
        </div>
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
        <h3 class="mt-2 text-sm font-medium text-gray-900">No events found</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating your first event.</p>
        <div class="mt-6">
          <button
            @click="openCreateModal"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-zinc-900 hover:bg-zinc-800"
          >
            Create Event
          </button>
        </div>
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
                <span class="text-2xl font-bold text-gray-900">${{ typeof event.ticket_price === 'string' ? parseFloat(event.ticket_price).toFixed(2) : event.ticket_price.toFixed(2) }}</span>
                <span v-if="event.max_tickets" class="text-sm text-gray-500">
                  {{ event.remaining_tickets || 0 }} / {{ event.max_tickets }} tickets
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-2">
              <button
                @click="openEditModal(event)"
                class="flex-1 bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2"
              >
                Edit
              </button>
              <button
                @click="deleteEvent(event.id)"
                class="px-4 py-2 border border-red-300 text-red-700 rounded-md hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ showCreateModal ? 'Create Event' : 'Edit Event' }}
          </h3>
          
          <form @submit.prevent="showCreateModal ? createEvent() : updateEvent()" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Event Title</label>
                <input
                  v-model="formData.title"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter event title"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input
                  v-model="formData.date"
                  type="date"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                v-model="formData.description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter event description"
              ></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input
                  v-model="formData.location"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter event location"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Poster URL</label>
                <input
                  v-model="formData.poster_url"
                  type="url"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter poster image URL"
                >
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ticket Price ($)</label>
                <input
                  v-model="formData.ticket_price"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="0.00"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Tickets</label>
                <input
                  v-model="formData.max_tickets"
                  type="number"
                  min="1"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="100"
                >
              </div>
            </div>

            <div class="flex space-x-3 pt-4">
              <button
                type="button"
                @click="closeModals"
                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="eventStore.loading"
                class="flex-1 bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 disabled:opacity-50"
              >
                {{ eventStore.loading ? 'Saving...' : (showCreateModal ? 'Create Event' : 'Update Event') }}
              </button>
            </div>
          </form>
        </div>
      </div>
  </div>
</template>
