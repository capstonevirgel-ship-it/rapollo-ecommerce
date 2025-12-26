import { defineStore } from 'pinia'
import { useCustomFetch } from '~/composables/useCustomFetch'

export interface PayMongoPaymentIntent {
  id: string
  attributes: {
    amount: number
    currency: string
    status: string
    client_key: string
    payment_method_allowed: string[]
    metadata: any
  }
}

export interface PayMongoPaymentMethod {
  id: string
  type: string
  attributes: {
    type: string
    details: any
  }
}

export interface PayMongoCheckoutSession {
  id: string
  type: string
  attributes: {
    amount: number
    currency: string
    checkout_url: string
    status: string
    payment_method_allowed: string[]
    metadata: any
    success_url: string
    cancel_url: string
  }
}

export interface PayMongoPaymentResponse {
  message: string
  payment_url: string
  checkout_session: PayMongoCheckoutSession
  payment_id: number
}

export interface PayMongoConfirmResponse {
  message: string
  status: string
  payment_intent: PayMongoPaymentIntent
  purchase_status: string
}

export const usePayMongoStore = defineStore('paymongo', () => {
  const createPaymentIntent = async (
    amount: number, 
    purchasableType: 'App\\Models\\ProductPurchase' | 'App\\Models\\TicketPurchase',
    purchasableId: number, 
    metadata: any = {}
  ): Promise<PayMongoPaymentResponse> => {
    const response = await useCustomFetch('/api/payments/paymongo/intent', {
      method: 'POST',
      body: {
        amount,
        currency: 'PHP',
        purchasable_type: purchasableType,
        purchasable_id: purchasableId,
        metadata
      }
    })
    return response as PayMongoPaymentResponse
  }

  const confirmPayment = async (
    paymentIntentId: string, 
    paymentMethodId: string, 
    purchasableType: 'App\\Models\\ProductPurchase' | 'App\\Models\\TicketPurchase',
    purchasableId: number
  ): Promise<PayMongoConfirmResponse> => {
    const response = await useCustomFetch('/api/payments/paymongo/confirm', {
      method: 'POST',
      body: {
        payment_intent_id: paymentIntentId,
        payment_method_id: paymentMethodId,
        purchasable_type: purchasableType,
        purchasable_id: purchasableId
      }
    })
    return response as PayMongoConfirmResponse
  }

  const createRefund = async (paymentId: string, amount: number, reason: string = 'requested_by_customer') => {
    const response = await useCustomFetch('/api/payments/paymongo/refund', {
      method: 'POST',
      body: {
        payment_id: paymentId,
        amount,
        reason
      }
    })
    return response
  }

  return {
    createPaymentIntent,
    confirmPayment,
    createRefund
  }
})
