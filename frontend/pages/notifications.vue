<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAlert } from '~/composables/useAlert'
import { useNotificationStore } from '~/stores/notification'

const { success, error } = useAlert()
const notificationStore = useNotificationStore()

// Set page title
useHead({
  title: 'Notifications | RAPOLLO',
  meta: [
    { name: 'description', content: 'Stay updated with your order notifications and important updates from Rapollo E-commerce.' }
  ]
})

// Load notifications on mount
onMounted(async () => {
  try {
    await notificationStore.fetchNotifications()
    await notificationStore.fetchUnreadCount()
  } catch (err) {
    error('Failed to load notifications')
  }
})

// Filter states
const activeFilter = ref('all')
const searchQuery = ref('')
const currentPage = ref(1)

// Computed properties
const filteredNotifications = computed(() => {
  return notificationStore.getFilteredNotifications(activeFilter.value, searchQuery.value)
})

const unreadCount = computed(() => notificationStore.unreadCount)

const filterOptions = computed(() => [
  { value: 'all', label: 'All Notifications', count: notificationStore.notifications.length },
  { value: 'order', label: 'Orders', count: notificationStore.notifications.filter(n => n.type === 'order').length },
  { value: 'payment', label: 'Payments', count: notificationStore.notifications.filter(n => n.type === 'payment').length },
  { value: 'event', label: 'Events', count: notificationStore.notifications.filter(n => n.type === 'event').length },
  { value: 'promotion', label: 'Offers', count: notificationStore.notifications.filter(n => n.type === 'promotion').length },
  { value: 'system', label: 'Account', count: notificationStore.notifications.filter(n => n.type === 'system').length }
])

// Methods
const markAsRead = async (id: number) => {
  try {
    await notificationStore.markAsRead(id)
    success('Notification marked as read')
  } catch (err) {
    error('Failed to mark notification as read')
  }
}

const deleteNotification = async (id: number) => {
  try {
    await notificationStore.deleteNotification(id)
    success('Notification deleted')
  } catch (err) {
    error('Failed to delete notification')
  }
}

const markAllAsRead = async () => {
  try {
    await notificationStore.markAllAsRead()
  success('All notifications marked as read')
  } catch (err) {
    error('Failed to mark all notifications as read')
  }
}

const clearAll = async () => {
  try {
    await notificationStore.clearAll()
  success('All notifications cleared')
  } catch (err) {
    error('Failed to clear all notifications')
  }
}

const handleFilterChange = async () => {
  try {
    currentPage.value = 1
    await notificationStore.fetchNotifications({
      type: activeFilter.value,
      search: searchQuery.value,
      page: currentPage.value
    })
  } catch (err) {
    error('Failed to load notifications')
  }
}

const handleSearch = async () => {
  try {
    currentPage.value = 1
    await notificationStore.fetchNotifications({
      type: activeFilter.value,
      search: searchQuery.value,
      page: currentPage.value
    })
  } catch (err) {
    error('Failed to load notifications')
  }
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
                  @input="handleSearch"
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
                @click="activeFilter = filter.value; handleFilterChange()"
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
