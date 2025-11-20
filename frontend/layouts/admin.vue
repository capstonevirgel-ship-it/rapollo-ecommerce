<!-- Admin Layout -->
<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, provide } from 'vue';
import Sidebar from '@/components/navigation/Sidebar.vue';

const contentPadding = ref('250px');
const isMobile = ref(false);
const isTablet = ref(false);
const sidebarRef = ref<InstanceType<typeof Sidebar> | null>(null);

// Provide mobile menu toggle function for AdminHeader
provide('toggleMobileMenu', () => {
  if (sidebarRef.value && typeof sidebarRef.value.toggleMobileMenu === 'function') {
    sidebarRef.value.toggleMobileMenu();
  }
});

// Provide isMobile state from Sidebar to AdminHeader
const sidebarIsMobile = computed(() => {
  return sidebarRef.value?.isMobile ?? false;
});
provide('sidebarIsMobile', sidebarIsMobile);

const handleSidebarWidthChange = (newWidth: string) => {
  if (!isMobile.value && !isTablet.value) {
    contentPadding.value = newWidth;
  }
};

const checkViewport = () => {
  if (process.client) {
    const width = window.innerWidth;
    isMobile.value = width < 640;
    isTablet.value = width >= 640 && width < 1024;
    
    if (isMobile.value || isTablet.value) {
      contentPadding.value = '0';
    }
  }
};

onMounted(() => {
  checkViewport();
  if (process.client) {
    window.addEventListener('resize', checkViewport);
  }
});

onBeforeUnmount(() => {
  if (process.client) {
    window.removeEventListener('resize', checkViewport);
  }
});
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50 overflow-x-hidden">
    <AdminHeader />
    <div class="flex flex-col lg:flex-row flex-1">
      <div class="lg:fixed lg:h-full">
        <Sidebar ref="sidebarRef" @width-change="handleSidebarWidthChange" />
      </div>
      <div 
        class="w-full transition-all duration-300 ease-in-out"
        :class="{
          'pt-12': isMobile || isTablet,
          'lg:pt-12': !isMobile && !isTablet,
          'lg:ml-[250px]': !isMobile && !isTablet && contentPadding === '250px', 
          'lg:ml-[74px]': !isMobile && !isTablet && contentPadding === '74px'
        }"
      >
        <div class="p-4 sm:p-6 md:p-8">
          <NuxtPage></NuxtPage>
        </div>
      </div>
    </div>
    
    <!-- Global Alert Component -->
    <Alert />
  </div>
</template>