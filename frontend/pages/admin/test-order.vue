<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Test Order - Admin - Rapollo E-commerce',
  meta: [
    { name: 'description', content: 'Test order page for admin functionality.' }
  ]
})
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold">Test Order Page</h1>
    <p>This is a test page to check if admin routing is working.</p>
  </div>
</template>
