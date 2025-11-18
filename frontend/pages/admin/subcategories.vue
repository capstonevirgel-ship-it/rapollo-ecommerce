<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Subcategories Management - Admin | RAPOLLO',
  meta: [
    { name: 'description', content: 'Manage product subcategories in your Rapollo E-commerce store.' }
  ]
})

import { ref, onMounted, computed } from 'vue'
import DataTable from '@/components/DataTable.vue'
import Dialog from '@/components/Dialog.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import AdminAddButton from '@/components/AdminAddButton.vue'
import { useSubcategoryStore } from '~/stores/subcategory'
import { useCategoryStore } from '~/stores/category'

const subcategoryStore = useSubcategoryStore()
const categoryStore = useCategoryStore()

const isDialogOpen = ref(false)

const columns = [
  { label: 'Category', key: 'category_name' }, // show category name instead of ID
  { label: 'Name', key: 'name' },
  { label: 'Slug', key: 'slug' },
  { label: 'Actions', key: 'actions' }
]

const selectedIds = ref<number[]>([])

const newSubcategory = ref({
  category_id: null as number | null,
  name: '',
  slug: '',
  meta_title: '',
  meta_description: ''
})

// computed rows with category_name for display
const subcategoryRows = computed(() =>
  subcategoryStore.subcategories.map((sub) => {
    const category = categoryStore.categories.find((c) => c.id === sub.category_id)
    return {
      ...sub,
      category_name: category ? category.name : '—'
    }
  })
)

onMounted(async () => {
  // Load categories first so category_name can be computed
  await categoryStore.fetchCategories()
  await subcategoryStore.fetchSubcategories()
})



const addSubcategory = () => {
  newSubcategory.value = { category_id: null, name: '', slug: '', meta_title: '', meta_description: '' }
  isDialogOpen.value = true
}

const saveSubcategory = async () => {
  if (!newSubcategory.value.category_id) return

  await subcategoryStore.createSubcategory({
    ...newSubcategory.value,
    category_id: newSubcategory.value.category_id as number, 
  })

  isDialogOpen.value = false
  subcategoryStore.fetchSubcategories()
}
</script>

<template>
  <div class="space-y-8 sm:space-y-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Subcategories</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Organize products into subcategories within categories</p>
      </div>
      <AdminAddButton text="Add Subcategory" @click="addSubcategory" />
    </div>

    <DataTable
      :columns="columns"
      :rows="subcategoryRows"
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
            @click="console.log('Edit subcategory', row.id)"
          />
          <AdminActionButton
            icon="mdi:delete"
            text="Delete"
            variant="danger"
            @click="console.log('Delete subcategory', row.id)"
          />
        </div>
      </template>
    </DataTable>
  </div>

  <Dialog v-model="isDialogOpen" title="Add Subcategory">
    <div class="space-y-4">
      <!-- Category dropdown -->
      <select v-model="newSubcategory.category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900">
        <option disabled value="">Select a Category</option>
        <option v-for="cat in categoryStore.categories" :key="cat.id" :value="cat.id">
          {{ cat.name }}
        </option>
      </select>

      <input v-model="newSubcategory.name" type="text" placeholder="Name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900" />
      <input v-model="newSubcategory.slug" type="text" placeholder="Slug" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900" />
      <input v-model="newSubcategory.meta_title" type="text" placeholder="Meta Title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900" />
      <textarea v-model="newSubcategory.meta_description" placeholder="Meta Description" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"></textarea>
    </div>
    <div class="mt-6 flex justify-end space-x-2">
      <button @click="isDialogOpen = false" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">Cancel</button>
      <button @click="saveSubcategory" class="bg-zinc-900 hover:bg-zinc-800 text-white px-4 py-2 rounded">Save</button>
    </div>
  </Dialog>
</template>

