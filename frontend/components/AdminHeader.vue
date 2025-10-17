<template>
  <div v-if="authStore.isAdmin" class="sticky top-0 z-50 bg-zinc-800 text-white py-2 px-4 text-sm border-b border-zinc-700">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      <!-- Left side - Admin info -->
      <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-2">
          <Icon name="mdi:shield-crown" class="text-yellow-400" />
          <span class="font-medium">Admin Panel</span>
        </div>
        <div class="text-zinc-300">
          Welcome, {{ authStore.user?.user_name }}
        </div>
      </div>

      <!-- Right side - Navigation and actions -->
      <div class="flex items-center space-x-4">

        <!-- Single Toggle Button -->
        <div class="flex items-center space-x-2">
          <NuxtLink
            :to="isAdminView ? '/' : '/admin/dashboard'"
            class="flex items-center space-x-1 px-3 py-1 bg-zinc-700 hover:bg-zinc-600 rounded text-xs transition-colors"
          >
            <Icon :name="isAdminView ? 'mdi:web' : 'mdi:view-dashboard'" />
            <span>{{ isAdminView ? 'Go to Website' : 'Go to Dashboard' }}</span>
          </NuxtLink>
        </div>

        <!-- Notifications -->
        <NotificationDropdown
          :notifications="adminNotifications"
          view-all-url="/admin/notifications"
          @mark-as-read="handleAdminMarkAsRead"
          @delete="handleAdminDeleteNotification"
          @mark-all-as-read="handleAdminMarkAllAsRead"
        />

        <!-- Admin Actions -->
        <div class="flex items-center space-x-2">
          <button
            @click="authStore.logout()"
            class="text-zinc-300 hover:text-white transition-colors"
            title="Logout"
          >
            <Icon name="mdi:logout" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import NotificationDropdown from '~/components/NotificationDropdown.vue'

const authStore = useAuthStore()
const route = useRoute()

// Track current view mode
const isAdminView = computed(() => route.path.startsWith('/admin'))

// Static admin notification data
const adminNotifications = ref([
  {
    id: 1,
    title: 'New Order Received',
    message: 'Order #12345 has been placed by John Doe for ₱2,500.00 worth of products.',
    type: 'order' as const,
    read: false,
    created_at: '2025-01-07T10:30:00Z'
  },
  {
    id: 2,
    title: 'Payment Confirmed',
    message: 'Payment for Order #12344 has been successfully processed via PayMongo.',
    type: 'payment' as const,
    read: false,
    created_at: '2025-01-07T09:15:00Z'
  },
  {
    id: 3,
    title: 'Low Stock Alert',
    message: 'Product "Classic White T-Shirt" is running low on stock (5 items remaining).',
    type: 'system' as const,
    read: true,
    created_at: '2025-01-07T08:45:00Z'
  },
  {
    id: 4,
    title: 'New Event Registration',
    message: 'Sarah Johnson has registered for "Fashion Week 2025" event.',
    type: 'event' as const,
    read: true,
    created_at: '2025-01-06T16:20:00Z'
  },
  {
    id: 5,
    title: 'System Maintenance',
    message: 'Scheduled maintenance will occur tonight from 2:00 AM to 4:00 AM.',
    type: 'system' as const,
    read: false,
    created_at: '2025-01-06T14:00:00Z'
  }
])

// Admin notification handlers
const handleAdminMarkAsRead = (id: number) => {
  const notification = adminNotifications.value.find(n => n.id === id)
  if (notification) {
    notification.read = true
  }
}

const handleAdminDeleteNotification = (id: number) => {
  const index = adminNotifications.value.findIndex(n => n.id === id)
  if (index > -1) {
    adminNotifications.value.splice(index, 1)
  }
}

const handleAdminMarkAllAsRead = () => {
  adminNotifications.value.forEach(n => n.read = true)
}
</script>
