<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

interface SelectOption {
  value: string | number
  label: string
  disabled?: boolean
}

const props = withDefaults(defineProps<{
  modelValue: string | number | null
  options: SelectOption[]
  placeholder?: string
  disabled?: boolean
  error?: boolean
  size?: 'sm' | 'md' | 'lg'
  variant?: 'default' | 'outline'
}>(), {
  placeholder: 'Select an option',
  disabled: false,
  error: false,
  size: 'md',
  variant: 'default'
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
  (e: 'change', value: string | number | null): void
}>()

const isOpen = ref(false)
const selectRef = ref<HTMLDivElement>()
const dropdownRef = ref<HTMLDivElement>()
const searchQuery = ref('')

// Computed properties
const selectedOption = computed(() => {
  return props.options.find(option => option.value === props.modelValue)
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options
  return props.options.filter(option => 
    option.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'px-3 py-1.5 text-sm'
    case 'lg':
      return 'px-4 py-3 text-base'
    default:
      return 'px-4 py-2 text-sm'
  }
})

const variantClasses = computed(() => {
  const baseClasses = 'w-full border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200'
  
  if (props.error) {
    return `${baseClasses} border-red-300 focus:ring-red-500 focus:border-red-500`
  }
  
  if (props.variant === 'outline') {
    return `${baseClasses} border-gray-300 bg-transparent hover:border-gray-400`
  }
  
  return `${baseClasses} border-gray-300 bg-white hover:border-gray-400`
})

const disabledClasses = computed(() => {
  return props.disabled ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'cursor-pointer'
})

const dropdownStyle = computed(() => {
  if (!selectRef.value || !isOpen.value) return {}
  
  const rect = selectRef.value.getBoundingClientRect()
  const viewportHeight = window.innerHeight
  const dropdownHeight = 200 // Estimated max height
  
  // Calculate if dropdown should open above or below
  const spaceBelow = viewportHeight - rect.bottom
  const spaceAbove = rect.top
  
  let top = rect.bottom + 4 // Default: below the select
  let maxHeight = '200px'
  
  // If not enough space below, open above
  if (spaceBelow < dropdownHeight && spaceAbove > spaceBelow) {
    top = rect.top - dropdownHeight - 4
  }
  
  // If not enough space above either, use available space
  if (spaceAbove < dropdownHeight && spaceBelow < dropdownHeight) {
    if (spaceBelow > spaceAbove) {
      top = rect.bottom + 4
      maxHeight = `${spaceBelow - 8}px`
    } else {
      top = 4
      maxHeight = `${spaceAbove - 8}px`
    }
  }
  
  return {
    top: `${top}px`,
    left: `${rect.left}px`,
    width: `${rect.width}px`,
    maxHeight
  }
})


// Methods
const toggleDropdown = () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    searchQuery.value = ''
  }
}

const selectOption = (option: SelectOption) => {
  if (option.disabled) return
  
  emit('update:modelValue', option.value)
  emit('change', option.value)
  isOpen.value = false
  searchQuery.value = ''
}

const clearSelection = () => {
  if (props.disabled) return
  emit('update:modelValue', null)
  emit('change', null)
}

const handleKeydown = (event: KeyboardEvent) => {
  if (!isOpen.value) {
    if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
      event.preventDefault()
      toggleDropdown()
    }
    return
  }

  switch (event.key) {
    case 'Escape':
      isOpen.value = false
      searchQuery.value = ''
      break
    case 'ArrowDown':
      event.preventDefault()
      // Focus next option
      break
    case 'ArrowUp':
      event.preventDefault()
      // Focus previous option
      break
    case 'Enter':
      event.preventDefault()
      // Select focused option
      break
  }
}

// Click outside to close
const handleClickOutside = (event: MouseEvent) => {
  if (selectRef.value && !selectRef.value.contains(event.target as Node)) {
    isOpen.value = false
    searchQuery.value = ''
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Watch for external changes
watch(() => props.modelValue, () => {
  // Reset search when value changes externally
  searchQuery.value = ''
})

// Watch for dropdown open/close to recalculate position
watch(isOpen, (newValue) => {
  if (newValue) {
    // Force recalculation on next tick
    nextTick(() => {
      // Trigger reactivity for dropdownStyle
      if (selectRef.value) {
        selectRef.value.getBoundingClientRect()
      }
    })
  }
})
</script>

<template>
  <div ref="selectRef" class="relative">
    <!-- Select Button -->
    <button
      type="button"
      :class="[
        sizeClasses,
        variantClasses,
        disabledClasses,
        'flex items-center justify-between text-left'
      ]"
      @click="toggleDropdown"
      @keydown="handleKeydown"
      :disabled="disabled"
      :aria-expanded="isOpen"
      :aria-haspopup="true"
    >
      <span class="block truncate">
        <slot name="selected" :option="selectedOption" :placeholder="placeholder">
          {{ selectedOption?.label || placeholder }}
        </slot>
      </span>
      
      <div class="flex items-center space-x-2">
        <!-- Clear button -->
        <button
          v-if="modelValue && !disabled"
          type="button"
          @click.stop="clearSelection"
          class="text-gray-400 hover:text-gray-600 focus:outline-none"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        
        <!-- Dropdown arrow -->
        <svg
          class="h-4 w-4 text-gray-400 transition-transform duration-200"
          :class="{ 'rotate-180': isOpen }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
    <div
      v-if="isOpen"
      ref="dropdownRef"
      class="fixed z-[9999] mt-1 w-full bg-white shadow-lg rounded-md py-1 text-base border border-gray-200 focus:outline-none"
      :style="dropdownStyle"
    >
        <!-- Search input (if many options) -->
        <div v-if="options.length > 10" class="px-3 py-2 border-b border-gray-100">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search options..."
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @click.stop
          />
        </div>

        <!-- Options -->
        <div class="py-1 overflow-y-auto ultra-thin-scrollbar" :style="{ maxHeight: dropdownStyle.maxHeight || '200px' }">
          <div
            v-for="option in filteredOptions"
            :key="option.value"
            :class="[
              'relative cursor-pointer select-none py-2 pl-3 pr-9',
              {
                'text-blue-900 bg-blue-50': option.value === modelValue,
                'text-gray-900 hover:bg-gray-50': option.value !== modelValue && !option.disabled,
                'text-gray-400 cursor-not-allowed': option.disabled
              }
            ]"
            @click="selectOption(option)"
          >
            <!-- Custom option slot -->
            <slot name="option" :option="option" :selected="option.value === modelValue">
              <span class="block truncate font-normal">
                {{ option.label }}
              </span>
            </slot>
            
            <!-- Selected indicator -->
            <span
              v-if="option.value === modelValue"
              class="absolute inset-y-0 right-0 flex items-center pr-4 text-blue-600"
            >
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
        </div>

        <!-- No options found -->
        <div v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">
          No options found
        </div>
      </div>
    </Transition>
  </div>
</template>
