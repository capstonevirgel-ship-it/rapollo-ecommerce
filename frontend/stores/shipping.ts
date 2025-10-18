import { defineStore } from 'pinia'

export interface ShippingPrice {
  id: number
  region: string
  price: number
  description: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface ShippingPriceForm {
  region: string
  price: number
  description?: string
  is_active: boolean
}

export const useShippingStore = defineStore('shipping', {
  state: () => ({
    shippingPrices: [] as ShippingPrice[],
    availableRegions: {} as Record<string, string>,
    loading: false,
    error: null as string | null
  }),

  getters: {
    activeShippingPrices: (state) => state.shippingPrices.filter(price => price.is_active),
    
    getPriceForRegion: (state) => (region: string) => {
      const shippingPrice = state.shippingPrices.find(price => 
        price.region === region && price.is_active
      )
      return shippingPrice ? shippingPrice.price : null
    },

    getShippingPriceById: (state) => (id: number) => {
      return state.shippingPrices.find(price => price.id === id)
    }
  },

  actions: {
    async fetchShippingPrices() {
      this.loading = true
      this.error = null
      
      try {
        const response = await $fetch<{
          data: ShippingPrice[]
          available_regions: Record<string, string>
        }>('/api/shipping-prices')
        
        this.shippingPrices = response.data
        this.availableRegions = response.available_regions
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to fetch shipping prices'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createShippingPrice(data: ShippingPriceForm) {
      this.loading = true
      this.error = null
      
      try {
        const response = await $fetch<{
          message: string
          data: ShippingPrice
        }>('/api/shipping-prices', {
          method: 'POST',
          body: data
        })
        
        this.shippingPrices.push(response.data)
        return response.data
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to create shipping price'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateShippingPrice(id: number, data: Partial<ShippingPriceForm>) {
      this.loading = true
      this.error = null
      
      try {
        const response = await $fetch<{
          message: string
          data: ShippingPrice
        }>(`/api/shipping-prices/${id}`, {
          method: 'PUT',
          body: data
        })
        
        const index = this.shippingPrices.findIndex(price => price.id === id)
        if (index !== -1) {
          this.shippingPrices[index] = response.data
        }
        
        return response.data
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to update shipping price'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteShippingPrice(id: number) {
      this.loading = true
      this.error = null
      
      try {
        await $fetch(`/api/shipping-prices/${id}`, {
          method: 'DELETE'
        })
        
        this.shippingPrices = this.shippingPrices.filter(price => price.id !== id)
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to delete shipping price'
        throw error
      } finally {
        this.loading = false
      }
    },

    async bulkUpdateShippingPrices(prices: Array<{ id: number; price: number; is_active: boolean }>) {
      this.loading = true
      this.error = null
      
      try {
        await $fetch('/api/shipping-prices/bulk-update', {
          method: 'PUT',
          body: { prices }
        })
        
        // Update local state
        prices.forEach(priceData => {
          const index = this.shippingPrices.findIndex(price => price.id === priceData.id)
          if (index !== -1) {
            this.shippingPrices[index].price = priceData.price
            this.shippingPrices[index].is_active = priceData.is_active
          }
        })
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to update shipping prices'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchActiveShippingPrices() {
      try {
        const response = await $fetch<{
          data: Record<string, number>
        }>('/api/shipping-prices/active')
        
        return response.data
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to fetch active shipping prices'
        throw error
      }
    }
  }
})
