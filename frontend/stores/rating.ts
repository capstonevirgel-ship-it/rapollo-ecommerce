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
        this.ratings = response.data
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
        this.userRating = response
        return response
      } catch (err: any) {
        this.error = err.data?.message || err.message || "Failed to fetch user rating"
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

    async createRating(payload: RatingPayload) {
      this.loading = true
      this.error = null
      
      try {
        const response = await useCustomFetch<Rating>("/api/ratings", {
          method: "POST",
          body: payload
        })
        
        // Update local state
        const existingIndex = this.ratings.findIndex(r => r.variant_id === payload.variant_id && r.user_id === response.user_id)
        if (existingIndex !== -1) {
          this.ratings[existingIndex] = response
        } else {
          this.ratings.unshift(response)
        }
        
        this.userRating = response
        return response
      } catch (err: any) {
        this.error = err.data?.message || err.message || "Failed to create rating"
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
        this.error = err.data?.message || err.message || "Failed to delete rating"
        throw err
      } finally {
        this.loading = false
      }
    }
  }
})
