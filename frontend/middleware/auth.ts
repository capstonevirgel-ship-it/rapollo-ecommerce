import { useAuthStore } from '@/stores/auth'

export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore()

  // If not authenticated, try to fetch user
  if (!auth.isAuthenticated) {
    try {
      await auth.fetchUser()
      // If still not authenticated after fetch, redirect to login
      if (!auth.isAuthenticated) {
        return navigateTo('/login')
      }
    } catch {
      // If fetch fails, redirect to login
      return navigateTo('/login')
    }
  }
})
