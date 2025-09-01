<!-- Admin Layout -->
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import Sidebar from '@/components/navigation/Sidebar.vue';

const contentPadding = ref('250px');
const isMobile = ref(false);

const handleSidebarWidthChange = (newWidth: string) => {
  if (!isMobile.value) {
    contentPadding.value = newWidth;
  }
};

const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768;
  if (isMobile.value) {
    contentPadding.value = '0';
  } else {
    const savedState = localStorage.getItem('sidebarCollapsed');
    contentPadding.value = savedState && JSON.parse(savedState) ? '74px' : '250px';
  }
};

onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);
});
</script>

<template>
  <div class="flex flex-col md:flex-row bg-[#FEFAFA] dark:bg-[#0B1215]">
    <div class="md:fixed md:h-full">
      <Sidebar @width-change="handleSidebarWidthChange" />
    </div>
    <div 
      class="w-full p-6 transition-all duration-300 ease-in-out"
      :class="{'md:ml-[250px]': !isMobile && contentPadding === '250px', 'md:ml-[74px]': !isMobile && contentPadding === '74px'}"
    >
      <div class="p-6 rounded shadow bg-[#FEFEFE]">
        <NuxtPage />
      </div>
    </div>
  </div>
</template>