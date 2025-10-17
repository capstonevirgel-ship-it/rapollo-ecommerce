<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
    <div class="p-4">
      <!-- Header -->
      <div class="flex items-start justify-between mb-3">
        <div class="flex items-center gap-3">
          <!-- Icon -->
          <div 
            class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
            :class="iconClasses"
          >
            <Icon :name="icon" class="text-sm" />
          </div>
          
          <!-- Title and Type -->
          <div>
            <h3 class="font-medium text-gray-900 text-sm">{{ notification.title }}</h3>
            <p class="text-xs text-gray-500 capitalize">{{ notification.type }}</p>
          </div>
        </div>
        
        <!-- Time and Actions -->
        <div class="flex items-center gap-2">
          <span class="text-xs text-gray-500">{{ formatTime(notification.created_at) }}</span>
          <button 
            @click="$emit('markAsRead', notification.id)"
            v-if="!notification.read"
            class="text-gray-400 hover:text-gray-600 transition-colors"
            title="Mark as read"
          >
            <Icon name="mdi:check" class="text-sm" />
          </button>
          <button 
            @click="$emit('delete', notification.id)"
            class="text-gray-400 hover:text-red-600 transition-colors"
            title="Delete notification"
          >
            <Icon name="mdi:delete" class="text-sm" />
          </button>
        </div>
      </div>
      
      <!-- Content -->
      <div class="ml-11">
        <p class="text-sm text-gray-700 leading-relaxed">{{ notification.message }}</p>
        
        <!-- Action Button -->
        <div v-if="notification.action_url" class="mt-3">
          <NuxtLink 
            :to="notification.action_url"
            class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors"
          >
            {{ notification.action_text || 'View Details' }}
            <Icon name="mdi:arrow-right" class="text-xs" />
          </NuxtLink>
        </div>
      </div>
      
      <!-- Unread Indicator -->
      <div v-if="!notification.read" class="absolute top-4 right-4 w-2 h-2 bg-blue-500 rounded-full"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Notification {
  id: number
  title: string
  message: string
  type: 'order' | 'payment' | 'system' | 'promotion' | 'event'
  read: boolean
  created_at: string
  action_url?: string
  action_text?: string
}

const props = defineProps<{
  notification: Notification
}>()

const emit = defineEmits<{
  markAsRead: [id: number]
  delete: [id: number]
}>()

const iconMap = {
  order: 'mdi:package-variant',
  payment: 'mdi:credit-card',
  system: 'mdi:cog',
  promotion: 'mdi:tag',
  event: 'mdi:calendar'
}

const icon = computed(() => iconMap[props.notification.type] || 'mdi:bell')

const iconClasses = computed(() => {
  const baseClasses = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0'
  
  switch (props.notification.type) {
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
})

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
