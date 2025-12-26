<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Categories Management - Admin | monogram',
  meta: [
    { name: 'description', content: 'Manage product categories in your monogram E-commerce store.' }
  ]
})

import { ref, onMounted } from 'vue'
import DataTable from '@/components/DataTable.vue'
import Dialog from '@/components/Dialog.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import AdminAddButton from '@/components/AdminAddButton.vue'
import { useCategoryStore } from '~/stores/category'
import { useAlert } from '@/composables/useAlert'

const categoryStore = useCategoryStore()
const { success, error } = useAlert()

const isDialogOpen = ref(false)
const isEditMode = ref(false)
const editingCategory = ref<{ id: number; name: string; slug: string } | null>(null)

const columns = [
  { label: 'Name', key: 'name', width: 30 },
  { label: 'Slug', key: 'slug', width: 35 },
  { label: 'Actions', key: 'actions', width: 35 }
]

const selectedIds = ref<number[]>([])
const showDeleteDialog = ref(false)
const deletingCategory = ref<{ id: number; name: string; slug: string } | null>(null)
const isDeleting = ref(false)

const newCategory = ref({
  name: '',
  slug: ''
})

onMounted(() => {
  categoryStore.fetchCategories()
})



const addCategory = () => {
  isEditMode.value = false
  editingCategory.value = null
  newCategory.value = { name: '', slug: '' }
  isDialogOpen.value = true
}

const editCategory = (category: { id: number; name: string; slug: string }) => {
  isEditMode.value = true
  editingCategory.value = category
  newCategory.value = {
    name: category.name,
    slug: category.slug
  }
  isDialogOpen.value = true
}

const saveCategory = async () => {
  if (isEditMode.value && editingCategory.value) {
    await categoryStore.updateCategory(editingCategory.value.id, newCategory.value)
  } else {
    await categoryStore.createCategory(newCategory.value)
  }
  isDialogOpen.value = false
  categoryStore.fetchCategories()
}

const openDeleteDialog = (category: { id: number; name: string; slug: string }) => {
  deletingCategory.value = category
  showDeleteDialog.value = true
}

const closeDeleteDialog = () => {
  showDeleteDialog.value = false
  deletingCategory.value = null
}

const handleDelete = async () => {
  if (!deletingCategory.value) return

  isDeleting.value = true
  const categoryToDelete = { ...deletingCategory.value }

  try {
    await categoryStore.deleteCategory(categoryToDelete.slug)
    success('Category Deleted', `"${categoryToDelete.name}" has been deleted successfully.`)
    await categoryStore.fetchCategories()
  } catch (err: any) {
    console.error('Failed to delete category:', err)
    const errorMessage = err.data?.message || err.message || 'Failed to delete category. Please try again.'
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
            @click="editCategory({ id: row.id, name: row.name, slug: row.slug })"
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
    <Dialog v-model="showDeleteDialog" title="Delete Category">
      <div class="flex flex-col items-center text-center">
        <!-- Warning Icon -->
        <div class="mb-4 flex items-center justify-center w-20 h-20 rounded-full bg-yellow-100">
          <Icon name="mdi:alert" class="text-[3rem] text-yellow-600" />
        </div>

        <!-- Confirmation Message -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          Are you sure you want to delete "{{ deletingCategory?.name }}"?
        </h3>
        <p class="text-sm text-gray-600 mb-6">
          This action cannot be undone. The category and all its associated data will be permanently deleted.
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

  <Dialog v-model="isDialogOpen" :title="isEditMode ? 'Edit Category' : 'Add Category'">
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
