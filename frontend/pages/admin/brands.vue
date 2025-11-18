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
  title: 'Brands Management - Admin | RAPOLLO',
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


const selectedIds = ref<number[]>([]);
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
