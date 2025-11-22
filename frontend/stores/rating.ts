import { defineStore } from "pinia"
import { useCustomFetch } from "~/composables/useCustomFetch"
import type { Rating, RatingStatistics, ReviewableProduct, RatingPayload } from "~/types"

export const useRatingStore = defineStore("rating", {
  state: () => ({
    ratings: [] as Rating[],
    statistics: null as RatingStatistics | null,
    reviewableProducts: [] as ReviewableProduct[],
    userRating: null as Rating | null,
    loading: false,
    error: null as string | null
  }),

  actions: {
    async fetchRatings(variantId: number) {
      this.loading = true
      this.error = null
      
      try {
        const response = await useCustomFetch<any>("/api/ratings", {
          query: { variant_id: variantId }
        })
        // Handle both paginated and non-paginated responses
        this.ratings = Array.isArray(response) ? response : (response.data || [])
        return response
      } catch (err: any) {
        this.error = err.data?.message || err.message || "Failed to fetch ratings"
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchUserRating(variantId: number) {
      this.loading = true
      this.error = null
      
      try {
        const response = await useCustomFetch<Rating>("/api/ratings/user", {
          query: { variant_id: variantId }
        })
        // Ensure we handle null/undefined responses correctly
        this.userRating = response && response.id ? response : null
        return response
      } catch (err: any) {
        // Don't set error for 403/404 - user might not have purchased the product
        if (err.status === 403 || err.status === 404 || err.statusCode === 403 || err.statusCode === 404) {
          this.userRating = null
          return null
        }
        
        this.error = err.data?.message || err.message || "Failed to fetch user rating"
        this.userRating = null
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchStatistics(variantId: number) {
      this.loading = true
      this.error = null
      
      try {
        const response = await useCustomFetch<RatingStatistics>("/api/ratings/statistics", {
          query: { variant_id: variantId }
        })
        this.statistics = response
        return response
      } catch (err: any) {
        this.error = err.data?.message || err.message || "Failed to fetch rating statistics"
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchReviewableProducts() {
      this.loading = true
      this.error = null
      
      try {
        const response = await useCustomFetch<ReviewableProduct[]>("/api/ratings/reviewable-products")
        this.reviewableProducts = response
        return response
      } catch (err: any) {
        this.error = err.data?.message || err.message || "Failed to fetch reviewable products"
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchReviewedProducts() {
      this.loading = true
      this.error = null
      
      try {
        const response = await useCustomFetch<ReviewableProduct[]>("/api/ratings/reviewed-products")
        // Handle both array and wrapped responses
        const products = Array.isArray(response) ? response : (response.data || [])
        this.reviewableProducts = products
        return products
      } catch (err: any) {
        this.error = err.data?.message || err.message || "Failed to fetch reviewed products"
        throw err
      } finally {
        this.loading = false
      }
    },

    async createRating(payload: RatingPayload) {
      this.loading = true
      this.error = null
      
      try {
        const response = await useCustomFetch<{ message: string; rating: Rating }>("/api/ratings", {
          method: "POST",
          body: payload
        })
        
        // Extract the rating from the response
        const rating = response.rating || response as any
        
        // Update local state
        const existingIndex = this.ratings.findIndex(r => r.variant_id === payload.variant_id && r.user_id === rating.user_id)
        if (existingIndex !== -1) {
          this.ratings[existingIndex] = rating
        } else {
          this.ratings.unshift(rating)
        }
        
        this.userRating = rating
        return rating
      } catch (err: any) {
        // Don't set global error for submission errors - let the component handle it
        throw err
      } finally {
        this.loading = false
      }
    },

    async deleteRating(variantId: number) {
      this.loading = true
      this.error = null
      
      try {
        await useCustomFetch("/api/ratings", {
          method: "DELETE",
          query: { variant_id: variantId }
        })
        
        // Update local state
        this.ratings = this.ratings.filter(r => !(r.variant_id === variantId && r.user_id === this.userRating?.user_id))
        this.userRating = null
        
        return true
      } catch (err: any) {
        // Don't set global error for deletion errors - let the component handle it
        throw err
      } finally {
        this.loading = false
      }
    },

    clearError() {
      this.error = null
    }
  }
})
