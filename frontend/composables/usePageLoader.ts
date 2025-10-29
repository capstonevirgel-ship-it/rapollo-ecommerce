import { ref } from 'vue'

// Global state for page loader - only for initial page load
const isInitialLoading = ref(true)
const progress = ref(0)
const hasCompletedInitialLoad = ref(false)

export const usePageLoader = () => {
  const startInitialLoading = () => {
    isInitialLoading.value = true
    progress.value = 0
    hasCompletedInitialLoad.value = false
  }

  const updateProgress = (newProgress: number) => {
    progress.value = Math.min(Math.max(newProgress, 0), 100)
  }

  const completeInitialLoading = () => {
    progress.value = 100
    hasCompletedInitialLoad.value = true
    
    // Hide loader after a short delay to show completion
    setTimeout(() => {
      isInitialLoading.value = false
    }, 500)
  }

  const forceComplete = () => {
    progress.value = 100
    hasCompletedInitialLoad.value = true
    isInitialLoading.value = false
  }

  return {
    isInitialLoading,
    progress,
    hasCompletedInitialLoad,
    startInitialLoading,
    updateProgress,
    completeInitialLoading,
    forceComplete
  }
}

