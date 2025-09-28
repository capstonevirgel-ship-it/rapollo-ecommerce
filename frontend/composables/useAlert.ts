export const useAlert = () => {
  // Check if alert service is available
  const getAlertService = () => {
    if (typeof window !== 'undefined' && window.$alert) {
      return window.$alert
    }
    
    // Fallback for SSR or if not available
    return {
      show: () => {},
      success: () => {},
      error: () => {},
      info: () => {},
      warning: () => {},
      remove: () => {}
    }
  }

  const alert = getAlertService()

  return {
    // Main alert function
    show: (type: 'success' | 'error' | 'info' | 'warning', title: string, message?: string, duration?: number) => {
      return alert.show({ type, title, message, duration })
    },
    
    // Convenience methods
    success: (title: string, message?: string, duration?: number) => {
      return alert.success(title, message, duration)
    },
    
    error: (title: string, message?: string, duration?: number) => {
      return alert.error(title, message, duration)
    },
    
    info: (title: string, message?: string, duration?: number) => {
      return alert.info(title, message, duration)
    },
    
    warning: (title: string, message?: string, duration?: number) => {
      return alert.warning(title, message, duration)
    },
    
    // Remove specific alert
    remove: (id: string) => {
      alert.remove(id)
    }
  }
}

// Declare global types for TypeScript
declare global {
  interface Window {
    $alert: {
      show: (alert: { type: 'success' | 'error' | 'info' | 'warning', title: string, message?: string, duration?: number }) => string
      success: (title: string, message?: string, duration?: number) => string
      error: (title: string, message?: string, duration?: number) => string
      info: (title: string, message?: string, duration?: number) => string
      warning: (title: string, message?: string, duration?: number) => string
      remove: (id: string) => void
    }
  }
}
