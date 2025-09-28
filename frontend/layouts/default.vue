<template>
  <div class="min-h-screen flex flex-col bg-gray-50">
    <AdminHeader />
    <Header />
    <main class="flex-grow">
      <div class="max-w-7xl mx-auto">
        <NuxtPage></NuxtPage>
      </div>
    </main>
    <Footer />
    
    <!-- Global Alert Component -->
    <Alert />
  </div>
</template>

<script setup>

</script>

<style>

</style>