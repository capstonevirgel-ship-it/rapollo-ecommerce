import { defineStore } from 'pinia'
import { useCartStore } from '~/stores/cart'
import { useCustomFetch } from '~/composables/useCustomFetch'
import type { ProductPurchase, ProductPurchaseItem, Payment, PaymentResponse, ProductPurchaseCreateResponse, ProductPurchaseFetchResponse, ProductPurchaseListResponse } from '~/types'

export const usePurchaseStore = defineStore('purchase', () => {
  const purchases = ref<ProductPurchase[]>([])
  const pagination = ref<any>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const createPurchase = async (cartItems: any[]) => {
    const cartStore = useCartStore()
    // Map cart items to the format expected by the backend (robust to item shapes)
    const items = cartItems.map((item: any) => {
      // Try multiple sources and fallbacks, including lookup in current cart by cart item id
      const fromStoreByCartId = item.id ? cartStore.cart.find((ci: any) => ci.id === item.id) : undefined
      const variantId = item.variant_id
        ?? item.variant?.id
        ?? item.variantId
        ?? fromStoreByCartId?.variant_id
        ?? fromStoreByCartId?.variant?.id
      // Use product base_price (before tax) to avoid double taxation on backend
      // The backend will calculate tax on top of this base price
      const variantProduct = (item.variant as any)?.product ?? (fromStoreByCartId?.variant as any)?.product
      const price = variantProduct?.base_price ?? variantProduct?.price ?? (item.variant as any)?.base_price ?? (item.variant as any)?.price ?? item.price ?? (fromStoreByCartId?.variant as any)?.base_price ?? (fromStoreByCartId?.variant as any)?.price ?? 0
      const quantity = item.quantity ?? 1
      return { variant_id: variantId, quantity, price }
    })

    const response = await useCustomFetch<ProductPurchaseCreateResponse>('/api/product-purchases', {
      method: 'POST',
      body: {
        items: items
      }
    })
    return response.data
  }

  const createPayment = async (purchaseId: number, amount: number, paymentMethod: string): Promise<PaymentResponse> => {
    const response = await useCustomFetch('/api/payments/create', {
      method: 'POST',
      body: {
        purchasable_type: 'App\\Models\\ProductPurchase',
        purchasable_id: purchaseId,
        amount: amount,
        currency: 'PHP',
        payment_method: paymentMethod
      }
    })
    return response as PaymentResponse
  }

  // Fetch single purchase by ID
  const fetchPurchaseById = async (id: number): Promise<ProductPurchase> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await useCustomFetch<ProductPurchaseFetchResponse>(`/api/product-purchases/${id}`)
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
    search?: string
    per_page?: number
  } = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const queryParams = new URLSearchParams()
      if (filters.status) queryParams.append('status', filters.status)
      if (filters.search) queryParams.append('search', filters.search)
      if (filters.per_page) queryParams.append('per_page', filters.per_page.toString())
      
      const response = await useCustomFetch<ProductPurchaseListResponse>(`/api/product-purchases/admin/all?${queryParams.toString()}`)
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
