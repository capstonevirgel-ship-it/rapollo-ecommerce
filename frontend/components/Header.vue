<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import Menu from '@/components/navigation/Menu.vue'
import Drawer from '@/components/navigation/Drawer.vue'
import { useAuthStore } from '~/stores/auth'
import { useCartStore } from '~/stores/cart'
import { useNotificationStore } from '~/stores/notification'
import { useSettingsStore } from '~/stores/settings'
import { getImageUrl } from '~/utils/imageHelper'
import NotificationDropdown from '~/components/NotificationDropdown.vue'

const isMenuOpen = ref(false)
const isShopDrawerOpen = ref(false)
const isProfileDrawerOpen = ref(false)
const activeCategory = ref<string | null>(null)
const activeMobileCategory = ref<string | null>(null)

const authStore = useAuthStore()
const cartStore = useCartStore()
const notificationStore = useNotificationStore()
const settingsStore = useSettingsStore()

const lastScrollY = ref(0)
const isHeaderVisible = ref(true)

// Load notifications when user is authenticated
watch(() => authStore.user, async (user) => {
  if (user && authStore.isAuthenticated && process.client) {
    try {
      await notificationStore.fetchNotifications()
      await notificationStore.fetchUnreadCount()
    } catch (error) {
      console.error('Failed to load notifications:', error)
      // Don't throw the error, just log it to prevent breaking the UI
    }
  }
}, { immediate: true })

const handleScroll = () => {
  // Don't hide header when mobile menu is open
  if (isMenuOpen.value) {
    isHeaderVisible.value = true
    return
  }
  
  const currentScrollY = window.scrollY
  isHeaderVisible.value = currentScrollY < lastScrollY.value || currentScrollY < 50
  lastScrollY.value = currentScrollY
}

onMounted(async () => {
  window.addEventListener('scroll', handleScroll)
  // Load settings and cart data
  await settingsStore.fetchSettings()
  if (authStore.isAuthenticated) {
    await cartStore.index()
  }
})
onBeforeUnmount(() => window.removeEventListener('scroll', handleScroll))

// Watch for authentication changes to sync cart data
watch(() => authStore.isAuthenticated, async (isAuthenticated) => {
  if (isAuthenticated) {
    await cartStore.index()
  } else {
    // Clear cart when user logs out
    cartStore.cart = []
  }
})

// Notification handlers
const handleMarkAsRead = async (id: number) => {
  try {
    await notificationStore.markAsRead(id)
  } catch (error) {
    console.error('Failed to mark notification as read:', error)
  }
}

const handleDeleteNotification = async (id: number) => {
  try {
    await notificationStore.deleteNotification(id)
  } catch (error) {
    console.error('Failed to delete notification:', error)
  }
}

const handleMarkAllAsRead = async () => {
  try {
    await notificationStore.markAllAsRead()
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error)
  }
}

const toggleCategory = (category: string | null) => {
  activeCategory.value = category
}

const toggleShopDrawer = () => {
  isShopDrawerOpen.value = !isShopDrawerOpen.value
  if (isShopDrawerOpen.value) {
    isMenuOpen.value = false
    isProfileDrawerOpen.value = false
  }
}

const toggleProfileDrawer = () => {
  isProfileDrawerOpen.value = !isProfileDrawerOpen.value
  if (isProfileDrawerOpen.value) {
    isMenuOpen.value = false
    isShopDrawerOpen.value = false
  }
}

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
  if (isMenuOpen.value) {
    isShopDrawerOpen.value = false
    isProfileDrawerOpen.value = false
  }
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
    <div class="mx-auto max-w-[88rem] py-3 px-4">
      <!-- Top Bar -->
      <div class="flex justify-between items-center mb-4">
        <div class="flex space-x-3 text-gray-300 hover:text-white">
          <a 
            v-if="settingsStore.contactFacebook"
            :href="settingsStore.contactFacebook" 
            target="_blank"
            rel="noopener noreferrer"
          >
            <Icon name="mdi:facebook" class="text-xl hover:text-primary-600" />
          </a>
          <a 
            v-if="settingsStore.contactTwitter"
            :href="settingsStore.contactTwitter" 
            target="_blank"
            rel="noopener noreferrer"
          >
            <Icon name="mdi:twitter" class="text-xl hover:text-primary-600" />
          </a>
          <a 
            v-if="settingsStore.contactInstagram"
            :href="settingsStore.contactInstagram" 
            target="_blank"
            rel="noopener noreferrer"
          >
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
      <div class="hidden lg:grid grid-cols-3 items-center">
        <!-- Logo (Column 1) -->
        <div class="flex items-center">
          <NuxtLink to="/" class="flex items-center space-x-2">
            <img 
              v-if="settingsStore.siteLogo" 
              :src="getImageUrl(settingsStore.siteLogo, 'default')" 
              :alt="settingsStore.siteName || 'Rapollo E-Commerce'"
              class="w-24 h-24 object-contain"
            />
            <Icon v-else name="mdi-application" class="text-2xl text-primary-600" />
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
          <!-- Cart (hidden for admin) -->
          <NuxtLink v-if="!authStore.isAdmin" to="/cart" aria-label="Cart" class="relative">
            <Icon name="mdi:cart-outline" class="text-2xl hover:text-primary-600" />
            <span 
              v-if="cartStore.cartCount > 0" 
              class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"
            >
              {{ cartStore.cartCount > 99 ? '99+' : cartStore.cartCount }}
            </span>
          </NuxtLink>
          
          <!-- Notifications (hidden for admin, shown in admin header instead) -->
          <NotificationDropdown
            v-if="authStore.isAuthenticated && !authStore.isAdmin"
            :notifications="[...notificationStore.notifications]"
            view-all-url="/notifications"
            @mark-as-read="handleMarkAsRead"
            @delete="handleDeleteNotification"
            @mark-all-as-read="handleMarkAllAsRead"
          />
          
          <!-- Theme Toggle -->
          
          <!-- Authenticated User (hidden for admin) -->
          <div v-if="authStore.isAuthenticated && !authStore.isAdmin" class="flex items-center space-x-4">
            <button 
              @click="toggleProfileDrawer"
              class="flex items-center space-x-2 p-1 rounded-full hover:bg-gray-800 transition-colors"
            >
              <img 
                src="/uploads/avatar_placeholder.png" 
                :alt="authStore.user?.user_name || 'User'"
                class="w-8 h-8 rounded-full object-cover border-2 border-gray-600 hover:border-white transition-colors"
              />
            </button>
          </div>
          
          <!-- Guest User -->
          <div v-else-if="!authStore.isAuthenticated" class="flex items-center space-x-4">
            <NuxtLink to="/login" class="text-base text-gray-300 hover:text-white">Sign In</NuxtLink>
            <NuxtLink to="/register" class="text-base text-gray-300 hover:text-white">Register</NuxtLink>
          </div>
        </div>
      </div>

      <!-- Mobile Header -->
      <div class="flex lg:hidden justify-between items-center">
        <NuxtLink to="/" class="flex items-center space-x-2 z-50">
          <img 
            v-if="settingsStore.siteLogo" 
            :src="getImageUrl(settingsStore.siteLogo, 'default')" 
            :alt="settingsStore.siteName || 'Rapollo E-Commerce'"
            class="w-8 h-8 object-contain"
          />
          <Icon v-else name="mdi-application" class="text-2xl text-primary-600" />
          <span class="text-xl font-bold text-gray-300 hover:text-white font-winner-extra-bold">{{ settingsStore.siteName || 'Rapollo E-Commerce' }}</span>
        </NuxtLink>

        <div class="flex items-center space-x-4">
          <button @click="toggleShopDrawer" aria-label="Open shop">
            <Icon name="mdi:shopping-outline" class="text-2xl" />
          </button>
          <NuxtLink to="/cart" aria-label="Cart" class="relative">
            <Icon name="mdi:cart-outline" class="text-2xl" />
            <span 
              v-if="cartStore.cartCount > 0" 
              class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"
            >
              {{ cartStore.cartCount > 99 ? '99+' : cartStore.cartCount }}
            </span>
          </NuxtLink>
          <button @click="toggleMenu" aria-label="Toggle menu">
            <Icon :name="isMenuOpen ? 'mdi:close' : 'mdi:menu'" class="text-2xl" />
          </button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <Transition 
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 transform -translate-y-2"
        enter-to-class="opacity-100 transform translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 transform translate-y-0"
        leave-to-class="opacity-0 transform -translate-y-2"
      >
        <div v-if="isMenuOpen" class="lg:hidden fixed inset-0 top-[5.8rem] bg-zinc-900/95 backdrop-blur-md z-40 overflow-y-auto">
          <div class="px-6 py-6 space-y-4">
            <!-- Navigation Links -->
            <div class="space-y-1">
              <NuxtLink
                v-for="link in navLinks"
                :key="`mobile-${link.path}`"
                :to="link.path"
                @click="isMenuOpen = false"
                class="flex items-center space-x-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-zinc-800/50 rounded-xl transition-all duration-200 group"
                active-class="text-white bg-zinc-800/50 font-winner-extra-bold"
              >
                <div class="w-8 h-8 bg-zinc-800/50 rounded-lg flex items-center justify-center group-hover:bg-zinc-700/50 transition-colors">
                  <Icon :name="link.icon" class="text-lg" />
                </div>
                <span class="text-base font-medium">{{ link.label }}</span>
              </NuxtLink>
            </div>
            
            <!-- Divider -->
            <div class="border-t border-zinc-700/50 my-4"></div>
            
            <!-- User Section -->
            <div class="space-y-2">
              <div v-if="authStore.isAuthenticated" class="space-y-3">
                <!-- User Profile -->
                <div class="flex items-center space-x-3 px-4 py-3 bg-zinc-800/30 rounded-xl">
                  <img 
                    src="/uploads/avatar_placeholder.png" 
                    :alt="authStore.user?.user_name || 'User'"
                    class="w-10 h-10 rounded-full object-cover border-2 border-zinc-700"
                  />
                  <div class="flex-1">
                    <p class="text-white font-medium text-base">{{ authStore.user?.user_name }}</p>
                    <p class="text-gray-400 text-sm">{{ authStore.user?.email }}</p>
                  </div>
                </div>
                
                <!-- User Menu -->
                <div class="space-y-1">
                  <NuxtLink
                    to="/my-tickets"
                    @click="isMenuOpen = false"
                    class="flex items-center space-x-3 px-4 py-2 text-gray-300 hover:text-white hover:bg-zinc-800/50 rounded-xl transition-all duration-200 group"
                  >
                    <div class="w-7 h-7 bg-zinc-800/50 rounded-lg flex items-center justify-center">
                      <Icon name="mdi:ticket-outline" class="text-base" />
                    </div>
                    <span class="font-medium text-sm">My Tickets</span>
                  </NuxtLink>
                  <NuxtLink
                    to="/my-orders"
                    @click="isMenuOpen = false"
                    class="flex items-center space-x-3 px-4 py-2 text-gray-300 hover:text-white hover:bg-zinc-800/50 rounded-xl transition-all duration-200 group"
                  >
                    <div class="w-7 h-7 bg-zinc-800/50 rounded-lg flex items-center justify-center">
                      <Icon name="mdi:package-variant" class="text-base" />
                    </div>
                    <span class="font-medium text-sm">Order History</span>
                  </NuxtLink>
                  <NuxtLink
                    to="/my-reviews"
                    @click="isMenuOpen = false"
                    class="flex items-center space-x-3 px-4 py-2 text-gray-300 hover:text-white hover:bg-zinc-800/50 rounded-xl transition-all duration-200 group"
                  >
                    <div class="w-7 h-7 bg-zinc-800/50 rounded-lg flex items-center justify-center">
                      <Icon name="mdi:star-outline" class="text-base" />
                    </div>
                    <span class="font-medium text-sm">My Reviews</span>
                  </NuxtLink>
                  <NuxtLink
                    to="/profile"
                    @click="isMenuOpen = false"
                    class="flex items-center space-x-3 px-4 py-2 text-gray-300 hover:text-white hover:bg-zinc-800/50 rounded-xl transition-all duration-200 group"
                  >
                    <div class="w-7 h-7 bg-zinc-800/50 rounded-lg flex items-center justify-center">
                      <Icon name="mdi:account-outline" class="text-base" />
                    </div>
                    <span class="font-medium text-sm">Profile</span>
                  </NuxtLink>
                  <NuxtLink
                    to="/settings"
                    @click="isMenuOpen = false"
                    class="flex items-center space-x-3 px-4 py-2 text-gray-300 hover:text-white hover:bg-zinc-800/50 rounded-xl transition-all duration-200 group"
                  >
                    <div class="w-7 h-7 bg-zinc-800/50 rounded-lg flex items-center justify-center">
                      <Icon name="mdi:cog-outline" class="text-base" />
                    </div>
                    <span class="font-medium text-sm">Settings</span>
                  </NuxtLink>
                  <NuxtLink
                    v-if="authStore.user?.role === 'admin'"
                    to="/admin/events"
                    @click="isMenuOpen = false"
                    class="flex items-center space-x-3 px-4 py-2 text-gray-300 hover:text-white hover:bg-zinc-800/50 rounded-xl transition-all duration-200 group"
                  >
                    <div class="w-7 h-7 bg-zinc-800/50 rounded-lg flex items-center justify-center">
                      <Icon name="mdi:shield-account" class="text-base" />
                    </div>
                    <span class="font-medium text-sm">Admin Panel</span>
                  </NuxtLink>
                  <button 
                    @click="authStore.logout(); isMenuOpen = false" 
                    class="flex items-center space-x-3 px-4 py-2 text-red-400 hover:text-red-300 hover:bg-red-900/20 rounded-xl transition-all duration-200 w-full"
                  >
                    <div class="w-7 h-7 bg-red-900/20 rounded-lg flex items-center justify-center">
                      <Icon name="mdi:logout" class="text-base" />
                    </div>
                    <span class="font-medium text-sm">Logout</span>
                  </button>
                </div>
              </div>
              
              <!-- Guest User -->
              <div v-else class="space-y-1">
                <NuxtLink
                  to="/login"
                  @click="isMenuOpen = false"
                  class="flex items-center space-x-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-zinc-800/50 rounded-xl transition-all duration-200 group"
                >
                  <div class="w-8 h-8 bg-zinc-800/50 rounded-lg flex items-center justify-center group-hover:bg-zinc-700/50 transition-colors">
                    <Icon name="mdi:login" class="text-lg" />
                  </div>
                  <span class="text-base font-medium">Sign In</span>
                </NuxtLink>
                <NuxtLink
                  to="/register"
                  @click="isMenuOpen = false"
                  class="flex items-center space-x-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-zinc-800/50 rounded-xl transition-all duration-200 group"
                >
                  <div class="w-8 h-8 bg-zinc-800/50 rounded-lg flex items-center justify-center group-hover:bg-zinc-700/50 transition-colors">
                    <Icon name="mdi:account-plus" class="text-lg" />
                  </div>
                  <span class="text-base font-medium">Register</span>
                </NuxtLink>
              </div>
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

  <!-- Profile Drawer -->
  <Transition name="drawer-slide">
    <div 
      v-if="isProfileDrawerOpen" 
      class="fixed inset-0 z-50 flex justify-end"
      @click="isProfileDrawerOpen = false"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/50"></div>
      
      <!-- Drawer Content -->
      <div 
        class="relative w-80 h-full bg-white shadow-xl transform transition-transform duration-300"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <div class="flex items-center space-x-3">
            <img 
              src="/uploads/avatar_placeholder.png" 
              :alt="authStore.user?.user_name || 'User'"
              class="w-12 h-12 rounded-full object-cover"
            />
            <div>
              <h3 class="text-lg font-semibold text-gray-900">{{ authStore.user?.user_name || 'User' }}</h3>
              <p class="text-sm text-gray-500">{{ authStore.user?.email }}</p>
            </div>
          </div>
          <button 
            @click="isProfileDrawerOpen = false"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <Icon name="mdi:close" class="text-xl" />
          </button>
        </div>

        <!-- Navigation Items -->
        <div class="p-4">
          <nav class="space-y-2">
            <NuxtLink
              to="/my-tickets"
              @click="isProfileDrawerOpen = false"
              class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <Icon name="mdi:ticket-outline" class="text-xl text-gray-400" />
              <span>My Tickets</span>
            </NuxtLink>
            
            <NuxtLink
              to="/my-orders"
              @click="isProfileDrawerOpen = false"
              class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <Icon name="mdi:package-variant" class="text-xl text-gray-400" />
              <span>Order History</span>
            </NuxtLink>
            
            <NuxtLink
              to="/my-reviews"
              @click="isProfileDrawerOpen = false"
              class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <Icon name="mdi:star-outline" class="text-xl text-gray-400" />
              <span>My Reviews</span>
            </NuxtLink>
            
            <NuxtLink
              to="/profile"
              @click="isProfileDrawerOpen = false"
              class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <Icon name="mdi:account-outline" class="text-xl text-gray-400" />
              <span>Profile</span>
            </NuxtLink>
            
            <NuxtLink
              to="/settings"
              @click="isProfileDrawerOpen = false"
              class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <Icon name="mdi:cog-outline" class="text-xl text-gray-400" />
              <span>Settings</span>
            </NuxtLink>

            <!-- Admin Panel (if admin) -->
            <NuxtLink
              v-if="authStore.user?.role === 'admin'"
              to="/admin/events"
              @click="isProfileDrawerOpen = false"
              class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <Icon name="mdi:shield-account" class="text-xl text-gray-400" />
              <span>Admin Panel</span>
            </NuxtLink>
          </nav>

          <!-- Logout Button -->
          <div class="mt-6 pt-4 border-t border-gray-200">
            <button 
              @click="authStore.logout(); isProfileDrawerOpen = false"
              class="flex items-center space-x-3 w-full px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
            >
              <Icon name="mdi:logout" class="text-xl" />
              <span>Logout</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
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
