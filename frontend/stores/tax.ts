import { defineStore } from 'pinia'

export interface TaxPrice {
  id: number
  name: string
  rate: number
  description: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface TaxPriceForm {
  name: string
  rate: number
  description?: string
  is_active: boolean
}

export const useTaxStore = defineStore('tax', {
  state: () => ({
    taxPrices: [] as TaxPrice[],
    loading: false,
    error: null as string | null
  }),

  getters: {
    activeTaxPrices: (state) => state.taxPrices.filter(price => price.is_active),
    
    totalTaxRate: (state) => {
      if (!state.taxPrices || state.taxPrices.length === 0) {
        return 0
      }
      const activeTaxes = state.taxPrices.filter(price => price.is_active)
      if (activeTaxes.length === 0) {
        return 0
      }
      return activeTaxes.reduce((sum, tax) => sum + (tax.rate || 0), 0)
    },

    getTaxPriceById: (state) => (id: number) => {
      return state.taxPrices.find(price => price.id === id)
    }
  },

  actions: {
    async fetchTaxPrices() {
      this.loading = true
      this.error = null
      
      try {
        const response = await $fetch<{
          data: TaxPrice[]
        }>('/api/tax-prices')
        
        this.taxPrices = response.data
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to fetch tax prices'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createTaxPrice(data: TaxPriceForm) {
      this.loading = true
      this.error = null
      
      try {
        const response = await $fetch<{
          message: string
          data: TaxPrice
        }>('/api/tax-prices', {
          method: 'POST',
          body: data
        })
        
        this.taxPrices.push(response.data)
        return response.data
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to create tax price'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateTaxPrice(id: number, data: Partial<TaxPriceForm>) {
      this.loading = true
      this.error = null
      
      try {
        const response = await $fetch<{
          message: string
          data: TaxPrice
        }>(`/api/tax-prices/${id}`, {
          method: 'PUT',
          body: data
        })
        
        const index = this.taxPrices.findIndex(price => price.id === id)
        if (index !== -1) {
          this.taxPrices[index] = response.data
        }
        
        return response.data
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to update tax price'
        throw error
      } finally {
        this.loading = false
      }
    },

    async toggleTaxActive(id: number, isActive: boolean) {
      this.error = null
      
      try {
        const response = await $fetch<{
          message: string
          data: TaxPrice
        }>(`/api/tax-prices/${id}`, {
          method: 'PUT',
          body: { is_active: isActive }
        })
        
        const index = this.taxPrices.findIndex(price => price.id === id)
        if (index !== -1) {
          this.taxPrices[index] = response.data
        }
        
        return response.data
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to toggle tax price status'
        throw error
      }
    },

    async deleteTaxPrice(id: number) {
      this.loading = true
      this.error = null
      
      try {
        await $fetch(`/api/tax-prices/${id}`, {
          method: 'DELETE'
        })
        
        this.taxPrices = this.taxPrices.filter(price => price.id !== id)
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to delete tax price'
        throw error
      } finally {
        this.loading = false
      }
    },

    async getActiveTaxRate() {
      try {
        const response = await $fetch<{
          data: {
            total_rate: number
            taxes: Array<{ name: string; rate: number }>
          }
        }>('/api/tax-prices/active')
        
        return response.data
      } catch (error: any) {
        this.error = error.data?.message || error.message || 'Failed to fetch active tax rate'
        throw error
      }
    }
  }
})

