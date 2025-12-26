import { defineStore } from 'pinia'
import { useCustomFetch } from '~/composables/useCustomFetch'

export interface Notification {
  id: number
  title: string
  message: string
  type: 'order' | 'payment' | 'system' | 'promotion' | 'event'
  read: boolean
  created_at: string
  action_url?: string
  action_text?: string
  metadata?: any
}

export interface NotificationResponse {
  notifications: Notification[]
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)

  // Fetch notifications with filters
  const fetchNotifications = async (params: {
    type?: string
    search?: string
    page?: number
  } = {}) => {
    loading.value = true
    try {
      const query = new URLSearchParams()
      if (params.type && params.type !== 'all') query.append('type', params.type)
      if (params.search) query.append('search', params.search)
      if (params.page) query.append('page', params.page.toString())

      const queryString = query.toString()
      const url = queryString ? `/api/notifications?${queryString}` : '/api/notifications'
      console.log('Fetching notifications from:', url)
      
      const response = await useCustomFetch<NotificationResponse>(url)
      console.log('Notification API response:', response)
      
      notifications.value = response.notifications || []
      return response
    } catch (error: any) {
      // Handle authentication errors gracefully
      if (error.status === 401 || error.statusCode === 401) {
        console.warn('User not authenticated, skipping notifications fetch')
        notifications.value = []
        return { notifications: [], pagination: { current_page: 1, last_page: 1, per_page: 20, total: 0 } }
      }
      console.error('Failed to fetch notifications:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  // Fetch unread count
  const fetchUnreadCount = async () => {
    try {
      console.log('Fetching unread count from: /api/notifications/unread-count')
      const response = await useCustomFetch<{ count: number }>('/api/notifications/unread-count')
      console.log('Unread count API response:', response)
      
      unreadCount.value = response.count || 0
      return response.count
    } catch (error: any) {
      // Handle authentication errors gracefully
      if (error.status === 401 || error.statusCode === 401) {
        console.warn('User not authenticated, skipping unread count fetch')
        unreadCount.value = 0
        return 0
      }
      console.error('Failed to fetch unread count:', error)
      throw error
    }
  }

  // Mark notification as read
  const markAsRead = async (id: number) => {
    try {
      await useCustomFetch(`/api/notifications/${id}/read`, {
        method: 'PUT'
      })
      
      // Update local state
      const notification = notifications.value.find(n => n.id === id)
      if (notification && !notification.read) {
        notification.read = true
        decrementUnreadCount()
      }
      
      // Also fetch to ensure sync
      await fetchUnreadCount()
    } catch (error) {
      console.error('Failed to mark notification as read:', error)
      throw error
    }
  }

  // Mark all notifications as read
  const markAllAsRead = async () => {
    try {
      await useCustomFetch('/api/notifications/mark-all-read', {
        method: 'PUT'
      })
      
      // Update local state
      notifications.value.forEach(n => n.read = true)
      unreadCount.value = 0
    } catch (error) {
      console.error('Failed to mark all notifications as read:', error)
      throw error
    }
  }

  // Delete notification
  const deleteNotification = async (id: number) => {
    try {
      await useCustomFetch(`/api/notifications/${id}`, {
        method: 'DELETE'
      })
      
      // Update local state
      const index = notifications.value.findIndex(n => n.id === id)
      if (index > -1) {
        const notification = notifications.value[index]
        notifications.value.splice(index, 1)
        
        // Decrement unread count if notification was unread
        if (!notification.read) {
          decrementUnreadCount()
        }
      }
      
      // Also fetch to ensure sync
      await fetchUnreadCount()
    } catch (error) {
      console.error('Failed to delete notification:', error)
      throw error
    }
  }

  // Clear all notifications
  const clearAll = async () => {
    try {
      await useCustomFetch('/api/notifications', {
        method: 'DELETE'
      })
      
      // Update local state
      notifications.value = []
      unreadCount.value = 0
    } catch (error) {
      console.error('Failed to clear all notifications:', error)
      throw error
    }
  }

  // Get filtered notifications
  const getFilteredNotifications = (type: string, search: string) => {
    let filtered = notifications.value

    if (type !== 'all') {
      filtered = filtered.filter(n => n.type === type)
    }

    if (search) {
      const query = search.toLowerCase()
      filtered = filtered.filter(n => 
        n.title.toLowerCase().includes(query) || 
        n.message.toLowerCase().includes(query)
      )
    }

    return filtered
  }

  // Add notification from WebSocket (prepend to list)
  const addNotification = (notification: Notification) => {
    // Check if notification already exists (avoid duplicates)
    const exists = notifications.value.some(n => n.id === notification.id)
    if (exists) {
      return
    }

    // Prepend new notification to the beginning of the list
    notifications.value.unshift(notification)
    
    // Limit to prevent memory issues (keep last 100 notifications)
    if (notifications.value.length > 100) {
      notifications.value = notifications.value.slice(0, 100)
    }
  }

  // Increment unread count
  const incrementUnreadCount = () => {
    unreadCount.value = unreadCount.value + 1
  }

  // Decrement unread count
  const decrementUnreadCount = () => {
    if (unreadCount.value > 0) {
      unreadCount.value = unreadCount.value - 1
    }
  }

  return {
    notifications,
    unreadCount,
    loading,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    clearAll,
    getFilteredNotifications,
    addNotification,
    incrementUnreadCount,
    decrementUnreadCount
  }
})
