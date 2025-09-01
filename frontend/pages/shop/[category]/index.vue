<script setup lang="ts">
import Breadcrumbs from '@/components/navigation/Breadcrumbs.vue'
import { useRoute } from 'vue-router'
import { useShopCategories } from '@/composables/useShopCategories'

const route = useRoute()
const categorySlug = route.params.category as string
const categories = useShopCategories()

const category = categories.find(cat => cat.slug === categorySlug)
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <Breadcrumbs />
    <div class="mb-12">
      <h1 class="text-4xl font-bold text-gray-900">{{ category?.name }}</h1>
      <p class="mt-3 text-lg text-gray-600">Explore our {{ category?.name.toLowerCase() }}</p>
    </div>

    <div v-if="category" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <NuxtLink
        v-for="sub in category.subcategories"
        :key="sub.slug"
        :to="`/shop/${category.slug}/${sub.slug}`"
        class="group relative block p-8 border border-gray-200 rounded-lg bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)]"
      >
        <div class="flex flex-col items-start">
          <h2 class="text-xl font-semibold text-gray-900 group-hover:text-black transition-colors relative pb-1">
            <span class="relative z-10">{{ sub.name }}</span>
            <span class="absolute bottom-0 left-0 h-0.5 w-0 bg-black group-hover:w-full transition-all duration-300 ease-out"></span>
          </h2>
          <div class="mt-6 w-full flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500 group-hover:text-gray-700 transition-colors">
              View products
            </span>
            <Icon 
              name="mdi:arrow-right" 
              class="h-5 w-5 text-gray-400 group-hover:text-black group-hover:translate-x-1 transition-all duration-300" 
            />
          </div>
        </div>
      </NuxtLink>
    </div>

    <p v-else class="text-red-500 text-center py-20">Category not found.</p>
  </div>
</template>