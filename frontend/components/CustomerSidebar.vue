<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const route = useRoute()

// Get user initials for avatar
const getInitials = computed(() => {
  if (authStore.user?.user_name) {
    return authStore.user.user_name.substring(0, 2).toUpperCase()
  }
  return 'U'
})

// Get join date (this would come from user data in a real app)
const joinDate = computed(() => {
  return 'January 2024'
})

// Navigation items
const navigationItems = [
  {
    name: 'My Profile',
    href: '/profile',
    icon: 'user',
    current: route.path === '/profile'
  },
  {
    name: 'My Orders',
    href: '/my-orders',
    icon: 'shopping-bag',
    current: route.path === '/my-orders'
  },
  {
    name: 'My Tickets',
    href: '/my-tickets',
    icon: 'ticket',
    current: route.path === '/my-tickets'
  },
  {
    name: 'My Reviews',
    href: '/my-reviews',
    icon: 'star',
    current: route.path === '/my-reviews'
  },
  {
    name: 'Settings',
    href: '/settings',
    icon: 'cog',
    current: route.path === '/settings'
  }
]

// Get icon component based on icon name
const getIconComponent = (iconName: string) => {
  const icons = {
    user: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
    </svg>`,
    'shopping-bag': `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
    </svg>`,
    ticket: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
    </svg>`,
    star: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
    </svg>`,
    cog: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>`
  }
  return icons[iconName as keyof typeof icons] || ''
}
</script>

<template>
  <div class="p-6">
    <!-- Avatar Section -->
    <div class="flex flex-col items-center mb-6">
      <div class="w-24 h-24 bg-gradient-to-br from-gray-900 to-gray-700 rounded-full flex items-center justify-center text-white text-2xl font-winner-extra-bold mb-4">
        {{ getInitials }}
      </div>
      <h2 class="text-lg font-bold text-gray-900">{{ authStore.user?.user_name }}</h2>
      <p class="text-sm text-gray-600">{{ authStore.user?.email }}</p>
    </div>

    <!-- Navigation Section -->
    <div class="space-y-2">
      <NuxtLink 
        v-for="item in navigationItems"
        :key="item.name"
        :to="item.href"
        class="flex items-center w-full px-4 py-3 text-sm font-medium rounded-lg transition-colors"
        :class="item.current 
          ? 'bg-gray-900 text-white' 
          : 'text-gray-700 hover:bg-gray-50'"
      >
        <div v-html="getIconComponent(item.icon)" class="mr-3"></div>
        {{ item.name }}
      </NuxtLink>
    </div>
  </div>
</template>
