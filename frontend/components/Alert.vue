<template>
  <Teleport to="body">
    <div class="fixed bottom-4 right-4 z-50 space-y-2">
      <TransitionGroup
        name="alert"
        tag="div"
        class="space-y-2"
      >
        <div
          v-for="alert in alerts"
          :key="alert.id"
          :class="[
            'max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden border-l-4',
            alertClasses[alert.type]
          ]"
        >
          <div class="p-4">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <!-- Success Icon -->
                <svg v-if="alert.type === 'success'" class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                
                <!-- Error Icon -->
                <svg v-else-if="alert.type === 'error'" class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                
                <!-- Info Icon -->
                <svg v-else-if="alert.type === 'info'" class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                
                <!-- Warning Icon -->
                <svg v-else-if="alert.type === 'warning'" class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              </div>
              
              <div class="ml-3 flex-1 min-w-0">
                <p :class="[
                  'text-sm font-medium',
                  textClasses[alert.type]
                ]">
                  {{ alert.title }}
                </p>
                <p v-if="alert.message" class="mt-1 text-sm text-gray-500">
                  {{ alert.message }}
                </p>
              </div>
              
              <div class="ml-2 flex-shrink-0">
                <button
                  @click="removeAlert(alert.id)"
                  class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-md p-1"
                >
                  <span class="sr-only">Close</span>
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
          
          <!-- Progress Bar -->
          <div v-if="alert.autoClose" class="h-1 bg-gray-200">
            <div
              :class="[
                'h-1 transition-all duration-300 ease-linear',
                progressClasses[alert.type]
              ]"
              :style="{ width: `${alert.progress}%` }"
            ></div>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

interface Alert {
  id: string
  type: 'success' | 'error' | 'info' | 'warning'
  title: string
  message?: string
  duration?: number
  autoClose?: boolean
  progress: number
}

const alerts = ref<Alert[]>([])
const alertClasses = {
  success: 'border-green-400',
  error: 'border-red-400',
  info: 'border-blue-400',
  warning: 'border-yellow-400'
}

const textClasses = {
  success: 'text-green-800',
  error: 'text-red-800',
  info: 'text-blue-800',
  warning: 'text-yellow-800'
}

// Removed buttonClasses - using simplified styling

const progressClasses = {
  success: 'bg-green-400',
  error: 'bg-red-400',
  info: 'bg-blue-400',
  warning: 'bg-yellow-400'
}

// Global alert function
const showAlert = (alert: Omit<Alert, 'id' | 'progress'>) => {
  const id = Math.random().toString(36).substr(2, 9)
  const duration = alert.duration || getDefaultDuration(alert.type)
  const autoClose = alert.autoClose !== false
  
  const newAlert: Alert = {
    ...alert,
    id,
    duration,
    autoClose,
    progress: 100
  }
  
  alerts.value.push(newAlert)
  
  // Auto remove after duration
  if (autoClose) {
    let progressInterval: NodeJS.Timeout
    let removeTimeout: NodeJS.Timeout
    
    // Update progress bar
    const updateProgress = () => {
      newAlert.progress -= 100 / (duration / 100)
      if (newAlert.progress <= 0) {
        clearInterval(progressInterval)
        clearTimeout(removeTimeout)
        removeAlert(id)
      }
    }
    
    progressInterval = setInterval(updateProgress, 100)
    removeTimeout = setTimeout(() => removeAlert(id), duration)
  }
  
  return id
}

// Remove alert
const removeAlert = (id: string) => {
  const index = alerts.value.findIndex(alert => alert.id === id)
  if (index > -1) {
    alerts.value.splice(index, 1)
  }
}

// Get default duration based on type
const getDefaultDuration = (type: string): number => {
  switch (type) {
    case 'success':
      return 4000 // 4 seconds
    case 'error':
      return 6000 // 6 seconds (errors need more time to read)
    case 'warning':
      return 5000 // 5 seconds
    case 'info':
      return 4000 // 4 seconds
    default:
      return 4000
  }
}

// Convenience methods
const success = (title: string, message?: string, duration?: number) => 
  showAlert({ type: 'success', title, message, duration })

const error = (title: string, message?: string, duration?: number) => 
  showAlert({ type: 'error', title, message, duration })

const info = (title: string, message?: string, duration?: number) => 
  showAlert({ type: 'info', title, message, duration })

const warning = (title: string, message?: string, duration?: number) => 
  showAlert({ type: 'warning', title, message, duration })

// Expose methods globally
const alertService = {
  show: showAlert,
  success,
  error,
  info,
  warning,
  remove: removeAlert
}

// Make it available globally
if (typeof window !== 'undefined') {
  window.$alert = alertService
}

// Provide for child components
provide('alert', alertService)
</script>

<style scoped>
.alert-enter-active,
.alert-leave-active {
  transition: all 0.3s ease;
}

.alert-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.alert-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.alert-move {
  transition: transform 0.3s ease;
}
</style>
