// composables/useCustomFetch.ts
export const useCustomFetch = async <T>(url: string, opts: any = {}): Promise<T> => {
  const config = useRuntimeConfig()
  
  // Get the auth token from cookies
  const token = useCookie('auth-token')
  
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
    
    const response = await $fetch<T>(fullUrl, {
      credentials: 'include',
      ...opts,
      headers: {
        'Accept': 'application/json',
        // Don't set Content-Type for FormData - let the browser set it with boundary
        ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
        ...(token.value ? { 'Authorization': `Bearer ${token.value}` } : {}),
        ...(opts?.headers || {})
      }
    })
    return response
  } catch (error) {
    console.error('API call failed:', error)
    throw error
  }
}