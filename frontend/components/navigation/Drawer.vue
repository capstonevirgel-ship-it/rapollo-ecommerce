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
  // Only fetch data on client side
  if (process.client) {
    categoryStore.fetchCategories();
    subcategoryStore.fetchSubcategories();
    brandStore.fetchBrands();
  }
});
</script>

<template>
  <Transition name="drawer-slide">
    <div v-if="isOpen" class="fixed inset-0 z-50 lg:hidden">
      <!-- Background overlay -->
      <div class="absolute inset-0 bg-zinc-900/95 backdrop-blur-md" @click="emit('close')" />

      <!-- Drawer panel -->
      <div class="absolute right-0 top-0 h-full w-4/5 max-w-sm bg-zinc-900 shadow-2xl">
        <!-- Header -->
        <div class="p-6 border-b border-zinc-800/50 flex justify-between items-center">
          <h2 class="text-xl font-bold text-white font-winner-extra-bold">Shop Categories</h2>
          <button @click="emit('close')" class="p-2 hover:bg-zinc-800/50 rounded-lg transition-colors">
            <Icon name="mdi:close" class="text-2xl text-gray-300 hover:text-white" />
          </button>
        </div>

        <!-- Category list -->
        <div class="overflow-y-auto h-[calc(100%-80px)]">
          <div v-if="catLoading || subLoading" class="p-6 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-zinc-600 mx-auto"></div>
            <p class="mt-2 text-gray-400">Loading categories...</p>
          </div>
          <div 
            v-else
            v-for="category in categories" 
            :key="category.id"
            class="flex flex-col border-b border-zinc-800/30"
          >
            <div class="flex justify-between items-center p-4 hover:bg-zinc-800/30 transition-colors">
              <!-- Category link -->
              <NuxtLink 
                :to="`/shop/${category.slug}`" 
                class="text-base font-medium text-gray-300 hover:text-white transition-colors flex-1"
                @click="emit('close')"
              >
                {{ category.name }}
              </NuxtLink>

              <!-- Toggle subcategory -->
              <button 
                @click="emit('toggle-category', category.slug)"
                aria-label="Toggle subcategories"
                class="p-2 hover:bg-zinc-700/50 rounded-lg transition-colors ml-2"
              >
                <Icon 
                  :name="activeMobileCategory === category.slug ? 'mdi:chevron-up' : 'mdi:chevron-down'" 
                  class="text-lg text-gray-400 hover:text-white transition-colors" 
                />
              </button>
            </div>

            <!-- Subcategory list -->
            <Transition name="accordion">
              <div 
                v-if="activeMobileCategory === category.slug"
                class="pl-6 bg-zinc-800/20 pb-2"
              >
                <NuxtLink
                  v-for="sub in subcategories.filter(s => s.category_id === category.id)"
                  :key="sub.id"
                  :to="`/shop/${category.slug}/${sub.slug}`"
                  class="block py-3 px-4 text-sm text-gray-400 hover:text-white hover:bg-zinc-700/30 rounded-lg transition-colors mx-2"
                  @click="emit('close')"
                >
                  {{ sub.name }}
                </NuxtLink>
              </div>
            </Transition>
          </div>

          <!-- Brands Section -->
          <div v-if="brands.length > 0" class="border-t border-zinc-800/50 pt-6">
            <div class="p-6">
              <h3 class="text-lg font-semibold text-white mb-6 font-winner-extra-bold">Brands</h3>
              <div class="grid grid-cols-3 gap-4">
                <div 
                  v-for="brand in brands.slice(0, 9)" 
                  :key="brand.id"
                  class="flex flex-col items-center group"
                >
                  <NuxtLink 
                    :to="`/shop?brand=${brand.slug}`" 
                    class="flex flex-col items-center space-y-3 hover:opacity-80 transition-all duration-200 p-3 rounded-xl hover:bg-zinc-800/30"
                    @click="emit('close')"
                  >
                    <div class="w-14 h-14 bg-zinc-800/50 rounded-xl flex items-center justify-center overflow-hidden border border-zinc-700/50 group-hover:border-zinc-600/50 transition-colors">
                      <img 
                        v-if="!brandImageError[brand.id]"
                        :src="getImageUrl(brand.logo_url || null, 'brand')"
                        :alt="brand.name"
                        class="w-10 h-10 object-contain"
                        @error="brandImageError[brand.id] = true"
                      />
                      <img 
                        v-else
                        :src="getImageUrl(null, 'brand')"
                        :alt="brand.name"
                        class="w-10 h-10 object-contain"
                      />
                    </div>
                    <span class="text-xs text-gray-400 text-center group-hover:text-white transition-colors font-medium">
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
