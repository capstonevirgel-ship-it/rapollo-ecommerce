import { defineStore } from 'pinia'
import { useCustomFetch } from '~/composables/useCustomFetch'

export const useAdminCountsStore = defineStore('adminCounts', () => {
  const pendingOrdersCount = ref(0)
  const pendingTicketsCount = ref(0)
  const loading = ref(false)

  // Fetch pending orders count
  const fetchPendingOrdersCount = async () => {
    try {
      const response = await useCustomFetch<{ count: number }>('/api/admin/counts/pending-orders')
      pendingOrdersCount.value = response.count || 0
      return response.count
    } catch (error: any) {
      // Handle authentication errors gracefully
      if (error.status === 401 || error.statusCode === 401) {
        console.warn('User not authenticated, skipping pending orders count fetch')
        pendingOrdersCount.value = 0
        return 0
      }
      console.error('Failed to fetch pending orders count:', error)
      throw error
    }
  }

  // Fetch pending tickets count
  const fetchPendingTicketsCount = async () => {
    try {
      const response = await useCustomFetch<{ count: number }>('/api/admin/counts/pending-tickets')
      pendingTicketsCount.value = response.count || 0
      return response.count
    } catch (error: any) {
      // Handle authentication errors gracefully
      if (error.status === 401 || error.statusCode === 401) {
        console.warn('User not authenticated, skipping pending tickets count fetch')
        pendingTicketsCount.value = 0
        return 0
      }
      console.error('Failed to fetch pending tickets count:', error)
      throw error
    }
  }

  // Fetch all counts
  const fetchAllCounts = async () => {
    loading.value = true
    try {
      await Promise.all([
        fetchPendingOrdersCount(),
        fetchPendingTicketsCount()
      ])
    } catch (error) {
      console.error('Failed to fetch admin counts:', error)
    } finally {
      loading.value = false
    }
  }

  // Increment pending orders count
  const incrementPendingOrders = () => {
    pendingOrdersCount.value = pendingOrdersCount.value + 1
  }

  // Decrement pending orders count
  const decrementPendingOrders = () => {
    if (pendingOrdersCount.value > 0) {
      pendingOrdersCount.value = pendingOrdersCount.value - 1
    }
  }

  // Increment pending tickets count
  const incrementPendingTickets = () => {
    pendingTicketsCount.value = pendingTicketsCount.value + 1
  }

  // Decrement pending tickets count
  const decrementPendingTickets = () => {
    if (pendingTicketsCount.value > 0) {
      pendingTicketsCount.value = pendingTicketsCount.value - 1
    }
  }

  // Update pending orders count (set directly)
  const setPendingOrdersCount = (count: number) => {
    pendingOrdersCount.value = Math.max(0, count)
  }

  // Update pending tickets count (set directly)
  const setPendingTicketsCount = (count: number) => {
    pendingTicketsCount.value = Math.max(0, count)
  }

  return {
    pendingOrdersCount,
    pendingTicketsCount,
    loading,
    fetchPendingOrdersCount,
    fetchPendingTicketsCount,
    fetchAllCounts,
    incrementPendingOrders,
    decrementPendingOrders,
    incrementPendingTickets,
    decrementPendingTickets,
    setPendingOrdersCount,
    setPendingTicketsCount
  }
})

