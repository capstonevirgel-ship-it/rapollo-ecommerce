<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useProductStore } from "~/stores/product";
import { useBrandStore } from "~/stores/brand";
import { useSubcategoryStore } from "~/stores/subcategory";
import { useAlert } from "~/composables/useAlert";

definePageMeta({
  layout: 'admin'
})

const productStore = useProductStore();
const brandStore = useBrandStore();
const subcategoryStore = useSubcategoryStore();
const { success, error, info } = useAlert();

// Product state
const name = ref("");
const brandId = ref<number | string | null>(null);
const subcategoryId = ref<number | null>(null);
const description = ref("");
const metaTitle = ref("");
const metaDescription = ref("");
const images = ref<File[]>([]);

// Inline brand
const newBrandMode = ref(false);
const newBrandName = ref("");

// Variants
type Variant = {
  color_name: string;
  color_hex: string;
  size_name: string;
  price: number;
  stock: number;
  sku: string;
  images: File[];
};

const variants = ref<Variant[]>([
  {
    color_name: "Black",
    color_hex: "#000000",
    size_name: "M",
    price: 0,
    stock: 0,
    sku: "",
    images: [],
  },
]);

// Example sizes/colors (frontend only for picker)
const availableColors = ref([
  { name: "Black", hex: "#000000" },
  { name: "White", hex: "#ffffff" },
]);

const availableSizes = ["S", "M", "L", "XL"];

// Load brands/subcategories on mount
onMounted(async () => {
  await brandStore.fetchBrands();
  await subcategoryStore.fetchSubcategories();
});

function handleProductImages(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files) images.value = Array.from(target.files);
}

function handleVariantImages(e: Event, index: number) {
  const target = e.target as HTMLInputElement;
  if (target.files) variants.value[index].images = Array.from(target.files);
}

function addVariant() {
  variants.value.push({
    color_name: "White",
    color_hex: "#ffffff",
    size_name: "S",
    price: 0,
    stock: 0,
    sku: "",
    images: [],
  });
}

async function submitProduct() {
  try {
    let finalBrandId = brandId.value as number | null;

    if (newBrandMode.value && newBrandName.value.trim()) {
      const created = await brandStore.createBrand({ name: newBrandName.value });
      finalBrandId = created.id;
    }

    await productStore.createProduct({
      subcategory_id: subcategoryId.value || undefined,
      brand_id: finalBrandId || undefined,
      name: name.value,
      description: description.value,
      meta_title: metaTitle.value,
      meta_description: metaDescription.value,
      images: images.value,
      variants: variants.value.map((v) => ({
        color_name: v.color_name,
        color_hex: v.color_hex,
        size_name: v.size_name,
        price: v.price,
        stock: v.stock,
        sku: v.sku,
        images: v.images,
      })),
    });

    success("Product Created", "Product has been created successfully!");

    // Reset
    name.value = "";
    brandId.value = null;
    subcategoryId.value = null;
    description.value = "";
    metaTitle.value = "";
    metaDescription.value = "";
    images.value = [];
    variants.value = [
      { color_name: "Black", color_hex: "#000000", size_name: "M", price: 0, stock: 0, sku: "", images: [] },
    ];
    newBrandMode.value = false;
    newBrandName.value = "";
  } catch (err) {
    console.error(err);
    error("Failed to Create Product", "Unable to create product. Please check all fields and try again.");
  }
}

</script>

<template>
  <div class="p-4 bg-white shadow border-0">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold flex items-center gap-2">
        <Icon name="mdi:plus-box" /> Add Product
      </h1>
      <button
        @click="submitProduct"
        class="bg-zinc-900 hover:bg-zinc-800 text-white px-4 py-2 rounded flex items-center gap-2"
      >
        <Icon name="mdi:content-save" /> Save Product
      </button>
    </div>

    <!-- Product Basic Info -->
    <div class="space-y-4 mb-6">
      <input v-model="name" type="text" placeholder="Product Name" class="w-full border p-2 rounded"/>
      <textarea v-model="description" placeholder="Description" rows="4" class="w-full border p-2 rounded"/>
      <input v-model="metaTitle" type="text" placeholder="Meta Title" class="w-full border p-2 rounded"/>
      <input v-model="metaDescription" type="text" placeholder="Meta Description" class="w-full border p-2 rounded"/>

      <!-- Subcategory -->
      <div>
        <label class="font-semibold">Subcategory</label>
        <select v-model="subcategoryId" class="w-full border p-2 rounded mt-2">
          <option disabled value="">-- Select Subcategory --</option>
          <option v-for="sub in subcategoryStore.subcategories" :key="sub.id" :value="sub.id">
            {{ sub.name }}
          </option>
        </select>
      </div>

      <!-- Brand Dropdown + Inline Add -->
      <div class="mt-4">
        <label class="font-semibold">Brand</label>
        <div v-if="!newBrandMode" class="mt-2">
          <select v-model="brandId" class="w-full border p-2 rounded">
            <option disabled value="">-- Select Brand --</option>
            <option v-for="b in brandStore.brands" :key="b.id" :value="b.id">{{ b.name }}</option>
            <option value="__new">+ Add new brand</option>
          </select>
          <div v-if="brandId === '__new'" class="mt-2">
            <button type="button" @click="newBrandMode = true; brandId = null;"
                    class="px-3 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded">Create New Brand</button>
          </div>
        </div>
        <div v-else class="flex gap-2 mt-2">
          <input v-model="newBrandName" type="text" placeholder="Enter new brand name" class="border p-2 rounded flex-1"/>
          <button type="button" @click="newBrandMode = false; newBrandName='';"
                  class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
        </div>
      </div>

      <!-- Product Images -->
      <div class="mt-4">
        <label class="font-semibold">Product Images</label>
        <input type="file" multiple @change="handleProductImages" class="block mt-2"/>
      </div>
    </div>

    <!-- Variants -->
    <div class="mb-6">
      <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
        <Icon name="mdi:palette" /> Variants
      </h2>

      <div v-for="(variant, index) in variants" :key="index" class="p-4 border rounded-lg mb-4 space-y-3">
        <!-- Color -->
        <div class="flex items-center gap-3">
          <label class="font-medium">Color:</label>
          <select v-model="variant.color_name" class="border rounded p-2">
            <option v-for="c in availableColors" :key="c.name" :value="c.name">{{ c.name }}</option>
          </select>
          <input type="color" v-model="variant.color_hex" class="w-10 h-10 border rounded"/>
        </div>

        <!-- Size -->
        <div class="flex items-center gap-3">
          <label class="font-medium">Size:</label>
          <select v-model="variant.size_name" class="border rounded p-2">
            <option v-for="s in availableSizes" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>

        <!-- Price / Stock / SKU -->
        <div class="grid grid-cols-3 gap-3">
          <input v-model="variant.price" type="number" step="0.01" placeholder="Price" class="border rounded p-2"/>
          <input v-model="variant.stock" type="number" placeholder="Stock" class="border rounded p-2"/>
          <input v-model="variant.sku" type="text" placeholder="SKU" class="border rounded p-2"/>
        </div>

        <!-- Variant Images -->
        <div>
          <label class="font-semibold">Variant Images</label>
          <input type="file" multiple @change="(e) => handleVariantImages(e, index)" class="block mt-2"/>
        </div>
      </div>

      <button @click="addVariant" class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
        <Icon name="mdi:plus" /> Add Variant
      </button>
    </div>
  </div>
</template>

