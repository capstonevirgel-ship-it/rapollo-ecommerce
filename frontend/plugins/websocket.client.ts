export default defineNuxtPlugin(() => {
  // Only run on client side
  if (process.server) {
    return
  }

  const { connect, disconnect } = useWebSocket()
  const authStore = useAuthStore()

  // Watch for authentication state changes
  watch(
    () => authStore.isAuthenticated,
    (isAuthenticated) => {
      if (isAuthenticated) {
        // Connect when user logs in
        // Small delay to ensure token is set
        setTimeout(() => {
          connect()
        }, 500)
      } else {
        // Disconnect when user logs out
        disconnect()
      }
    },
    { immediate: true }
  )

  // Also watch for token changes
  const token = useCookie('auth-token')
  watch(
    () => token.value,
    (newToken) => {
      if (newToken && authStore.isAuthenticated) {
        // Reconnect with new token
        setTimeout(() => {
          connect()
        }, 500)
      } else if (!newToken) {
        // Disconnect if token is removed
        disconnect()
      }
    }
  )

  // Cleanup on app unmount
  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', () => {
      disconnect()
    })
  }
})

