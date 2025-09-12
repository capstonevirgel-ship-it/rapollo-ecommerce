<script setup lang="ts">
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useCategoryStore } from '~/stores/category'
import { useSubcategoryStore } from '~/stores/subcategory'
import { useProductStore } from '~/stores/product'

const route = useRoute()

// Stores
const categoryStore = useCategoryStore()
const subcategoryStore = useSubcategoryStore()
const productStore = useProductStore()

const { category } = storeToRefs(categoryStore)
const { subcategory } = storeToRefs(subcategoryStore)
const { product } = storeToRefs(productStore)

// Watch for route changes and fetch data if needed
watch(() => route.params, async (newParams) => {
  // Fetch category if we're on a category/subcategory/product page and don't have it
  if (newParams.category && !category.value) {
    try {
      await categoryStore.fetchCategoryBySlug(newParams.category as string)
    } catch (error) {
      console.error('Failed to fetch category for breadcrumbs:', error)
    }
  }

  // Fetch subcategory if we're on a subcategory/product page and don't have it
  if (newParams.sub_category && !subcategory.value) {
    try {
      await subcategoryStore.fetchSubcategoryBySlug(newParams.sub_category as string)
    } catch (error) {
      console.error('Failed to fetch subcategory for breadcrumbs:', error)
    }
  }
}, { immediate: true })

const breadcrumbs = computed(() => {
  const crumbs: Array<{ name: string; path: string }> = []
  
  // Always add Home
  crumbs.push({ name: 'Home', path: '/' })
  
  // Add Shop if we're in the shop hierarchy
  if (route.path.startsWith('/shop')) {
    crumbs.push({ name: 'Shop', path: '/shop' })
  }

  // Handle category level
  if (route.params.category && category.value) {
    crumbs.push({
      name: category.value.name,
      path: `/shop/${category.value.slug}`
    })
  }

  // Handle subcategory level
  if (route.params.sub_category && subcategory.value) {
    crumbs.push({
      name: subcategory.value.name,
      path: `/shop/${route.params.category}/${subcategory.value.slug}`
    })
  }

  // Handle product level
  if (route.params.product && product.value) {
    crumbs.push({
      name: product.value.name,
      path: route.path // Current path
    })
  }

  return crumbs
})
</script>

<template>
  <nav class="mb-6" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-x-1 gap-y-2 text-base">
      <li v-for="(crumb, index) in breadcrumbs" :key="index" class="inline-flex items-center">
        <template v-if="index > 0">
          <svg 
            class="mx-1 h-4 w-4 text-gray-400" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </template>
        <NuxtLink
          :to="crumb.path"
          class="inline-flex items-center font-medium"
          :class="{
            'text-gray-700 hover:text-primary-600': index < breadcrumbs.length - 1,
            'text-primary-600': index === breadcrumbs.length - 1
          }"
        >
          {{ crumb.name }}
        </NuxtLink>
      </li>
    </ol>
  </nav>
</template>
