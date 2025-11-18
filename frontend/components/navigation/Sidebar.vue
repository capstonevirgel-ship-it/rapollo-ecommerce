<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { useAuthStore } from '@/stores/auth';

const emit = defineEmits(['width-change']);
const authStore = useAuthStore();

// Sidebar state
const isCollapsed = ref(false);
const isMobile = ref(false);
const mobileMenuOpen = ref(false);
const openDropdowns = ref<Record<string, boolean>>({});

// Menu items
const menuItems = ref([
  { link: '/admin/dashboard', label: 'Dashboard', icon: 'view-dashboard' },
  { link: '/admin/orders', label: 'Orders', icon: 'clipboard-list' },
  {
    label: 'Catalog',
    icon: 'shopping',
    children: [
      { link: '/admin/products', label: 'Products' },
      { link: '/admin/categories', label: 'Categories' },
      { link: '/admin/subcategories', label: 'Subcategories' },
      { link: '/admin/brands', label: 'Brands' }
    ]
  },
  { link: '/admin/events', label: 'Events', icon: 'calendar' },
  { link: '/admin/tickets', label: 'Tickets', icon: 'ticket' },
  { link: '/admin/notifications', label: 'Notifications', icon: 'bell' },
  { link: '/admin/settings', label: 'Settings', icon: 'cog' }
]);

// Computed properties
const sidebarWidth = computed(() => isCollapsed.value ? '74px' : '250px');

// Methods
const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value;
  localStorage.setItem('sidebarCollapsed', JSON.stringify(isCollapsed.value));
  emit('width-change', sidebarWidth.value);
};

const toggleDropdown = (label: string) => {
  openDropdowns.value[label] = !openDropdowns.value[label];
};

const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
};

const handleClickOutside = (event: MouseEvent) => {
  if (!mobileMenuOpen.value) return;
  
  const target = event.target as HTMLElement;
  if (!target.closest('.mobile-menu') && !target.closest('.mobile-menu-button')) {
    closeMobileMenu();
  }
};

const handleResize = () => {
  const width = window.innerWidth;
  isMobile.value = width < 1024;
  if (isMobile.value) {
    emit('width-change', '0');
    mobileMenuOpen.value = false;
  } else {
    emit('width-change', sidebarWidth.value);
  }
};

const handleLogout = () => {
  authStore.logout();
};

// Watch for mobile menu open/close to add/remove click listener
watch(mobileMenuOpen, (isOpen) => {
  if (process.client) {
    if (isOpen) {
      // Use nextTick to ensure the menu is rendered before adding listener
      nextTick(() => {
        document.addEventListener('click', handleClickOutside);
      });
    } else {
      document.removeEventListener('click', handleClickOutside);
    }
  }
});

// Initialize sidebar state from localStorage
onMounted(() => {
  // Check if mobile (using 1024px breakpoint for desktop)
  isMobile.value = window.innerWidth < 1024;
  
  // Load collapsed state from localStorage
  const savedState = localStorage.getItem('sidebarCollapsed');
  if (savedState !== null) {
    isCollapsed.value = JSON.parse(savedState);
  }
  
  // Emit initial width
  emit('width-change', isMobile.value ? '0' : sidebarWidth.value);
  
  // Add resize listener
  window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize);
  document.removeEventListener('click', handleClickOutside);
});

defineExpose({
  isCollapsed,
  width: sidebarWidth,
  toggleMobileMenu: () => { mobileMenuOpen.value = !mobileMenuOpen.value; },
  closeMobileMenu,
  mobileMenuOpen
});
</script>

<template>
  <div>
    <!-- Desktop Sidebar -->
    <div
      v-if="!isMobile"
      class="bg-gray-800 transition-all fixed duration-300 ease-in-out overflow-hidden shadow z-40"
      :style="{ 
        width: sidebarWidth,
        height: 'calc(100vh - 48px)',
        top: '48px'
      }"
    >
      <aside class="h-full flex flex-col" :class="isCollapsed ? 'px-2' : 'px-4'">
        <!-- Header Section -->
        <div class="flex-shrink-0 py-6">
          <div 
            class="flex items-center mb-6 text-white" 
            :class="isCollapsed ? 'justify-center' : 'justify-between'"
          >
            <h2 v-if="!isCollapsed" class="text-lg font-semibold">Menu</h2>
            <div class="flex items-center gap-2">
              <button 
                @click="toggleSidebar"
                class="cursor-pointer py-2 px-2 rounded hover:bg-gray-700 flex items-center transition-colors duration-200"
              >
                <Icon :name="isCollapsed ? 'mdi:arrow-right' : 'mdi:arrow-left'" class="text-lg" />
              </button>
            </div>
          </div>
        </div>

        <!-- Navigation Section -->
        <nav class="flex-1 overflow-y-auto sidebar-scrollbar">
          <ul class="space-y-2">
            <li v-for="item in menuItems" :key="item.label" class="text-white">
              <div v-if="item.children">
                <button
                  @click="toggleDropdown(item.label)"
                  class="flex items-center w-full hover:bg-gray-700 rounded transition-all duration-200"
                  :class="isCollapsed ? 'justify-center py-3 px-0' : 'justify-start py-3 px-3'" 
                >
                  <Icon :name="`mdi:${item.icon}`" class="text-xl flex-shrink-0"/>
                  <span v-if="!isCollapsed" class="ml-3 flex-1 text-left">{{ item.label }}</span>
                  <Icon v-if="!isCollapsed" :name="`mdi:${openDropdowns[item.label] ? 'chevron-up' : 'chevron-down'}`" class="text-lg flex-shrink-0" />
                </button>
                <ul v-show="openDropdowns[item.label]" class="ml-6 mt-1 space-y-1" v-if="!isCollapsed">
                  <li v-for="child in item.children" :key="child.link">
                    <NuxtLink
                      :to="child.link"
                      class="flex items-center px-3 py-2 hover:bg-gray-700 hover:text-white rounded transition-colors duration-200"
                      active-class="bg-gray-200 text-gray-900"
                    >
                      <span>{{ child.label }}</span>
                    </NuxtLink>
                  </li>
                </ul>
              </div>

              <div v-else>
                <NuxtLink 
                  :to="item.link" 
                  class="flex items-center hover:bg-gray-700 transition-colors duration-200 rounded"
                  :class="isCollapsed ? 'justify-center py-3 px-0' : 'justify-start py-3 px-3'"
                  active-class="rounded bg-gray-200 text-gray-900" 
                >
                  <Icon :name="`mdi:${item.icon}`" class="text-xl flex-shrink-0" />
                  <span v-if="!isCollapsed" class="ml-3">{{ item.label }}</span>
                </NuxtLink>
              </div>
            </li>
          </ul>
        </nav>

        <!-- Footer Section -->
        <div class="flex-shrink-0 py-6">
          <hr class="bg-gray-600 mb-4" />
          <div :class="isCollapsed ? 'flex justify-center' : 'flex items-center gap-3'">

          </div>
          <button 
            class="bg-gray-200 text-gray-900 w-full py-3 rounded text-base items-center flex cursor-pointer hover:bg-gray-300 transition-colors duration-200 mt-3"
            :class="isCollapsed ? 'justify-center px-0' : 'justify-center px-3'"
            @click="handleLogout"
          >
            <Icon name="mdi:exit-to-app" class="text-lg" />
            <span v-if="!isCollapsed" class="ml-2">Logout</span>
          </button>
        </div>
      </aside>
    </div>

    <!-- Mobile Menu Backdrop -->
    <div
      v-if="isMobile && mobileMenuOpen"
      class="fixed inset-0 bg-black/50 z-[55]"
      :style="{ top: '48px' }"
      @click="closeMobileMenu"
    ></div>

    <!-- Mobile Dropdown Menu -->
    <div 
      v-if="isMobile && mobileMenuOpen" 
      class="mobile-menu bg-gray-800 text-white shadow-lg px-4 py-2 fixed left-0 right-0 z-[55] max-h-[calc(100vh-48px)] overflow-y-auto sidebar-scrollbar border-t border-gray-700"
      :style="{ top: '64px' }"
    >
      <nav>
        <ul class="flex flex-col gap-3 pb-2">
          <li v-for="item in menuItems" :key="item.label">
            <div v-if="item.children">
              <button
                @click="toggleDropdown(item.label)"
                class="flex items-center gap-3 py-3 px-3 w-full rounded hover:bg-gray-700 transition-colors duration-200"
              >
                <Icon :name="`mdi:${item.icon}`" class="text-xl" />
                <span class="text-lg">{{ item.label }}</span>
                <Icon :name="`mdi:${openDropdowns[item.label] ? 'chevron-up' : 'chevron-down'}`" class="ml-auto text-lg" />
              </button>
              <ul v-show="openDropdowns[item.label]" class="ml-6 mt-1 space-y-1">
                <li v-for="child in item.children" :key="child.link">
                  <NuxtLink
                    :to="child.link"
                    class="flex items-center py-2 px-2 hover:bg-gray-700 rounded"
                    active-class="bg-gray-200 text-gray-900"
                    @click="mobileMenuOpen = false"
                  >
                    <span>{{ child.label }}</span>
                  </NuxtLink>
                </li>
              </ul>
            </div>
            <div v-else>
              <NuxtLink
                :to="item.link"
                class="flex items-center gap-3 py-3 px-3 rounded hover:bg-gray-700 transition-colors duration-200"
                active-class="bg-gray-200 text-gray-900"
                @click="mobileMenuOpen = false"
              >
                <Icon :name="`mdi:${item.icon}`" class="text-xl" />
                <span class="text-lg">{{ item.label }}</span>
              </NuxtLink>
            </div>
          </li>

          <li>
            <hr class="my-2 border-gray-700" />
            <div class="px-3 py-2">
              <button 
                class="bg-gray-200 text-gray-900 w-full py-3 rounded flex items-center justify-center mt-2 hover:bg-gray-300 transition-colors duration-200"
                @click="() => { mobileMenuOpen = false; handleLogout() }"
              >
                <Icon name="mdi:exit-to-app" class="text-xl" />
                <span class="ml-2 text-lg">Logout</span>
              </button>
            </div>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>