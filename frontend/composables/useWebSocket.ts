import { io, Socket } from 'socket.io-client'
import { ref, computed, onUnmounted } from 'vue'
import { useNotificationStore } from '~/stores/notification'
import { useAlert } from '~/composables/useAlert'

interface NotificationData {
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

interface WebSocketMessage {
  type: string
  notification: NotificationData
}

export const useWebSocket = () => {
  const socket = ref<Socket | null>(null)
  const isConnected = ref(false)
  const isConnecting = ref(false)
  const error = ref<string | null>(null)

  const notificationStore = useNotificationStore()
  const alert = useAlert()

  /**
   * Connect to WebSocket server
   */
  const connect = () => {
    // Only connect on client side
    if (typeof window === 'undefined') {
      return
    }

    // Don't connect if already connected or connecting
    if (socket.value?.connected || isConnecting.value) {
      return
    }

    // Get auth token from cookie
    const token = useCookie('auth-token')
    
    if (!token.value) {
      console.log('No auth token found, skipping WebSocket connection')
      return
    }

    // Get WebSocket server URL from runtime config
    const config = useRuntimeConfig()
    const wsUrl = config.public.websocketUrl || 'http://localhost:6001'

    isConnecting.value = true
    error.value = null

    console.log('Connecting to WebSocket server:', wsUrl)

    // Create socket connection with token in query string
    socket.value = io(wsUrl, {
      transports: ['websocket', 'polling'],
      query: {
        token: token.value
      },
      reconnection: true,
      reconnectionDelay: 1000,
      reconnectionDelayMax: 5000,
      reconnectionAttempts: 5,
      timeout: 20000
    })

    // Connection established
    socket.value.on('connect', () => {
      console.log('WebSocket connected')
      isConnected.value = true
      isConnecting.value = false
      error.value = null
    })

    // Connection confirmation from server
    socket.value.on('connected', (data: any) => {
      console.log('WebSocket connection confirmed:', data)
    })

    // Handle new notification
    socket.value.on('new_notification', (message: WebSocketMessage) => {
      console.log('New notification received:', message)
      
      if (message.notification) {
        // Add notification to store (prepend to list)
        notificationStore.addNotification(message.notification)
        
        // Update unread count
        notificationStore.incrementUnreadCount()
        
        // Show toast alert
        alert.info('You got a new notification', message.notification.title)
      }
    })

    // Connection error
    socket.value.on('error', (err: any) => {
      console.error('WebSocket error:', err)
      error.value = err.message || 'WebSocket connection error'
      isConnecting.value = false
    })

    // Disconnected
    socket.value.on('disconnect', (reason: string) => {
      console.log('WebSocket disconnected:', reason)
      isConnected.value = false
      isConnecting.value = false
      
      // Attempt to reconnect if it wasn't a manual disconnect
      if (reason === 'io server disconnect') {
        // Server disconnected, reconnect manually
        setTimeout(() => {
          if (token.value) {
            connect()
          }
        }, 1000)
      }
    })

    // Connection failed
    socket.value.on('connect_error', (err: Error) => {
      console.error('WebSocket connection error:', err)
      error.value = err.message || 'Failed to connect to WebSocket server'
      isConnecting.value = false
      isConnected.value = false
    })

    // Pong response for keepalive
    socket.value.on('pong', () => {
      // Keepalive working
    })
  }

  /**
   * Disconnect from WebSocket server
   */
  const disconnect = () => {
    if (socket.value) {
      console.log('Disconnecting WebSocket')
      socket.value.disconnect()
      socket.value = null
      isConnected.value = false
      isConnecting.value = false
    }
  }

  /**
   * Reconnect to WebSocket server
   */
  const reconnect = () => {
    disconnect()
    setTimeout(() => {
      connect()
    }, 500)
  }

  /**
   * Send ping for keepalive
   */
  const ping = () => {
    if (socket.value?.connected) {
      socket.value.emit('ping')
    }
  }

  // Cleanup on unmount
  onUnmounted(() => {
    disconnect()
  })

  return {
    socket: computed(() => socket.value),
    isConnected: computed(() => isConnected.value),
    isConnecting: computed(() => isConnecting.value),
    error: computed(() => error.value),
    connect,
    disconnect,
    reconnect,
    ping
  }
}

