<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

import { ref, onMounted, computed } from 'vue'
import DataTable from '@/components/DataTable.vue'
import Dialog from '@/components/Dialog.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import AdminAddButton from '@/components/AdminAddButton.vue'
import { useSubcategoryStore } from '~/stores/subcategory'
import { useCategoryStore } from '~/stores/category'
import type { Subcategory } from '~/types'

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
const selectedSubcategory = ref<Subcategory | null>(null)

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

onMounted(() => {
  categoryStore.fetchCategories()
  subcategoryStore.fetchSubcategories()
})

const isSubcategoryLoading = ref(false)

const handleRowClick = async (row: any) => {
  isSubcategoryLoading.value = true
  try {
    await subcategoryStore.fetchSubcategoryById(row.id)
    selectedSubcategory.value = subcategoryStore.subcategory
  } catch (error) {
    console.error('Error fetching subcategory:', error)
  } finally {
    isSubcategoryLoading.value = false
  }
}

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
  <div class="p-4 mb-4 bg-white shadow border-0">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Subcategories</h1>
      <AdminAddButton text="Add Subcategory" @click="addSubcategory" />
    </div>

    <DataTable
      :columns="columns"
      :rows="subcategoryRows"
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
  
  <div class="p-4 bg-white shadow border-0">
    <h1 class="text-2xl font-bold mb-4">SEO Preview</h1>

    <!-- Skeleton loader -->
    <div
      v-if="isSubcategoryLoading"
      class="border rounded-lg p-4 bg-white shadow-sm max-w-2xl animate-pulse"
    >
      <div class="h-4 bg-gray-200 rounded w-24 mb-3"></div>
      <div class="h-5 bg-gray-200 rounded w-3/4 mb-2"></div>
      <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
      <div class="h-4 bg-gray-200 rounded w-full"></div>
    </div>

  <!-- Preview content -->
  <div
    v-else-if="selectedSubcategory"
    class="border rounded-lg p-4 bg-white shadow-sm max-w-2xl"
  >
    <p class="text-sm text-gray-500 mb-2">SEO Preview</p>
    <p class="text-blue-800 text-lg font-medium leading-snug truncate">
      {{ selectedSubcategory.meta_title || selectedSubcategory.name }}
    </p>
    <p class="text-green-700 text-sm truncate">
      https://yourdomain.com/shop/{{ selectedSubcategory.slug }}
    </p>
    <p class="text-gray-700 text-sm mt-1 line-clamp-2">
      {{ selectedSubcategory.meta_description || 'No meta description provided.' }}
    </p>
  </div>

    <!-- Empty state -->
    <div v-else class="text-gray-500 italic">
      Select a subcategory to see the SEO preview.
    </div>
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

