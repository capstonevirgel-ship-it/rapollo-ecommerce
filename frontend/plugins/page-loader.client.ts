import { usePageLoader } from '~/composables/usePageLoader'

export default defineNuxtPlugin(() => {
  const { startInitialLoading, updateProgress, completeInitialLoading } = usePageLoader()
  
  // Start loading immediately when the app starts
  startInitialLoading()
  
  // Simulate progress updates based on page load events
  let progress = 0
  
  // Update progress based on DOM events
  const updateProgressBasedOnEvents = () => {
    // DOM content loaded
    if (document.readyState === 'interactive') {
      progress = 30
      updateProgress(progress)
    }
    
    // DOM fully loaded
    if (document.readyState === 'complete') {
      progress = 60
      updateProgress(progress)
    }
  }
  
  // Listen for DOM events
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      progress = 30
      updateProgress(progress)
    })
  } else {
    progress = 30
    updateProgress(progress)
  }
  
  // Listen for window load event
  window.addEventListener('load', () => {
    progress = 80
    updateProgress(progress)
    
    // Complete loading after a short delay
    setTimeout(() => {
      completeInitialLoading()
    }, 500)
  })
  
  // Fallback: complete loading after 3 seconds to prevent stuck states
  setTimeout(() => {
    if (progress < 100) {
      completeInitialLoading()
    }
  }, 3000)
})
