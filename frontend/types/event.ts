export interface EventComment {
  id: number
  event_id: number
  user_id: number
  comment: string
  created_at: string
  updated_at: string
  user?: User
}

export interface Event {
  id: number
  admin_id: number
  title: string
  description?: string
  date: string
  location?: string
  poster_url?: string
  ticket_price?: string | number
  max_tickets?: number
  available_tickets?: number
  created_at: string
  updated_at: string
  admin?: User
  tickets?: Ticket[]
  comments?: EventComment[]
  booked_tickets_count?: number
  remaining_tickets?: number
}

export interface User {
  id: number
  user_name: string
  email: string
  role: string
  created_at: string
  updated_at: string
}

export interface Ticket {
  id: number
  event_id: number
  user_id: number
  purchase_id: number
  ticket_number: string
  price: number
  status: 'pending' | 'confirmed' | 'cancelled' | 'used' | 'failed'
  booked_at: string
  created_at: string
  updated_at: string
  event?: Event
  user?: User
  purchase?: Purchase
}

export interface Purchase {
  id: number
  user_id: number
  total: number
  status: 'pending' | 'processing' | 'completed' | 'cancelled'
  type?: 'product' | 'ticket' // Optional to handle NULL values from existing purchases
  event_id?: number
  shipping_address?: any
  billing_address?: any
  paid_at?: string
  created_at: string
  updated_at: string
  user?: User
  event?: Event
  purchase_items?: PurchaseItem[]
  tickets?: Ticket[]
}

export interface PurchaseItem {
  id: number
  purchase_id: number
  variant_id?: number
  ticket_id?: number
  quantity: number
  price: number
  created_at: string
  updated_at: string
  variant?: any
  ticket?: Ticket
}

export interface TicketStatistics {
  total_tickets: number
  confirmed_tickets: number
  pending_tickets: number
  cancelled_tickets: number
  used_tickets: number
  total_revenue: number
  recent_bookings: Ticket[]
}
