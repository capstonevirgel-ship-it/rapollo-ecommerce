<script setup lang="ts">
interface Props {
  status: string
  type?: 'purchase' | 'payment' | 'ticket' | 'auto'
}

const props = withDefaults(defineProps<Props>(), {
  type: 'auto'
})

const getStatusConfig = () => {
  const status = props.status.toLowerCase()
  
  // Define all possible status configurations
  const statusConfigs: Record<string, { color: string; label?: string }> = {
    // Purchase statuses
    'pending': { color: 'bg-blue-100 text-blue-800' },
    'processing': { color: 'bg-yellow-100 text-yellow-800' },
    'completed': { color: 'bg-green-100 text-green-800' },
    'delivered': { color: 'bg-green-100 text-green-800' },
    'cancelled': { color: 'bg-red-100 text-red-800' },
    'failed': { color: 'bg-red-100 text-red-800' },
    'shipped': { color: 'bg-purple-100 text-purple-800' },
    
    // Payment statuses
    'paid': { color: 'bg-green-100 text-green-800' },
    'refunded': { color: 'bg-purple-100 text-purple-800' },
    
    // Ticket statuses
    'confirmed': { color: 'bg-green-100 text-green-800' },
    'used': { color: 'bg-blue-100 text-blue-800' },
  }
  
  // Get configuration or use default
  const config = statusConfigs[status] || { color: 'bg-gray-100 text-gray-800' }
  
  // Capitalize first letter for label
  const label = config.label || (status.charAt(0).toUpperCase() + status.slice(1))
  
  return {
    color: config.color,
    label
  }
}

const statusConfig = getStatusConfig()
</script>

<template>
  <span :class="[
    'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold',
    statusConfig.color
  ]">
    {{ statusConfig.label }}
  </span>
</template>

