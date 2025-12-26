<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

interface Props {
  progress: number // 0-100
  isLoading: boolean
}

const props = defineProps<Props>()

// Smooth progress animation
const animatedProgress = ref(0)
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

const progressPercentage = computed(() => {
  return Math.min(Math.max(animatedProgress.value, 0), 100)
})
</script>

<template>
  <Transition name="fade">
    <div 
      v-if="isLoading" 
      class="fixed inset-0 z-[9999] bg-white flex flex-col items-center justify-center"
    >
      <!-- Logo -->
      <div class="mb-8">
        <img 
          src="/uploads/logo/loader.svg" 
          alt="monogram" 
          class="w-[12rem] h-auto"
        />
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active {
  transition: opacity 0.3s ease;
}

.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from {
  opacity: 0;
}

.fade-leave-to {
  opacity: 0;
}
</style>
