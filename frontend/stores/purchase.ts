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

export interface PayMongoPaymentResponse {
  message: string
  payment_intent_id: string
  client_key: string
  purchase_id: number
  redirect_url: string
}

export interface PayMongoVerifyResponse {
  status: string
  amount: number
  currency: string
}

export const usePurchaseStore = defineStore('purchase', () => {
  const createPurchase = async (cartItems: any[]) => {
    const response = await $fetch('/api/purchases', {
      method: 'POST',
      body: {
        items: cartItems // cartItems are already mapped in cart.vue
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

  // PayMongo payment methods
  const createPayMongoPayment = async (cartData: any): Promise<PayMongoPaymentResponse> => {
    const response = await $fetch('/api/payments/paymongo/create', {
      method: 'POST',
      body: cartData
    })
    return response as PayMongoPaymentResponse
  }

  const verifyPayMongoPayment = async (paymentIntentId: string): Promise<PayMongoVerifyResponse> => {
    const response = await $fetch('/api/payments/paymongo/verify', {
      method: 'POST',
      body: {
        payment_intent_id: paymentIntentId
      }
    })
    return response as PayMongoVerifyResponse
  }

  return {
    createPurchase,
    createPayment,
    createPayMongoPayment,
    verifyPayMongoPayment
  }
})
