<template>
  <div class="py-16 bg-zinc-900 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-3xl font-bold mb-6">{{ title }}</h2>
      <p class="text-xl text-gray-300 mb-8">
        {{ description }}
      </p>
      <NuxtLink 
        :to="link" 
        class="inline-flex items-center px-8 py-3 bg-white text-zinc-900 font-semibold rounded-lg hover:bg-gray-100 transition-colors"
      >
        {{ buttonText }}
        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title?: string
  description?: string
  buttonText?: string
  link?: string
}

withDefaults(defineProps<Props>(), {
  title: 'Ready to Start Shopping?',
  description: 'Discover our amazing collection of products and experience the Rapollo difference',
  buttonText: 'Shop Now',
  link: '/shop'
})
</script>
