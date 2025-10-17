<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAlert } from '~/composables/useAlert'

definePageMeta({
  layout: 'admin'
})

const { success, error } = useAlert()

// Static notification data for admin
const notifications = ref([
  {
    id: 1,
    title: 'New Order Received',
    message: 'Order #12345 has been placed by John Doe for ₱2,500.00 worth of products.',
    type: 'order' as const,
    read: false,
    created_at: '2025-01-07T10:30:00Z',
    action_url: '/admin/orders/12345',
    action_text: 'View Order'
  },
  {
    id: 2,
    title: 'Payment Confirmed',
    message: 'Payment for Order #12344 has been successfully processed via PayMongo.',
    type: 'payment' as const,
    read: false,
    created_at: '2025-01-07T09:15:00Z',
    action_url: '/admin/orders/12344',
    action_text: 'View Order'
  },
  {
    id: 3,
    title: 'Low Stock Alert',
    message: 'Product "Classic White T-Shirt" is running low on stock (5 items remaining).',
    type: 'system' as const,
    read: true,
    created_at: '2025-01-07T08:45:00Z',
    action_url: '/admin/products',
    action_text: 'Manage Inventory'
  },
  {
    id: 4,
    title: 'New Event Registration',
    message: 'Sarah Johnson has registered for "Fashion Week 2025" event.',
    type: 'event' as const,
    read: true,
    created_at: '2025-01-06T16:20:00Z',
    action_url: '/admin/events',
    action_text: 'View Event'
  },
  {
    id: 5,
    title: 'System Maintenance',
    message: 'Scheduled maintenance will occur tonight from 2:00 AM to 4:00 AM. Some features may be temporarily unavailable.',
    type: 'system' as const,
    read: false,
    created_at: '2025-01-06T14:00:00Z',
    action_url: null,
    action_text: null
  },
  {
    id: 6,
    title: 'New Product Review',
    message: 'A new 5-star review has been submitted for "Premium Denim Jacket".',
    type: 'system' as const,
    read: true,
    created_at: '2025-01-06T11:30:00Z',
    action_url: '/admin/products',
    action_text: 'View Reviews'
  },
  {
    id: 7,
    title: 'Order Cancelled',
    message: 'Order #12343 has been cancelled by the customer. Refund processing initiated.',
    type: 'order' as const,
    read: true,
    created_at: '2025-01-05T15:45:00Z',
    action_url: '/admin/orders/12343',
    action_text: 'View Order'
  },
  {
    id: 8,
    title: 'Promotion Campaign',
    message: 'Your "Summer Sale 2025" promotion campaign is now live and running.',
    type: 'promotion' as const,
    read: true,
    created_at: '2025-01-05T10:00:00Z',
    action_url: '/admin/promotions',
    action_text: 'View Campaign'
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
  { value: 'system', label: 'System', count: notifications.value.filter(n => n.type === 'system').length },
  { value: 'event', label: 'Events', count: notifications.value.filter(n => n.type === 'event').length },
  { value: 'promotion', label: 'Promotions', count: notifications.value.filter(n => n.type === 'promotion').length }
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
  <div class="p-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
          <p class="text-gray-600">Stay updated with your store activities</p>
        </div>
        <div class="flex items-center gap-3">
          <span v-if="unreadCount > 0" class="bg-red-500 text-white text-xs font-medium px-2 py-1 rounded-full">
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

    <!-- Notifications List -->
    <div class="space-y-4">
      <div v-if="filteredNotifications.length === 0" class="text-center py-12">
        <Icon name="mdi:bell-off" class="text-4xl text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications found</h3>
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
</template>
