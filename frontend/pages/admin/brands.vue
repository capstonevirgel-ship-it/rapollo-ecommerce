<script setup lang="ts">
import { onMounted, computed, ref } from "vue";
import { useBrandStore } from "~/stores/brand";
import { getImageUrl } from "~/utils/imageHelper";
import DataTable from "@/components/DataTable.vue";
import Dialog from "@/components/Dialog.vue";
import AdminActionButton from "@/components/AdminActionButton.vue";
import AdminAddButton from "@/components/AdminAddButton.vue";
import { useAlert } from '@/composables/useAlert'
import type { BrandPayload } from "~/types";

definePageMeta({
  layout: "admin",
});

// Set page title
useHead({
  title: 'Brands Management - Admin | monogram',
  meta: [
    { name: 'description', content: 'Manage product brands in your monogram E-commerce store.' }
  ]
})

const brandStore = useBrandStore();
const { success, error } = useAlert();

const columns = [
  { label: "Logo", key: "logo", width: 30 },
  { label: "Name", key: "name", width: 35 },
  { label: "Actions", key: "actions", width: 35 },
];

const brands = computed(() =>
  brandStore.brands.map((b) => ({
    id: b.id,
    name: b.name,
    logo: getImageUrl(b.logo_url ?? null),
    meta_title: b.meta_title,
    meta_description: b.meta_description,
    slug: b.slug,
  }))
);

onMounted(async () => {
  try {
    await (brandStore as any).fetchBrands();
  } catch (err) {
    console.error("Failed to fetch brands", err);
  }
});

const openDeleteDialog = (brand: { id: number; name: string }) => {
  deletingBrand.value = brand;
  showDeleteDialog.value = true;
};

const closeDeleteDialog = () => {
  showDeleteDialog.value = false;
  deletingBrand.value = null;
};

async function handleDelete() {
  if (!deletingBrand.value) return;

  isDeleting.value = true;
  const brandToDelete = { ...deletingBrand.value };

  try {
    await (brandStore as any).deleteBrand(brandToDelete.id);
    success('Brand Deleted', `"${brandToDelete.name}" has been deleted successfully.`);
    await (brandStore as any).fetchBrands();
  } catch (err: any) {
    console.error('Failed to delete brand:', err);
    const errorMessage = err.data?.message || err.message || 'Failed to delete brand. Please try again.';
    error('Delete Failed', errorMessage);
  } finally {
    isDeleting.value = false;
    closeDeleteDialog();
  }
}

const isDialogOpen = ref(false);
const isEditMode = ref(false);
const editingBrand = ref<{ id: number; name: string; slug: string; meta_title: string; meta_description: string } | null>(null);
const newBrand = ref({
  name: "",
  slug: "",
  meta_title: "",
  meta_description: "",
  logo: null as File | null, 
});

function handleBrandLogo(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target?.files?.[0]) {
    newBrand.value.logo = target.files[0] as File;
  } else {
    newBrand.value.logo = null;
  }
}


const addBrand = () => {
  isEditMode.value = false;
  editingBrand.value = null;
  newBrand.value = {
    name: "",
    slug: "",
    meta_title: "",
    meta_description: "",
    logo: null,
  };
  isDialogOpen.value = true;
};

const editBrand = (brand: { id: number; name: string; slug: string; meta_title: string; meta_description: string }) => {
  isEditMode.value = true;
  editingBrand.value = brand;
  newBrand.value = {
    name: brand.name,
    slug: brand.slug,
    meta_title: brand.meta_title || "",
    meta_description: brand.meta_description || "",
    logo: null,
  };
  isDialogOpen.value = true;
};

const saveBrand = async () => {
  const payload: BrandPayload & { logo?: File } = {
    name: newBrand.value.name,
    slug: newBrand.value.slug,
    meta_title: newBrand.value.meta_title,
    meta_description: newBrand.value.meta_description,
    logo: newBrand.value.logo || undefined, // null → undefined
  };

  if (isEditMode.value && editingBrand.value) {
    await (brandStore as any).updateBrand(editingBrand.value.id, payload);
  } else {
    await (brandStore as any).createBrand(payload);
  }
  isDialogOpen.value = false;
  await (brandStore as any).fetchBrands();
};


const selectedIds = ref<number[]>([]);
const showDeleteDialog = ref(false);
const deletingBrand = ref<{ id: number; name: string } | null>(null);
const isDeleting = ref(false);
</script>

<template>
  <div class="space-y-8 sm:space-y-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Brands</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Manage product brands and manufacturers</p>
      </div>
      <AdminAddButton text="Add Brand" @click="addBrand" />
    </div>

    <!-- DataTable -->
    <DataTable
      :columns="columns"
      :rows="brands"
      v-model:selected="selectedIds"
      :rows-per-page="10"
      :show-checkboxes="false"
      class="cursor-pointer"
    >
      <!-- Logo Slot -->
      <template #cell-logo="{ row }">
        <img
          :src="row.logo"
          alt="Logo"
          class="h-10 w-10 object-contain"
        />
      </template>

      <!-- Actions Slot -->
      <template #cell-actions="{ row }">
        <div class="flex gap-2 justify-center" @click.stop>
          <AdminActionButton
            icon="mdi:pencil"
            text="Edit"
            variant="primary"
            @click="editBrand({ id: row.id, name: row.name, slug: row.slug, meta_title: row.meta_title || '', meta_description: row.meta_description || '' })"
          />
          <AdminActionButton
            icon="mdi:delete"
            text="Delete"
            variant="danger"
            @click="openDeleteDialog({ id: row.id, name: row.name })"
          />
        </div>
      </template>
    </DataTable>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model="showDeleteDialog" title="Delete Brand">
      <div class="flex flex-col items-center text-center">
        <!-- Warning Icon -->
        <div class="mb-4 flex items-center justify-center w-20 h-20 rounded-full bg-yellow-100">
          <Icon name="mdi:alert" class="text-[3rem] text-yellow-600" />
        </div>

        <!-- Confirmation Message -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          Are you sure you want to delete "{{ deletingBrand?.name }}"?
        </h3>
        <p class="text-sm text-gray-600 mb-6">
          This action cannot be undone. The brand and all its associated data will be permanently deleted.
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

    <!-- Dialog -->
    <Dialog v-model="isDialogOpen" :title="isEditMode ? 'Edit Brand' : 'Add Brand'">
      <div class="space-y-4">
        <input
          v-model="newBrand.name"
          type="text"
          placeholder="Name"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
        />
        <input
          v-model="newBrand.slug"
          type="text"
          placeholder="Slug"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
        />
        <input
          v-model="newBrand.meta_title"
          type="text"
          placeholder="Meta Title"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
        />
        <textarea
          v-model="newBrand.meta_description"
          placeholder="Meta Description"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
        ></textarea>
        <input type="file" @change="handleBrandLogo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900" />
      </div>

      <div class="mt-6 flex justify-end space-x-2">
        <button
          @click="isDialogOpen = false"
          class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded"
        >
          Cancel
        </button>
        <button
          @click="saveBrand"
          class="bg-zinc-900 hover:bg-zinc-800 text-white px-4 py-2 rounded"
        >
          Save
        </button>
      </div>
    </Dialog>
  </div>
</template>
