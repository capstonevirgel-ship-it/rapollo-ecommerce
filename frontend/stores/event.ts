import { defineStore } from 'pinia'
import type { Event, PaginatedResponse } from '~/types'

export const useEventStore = defineStore('event', () => {
  const events = ref<Event[]>([])
  const currentEvent = ref<Event | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Fetch all events
  const fetchEvents = async () => {
    loading.value = true
    error.value = null
    try {
      console.log('Fetching events...')
      const response = await useCustomFetch<PaginatedResponse<Event>>('/api/events')
      console.log('API response:', response)
      events.value = response.data || []
      console.log('Events loaded:', events.value.length)
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch events'
      console.error('Error fetching events:', err)
    } finally {
      loading.value = false
    }
  }

  // Fetch single event
  const fetchEvent = async (eventId: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<Event>(`/api/events/${eventId}`)
      currentEvent.value = response
      return response
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch event'
      console.error('Error fetching event:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Create event (admin only)
  const createEvent = async (eventData: Partial<Event>) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<any>('/api/events', {
        method: 'POST',
        body: eventData
      })
      
      // Add to events list
      events.value.unshift(response.event)
      
      return response
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to create event'
      console.error('Error creating event:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update event (admin only)
  const updateEvent = async (eventId: number, eventData: Partial<Event>) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<any>(`/api/events/${eventId}`, {
        method: 'PUT',
        body: eventData
      })
      
      // Update in events list
      const index = events.value.findIndex(e => e.id === eventId)
      if (index !== -1) {
        events.value[index] = response.event
      }
      
      // Update current event if it's the same
      if (currentEvent.value?.id === eventId) {
        currentEvent.value = response.event
      }
      
      return response
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update event'
      console.error('Error updating event:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Delete event (admin only)
  const deleteEvent = async (eventId: number) => {
    loading.value = true
    error.value = null
    try {
      await useCustomFetch<any>(`/api/events/${eventId}`, {
        method: 'DELETE'
      })
      
      // Remove from events list
      events.value = events.value.filter(e => e.id !== eventId)
      
      // Clear current event if it's the same
      if (currentEvent.value?.id === eventId) {
        currentEvent.value = null
      }
      
      return true
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to delete event'
      console.error('Error deleting event:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    events,
    currentEvent,
    loading,
    error,
    fetchEvents,
    fetchEvent,
    createEvent,
    updateEvent,
    deleteEvent
  }
})
