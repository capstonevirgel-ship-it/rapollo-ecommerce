<script setup lang="ts">
import { storeToRefs } from "pinia";
import { onMounted, reactive } from "vue";
import { useCategoryStore } from "~/stores/category";
import { useBrandStore } from "~/stores/brand";
import { getImageUrl } from "~/utils/imageHelper";

interface NavLink {
  path: string;
  label: string;
  icon: string;
}

const props = defineProps<{
  navLinks: NavLink[];
  activeCategory: string | null;
  toggleCategory: (category: string | null) => void;
}>();

// Pinia stores
const categoryStore = useCategoryStore();
const brandStore = useBrandStore();

const { categories, loading: categoryLoading } = storeToRefs(categoryStore);
const { brands, loading: brandLoading } = storeToRefs(brandStore);

// Track image load errors for brands
const brandImageError = reactive<Record<number, boolean>>({});

onMounted(async () => {
  try {
    await Promise.all([
      categoryStore.fetchCategories(),
      brandStore.fetchBrands()
    ]);
  } catch (error) {
    console.error('Error fetching data:', error);
  }
});
</script>

<template>
  <nav class="flex items-center space-x-8">
    <!-- Regular Nav Links -->
    <NuxtLink 
      v-for="link in navLinks" 
      :key="link.path" 
      :to="link.path" 
      class="text-gray-300 hover:text-white transition-colors flex items-center space-x-1"
      active-class="text-primary-600 font-medium"
    >
      <Icon :name="link.icon" class="text-lg" />
      <span>{{ link.label }}</span>
    </NuxtLink>

    <!-- Shop Dropdown -->
    <div class="relative group" @mouseenter="toggleCategory('shop')">
      <div 
        class="text-gray-300 hover:text-white transition-colors flex items-center space-x-1 cursor-pointer"
        @click="toggleCategory(activeCategory === 'shop' ? null : 'shop')"
      >
        <NuxtLink to="/shop" class="flex items-center space-x-1">
          <Icon name="mdi:shopping-outline" class="text-lg" />
          <span>Shop</span>
        </NuxtLink>
        <Icon 
          name="mdi:chevron-down" 
          class="text-sm transition-transform" 
          :class="{ 'rotate-180': activeCategory === 'shop' }" 
        />
      </div>

      <!-- Mega Menu -->
      <Transition name="mega-menu">
        <div 
          v-if="activeCategory === 'shop'"
          class="fixed left-0 w-full bg-white shadow-lg border-t border-gray-200 py-8 top-[175px] z-50"
          @mouseleave="toggleCategory(null)"
        >
          <div class="container mx-auto px-4">
            <!-- Loading State -->
            <div v-if="categoryLoading || brandLoading" class="space-y-8">
              <!-- Categories Loading -->
              <div class="grid grid-cols-6 gap-6">
                <div v-for="n in 6" :key="n" class="space-y-3 animate-pulse">
                  <div class="h-4 w-24 bg-gray-200 rounded"></div>
                  <div class="h-3 w-20 bg-gray-200 rounded"></div>
                  <div class="h-3 w-16 bg-gray-200 rounded"></div>
                </div>
              </div>
              <!-- Brands Loading -->
              <div class="border-t pt-6">
                <div class="h-4 w-16 bg-gray-200 rounded mb-4"></div>
                <div class="grid grid-cols-6 gap-4">
                  <div v-for="n in 6" :key="n" class="animate-pulse">
                    <div class="h-12 w-12 bg-gray-200 rounded"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Categories and Subcategories -->
            <div v-else-if="categories.length > 0" class="space-y-8">
              <div class="grid grid-cols-6 gap-6">
                <div v-for="category in categories" :key="category.id">
                  <!-- Category -->
                  <NuxtLink 
                    :to="`/shop/${category.slug}`" 
                    class="text-sm font-semibold text-gray-900 uppercase mb-3 hover:text-primary-600 block"
                    @click="toggleCategory(null)"
                  >
                    {{ category.name }}
                  </NuxtLink>

                  <!-- Subcategories -->
                  <ul class="space-y-2">
                    <li 
                      v-for="sub in (category.subcategories || [])" 
                      :key="sub.id"
                    >
                      <NuxtLink 
                        :to="`/shop/${category.slug}/${sub.slug}`" 
                        class="text-gray-600 hover:text-primary-600 block text-sm"
                        @click="toggleCategory(null)"
                      >
                        {{ sub.name }}
                      </NuxtLink>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Brands Section -->
              <div v-if="brands.length > 0" class="border-t pt-6">
                <h3 class="text-sm font-semibold text-gray-900 uppercase mb-4">Brands</h3>
                <div class="grid grid-cols-6 gap-4">
                  <div 
                    v-for="brand in brands.slice(0, 12)" 
                    :key="brand.id"
                    class="flex flex-col items-center group"
                  >
                    <NuxtLink 
                      :to="`/shop?brand=${brand.slug}`" 
                      class="flex flex-col items-center space-y-2 hover:opacity-80 transition-opacity"
                      @click="toggleCategory(null)"
                    >
                      <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                        <img 
                          v-if="!brandImageError[brand.id]"
                          :src="getImageUrl(brand.logo_url ?? null, 'brand')"
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

            <!-- No Data State -->
            <div v-else class="text-center text-gray-500 py-8">
              <p>No categories available</p>
              <p class="text-sm">Categories: {{ categories.length }}</p>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </nav>
</template>
