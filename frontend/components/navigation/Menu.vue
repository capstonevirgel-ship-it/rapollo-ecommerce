<script setup lang="ts">
import { storeToRefs } from "pinia";
import { onMounted } from "vue";
import { useCategoryStore } from "~/stores/category";
import { useSubcategoryStore } from "~/stores/subcategory";

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
const subcategoryStore = useSubcategoryStore();

const { categories, loading: catLoading } = storeToRefs(categoryStore);
const { subcategories, loading: subLoading } = storeToRefs(subcategoryStore);

onMounted(async () => {
  if (!categories.value.length) await categoryStore.fetchCategories();
  if (!subcategories.value.length) await subcategoryStore.fetchSubcategories();
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
          class="fixed left-0 w-full bg-white shadow-lg border-t border-gray-200 py-8 top-[119.5px] z-50"
          @mouseleave="toggleCategory(null)"
        >
          <div class="container mx-auto px-4">
            <!-- Skeleton Loader -->
            <div v-if="catLoading || subLoading" class="grid grid-cols-6 gap-6">
              <div v-for="n in 6" :key="n" class="space-y-3 animate-pulse">
                <div class="h-4 w-24 bg-gray-200 rounded"></div>
                <div class="h-3 w-20 bg-gray-200 rounded"></div>
                <div class="h-3 w-16 bg-gray-200 rounded"></div>
              </div>
            </div>

            <!-- Actual Categories/Subcategories -->
            <div v-else class="grid grid-cols-6 gap-6">
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
                    v-for="sub in subcategories.filter(s => s.category_id === category.id)" 
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
          </div>
        </div>
      </Transition>
    </div>
  </nav>
</template>
