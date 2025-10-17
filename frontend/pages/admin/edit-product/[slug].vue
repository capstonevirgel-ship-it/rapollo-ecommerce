<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useProductStore } from "~/stores/product";
import { useBrandStore } from "~/stores/brand";
import { useSubcategoryStore } from "~/stores/subcategory";
import { useSizeStore } from "~/stores/size";
import { useAlert } from "~/composables/useAlert";
import { useRoute, useRouter } from "vue-router";

definePageMeta({
  layout: 'admin'
})

const route = useRoute()
const router = useRouter()
const productSlug = route.params.slug as string

const productStore = useProductStore();
const brandStore = useBrandStore();
const subcategoryStore = useSubcategoryStore();
const sizeStore = useSizeStore();
const { success, error } = useAlert();

// Loading state
const loading = ref(true)

// Accordion state
const activeAccordion = ref('basic');

// Product state
const name = ref("");
const brandId = ref<number | string | null>(null);
const subcategoryId = ref<number | null>(null);
const description = ref("");
const images = ref<File[]>([]);
const existingImages = ref<any[]>([]);

// Size selection
const selectedSizes = ref<number[]>([]);

// SEO state
const seoTitle = ref("");
const seoDescription = ref("");
const seoKeywords = ref("");
const seoCanonicalUrl = ref("");
const seoRobots = ref("index,follow");

// Variants
type Variant = {
  id?: number;
  color_name: string;
  color_hex: string;
  size_id: number | null;
  price: number;
  stock: number;
  sku: string;
  images: File[];
  existing_images?: any[];
  available_sizes: number[];
  size_stocks: { [sizeId: number]: number };
};

const variants = ref<Variant[]>([]);

const canSubmit = computed(() => {
  return name.value.trim() !== '' && subcategoryId.value !== null && variants.value.length > 0;
});

// Load product data
onMounted(async () => {
  try {
    loading.value = true
    
    // Load dropdowns
    await Promise.all([
      brandStore.fetchBrands(),
      subcategoryStore.fetchSubcategories(),
      sizeStore.fetchSizes()
    ])
    
    // Fetch product
    const product = await productStore.fetchProduct(productSlug)
    
    // Populate form
    name.value = product.name
    brandId.value = product.brand_id
    subcategoryId.value = product.subcategory_id
    description.value = product.description || ''
    seoTitle.value = product.meta_title || ''
    seoDescription.value = product.meta_description || ''
    seoKeywords.value = product.meta_keywords || ''
    seoCanonicalUrl.value = product.canonical_url || ''
    seoRobots.value = product.robots || 'index,follow'
    
    // Existing images
    existingImages.value = product.images || []
    
    // Sizes
    if (product.sizes && product.sizes.length > 0) {
      selectedSizes.value = product.sizes.map((s: any) => s.id)
    }
    
    // Group variants by color
    const variantsByColor = new Map<string, any[]>();
    if (product.variants && product.variants.length > 0) {
      product.variants.forEach((v: any) => {
        const colorKey = v.color?.hex_code || v.color?.name || 'default';
        if (!variantsByColor.has(colorKey)) {
          variantsByColor.set(colorKey, []);
        }
        variantsByColor.get(colorKey)!.push(v);
      });
      
      // Create variant objects from groups
      variants.value = Array.from(variantsByColor.values()).map(group => {
        const first = group[0];
        const available_sizes = group.map(v => v.size_id).filter(id => id !== null);
        const size_stocks: { [key: number]: number } = {};
        
        group.forEach(v => {
          if (v.size_id) {
            size_stocks[v.size_id] = v.stock;
          }
        });
        
        return {
          id: first.id,
          color_name: first.color?.name || 'Unknown',
          color_hex: first.color?.hex_code || '#000000',
          size_id: null,
          price: first.price,
          stock: first.stock,
          sku: first.sku.replace(/-\d+$/, ''), // Remove size suffix
          images: [],
          existing_images: first.images || [],
          available_sizes,
          size_stocks
        };
      });
    }
    
    loading.value = false
    
  } catch (err) {
    console.error('Failed to load product:', err)
    error('Failed to Load Product', 'Unable to load product data')
    loading.value = false
  }
})

function getSizeName(sizeId: number) {
  return sizeStore.sizes.find(size => size.id === sizeId)?.name || 'Unknown';
}

function updateSizeStock(variantIndex: number, sizeId: number, stock: number) {
  variants.value[variantIndex].size_stocks[sizeId] = stock;
}

function removeSizeFromVariant(variantIndex: number, sizeId: number) {
  const variant = variants.value[variantIndex];
  variant.available_sizes = variant.available_sizes.filter(id => id !== sizeId);
}

async function updateProduct() {
  try {
    if (!subcategoryId.value) {
      error("Validation Error", "Please select a subcategory");
      return;
    }

    // For now, show a message that update is not fully implemented
    error("Update Not Fully Implemented", "Product editing is coming soon. Please delete and recreate for now.");
    
  } catch (err) {
    console.error(err);
    error("Failed to Update Product", "Unable to update product");
  }
}

function toggleAccordion(sectionId: string) {
  activeAccordion.value = activeAccordion.value === sectionId ? '' : sectionId;
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Edit Product</h1>
          <p class="text-sm text-gray-600 mt-1">Update product information</p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="$router.back()"
            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="updateProduct"
            :disabled="!canSubmit"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <Icon name="mdi:content-save" />
            Update Product
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="max-w-4xl mx-auto p-6">
      <div class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    </div>

    <!-- Content -->
    <div v-else class="max-w-4xl mx-auto p-6">
      <!-- Basic Information -->
      <div class="bg-white rounded-lg shadow-sm mb-4">
        <button
          @click="toggleAccordion('basic')"
          class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <Icon name="mdi:package-variant" class="text-xl text-gray-600" />
            <h2 class="text-lg font-semibold text-gray-900">Basic Information</h2>
          </div>
          <Icon
            :name="activeAccordion === 'basic' ? 'mdi:chevron-up' : 'mdi:chevron-down'"
            class="text-2xl text-gray-400"
          />
        </button>
        
        <div v-show="activeAccordion === 'basic'" class="p-6 border-t border-gray-100">
          <div class="space-y-4">
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Product Name</label>
              <input
                v-model="name"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter product name"
              />
            </div>

            <!-- Category & Brand (readonly for now) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subcategory</label>
                <p class="text-gray-600">{{ subcategoryStore.subcategories.find(s => s.id === subcategoryId)?.name || 'Not set' }}</p>
                <p class="text-xs text-gray-500 mt-1">Cannot change category in edit mode</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                <p class="text-gray-600">{{ brandStore.brands.find(b => b.id === brandId)?.name || 'Not set' }}</p>
                <p class="text-xs text-gray-500 mt-1">Cannot change brand in edit mode</p>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea
                v-model="description"
                rows="4"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter product description"
              ></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Message -->
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
        <Icon name="mdi:information" class="text-3xl text-yellow-600 mb-3" />
        <h3 class="text-lg font-semibold text-yellow-900 mb-2">Full Edit Coming Soon</h3>
        <p class="text-yellow-700 mb-4">
          Product editing is currently under development. For now, you can delete products and recreate them with the correct information.
        </p>
        <p class="text-sm text-yellow-600">
          Update functionality for images, variants, and stock will be available in a future update.
        </p>
      </div>
    </div>
  </div>
</template>

