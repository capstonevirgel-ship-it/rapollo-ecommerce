export interface Ticket {
  id: number
  event_id: number
  user_id: number
  ticket_purchase_id: number
  ticket_number: string
  price: number
  status: 'pending' | 'confirmed' | 'cancelled' | 'used' | 'failed'
  booked_at: string
  created_at: string
  updated_at: string
  event?: Event
  user?: User
  ticketPurchase?: TicketPurchase
  // Deprecated: Use ticketPurchase instead
  purchase?: Purchase
  purchase_id?: number
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
  booked_tickets_count?: number
  remaining_tickets?: number
}

import type { User, Purchase, PurchaseItem } from './purchase'

export type { User, Purchase, PurchaseItem }

export interface TicketStatistics {
  total_tickets: number
  confirmed_tickets: number
  pending_tickets: number
  cancelled_tickets: number
  used_tickets: number
  total_revenue: number
  recent_bookings: Ticket[]
}
