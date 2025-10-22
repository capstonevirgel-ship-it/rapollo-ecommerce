<script setup lang="ts">
interface ValidationRule {
  required?: boolean
  minLength?: number
  maxLength?: number
  pattern?: RegExp
  customMessage?: string
  email?: boolean
  password?: boolean
  confirmPassword?: string
  fieldName?: string
}

interface Props {
  value: string
  rules: ValidationRule
  label?: string
  fieldName?: string
}

const props = withDefaults(defineProps<Props>(), {
  label: '',
  fieldName: ''
})

const errors = ref<string[]>([])

const validate = (): boolean => {
  errors.value = []
  
  // Required validation
  if (props.rules.required && (!props.value || props.value.trim() === '')) {
    errors.value.push(`${props.label || props.fieldName || 'Field'} is required`)
    return false
  }
  
  // Skip other validations if field is empty and not required
  if (!props.value || props.value.trim() === '') {
    return true
  }
  
  // Min length validation
  if (props.rules.minLength && props.value.length < props.rules.minLength) {
    const message = props.rules.customMessage || 
      `${props.label || props.fieldName || 'Field'} must be at least ${props.rules.minLength} characters`
    errors.value.push(message)
  }
  
  // Max length validation
  if (props.rules.maxLength && props.value.length > props.rules.maxLength) {
    const message = props.rules.customMessage || 
      `${props.label || props.fieldName || 'Field'} must not exceed ${props.rules.maxLength} characters`
    errors.value.push(message)
  }
  
  // Email validation
  if (props.rules.email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailPattern.test(props.value)) {
      errors.value.push(`${props.label || props.fieldName || 'Field'} must be a valid email address`)
    }
  }
  
  // Password validation
  if (props.rules.password) {
    if (props.value.length < 8) {
      errors.value.push('Password must be at least 8 characters')
    }
    if (!/(?=.*[a-z])/.test(props.value)) {
      errors.value.push('Password must contain at least one lowercase letter')
    }
    if (!/(?=.*[A-Z])/.test(props.value)) {
      errors.value.push('Password must contain at least one uppercase letter')
    }
    if (!/(?=.*\d)/.test(props.value)) {
      errors.value.push('Password must contain at least one number')
    }
  }
  
  // Pattern validation
  if (props.rules.pattern && !props.rules.pattern.test(props.value)) {
    const message = props.rules.customMessage || 
      `${props.label || props.fieldName || 'Field'} format is invalid`
    errors.value.push(message)
  }
  
  // Confirm password validation
  if (props.rules.confirmPassword && props.value !== props.rules.confirmPassword) {
    errors.value.push('Passwords do not match')
  }
  
  return errors.value.length === 0
}

const isValid = computed(() => {
  return validate()
})

const hasErrors = computed(() => {
  return errors.value.length > 0
})

const firstError = computed(() => {
  return errors.value[0] || ''
})

// Expose validation methods
defineExpose({
  validate,
  isValid,
  hasErrors,
  firstError,
  errors: readonly(errors)
})
</script>

<template>
  <div>
    <slot :errors="errors" :isValid="isValid" :hasErrors="hasErrors" :firstError="firstError" />
  </div>
</template>
