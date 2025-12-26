<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

const goToHome = () => {
  router.push('/')
}

// Set page title
useHead({
  title: 'Thank You | monogram'
})
</script>

<template>
  <div class="min-h-screen bg-white flex items-center justify-center px-4">
    <div class="max-w-md w-full text-center">
      <!-- Success Icon -->
      <div class="flex justify-center mb-6">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
          <Icon name="mdi:check-circle" class="w-10 h-10 text-green-600" />
        </div>
      </div>

      <!-- Thank You Message -->
      <h1 class="text-3xl font-poppins text-gray-900 mb-4">
        Thank You
      </h1>
      
      <p class="text-gray-600 mb-8">
        We've received your message and will get back to you soon.
      </p>

      <!-- Back Button -->
      <button
        @click="goToHome"
        class="inline-flex items-center bg-zinc-900 text-white py-3 px-8 rounded-lg font-semibold hover:bg-zinc-800 transition-colors"
      >
        Return to Home
      </button>
    </div>
  </div>
</template>

