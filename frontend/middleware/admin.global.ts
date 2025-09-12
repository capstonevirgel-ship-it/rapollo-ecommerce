export default defineNuxtRouteMiddleware((to) => {
  const authStore = useAuthStore()
  
  // Check if the route is an admin route
  if (to.path.startsWith('/admin')) {
    // Allow access to admin login page
    if (to.path === '/admin/login') {
      return
    }
    
    // If user is not authenticated, redirect to admin login
    if (!authStore.isAuthenticated) {
      return navigateTo('/admin/login')
    }
    
    // If user is authenticated but not admin, redirect to home
    if (!authStore.isAdmin) {
      return navigateTo('/')
    }
  }
})
