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

export interface UnreadCountResponse {
  count: number
}
