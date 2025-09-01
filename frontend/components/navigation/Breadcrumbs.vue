<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useShopCategories } from '@/composables/useShopCategories'

const route = useRoute()
const categories = useShopCategories()

const breadcrumbs = computed(() => {
  const crumbs: Array<{ name: string; path: string }> = []
  
  // Always add Home
  crumbs.push({ name: 'Home', path: '/' })
  
  // Add Shop if we're in the shop hierarchy
  if (route.path.startsWith('/shop')) {
    crumbs.push({ name: 'Shop', path: '/shop' })
  }

  // Handle category level
  if (route.params.category) {
    const category = categories.find(c => c.slug === route.params.category)
    if (category) {
      crumbs.push({
        name: category.name,
        path: `/shop/${category.slug}`
      })
    }
  }

  // Handle subcategory level
  if (route.params.sub_category) {
    const category = categories.find(c => c.slug === route.params.category)
    if (category) {
      const subcategory = category.subcategories.find(sc => sc.slug === route.params.sub_category)
      if (subcategory) {
        crumbs.push({
          name: subcategory.name,
          path: `/shop/${category.slug}/${subcategory.slug}`
        })
      }
    }
  }

  // Handle product level
  if (route.params.product) {
    const category = categories.find(c => c.slug === route.params.category)
    if (category) {
      const subcategory = category.subcategories.find(sc => sc.slug === route.params.sub_category)
      if (subcategory) {
        const product = subcategory.products.find(p => p.slug === route.params.product)
        if (product) {
          crumbs.push({
            name: product.name,
            path: route.path // Current path
          })
        }
      }
    }
  }

  return crumbs
})
</script>

<template>
  <nav class="mb-6" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-x-1 gap-y-2 text-base">
      <li v-for="(crumb, index) in breadcrumbs" :key="index" class="inline-flex items-center">
        <template v-if="index > 0">
          <Icon name="mdi:chevron-right" class="mx-1 text-gray-400" />
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
