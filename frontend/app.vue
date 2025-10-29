<script setup lang="ts">
import PageLoader from '@/components/PageLoader.vue'
import { usePageLoader } from '@/composables/usePageLoader'
import { computed, onMounted } from 'vue'

const { isInitialLoading, progress, hasCompletedInitialLoad } = usePageLoader()

// Show loader only on initial page load
const shouldShowLoader = computed(() => {
  return isInitialLoading.value && !hasCompletedInitialLoad.value
})

// Show content when not loading or when initial load is complete
const shouldShowContent = computed(() => {
  return !isInitialLoading.value || hasCompletedInitialLoad.value
})

// Auto-complete loading after a reasonable time to prevent stuck states
onMounted(() => {
  // If loader is still showing after 10 seconds, force complete it
  setTimeout(() => {
    if (isInitialLoading.value && !hasCompletedInitialLoad.value) {
      const { forceComplete } = usePageLoader()
      forceComplete()
    }
  }, 10000)
})
</script>

<template>
  <div>
    <!-- Global Page Loader (hidden on excluded pages) -->
    <ClientOnly>
      <PageLoader :progress="progress" :is-loading="shouldShowLoader" />
    </ClientOnly>
    
    <!-- Content - Hidden until loading completes (unless on excluded pages) -->
    <div v-show="shouldShowContent">
      <NuxtLayout>
        <NuxtPage />
      </NuxtLayout>
    </div>
  </div>
</template>