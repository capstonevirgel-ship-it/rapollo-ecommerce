<script setup lang="ts">
import type { Event, EventComment } from '~/types'
import { getImageUrl } from '~/helpers/imageHelper'

const route = useRoute()
const eventStore = useEventStore()
const eventCommentStore = useEventCommentStore()
const ticketStore = useTicketStore()
const authStore = useAuthStore()
const { success, error } = useAlert()

// Event data
const event = ref<Event | null>(null)
const loading = ref(true)
const eventError = ref<string | null>(null)

// Booking modal state
const showBookingModal = ref(false)
const selectedQuantity = ref(1)
const paymentLoading = ref(false)
const paymentError = ref('')

// Comment state
const newComment = ref('')
const commentLoading = ref(false)
const editingCommentId = ref<number | null>(null)
const editingCommentText = ref('')

// Get event ID from query params
const eventId = computed(() => {
  const id = route.query.id
  return id ? parseInt(id as string) : null
})

// Fetch event details
const fetchEventDetails = async () => {
  if (!eventId.value) {
    eventError.value = 'Event not found'
    loading.value = false
    return
  }

  loading.value = true
  eventError.value = null

  try {
    await eventStore.fetchEvent(eventId.value)
    event.value = eventStore.currentEvent
    
    // Fetch comments for this event
    if (event.value) {
      await eventCommentStore.fetchComments(eventId.value)
    }
  } catch (err: any) {
    eventError.value = err.data?.message || 'Failed to fetch event details'
    console.error('Error fetching event:', err)
  } finally {
    loading.value = false
  }
}

// Format date and time
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

// Booking functions
const openBookingModal = () => {
  if (!authStore.isAuthenticated) {
    navigateTo('/login')
    return
  }
  
  selectedQuantity.value = 1
  showBookingModal.value = true
}

const closeBookingModal = () => {
  showBookingModal.value = false
  selectedQuantity.value = 1
  paymentError.value = ''
}

const proceedToPayment = async () => {
  if (!event.value) return
  
  // Validate ticket quantity
  if (selectedQuantity.value > 5) {
    error('Invalid Quantity', 'You can only purchase a maximum of 5 tickets at once. Please select 5 or fewer tickets.')
    return
  }
  
  paymentLoading.value = true
  paymentError.value = ''
  
  try {
    const paymentResponse = await ticketStore.createTicketPaymentIntent(
      event.value.id, 
      selectedQuantity.value
    )
    
    if (paymentResponse.checkout_url) {
      window.location.href = paymentResponse.checkout_url
    } else {
      throw new Error('No checkout URL received')
    }
    
  } catch (err: any) {
    paymentError.value = err.data?.message || 'Failed to create payment. Please try again.'
    error('Payment Error', paymentError.value)
  } finally {
    paymentLoading.value = false
  }
}

// Ticket availability functions
const getRemainingTickets = (event: Event) => {
  if (!event.max_tickets) return 0
  return event.max_tickets - (event.booked_tickets_count || 0)
}

const isEventFullyBooked = (event: Event) => {
  return getRemainingTickets(event) <= 0
}

const getTicketAvailabilityStatus = (event: Event) => {
  const remaining = getRemainingTickets(event)
  if (remaining === 0) return { status: 'sold-out', label: 'Sold Out', class: 'text-red-600 bg-red-50' }
  if (remaining <= 10) return { status: 'low', label: `Only ${remaining} left!`, class: 'text-orange-600 bg-orange-50' }
  return { status: 'available', label: `${remaining} tickets left`, class: 'text-green-600 bg-green-50' }
}

// Ticket quantity options
const ticketQuantityOptions = computed(() => {
  if (!event.value) return []
  
  const maxTickets = Math.min(5, getRemainingTickets(event.value))
  const options = []
  
  for (let i = 1; i <= maxTickets; i++) {
    options.push({
      value: i,
      label: `${i} ticket${i > 1 ? 's' : ''}`
    })
  }
  
  return options
})

const canBookTickets = (event: Event) => {
  return event.ticket_price && event.max_tickets && !isEventFullyBooked(event)
}

// Comment functions
const submitComment = async () => {
  if (!newComment.value.trim() || !eventId.value) return
  
  commentLoading.value = true
  try {
    await eventCommentStore.addComment(eventId.value, newComment.value.trim())
    newComment.value = ''
    success('Comment added successfully!')
  } catch (err: any) {
    error('Failed to add comment', err.data?.message || 'Please try again')
  } finally {
    commentLoading.value = false
  }
}

const startEditComment = (comment: EventComment) => {
  editingCommentId.value = comment.id
  editingCommentText.value = comment.comment
}

const cancelEditComment = () => {
  editingCommentId.value = null
  editingCommentText.value = ''
}

const updateComment = async (commentId: number) => {
  if (!editingCommentText.value.trim() || !eventId.value) return
  
  commentLoading.value = true
  try {
    await eventCommentStore.updateComment(eventId.value, commentId, editingCommentText.value.trim())
    editingCommentId.value = null
    editingCommentText.value = ''
    success('Comment updated successfully!')
  } catch (err: any) {
    error('Failed to update comment', err.data?.message || 'Please try again')
  } finally {
    commentLoading.value = false
  }
}

const deleteComment = async (commentId: number) => {
  if (!confirm('Are you sure you want to delete this comment?') || !eventId.value) return
  
  commentLoading.value = true
  try {
    await eventCommentStore.deleteComment(eventId.value, commentId)
    success('Comment deleted successfully!')
  } catch (err: any) {
    error('Failed to delete comment', err.data?.message || 'Please try again')
  } finally {
    commentLoading.value = false
  }
}

const formatCommentDate = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))
  
  if (diffInHours < 1) return 'Just now'
  if (diffInHours < 24) return `${diffInHours}h ago`
  if (diffInHours < 168) return `${Math.floor(diffInHours / 24)}d ago`
  
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
  })
}

// Set page title and meta
const setPageMeta = () => {
  if (event.value) {
    useHead({
      title: `${event.value.title} | RAPOLLO`,
      meta: [
        { name: 'description', content: event.value.description || `Join us for ${event.value.title} - ${formatDate(event.value.date)} at ${event.value.location || 'TBA'}` }
      ]
    })
  }
}

// Fetch event on mount
onMounted(async () => {
  await fetchEventDetails()
  if (event.value) {
    setPageMeta()
  }
})

// Watch for event changes to update meta
watch(event, (newEvent) => {
  if (newEvent) {
    setPageMeta()
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-zinc-900"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="eventError" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <svg class="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <h3 class="text-lg font-medium text-red-800 mb-2">Event Not Found</h3>
        <p class="text-red-600 mb-4">{{ eventError }}</p>
        <NuxtLink 
          to="/events" 
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-zinc-900 hover:bg-zinc-800"
        >
          Back to Events
        </NuxtLink>
      </div>
    </div>

    <!-- Event Details - Ticket Design -->
    <div v-else-if="event" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Back Button -->
      <div class="mb-6">
        <NuxtLink 
          to="/events" 
          class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Events
        </NuxtLink>
      </div>

      <!-- Main Ticket Container -->
      <div class="relative">
        <!-- Ticket Background with Perforated Edges -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden relative">
          <!-- Perforated Edge Effect -->
          <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
          <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
          
          <!-- Ticket Header with Event Image -->
          <div class="relative h-64 md:h-96 bg-gradient-to-r from-zinc-900 to-zinc-700">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
              <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent"></div>
            </div>
            
            <!-- Event Image Overlay -->
            <div v-if="event.poster_url" class="absolute inset-0">
          <img 
            :src="getImageUrl(event.poster_url)" 
            :alt="event.title" 
                class="h-full w-full object-cover opacity-80"
            @error="($event.target as HTMLImageElement).src = '/placeholder.png'"
          >
        </div>
            
            <!-- Event Title Overlay -->
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center p-6">
              <div class="text-center">
                <h1 class="text-3xl md:text-5xl font-winner-extra-bold text-white mb-4 drop-shadow-lg">
                  {{ event.title }}
                </h1>
                <div class="text-lg text-white/90 font-medium">
                  {{ formatDate(event.date) }} • {{ formatTime(event.date) }}
                </div>
                <div v-if="event.location" class="text-sm text-white/80 mt-2">
                  📍 {{ event.location }}
                </div>
              </div>
            </div>
            
            <!-- Ticket Corner Decorations -->
            <div class="absolute top-4 left-4 w-8 h-8 border-2 border-white/30 rounded-full"></div>
            <div class="absolute top-4 right-4 w-8 h-8 border-2 border-white/30 rounded-full"></div>
            <div class="absolute bottom-4 left-4 w-8 h-8 border-2 border-white/30 rounded-full"></div>
            <div class="absolute bottom-4 right-4 w-8 h-8 border-2 border-white/30 rounded-full"></div>
          </div>

          <!-- Ticket Body -->
          <div class="p-6 md:p-8">
            <!-- Event Description -->
            <div v-if="event.description" class="mb-8">
              <h2 class="text-2xl font-winner-extra-bold text-gray-900 mb-4">About This Event</h2>
              <div class="prose max-w-none text-gray-600 leading-relaxed">
                {{ event.description }}
              </div>
            </div>

            <!-- Ticket Information Card -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8 border border-gray-200">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                  <div class="text-4xl font-bold text-gray-900 mb-2">
                    ₱{{ typeof event.ticket_price === 'string' ? parseFloat(event.ticket_price).toFixed(2) : event.ticket_price.toFixed(2) }}
                  </div>
                  <div class="text-sm text-gray-600">Per ticket</div>
                </div>
                
                <!-- Availability Badge -->
                <div v-if="event.max_tickets" class="mb-4 md:mb-0">
                  <span :class="['text-sm font-medium px-4 py-2 rounded-full', getTicketAvailabilityStatus(event).class]">
                    <Icon v-if="getTicketAvailabilityStatus(event).status === 'sold-out'" name="mdi:close-circle" class="inline-block mr-1" />
                    <Icon v-else-if="getTicketAvailabilityStatus(event).status === 'low'" name="mdi:alert-circle" class="inline-block mr-1" />
                    <Icon v-else name="mdi:check-circle" class="inline-block mr-1" />
                    {{ getTicketAvailabilityStatus(event).label }}
                  </span>
                </div>
            </div>
          </div>

          <!-- Action Buttons -->
            <div class="flex justify-end mb-8">
            <button
              v-if="canBookTickets(event)"
              @click="openBookingModal"
                class="bg-zinc-900 text-white px-6 py-3 rounded-lg font-semibold hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 transition-colors"
            >
              Book Tickets Now
            </button>
            <button
              v-else-if="isEventFullyBooked(event)"
              disabled
                class="bg-gray-300 text-gray-500 px-6 py-3 rounded-lg font-semibold cursor-not-allowed"
            >
              Sold Out
            </button>
            <button
              v-else
              disabled
                class="bg-gray-300 text-gray-500 px-6 py-3 rounded-lg font-semibold cursor-not-allowed"
            >
              No Tickets Available
            </button>
            </div>
          </div>
        </div>

        <!-- Comments Section -->
        <div class="mt-12 bg-white rounded-lg shadow-sm p-8">
          <h2 class="text-2xl font-winner-extra-bold text-gray-900 mb-6">Comments</h2>
          
          <!-- Add Comment Form (Authenticated Users Only) -->
          <div v-if="authStore.isAuthenticated" class="mb-8">
            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Share your thoughts</h3>
              <div class="space-y-4">
                <textarea
                  v-model="newComment"
                  placeholder="Write your comment here..."
                  rows="3"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-500 focus:border-transparent resize-none"
                  :disabled="commentLoading"
                ></textarea>
                <div class="flex justify-end">
                  <button
                    @click="submitComment"
                    :disabled="!newComment.trim() || commentLoading"
                    class="px-6 py-2 bg-zinc-900 text-white rounded-lg font-medium hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <span v-if="commentLoading">Posting...</span>
                    <span v-else>Post Comment</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Login Prompt for Non-Authenticated Users -->
          <div v-else class="mb-8 bg-blue-50 rounded-lg p-6 border border-blue-200">
            <div class="text-center">
              <svg class="w-12 h-12 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              <h3 class="text-lg font-semibold text-blue-900 mb-2">Join the Conversation</h3>
              <p class="text-blue-700 mb-4">Sign in to share your thoughts about this event</p>
              <NuxtLink 
                to="/login" 
                class="inline-flex items-center px-6 py-2 bg-zinc-900 text-white rounded-lg font-medium hover:bg-zinc-800 transition-colors"
              >
                Sign In
              </NuxtLink>
            </div>
          </div>

          <!-- Comments List -->
          <div v-if="eventCommentStore.comments.length > 0" class="space-y-6">
            <div 
              v-for="comment in eventCommentStore.comments" 
              :key="comment.id"
              class="bg-gray-50 rounded-lg p-6 border border-gray-200"
            >
              <!-- Comment Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-zinc-300 rounded-full flex items-center justify-center text-zinc-800 font-semibold">
                    {{ comment.user?.user_name?.charAt(0).toUpperCase() || 'U' }}
                  </div>
                  <div>
                    <div class="font-semibold text-gray-900">{{ comment.user?.user_name || 'Anonymous' }}</div>
                    <div class="text-sm text-gray-500">{{ formatCommentDate(comment.created_at) }}</div>
                  </div>
                </div>
                
                <!-- Comment Actions (Only for comment author) -->
                <div v-if="authStore.isAuthenticated && comment.user_id === authStore.user?.id" class="flex space-x-2">
                  <button
                    v-if="editingCommentId !== comment.id"
                    @click="startEditComment(comment)"
                    class="text-gray-400 hover:text-zinc-600 transition-colors"
                    title="Edit comment"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    v-if="editingCommentId !== comment.id"
                    @click="deleteComment(comment.id)"
                    class="text-gray-400 hover:text-red-600 transition-colors"
                    title="Delete comment"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Comment Content -->
              <div v-if="editingCommentId !== comment.id" class="text-gray-700 leading-relaxed">
                {{ comment.comment }}
              </div>

              <!-- Edit Comment Form -->
              <div v-else class="space-y-3">
                <textarea
                  v-model="editingCommentText"
                  rows="3"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-500 focus:border-transparent resize-none"
                ></textarea>
                <div class="flex justify-end space-x-2">
                  <button
                    @click="cancelEditComment"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
                  >
                    Cancel
                  </button>
                  <button
                    @click="updateComment(comment.id)"
                    :disabled="!editingCommentText.trim() || commentLoading"
                    class="px-4 py-2 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    Update
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty Comments State -->
          <div v-else class="text-center py-12">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No comments yet</h3>
            <p class="text-gray-500">Be the first to share your thoughts about this event!</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Booking Dialog -->
    <Dialog 
      v-model="showBookingModal" 
      title="Book Tickets"
      width="500px"
    >
      <div v-if="event" class="space-y-6">
        <!-- Event Summary -->
        <div class="text-center">
          <h3 class="text-xl font-bold text-gray-900 mb-2">{{ event.title }}</h3>
          <div class="text-sm text-gray-600 space-y-1">
            <p>{{ formatDate(event.date) }} at {{ formatTime(event.date) }}</p>
            <p v-if="event.location">{{ event.location }}</p>
            <p class="text-zinc-600 font-medium">{{ getRemainingTickets(event) }} tickets remaining</p>
          </div>
        </div>

        <!-- Ticket Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Number of Tickets</label>
          <Select
            v-model="selectedQuantity"
            :options="ticketQuantityOptions"
            placeholder="Select quantity"
            size="md"
            variant="outline"
          />
        </div>

        <!-- Price Summary -->
        <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-200">
          <div class="text-2xl font-bold text-gray-900">
            ₱{{ (parseFloat(event.ticket_price!.toString()) * selectedQuantity).toFixed(2) }}
          </div>
          <p class="text-sm text-gray-600">{{ selectedQuantity }} × ₱{{ event.ticket_price }} per ticket</p>
        </div>

        <!-- Error Message -->
        <div v-if="paymentError" class="bg-red-50 border border-red-200 rounded-lg p-3">
          <p class="text-sm text-red-600">{{ paymentError }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3">
          <button
            @click="closeBookingModal"
            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
          >
            Cancel
          </button>
          <LoadingButton
            :loading="paymentLoading"
            :disabled="getRemainingTickets(event) === 0 || paymentLoading"
            loading-text="Processing..."
            :normal-text="getRemainingTickets(event) === 0 ? 'Sold Out' : 'Book Now'"
            variant="primary"
            size="md"
            class="flex-1"
            @click="proceedToPayment"
          />
        </div>
      </div>
    </Dialog>
  </div>
</template>

