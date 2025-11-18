import { defineStore } from 'pinia'
import type { Ticket, Event, PaginatedResponse } from '~/types'

export const useTicketStore = defineStore('ticket', () => {
  const tickets = ref<Ticket[]>([])
  const pagination = ref<any>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Fetch user's tickets
  const fetchTickets = async () => {
    loading.value = true
    error.value = null
    try {
      console.log('Fetching tickets...')
      const response = await useCustomFetch<any>('/api/tickets')
      console.log('Tickets API response:', response)
      // Handle Laravel paginated response - data might be directly in response or response.data
      tickets.value = Array.isArray(response) ? response : (response.data || [])
      console.log('Tickets loaded:', tickets.value.length)
    } catch (err: any) {
      error.value = err?.data?.message || err?.data?.error || 'Failed to fetch tickets'
      console.error('Error fetching tickets:', err)
      tickets.value = []
    } finally {
      loading.value = false
    }
  }

  // Book tickets for an event (without payment)
  const bookTickets = async (eventId: number, quantity: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<any>('/api/tickets', {
        method: 'POST',
        body: {
          event_id: eventId,
          quantity
        }
      })
      
      // Refresh tickets list
      await fetchTickets()
      
      return response
    } catch (err: any) {
      error.value = err?.data?.message || err?.data?.error || 'Failed to book tickets'
      console.error('Error booking tickets:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Create payment intent for tickets
  const createTicketPaymentIntent = async (eventId: number, quantity: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<any>('/api/tickets/payment-intent', {
        method: 'POST',
        body: {
          event_id: eventId,
          quantity
        }
      })
      
      // Store purchase ID for success page
      if (response.purchase_id) {
        if (import.meta.client) {
          sessionStorage.setItem('last_purchase_id', response.purchase_id.toString())
        }
      }
      
      return response
    } catch (err: any) {
      error.value = err?.data?.message || err?.data?.error || 'Failed to create payment intent'
      console.error('Error creating payment intent:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Confirm ticket payment
  const confirmTicketPayment = async (paymentIntentId: string, paymentMethodId: string, purchaseId: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<any>('/api/tickets/confirm-payment', {
        method: 'POST',
        body: {
          payment_intent_id: paymentIntentId,
          payment_method_id: paymentMethodId,
          purchase_id: purchaseId
        }
      })
      
      // Refresh tickets list
      await fetchTickets()
      
      return response
    } catch (err: any) {
      error.value = err?.data?.message || err?.data?.error || 'Failed to confirm payment'
      console.error('Error confirming payment:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Cancel a ticket
  const cancelTicket = async (ticketId: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<any>(`/api/tickets/${ticketId}/cancel`, {
        method: 'PUT'
      })
      
      // Update the ticket in the local state
      const index = tickets.value.findIndex(t => t.id === ticketId)
      if (index !== -1) {
        tickets.value[index].status = 'cancelled'
      }
      
      return response
    } catch (err: any) {
      error.value = err?.data?.message || err?.data?.error || 'Failed to cancel ticket'
      console.error('Error cancelling ticket:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Get ticket by ID
  const getTicket = async (ticketId: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<Ticket>(`/api/tickets/${ticketId}`)
      return response
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch ticket'
      console.error('Error fetching ticket:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Admin functions
  const fetchAllTickets = async (params: {
    status?: string
    search?: string
    per_page?: number
    page?: number
  } = {}) => {
    loading.value = true
    error.value = null
    try {
      const query = new URLSearchParams()
      if (params.status && params.status !== 'all') query.append('status', params.status)
      if (params.search) query.append('search', params.search)
      if (params.per_page) query.append('per_page', params.per_page.toString())
      if (params.page) query.append('page', params.page.toString())

      const url = query.toString() ? `/api/tickets/admin/all?${query.toString()}` : '/api/tickets/admin/all'
      const response = await useCustomFetch<any>(url)
      
      tickets.value = response.data || []
      pagination.value = {
        current_page: response.current_page,
        last_page: response.last_page,
        per_page: response.per_page,
        total: response.total,
        from: response.from,
        to: response.to
      }
      
      return response
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch all tickets'
      console.error('Error fetching all tickets:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchEventTickets = async (eventId: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<Ticket[]>(`/api/tickets/event/${eventId}`)
      return response
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch event tickets'
      console.error('Error fetching event tickets:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateTicketStatus = async (ticketId: number, status: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<any>(`/api/tickets/${ticketId}/status`, {
        method: 'PUT',
        body: { status }
      })
      
      // Update the ticket in the local state
      const index = tickets.value.findIndex(t => t.id === ticketId)
      if (index !== -1) {
        tickets.value[index].status = status as 'pending' | 'confirmed' | 'cancelled' | 'used'
      }
      
      return response
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update ticket status'
      console.error('Error updating ticket status:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchTicketStatistics = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<any>('/api/tickets/statistics')
      return response
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch ticket statistics'
      console.error('Error fetching ticket statistics:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    tickets,
    pagination,
    loading,
    error,
    fetchTickets,
    bookTickets,
    createTicketPaymentIntent,
    confirmTicketPayment,
    cancelTicket,
    getTicket,
    fetchAllTickets,
    fetchEventTickets,
    updateTicketStatus,
    fetchTicketStatistics
  }
})
