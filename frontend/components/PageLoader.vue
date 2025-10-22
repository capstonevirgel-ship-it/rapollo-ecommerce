<script setup lang="ts">
interface Props {
  text?: string
  showText?: boolean
  size?: 'sm' | 'md' | 'lg' | 'xl'
  color?: 'primary' | 'secondary' | 'white' | 'gray'
}

const props = withDefaults(defineProps<Props>(), {
  text: 'Loading...',
  showText: true,
  size: 'lg',
  color: 'primary'
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <!-- Loading Spinner -->
      <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <LoadingSpinner 
          :size="size" 
          :color="color"
        />
      </div>
      
      <!-- Loading Text -->
      <h2 
        v-if="showText" 
        class="text-2xl font-bold text-gray-900 mb-2"
      >
        {{ text }}
      </h2>
      
      <!-- Subtitle -->
      <p class="text-gray-600">
        Please wait while we load your content.
      </p>
    </div>
  </div>
</template>
