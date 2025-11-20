<template>
  <div class="min-h-screen flex flex-col bg-gray-50">
    <AdminHeader />
    <Header />
    <main class="flex-grow" :class="{ 'pt-[3.5rem]': authStore.isAdmin }">
      <div class="mx-auto">
        <NuxtPage></NuxtPage>
      </div>
    </main>
    <Footer />
    
    <!-- Global Alert Component -->
    <Alert />
  </div>
</template>

<script setup>
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
</script>

<style>

</style>