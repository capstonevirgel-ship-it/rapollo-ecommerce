<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useAuthStore } from '@/stores/auth';

const emit = defineEmits(['width-change']);
const authStore = useAuthStore();

const collapse = ref(false);
const isMobile = ref(false);
const mobileMenuOpen = ref(false);
const showComponent = ref(false);
const openDropdowns = ref<Record<string, boolean>>({});

const sidebarWidth = computed(() => (collapse.value ? '74px' : '250px'));

const toggleSidebar = () => {
  collapse.value = !collapse.value;
  localStorage.setItem('sidebarCollapsed', JSON.stringify(collapse.value));
  updateWidth();
};

const updateWidth = () => {
  const newWidth = collapse.value ? '74px' : '250px';
  if (!isMobile.value) emit('width-change', newWidth);
};

const handleResize = () => {
  isMobile.value = window.innerWidth <= 768;
  if (!isMobile.value) {
    updateWidth();
    mobileMenuOpen.value = false;
  } else {
    emit('width-change', '0');
  }
};

const toggleDropdown = (label: string) => {
  openDropdowns.value[label] = !openDropdowns.value[label];
};

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
  { link: '/admin/users', label: 'Users', icon: 'account-group' },
  { link: '/admin/settings', label: 'Settings', icon: 'cog' }
]);

if (process.client) {
  onMounted(() => {
    isMobile.value = window.innerWidth <= 768;
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState !== null) collapse.value = JSON.parse(savedState);
    updateWidth();
    window.addEventListener('resize', handleResize);
    showComponent.value = true;
  });

  onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
  });
}

function handleLogout() {
  authStore.logout();
}

defineExpose({
  isCollapsed: collapse,
  width: sidebarWidth
});
</script>

<template>
  <div v-show="showComponent">
    <!-- Desktop Sidebar -->
    <div
      v-if="!isMobile"
      class="bg-[#101720] transition-all fixed duration-300 h-full ease-in-out overflow-hidden shadow z-40"
      :style="{ width: sidebarWidth }"
    >
      <aside class="px-4 py-6 h-full">
        <div class="flex flex-col justify-between h-full">
          <div>
            <div 
              class="flex items-center mb-6 text-[#FAFAFA]" 
              :class="collapse ? 'justify-center' : 'justify-between'"
            >
              <h2 v-if="!collapse" class="pl-2">Menu</h2>
              <button 
                @click="toggleSidebar"
                class="cursor-pointer py-2 px-2 rounded hover:bg-gray-100 hover:text-[#101720] flex items-center"
              >
                <Icon :name="collapse ? 'mdi:chevron-right' : 'mdi:chevron-left'" />
              </button>
            </div>

            <nav>
              <ul>
                <li v-for="item in menuItems" :key="item.label" class="text-[#FAFAFA] text-base/10 mb-2">
                  <div v-if="item.children">
                    <button
                      @click="toggleDropdown(item.label)"
                      class="flex items-center w-full px-2 hover:bg-gray-800 rounded transition-all"
                      :class="collapse ? 'py-2' : ''" 
                    >
                      <Icon :name="`mdi:${item.icon}`"/>
                      <span v-if="!collapse" class="ml-3 flex-1 text-left">{{ item.label }}</span>
                      <Icon v-if="!collapse" :name="`mdi:${openDropdowns[item.label] ? 'chevron-up' : 'chevron-down'}`" />
                    </button>
                    <ul v-show="openDropdowns[item.label]" class="ml-6 mt-2 space-y-1" v-if="!collapse">
                      <li v-for="child in item.children" :key="child.link">
                        <NuxtLink
                          :to="child.link"
                          class="flex items-center px-3 hover:bg-gray-700 hover:text-white rounded transition-colors duration-200"
                          active-class="bg-gray-100 text-[#101720]"
                        >
                          <span>{{ child.label }}</span>
                        </NuxtLink>
                      </li>
                    </ul>
                  </div>

                  <div v-else>
                    <NuxtLink 
                      :to="item.link" 
                      :class="collapse ? 'py-2' : ''" 
                      active-class="rounded bg-gray-100 text-[#101720]" 
                      class="px-2 flex items-center hover:bg-gray-800 transition-colors duration-200"
                    >
                      <Icon :name="`mdi:${item.icon}`" />
                      <span v-if="!collapse" class="ml-3">{{ item.label }}</span>
                    </NuxtLink>
                  </div>
                </li>
              </ul>
            </nav>
          </div>

          <div class="flex flex-col gap-4">
            <hr class="bg-gray-600" />
            <div :class="collapse ? 'flex justify-center' : 'flex items-center gap-3'">
              <span v-if="!collapse" class="text-[#FAFAFA]">Hello, Admin!</span>
            </div>
            <button 
              class="bg-gray-100 w-full py-2 rounded text-base items-center flex justify-center cursor-pointer hover:bg-gray-300 transition-colors duration-200"
              @click="handleLogout"
            >
              <Icon name="mdi:exit-to-app" />
              <span v-if="!collapse" class="ml-2">Logout</span>
            </button>
          </div>
        </div>
      </aside>
    </div>

    <!-- Mobile Header -->
    <div 
      v-else
      class="w-full bg-[#101720] text-white px-4 py-3 flex justify-between items-center shadow fixed top-0 left-0 z-50"
    >
      <h2 class="text-lg font-semibold">Admin Panel</h2>
      <button 
        @click="mobileMenuOpen = !mobileMenuOpen"
        class="p-1 rounded-md hover:bg-gray-800 transition-colors duration-200"
      >
        <Icon :name="mobileMenuOpen ? 'mdi:close' : 'mdi:menu'" class="text-2xl" />
      </button>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div 
      v-if="isMobile && mobileMenuOpen" 
      class="bg-[#101720] text-white shadow px-4 py-2 fixed top-16 left-0 right-0 z-40 max-h-[calc(100vh-64px)] overflow-y-auto border-t border-gray-700"
    >
      <nav>
        <ul class="flex flex-col gap-3 pb-2">
          <li v-for="item in menuItems" :key="item.label">
            <div v-if="item.children">
              <button
                @click="toggleDropdown(item.label)"
                class="flex items-center gap-3 py-3 px-3 w-full rounded hover:bg-gray-800 transition-colors duration-200"
              >
                <Icon :name="`mdi:${item.icon}`" class="text-xl" />
                <span class="text-lg">{{ item.label }}</span>
                <Icon :name="`mdi:${openDropdowns[item.label] ? 'chevron-up' : 'chevron-down'}`" class="ml-auto text-lg" />
              </button>
              <ul v-show="openDropdowns[item.label]" class="ml-6 mt-1 space-y-1">
                <li v-for="child in item.children" :key="child.link">
                  <NuxtLink
                    :to="child.link"
                    class="flex items-center py-2 px-2 hover:bg-gray-800 rounded"
                    active-class="bg-gray-100 text-[#101720]"
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
                class="flex items-center gap-3 py-3 px-3 rounded hover:bg-gray-800 transition-colors duration-200"
                active-class="bg-gray-100 text-[#101720]"
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
              <span class="block text-gray-300">Hello, Admin!</span>
              <button 
                class="bg-gray-100 text-[#101720] w-full py-3 rounded flex items-center justify-center mt-2 hover:bg-gray-300 transition-colors duration-200"
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
