import { defineStore } from 'pinia'

export interface Purchase {
  id: number
  user_id: number
  total: number
  status: 'pending' | 'processing' | 'completed' | 'cancelled'
  created_at: string
  updated_at: string
  items?: PurchaseItem[]
  payment?: Payment
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

  // Removed PayMongo payment methods

  return {
    createPurchase,
    createPayment
  }
})
