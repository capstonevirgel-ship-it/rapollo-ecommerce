<script setup lang="ts">
import type { Event } from '~/types'
import { getImageUrl } from '~/helpers/imageHelper'
import { useTaxStore } from '~/stores/tax'

// Function to fix image URLs in HTML content
const fixContentImageUrls = (html: string): string => {
  if (!html) return html
  
  // Replace image URLs that don't have the port or have wrong format
  return html.replace(
    /src="(http:\/\/localhost\/storage\/|http:\/\/localhost:8000\/storage\/|\/storage\/)([^"]+)"/g,
    (match, prefix, path) => {
      // Extract just the path without /storage/ prefix
      const cleanPath = path.startsWith('/') ? path.slice(1) : path
      return `src="${getImageUrl(cleanPath)}"`
    }
  )
}

// Use admin layout
definePageMeta({
  layout: 'admin'
})

// Get route params - use computed to ensure reactivity
const route = useRoute()
const eventIdParam = computed(() => {
  // Nuxt 3 converts [event-id] to camelCase: eventid (removes hyphen)
  const paramValue = route.params.eventid || route.params['event-id'] || route.params.eventId || route.params.id
  
  if (!paramValue) {
    console.error('Route parameter not found. Available params:', Object.keys(route.params), route.params)
    return null
  }
  
  // Handle array case (Nuxt can return arrays for route params)
  const id = Array.isArray(paramValue) ? paramValue[0] : paramValue
  
  if (!id) {
    return null
  }
  
  const parsed = parseInt(String(id))
  if (isNaN(parsed)) {
    console.error('Failed to parse event ID:', id, typeof id)
    return null
  }
  
  return parsed
})

// Set page title
useHead({
  title: 'Edit Event - Admin | monogram',
  meta: [
    { name: 'description', content: 'Edit event details in your monogram E-commerce store.' }
  ]
})

const eventStore = useEventStore()
const authStore = useAuthStore()
const taxStore = useTaxStore()
const { success, error } = useAlert()

// Redirect if not admin
if (!authStore.isAuthenticated || authStore.user?.role !== 'admin') {
  await navigateTo('/login')
}

// Loading state
const isLoading = ref(true)

// Form data
const formData = ref({
  title: '',
  content: '',
  date: '',
  time: '',
  location: '',
  base_ticket_price: '',
  max_tickets: ''
})

// Image handling
const posterFile = ref<File | null>(null)
const posterPreview = ref<string | null>(null)
const existingPosterUrl = ref<string | null>(null)
const isUploadingImage = ref(false)
const isSubmitting = ref(false)

// Computed function to calculate final ticket price with tax
const getFinalTicketPrice = (basePrice: number | string) => {
  const price = typeof basePrice === 'string' ? parseFloat(basePrice) : basePrice
  if (price <= 0) return 0
  const totalTaxRate = taxStore.totalTaxRate
  return price * (1 + totalTaxRate / 100)
}

// Get poster image URL for display
const getPosterImageUrl = (): string | null => {
  if (posterPreview.value) return posterPreview.value
  if (existingPosterUrl.value) return getImageUrl(existingPosterUrl.value)
  return null
}

// Computed preview data
const previewEvent = computed(() => {
  const basePrice = formData.value.base_ticket_price ? parseFloat(formData.value.base_ticket_price) : 0
  const finalPrice = getFinalTicketPrice(basePrice)
  
  // Combine date and time for preview
  let dateTime = new Date().toISOString()
  if (formData.value.date) {
    if (formData.value.time) {
      dateTime = `${formData.value.date}T${formData.value.time}:00`
    } else {
      dateTime = `${formData.value.date}T12:00:00`
    }
  }
  
  return {
    title: formData.value.title || 'Event Title',
    content: fixContentImageUrls(formData.value.content || ''),
    date: dateTime,
    location: formData.value.location || '',
    poster_url: getPosterImageUrl(),
    ticket_price: finalPrice,
    max_tickets: formData.value.max_tickets ? parseInt(formData.value.max_tickets) : null
  }
})

// Handle poster image file selection
const handlePosterChange = (event: any) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    posterFile.value = target.files[0]
    const reader = new FileReader()
    reader.onload = (e) => {
      posterPreview.value = e.target?.result as string
    }
    reader.readAsDataURL(target.files[0])
  } else {
    posterFile.value = null
    posterPreview.value = null
  }
}

// Format date and time for preview
const formatDate = (dateString: string) => {
  if (!dateString) return 'TBA'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTime = (dateString: string) => {
  if (!dateString) return 'TBA'
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Load event data
const loadEvent = async () => {
  const id = eventIdParam.value
  if (!id) {
    error('Invalid Event ID', 'The event ID is invalid')
    navigateTo('/admin/events')
    return
  }
  
  try {
    isLoading.value = true
    const event = await eventStore.fetchEvent(id)
    
    // Parse date and time from event.date
    const eventDate = new Date(event.date)
    const dateStr = eventDate.toISOString().split('T')[0]
    const timeStr = eventDate.toTimeString().split(' ')[0].slice(0, 5) // HH:MM format
    
    formData.value = {
      title: event.title,
      content: (event.content ?? '') as string,
      date: dateStr,
      time: timeStr,
      location: event.location || '',
      base_ticket_price: (event as any).base_ticket_price?.toString() || event.ticket_price?.toString() || '',
      max_tickets: event.max_tickets?.toString() || ''
    }
    
    // Set existing poster URL
    if (event.poster_url) {
      existingPosterUrl.value = event.poster_url
    }
  } catch (err: any) {
    error('Failed to load event', err.data?.message || 'Event not found')
    console.error('Error loading event:', err)
    navigateTo('/admin/events')
  } finally {
    isLoading.value = false
  }
}

// Update event
const updateEvent = async () => {
  if (!formData.value.title || !formData.value.date || !formData.value.time) {
    error('Validation Error', 'Please fill in all required fields (Title, Date, and Time)')
    return
  }

  try {
    isSubmitting.value = true
    isUploadingImage.value = true
    
    // Combine date and time
    const dateTime = `${formData.value.date} ${formData.value.time}:00`
    
    const eventData = new FormData()
    eventData.append('title', formData.value.title)
    // Always append content, even if empty, to ensure it's sent
    eventData.append('content', formData.value.content || '')
    eventData.append('date', dateTime)
    eventData.append('location', formData.value.location || '')
    
    if (posterFile.value) {
      eventData.append('poster_image', posterFile.value)
    } else if (existingPosterUrl.value) {
      eventData.append('poster_url', existingPosterUrl.value)
    }
    
    // Always append base_ticket_price and max_tickets, even if empty
    eventData.append('base_ticket_price', formData.value.base_ticket_price || '')
    eventData.append('max_tickets', formData.value.max_tickets || '')
    
    const id = eventIdParam.value
    if (!id) {
      error('Invalid Event ID', 'The event ID is invalid')
      return
    }
    
    await eventStore.updateEvent(id, eventData as any)
    success('Event updated successfully!')
    navigateTo('/admin/events')
  } catch (err: any) {
    error('Failed to update event', err.data?.message || 'Please try again')
    console.error('Error updating event:', err)
  } finally {
    isSubmitting.value = false
    isUploadingImage.value = false
  }
}

// Fetch tax prices and event data on mount
onMounted(async () => {
  taxStore.fetchTaxPrices()
  await loadEvent()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Event</h1>
            <p class="text-sm sm:text-base text-gray-600 mt-1">Update the details below to modify this event</p>
          </div>
          <NuxtLink
            to="/admin/events"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
          >
            <Icon name="mdi:arrow-left" class="w-4 h-4 mr-2" />
            Back to Events
          </NuxtLink>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center items-center py-12">
        <LoadingSpinner 
          size="lg" 
          color="secondary" 
          text="Loading event..." 
          :show-text="true"
        />
      </div>

      <div v-else class="space-y-8">
        <!-- Form Section -->
        <div class="space-y-6">
          <form @submit.prevent="updateEvent" class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
              
              <div class="space-y-4">
                <!-- Title -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Event Title *</label>
                  <input
                    v-model="formData.title"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    placeholder="Enter event title"
                  >
                </div>

                <!-- Date and Time -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                    <input
                      v-model="formData.date"
                      type="date"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    >
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time *</label>
                    <input
                      v-model="formData.time"
                      type="time"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    >
                  </div>
                </div>

                <!-- Location -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                  <input
                    v-model="formData.location"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    placeholder="Enter event location"
                  >
                </div>
              </div>
            </div>

            <!-- Poster Image -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Event Poster</h2>
              
              <div class="space-y-4">
                <!-- Image Preview -->
                <div v-if="getPosterImageUrl()" class="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden border-2 border-gray-200">
                  <img 
                    :src="getPosterImageUrl() || ''" 
                    alt="Event poster preview" 
                    class="w-full h-full object-cover" 
                  />
                </div>
                <div v-else class="w-full h-48 bg-gray-50 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300">
                  <Icon name="mdi:image-outline" class="text-4xl text-gray-400" />
                </div>
                
                <!-- File Input -->
                <div>
                  <input
                    type="file"
                    @change="handlePosterChange"
                    accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-900 file:text-white hover:file:bg-zinc-800"
                  />
                  <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF, SVG up to 5MB. Recommended: 16:9 aspect ratio.</p>
                  <p v-if="existingPosterUrl && !posterFile" class="mt-1 text-xs text-gray-400">Leave empty to keep current poster image.</p>
                </div>
              </div>
            </div>

            <!-- Content -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Event Content</h2>
              <RichTextEditor
                v-model="formData.content"
                placeholder="Write about your event..."
              />
            </div>

            <!-- Ticket Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Ticket Information</h2>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Base Ticket Price (Before Tax) (₱)</label>
                  <input
                    v-model="formData.base_ticket_price"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    placeholder="0.00"
                  >
                  <p v-if="formData.base_ticket_price" class="mt-1 text-xs text-gray-500">
                    Final price: ₱{{ getFinalTicketPrice(formData.base_ticket_price).toFixed(2) }}
                  </p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Max Tickets</label>
                  <input
                    v-model="formData.max_tickets"
                    type="number"
                    min="1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    placeholder="100"
                  >
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="isSubmitting || isUploadingImage"
                class="px-6 py-2 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ (isSubmitting || isUploadingImage) ? 'Updating...' : 'Update Event' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Preview Section -->
        <div class="space-y-6">
          <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">Preview</h2>
            <p class="text-sm sm:text-base text-gray-600 mt-1">This is how your event will appear to visitors</p>
          </div>

          <!-- Ticket Preview -->
          <div class="relative">
            <div class="bg-white border border-gray-200 rounded-lg shadow overflow-visible">
              <div class="flex flex-col lg:flex-row">
                <!-- Ticket Hero -->
                <div class="relative lg:w-2/5">
                  <div class="h-56 lg:h-80">
                    <img
                      v-if="previewEvent.poster_url"
                      :src="previewEvent.poster_url"
                      :alt="previewEvent.title"
                      class="h-full w-full object-cover"
                    >
                    <div v-else class="h-full w-full bg-gradient-to-br from-zinc-900 to-zinc-700 flex items-center justify-center">
                      <Icon name="mdi:ticket-confirmation" class="text-white/80 w-16 h-16" />
                    </div>
                  </div>
                  <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-black/10 to-transparent"></div>
                </div>

                <!-- Ticket Details -->
                <div class="flex-1 p-6 lg:p-8 flex flex-col gap-6">
                  <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                    <div class="space-y-2">
                      <h1 class="text-2xl md:text-3xl font-poppins text-gray-900 leading-tight">
                        {{ previewEvent.title }}
                      </h1>
                      <div v-if="previewEvent.location" class="text-sm font-medium text-gray-600 lg:hidden">
                        {{ previewEvent.location }}
                      </div>
                    </div>
                    <div class="hidden lg:block text-right">
                      <div v-if="previewEvent.location" class="text-sm font-medium text-gray-700">{{ previewEvent.location }}</div>
                    </div>
                  </div>
                  <div class="mt-4">
                    <div class="flex flex-wrap gap-3">
                      <div class="px-5 py-2 rounded-full border border-zinc-900 text-sm font-semibold text-zinc-900 uppercase tracking-wide">
                        {{ formatDate(previewEvent.date) }}
                      </div>
                      <div class="px-5 py-2 rounded-full border border-zinc-900 text-sm font-semibold text-zinc-900 uppercase tracking-wide">
                        {{ formatTime(previewEvent.date) }}
                      </div>
                      <div v-if="previewEvent.ticket_price > 0" class="px-5 py-2 rounded-full border border-zinc-900 text-sm font-semibold text-zinc-900 uppercase tracking-wide">
                        Price: ₱{{ previewEvent.ticket_price.toFixed(2) }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Ticket Stub -->
                <div class="relative lg:w-56 bg-gray-50 flex flex-col items-center px-6 py-6 border-dashed border-gray-300 lg:border-l-2 lg:border-t-0 border-t-2 lg:border-t-transparent">
                  <!-- Tear Notches - Desktop -->
                  <div class="absolute top-0 left-0 hidden lg:flex -translate-x-1/2 -translate-y-1/2">
                    <div class="w-12 h-12 bg-gray-50 border-2 border-gray-200 rounded-full [clip-path:inset(22px_0_0_0)]"></div>
                  </div>
                  <div class="absolute bottom-0 left-0 hidden lg:flex -translate-x-1/2 translate-y-1/2">
                    <div class="w-12 h-12 bg-gray-50 border-2 border-gray-200 rounded-full [clip-path:inset(0_0_20px_0)]"></div>
                  </div>
                  <!-- Tear Notches - Mobile -->
                  <div class="absolute lg:hidden left-0 top-0 -translate-x-1/2 -translate-y-1/2">
                    <div class="w-12 h-12 bg-gray-50 border-2 border-gray-200 rounded-full [clip-path:inset(0_0_0_22px)]"></div>
                  </div>
                  <div class="absolute lg:hidden right-0 top-0 translate-x-1/2 -translate-y-1/2">
                    <div class="w-12 h-12 bg-gray-50 border-2 border-gray-200 rounded-full [clip-path:inset(0_22px_0_0)]"></div>
                  </div>
                  <div class="flex flex-col items-center justify-center h-full w-full">
                    <span class="text-xs font-poppins tracking-[0.45em] text-gray-900 uppercase text-center whitespace-nowrap lg:-rotate-90 lg:origin-center lg:translate-y-[3.75rem]">
                      YOUR TICKET NUMBER
                    </span>
                    <div class="mt-4 w-full h-px bg-[repeating-linear-gradient(90deg,#111111 0px,#111111 2px,transparent 2px,transparent 4px)] lg:h-24 lg:w-12 lg:bg-[repeating-linear-gradient(90deg,#111111 0px,#111111 2px,transparent 2px,transparent 4px)] lg:mt-6"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- About This Event Preview -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            <h2 class="text-2xl font-poppins text-gray-900 mb-4">About This Event</h2>
            <div v-if="previewEvent.content" class="event-content-preview text-gray-600 leading-relaxed" v-html="previewEvent.content">
            </div>
            <p v-else class="text-gray-600 leading-relaxed">
              Stay tuned for more details about this event. Check back soon for the full experience breakdown.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.event-content-preview :deep(h1) {
  font-size: 2em;
  font-weight: bold;
  margin-top: 0.67em;
  margin-bottom: 0.67em;
  color: #111827;
}

.event-content-preview :deep(h2) {
  font-size: 1.5em;
  font-weight: bold;
  margin-top: 0.83em;
  margin-bottom: 0.83em;
  color: #111827;
}

.event-content-preview :deep(h3) {
  font-size: 1.17em;
  font-weight: bold;
  margin-top: 1em;
  margin-bottom: 1em;
  color: #111827;
}

.event-content-preview :deep(strong),
.event-content-preview :deep(b) {
  font-weight: bold;
}

.event-content-preview :deep(em),
.event-content-preview :deep(i) {
  font-style: italic;
}

.event-content-preview :deep(ul),
.event-content-preview :deep(ol) {
  padding-left: 1.5em;
  margin: 1em 0;
}

.event-content-preview :deep(ul) {
  list-style-type: disc;
}

.event-content-preview :deep(ol) {
  list-style-type: decimal;
}

.event-content-preview :deep(li) {
  margin: 0.5em 0;
}

.event-content-preview :deep(p) {
  margin: 1em 0;
}

.event-content-preview :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: 0.375rem;
  margin: 1rem 0;
  display: block;
}

.event-content-preview :deep([style*="text-align: left"]) {
  text-align: left;
}

.event-content-preview :deep([style*="text-align: center"]) {
  text-align: center;
}

.event-content-preview :deep([style*="text-align: right"]) {
  text-align: right;
}

.event-content-preview :deep([style*="text-align: justify"]) {
  text-align: justify;
}
</style>

