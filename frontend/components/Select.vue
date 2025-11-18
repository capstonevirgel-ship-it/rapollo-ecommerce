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
const positionTrigger = ref(0) // Reactive trigger to force dropdownStyle recalculation

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
  // Access positionTrigger to ensure reactivity
  positionTrigger.value
  
  if (!selectRef.value || !isOpen.value) return {}
  
  // Get the main select button element (the first button, which is the main select)
  const buttonElement = selectRef.value.querySelector('button:first-of-type') as HTMLElement
  if (!buttonElement) return {}
  
  // Use the button's bounding rect for accurate positioning
  const rect = buttonElement.getBoundingClientRect()
  const viewportHeight = window.innerHeight
  const viewportWidth = window.innerWidth
  
  // Estimate dropdown height based on number of options
  // Each option is approximately 40px, plus padding
  const estimatedOptionHeight = 40
  const searchHeight = props.options.length > 10 ? 50 : 0
  const estimatedDropdownHeight = Math.min(
    (props.options.length * estimatedOptionHeight) + searchHeight + 16,
    200
  )
  
  // Calculate available space
  const spaceBelow = viewportHeight - rect.bottom
  const spaceAbove = rect.top
  
  // Use fixed positioning to escape overflow constraints
  // Match the exact width of the button
  let top = rect.bottom + 4 // Default: below the select
  let left = rect.left
  let width = rect.width
  let maxHeight = '200px'
  let position = 'fixed' // Use fixed to escape table overflow
  
  // Always default to opening below unless there's really not enough space
  // Only open above if there's less than 100px below AND significantly more space above
  const minSpaceBelow = 100
  if (spaceBelow < minSpaceBelow && spaceAbove > spaceBelow + 100) {
    // Open above - calculate position from top of button
    top = rect.top - estimatedDropdownHeight - 4
    // Ensure it doesn't go above viewport
    if (top < 8) {
      top = 8
      maxHeight = `${rect.top - 12}px`
    }
  } else {
    // Open below (default) - always prefer this
    top = rect.bottom + 4
    // If not enough space below, limit max height but still open below
    if (spaceBelow < estimatedDropdownHeight) {
      maxHeight = `${Math.max(spaceBelow - 8, 100)}px` // At least 100px height
    }
  }
  
  // Ensure dropdown doesn't go off screen horizontally
  if (left + width > viewportWidth) {
    left = viewportWidth - width - 8
  }
  if (left < 8) {
    left = 8
  }
  
  return {
    position,
    top: `${top}px`,
    left: `${left}px`,
    width: `${width}px`,
    minWidth: `${width}px`, // Ensure minimum width matches
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

// Click outside to close (works with Teleport)
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as Node
  if (
    selectRef.value && 
    !selectRef.value.contains(target) &&
    dropdownRef.value &&
    !dropdownRef.value.contains(target)
  ) {
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

// Update position on scroll when dropdown is open (for fixed positioning)
const updatePosition = () => {
  if (isOpen.value) {
    // Trigger reactivity by updating positionTrigger
    positionTrigger.value = Date.now()
  }
}

// Watch for dropdown open/close to recalculate position and handle scroll/resize
watch(isOpen, (newValue) => {
  if (newValue) {
    // Force recalculation after DOM updates
    // Use multiple nextTick calls to ensure Teleport has completed
    nextTick(() => {
      nextTick(() => {
        // Small delay to ensure button position is stable and dropdown is rendered
        setTimeout(() => {
          updatePosition()
          // One more update after a brief moment to catch any layout shifts
          setTimeout(() => {
            updatePosition()
          }, 50)
        }, 10)
      })
    })
    // Add scroll and resize listeners for fixed positioning
    window.addEventListener('scroll', updatePosition, true)
    window.addEventListener('resize', updatePosition)
    // Also listen to scroll on parent containers (like DataTable)
    if (selectRef.value) {
      let parent = selectRef.value.parentElement
      while (parent && parent !== document.body) {
        parent.addEventListener('scroll', updatePosition, true)
        parent = parent.parentElement
      }
    }
  } else {
    // Remove listeners when closed
    window.removeEventListener('scroll', updatePosition, true)
    window.removeEventListener('resize', updatePosition)
    // Remove parent scroll listeners
    if (selectRef.value) {
      let parent = selectRef.value.parentElement
      while (parent && parent !== document.body) {
        parent.removeEventListener('scroll', updatePosition, true)
        parent = parent.parentElement
      }
    }
  }
})

// Also watch dropdownRef to recalculate when it's actually rendered
watch(() => dropdownRef.value, (newValue) => {
  if (newValue && isOpen.value) {
    // Dropdown just rendered, recalculate position
    nextTick(() => {
      updatePosition()
    })
  }
})

onUnmounted(() => {
  window.removeEventListener('scroll', updatePosition, true)
  window.removeEventListener('resize', updatePosition)
  // Clean up parent scroll listeners
  if (selectRef.value) {
    let parent = selectRef.value.parentElement
    while (parent && parent !== document.body) {
      parent.removeEventListener('scroll', updatePosition, true)
      parent = parent.parentElement
    }
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
    <Teleport to="body">
      <div
        v-if="isOpen"
        ref="dropdownRef"
        class="fixed z-[9999] bg-white shadow-lg rounded-md py-1 text-base border border-gray-200 focus:outline-none overflow-hidden"
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
              'relative cursor-pointer select-none py-2 pl-3 pr-9 leading-snug',
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
              <span class="block font-normal whitespace-normal break-words pr-2">
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
      </Teleport>
    </Transition>
  </div>
</template>
