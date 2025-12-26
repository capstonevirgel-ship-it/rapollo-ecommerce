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
  title: 'Register | monogram',
  meta: [
    { name: 'description', content: 'Create your monogram E-commerce account to start shopping and booking event tickets.' }
  ]
})

const authStore = useAuthStore()
const router = useRouter()
const { success, error: showError } = useAlert()
const { validateForm, rules } = useValidation()

// Global error handler
const handleError = (error: any) => {
  console.error('Unhandled error:', error)
  showError('An error occurred', 'Please try again later.')
}

// Vue error handler
onErrorCaptured((error: any) => {
  handleError(error)
  return false // Prevent the error from propagating
})

// Form data
const form = reactive({
  user_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false
})


// Form validation
const errors = ref<Record<string, string[]>>({})
const showPassword = ref(false)
const showConfirmPassword = ref(false)

// Validation rules with proper field labels
const validationRules = {
  user_name: {
    ...rules.username(8),
    fieldName: 'Username'
  },
  email: {
    ...rules.email(),
    fieldName: 'Email'
  },
  password: {
    ...rules.password(),
    fieldName: 'Password'
  },
  password_confirmation: {
    ...rules.confirmPassword(form.password),
    fieldName: 'Confirm Password'
  },
  terms: {
    ...rules.required(),
    fieldName: 'Terms and Conditions'
  }
}

// Handle form submission
const handleSubmit = async (event: Event) => {
  event.preventDefault()
  
  try {
  // Clear previous errors
  errors.value = {}
  
    // Validate form
    const validation = validateForm(form, validationRules)
    
    
    if (!validation.isValid) {
      errors.value = validation.errors
      showError('Validation Failed', 'Please check the form for errors.')
      return
    }
  
    await authStore.register({
      user_name: form.user_name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation
    })
    
    // Show success message
    success('Registration Successful', 'Your account has been created and you are now logged in!')
    
    // Redirect will be handled by the store (automatic login)
  } catch (error: any) {
    console.error('Registration error:', error)
    
    // Handle validation errors
    if (error?.data?.errors) {
      // Convert backend errors to our format
      const backendErrors: Record<string, string[]> = {}
      for (const [field, messages] of Object.entries(error.data.errors)) {
        backendErrors[field] = Array.isArray(messages) ? messages : [messages as string]
      }
      errors.value = backendErrors
      showError('Registration Failed', 'Please check the form for errors.')
    } else {
      errors.value.general = [authStore.error || 'Registration failed. Please try again.']
      showError('Registration Failed', authStore.error || 'Registration failed. Please try again.')
    }
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

// Password toggle functions with error handling
const togglePassword = () => {
  try {
    showPassword.value = !showPassword.value
  } catch (error) {
    handleError(error)
  }
}

const toggleConfirmPassword = () => {
  try {
    showConfirmPassword.value = !showConfirmPassword.value
  } catch (error) {
    handleError(error)
  }
}
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
          <h1 class="text-4xl font-poppins mb-6 leading-tight">
            monogram
          </h1>
          <p class="text-xl text-gray-300 leading-relaxed">
            Create your account and start shopping with us today. Get access to exclusive deals and personalized recommendations.
          </p>
        </div>
        
        <!-- Login Model Illustration -->
        <div class="absolute bottom-0 right-0 translate-x-[23rem]">
          <img 
            src="/uploads/login_model_image.svg" 
            alt="Login Model" 
            class="w-[40rem] h-[40rem] object-contain filter"
          />
        </div>
      </div>
    </div>

    <!-- Right Side - Registration Form -->
    <div class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:px-20 xl:px-24">
      <div class="mx-auto w-full max-w-sm lg:w-96">
        <!-- Back to Home Link -->
        <div class="mb-6">
          <NuxtLink 
            to="/" 
            class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-zinc-900 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to the Shop
          </NuxtLink>
        </div>
        
        <!-- Header -->
        <div class="text-center">
          <h2 class="text-3xl font-bold text-gray-900">Create your account</h2>
          <p class="mt-2 text-sm text-gray-600">
            Already have an account? 
            <NuxtLink to="/login" class="font-medium text-zinc-900 hover:text-zinc-700">
              Sign in here
            </NuxtLink>
          </p>
        </div>

        <!-- Registration Form -->
        <form @submit="handleSubmit" class="mt-8 space-y-6">
          <div class="space-y-4">
            <!-- Username Field -->
            <div>
              <label for="user_name" class="block text-sm font-medium text-gray-700">
                Username
              </label>
              <div class="mt-1">
                <input
                  id="user_name"
                  v-model="form.user_name"
                  type="text"
                  autocomplete="username"
                  class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-zinc-500 focus:border-zinc-500 sm:text-sm"
                  :class="{ 'border-red-500': hasFieldError('user_name') }"
                  placeholder="Choose a username"
                />
                <p v-if="hasFieldError('user_name')" class="mt-1 text-sm text-red-600">{{ getFieldError('user_name') }}</p>
              </div>
            </div>

            <!-- Email Field -->
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">
                Email Address
              </label>
              <div class="mt-1">
                <input
                  id="email"
                  v-model="form.email"
                  type="text"
                  autocomplete="email"
                  class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-zinc-500 focus:border-zinc-500 sm:text-sm"
                  :class="{ 'border-red-500': hasFieldError('email') }"
                  placeholder="Enter your email address"
                />
                <p v-if="hasFieldError('email')" class="mt-1 text-sm text-red-600">{{ getFieldError('email') }}</p>
              </div>
            </div>

            <!-- Password Field -->
            <div>
              <label for="password" class="block text-sm font-medium text-gray-700">
                Password
              </label>
              <div class="mt-1">
                <div class="relative">
                  <input
                    id="password"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    class="appearance-none block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-zinc-500 focus:border-zinc-500 sm:text-sm"
                    :class="{ 'border-red-500': hasFieldError('password') }"
                    placeholder="Create a password"
                  />
                <button
                  type="button"
                    @click="togglePassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center z-10"
                >
                  <svg v-if="showPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                  </svg>
                </button>
                </div>
                <p v-if="hasFieldError('password')" class="mt-1 text-sm text-red-600">{{ getFieldError('password') }}</p>
              </div>
            </div>

            <!-- Confirm Password Field -->
            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                Confirm Password
              </label>
              <div class="mt-1">
                <div class="relative">
                  <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    class="appearance-none block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-zinc-500 focus:border-zinc-500 sm:text-sm"
                    :class="{ 'border-red-500': hasFieldError('password_confirmation') }"
                    placeholder="Confirm your password"
                  />
                <button
                  type="button"
                    @click="toggleConfirmPassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center z-10"
                >
                  <svg v-if="showConfirmPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                  </svg>
                </button>
                </div>
                <p v-if="hasFieldError('password_confirmation')" class="mt-1 text-sm text-red-600">{{ getFieldError('password_confirmation') }}</p>
              </div>
            </div>
          </div>

          <!-- Terms and Conditions -->
          <div class="flex items-center">
            <input
              id="terms"
              v-model="form.terms"
              type="checkbox"
              class="h-4 w-4 text-zinc-900 focus:ring-zinc-500 border-gray-300 rounded"
            />
            <label for="terms" class="ml-2 block text-sm text-gray-900">
              I agree to the 
              <a href="#" class="text-zinc-900 hover:text-zinc-700">Terms and Conditions</a>
              and 
              <a href="#" class="text-zinc-900 hover:text-zinc-700">Privacy Policy</a>
            </label>
          </div>
          <p v-if="hasFieldError('terms')" class="text-sm text-red-600">{{ getFieldError('terms') }}</p>

          <!-- Error Messages -->
          <div v-if="hasFieldError('general')" class="rounded-md bg-red-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                  Registration Failed
                </h3>
                <div class="mt-2 text-sm text-red-700">
                  <p>{{ getFieldError('general') }}</p>
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
              loading-text="Creating account..."
              normal-text="Create account"
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

<style scoped>
/* Ensure password toggle buttons stay properly positioned */
.relative button[type="button"] {
  position: absolute;
  top: 0;
  right: 0;
  height: 100%;
  z-index: 10;
  background: transparent;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding-right: 0.75rem;
}

.relative button[type="button"]:hover {
  background: transparent;
}

.relative button[type="button"]:focus {
  outline: none;
  background: transparent;
}
</style>