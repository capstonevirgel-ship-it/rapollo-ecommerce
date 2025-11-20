export interface User {
  id: number
  user_name: string
  email: string
  role: string
  created_at: string
  updated_at: string
}

export interface ProductPurchase {
  id: number
  user_id: number
  total: number
  status: 'pending' | 'processing' | 'shipped' | 'delivered' | 'completed' | 'cancelled' | 'failed'
  shipping_address?: any
  billing_address?: any
  paid_at?: string
  created_at: string
  updated_at: string
  user?: User
  items?: ProductPurchaseItem[]
  productPurchaseItems?: ProductPurchaseItem[]
  payment?: Payment
}

export interface TicketPurchase {
  id: number
  user_id: number
  event_id: number
  total: number
  paid_at?: string
  created_at: string
  updated_at: string
  user?: User
  event?: {
    id: number
    title: string
    slug?: string
  }
  tickets?: any[]
  payment?: Payment
}

// Deprecated: Use ProductPurchase or TicketPurchase instead
export interface Purchase {
  id: number
  user_id: number
  total: number
  status: 'pending' | 'processing' | 'completed' | 'cancelled'
  type?: 'product' | 'ticket'
  event_id?: number
  shipping_address?: any
  billing_address?: any
  paid_at?: string
  created_at: string
  updated_at: string
  user?: User
  event?: {
    id: number
    title: string
  }
  items?: ProductPurchaseItem[]
  purchase_items?: ProductPurchaseItem[]
  payment?: Payment
  tickets?: any[]
}

export interface ProductPurchaseItem {
  id: number
  product_purchase_id: number
  variant_id?: number
  quantity: number
  price: number
  created_at: string
  updated_at: string
  variant?: any
  purchase_tax_rate?: number
  purchase_shipping_amount?: number
}

// Deprecated: Use ProductPurchaseItem instead
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
  ticket?: any
}

export interface Payment {
  id: number
  user_id: number
  purchasable_type?: string
  purchasable_id?: number
  amount: number
  currency: string
  status: 'pending' | 'processing' | 'paid' | 'failed' | 'cancelled' | 'expired'
  payment_method: string
  transaction_id?: string
  payment_method_id?: string
  payment_date?: string
  notes?: string
  metadata?: any
  // Deprecated: Use purchasable_type and purchasable_id instead
  purchase_id?: number
}

export interface PaymentResponse {
  message: string
  payment_status: string
  payment_method: string
  purchase_status: string
}

export interface ProductPurchaseCreateResponse {
  message: string
  data: ProductPurchase
}

export interface ProductPurchaseFetchResponse {
  data: ProductPurchase
}

export interface ProductPurchaseListResponse {
  data: ProductPurchase[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// Deprecated: Use ProductPurchase*Response instead
export interface PurchaseCreateResponse {
  data: Purchase
}

export interface PurchaseFetchResponse {
  data: Purchase
}

export interface PurchaseListResponse {
  data: Purchase[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
