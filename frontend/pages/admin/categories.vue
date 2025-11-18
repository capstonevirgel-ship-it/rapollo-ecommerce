<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Categories Management - Admin | RAPOLLO',
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

const categoryStore = useCategoryStore()

const isDialogOpen = ref(false)

const columns = [
  { label: 'Name', key: 'name' },
  { label: 'Slug', key: 'slug' },
  { label: 'Actions', key: 'actions' }
]

const selectedIds = ref<number[]>([])

const newCategory = ref({
  name: '',
  slug: ''
})

onMounted(() => {
  categoryStore.fetchCategories()
})



const addCategory = () => {
  newCategory.value = { name: '', slug: '' }
  isDialogOpen.value = true
}

const saveCategory = async () => {
  await categoryStore.createCategory(newCategory.value)
  isDialogOpen.value = false
  categoryStore.fetchCategories()
}

</script>

<template>
  <div class="space-y-8 sm:space-y-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Categories</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Organize products by categories</p>
      </div>
      <AdminAddButton text="Add Category" @click="addCategory" />
    </div>

    <DataTable
      :columns="columns"
      :rows="categoryStore.categories"
      v-model:selected="selectedIds"
      :rows-per-page="5"
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

  <Dialog v-model="isDialogOpen" title="Add Category">
    <div class="space-y-4">
      <input v-model="newCategory.name" type="text" placeholder="Name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900" />
      <input v-model="newCategory.slug" type="text" placeholder="Slug" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900" />
    </div>
    <div class="mt-6 flex justify-end space-x-2">
      <button @click="isDialogOpen = false" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">Cancel</button>
      <button @click="saveCategory" class="bg-zinc-900 hover:bg-zinc-800 text-white px-4 py-2 rounded">Save</button>
    </div>
  </Dialog>
</template>
