<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-bold text-gray-900">Review Your Ticket Purchase</h2>
          <button 
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-6">
        <!-- Event Details -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-3">Event Details</h3>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-gray-600">Event:</span>
              <span class="font-medium text-gray-900">{{ event.title }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Date:</span>
              <span class="font-medium text-gray-900">{{ formatDate(event.event_date) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Time:</span>
              <span class="font-medium text-gray-900">{{ formatTime(event.event_date) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Location:</span>
              <span class="font-medium text-gray-900">{{ event.location }}</span>
            </div>
          </div>
        </div>

        <!-- Ticket Details -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-3">Ticket Details</h3>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-gray-600">Quantity:</span>
              <span class="font-medium text-gray-900">{{ quantity }} ticket(s)</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Price per ticket:</span>
              <span class="font-medium text-gray-900">₱{{ formatPrice(event.ticket_price) }}</span>
            </div>
            <div class="flex justify-between text-lg font-bold border-t pt-2">
              <span class="text-gray-900">Total:</span>
              <span class="text-gray-900">₱{{ formatPrice(totalPrice) }}</span>
            </div>
          </div>
        </div>

        <!-- User Information -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-3">Your Information</h3>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-gray-600">Name:</span>
              <span class="font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Email:</span>
              <span class="font-medium text-gray-900">{{ user.email }}</span>
            </div>
            <div v-if="user.phone" class="flex justify-between">
              <span class="text-gray-600">Phone:</span>
              <span class="font-medium text-gray-900">{{ user.phone }}</span>
            </div>
          </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="bg-blue-50 rounded-lg p-4">
          <h4 class="text-sm font-semibold text-blue-900 mb-2">Important Notes:</h4>
          <ul class="text-sm text-blue-800 space-y-1">
            <li>• Tickets are non-refundable once purchased</li>
            <li>• Please arrive 30 minutes before the event starts</li>
            <li>• Bring a valid ID for verification</li>
            <li>• Tickets will be sent to your email after payment</li>
          </ul>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
        <div class="flex flex-col sm:flex-row gap-3">
          <button 
            @click="$emit('close')"
            class="flex-1 px-4 py-3 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium"
          >
            Cancel
          </button>
          <button 
            @click="$emit('proceed')"
            :disabled="loading"
            class="flex-1 px-4 py-3 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="loading" class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Processing...
            </span>
            <span v-else>Looks Good</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Event, User } from '~/types'

interface Props {
  event: Event
  quantity: number
  user: User
  loading?: boolean
}

const props = defineProps<Props>()

defineEmits<{
  close: []
  proceed: []
}>()

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

const formatPrice = (price: number) => {
  return price.toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const totalPrice = computed(() => {
  return props.event.ticket_price * props.quantity
})
</script>
