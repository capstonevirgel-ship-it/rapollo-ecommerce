import { defineStore } from 'pinia'
import type { EventComment, PaginatedResponse } from '~/types'

export const useEventCommentStore = defineStore('eventComment', () => {
  const comments = ref<EventComment[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0
  })

  // Fetch comments for an event
  const fetchComments = async (eventId: number, page: number = 1) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<PaginatedResponse<EventComment>>(`/api/events/${eventId}/comments?page=${page}`)
      comments.value = response.data || []
      pagination.value = {
        current_page: response.current_page || 1,
        last_page: response.last_page || 1,
        per_page: response.per_page || 10,
        total: response.total || 0,
        from: response.from || 0,
        to: response.to || 0
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch comments'
      console.error('Error fetching comments:', err)
    } finally {
      loading.value = false
    }
  }

  // Add a new comment
  const addComment = async (eventId: number, comment: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<{ comment: EventComment }>(`/api/events/${eventId}/comments`, {
        method: 'POST',
        body: { comment }
      })
      
      // Add to comments list at the beginning (newest first)
      comments.value.unshift(response.comment)
      
      return response.comment
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to add comment'
      console.error('Error adding comment:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update a comment
  const updateComment = async (eventId: number, commentId: number, comment: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await useCustomFetch<{ comment: EventComment }>(`/api/events/${eventId}/comments/${commentId}`, {
        method: 'PUT',
        body: { comment }
      })
      
      // Update in comments list
      const index = comments.value.findIndex(c => c.id === commentId)
      if (index !== -1) {
        comments.value[index] = response.comment
      }
      
      return response.comment
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update comment'
      console.error('Error updating comment:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Delete a comment
  const deleteComment = async (eventId: number, commentId: number) => {
    loading.value = true
    error.value = null
    try {
      await useCustomFetch(`/api/events/${eventId}/comments/${commentId}`, {
        method: 'DELETE'
      })
      
      // Remove from comments list
      comments.value = comments.value.filter(c => c.id !== commentId)
      
      return true
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to delete comment'
      console.error('Error deleting comment:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Clear comments (when switching events)
  const clearComments = () => {
    comments.value = []
    pagination.value = {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0
    }
  }

  return {
    comments,
    loading,
    error,
    pagination,
    fetchComments,
    addComment,
    updateComment,
    deleteComment,
    clearComments
  }
})
