import { defineStore } from 'pinia'

export interface Purchase {
  id: number
  user_id: number
  total: number
  status: 'pending' | 'processing' | 'completed' | 'cancelled'
  type?: 'product' | 'ticket'
  event_id?: number
  created_at: string
  updated_at: string
  items?: PurchaseItem[]
  payment?: Payment
  user?: {
    id: number
    user_name: string
    email: string
    role: string
  }
  event?: {
    id: number
    title: string
  }
}

export interface PurchaseItem {
  id: number
  purchase_id: number
  variant_id: number
  quantity: number
  price: number
  variant?: any
}

export interface Payment {
  id: number
  user_id: number
  purchase_id: number
  amount: number
  currency: string
  status: 'pending' | 'processing' | 'paid' | 'failed' | 'cancelled' | 'expired'
  payment_method: string
  transaction_id?: string
  payment_method_id?: string
  payment_date?: string
  notes?: string
  metadata?: any
}

export interface PaymentResponse {
  message: string
  payment_status: string
  payment_method: string
  purchase_status: string
}

// Removed PayMongo interfaces

export const usePurchaseStore = defineStore('purchase', () => {
  const purchases = ref<Purchase[]>([])
  const pagination = ref<any>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const createPurchase = async (cartItems: any[]) => {
    // Map cart items to the format expected by the backend
    const items = cartItems.map(item => ({
      variant_id: item.variant_id,
      quantity: item.quantity,
      price: item.variant.price // Extract price from variant
    }))

    const response = await $fetch('/api/purchases', {
      method: 'POST',
      body: {
        items: items
      }
    })
    return response.data
  }

  const createPayment = async (purchaseId: number, amount: number, paymentMethod: string): Promise<PaymentResponse> => {
    const response = await $fetch('/api/payments/create', {
      method: 'POST',
      body: {
        purchase_id: purchaseId,
        amount: amount,
        currency: 'PHP',
        payment_method: paymentMethod
      }
    })
    return response as PaymentResponse
  }

  // Fetch single purchase by ID
  const fetchPurchaseById = async (id: number): Promise<Purchase> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await $fetch(`/api/purchases/${id}`)
      return response.data
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'Failed to fetch purchase'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Admin methods
  const fetchAdminPurchases = async (filters: {
    status?: string
    type?: string
    search?: string
    per_page?: number
  } = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const queryParams = new URLSearchParams()
      if (filters.status) queryParams.append('status', filters.status)
      if (filters.type) queryParams.append('type', filters.type)
      if (filters.search) queryParams.append('search', filters.search)
      if (filters.per_page) queryParams.append('per_page', filters.per_page.toString())
      
      const response = await $fetch(`/api/purchases/admin/all?${queryParams.toString()}`)
      purchases.value = response.data
      pagination.value = {
        current_page: response.current_page,
        last_page: response.last_page,
        per_page: response.per_page,
        total: response.total
      }
      return response
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'Failed to fetch purchases'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    purchases,
    pagination,
    loading,
    error,
    createPurchase,
    createPayment,
    fetchPurchaseById,
    fetchAdminPurchases
  }
})
