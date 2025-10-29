<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useSettingsStore } from '~/stores/settings'

interface Props {
  progress: number // 0-100
  isLoading: boolean
}

const props = defineProps<Props>()
const settingsStore = useSettingsStore()

// Smooth progress animation
const animatedProgress = ref(0)
const isVisible = ref(true)
const isMounted = ref(false)

onMounted(() => {
  isMounted.value = true
})

// Watch progress changes and animate smoothly
watch(() => props.progress, (newProgress) => {
  // Only animate on client side
  if (!isMounted.value || typeof window === 'undefined') {
    animatedProgress.value = newProgress
    return
  }

  const duration = 300 // ms
  const steps = 20
  const increment = (newProgress - animatedProgress.value) / steps
  let currentStep = 0

  const animate = () => {
    if (currentStep < steps) {
      animatedProgress.value += increment
      currentStep++
      requestAnimationFrame(animate)
    } else {
      animatedProgress.value = newProgress
    }
  }

  requestAnimationFrame(animate)
}, { immediate: true })

// Hide loader with fade out animation when loading completes
watch(() => props.isLoading, (loading) => {
  if (!loading) {
    setTimeout(() => {
      isVisible.value = false
    }, 500) // Wait for fade out animation
  } else {
    // Reset visibility when loading starts again
    isVisible.value = true
  }
})

const progressPercentage = computed(() => {
  return Math.min(Math.max(animatedProgress.value, 0), 100)
})
</script>

<template>
  <Transition name="fade">
    <div 
      v-if="isLoading && isVisible" 
      class="fixed inset-0 z-[9999] bg-white flex flex-col items-center justify-center"
    >
      <!-- Logo or Brand Name -->
      <div class="mb-8">
        <h1 class="text-4xl font-winner-extra-bold text-gray-900">
          {{ settingsStore.siteName || 'RAPOLLO' }}
        </h1>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
