<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { getImageUrl } from '~/utils/imageHelper'
import type { Event } from '~/types'
import EventSkeleton from '~/components/skeleton/EventSkeleton.vue'

interface Props {
  events: Event[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const currentIndex = ref(0)
const isHovered = ref(false)

let intervalId: ReturnType<typeof setInterval> | null = null

const setImage = (index: number) => {
  currentIndex.value = index
}

const startAutoplay = () => {
  // Clear existing interval
  if (intervalId) {
    clearInterval(intervalId)
  }
  
  // Start new interval if we have events
  if (props.events.length > 1) {
    intervalId = setInterval(() => {
      currentIndex.value = (currentIndex.value + 1) % props.events.length
    }, 5000)
  }
}

const stopAutoplay = () => {
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPrice = (price: string | number | undefined) => {
  if (!price) return 'Free'
  const numPrice = typeof price === 'string' ? parseFloat(price) : Number(price)
  return isNaN(numPrice) ? 'Free' : `₱${numPrice.toFixed(2)}`
}

// Generate slug from event title
const generateSlug = (title: string) => {
  return title
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
    .replace(/\s+/g, '-') // Replace spaces with hyphens
    .replace(/-+/g, '-') // Replace multiple hyphens with single hyphen
    .trim()
}

const handleEventClick = (event: Event) => {
  const slug = generateSlug(event.title)
  navigateTo(`/events/${slug}?id=${event.id}`)
}

// Watch for changes in events array
watch(() => props.events, (newEvents) => {
  if (newEvents && newEvents.length > 0) {
    currentIndex.value = 0 // Reset to first event
    startAutoplay()
  } else {
    stopAutoplay()
  }
}, { immediate: true })

// Watch for loading state changes
watch(() => props.loading, (isLoading) => {
  if (isLoading) {
    stopAutoplay()
  } else if (props.events.length > 0) {
    startAutoplay()
  }
})

onMounted(() => {
  if (props.events.length > 0) {
    startAutoplay()
  }
})

onUnmounted(() => {
  stopAutoplay()
})
</script>

<template>
  <div class="max-w-6xl mx-auto relative">
    <!-- Loading State -->
    <EventSkeleton v-if="loading" />

    <!-- No Events State -->
    <div v-else-if="events.length === 0" class="text-center py-12">
      <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No Events Available</h3>
      <p class="text-gray-500">Check back later for upcoming events!</p>
    </div>

    <!-- Events Gallery -->
    <div v-else class="flex flex-col lg:flex-row gap-8 items-start">
      <!-- Main Event Display -->
      <div
        class="relative w-full h-[400px] rounded-2xl overflow-hidden shadow-lg group cursor-pointer"
        @mouseenter="() => { isHovered = true; stopAutoplay() }"
        @mouseleave="() => { isHovered = false; startAutoplay() }"
        @click="handleEventClick(events[currentIndex])"
      >
        <img
          :src="getImageUrl(events[currentIndex].poster_url || null, 'event')"
          :alt="events[currentIndex].title"
          class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-105"
        />

        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

        <!-- Event Info -->
        <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
          <div class="transform transition-all duration-300" :class="isHovered ? 'translate-y-0' : 'translate-y-2'">
            <h3 class="text-2xl font-bold mb-2">{{ events[currentIndex].title }}</h3>
            <p class="text-gray-200 mb-3 line-clamp-2">{{ events[currentIndex].description }}</p>
            
            <!-- Event Details -->
            <div class="flex flex-wrap gap-4 text-sm mb-4">
              <div class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ formatDate(events[currentIndex].date) }}</span>
              </div>
              <div v-if="events[currentIndex].location" class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ events[currentIndex].location }}</span>
              </div>
              <div class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
                <span>{{ formatPrice(events[currentIndex].ticket_price) }}</span>
              </div>
            </div>

            <!-- CTA Button -->
            <button class="w-full py-3 bg-white text-black font-medium rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
              View Event Details
            </button>
          </div>
        </div>
      </div>

      <!-- Event Thumbnails -->
      <div class="flex flex-row lg:flex-col gap-3 w-full lg:w-[140px] lg:h-[400px] h-[150px]">
        <div
          v-for="(event, idx) in events.slice(0, 4)"
          :key="event.id"
          @click="setImage(idx)"
          :class="[
            'relative w-full h-auto lg:h-[calc(25%-9px)] rounded-lg overflow-hidden cursor-pointer transition-all duration-300 group',
            currentIndex === idx 
              ? 'ring-2 ring-gray-900 scale-105 shadow-lg' 
              : 'opacity-70 hover:opacity-100 hover:scale-105'
          ]"
        >
          <img
            :src="getImageUrl(event.poster_url || null, 'event')"
            :alt="event.title"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-300"></div>
        </div>
      </div>
    </div>

    <!-- View All Events Button -->
    <div class="text-center mt-4 md:mt-8">
      <NuxtLink
        to="/events"
        class="inline-flex items-center px-6 py-3 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors"
      >
        View All Events
        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </NuxtLink>
    </div>
  </div>
</template>


