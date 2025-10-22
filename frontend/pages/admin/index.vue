<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

// Use admin layout
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Admin - Rapollo E-commerce',
  meta: [
    { name: 'description', content: 'Admin panel for Rapollo E-commerce store management.' }
  ]
})

const authStore = useAuthStore()

// Redirect based on authentication status
onMounted(() => {
  if (authStore.isAuthenticated && authStore.user?.role === 'admin') {
    // User is logged in as admin, redirect to dashboard
    navigateTo('/admin/dashboard')
  } else {
    // User is not logged in or not admin, redirect to admin login
    navigateTo('/admin/login')
  }
})
</script>

<template>
  <PageLoader 
    text="Redirecting..." 
    :show-text="true"
    size="lg"
    color="primary"
  />
</template>
