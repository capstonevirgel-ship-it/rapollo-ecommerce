<script setup lang="ts">
import { storeToRefs } from "pinia";
import { onMounted, reactive } from "vue";
import { useCategoryStore } from "~/stores/category";
import { useSubcategoryStore } from "~/stores/subcategory";
import { useBrandStore } from "~/stores/brand";
import { getImageUrl } from "~/utils/imageHelper";

const props = defineProps<{
  activeMobileCategory: string | null;
  isOpen: boolean;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'toggle-category', slug: string): void;
}>();

// Pinia stores
const categoryStore = useCategoryStore();
const subcategoryStore = useSubcategoryStore();
const brandStore = useBrandStore();

const { categories, loading: catLoading } = storeToRefs(categoryStore);
const { subcategories, loading: subLoading } = storeToRefs(subcategoryStore);
const { brands, loading: brandLoading } = storeToRefs(brandStore);

// Track image load errors for brands
const brandImageError = reactive<Record<number, boolean>>({});

onMounted(() => {
  categoryStore.fetchCategories();
  subcategoryStore.fetchSubcategories();
  brandStore.fetchBrands();
});
</script>

<template>
  <Transition name="drawer-slide">
    <div v-if="isOpen" class="fixed inset-0 z-100 md:hidden">
      <!-- Background overlay -->
      <div class="absolute inset-0 bg-black/50" @click="emit('close')" />

      <!-- Drawer panel -->
      <div class="absolute right-0 top-0 h-full w-4/5 max-w-sm bg-white shadow-xl pt-2">
        <!-- Header -->
        <div class="p-4 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold">Shop Categories</h2>
          <button @click="emit('close')">
            <Icon name="mdi:close" class="text-2xl" />
          </button>
        </div>

        <!-- Category list -->
        <div class="overflow-y-auto h-[calc(100%-60px)] divide-y">
          <div v-if="catLoading || subLoading" class="p-4 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
            <p class="mt-2 text-gray-600">Loading categories...</p>
          </div>
          <div 
            v-else
            v-for="category in categories" 
            :key="category.id"
            class="flex flex-col"
          >
            <div class="flex justify-between items-center p-4">
              <!-- Category link -->
              <NuxtLink 
                :to="`/shop/${category.slug}`" 
                class="text-base font-medium text-gray-800 hover:text-primary-600"
                @click="emit('close')"
              >
                {{ category.name }}
              </NuxtLink>

              <!-- Toggle subcategory -->
              <button 
                @click="emit('toggle-category', category.slug)"
                aria-label="Toggle subcategories"
              >
                <Icon 
                  :name="activeMobileCategory === category.slug ? 'mdi:chevron-up' : 'mdi:chevron-down'" 
                  class="text-xl text-gray-600" 
                />
              </button>
            </div>

            <!-- Subcategory list -->
            <Transition name="accordion">
              <div 
                v-if="activeMobileCategory === category.slug"
                class="pl-6 bg-gray-50 pb-2"
              >
                <NuxtLink
                  v-for="sub in subcategories.filter(s => s.category_id === category.id)"
                  :key="sub.id"
                  :to="`/shop/${category.slug}/${sub.slug}`"
                  class="block py-2 px-2 text-sm text-gray-700 hover:text-primary-600 hover:bg-gray-100 rounded"
                  @click="emit('close')"
                >
                  {{ sub.name }}
                </NuxtLink>
              </div>
            </Transition>
          </div>

          <!-- Brands Section -->
          <div v-if="brands.length > 0" class="border-t pt-4">
            <div class="p-4">
              <h3 class="text-base font-semibold text-gray-900 mb-4">Brands</h3>
              <div class="grid grid-cols-3 gap-4">
                <div 
                  v-for="brand in brands.slice(0, 9)" 
                  :key="brand.id"
                  class="flex flex-col items-center group"
                >
                  <NuxtLink 
                    :to="`/shop?brand=${brand.slug}`" 
                    class="flex flex-col items-center space-y-2 hover:opacity-80 transition-opacity"
                    @click="emit('close')"
                  >
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                      <img 
                        v-if="!brandImageError[brand.id]"
                        :src="getImageUrl(brand.logo_url, 'brand')"
                        :alt="brand.name"
                        class="w-8 h-8 object-contain"
                        @error="brandImageError[brand.id] = true"
                      />
                      <img 
                        v-else
                        :src="getImageUrl(null, 'brand')"
                        :alt="brand.name"
                        class="w-8 h-8 object-contain"
                      />
                    </div>
                    <span class="text-xs text-gray-600 text-center group-hover:text-primary-600 transition-colors">
                      {{ brand.name }}
                    </span>
                  </NuxtLink>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.drawer-slide-enter-active,
.drawer-slide-leave-active {
  transition: transform 0.3s ease;
}
.drawer-slide-enter-from {
  transform: translateX(100%);
}
.drawer-slide-leave-to {
  transform: translateX(100%);
}
.accordion-enter-active,
.accordion-leave-active {
  transition: all 0.3s ease;
}
.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
  max-height: 0;
}
</style>
