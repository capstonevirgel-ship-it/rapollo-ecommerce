<script setup lang="ts">
import { watch, onUnmounted } from 'vue'

const props = defineProps<{
  modelValue: boolean
  title?: string
  width?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

const close = () => {
  emit('update:modelValue', false)
}

// Handle body scroll when dialog is open
watch(() => props.modelValue, (isOpen) => {
  if (process.client) {
    if (isOpen) {
      document.body.classList.add('dialog-open')
    } else {
      document.body.classList.remove('dialog-open')
    }
  }
})

// Cleanup on unmount
onUnmounted(() => {
  if (process.client) {
    document.body.classList.remove('dialog-open')
  }
})
</script>

<template>
  <div
    v-if="modelValue"
    class="dialog-overlay flex items-center justify-center bg-black/50 p-4"
  >
    <div
      class="bg-white rounded-xl shadow-2xl w-full max-w-lg relative max-h-[90vh] overflow-y-auto"
      :style="{ width }"
    >
      <!-- Header -->
      <div v-if="title" class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-bold text-gray-900">{{ title }}</h2>
          <button
            class="text-gray-400 hover:text-gray-600 transition-colors"
            @click="close"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="p-6">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

