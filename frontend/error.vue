<script setup lang="ts">
import Footer from '~/components/Footer.vue'

interface ErrorProps {
  error: {
    statusCode: number
    statusMessage?: string
    message?: string
    url?: string
  }
}

const props = defineProps<ErrorProps>()

// Set page title based on error code
useHead({
  title: `${props.error.statusCode} - ${props.error.statusCode === 404 ? 'Page Not Found' : 'Error'} | RAPOLLO`,
  meta: [
    { name: 'description', content: props.error.message || 'An error occurred.' }
  ]
})

const handleError = () => {
  clearError({ redirect: '/' })
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    
    <!-- Main Content -->
    <main class="flex-1 bg-gray-50 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-16">
      <div class="max-w-2xl w-full text-center">
        <!-- Error Code -->
        <div class="mb-6">
          <h1 class="text-8xl md:text-9xl font-winner-extra-bold text-gray-900 mb-4">
            {{ error.statusCode }}
          </h1>
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900">
            {{ error.statusCode === 404 ? 'Page Not Found' : 'An Error Occurred' }}
          </h2>
        </div>

        <!-- Error Message -->
        <div class="mb-8">
          <p class="text-lg text-gray-600 mb-2">
            {{ error.statusCode === 404 
              ? "The page you're looking for doesn't exist." 
              : error.statusMessage || 'Something went wrong.' 
            }}
          </p>
          <p v-if="error.message" class="text-sm text-gray-500">
            {{ error.message }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            @click="handleError"
            class="inline-flex items-center justify-center px-8 py-3 bg-gray-900 text-white font-semibold rounded-lg hover:bg-gray-800 transition-colors"
          >
            <Icon name="mdi:home" class="text-xl mr-2" />
            Go Home
          </button>
        </div>
      </div>
    </main>
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

