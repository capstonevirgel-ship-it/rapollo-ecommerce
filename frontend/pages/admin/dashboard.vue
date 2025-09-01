<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})
</script>

<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-4">This is the Admin Page</h1>
    <!-- Add your content here -->
    <div class="space-y-4">
      <div v-for="i in 20" :key="i" class="p-4 bg-white dark:bg-gray-800 rounded shadow">
        Content Item {{ i }}
      </div>
    </div>
  </div>
</template>

