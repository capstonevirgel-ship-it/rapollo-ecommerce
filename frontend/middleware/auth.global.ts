import { useAuthStore } from '@/stores/auth'

export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore()

  // Handle /admin/login route
  if (to.path === '/admin/login') {
    // Only check existing authentication state, don't try to fetch user
    if (auth.isAuthenticated) {
      return navigateTo('/admin/dashboard')
    }
    // Allow access to login page if not authenticated
    return
  }

  // Protect all other admin routes
  if (to.path.startsWith('/admin')) {
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
  }
})