<script setup lang="ts">
interface Props {
  size?: 'sm' | 'md' | 'lg' | 'xl'
  color?: 'primary' | 'secondary' | 'white' | 'gray'
  text?: string
  showText?: boolean
  centered?: boolean
  fullScreen?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  color: 'primary',
  text: 'Loading...',
  showText: false,
  centered: true,
  fullScreen: false
})

const sizeClasses = {
  sm: 'h-4 w-4',
  md: 'h-8 w-8',
  lg: 'h-12 w-12',
  xl: 'h-16 w-16'
}

const colorClasses = {
  primary: 'border-zinc-900',
  secondary: 'border-blue-600',
  white: 'border-white',
  gray: 'border-gray-400'
}

const textSizeClasses = {
  sm: 'text-sm',
  md: 'text-base',
  lg: 'text-lg',
  xl: 'text-xl'
}

const containerClasses = computed(() => {
  let classes = 'flex items-center'
  
  if (props.centered) {
    classes += ' justify-center'
  }
  
  if (props.fullScreen) {
    classes += ' min-h-screen'
  } else {
    classes += ' py-12'
  }
  
  return classes
})
</script>

<template>
  <div :class="containerClasses">
    <div class="flex flex-col items-center space-y-4">
      <!-- Spinner -->
      <div 
        :class="[
          'animate-spin rounded-full border-2 border-t-transparent',
          sizeClasses[size],
          colorClasses[color]
        ]"
      ></div>
      
      <!-- Loading Text -->
      <div 
        v-if="showText" 
        :class="[
          'text-gray-600 font-medium',
          textSizeClasses[size]
        ]"
      >
        {{ text }}
      </div>
    </div>
  </div>
</template>
