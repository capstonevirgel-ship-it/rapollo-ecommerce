<script setup lang="ts">
import { onMounted, computed, ref } from "vue";
import { useBrandStore } from "~/stores/brand";
import { getImageUrl } from "~/utils/imageHelper";
import DataTable from "@/components/DataTable.vue";
import Dialog from "@/components/Dialog.vue";
import AdminActionButton from "@/components/AdminActionButton.vue";
import AdminAddButton from "@/components/AdminAddButton.vue";
import type { BrandPayload } from "~/types";

definePageMeta({
  layout: "admin",
});

// Set page title
useHead({
  title: 'Brands Management - Admin - Rapollo E-commerce',
  meta: [
    { name: 'description', content: 'Manage product brands in your Rapollo E-commerce store.' }
  ]
})

const brandStore = useBrandStore();

const columns = [
  { label: "Logo", key: "logo" },
  { label: "Name", key: "name" },
  { label: "Actions", key: "actions" },
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
    await brandStore.fetchBrands();
  } catch (err) {
    console.error("Failed to fetch brands", err);
  }
});

async function handleDelete(id: number) {
  if (confirm("Are you sure you want to delete this brand?")) {
    try {
      await brandStore.deleteBrand(id);
      alert("Brand deleted ✅");
    } catch (err) {
      console.error(err);
      alert("Failed to delete brand ❌");
    }
  }
}

const isDialogOpen = ref(false);
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

const selectedBrand = ref<any | null>(null);
const isBrandLoading = ref(false);

const addBrand = () => {
  newBrand.value = {
    name: "",
    slug: "",
    meta_title: "",
    meta_description: "",
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

  await brandStore.createBrand(payload);
  isDialogOpen.value = false;
  await brandStore.fetchBrands();
};

const handleRowClick = async (row: any) => {
  isBrandLoading.value = true;
  await brandStore.fetchBrand(row.id);
  selectedBrand.value = brandStore.brand;
  isBrandLoading.value = false;
};

const selectedIds = ref<number[]>([]);
</script>

<template>
  <div class="p-4">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold flex items-center gap-2">
        <Icon name="mdi:tag-multiple" /> Brands
      </h1>
      <AdminAddButton text="Add Brand" @click="addBrand" />
    </div>

    <!-- DataTable -->
    <DataTable
      :columns="columns"
      :rows="brands"
      v-model:selected="selectedIds"
      :rows-per-page="10"
      @row-click="handleRowClick"
      class="cursor-pointer"
    >
      <!-- Logo Slot -->
      <template #cell-logo="{ row }">
        <img
          :src="row.logo"
          alt="Logo"
          class="h-10 w-10 object-contain mx-auto"
        />
      </template>

      <!-- Actions Slot -->
      <template #cell-actions="{ row }">
        <div class="flex gap-2 justify-center" @click.stop>
          <AdminActionButton
            icon="mdi:pencil"
            text="Edit"
            variant="primary"
            @click="console.log('Edit brand', row.id)"
          />
          <AdminActionButton
            icon="mdi:delete"
            text="Delete"
            variant="danger"
            @click="handleDelete(row.id)"
          />
        </div>
      </template>
    </DataTable>

    <!-- SEO Preview -->
    <div class="p-4 bg-white shadow border-0 mt-6">
      <h1 class="text-2xl font-bold mb-4">SEO Preview</h1>

      <!-- Skeleton -->
      <div
        v-if="isBrandLoading"
        class="border rounded-lg p-4 bg-white shadow-sm max-w-2xl animate-pulse"
      >
        <div class="h-4 bg-gray-200 rounded w-24 mb-3"></div>
        <div class="h-5 bg-gray-200 rounded w-3/4 mb-2"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
        <div class="h-4 bg-gray-200 rounded w-full"></div>
      </div>

      <!-- Preview content -->
      <div
        v-else-if="selectedBrand"
        class="border rounded-lg p-4 bg-white shadow-sm max-w-2xl"
      >
        <p class="text-sm text-gray-500 mb-2">SEO Preview</p>
        <p class="text-blue-800 text-lg font-medium leading-snug truncate">
          {{ selectedBrand.meta_title || selectedBrand.name }}
        </p>
        <p class="text-green-700 text-sm truncate">
          https://yourdomain.com/brands/{{ selectedBrand.slug }}
        </p>
        <p class="text-gray-700 text-sm mt-1 line-clamp-2">
          {{ selectedBrand.meta_description || "No meta description provided." }}
        </p>
      </div>

      <div v-else class="text-gray-500 italic">
        Select a brand to see the SEO preview.
      </div>
    </div>

    <!-- Dialog -->
    <Dialog v-model="isDialogOpen" title="Add Brand">
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
