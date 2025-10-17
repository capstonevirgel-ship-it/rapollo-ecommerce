<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAlert } from '~/composables/useAlert'

const { success, error } = useAlert()

// Static notification data for user
const notifications = ref([
  {
    id: 1,
    title: 'Order Confirmed',
    message: 'Your order #12345 has been confirmed and is being prepared for shipment.',
    type: 'order' as const,
    read: false,
    created_at: '2025-01-07T10:30:00Z',
    action_url: '/my-orders/12345',
    action_text: 'Track Order'
  },
  {
    id: 2,
    title: 'Payment Successful',
    message: 'Your payment of ₱2,500.00 has been processed successfully.',
    type: 'payment' as const,
    read: false,
    created_at: '2025-01-07T09:15:00Z',
    action_url: '/my-orders',
    action_text: 'View Orders'
  },
  {
    id: 3,
    title: 'Order Shipped',
    message: 'Your order #12344 has been shipped! Track your package with tracking number: TRK123456789.',
    type: 'order' as const,
    read: true,
    created_at: '2025-01-06T14:20:00Z',
    action_url: '/my-orders/12344',
    action_text: 'Track Package'
  },
  {
    id: 4,
    title: 'Event Registration Confirmed',
    message: 'You have successfully registered for "Fashion Week 2025" event. Your ticket has been sent to your email.',
    type: 'event' as const,
    read: true,
    created_at: '2025-01-06T16:20:00Z',
    action_url: '/my-tickets',
    action_text: 'View Tickets'
  },
  {
    id: 5,
    title: 'Special Offer Available',
    message: 'Get 20% off on all summer collection items! Use code SUMMER20 at checkout. Valid until January 15th.',
    type: 'promotion' as const,
    read: false,
    created_at: '2025-01-06T12:00:00Z',
    action_url: '/shop?promo=summer20',
    action_text: 'Shop Now'
  },
  {
    id: 6,
    title: 'Order Delivered',
    message: 'Your order #12343 has been delivered successfully. We hope you love your purchase!',
    type: 'order' as const,
    read: true,
    created_at: '2025-01-05T15:45:00Z',
    action_url: '/my-orders/12343',
    action_text: 'Leave Review'
  },
  {
    id: 7,
    title: 'Account Security Alert',
    message: 'We noticed a new login from a different device. If this wasn\'t you, please secure your account.',
    type: 'system' as const,
    read: true,
    created_at: '2025-01-05T08:30:00Z',
    action_url: '/profile/security',
    action_text: 'Review Security'
  },
  {
    id: 8,
    title: 'Wishlist Item on Sale',
    message: 'The "Premium Denim Jacket" in your wishlist is now 30% off! Don\'t miss out on this deal.',
    type: 'promotion' as const,
    read: true,
    created_at: '2025-01-04T11:15:00Z',
    action_url: '/wishlist',
    action_text: 'View Wishlist'
  },
  {
    id: 9,
    title: 'Event Reminder',
    message: 'Fashion Week 2025 starts tomorrow! Don\'t forget to check your ticket details.',
    type: 'event' as const,
    read: false,
    created_at: '2025-01-04T09:00:00Z',
    action_url: '/my-tickets',
    action_text: 'View Event'
  },
  {
    id: 10,
    title: 'Order Return Processed',
    message: 'Your return request for order #12342 has been approved. Refund will be processed within 3-5 business days.',
    type: 'order' as const,
    read: true,
    created_at: '2025-01-03T16:20:00Z',
    action_url: '/my-orders/12342',
    action_text: 'View Details'
  }
])

// Filter states
const activeFilter = ref('all')
const searchQuery = ref('')

// Computed properties
const filteredNotifications = computed(() => {
  let filtered = notifications.value

  // Filter by type
  if (activeFilter.value !== 'all') {
    filtered = filtered.filter(n => n.type === activeFilter.value)
  }

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(n => 
      n.title.toLowerCase().includes(query) || 
      n.message.toLowerCase().includes(query)
    )
  }

  return filtered
})

const unreadCount = computed(() => 
  notifications.value.filter(n => !n.read).length
)

const filterOptions = [
  { value: 'all', label: 'All Notifications', count: notifications.value.length },
  { value: 'order', label: 'Orders', count: notifications.value.filter(n => n.type === 'order').length },
  { value: 'payment', label: 'Payments', count: notifications.value.filter(n => n.type === 'payment').length },
  { value: 'event', label: 'Events', count: notifications.value.filter(n => n.type === 'event').length },
  { value: 'promotion', label: 'Offers', count: notifications.value.filter(n => n.type === 'promotion').length },
  { value: 'system', label: 'Account', count: notifications.value.filter(n => n.type === 'system').length }
]

// Methods
const markAsRead = (id: number) => {
  const notification = notifications.value.find(n => n.id === id)
  if (notification) {
    notification.read = true
    success('Notification marked as read')
  }
}

const deleteNotification = (id: number) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index > -1) {
    notifications.value.splice(index, 1)
    success('Notification deleted')
  }
}

const markAllAsRead = () => {
  notifications.value.forEach(n => n.read = true)
  success('All notifications marked as read')
}

const clearAll = () => {
  notifications.value = []
  success('All notifications cleared')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
            <p class="text-gray-600 mt-1">Stay updated with your orders and account activity</p>
          </div>
          <div class="flex items-center gap-3">
            <span v-if="unreadCount > 0" class="bg-red-500 text-white text-sm font-medium px-3 py-1 rounded-full">
              {{ unreadCount }} unread
            </span>
            <button 
              @click="markAllAsRead"
              v-if="unreadCount > 0"
              class="px-4 py-2 text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors"
            >
              Mark all as read
            </button>
            <button 
              @click="clearAll"
              class="px-4 py-2 text-sm text-red-600 hover:text-red-700 font-medium transition-colors"
            >
              Clear all
            </button>
          </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="flex flex-col sm:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
              <div class="relative">
                <Icon name="mdi:magnify" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input 
                  v-model="searchQuery"
                  type="text" 
                  placeholder="Search notifications..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>

            <!-- Filter Tabs -->
            <div class="flex flex-wrap gap-2">
              <button
                v-for="filter in filterOptions"
                :key="filter.value"
                @click="activeFilter = filter.value"
                :class="[
                  'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                  activeFilter === filter.value
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                {{ filter.label }}
                <span class="ml-1 text-xs opacity-75">({{ filter.count }})</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="space-y-4">
        <div v-if="filteredNotifications.length === 0" class="text-center py-16 bg-white rounded-lg border border-gray-200">
          <Icon name="mdi:bell-off" class="text-6xl text-gray-400 mx-auto mb-4" />
          <h3 class="text-xl font-medium text-gray-900 mb-2">No notifications found</h3>
          <p class="text-gray-500">
            {{ searchQuery ? 'Try adjusting your search terms' : 'You\'re all caught up!' }}
          </p>
        </div>

        <div v-else class="space-y-3">
          <Notification
            v-for="notification in filteredNotifications"
            :key="notification.id"
            :notification="notification"
            @mark-as-read="markAsRead"
            @delete="deleteNotification"
          />
        </div>
      </div>
    </div>
  </div>
</template>
