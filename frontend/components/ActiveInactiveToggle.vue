<script setup lang="ts">
interface Props {
  modelValue: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const toggle = () => {
  if (!props.disabled) {
    emit('update:modelValue', !props.modelValue)
  }
}
</script>

<template>
  <button
    type="button"
    role="switch"
    :aria-checked="modelValue"
    :disabled="disabled"
    @click="toggle"
    :class="[
      'relative inline-flex items-center transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2',
      'w-20 h-6 rounded-md max-[489px]:w-11 max-[489px]:h-5 max-[489px]:rounded',
      modelValue 
        ? 'bg-green-600 focus:ring-green-500' 
        : 'bg-gray-300 focus:ring-gray-400',
      disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:opacity-90'
    ]"
  >
    <!-- Sliding Box with Text -->
    <span
      :class="[
        'absolute bg-white rounded border border-gray-200 flex items-center justify-center text-[10px] font-medium uppercase transform transition-transform duration-300 shadow-sm',
        'left-0.4 h-4.25',
        'max-[489px]:left-0.5 max-[489px]:h-4 max-[489px]:w-4 max-[489px]:rounded',
        modelValue 
          ? 'translate-x-[3px] text-green-700 w-12 max-[489px]:translate-x-0.5' 
          : 'translate-x-5 text-gray-600 w-14 max-[489px]:translate-x-4'
      ]"
    >
      <span class="max-[489px]:hidden">{{ modelValue ? 'ACTIVE' : 'INACTIVE' }}</span>
    </span>
  </button>
</template>

