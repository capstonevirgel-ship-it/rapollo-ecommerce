<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

// Disable default layout
definePageMeta({
  layout: false
})

const authStore = useAuthStore()
const router = useRouter()

// Form data
const form = reactive({
  login: '',
  password: '',
  remember: false
})

// Form validation
const errors = ref<Record<string, string>>({})
const showPassword = ref(false)

// Handle form submission
const handleSubmit = async () => {
  // Clear previous errors
  errors.value = {}
  
  // Basic validation
  if (!form.login) {
    errors.value.login = 'Email or username is required'
  }
  if (!form.password) {
    errors.value.password = 'Password is required'
  }
  
  if (Object.keys(errors.value).length > 0) {
    return
  }
  
  try {
    await authStore.login({
      login: form.login,
      password: form.password,
      remember: form.remember
    })
    // Redirect will be handled by the store
  } catch (error: any) {
    console.error('Login error:', error)
    // Error is already handled in the store
  }
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
            Welcome to 
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-300">Rapollo</span>
            <br>
            <span class="underline decoration-gray-400 decoration-2">Ecommerce</span>
          </h1>
          <p class="text-xl text-gray-300 leading-relaxed">
            Discover amazing products and enjoy a seamless shopping experience with our modern e-commerce platform.
          </p>
        </div>
        
        <!-- 3D Characters Illustration -->
        <div class="mt-16 flex justify-center space-x-8">
          <!-- Male Character -->
          <div class="relative character-3d">
            <div class="w-32 h-40 bg-gradient-to-b from-gray-700 to-gray-800 rounded-2xl shadow-2xl transform rotate-3 hover:rotate-6 transition-transform duration-300 border border-gray-600">
              <!-- Head -->
              <div class="absolute top-4 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-gray-600 rounded-full border-2 border-gray-500"></div>
              <!-- Body -->
              <div class="absolute top-16 left-1/2 transform -translate-x-1/2 w-20 h-16 bg-gray-700 rounded-lg"></div>
              <!-- Arms -->
              <div class="absolute top-18 left-2 w-6 h-12 bg-gray-600 rounded-full"></div>
              <div class="absolute top-18 right-2 w-6 h-12 bg-gray-600 rounded-full"></div>
              <!-- Legs -->
              <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 w-16 h-8 bg-gray-800 rounded-lg"></div>
            </div>
          </div>
          
          <!-- Female Character -->
          <div class="relative character-3d">
            <div class="w-32 h-40 bg-gradient-to-b from-gray-600 to-gray-700 rounded-2xl shadow-2xl transform -rotate-3 hover:-rotate-6 transition-transform duration-300 border border-gray-500">
              <!-- Head -->
              <div class="absolute top-4 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-gray-500 rounded-full border-2 border-gray-400"></div>
              <!-- Body -->
              <div class="absolute top-16 left-1/2 transform -translate-x-1/2 w-20 h-16 bg-gray-600 rounded-lg"></div>
              <!-- Arms -->
              <div class="absolute top-18 left-2 w-6 h-12 bg-gray-500 rounded-full"></div>
              <div class="absolute top-18 right-2 w-6 h-12 bg-gray-500 rounded-full"></div>
              <!-- Legs -->
              <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 w-16 h-8 bg-gray-700 rounded-lg"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:px-20 xl:px-24">
      <div class="mx-auto w-full max-w-sm lg:w-96">
        <!-- Header -->
        <div class="text-center">
          <h2 class="text-3xl font-bold text-gray-900">Welcome back</h2>
          <p class="mt-2 text-sm text-gray-600">
            Don't have an account? 
            <NuxtLink to="/register" class="font-medium text-zinc-900 hover:text-zinc-700">
              Sign up for free
            </NuxtLink>
          </p>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="handleSubmit" class="mt-8 space-y-6">
          <div class="space-y-4">
            <!-- Email/Username Field -->
            <div>
              <label for="login" class="block text-sm font-medium text-gray-700">
                Email or Username
              </label>
              <div class="mt-1">
                <input
                  id="login"
                  v-model="form.login"
                  type="text"
                  autocomplete="username"
                  required
                  class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-zinc-500 focus:border-zinc-500 sm:text-sm"
                  :class="{ 'border-red-500': errors.login }"
                  placeholder="Enter your email or username"
                />
                <p v-if="errors.login" class="mt-1 text-sm text-red-600">{{ errors.login }}</p>
              </div>
            </div>

            <!-- Password Field -->
            <div>
              <label for="password" class="block text-sm font-medium text-gray-700">
                Password
              </label>
              <div class="mt-1 relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  required
                  class="appearance-none block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-zinc-500 focus:border-zinc-500 sm:text-sm"
                  :class="{ 'border-red-500': errors.password }"
                  placeholder="Enter your password"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center"
                >
                  <svg v-if="showPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                  </svg>
                </button>
                <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
              </div>
            </div>
          </div>

          <!-- Remember Me & Forgot Password -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember"
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 text-zinc-900 focus:ring-zinc-500 border-gray-300 rounded"
              />
              <label for="remember" class="ml-2 block text-sm text-gray-900">
                Remember me
              </label>
            </div>
            <div class="text-sm">
              <a href="#" class="font-medium text-zinc-900 hover:text-zinc-700">
                Forgot your password?
              </a>
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
                  Login Failed
                </h3>
                <div class="mt-2 text-sm text-red-700">
                  <p>{{ authStore.error }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="authStore.loading"
              class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
            >
              <span v-if="authStore.loading" class="absolute left-0 inset-y-0 flex items-center pl-3">
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </span>
              {{ authStore.loading ? 'Signing in...' : 'Sign in' }}
            </button>
          </div>

          <!-- Social Login -->
          <div class="mt-6">
            <div class="relative">
              <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300" />
              </div>
              <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-gray-50 text-gray-500">Or continue with</span>
              </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
              <button
                type="button"
                class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                <svg class="h-5 w-5" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                  <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                  <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                  <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span class="ml-2">Google</span>
              </button>

              <button
                type="button"
                class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span class="ml-2">Facebook</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.character-3d {
  animation: float 6s ease-in-out infinite;
}

.character-3d:nth-child(2) {
  animation-delay: -3s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-20px);
  }
}
</style>