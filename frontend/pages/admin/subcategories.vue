<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Subcategories Management - Admin | monogram',
  meta: [
    { name: 'description', content: 'Manage product subcategories in your monogram E-commerce store.' }
  ]
})

import { ref, onMounted, computed } from 'vue'
import DataTable from '@/components/DataTable.vue'
import Dialog from '@/components/Dialog.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import AdminAddButton from '@/components/AdminAddButton.vue'
import Select from '@/components/Select.vue'
import { useSubcategoryStore } from '~/stores/subcategory'
import { useCategoryStore } from '~/stores/category'
import { useAlert } from '@/composables/useAlert'

const subcategoryStore = useSubcategoryStore()
const categoryStore = useCategoryStore()
const { success, error } = useAlert()

const isDialogOpen = ref(false)
const isEditMode = ref(false)
const editingSubcategory = ref<{ id: number; category_id: number; name: string; slug: string; meta_title: string; meta_description: string } | null>(null)

const columns = [
  { label: 'Category', key: 'category_name', width: 20 }, // show category name instead of ID
  { label: 'Name', key: 'name', width: 25 },
  { label: 'Slug', key: 'slug', width: 30 },
  { label: 'Actions', key: 'actions', width: 25 }
]

const selectedIds = ref<number[]>([])
const showDeleteDialog = ref(false)
const deletingSubcategory = ref<{ id: number; name: string; slug: string } | null>(null)
const isDeleting = ref(false)

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
  isEditMode.value = false
  editingSubcategory.value = null
  newSubcategory.value = { category_id: null, name: '', slug: '', meta_title: '', meta_description: '' }
  isDialogOpen.value = true
}

const editSubcategory = (subcategory: { id: number; category_id: number; name: string; slug: string; meta_title: string; meta_description: string }) => {
  isEditMode.value = true
  editingSubcategory.value = subcategory
  newSubcategory.value = {
    category_id: subcategory.category_id,
    name: subcategory.name,
    slug: subcategory.slug,
    meta_title: subcategory.meta_title || '',
    meta_description: subcategory.meta_description || ''
  }
  isDialogOpen.value = true
}

const saveSubcategory = async () => {
  if (!newSubcategory.value.category_id) return

  const payload = {
    ...newSubcategory.value,
    category_id: newSubcategory.value.category_id as number, 
  }

  if (isEditMode.value && editingSubcategory.value) {
    await subcategoryStore.updateSubcategory(editingSubcategory.value.id, payload)
  } else {
    await subcategoryStore.createSubcategory(payload)
  }

  isDialogOpen.value = false
  subcategoryStore.fetchSubcategories()
}

const openDeleteDialog = (subcategory: { id: number; name: string; slug: string }) => {
  deletingSubcategory.value = subcategory
  showDeleteDialog.value = true
}

const closeDeleteDialog = () => {
  showDeleteDialog.value = false
  deletingSubcategory.value = null
}

const handleDelete = async () => {
  if (!deletingSubcategory.value) return

  isDeleting.value = true
  const subcategoryToDelete = { ...deletingSubcategory.value }

  try {
    await subcategoryStore.deleteSubcategory(subcategoryToDelete.slug)
    success('Subcategory Deleted', `"${subcategoryToDelete.name}" has been deleted successfully.`)
    await subcategoryStore.fetchSubcategories()
  } catch (err: any) {
    console.error('Failed to delete subcategory:', err)
    const errorMessage = err.data?.message || err.message || 'Failed to delete subcategory. Please try again.'
    error('Delete Failed', errorMessage)
  } finally {
    isDeleting.value = false
    closeDeleteDialog()
  }
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
      :show-checkboxes="false"
      class="cursor-pointer"
    >
      <!-- Slot for action buttons -->
      <template #cell-actions="{ row }">
        <div class="flex gap-2 justify-center" @click.stop>
          <AdminActionButton
            icon="mdi:pencil"
            text="Edit"
            variant="primary"
            @click="editSubcategory({ id: row.id, category_id: row.category_id, name: row.name, slug: row.slug, meta_title: row.meta_title || '', meta_description: row.meta_description || '' })"
          />
          <AdminActionButton
            icon="mdi:delete"
            text="Delete"
            variant="danger"
            @click="openDeleteDialog({ id: row.id, name: row.name, slug: row.slug })"
          />
        </div>
      </template>
    </DataTable>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model="showDeleteDialog" title="Delete Subcategory">
      <div class="flex flex-col items-center text-center">
        <!-- Warning Icon -->
        <div class="mb-4 flex items-center justify-center w-20 h-20 rounded-full bg-yellow-100">
          <Icon name="mdi:alert" class="text-[3rem] text-yellow-600" />
        </div>

        <!-- Confirmation Message -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          Are you sure you want to delete "{{ deletingSubcategory?.name }}"?
        </h3>
        <p class="text-sm text-gray-600 mb-6">
          This action cannot be undone. The subcategory and all its associated data will be permanently deleted.
        </p>

        <!-- Action Buttons -->
        <div class="flex gap-3 w-full">
          <button
            @click="closeDeleteDialog"
            :disabled="isDeleting"
            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Cancel
          </button>
          <button
            @click="handleDelete"
            :disabled="isDeleting"
            class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
          >
            <span v-if="isDeleting">Deleting...</span>
            <span v-else>Delete</span>
          </button>
        </div>
      </div>
    </Dialog>
  </div>

  <Dialog v-model="isDialogOpen" :title="isEditMode ? 'Edit Subcategory' : 'Add Subcategory'">
    <div class="space-y-4">
      <!-- Category dropdown -->
      <Select
        v-model="newSubcategory.category_id"
        :options="categoryStore.categories.map(cat => ({ value: cat.id, label: cat.name }))"
        placeholder="Select a Category"
      />

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

