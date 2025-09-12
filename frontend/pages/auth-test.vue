<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold mb-4">Authentication Test</h1>
    
    <div class="mb-4">
      <h2 class="text-lg font-semibold mb-2">Auth Status:</h2>
      <p>Is Authenticated: {{ authStore.isAuthenticated }}</p>
      <p>User: {{ authStore.user ? JSON.stringify(authStore.user) : 'None' }}</p>
      <p>Token: {{ token ? 'Present' : 'Missing' }}</p>
    </div>

    <div class="mb-4">
      <button @click="testUserEndpoint" class="bg-zinc-900 hover:bg-zinc-800 text-white px-4 py-2 rounded mr-2" :disabled="loading">
        Test /api/user
      </button>
      <button @click="testTicketsEndpoint" class="bg-zinc-900 hover:bg-zinc-800 text-white px-4 py-2 rounded" :disabled="loading">
        Test /api/tickets
      </button>
    </div>

    <div v-if="loading" class="text-blue-600">Loading...</div>
    <div v-if="error" class="text-red-600">Error: {{ error }}</div>
    <div v-if="result" class="bg-gray-100 p-4 rounded">
      <h3 class="font-semibold mb-2">Result:</h3>
      <pre>{{ JSON.stringify(result, null, 2) }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
const authStore = useAuthStore()
const token = useCookie('auth-token')
const loading = ref(false)
const error = ref<string | null>(null)
const result = ref<any>(null)

const testUserEndpoint = async () => {
  loading.value = true
  error.value = null
  result.value = null
  
  try {
    const response = await useCustomFetch('/api/user')
    result.value = response
  } catch (err: any) {
    error.value = err.data?.message || err.message || 'Request failed'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
}

const testTicketsEndpoint = async () => {
  loading.value = true
  error.value = null
  result.value = null
  
  try {
    const response = await useCustomFetch('/api/tickets')
    result.value = response
  } catch (err: any) {
    error.value = err.data?.message || err.message || 'Request failed'
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
}
</script>
