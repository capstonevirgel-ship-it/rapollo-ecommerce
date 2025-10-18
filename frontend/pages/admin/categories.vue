<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Categories Management - Admin - Rapollo E-commerce',
  meta: [
    { name: 'description', content: 'Manage product categories in your Rapollo E-commerce store.' }
  ]
})

import { ref, onMounted } from 'vue'
import DataTable from '@/components/DataTable.vue'
import Dialog from '@/components/Dialog.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import AdminAddButton from '@/components/AdminAddButton.vue'
import { useCategoryStore } from '~/stores/category'
import type { Category } from '~/types'

const categoryStore = useCategoryStore()

const isDialogOpen = ref(false)

const columns = [
  { label: 'Name', key: 'name' },
  { label: 'Slug', key: 'slug' },
  { label: 'Actions', key: 'actions' }
]

const selectedIds = ref<number[]>([])
const selectedCategory = ref<Category | null>(null)

const newCategory = ref({
  name: '',
  slug: '',
  meta_title: '',
  meta_description: ''
})

onMounted(() => {
  categoryStore.fetchCategories()
})

const isCategoryLoading = ref(false)

const handleRowClick = async (row: any) => {
  isCategoryLoading.value = true
  try {
    await categoryStore.fetchCategoryById(row.id)
    selectedCategory.value = categoryStore.category
  } catch (error) {
    console.error('Error fetching category:', error)
  } finally {
    isCategoryLoading.value = false
  }
}

const addCategory = () => {
  newCategory.value = { name: '', slug: '', meta_title: '', meta_description: '' }
  isDialogOpen.value = true
}

const saveCategory = async () => {
  await categoryStore.createCategory(newCategory.value)
  isDialogOpen.value = false
  categoryStore.fetchCategories()
}

</script>

<template>
  <div class="p-4 mb-4 bg-white shadow border-0">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Categories</h1>
      <AdminAddButton text="Add Category" @click="addCategory" />
    </div>

    <DataTable
      :columns="columns"
      :rows="categoryStore.categories"
      v-model:selected="selectedIds"
      :rows-per-page="5"
      @row-click="handleRowClick"
      class="cursor-pointer"
    >
      <!-- Slot for action buttons -->
      <template #cell-actions="{ row }">
        <div class="flex gap-2 justify-center" @click.stop>
          <AdminActionButton
            icon="mdi:pencil"
            text="Edit"
            variant="primary"
            @click="console.log('Edit category', row.id)"
          />
          <AdminActionButton
            icon="mdi:delete"
            text="Delete"
            variant="danger"
            @click="console.log('Delete category', row.id)"
          />
        </div>
      </template>
    </DataTable>
  </div>
  
<div class="p-4 bg-white shadow border-0">
  <h1 class="text-2xl font-bold mb-4">SEO Preview</h1>

  <!-- Skeleton loader -->
  <div
    v-if="isCategoryLoading"
    class="border rounded-lg p-4 bg-white shadow-sm max-w-2xl animate-pulse"
  >
    <div class="h-4 bg-gray-200 rounded w-24 mb-3"></div>
    <div class="h-5 bg-gray-200 rounded w-3/4 mb-2"></div>
    <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
    <div class="h-4 bg-gray-200 rounded w-full"></div>
  </div>

  <!-- Preview content -->
  <div
    v-else-if="selectedCategory"
    class="border rounded-lg p-4 bg-white shadow-sm max-w-2xl"
  >
    <p class="text-sm text-gray-500 mb-2">SEO Preview</p>
    <p class="text-blue-800 text-lg font-medium leading-snug truncate">
      {{ selectedCategory.meta_title || selectedCategory.name }}
    </p>
    <p class="text-green-700 text-sm truncate">
      https://yourdomain.com/shop/{{ selectedCategory.slug }}
    </p>
    <p class="text-gray-700 text-sm mt-1 line-clamp-2">
      {{ selectedCategory.meta_description || 'No meta description provided.' }}
    </p>
  </div>

  <!-- Empty state -->
  <div v-else class="text-gray-500 italic">
    Select a category to see the SEO preview.
  </div>
</div>

  <Dialog v-model="isDialogOpen" title="Add Category">
    <div class="space-y-4">
      <input v-model="newCategory.name" type="text" placeholder="Name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900" />
      <input v-model="newCategory.slug" type="text" placeholder="Slug" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900" />
      <input v-model="newCategory.meta_title" type="text" placeholder="Meta Title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900" />
      <textarea v-model="newCategory.meta_description" placeholder="Meta Description" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"></textarea>
    </div>
    <div class="mt-6 flex justify-end space-x-2">
      <button @click="isDialogOpen = false" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">Cancel</button>
      <button @click="saveCategory" class="bg-zinc-900 hover:bg-zinc-800 text-white px-4 py-2 rounded">Save</button>
    </div>
  </Dialog>
</template>
