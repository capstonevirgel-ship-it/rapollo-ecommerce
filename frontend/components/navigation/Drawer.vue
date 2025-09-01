<script setup lang="ts">
interface Subcategory {
  name: string;
  slug: string;
}

interface ShopCategory {
  name: string;
  slug: string;
  subcategories: Subcategory[];
}

const props = defineProps<{
  shopCategories: ShopCategory[];
  activeMobileCategory: string | null;
  isOpen: boolean;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'toggle-category', slug: string): void;
}>();
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
          <div 
            v-for="category in shopCategories" 
            :key="category.slug"
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
                  v-for="sub in category.subcategories"
                  :key="sub.slug"
                  :to="`/shop/${category.slug}/${sub.slug}`"
                  class="block py-2 px-2 text-sm text-gray-700 hover:text-primary-600 hover:bg-gray-100 rounded"
                  @click="emit('close')"
                >
                  {{ sub.name }}
                </NuxtLink>
              </div>
            </Transition>
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
