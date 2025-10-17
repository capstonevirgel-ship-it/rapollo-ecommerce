<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

interface Option {
  value: string | number
  label: string
  disabled?: boolean
}

const props = withDefaults(defineProps<{
  options: Option[]
  modelValue: (string | number)[]
  placeholder?: string
  disabled?: boolean
  maxHeight?: string
}>(), {
  placeholder: 'Select options...',
  disabled: false,
  maxHeight: '200px'
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: (string | number)[]): void
}>()

const isOpen = ref(false)
const searchQuery = ref('')
const selectRef = ref<HTMLDivElement>()

// Computed properties
const selectedOptions = computed(() => {
  return props.options.filter(option => props.modelValue.includes(option.value))
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options
  
  return props.options.filter(option =>
    option.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const displayText = computed(() => {
  if (selectedOptions.value.length === 0) {
    return props.placeholder
  }
  
  if (selectedOptions.value.length === 1) {
    return selectedOptions.value[0].label
  }
  
  return `${selectedOptions.value.length} selected`
});


// Methods
const toggleDropdown = () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    searchQuery.value = ''
  }
}

const toggleOption = (option: Option) => {
  if (option.disabled) return
  
  const newValue = [...props.modelValue]
  const index = newValue.indexOf(option.value)
  
  if (index > -1) {
    newValue.splice(index, 1)
  } else {
    newValue.push(option.value)
  }
  
  emit('update:modelValue', newValue)
}

const removeOption = (option: Option) => {
  const newValue = props.modelValue.filter(value => value !== option.value)
  emit('update:modelValue', newValue)
}

const clearAll = () => {
  emit('update:modelValue', [])
}

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  if (selectRef.value && !selectRef.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div ref="selectRef" class="relative">
    <!-- Select Button -->
    <button
      type="button"
      :class="[
        'w-full px-3 py-2 text-left border border-gray-300 rounded-lg',
        'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
        'flex items-center justify-between',
        disabled ? 'bg-gray-50 cursor-not-allowed' : 'bg-white hover:border-gray-400'
      ]"
      @click="toggleDropdown"
      :disabled="disabled"
    >
      <span class="block truncate text-gray-900">
        {{ displayText }}
      </span>
      
      <div class="flex items-center space-x-2">
        <!-- Clear button -->
        <button
          v-if="modelValue.length > 0 && !disabled"
          type="button"
          @click.stop="clearAll"
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

    <!-- Selected Options Display -->
    <div v-if="selectedOptions.length > 0" class="mt-2 flex flex-wrap gap-1">
      <span
        v-for="option in selectedOptions"
        :key="option.value"
        class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-md"
      >
        {{ option.label }}
        <button
          v-if="!disabled"
          @click="removeOption(option)"
          class="text-blue-600 hover:text-blue-800 focus:outline-none"
        >
          <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </span>
    </div>

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
        class="absolute z-[9999] mt-1 w-full bg-white shadow-lg rounded-lg border border-gray-200 focus:outline-none"
      >
        <!-- Search input -->
        <div v-if="options.length > 5" class="p-3 border-b border-gray-100">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search options..."
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @click.stop
          />
        </div>

        <!-- Options -->
        <div class="py-1 max-h-48 overflow-y-auto ultra-thin-scrollbar">
          <div
            v-for="option in filteredOptions"
            :key="option.value"
            :class="[
              'relative cursor-pointer select-none py-2 pl-3 pr-9',
              {
                'text-blue-900 bg-blue-50': modelValue.includes(option.value),
                'text-gray-900 hover:bg-gray-50': !modelValue.includes(option.value) && !option.disabled,
                'text-gray-400 cursor-not-allowed': option.disabled
              }
            ]"
            @click="toggleOption(option)"
          >
            <span class="block truncate font-normal">
              {{ option.label }}
            </span>
            
            <!-- Selected indicator -->
            <span
              v-if="modelValue.includes(option.value)"
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
