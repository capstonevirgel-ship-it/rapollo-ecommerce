<template>
  <div class="relative">
    <!-- Notification Bell Button -->
    <button 
      @click="toggleDropdown"
      class="relative p-2 text-gray-300 hover:text-white transition-colors"
      aria-label="Notifications"
    >
      <Icon name="mdi:bell-outline" class="text-2xl" />
      <span 
        v-if="unreadCount > 0" 
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 transform scale-95"
      enter-to-class="opacity-100 transform scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 transform scale-100"
      leave-to-class="opacity-0 transform scale-95"
    >
      <div 
        v-if="isOpen"
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-[70]"
        @click.stop
      >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
          <div class="flex items-center gap-2">
            <span v-if="unreadCount > 0" class="text-sm text-gray-500">{{ unreadCount }} unread</span>
            <button 
              @click="markAllAsRead"
              v-if="unreadCount > 0"
              class="text-sm text-blue-600 hover:text-blue-700 font-medium"
            >
              Mark all read
            </button>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
            <Icon name="mdi:bell-off" class="text-3xl mx-auto mb-2 text-gray-400" />
            <p>No notifications</p>
          </div>
          
          <div v-else class="divide-y divide-gray-100">
            <div 
              v-for="notification in notifications.slice(0, 5)" 
              :key="notification.id"
              class="p-4 hover:bg-gray-50 transition-colors"
              :class="{ 'bg-blue-50': !notification.read }"
            >
              <div class="flex items-start gap-3">
                <!-- Icon -->
                <div 
                  class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                  :class="getIconClasses(notification.type)"
                >
                  <Icon :name="getIcon(notification.type)" class="text-sm" />
                </div>
                
                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-gray-900 truncate">{{ notification.title }}</h4>
                  <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ notification.message }}</p>
                  <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-gray-500">{{ formatTime(notification.created_at) }}</span>
                    <div class="flex items-center gap-2">
                      <button 
                        v-if="!notification.read"
                        @click="markAsRead(notification.id)"
                        class="text-xs text-blue-600 hover:text-blue-700"
                      >
                        Mark read
                      </button>
                      <button 
                        @click="deleteNotification(notification.id)"
                        class="text-xs text-gray-400 hover:text-red-600"
                      >
                        <Icon name="mdi:delete" class="text-sm" />
                      </button>
                    </div>
                  </div>
                </div>
                
                <!-- Unread indicator -->
                <div v-if="!notification.read" class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200">
          <NuxtLink 
            :to="viewAllUrl"
            @click="closeDropdown"
            class="block w-full text-center text-sm text-blue-600 hover:text-blue-700 font-medium"
          >
            View all notifications
          </NuxtLink>
        </div>
      </div>
    </Transition>

    <!-- Backdrop -->
    <div 
      v-if="isOpen"
      @click="closeDropdown"
      class="fixed inset-0 z-[65]"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

interface Notification {
  id: number
  title: string
  message: string
  type: 'order' | 'payment' | 'system' | 'promotion' | 'event'
  read: boolean
  created_at: string
}

const props = defineProps<{
  notifications: Notification[]
  viewAllUrl: string
}>()

const emit = defineEmits<{
  markAsRead: [id: number]
  delete: [id: number]
  markAllAsRead: []
}>()

const isOpen = ref(false)

// Close dropdown on scroll
const handleScroll = () => {
  if (isOpen.value) {
    isOpen.value = false
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)
})

const unreadCount = computed(() => 
  props.notifications.filter(n => !n.read).length
)

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const closeDropdown = () => {
  isOpen.value = false
}

const markAsRead = (id: number) => {
  emit('markAsRead', id)
}

const deleteNotification = (id: number) => {
  emit('delete', id)
}

const markAllAsRead = () => {
  emit('markAllAsRead')
  closeDropdown()
}

const getIcon = (type: string) => {
  const iconMap = {
    order: 'mdi:package-variant',
    payment: 'mdi:credit-card',
    system: 'mdi:cog',
    promotion: 'mdi:tag',
    event: 'mdi:calendar'
  }
  return iconMap[type as keyof typeof iconMap] || 'mdi:bell'
}

const getIconClasses = (type: string) => {
  const baseClasses = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0'
  
  switch (type) {
    case 'order':
      return `${baseClasses} bg-blue-100 text-blue-600`
    case 'payment':
      return `${baseClasses} bg-green-100 text-green-600`
    case 'system':
      return `${baseClasses} bg-gray-100 text-gray-600`
    case 'promotion':
      return `${baseClasses} bg-purple-100 text-purple-600`
    case 'event':
      return `${baseClasses} bg-orange-100 text-orange-600`
    default:
      return `${baseClasses} bg-gray-100 text-gray-600`
  }
}

const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
  
  if (diffInMinutes < 1) return 'Just now'
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`
  
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) return `${diffInHours}h ago`
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 7) return `${diffInDays}d ago`
  
  return date.toLocaleDateString()
}
</script>
