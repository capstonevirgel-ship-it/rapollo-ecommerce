<script setup lang="ts">
import Breadcrumbs from '@/components/navigation/Breadcrumbs.vue'
import { useRoute } from 'vue-router'
import { useShopCategories } from '@/composables/useShopCategories'

const route = useRoute()
const categorySlug = route.params.category as string
const subcategorySlug = route.params['sub_category'] as string

const categories = useShopCategories()
const category = categories.find(c => c.slug === categorySlug)
const subcategory = category?.subcategories.find(sc => sc.slug === subcategorySlug)
</script>

<template>
  <div class="max-w-6xl mx-auto p-6">
    <Breadcrumbs />
    <h1 class="text-3xl font-bold mb-6">{{ subcategory?.name }}</h1>

    <div v-if="subcategory" class="grid grid-cols-2 md:grid-cols-3 gap-6">
      <NuxtLink
        v-for="product in subcategory.products"
        :key="product.slug"
        :to="`/shop/${categorySlug}/${subcategorySlug}/${product.slug}`"
        class="bg-white rounded overflow-hidden shadow hover:shadow-md transition"
      >
        <img :src="product.image" alt="" class="w-full h-48 object-cover" />
        <div class="p-4">
          <h2 class="text-lg font-semibold">{{ product.name }}</h2>
          <p class="text-primary-600 font-medium">${{ product.price.toFixed(2) }}</p>
        </div>
      </NuxtLink>
    </div>

    <p v-else class="text-red-500">Subcategory not found.</p>
  </div>
</template>