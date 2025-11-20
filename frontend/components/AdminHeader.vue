<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { useNotificationStore } from '~/stores/notification'
import NotificationDropdown from '~/components/NotificationDropdown.vue'
import { inject, ref, onMounted, onBeforeUnmount, computed, type ComputedRef } from 'vue'

const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const route = useRoute()

// Get mobile menu controls from Sidebar (injected)
const toggleMobileMenu = inject<(() => void) | null>('toggleMobileMenu', null)
const sidebarIsMobile = inject<ComputedRef<boolean> | null>('sidebarIsMobile', null)
const isMobile = ref(false)

const checkMobile = () => {
  if (process.client) {
    isMobile.value = window.innerWidth < 1024
  }
}

// Track current view mode
const isAdminView = computed(() => route.path.startsWith('/admin'))

// Load notifications when component mounts
onMounted(async () => {
  checkMobile()
  if (process.client) {
    window.addEventListener('resize', checkMobile)
  }
  
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

<template>
  <div v-if="authStore.isAdmin" class="admin-header fixed top-0 left-0 right-0 z-[60] bg-zinc-800 text-white py-2.5 px-4 text-sm border-b border-zinc-700">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
      <!-- Left side - Admin info -->
      <div class="flex items-center gap-3 lg:gap-4">
        <div class="flex items-center gap-2">
          <Icon name="mdi:shield-crown" class="text-yellow-400 hidden lg:block" />
          <span class="font-medium">Admin Panel</span>
        </div>
        <div class="text-zinc-300 hidden lg:block">
          Welcome, {{ authStore.user?.user_name }}
        </div>
      </div>

      <!-- Right side - Navigation and actions -->
      <div class="flex items-center gap-2 sm:gap-3">
        <!-- Single Toggle Button -->
        <NuxtLink
          :to="isAdminView ? '/' : '/admin/dashboard'"
          class="flex items-center justify-center gap-1.5 px-2 sm:px-3 py-1.5 bg-zinc-700 hover:bg-zinc-600 rounded text-xs transition-colors"
        >
          <Icon :name="isAdminView ? 'mdi:web' : 'mdi:view-dashboard'" class="m-0" />
          <span v-if="!sidebarIsMobile?.value" class="hidden sm:inline">{{ isAdminView ? 'Go to Website' : 'Go to Dashboard' }}</span>
        </NuxtLink>
        
        <!-- Notifications -->
        <div v-if="!sidebarIsMobile?.value" class="max-[722px]:hidden">
          <NotificationDropdown
            :notifications="[...notificationStore.notifications]"
            view-all-url="/admin/notifications"
            @mark-as-read="handleAdminMarkAsRead"
            @delete="handleAdminDeleteNotification"
            @mark-all-as-read="handleAdminMarkAllAsRead"
          />
        </div>

        <!-- Mobile Menu Toggle Button -->
        <button
          v-if="isMobile && toggleMobileMenu"
          @click="toggleMobileMenu"
          class="lg:hidden p-1 rounded-md hover:bg-zinc-700 transition-colors duration-200 mobile-menu-button flex items-center justify-center"
          aria-label="Toggle menu"
        >
          <Icon name="mdi:menu" class="text-xl" />
        </button>

        <!-- Admin Actions -->
        <button
          @click="authStore.logout()"
          class="hidden lg:flex text-zinc-300 hover:text-white transition-colors p-1"
          title="Logout"
        >
          <Icon name="mdi:logout" class="text-lg" />
        </button>
      </div>
    </div>
  </div>
</template>

