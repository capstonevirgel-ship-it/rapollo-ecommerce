<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  title: string
  value: string | number
  icon?: string
  iconColor?: string
  iconBgColor?: string
  growth?: number
  growthLabel?: string
  valueColor?: string
}

const props = withDefaults(defineProps<Props>(), {
  icon: '',
  iconColor: 'text-zinc-600',
  iconBgColor: 'bg-zinc-100',
  growth: undefined,
  growthLabel: 'vs last month',
  valueColor: 'text-gray-900'
})

const formatNumber = (num: number | string): string => {
  // If it's already a string (e.g., currency), return as is
  if (typeof num === 'string') {
    return num
  }

  // Handle numbers
  const absNum = Math.abs(num)
  
  // 7-9 digits: format as millions (M)
  if (absNum >= 1000000 && absNum < 1000000000) {
    const millions = num / 1000000
    // Show 1 decimal place if needed, otherwise show as integer
    const formatted = millions % 1 === 0 
      ? millions.toString() 
      : millions.toFixed(1).replace(/\.?0+$/, '')
    return `${formatted}m`
  }
  
  // 4-6 digits: format as thousands (K)
  if (absNum >= 1000 && absNum < 1000000) {
    const thousands = num / 1000
    // Show 1 decimal place if needed, otherwise show as integer
    const formatted = thousands % 1 === 0 
      ? thousands.toString() 
      : thousands.toFixed(1).replace(/\.?0+$/, '')
    return `${formatted}k`
  }
  
  // Less than 1000: return as is
  return num.toString()
}

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return formatNumber(props.value)
  }
  return props.value
})

const getGrowthColor = (growth: number) => {
  return growth >= 0 ? 'text-green-600' : 'text-red-600'
}

const getGrowthIcon = (growth: number) => {
  return growth >= 0 ? '↗' : '↘'
}
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6 lg:p-8">
    <div class="flex items-center justify-between">
      <div class="flex-1 min-w-0">
        <p class="text-xs sm:text-sm font-medium text-gray-600">{{ title }}</p>
        <p :class="['text-xl sm:text-2xl lg:text-3xl font-bold truncate', valueColor]">
          {{ formattedValue }}
        </p>
        <div v-if="growth !== undefined" class="flex flex-col sm:flex-row sm:items-center mt-2 gap-1 sm:gap-0">
          <span :class="['text-xs sm:text-sm font-medium', getGrowthColor(growth)]">
            {{ getGrowthIcon(growth) }} {{ Math.abs(growth) }}%
          </span>
          <span class="text-xs sm:text-sm text-gray-500 sm:ml-1">{{ growthLabel }}</span>
        </div>
      </div>
      <div v-if="icon" :class="['p-2 sm:p-3 rounded-lg flex-shrink-0 ml-2', iconBgColor]">
        <Icon :name="icon" :class="['w-5 h-5 sm:w-6 sm:h-6', iconColor]" />
      </div>
    </div>
  </div>
</template>

