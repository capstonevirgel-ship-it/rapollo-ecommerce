<script setup lang="ts">
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
</script>

<template>
  <div
    v-if="modelValue"
    class="dialog-overlay flex items-center justify-center bg-black/50"
  >
    <div
      class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative"
      :style="{ width }"
    >

      <button
        class="absolute top-3 right-3 text-gray-400 hover:text-gray-600"
        @click="close"
      >
        <Icon name="mdi:close" />
      </button>

      <h2 v-if="title" class="text-lg font-semibold mb-4">{{ title }}</h2>

      <div>
        <slot></slot>
      </div>
    </div>
  </div>
</template>

