<script setup lang="ts">
interface Props {
  modelValue: boolean
  disabled?: boolean
  label?: string
  size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
  size: 'md'
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const toggle = () => {
  if (!props.disabled) {
    emit('update:modelValue', !props.modelValue)
  }
}

const sizeClasses = {
  sm: 'w-9 h-5',
  md: 'w-11 h-6',
  lg: 'w-14 h-7'
}

const dotSizeClasses = {
  sm: 'w-4 h-4',
  md: 'w-5 h-5',
  lg: 'w-6 h-6'
}

const translateClasses = {
  sm: 'translate-x-4',
  md: 'translate-x-5',
  lg: 'translate-x-7'
}
</script>

<template>
  <div class="flex items-center gap-3">
    <label
      v-if="label"
      class="text-sm font-medium text-gray-700"
      :class="{ 'cursor-pointer': !disabled, 'cursor-not-allowed opacity-50': disabled }"
    >
      {{ label }}
    </label>
    <button
      type="button"
      role="switch"
      :aria-checked="modelValue"
      :disabled="disabled"
      @click="toggle"
      :class="[
        sizeClasses[size],
        'relative inline-flex items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2',
        modelValue ? 'bg-zinc-900' : 'bg-gray-300',
        disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
      ]"
    >
      <span
        :class="[
          dotSizeClasses[size],
          'inline-block transform rounded-full bg-white transition-transform shadow-sm',
          modelValue ? translateClasses[size] : 'translate-x-0.5'
        ]"
      />
    </button>
  </div>
</template>

