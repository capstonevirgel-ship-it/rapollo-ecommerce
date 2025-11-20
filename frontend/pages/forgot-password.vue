<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { useAlert } from '~/composables/useAlert'
import { useValidation } from '~/composables/useValidation'

// Disable default layout
definePageMeta({
  layout: false
})

// Set page title
useHead({
  title: 'Forgot Password | RAPOLLO',
  meta: [
    { name: 'description', content: 'Reset your Rapollo E-commerce account password.' }
  ]
})

const authStore = useAuthStore()
const router = useRouter()
const { success, error } = useAlert()
const { validateForm, rules } = useValidation()

// Form data
const form = reactive({
  email: ''
})

// Form validation
const errors = ref<Record<string, string[]>>({})
const emailSent = ref(false)

// Validation rules
const validationRules = {
  email: {
    ...rules.required('Email is required'),
    ...rules.email('Please enter a valid email address')
  }
}

// Handle form submission
const handleSubmit = async () => {
  // Clear previous errors
  errors.value = {}
  
  // Validate form
  const validation = validateForm(form, validationRules)
  
  if (!validation.isValid) {
    errors.value = validation.errors
    error('Validation Failed', 'Please check the form for errors.')
    return
  }
  
  try {
    await authStore.forgotPassword(form.email)
    
    // Show success message
    emailSent.value = true
    success('Email Sent', 'If an account exists with that email, we have sent a password reset link.')
  } catch (err: any) {
    console.error('Forgot password error:', err)
    
    // Show error message
    error('Request Failed', err?.message || 'Failed to send password reset link. Please try again.')
  }
}

// Helper function to get first error for a field
const getFieldError = (fieldName: string): string => {
  return errors.value[fieldName]?.[0] || ''
}

// Helper function to check if field has errors
const hasFieldError = (fieldName: string): boolean => {
  return errors.value[fieldName] && errors.value[fieldName].length > 0
}

// Redirect if already authenticated
onMounted(() => {
  if (authStore.isAuthenticated) {
    if (authStore.isAdmin) {
      router.push('/admin/dashboard')
    } else {
      router.push('/')
    }
  }
})
</script>

<template>
  <div class="min-h-screen flex bg-gray-50">
    <!-- Left Side - Promotional Section -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-gray-900 via-black to-gray-800 relative overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-20">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.1) 1px, transparent 0); background-size: 30px 30px;"></div>
      </div>
      
      <!-- Geometric Shapes -->
      <div class="absolute top-20 left-20 w-32 h-32 bg-gradient-to-br from-gray-700 to-gray-800 rounded-full opacity-30"></div>
      <div class="absolute bottom-32 right-16 w-24 h-24 bg-gradient-to-br from-gray-600 to-gray-700 rounded-lg transform rotate-45 opacity-20"></div>
      <div class="absolute top-1/2 right-20 w-16 h-16 bg-gradient-to-br from-gray-500 to-gray-600 rounded-full opacity-40"></div>
      
      <!-- Content -->
      <div class="relative z-10 flex flex-col justify-center px-12 text-white">
        <div class="max-w-md">
          <h1 class="text-4xl font-bold mb-6 leading-tight">
            Reset Your 
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-300">Password</span>
          </h1>
          <p class="text-xl text-gray-300 leading-relaxed">
            Enter your email address and we'll send you a link to reset your password.
          </p>
        </div>
      </div>
    </div>

    <!-- Right Side - Forgot Password Form -->
    <div class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:px-20 xl:px-24">
      <div class="mx-auto w-full max-w-sm lg:w-96">
        <!-- Back to Login Link -->
        <div class="mb-6">
          <NuxtLink 
            to="/login" 
            class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-zinc-900 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Login
          </NuxtLink>
        </div>
        
        <!-- Header -->
        <div class="text-center">
          <h2 class="text-3xl font-bold text-gray-900">Forgot Password</h2>
          <p class="mt-2 text-sm text-gray-600">
            Remember your password? 
            <NuxtLink to="/login" class="font-medium text-zinc-900 hover:text-zinc-700">
              Sign in
            </NuxtLink>
          </p>
        </div>

        <!-- Success Message -->
        <div v-if="emailSent" class="mt-8 rounded-md bg-green-50 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">
                Check Your Email
              </h3>
              <div class="mt-2 text-sm text-green-700">
                <p>We've sent a password reset link to <strong>{{ form.email }}</strong>. Please check your email and follow the instructions to reset your password.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Forgot Password Form -->
        <form v-else @submit.prevent="handleSubmit" class="mt-8 space-y-6">
          <div class="space-y-4">
            <!-- Email Field -->
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">
                Email Address
              </label>
              <div class="mt-1">
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  autocomplete="email"
                  class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-zinc-500 focus:border-zinc-500 sm:text-sm"
                  :class="{ 'border-red-500': hasFieldError('email') }"
                  placeholder="Enter your email address"
                />
                <p v-if="hasFieldError('email')" class="mt-1 text-sm text-red-600">{{ getFieldError('email') }}</p>
              </div>
            </div>
          </div>

          <!-- Error Messages -->
          <div v-if="authStore.error" class="rounded-md bg-red-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                  Request Failed
                </h3>
                <div class="mt-2 text-sm text-red-700">
                  <p>{{ authStore.error }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div>
            <LoadingButton
              type="submit"
              :loading="authStore.loading"
              :disabled="authStore.loading"
              loading-text="Sending..."
              normal-text="Send Reset Link"
              variant="primary"
              size="md"
              class="w-full"
            />
          </div>
        </form>
      </div>
    </div>
    
    <!-- Alert Component -->
    <Alert />
  </div>
</template>

