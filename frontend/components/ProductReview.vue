<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRatingStore } from '~/stores/rating'
import { useAlert } from '~/composables/useAlert'
import type { RatingPayload } from '~/types'

interface Props {
  variantId: number
  productName: string
}

const props = defineProps<Props>()
const ratingStore = useRatingStore()
const { success, error } = useAlert()

const isSubmitting = ref(false)
const showReviewForm = ref(false)
const stars = ref(0)
const comment = ref('')
const hoveredStar = ref(0)

const averageRating = computed(() => {
  if (!ratingStore.statistics) return 0
  return Math.round(ratingStore.statistics.average_rating * 10) / 10
})

const totalRatings = computed(() => {
  return ratingStore.statistics?.total_ratings || 0
})

const hasUserRated = computed(() => {
  return !!ratingStore.userRating
})

const starDistribution = computed(() => {
  if (!ratingStore.statistics) return []
  
  const stats = ratingStore.statistics
  const total = stats.total_ratings
  
  return [
    { stars: 5, count: stats.five_star, percentage: total > 0 ? (stats.five_star / total) * 100 : 0 },
    { stars: 4, count: stats.four_star, percentage: total > 0 ? (stats.four_star / total) * 100 : 0 },
    { stars: 3, count: stats.three_star, percentage: total > 0 ? (stats.three_star / total) * 100 : 0 },
    { stars: 2, count: stats.two_star, percentage: total > 0 ? (stats.two_star / total) * 100 : 0 },
    { stars: 1, count: stats.one_star, percentage: total > 0 ? (stats.one_star / total) * 100 : 0 }
  ]
})

onMounted(async () => {
  try {
    await Promise.all([
      ratingStore.fetchRatings(props.variantId),
      ratingStore.fetchUserRating(props.variantId),
      ratingStore.fetchStatistics(props.variantId)
    ])
    
    // If user has already rated, populate the form
    if (ratingStore.userRating) {
      stars.value = ratingStore.userRating.stars
      comment.value = ratingStore.userRating.comment || ''
    }
  } catch (err) {
    console.error('Failed to load ratings:', err)
  }
})

const handleStarClick = (star: number) => {
  stars.value = star
}

const handleStarHover = (star: number) => {
  hoveredStar.value = star
}

const handleStarLeave = () => {
  hoveredStar.value = 0
}

const submitReview = async () => {
  if (stars.value === 0) {
    error('Rating Required', 'Please select a star rating before submitting.')
    return
  }

  isSubmitting.value = true
  
  try {
    const payload: RatingPayload = {
      variant_id: props.variantId,
      stars: stars.value,
      comment: comment.value.trim() || undefined
    }

    await ratingStore.createRating(payload)
    
    // Refresh statistics
    await ratingStore.fetchStatistics(props.variantId)
    
    success(
      hasUserRated.value ? 'Review Updated!' : 'Review Submitted!',
      `Thank you for your ${hasUserRated.value ? 'updated' : ''} review of ${props.productName}.`
    )
    
    showReviewForm.value = false
  } catch (err: any) {
    error('Review Failed', err.message || 'Failed to submit review. Please try again.')
  } finally {
    isSubmitting.value = false
  }
}

const deleteReview = async () => {
  if (!confirm('Are you sure you want to delete your review?')) {
    return
  }

  try {
    await ratingStore.deleteRating(props.variantId)
    await ratingStore.fetchStatistics(props.variantId)
    
    stars.value = 0
    comment.value = ''
    
    success('Review Deleted', 'Your review has been removed.')
  } catch (err: any) {
    error('Delete Failed', err.message || 'Failed to delete review. Please try again.')
  }
}

const editReview = () => {
  showReviewForm.value = true
}

const cancelReview = () => {
  showReviewForm.value = false
  if (ratingStore.userRating) {
    stars.value = ratingStore.userRating.stars
    comment.value = ratingStore.userRating.comment || ''
  }
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900">Customer Reviews</h3>
      <button
        v-if="!hasUserRated"
        @click="showReviewForm = true"
        class="px-4 py-2 bg-zinc-900 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors"
      >
        Write a Review
      </button>
    </div>

    <!-- Rating Summary -->
    <div v-if="totalRatings > 0" class="flex items-start space-x-6 mb-6">
      <!-- Average Rating -->
      <div class="text-center">
        <div class="text-3xl font-bold text-gray-900">{{ averageRating }}</div>
        <div class="flex items-center justify-center space-x-1 mb-1">
          <div v-for="i in 5" :key="i" class="flex">
            <svg
              :class="[
                'w-5 h-5',
                i <= Math.round(averageRating) ? 'text-yellow-400' : 'text-gray-300'
              ]"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
        </div>
        <div class="text-sm text-gray-500">{{ totalRatings }} review{{ totalRatings !== 1 ? 's' : '' }}</div>
      </div>

      <!-- Star Distribution -->
      <div class="flex-1">
        <div v-for="item in starDistribution" :key="item.stars" class="flex items-center space-x-2 mb-2">
          <span class="text-sm text-gray-600 w-2">{{ item.stars }}</span>
          <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
          <div class="flex-1 bg-gray-200 rounded-full h-2">
            <div
              class="bg-yellow-400 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${item.percentage}%` }"
            ></div>
          </div>
          <span class="text-sm text-gray-600 w-8">{{ item.count }}</span>
        </div>
      </div>
    </div>

    <!-- User's Review -->
    <div v-if="hasUserRated && !showReviewForm" class="mb-6 p-4 bg-blue-50 rounded-lg">
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center space-x-2">
          <span class="font-medium text-gray-900">Your Review</span>
          <div class="flex space-x-1">
            <div v-for="i in 5" :key="i" class="flex">
              <svg
                :class="[
                  'w-4 h-4',
                  i <= ratingStore.userRating!.stars ? 'text-yellow-400' : 'text-gray-300'
                ]"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
          </div>
        </div>
        <div class="flex space-x-2">
          <button
            @click="editReview"
            class="text-sm text-blue-600 hover:text-blue-800 transition-colors"
          >
            Edit
          </button>
          <button
            @click="deleteReview"
            class="text-sm text-red-600 hover:text-red-800 transition-colors"
          >
            Delete
          </button>
        </div>
      </div>
      <p v-if="ratingStore.userRating!.comment" class="text-gray-700">{{ ratingStore.userRating!.comment }}</p>
      <p class="text-xs text-gray-500 mt-2">
        Reviewed on {{ new Date(ratingStore.userRating!.created_at).toLocaleDateString() }}
      </p>
    </div>

    <!-- Review Form -->
    <div v-if="showReviewForm" class="mb-6 p-4 bg-gray-50 rounded-lg">
      <h4 class="font-medium text-gray-900 mb-4">
        {{ hasUserRated ? 'Edit Your Review' : 'Write a Review' }}
      </h4>
      
      <!-- Star Rating -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
        <div class="flex space-x-1">
          <button
            v-for="i in 5"
            :key="i"
            @click="handleStarClick(i)"
            @mouseenter="handleStarHover(i)"
            @mouseleave="handleStarLeave"
            :class="[
              'w-8 h-8 transition-colors',
              (i <= stars || i <= hoveredStar) ? 'text-yellow-400' : 'text-gray-300'
            ]"
          >
            <svg fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Comment -->
      <div class="mb-4">
        <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
          Review (Optional)
        </label>
        <textarea
          id="comment"
          v-model="comment"
          rows="3"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Share your thoughts about this product..."
        ></textarea>
      </div>

      <!-- Actions -->
      <div class="flex space-x-3">
        <button
          @click="submitReview"
          :disabled="isSubmitting || stars === 0"
          class="px-4 py-2 bg-zinc-900 text-white text-sm font-medium rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <span v-if="isSubmitting">Submitting...</span>
          <span v-else>{{ hasUserRated ? 'Update Review' : 'Submit Review' }}</span>
        </button>
        <button
          @click="cancelReview"
          :disabled="isSubmitting"
          class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          Cancel
        </button>
      </div>
    </div>

    <!-- Reviews List -->
    <div v-if="ratingStore.ratings.length > 0" class="space-y-4">
      <h4 class="font-medium text-gray-900">Customer Reviews</h4>
      <div
        v-for="rating in ratingStore.ratings"
        :key="rating.id"
        class="border-b border-gray-200 pb-4 last:border-b-0"
      >
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center space-x-2">
            <span class="font-medium text-gray-900">{{ rating.user?.user_name || 'Anonymous' }}</span>
            <div class="flex space-x-1">
              <div v-for="i in 5" :key="i" class="flex">
                <svg
                  :class="[
                    'w-4 h-4',
                    i <= rating.stars ? 'text-yellow-400' : 'text-gray-300'
                  ]"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
            </div>
          </div>
          <span class="text-sm text-gray-500">
            {{ new Date(rating.created_at).toLocaleDateString() }}
          </span>
        </div>
        <p v-if="rating.comment" class="text-gray-700">{{ rating.comment }}</p>
      </div>
    </div>

    <!-- No Reviews -->
    <div v-else-if="totalRatings === 0" class="text-center py-8">
      <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
      </div>
      <h4 class="text-lg font-medium text-gray-900 mb-2">No Reviews Yet</h4>
      <p class="text-gray-500">Be the first to review this product!</p>
    </div>
  </div>
</template>
