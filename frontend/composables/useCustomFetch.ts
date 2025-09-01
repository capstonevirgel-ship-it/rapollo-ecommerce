// composables/useCustomFetch.ts
export const useCustomFetch = async <T>(url: string, opts: any = {}): Promise<T> => {
  const config = useRuntimeConfig()
  const xsrfToken = useCookie('XSRF-TOKEN')

  // On client-side, ensure we have a CSRF token before making requests
  if (import.meta.client && !xsrfToken.value) {
    await $fetch('/sanctum/csrf-cookie', {
      baseURL: config.public.apiBase,
      credentials: 'include'
    })
  }

  // Prepare headers with proper typing
  const headers: Record<string, string> = {
    accept: 'application/json',
    ...(opts?.headers || {})
  }

  // Add CSRF token if available (with type assertion)
  if (xsrfToken.value) {
    headers['X-XSRF-TOKEN'] = xsrfToken.value as string
  }

  // Add server-specific headers
  if (import.meta.server) {
    headers.referer = config.public.baseURL as string
    
    // Forward cookies for server-side requests
    const cookieHeaders = useRequestHeaders(['cookie'])
    if (cookieHeaders.cookie) {
      headers.cookie = cookieHeaders.cookie as string
    }
  }

  return $fetch<T>(url, {
    baseURL: config.public.apiBase,
    credentials: 'include',
    ...opts,
    headers
  })
}