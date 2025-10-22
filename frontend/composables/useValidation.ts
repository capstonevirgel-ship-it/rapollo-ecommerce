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

export const useValidation = () => {
  const validateField = (value: any, rules: ValidationRule, fieldName?: string): { isValid: boolean; errors: string[] } => {
    const errors: string[] = []
    const displayName = rules.fieldName || fieldName || 'Field'
    
    // Convert value to string for validation, handle different types
    const stringValue = typeof value === 'string' ? value : String(value || '')
    
    // Required validation
    if (rules.required && (!value || (typeof value === 'string' && value.trim() === '') || (typeof value === 'boolean' && !value))) {
      errors.push(`${displayName} is required`)
      return { isValid: false, errors }
    }
    
    // Skip other validations if field is empty and not required
    if (!value || (typeof value === 'string' && value.trim() === '') || (typeof value === 'boolean' && !value)) {
      return { isValid: true, errors: [] }
    }
    
    // Min length validation
    if (rules.minLength && stringValue.length < rules.minLength) {
      const message = rules.customMessage || 
        `${displayName} must be at least ${rules.minLength} characters`
      errors.push(message)
    }
    
    // Max length validation
    if (rules.maxLength && stringValue.length > rules.maxLength) {
      const message = rules.customMessage || 
        `${displayName} must not exceed ${rules.maxLength} characters`
      errors.push(message)
    }
    
    // Email validation
    if (rules.email) {
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailPattern.test(stringValue)) {
        errors.push(`${displayName} must be a valid email address`)
      }
    }
    
    // Password validation
    if (rules.password) {
      if (stringValue.length < 8) {
        errors.push(`${displayName} must be at least 8 characters`)
      }
      if (rules.maxLength && stringValue.length > rules.maxLength) {
        errors.push(`${displayName} must not exceed ${rules.maxLength} characters`)
      }
      if (!/(?=.*[a-z])/.test(stringValue)) {
        errors.push(`${displayName} must contain at least one lowercase letter`)
      }
      if (!/(?=.*[A-Z])/.test(stringValue)) {
        errors.push(`${displayName} must contain at least one uppercase letter`)
      }
      if (!/(?=.*\d)/.test(stringValue)) {
        errors.push(`${displayName} must contain at least one number`)
      }
    }
    
    // Pattern validation
    if (rules.pattern && !rules.pattern.test(stringValue)) {
      const message = rules.customMessage || 
        `${displayName} format is invalid`
      errors.push(message)
    }
    
    // Confirm password validation
    if (rules.confirmPassword && stringValue !== rules.confirmPassword) {
      errors.push('Passwords do not match')
    }
    
    return { isValid: errors.length === 0, errors }
  }

  const validateForm = (formData: Record<string, any>, validationRules: Record<string, ValidationRule>): { isValid: boolean; errors: Record<string, string[]> } => {
    const formErrors: Record<string, string[]> = {}
    let isFormValid = true

    for (const [fieldName, rules] of Object.entries(validationRules)) {
      const fieldValue = formData[fieldName]
      const result = validateField(fieldValue, rules, fieldName)
      
      if (!result.isValid) {
        formErrors[fieldName] = result.errors
        isFormValid = false
      }
    }

    return { isValid: isFormValid, errors: formErrors }
  }

  // Common validation rules
  const rules = {
    required: (customMessage?: string): ValidationRule => ({
      required: true,
      customMessage
    }),
    
    email: (): ValidationRule => ({
      required: true,
      email: true
    }),
    
    username: (minLength: number = 8): ValidationRule => ({
      required: true,
      minLength,
      maxLength: 50
    }),
    
    password: (): ValidationRule => ({
      required: true,
      password: true,
      maxLength: 128
    }),
    
    confirmPassword: (originalPassword: string): ValidationRule => ({
      required: true,
      confirmPassword: originalPassword
    }),
    
    minLength: (length: number, customMessage?: string): ValidationRule => ({
      required: true,
      minLength: length,
      customMessage
    }),
    
    maxLength: (length: number, customMessage?: string): ValidationRule => ({
      required: true,
      maxLength: length,
      customMessage
    }),
    
    pattern: (regex: RegExp, customMessage?: string): ValidationRule => ({
      required: true,
      pattern: regex,
      customMessage
    })
  }

  return {
    validateField,
    validateForm,
    rules
  }
}
