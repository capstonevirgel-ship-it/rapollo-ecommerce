<script setup lang="ts">
definePageMeta({
  layout: false
})

const username = ref('')
const password = ref('')
const rememberMe = ref(false)
const authStore = useAuthStore()

async function handleLogin() {
  try {
    await authStore.login({
      login: username.value,
      password: password.value,
      remember: rememberMe.value
    })
  } catch (error) {
    // Error is already handled in the store
    console.error('Login error:', error)
  }
}

</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center px-4 py-10">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <Icon name="mdi:shield-account" class="mx-auto h-12 w-12 text-indigo-600" />
        <h2 class="mt-4 text-2xl font-bold text-gray-900">Admin Portal</h2>
        <p class="mt-1 text-sm text-gray-600">Sign in to access your dashboard</p>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-md">
        <form class="space-y-6" @submit.prevent="handleLogin">
          <!-- Username -->
          <div class="mb-4">
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
              Username
            </label>
            <div class="flex items-center border border-gray-300 rounded-md overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 bg-gray-100">
              <div class="flex items-center justify-center px-3 text-gray-400">
                <Icon name="mdi:account" class="h-5 w-5" />
              </div>
              <input
                id="username"
                v-model="username"
                name="username"
                type="text"
                required
                placeholder="admin"
                autocomplete="username"
                class="w-full py-2 px-3 text-sm focus:outline-none bg-white"
              />
            </div>
          </div>

          <!-- Password -->
          <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              Password
            </label>
            <div class="flex items-center border border-gray-300 rounded-md overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 bg-gray-100">
              <div class="flex items-center justify-center px-3 text-gray-400">
                <Icon name="mdi:lock-outline" class="h-5 w-5" />
              </div>
              <input
                id="password"
                v-model="password"
                name="password"
                type="password"
                required
                placeholder="••••••••"
                autocomplete="current-password"
                class="w-full py-2 px-3 text-sm focus:outline-none bg-white"
              />
            </div>
          </div>

          <!-- Remember Me + Forgot -->
          <div class="flex items-center justify-between pt-1 mb-2">
            <label class="flex items-center text-sm text-gray-700">
              <input
                type="checkbox"
                v-model="rememberMe"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <span class="ml-2">Remember me</span>
            </label>
            <a href="#" class="text-sm text-indigo-600 hover:underline">Forgot password?</a>
          </div>

          <!-- Error Message -->
          <div v-if="authStore.error" class="text-red-500 text-sm text-center">
            {{ authStore.error }}
          </div>

          <!-- Submit -->
          <div class="pt-2">
            <button
              type="submit"
              :disabled="authStore.loading"
              class="w-full flex justify-center items-center gap-2 py-2 px-4 bg-zinc-800 hover:bg-zinc-700 text-white text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Icon v-if="authStore.loading" name="mdi:loading" class="animate-spin h-4 w-4" />
              {{ authStore.loading ? 'Signing in...' : 'Sign in' }}
            </button>
          </div>
        </form>
      </div>

      <p class="mt-8 text-center text-sm text-gray-500">
        © {{ new Date().getFullYear() }} Your Company. All rights reserved.
      </p>
    </div>
  </div>
</template>