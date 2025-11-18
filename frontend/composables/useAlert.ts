export const useAlert = () => {
  // Always resolve the alert service at call time to avoid SSR/no-op capture
  const getAlertService = () => {
    if (typeof window !== 'undefined' && (window as any).$alert) {
      return (window as any).$alert
    }
    // Fallback for SSR or if not available yet
    return {
      show: () => {},
      success: () => {},
      error: () => {},
      info: () => {},
      warning: () => {},
      remove: () => {}
    }
  }

  return {
    // Main alert function
    show: (type: 'success' | 'error' | 'info' | 'warning', title: string, message?: string, duration?: number) => {
      const alert = getAlertService()
      return alert.show({ type, title, message, duration })
    },
    
    // Convenience methods
    success: (title: string, message?: string, duration?: number) => {
      const alert = getAlertService()
      return alert.success(title, message, duration)
    },
    
    error: (title: string, message?: string, duration?: number) => {
      const alert = getAlertService()
      return alert.error(title, message, duration)
    },
    
    info: (title: string, message?: string, duration?: number) => {
      const alert = getAlertService()
      return alert.info(title, message, duration)
    },
    
    warning: (title: string, message?: string, duration?: number) => {
      const alert = getAlertService()
      return alert.warning(title, message, duration)
    },
    
    // Remove specific alert
    remove: (id: string) => {
      const alert = getAlertService()
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
