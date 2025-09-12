<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import Menu from '@/components/navigation/Menu.vue'
import Drawer from '@/components/navigation/Drawer.vue'
import { useAuthStore } from '~/stores/auth'

const isMenuOpen = ref(false)
const isShopDrawerOpen = ref(false)
const activeCategory = ref<string | null>(null)
const activeMobileCategory = ref<string | null>(null)

const authStore = useAuthStore()

const lastScrollY = ref(0)
const isHeaderVisible = ref(true)

const handleScroll = () => {
  const currentScrollY = window.scrollY
  isHeaderVisible.value = currentScrollY < lastScrollY.value || currentScrollY < 50
  lastScrollY.value = currentScrollY
}

onMounted(() => window.addEventListener('scroll', handleScroll))
onBeforeUnmount(() => window.removeEventListener('scroll', handleScroll))

const toggleCategory = (category: string | null) => {
  activeCategory.value = category
}

const toggleShopDrawer = () => {
  isShopDrawerOpen.value = !isShopDrawerOpen.value
  if (isShopDrawerOpen.value) isMenuOpen.value = false
}

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
  if (isMenuOpen.value) isShopDrawerOpen.value = false
}

interface NavLink {
  path: string
  label: string
  icon: string
}

const navLinks: NavLink[] = [
  { path: '/', label: 'Home', icon: 'mdi:home-outline' },
  { path: '/events', label: 'Events', icon: 'mdi:microphone-variant' },
  { path: '/about-us', label: 'About', icon: 'mdi:information-outline' },
  { path: '/contact', label: 'Contact', icon: 'mdi:email-outline' }
]
</script>

<template>
  <header
    class="bg-zinc-900 text-gray-100 shadow-sm sticky top-0 z-50 transition-transform duration-300"
    :class="{ '-translate-y-full': !isHeaderVisible }"
  >
    <div class="mx-auto py-3 px-4">
      <!-- Top Bar -->
      <div class="flex justify-between items-center mb-10">
        <div class="flex space-x-3 text-gray-300 hover:text-white">
          <a href="https://facebook.com" target="_blank">
            <Icon name="mdi:facebook" class="text-xl hover:text-primary-600" />
          </a>
          <a href="https://twitter.com" target="_blank">
            <Icon name="mdi:twitter" class="text-xl hover:text-primary-600" />
          </a>
          <a href="https://instagram.com" target="_blank">
            <Icon name="mdi:instagram" class="text-xl hover:text-primary-600" />
          </a>
        </div>
        <NuxtLink
          to="/find-store"
          class="text-base font-medium text-gray-300 hover:text-white flex items-center space-x-1"
        >
          <Icon name="mdi:map-marker-outline" />
          <span>Find Store</span>
        </NuxtLink>
      </div>

      <!-- Main Header Grid (Desktop) -->
      <div class="hidden md:grid grid-cols-3 items-center">
        <!-- Logo (Column 1) -->
        <div class="flex items-center">
          <NuxtLink to="/" class="flex items-center space-x-2">
            <Icon name="mdi-application" class="text-2xl text-primary-600" />
            <span class="text-xl font-bold text-gray-300 hover:text-white">YourLogo</span>
          </NuxtLink>
        </div>

        <!-- Navigation Links -->
        <div class="flex justify-center">
          <Menu
            :navLinks="navLinks"
            :activeCategory="activeCategory"
            :toggleCategory="toggleCategory"
          />
        </div>

        <!-- Cart + Auth + Theme -->
        <div class="flex justify-end items-center space-x-4">
          <NuxtLink to="/cart" aria-label="Cart">
            <Icon name="mdi:cart-outline" class="text-2xl hover:text-primary-600" />
          </NuxtLink>
          
          <!-- Theme Toggle -->
          
          <!-- Authenticated User -->
          <div v-if="authStore.isAuthenticated" class="flex items-center space-x-4">
            <NuxtLink to="/my-tickets" class="text-base text-gray-300 hover:text-white">
              My Tickets
            </NuxtLink>
            <NuxtLink v-if="authStore.user?.role === 'admin'" to="/admin/events" class="text-base text-gray-300 hover:text-white">
              Admin
            </NuxtLink>
            <span class="text-base text-gray-300">Welcome, {{ authStore.user?.user_name }}</span>
            <button 
              @click="authStore.logout()" 
              class="text-base text-gray-300 hover:text-white"
            >
              Logout
            </button>
          </div>
          
          <!-- Guest User -->
          <div v-else class="flex items-center space-x-4">
            <NuxtLink to="/login" class="text-base text-gray-300 hover:text-white">Sign In</NuxtLink>
            <NuxtLink to="/register" class="text-base text-gray-300 hover:text-white">Register</NuxtLink>
          </div>
        </div>
      </div>

      <!-- Mobile Header -->
      <div class="flex md:hidden justify-between items-center">
        <NuxtLink to="/" class="flex items-center space-x-2 z-50">
          <Icon name="mdi-application" class="text-2xl text-primary-600" />
          <span class="text-xl font-bold text-gray-300 hover:text-white">YourLogo</span>
        </NuxtLink>

        <div class="flex items-center space-x-4">
          <button @click="toggleShopDrawer" aria-label="Open shop">
            <Icon name="mdi:shopping-outline" class="text-2xl" />
          </button>
          <button @click="toggleMenu" aria-label="Toggle menu">
            <Icon :name="isMenuOpen ? 'mdi:close' : 'mdi:menu'" class="text-2xl" />
          </button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <Transition name="mega-menu">
        <div v-if="isMenuOpen" class="md:hidden mt-4 pb-4 space-y-3">
          <NuxtLink
            v-for="link in navLinks"
            :key="`mobile-${link.path}`"
            :to="link.path"
            @click="isMenuOpen = false"
            class="px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-50 rounded-lg transition-colors flex items-center space-x-2"
            active-class="text-zinc-900 font-medium bg-gray-50"
          >
            <Icon :name="link.icon" class="text-lg" />
            <span>{{ link.label }}</span>
          </NuxtLink>
          
          <!-- Mobile Auth + Theme -->
          <div class="border-t border-gray-700 pt-3 mt-3">
            <!-- Theme Toggle for Mobile -->
            <div class="px-4 py-2 flex items-center justify-between">
              <span class="text-gray-300">Theme</span>
            </div>
            
            <div v-if="authStore.isAuthenticated" class="px-4 py-2">
              <span class="text-gray-300">Welcome, {{ authStore.user?.user_name }}</span>
              <div class="mt-2 space-y-2">
                <NuxtLink
                  to="/my-tickets"
                  @click="isMenuOpen = false"
                  class="block text-gray-300 hover:text-white"
                >
                  My Tickets
                </NuxtLink>
                <NuxtLink
                  v-if="authStore.user?.role === 'admin'"
                  to="/admin/events"
                  @click="isMenuOpen = false"
                  class="block text-gray-300 hover:text-white"
                >
                  Admin Panel
                </NuxtLink>
                <button 
                  @click="authStore.logout(); isMenuOpen = false" 
                  class="block w-full text-left text-gray-300 hover:text-white"
                >
                  Logout
                </button>
              </div>
            </div>
            <div v-else class="space-y-2">
              <NuxtLink
                to="/login"
                @click="isMenuOpen = false"
                class="px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-50 rounded-lg transition-colors flex items-center space-x-2"
              >
                <Icon name="mdi:login" class="text-lg" />
                <span>Sign In</span>
              </NuxtLink>
              <NuxtLink
                to="/register"
                @click="isMenuOpen = false"
                class="px-4 py-2 text-gray-300 hover:text-white hover:bg-gray-50 rounded-lg transition-colors flex items-center space-x-2"
              >
                <Icon name="mdi:account-plus" class="text-lg" />
                <span>Register</span>
              </NuxtLink>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </header>

  <!-- Drawer Navigation -->
  <Drawer
    :isOpen="isShopDrawerOpen"
    :activeMobileCategory="activeMobileCategory"
    @close="isShopDrawerOpen = false"
    @toggle-category="slug => activeMobileCategory = activeMobileCategory === slug ? null : slug"
  />
</template>

<style scoped>
.mega-menu-enter-active,
.mega-menu-leave-active {
  transition: all 0.2s ease;
}
.mega-menu-enter-from,
.mega-menu-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
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
