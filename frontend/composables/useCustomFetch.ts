// composables/useCustomFetch.ts
export const useCustomFetch = async <T>(url: string, opts: any = {}): Promise<T> => {
  const config = useRuntimeConfig()
  
  // Get the auth token from cookies
  const token = useCookie('auth-token')
  
  // Use the proxy for development
  const fullUrl = url.startsWith('/api') ? url : `/api${url}`
  
  try {
    const response = await $fetch<T>(fullUrl, {
      credentials: 'include',
      ...opts,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
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