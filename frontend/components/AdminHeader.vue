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
          :notifications="[...notificationStore.notifications]"
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
import { useNotificationStore } from '~/stores/notification'
import NotificationDropdown from '~/components/NotificationDropdown.vue'

const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const route = useRoute()

// Track current view mode
const isAdminView = computed(() => route.path.startsWith('/admin'))

// Load notifications when component mounts
onMounted(async () => {
  if (authStore.isAuthenticated) {
    try {
      await notificationStore.fetchNotifications()
      await notificationStore.fetchUnreadCount()
    } catch (error) {
      console.error('Failed to load notifications:', error)
    }
  }
})

// No static data needed - using real notification store

// Admin notification handlers
const handleAdminMarkAsRead = async (id: number) => {
  try {
    await notificationStore.markAsRead(id)
  } catch (error) {
    console.error('Failed to mark notification as read:', error)
  }
}

const handleAdminDeleteNotification = async (id: number) => {
  try {
    await notificationStore.deleteNotification(id)
  } catch (error) {
    console.error('Failed to delete notification:', error)
  }
}

const handleAdminMarkAllAsRead = async () => {
  try {
    await notificationStore.markAllAsRead()
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error)
  }
}
</script>
