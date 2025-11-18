import { defineStore } from 'pinia'
import { useCustomFetch } from '~/composables/useCustomFetch'

export interface Order {
  id: number
  user_id: number
  total: number
  status: 'pending' | 'processing' | 'completed' | 'cancelled' | 'shipped' | 'delivered'
  type?: 'product' | 'ticket'
  created_at: string
  updated_at: string
  items?: OrderItem[]
  payment?: Payment
  user?: {
    id: number
    user_name: string
    email: string
    role: string
    profile?: {
      id: number
      user_id: number
      full_name: string
      phone?: string
      address?: string
    }
  }
}

export interface OrderItem {
  id: number
  purchase_id: number
  variant_id: number
  quantity: number
  price: number
  variant?: {
    id: number
    product_id: number
    size_id?: number
    color_id?: number
    price: number
    size?: {
      id: number
      name: string
    }
    color?: {
      id: number
      name: string
    }
    images?: Array<{
      id: number
      variant_id: number
      url: string
    }>
    product?: {
      id: number
      name: string
      slug: string
      subcategory_id: number
      subcategory?: {
        id: number
        name: string
        slug: string
        category_id: number
        category?: {
          id: number
          name: string
          slug: string
        }
      }
      images?: Array<{
        id: number
        product_id: number
        url: string
      }>
    }
  }
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

export const useOrderStore = defineStore('order', () => {
  const orders = ref<Order[]>([])
  const pagination = ref<any>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Fetch orders for admin
  const fetchOrders = async (filters: {
    status?: string
    search?: string
    per_page?: number
  } = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const queryParams = new URLSearchParams()
      if (filters.status && filters.status !== 'all') {
        queryParams.append('status', filters.status)
      }
      if (filters.search) {
        queryParams.append('search', filters.search)
      }
      if (filters.per_page) {
        queryParams.append('per_page', filters.per_page.toString())
      }
      
      const response = await useCustomFetch<any>(`/api/orders/admin?${queryParams.toString()}`)
      
      // Handle paginated response
      orders.value = response.data || []
      pagination.value = {
        current_page: response.current_page || 1,
        last_page: response.last_page || 1,
        per_page: response.per_page || 20,
        total: response.total || 0
      }
      
      return response
    } catch (err: any) {
      console.error('Error fetching orders:', err)
      error.value = err.data?.message || err.message || 'Failed to fetch orders'
      orders.value = []
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update order status
  const updateOrderStatus = async (orderId: number, status: string) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await useCustomFetch(`/api/purchases/${orderId}/status`, {
        method: 'PUT',
        body: { status }
      })
      
      // Update local state
      const order = orders.value.find(o => o.id === orderId)
      if (order) {
        order.status = status as any
      }
      
      return response
    } catch (err: any) {
      error.value = err.data?.message || err.message || 'Failed to update order status'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    orders,
    pagination,
    loading,
    error,
    fetchOrders,
    updateOrderStatus
  }
})

