<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { useProductStore } from "~/stores/product";
import { useBrandStore } from "~/stores/brand";
import { useSubcategoryStore } from "~/stores/subcategory";
import { useSizeStore } from "~/stores/size";
import { useTaxStore } from "~/stores/tax";
import { useAlert } from "~/composables/useAlert";
import { useRoute, useRouter } from "vue-router";
import { getImageUrl } from "~/utils/imageHelper";

definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Edit Product - Admin | RAPOLLO',
  meta: [
    { name: 'description', content: 'Edit product details in your Rapollo E-commerce store.' }
  ]
})

const route = useRoute()
const router = useRouter()
const productSlug = route.params.slug as string

const productStore = useProductStore();
const brandStore = useBrandStore();
const subcategoryStore = useSubcategoryStore();
const sizeStore = useSizeStore();
const taxStore = useTaxStore();
const { success, error } = useAlert();

// Loading state
const loading = ref(true)

// Accordion state
const activeAccordion = ref('basic');

// Preview tab state
const activePreviewTab = ref('search');

// Product state
const productId = ref<number | null>(null);
const name = ref("");
const brandId = ref<number | string | null>(null);
const subcategoryId = ref<number | null>(null);
const description = ref("");
const metaTitle = ref("");
const metaDescription = ref("");
const metaKeywords = ref("");

// Image management - track existing and new images separately
const existingImages = ref<Array<{ id: number; url: string; is_primary: boolean; sort_order: number }>>([]);
const imagesToDelete = ref<number[]>([]);
const newImages = ref<File[]>([]);
const primaryProductImageId = ref<number | null>(null); // ID of existing primary image
const primaryNewImageIndex = ref<number | null>(null); // Index of new primary image

// Default color
const defaultColorId = ref<number | null>(null);
const defaultColorName = ref("");
const defaultColorHex = ref("#000000");
const defaultColorMode = ref<'select' | 'custom'>('select');

// Master base price, stock, and sku
const masterBasePrice = ref<number>(0);
const masterStock = ref<number>(10);
const masterSku = ref<string>("");

// Computed final price with tax
const masterFinalPrice = computed(() => {
  if (masterBasePrice.value <= 0) return 0
  const totalTaxRate = taxStore.totalTaxRate
  return masterBasePrice.value * (1 + totalTaxRate / 100)
})

// Size selection
const selectedSizes = ref<number[]>([]);

// SEO state
const seoTitle = ref("");
const seoDescription = ref("");
const seoKeywords = ref("");
const seoCanonicalUrl = ref("");
const seoRobots = ref("index,follow");

// Inline brand
const newBrandMode = ref(false);
const newBrandName = ref("");

// Variants - enhanced for edit mode
type Variant = {
  id?: number; // Existing variant ID
  color_id?: number; // Existing color ID
  color_name: string;
  color_hex: string;
  size_id: number | null;
  stock: number;
  sku: string;
  new_images: File[]; // New files to upload
  existing_images: Array<{ id: number; url: string; is_primary: boolean; sort_order: number }>; // Existing images
  images_to_delete: number[]; // IDs of existing images to delete
  primary_existing_image_id: number | null; // ID of existing primary image
  primary_new_image_index: number | null; // Index of new primary image
  available_sizes: number[];
  size_stocks: { [sizeId: number]: number };
};

const variants = ref<Variant[]>([]);

// Computed function to calculate final price for product
const getProductFinalPrice = (basePrice: number) => {
  if (basePrice <= 0) return 0
  const totalTaxRate = taxStore.totalTaxRate
  return basePrice * (1 + totalTaxRate / 100)
}

// Example sizes/colors (frontend only for picker)
const availableColors = ref([
  { name: "Black", hex: "#000000" },
  { name: "White", hex: "#ffffff" },
]);

// Watch selectedSizes to update variant available_sizes
watch(selectedSizes, (newSizes) => {
  variants.value.forEach(variant => {
    variant.available_sizes = variant.available_sizes.filter(sizeId => newSizes.includes(sizeId));
    newSizes.forEach(sizeId => {
      if (!variant.available_sizes.includes(sizeId)) {
        variant.available_sizes.push(sizeId);
      }
    });
  });
}, { deep: true });

// Grouped subcategories for dropdown display
const groupedSubcategories = computed(() => {
  const groups: { [key: string]: { category: any; subcategories: any[] } } = {};
  
  subcategoryStore.subcategories.forEach(sub => {
    const categoryName = sub.category?.name || 'Uncategorized';
    if (!groups[categoryName]) {
      groups[categoryName] = {
        category: sub.category,
        subcategories: []
      };
    }
    groups[categoryName].subcategories.push(sub);
  });
  
  return Object.entries(groups).map(([categoryName, group]) => ({
    categoryName,
    ...group
  }));
});

// Accordion sections
const accordionSections = [
  { id: 'basic', title: 'Basic Information', icon: 'mdi:information', required: true },
  { id: 'media', title: 'Media & Images', icon: 'mdi:image', required: true },
  { id: 'variants', title: 'Product Variants', icon: 'mdi:palette', required: false },
  { id: 'inventory', title: 'Inventory', icon: 'mdi:package-variant', required: false },
  { id: 'seo', title: 'SEO & Marketing', icon: 'mdi:search-web', required: false },
];

// Computed properties
const isAccordionComplete = (sectionId: string) => {
  switch (sectionId) {
    case 'basic':
      return name.value.trim() !== '' && subcategoryId.value !== null && masterBasePrice.value > 0 && masterStock.value >= 0;
    case 'media':
      // Media is complete if there are existing images or new images
      return (existingImages.value.length - imagesToDelete.value.length > 0) || newImages.value.length > 0;
    case 'variants':
      return variants.value.length > 0;
    case 'inventory':
      return false;
    case 'seo':
      return (seoTitle.value.trim() !== '' || seoDescription.value.trim() !== '' || seoKeywords.value.trim() !== '' || seoCanonicalUrl.value.trim() !== '');
    default:
      return false;
  }
};

// Google search preview
const searchPreview = computed(() => {
  const title = seoTitle.value || name.value || 'Product Title';
  const metaDescription = seoDescription.value || description.value || 'Product description will appear here...';
  const url = seoCanonicalUrl.value || `https://yoursite.com/products/${name.value?.toLowerCase().replace(/\s+/g, '-') || 'product-name'}`;
  
  return {
    title: title.length > 60 ? title.substring(0, 60) + '...' : title,
    description: metaDescription.length > 160 ? metaDescription.substring(0, 160) + '...' : metaDescription,
    url: url,
    titleLength: title.length,
    descriptionLength: metaDescription.length
  };
});

const canSubmit = computed(() => {
  return isAccordionComplete('basic');
});

// Load product data
onMounted(async () => {
  try {
    loading.value = true
    
    // Load dropdowns
    await Promise.all([
      brandStore.fetchBrands(),
      subcategoryStore.fetchSubcategories(),
      sizeStore.fetchSizes(),
      taxStore.fetchTaxPrices().catch(() => {}) // Optional
    ])
    
    // Fetch product
    const product = await productStore.fetchProduct(productSlug)
    
    // Store product ID
    productId.value = product.id
    
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
    masterBasePrice.value = product.base_price || 0
    masterStock.value = product.stock || 10
    masterSku.value = product.sku || ''
    
    // Existing images - filter out variant images (only product-level images)
    existingImages.value = (product.images || []).filter((img: any) => !img.variant_id).map((img: any) => ({
      id: img.id,
      url: img.url,
      is_primary: img.is_primary,
      sort_order: img.sort_order || 0
    }));
    
    // Set primary image ID
    const primaryImage = existingImages.value.find(img => img.is_primary);
    if (primaryImage) {
      primaryProductImageId.value = primaryImage.id;
    }
    
    // Default color
    if (product.default_color) {
      defaultColorId.value = product.default_color.id;
      defaultColorName.value = product.default_color.name;
      defaultColorHex.value = product.default_color.hex_code;
      defaultColorMode.value = 'select';
    } else if (product.default_color_id) {
      defaultColorId.value = product.default_color_id;
    }
    
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
        
        // Get all images for this color variant group
        const variantImages = group.flatMap((v: any) => v.images || []).map((img: any) => ({
          id: img.id,
          url: img.url,
          is_primary: img.is_primary,
          sort_order: img.sort_order || 0
        }));
        
        // Find primary image
        const primaryVariantImage = variantImages.find((img: any) => img.is_primary);
        
        return {
          id: first.id, // Use first variant ID as group identifier
          color_id: first.color?.id,
          color_name: first.color?.name || 'Unknown',
          color_hex: first.color?.hex_code || '#000000',
          size_id: null,
          stock: first.stock,
          sku: first.sku.replace(/-\d+$/, ''), // Remove size suffix
          new_images: [],
          existing_images: variantImages,
          images_to_delete: [],
          primary_existing_image_id: primaryVariantImage?.id || null,
          primary_new_image_index: null,
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

// Watch master stock and sync with variants that have no sizes
watch(masterStock, (newStock) => {
  variants.value.forEach(variant => {
    if (variant.available_sizes.length === 0) {
      variant.stock = newStock;
    }
  });
});

// Image handling functions
function handleProductImages(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files) {
    const newFiles = Array.from(target.files);
    newImages.value = [...newImages.value, ...newFiles];
    // Set first new image as primary if no primary is set
    if (primaryNewImageIndex.value === null && primaryProductImageId.value === null && newImages.value.length > 0) {
      primaryNewImageIndex.value = 0;
    }
  }
}

function handleVariantImages(e: Event, index: number) {
  const target = e.target as HTMLInputElement;
  if (target.files) {
    const newFiles = Array.from(target.files);
    variants.value[index].new_images = [...variants.value[index].new_images, ...newFiles];
    // Set first image as primary if no primary is set
    if (variants.value[index].primary_new_image_index === null && variants.value[index].primary_existing_image_id === null && variants.value[index].new_images.length > 0) {
      variants.value[index].primary_new_image_index = 0;
    }
  }
}

// Product image management
function removeExistingProductImage(imageId: number) {
  imagesToDelete.value.push(imageId);
  existingImages.value = existingImages.value.filter(img => img.id !== imageId);
  // Reset primary if deleted image was primary
  if (primaryProductImageId.value === imageId) {
    primaryProductImageId.value = null;
    // Set first remaining existing image as primary, or first new image
    if (existingImages.value.length > 0) {
      primaryProductImageId.value = existingImages.value[0].id;
    } else if (newImages.value.length > 0) {
      primaryNewImageIndex.value = 0;
    }
  }
}

function removeNewProductImage(index: number) {
  newImages.value.splice(index, 1);
  // Adjust primary index if needed
  if (primaryNewImageIndex.value === index) {
    primaryNewImageIndex.value = newImages.value.length > 0 ? 0 : null;
    // If no new images left, set first existing as primary
    if (primaryNewImageIndex.value === null && existingImages.value.length > 0) {
      primaryProductImageId.value = existingImages.value[0].id;
    }
  } else if (primaryNewImageIndex.value !== null && primaryNewImageIndex.value > index) {
    primaryNewImageIndex.value = primaryNewImageIndex.value - 1;
  }
}

function setPrimaryExistingProductImage(imageId: number) {
  primaryProductImageId.value = imageId;
  primaryNewImageIndex.value = null; // Clear new image primary
}

function setPrimaryNewProductImage(index: number) {
  primaryNewImageIndex.value = index;
  primaryProductImageId.value = null; // Clear existing image primary
}

// Variant image management
function removeExistingVariantImage(variantIndex: number, imageId: number) {
  const variant = variants.value[variantIndex];
  variant.images_to_delete.push(imageId);
  variant.existing_images = variant.existing_images.filter(img => img.id !== imageId);
  // Reset primary if deleted image was primary
  if (variant.primary_existing_image_id === imageId) {
    variant.primary_existing_image_id = null;
    // Set first remaining existing image as primary, or first new image
    if (variant.existing_images.length > 0) {
      variant.primary_existing_image_id = variant.existing_images[0].id;
    } else if (variant.new_images.length > 0) {
      variant.primary_new_image_index = 0;
    }
  }
}

function removeNewVariantImage(variantIndex: number, index: number) {
  const variant = variants.value[variantIndex];
  variant.new_images.splice(index, 1);
  // Adjust primary index if needed
  if (variant.primary_new_image_index === index) {
    variant.primary_new_image_index = variant.new_images.length > 0 ? 0 : null;
    // If no new images left, set first existing as primary
    if (variant.primary_new_image_index === null && variant.existing_images.length > 0) {
      variant.primary_existing_image_id = variant.existing_images[0].id;
    }
  } else if (variant.primary_new_image_index !== null && variant.primary_new_image_index > index) {
    variant.primary_new_image_index = variant.primary_new_image_index - 1;
  }
}

function setPrimaryExistingVariantImage(variantIndex: number, imageId: number) {
  const variant = variants.value[variantIndex];
  variant.primary_existing_image_id = imageId;
  variant.primary_new_image_index = null; // Clear new image primary
}

function setPrimaryNewVariantImage(variantIndex: number, index: number) {
  const variant = variants.value[variantIndex];
  variant.primary_new_image_index = index;
  variant.primary_existing_image_id = null; // Clear existing image primary
}

function getImagePreview(file: File) {
  return URL.createObjectURL(file);
}

// Variant management
function addVariant() {
  variants.value.push({
    color_name: "White",
    color_hex: "#ffffff",
    size_id: null,
    stock: masterStock.value || 10,
    sku: "",
    new_images: [],
    existing_images: [],
    images_to_delete: [],
    primary_existing_image_id: null,
    primary_new_image_index: null,
    available_sizes: [...selectedSizes.value],
    size_stocks: {},
  });
}

function removeVariant(index: number) {
  variants.value.splice(index, 1);
}

// Handle color picker change
function handleColorPickerChange(variantIndex: number, hexValue: string) {
  const variant = variants.value[variantIndex];
  const capitalizedHex = hexValue.toUpperCase();
  variant.color_hex = capitalizedHex;
  variant.color_name = capitalizedHex;
}

// Handle color dropdown change
function handleColorDropdownChange(variantIndex: number, colorName: string | number | null) {
  if (!colorName) return;
  
  const variant = variants.value[variantIndex];
  variant.color_name = String(colorName);
  
  const selectedColor = availableColors.value.find(c => c.name === String(colorName));
  if (selectedColor) {
    variant.color_hex = selectedColor.hex;
  }
}

// Handle size stock change
function updateSizeStock(variantIndex: number, sizeId: number, stock: number) {
  variants.value[variantIndex].size_stocks[sizeId] = stock;
}

function removeSizeFromVariant(variantIndex: number, sizeId: number) {
  const variant = variants.value[variantIndex];
  variant.available_sizes = variant.available_sizes.filter(id => id !== sizeId);
}

function getSizeName(sizeId: number) {
  return sizeStore.sizes.find(size => size.id === sizeId)?.name || 'Unknown';
}

// Initialize selected sizes from variants
const initializeSelectedSizes = () => {
  if (selectedSizes.value.length === 0) {
    const allSizes = new Set<number>();
    variants.value.forEach(variant => {
      variant.available_sizes.forEach(sizeId => allSizes.add(sizeId));
    });
    selectedSizes.value = Array.from(allSizes);
  }
};

function toggleAccordion(sectionId: string) {
  activeAccordion.value = activeAccordion.value === sectionId ? '' : sectionId;
}

function switchPreviewTab(tab: string) {
  activePreviewTab.value = tab;
}

async function updateProduct() {
  try {
    if (!subcategoryId.value) {
      error("Validation Error", "Please select a subcategory");
      return;
    }

    if (!productId.value) {
      error("Validation Error", "Product ID is missing");
      return;
    }

    let finalBrandId = brandId.value as number | null;

    if (newBrandMode.value && newBrandName.value.trim()) {
      const created = await brandStore.createBrand({ name: newBrandName.value });
      finalBrandId = created.id;
    }

    // Prepare variant data
    const validVariants = variants.value.length > 0
      ? variants.value.map((v) => ({
          id: v.id, // Include variant ID if updating
          color_id: v.color_id,
          color_name: v.color_name,
          color_hex: v.color_hex,
          available_sizes: v.available_sizes,
          stock: v.stock,
          sku: v.sku,
          new_images: v.new_images,
          existing_images: v.existing_images.map(img => img.id), // IDs to keep
          images_to_delete: v.images_to_delete,
          primary_existing_image_id: v.primary_existing_image_id,
          primary_new_image_index: v.primary_new_image_index,
        }))
      : [];

    await productStore.updateProduct(productSlug, {
      name: name.value,
      description: description.value,
      meta_title: seoTitle.value || metaTitle.value,
      meta_description: seoDescription.value || metaDescription.value,
      meta_keywords: seoKeywords.value || metaKeywords.value,
      canonical_url: seoCanonicalUrl.value,
      robots: seoRobots.value,
      base_price: masterBasePrice.value > 0 ? masterBasePrice.value : undefined,
      stock: masterStock.value > 0 ? masterStock.value : undefined,
      sku: masterSku.value.trim() || undefined,
      existing_image_ids: existingImages.value.map(img => img.id), // IDs to keep
      images_to_delete: imagesToDelete.value,
      new_images: newImages.value,
      primary_existing_image_id: primaryProductImageId.value,
      primary_new_image_index: primaryNewImageIndex.value,
      sizes: selectedSizes.value,
      default_color_id: defaultColorId.value || undefined,
      default_color_name: defaultColorName.value ? defaultColorName.value : undefined,
      default_color_hex: defaultColorHex.value && defaultColorHex.value !== '#000000' ? defaultColorHex.value : undefined,
      variants: validVariants.length > 0 ? validVariants : undefined,
    });

    success("Product Updated", "Product has been updated successfully!");
    router.push('/admin/products');
    
  } catch (err) {
    console.error(err);
    error("Failed to Update Product", "Unable to update product. Please check all fields and try again.");
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Product</h1>
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
            class="px-6 py-2 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
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
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-zinc-900"></div>
      </div>
    </div>

    <!-- Content -->
    <div v-else class="max-w-4xl mx-auto p-6">
      <!-- Accordion Sections -->
      <div class="space-y-4">
      <!-- Basic Information -->
        <div class="bg-white rounded-lg border border-gray-200">
        <button
          @click="toggleAccordion('basic')"
            class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center gap-3">
              <Icon 
                name="mdi:information" 
                class="text-lg"
                :class="isAccordionComplete('basic') ? 'text-green-600' : 'text-gray-400'"
              />
              <div>
                <h3 class="font-semibold text-gray-900">Basic Information</h3>
                <p class="text-sm text-gray-500">Product name, description, and categorization</p>
          </div>
            </div>
            <div class="flex items-center gap-2">
          <Icon
                name="mdi:chevron-down" 
                class="text-gray-400 transition-transform"
                :class="{ 'rotate-180': activeAccordion === 'basic' }"
          />
            </div>
        </button>
        
          <div v-if="activeAccordion === 'basic'" class="px-6 pb-6 border-t border-gray-200">
            <div class="space-y-6 pt-6">
              <!-- Product Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
              <input
                v-model="name"
                type="text"
                placeholder="Enter product name"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
              />
            </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea 
                  v-model="description" 
                  placeholder="Describe your product"
                  rows="4"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                />
              </div>

              <!-- Category and Brand -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Subcategory -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Subcategory *</label>
                  <div class="relative">
                    <select 
                      v-model="subcategoryId" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                      disabled
                    >
                      <option :value="subcategoryId">{{ subcategoryStore.subcategories.find(s => s.id === subcategoryId)?.name || 'Not set' }}</option>
                    </select>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Subcategory cannot be changed</p>
                </div>

                <!-- Brand -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                  <div v-if="!newBrandMode">
                    <Select
                      v-model="brandId"
                      :options="brandStore.brands.map(b => ({ value: b.id, label: b.name }))"
                      placeholder="Select a brand"
                      :disabled="true"
                    />
                    <p class="text-xs text-gray-500 mt-1">Brand cannot be changed</p>
                  </div>
              </div>
            </div>

              <!-- Default/Main Product Color -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Default Product Color</label>
                <div class="flex items-center gap-2">
                  <Select
                    v-if="defaultColorMode === 'select'"
                    :model-value="defaultColorName || null"
                    @update:model-value="(val) => { 
                      if (val) { 
                        const color = availableColors.find(c => c.name === val); 
                        if (color) { defaultColorName = color.name; defaultColorHex = color.hex; }
                      }
                    }"
                    :options="availableColors.map(c => ({ value: c.name, label: c.name }))"
                    placeholder="Select default color (optional)"
                    class="flex-1"
                  />
                  <div v-else class="flex items-center gap-2 flex-1">
                    <input 
                      v-model="defaultColorName" 
                      type="text" 
                      placeholder="Color name"
                      class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    />
                    <input 
                      type="color" 
                      :value="defaultColorHex"
                      @input="(e) => {
                        const hex = (e.target as HTMLInputElement).value.toUpperCase();
                        defaultColorHex = hex;
                        defaultColorName = hex;
                      }"
                      class="w-10 h-10 border-2 border-gray-300 rounded-md cursor-pointer"
                    />
            </div>
                  <button
                    @click="defaultColorMode = defaultColorMode === 'select' ? 'custom' : 'select'"
                    class="px-3 py-2 text-sm text-gray-600 hover:text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    {{ defaultColorMode === 'select' ? 'Custom' : 'Select' }}
                  </button>
          </div>
                <p class="text-xs text-gray-500 mt-1">Choose a default color for the main product, or leave empty if no default color is needed.</p>
        </div>

              <!-- Available Sizes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Available Sizes</label>
                <MultiSelect
                  v-model="selectedSizes"
                  :options="sizeStore.sizes.map(size => ({ value: size.id, label: size.name }))"
                  placeholder="Select available sizes for this product"
                />
                <p class="text-xs text-gray-500 mt-1">Select all sizes this product can come in. You can remove specific sizes per variant later.</p>
      </div>

              <!-- Price and Stock -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Master Base Price -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Base Price (Before Tax) *</label>
                  <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">₱</span>
                    <input 
                      v-model="masterBasePrice" 
                      type="number" 
                      step="0.01" 
                      placeholder="0.00"
                      class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    />
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Base price before tax. Final price: <span class="font-semibold text-zinc-900">₱{{ masterFinalPrice.toFixed(2) }}</span></p>
                </div>

                <!-- Master Stock -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Default Stock *</label>
                  <input 
                    v-model="masterStock" 
                    type="number" 
                    min="0"
                    placeholder="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                  />
                  <p class="text-xs text-gray-500 mt-1">Default stock for variants without sizes</p>
                </div>
              </div>

              <!-- SKU -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                <input 
                  v-model="masterSku" 
                  type="text" 
                  placeholder="Enter product SKU (optional)"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                />
                <p class="text-xs text-gray-500 mt-1">Stock Keeping Unit for products without variants</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Media & Images -->
        <div class="bg-white rounded-lg border border-gray-200">
          <button
            @click="toggleAccordion('media')"
            class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <Icon 
                name="mdi:image" 
                class="text-lg"
                :class="isAccordionComplete('media') ? 'text-green-600' : 'text-gray-400'"
              />
              <div>
                <h3 class="font-semibold text-gray-900">Media & Images</h3>
                <p class="text-sm text-gray-500">Product photos and media files</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Icon 
                name="mdi:chevron-down" 
                class="text-gray-400 transition-transform"
                :class="{ 'rotate-180': activeAccordion === 'media' }"
              />
            </div>
          </button>
          
          <div v-if="activeAccordion === 'media'" class="px-6 pb-6 border-t border-gray-200">
            <div class="pt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Product Images *</label>
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                <Icon name="mdi:cloud-upload" class="text-4xl text-gray-400 mx-auto mb-4" />
                <p class="text-sm text-gray-600 mb-2">Upload additional product images</p>
                <input 
                  type="file" 
                  multiple 
                  accept="image/*"
                  @change="handleProductImages" 
                  class="hidden"
                  id="product-images"
                />
                <label for="product-images" class="inline-flex items-center px-4 py-2 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 cursor-pointer">
                  <Icon name="mdi:upload" class="mr-2" />
                  Choose Files
                </label>
                <p v-if="newImages.length > 0" class="text-sm text-green-600 mt-2">
                  {{ newImages.length }} new file(s) selected
                </p>
              </div>
              
              <!-- Image Previews - Existing Images -->
              <div v-if="existingImages.length > 0" class="mt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Existing Images</h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                  <div 
                    v-for="image in existingImages" 
                    :key="image.id"
                    class="relative group border-2 rounded-lg overflow-hidden"
                    :class="primaryProductImageId === image.id ? 'border-zinc-900 ring-2 ring-zinc-900' : 'border-gray-300'"
                  >
                    <img 
                      :src="getImageUrl(image.url)" 
                      :alt="`Product image ${image.id}`"
                      class="w-full h-32 object-cover"
                    />
                    <div class="absolute top-2 right-2 flex gap-1">
                      <button
                        @click="setPrimaryExistingProductImage(image.id)"
                        :class="[
                          'px-2 py-1 rounded text-xs font-medium transition-colors',
                          primaryProductImageId === image.id 
                            ? 'bg-zinc-900 text-white' 
                            : 'bg-white/90 text-gray-700 hover:bg-white'
                        ]"
                        :title="primaryProductImageId === image.id ? 'Primary image' : 'Set as thumbnail'"
                      >
                        <Icon :name="primaryProductImageId === image.id ? 'mdi:star' : 'mdi:star-outline'" />
                      </button>
                      <button
                        @click="removeExistingProductImage(image.id)"
                        class="px-2 py-1 rounded bg-red-500 text-white text-xs hover:bg-red-600 transition-colors"
                        title="Remove image"
                      >
                        <Icon name="mdi:delete" />
                      </button>
                    </div>
                    <div v-if="primaryProductImageId === image.id" class="absolute bottom-0 left-0 right-0 bg-zinc-900/80 text-white text-xs py-1 px-2 text-center">
                      Primary Image
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Image Previews - New Images -->
              <div v-if="newImages.length > 0" class="mt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">New Images</h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                  <div 
                    v-for="(image, index) in newImages" 
                    :key="`new-${index}`"
                    class="relative group border-2 rounded-lg overflow-hidden"
                    :class="primaryNewImageIndex === index ? 'border-zinc-900 ring-2 ring-zinc-900' : 'border-gray-300'"
                  >
                    <img 
                      :src="getImagePreview(image)" 
                      :alt="`New product image ${index + 1}`"
                      class="w-full h-32 object-cover"
                    />
                    <div class="absolute top-2 right-2 flex gap-1">
                      <button
                        @click="setPrimaryNewProductImage(index)"
                        :class="[
                          'px-2 py-1 rounded text-xs font-medium transition-colors',
                          primaryNewImageIndex === index 
                            ? 'bg-zinc-900 text-white' 
                            : 'bg-white/90 text-gray-700 hover:bg-white'
                        ]"
                        :title="primaryNewImageIndex === index ? 'Primary image' : 'Set as thumbnail'"
                      >
                        <Icon :name="primaryNewImageIndex === index ? 'mdi:star' : 'mdi:star-outline'" />
                      </button>
                      <button
                        @click="removeNewProductImage(index)"
                        class="px-2 py-1 rounded bg-red-500 text-white text-xs hover:bg-red-600 transition-colors"
                        title="Remove image"
                      >
                        <Icon name="mdi:delete" />
                      </button>
                    </div>
                    <div v-if="primaryNewImageIndex === index" class="absolute bottom-0 left-0 right-0 bg-zinc-900/80 text-white text-xs py-1 px-2 text-center">
                      Primary Image
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Variants -->
        <div class="bg-white rounded-lg border border-gray-200">
          <button
            @click="toggleAccordion('variants')"
            class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <Icon 
                name="mdi:palette" 
                class="text-lg"
                :class="isAccordionComplete('variants') ? 'text-green-600' : 'text-gray-400'"
              />
              <div>
                <h3 class="font-semibold text-gray-900">Product Variants</h3>
                <p class="text-sm text-gray-500">Product variants, colors, sizes, and pricing</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Icon 
                name="mdi:chevron-down" 
                class="text-gray-400 transition-transform"
                :class="{ 'rotate-180': activeAccordion === 'variants' }"
              />
            </div>
          </button>
          
          <div v-if="activeAccordion === 'variants'" class="px-6 pb-6 border-t border-gray-200">
            <div class="pt-6 space-y-6">
              <!-- Empty State -->
              <div v-if="variants.length === 0" class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg">
                <Icon name="mdi:palette-outline" class="text-4xl text-gray-400 mx-auto mb-4" />
                <h4 class="text-lg font-medium text-gray-900 mb-2">No Variants Added</h4>
                <p class="text-sm text-gray-500 mb-6">Variants are optional. Click the button below to add a variant if needed.</p>
                <button 
                  @click="addVariant" 
                  class="px-6 py-3 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 transition-colors flex items-center justify-center gap-2 mx-auto"
                >
                  <Icon name="mdi:plus" />
                  Add Your First Variant
                </button>
              </div>

              <!-- Variants List -->
              <div v-for="(variant, index) in variants" :key="index" class="p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center justify-between mb-4">
                  <h4 class="font-medium text-gray-900">Variant {{ index + 1 }}</h4>
                  <button 
                    @click="removeVariant(index)"
                    class="text-red-600 hover:text-red-700"
                  >
                    <Icon name="mdi:delete" />
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                  <!-- Color -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                    <div class="flex items-center gap-2">
                      <Select
                        :model-value="variant.color_name"
                        @update:model-value="handleColorDropdownChange(index, $event)"
                        :options="[
                          ...availableColors.map(c => ({ value: c.name, label: c.name })),
                          ...(variant.color_name && !availableColors.find(c => c.name === variant.color_name) 
                            ? [{ value: variant.color_name, label: variant.color_name }] 
                            : [])
                        ]"
                        placeholder="Select color"
                        class="flex-1"
                      />
                      <input 
                        type="color" 
                        :value="variant.color_hex"
                        @input="handleColorPickerChange(index, ($event.target as HTMLInputElement).value)"
                        class="w-10 h-10 border-2 border-gray-300 rounded-md cursor-pointer"
                      />
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Select Black or White, then use color picker for exact hex</p>
                  </div>

                  <!-- Available Sizes -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Available Sizes</label>
                    <div class="flex flex-wrap gap-2">
                      <span
                        v-for="sizeId in variant.available_sizes"
                        :key="sizeId"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-zinc-100 text-zinc-800 rounded-md"
                      >
                        {{ getSizeName(sizeId) }}
                        <button
                          @click="removeSizeFromVariant(index, sizeId)"
                          class="text-zinc-600 hover:text-zinc-800 focus:outline-none"
                        >
                          <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </span>
                      <span v-if="variant.available_sizes.length === 0" class="text-xs text-gray-500 italic">
                        No sizes selected
                      </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Remove sizes that are not available for this variant</p>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                  <!-- SKU -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                    <input 
                      v-model="variant.sku" 
                      type="text" 
                      placeholder="Product SKU"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    />
                  </div>
                </div>

                <!-- Individual Stock per Size -->
                <div v-if="variant.available_sizes.length > 0" class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Stock per Size</label>
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="sizeId in variant.available_sizes" :key="sizeId" class="flex items-center gap-2">
                      <label class="text-sm text-gray-600 w-16">{{ getSizeName(sizeId) }}:</label>
                      <input 
                        :value="variant.size_stocks[sizeId] || 0"
                        @input="updateSizeStock(index, sizeId, parseInt(($event.target as HTMLInputElement).value) || 0)"
                        type="number" 
                        min="0"
                        placeholder="0"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                      />
                    </div>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Set individual stock quantities for each size</p>
                </div>

                <!-- Stock for variants without sizes -->
                <div v-else class="mb-4">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Stock</label>
                  <div class="flex items-center gap-2">
                    <input 
                      v-model="variant.stock" 
                      type="number" 
                      min="0"
                      placeholder="0"
                      class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    />
                    <span class="text-sm text-gray-500">units</span>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Stock for this variant (no sizes selected)</p>
                </div>

                <!-- Variant Images -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Variant Images</label>
                  <input 
                    type="file" 
                    multiple 
                    accept="image/*"
                    @change="(e) => handleVariantImages(e, index)" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900 mb-3"
                  />
                  
                  <!-- Existing Variant Images -->
                  <div v-if="variant.existing_images.length > 0" class="mt-3">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">Existing Images</h5>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                      <div 
                        v-for="image in variant.existing_images" 
                        :key="image.id"
                        class="relative group border-2 rounded-lg overflow-hidden"
                        :class="variant.primary_existing_image_id === image.id ? 'border-zinc-900 ring-2 ring-zinc-900' : 'border-gray-300'"
                      >
                        <img 
                          :src="getImageUrl(image.url)" 
                          :alt="`Variant image ${image.id}`"
                          class="w-full h-32 object-cover"
                        />
                        <div class="absolute top-2 right-2 flex gap-1">
                          <button
                            @click="setPrimaryExistingVariantImage(index, image.id)"
                            :class="[
                              'px-2 py-1 rounded text-xs font-medium transition-colors',
                              variant.primary_existing_image_id === image.id 
                                ? 'bg-zinc-900 text-white' 
                                : 'bg-white/90 text-gray-700 hover:bg-white'
                            ]"
                            :title="variant.primary_existing_image_id === image.id ? 'Primary image' : 'Set as thumbnail'"
                          >
                            <Icon :name="variant.primary_existing_image_id === image.id ? 'mdi:star' : 'mdi:star-outline'" />
                          </button>
                          <button
                            @click="removeExistingVariantImage(index, image.id)"
                            class="px-2 py-1 rounded bg-red-500 text-white text-xs hover:bg-red-600 transition-colors"
                            title="Remove image"
                          >
                            <Icon name="mdi:delete" />
                          </button>
                        </div>
                        <div v-if="variant.primary_existing_image_id === image.id" class="absolute bottom-0 left-0 right-0 bg-zinc-900/80 text-white text-xs py-1 px-2 text-center">
                          Primary Image
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- New Variant Images -->
                  <div v-if="variant.new_images.length > 0" class="mt-3">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">New Images</h5>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                      <div 
                        v-for="(image, imgIndex) in variant.new_images" 
                        :key="`new-${imgIndex}`"
                        class="relative group border-2 rounded-lg overflow-hidden"
                        :class="variant.primary_new_image_index === imgIndex ? 'border-zinc-900 ring-2 ring-zinc-900' : 'border-gray-300'"
                      >
                        <img 
                          :src="getImagePreview(image)" 
                          :alt="`New variant image ${imgIndex + 1}`"
                          class="w-full h-32 object-cover"
                        />
                        <div class="absolute top-2 right-2 flex gap-1">
                          <button
                            @click="setPrimaryNewVariantImage(index, imgIndex)"
                            :class="[
                              'px-2 py-1 rounded text-xs font-medium transition-colors',
                              variant.primary_new_image_index === imgIndex 
                                ? 'bg-zinc-900 text-white' 
                                : 'bg-white/90 text-gray-700 hover:bg-white'
                            ]"
                            :title="variant.primary_new_image_index === imgIndex ? 'Primary image' : 'Set as thumbnail'"
                          >
                            <Icon :name="variant.primary_new_image_index === imgIndex ? 'mdi:star' : 'mdi:star-outline'" />
                          </button>
                          <button
                            @click="removeNewVariantImage(index, imgIndex)"
                            class="px-2 py-1 rounded bg-red-500 text-white text-xs hover:bg-red-600 transition-colors"
                            title="Remove image"
                          >
                            <Icon name="mdi:delete" />
                          </button>
                        </div>
                        <div v-if="variant.primary_new_image_index === imgIndex" class="absolute bottom-0 left-0 right-0 bg-zinc-900/80 text-white text-xs py-1 px-2 text-center">
                          Primary Image
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Add Variant Button (only show if variants exist) -->
              <button 
                v-if="variants.length > 0"
                @click="addVariant" 
                class="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-gray-400 hover:text-gray-700 transition-colors flex items-center justify-center gap-2"
              >
                <Icon name="mdi:plus" />
                Add Variant
              </button>
            </div>
          </div>
        </div>

        <!-- SEO & Marketing -->
        <div class="bg-white rounded-lg border border-gray-200">
          <button
            @click="toggleAccordion('seo')"
            class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <Icon 
                name="mdi:search-web" 
                class="text-lg"
                :class="isAccordionComplete('seo') ? 'text-green-600' : 'text-gray-400'"
              />
              <div>
                <h3 class="font-semibold text-gray-900">SEO & Marketing</h3>
                <p class="text-sm text-gray-500">Search engine optimization settings</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Icon 
                name="mdi:chevron-down" 
                class="text-gray-400 transition-transform"
                :class="{ 'rotate-180': activeAccordion === 'seo' }"
              />
            </div>
          </button>
          
          <div v-if="activeAccordion === 'seo'" class="px-6 pb-6 border-t border-gray-200">
            <div class="pt-6 space-y-6">
              <!-- Preview Tabs -->
              <div>
                <h4 class="text-lg font-medium text-gray-900 mb-4">Preview</h4>
                
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200 mb-4">
                  <nav class="-mb-px flex space-x-8">
                    <button
                      @click="switchPreviewTab('search')"
                      :class="[
                        'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
                        activePreviewTab === 'search'
                          ? 'border-zinc-900 text-zinc-900'
                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                      ]"
                    >
                      Search Engine
                    </button>
                    <button
                      @click="switchPreviewTab('shop')"
                      :class="[
                        'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
                        activePreviewTab === 'shop'
                          ? 'border-zinc-900 text-zinc-900'
                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                      ]"
                    >
                      Shopping Tab
                    </button>
                  </nav>
                </div>

                <!-- Search Preview Tab -->
                <div v-if="activePreviewTab === 'search'" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                  <div class="max-w-2xl">
                    <!-- Search Result Preview -->
                    <div class="space-y-1">
                      <div class="flex items-center gap-2 text-sm text-green-700">
                        <span class="font-medium truncate">{{ searchPreview.url }}</span>
                        <Icon name="mdi:chevron-down" class="text-xs flex-shrink-0" />
                      </div>
                      <h3 class="text-xl text-zinc-900 hover:underline cursor-pointer break-words">
                        {{ searchPreview.title }}
                      </h3>
                      <p class="text-sm text-gray-600 leading-relaxed break-words whitespace-normal">
                        {{ searchPreview.description }}
                      </p>
                    </div>
                  </div>
                  
                  <!-- Character Count Indicators -->
                  <div class="mt-4 flex gap-6 text-xs">
                    <div class="flex items-center gap-2">
                      <span class="text-gray-500">Title:</span>
                      <span :class="searchPreview.titleLength > 60 ? 'text-red-500' : searchPreview.titleLength > 50 ? 'text-yellow-500' : 'text-green-500'">
                        {{ searchPreview.titleLength }}/60
                      </span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-gray-500">Description:</span>
                      <span :class="searchPreview.descriptionLength > 160 ? 'text-red-500' : searchPreview.descriptionLength > 120 ? 'text-yellow-500' : 'text-green-500'">
                        {{ searchPreview.descriptionLength }}/160
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Shopping Tab Preview -->
                <div v-if="activePreviewTab === 'shop'" class="border border-gray-200 rounded-lg p-4 bg-white">
                  <div class="max-w-md">
                    <!-- Shopping Result Preview -->
                    <div class="flex gap-4">
                      <!-- Product Image -->
                      <div class="w-24 h-24 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center overflow-hidden">
                        <Icon v-if="existingImages.length === 0 && newImages.length === 0" name="mdi:image-outline" class="text-3xl text-gray-400" />
                        <img v-else-if="primaryProductImageId !== null" :src="getImageUrl(existingImages.find(img => img.id === primaryProductImageId)?.url || '')" :alt="name || 'Product'" class="w-full h-full object-cover" />
                        <img v-else-if="primaryNewImageIndex !== null" :src="getImagePreview(newImages[primaryNewImageIndex])" :alt="name || 'Product'" class="w-full h-full object-cover" />
                        <img v-else-if="existingImages.length > 0" :src="getImageUrl(existingImages[0].url)" :alt="name || 'Product'" class="w-full h-full object-cover" />
                        <img v-else :src="getImagePreview(newImages[0])" :alt="name || 'Product'" class="w-full h-full object-cover" />
                      </div>
                      
                      <!-- Product Details -->
                      <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-gray-900 text-sm leading-tight mb-1 break-words">
                          {{ name || 'Product Name' }}
                        </h3>
                        <p class="text-sm font-semibold text-gray-900 mb-2">
                          {{ masterBasePrice > 0 ? `₱${getProductFinalPrice(masterBasePrice).toFixed(2)}` : 'Price not set' }}
                        </p>
                        
                        <!-- Brand/Seller -->
                        <div class="flex items-center gap-2 mb-2">
                          <div class="w-4 h-4 bg-red-500 rounded-sm flex-shrink-0"></div>
                          <span class="text-xs text-gray-600">
                            {{ brandStore.brands.find(b => b.id === brandId)?.name || 'Brand Name' }}
                          </span>
                        </div>
                        
                        <!-- Additional Info -->
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                          <span>30-day returns</span>
                          <div class="flex items-center gap-1">
                            <div class="flex">
                              <Icon name="mdi:star" class="text-yellow-400 text-xs" />
                              <Icon name="mdi:star" class="text-yellow-400 text-xs" />
                              <Icon name="mdi:star" class="text-yellow-400 text-xs" />
                              <Icon name="mdi:star" class="text-yellow-400 text-xs" />
                              <Icon name="mdi:star-half-full" class="text-yellow-400 text-xs" />
                            </div>
                            <span>4.2 (22)</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- SEO Settings -->
              <div>
                <h4 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h4>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">SEO Title</label>
                    <input 
                      v-model="seoTitle" 
                      type="text" 
                      placeholder="Optimized title for search engines"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    />
                    <p class="text-xs text-gray-500 mt-1">Recommended: 50-60 characters</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">SEO Description</label>
                    <textarea 
                      v-model="seoDescription" 
                      placeholder="Meta description for search results"
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    />
                    <p class="text-xs text-gray-500 mt-1">Recommended: 150-160 characters</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Keywords</label>
                    <input 
                      v-model="seoKeywords" 
                      type="text" 
                      placeholder="keyword1, keyword2, keyword3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    />
                    <p class="text-xs text-gray-500 mt-1">Separate keywords with commas</p>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Canonical URL</label>
                      <input 
                        v-model="seoCanonicalUrl" 
                        type="url" 
                        placeholder="https://example.com/product"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Robots</label>
                      <Select
                        v-model="seoRobots"
                        :options="[
                          { value: 'index,follow', label: 'Index, Follow' },
                          { value: 'noindex,follow', label: 'No Index, Follow' },
                          { value: 'index,nofollow', label: 'Index, No Follow' },
                          { value: 'noindex,nofollow', label: 'No Index, No Follow' }
                        ]"
                        placeholder="Select robots directive"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

