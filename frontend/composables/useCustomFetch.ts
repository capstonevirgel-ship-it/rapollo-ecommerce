// composables/useCustomFetch.ts
export const useCustomFetch = async <T>(url: string, opts: any = {}): Promise<T> => {
  // Safely get runtime config - handle cases where it's not available
  let config: any = null
  try {
    config = useRuntimeConfig()
  } catch (error) {
    console.warn('useRuntimeConfig not available, using fallback configuration')
    // Fallback configuration for when Nuxt context is not available
    config = {
      public: {
        apiBase: process.dev ? 'http://localhost:8000/api' : 'http://localhost:8000/api'
      }
    }
  }
  
  // Safely get auth token from cookies
  let token: any = null
  try {
    token = useCookie('auth-token')
  } catch (error) {
    console.warn('useCookie not available, proceeding without auth token')
    token = { value: null }
  }
  
  // Build the full URL - use live server in production, proxy in development
  let fullUrl: string
  if (process.dev) {
    // Development: use proxy
    fullUrl = url.startsWith('/api') ? url : `/api${url}`
  } else {
    // Production: use live server
    fullUrl = url.startsWith('/api') ? `${config.public.apiBase}${url.slice(4)}` : `${config.public.apiBase}${url}`
  }
  
  try {
    // Check if body is FormData to handle multipart/form-data correctly
    const isFormData = opts.body instanceof FormData
    
    console.log('Making API call to:', fullUrl)
    const response = await $fetch<T>(fullUrl, {
      credentials: 'include',
      ...opts,
      headers: {
        'Accept': 'application/json',
        // Don't set Content-Type for FormData - let the browser set it with boundary
        ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
        ...(token?.value ? { 'Authorization': `Bearer ${token.value}` } : {}),
        ...(opts?.headers || {})
      }
    })
    console.log('API call successful:', fullUrl)
    return response
  } catch (error) {
    console.error('API call failed:', fullUrl, error)
    throw error
  }
}