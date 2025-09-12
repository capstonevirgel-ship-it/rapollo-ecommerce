import { defineStore } from 'pinia'
import type { Ticket, Event, PaginatedResponse } from '~/types'

export const useTicketStore = defineStore('ticket', () => {
  const tickets = ref<Ticket[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Fetch user's tickets
  const fetchTickets = async () => {
    loading.value = true
    error.value = null
    try {
      console.log('Fetching tickets...')
      const response = await useCustomFetch<PaginatedResponse<Ticket>>('/api/tickets')
      console.log('Tickets API response:', response)
      tickets.value = response.data || []
      console.log('Tickets loaded:', tickets.value.length)
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch tickets'
      console.error('Error fetching tickets:', err)
    } finally {
      loading.value = false
    }
  }

  // Book tickets for an event
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
      error.value = err.data?.message || 'Failed to book tickets'
      console.error('Error booking tickets:', err)
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
      error.value = err.data?.message || 'Failed to cancel ticket'
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
  const fetchAllTickets = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<PaginatedResponse<Ticket>>('/api/tickets/admin/all')
      tickets.value = response.data || []
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch all tickets'
      console.error('Error fetching all tickets:', err)
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
    loading,
    error,
    fetchTickets,
    bookTickets,
    cancelTicket,
    getTicket,
    fetchAllTickets,
    fetchEventTickets,
    updateTicketStatus,
    fetchTicketStatistics
  }
})
