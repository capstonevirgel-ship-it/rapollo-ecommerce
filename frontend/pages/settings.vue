<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useAlert } from '~/composables/useAlert'

definePageMeta({
  layout: 'default',
  middleware: 'auth'
})

useHead({
  title: 'Settings | RAPOLLO',
  meta: [
    { name: 'description', content: 'Manage your account settings and preferences at Rapollo E-commerce.' }
  ]
})

const authStore = useAuthStore()
const { success, info } = useAlert()

// Active tab
const activeTab = ref<'account' | 'notifications' | 'privacy' | 'preferences'>('account')

// Static form data (non-functional for now)
const accountSettings = reactive({
  email: authStore.user?.email || '',
  username: authStore.user?.user_name || '',
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const notificationSettings = reactive({
  emailNotifications: true,
  orderUpdates: true,
  promotionalEmails: false,
  eventReminders: true,
  productReviews: true,
  newsletterSubscription: false
})

const privacySettings = reactive({
  profileVisibility: 'public',
  showPurchaseHistory: false,
  dataCollection: true,
  thirdPartySharing: false
})

const preferenceSettings = reactive({
  language: 'English',
  currency: 'PHP',
  timezone: 'Asia/Manila',
  itemsPerPage: 12
})

// Static save handlers (will show info message)
const saveAccountSettings = () => {
  info('Coming Soon', 'Account settings functionality will be available soon.')
}

const saveNotificationSettings = () => {
  info('Coming Soon', 'Notification settings functionality will be available soon.')
}

const savePrivacySettings = () => {
  info('Coming Soon', 'Privacy settings functionality will be available soon.')
}

const savePreferences = () => {
  info('Coming Soon', 'Preference settings functionality will be available soon.')
}

const tabs = [
  { id: 'account', name: 'Account', icon: 'user' },
  { id: 'notifications', name: 'Notifications', icon: 'bell' },
  { id: 'privacy', name: 'Privacy', icon: 'lock' },
  { id: 'preferences', name: 'Preferences', icon: 'cog' }
]

const setActiveTab = (tab: typeof activeTab.value) => {
  activeTab.value = tab
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Settings</h1>
        <p class="text-gray-600">Manage your account settings and preferences</p>
      </div>

      <!-- Content with Sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="lg:sticky lg:top-8 lg:self-start">
            <CustomerSidebar />
          </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-3">
      <!-- Settings Container -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200">
          <div class="flex overflow-x-auto">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="setActiveTab(tab.id as any)"
              :class="[
                'flex-1 min-w-fit px-6 py-4 text-sm font-medium border-b-2 transition-colors',
                activeTab === tab.id
                  ? 'border-gray-900 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              <div class="flex items-center justify-center gap-2">
                <!-- Icons -->
                <svg v-if="tab.icon === 'user'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <svg v-if="tab.icon === 'bell'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <svg v-if="tab.icon === 'lock'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <svg v-if="tab.icon === 'cog'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ tab.name }}</span>
              </div>
            </button>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
          <!-- Account Settings -->
          <div v-show="activeTab === 'account'" class="space-y-6">
            <div>
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Information</h2>
              <div class="space-y-4">
                <div>
                  <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                  <input
                    id="email"
                    v-model="accountSettings.email"
                    type="email"
                    disabled
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
                  />
                  <p class="mt-1 text-xs text-gray-500">Email cannot be changed at this time</p>
                </div>
                
                <div>
                  <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                  <input
                    id="username"
                    v-model="accountSettings.username"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                  />
                </div>
              </div>
            </div>

            <div class="pt-6 border-t border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h2>
              <div class="space-y-4">
                <div>
                  <label for="current-password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                  <input
                    id="current-password"
                    v-model="accountSettings.currentPassword"
                    type="password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                  />
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="new-password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input
                      id="new-password"
                      v-model="accountSettings.newPassword"
                      type="password"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    />
                  </div>
                  
                  <div>
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input
                      id="confirm-password"
                      v-model="accountSettings.confirmPassword"
                      type="password"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-200">
              <button
                @click="saveAccountSettings"
                class="px-6 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors"
              >
                Save Changes
              </button>
            </div>
          </div>

          <!-- Notification Settings -->
          <div v-show="activeTab === 'notifications'" class="space-y-6">
            <div>
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Email Notifications</h2>
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Order Updates</p>
                    <p class="text-xs text-gray-500">Get notified about your order status</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input v-model="notificationSettings.orderUpdates" type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                  </label>
                </div>
                
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Event Reminders</p>
                    <p class="text-xs text-gray-500">Reminders for upcoming events</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input v-model="notificationSettings.eventReminders" type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                  </label>
                </div>
                
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Product Reviews</p>
                    <p class="text-xs text-gray-500">Requests to review purchased products</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input v-model="notificationSettings.productReviews" type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                  </label>
                </div>
                
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Promotional Emails</p>
                    <p class="text-xs text-gray-500">Receive special offers and promotions</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input v-model="notificationSettings.promotionalEmails" type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                  </label>
                </div>
                
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Newsletter</p>
                    <p class="text-xs text-gray-500">Monthly newsletter with updates</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input v-model="notificationSettings.newsletterSubscription" type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                  </label>
                </div>
              </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-200">
              <button
                @click="saveNotificationSettings"
                class="px-6 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors"
              >
                Save Preferences
              </button>
            </div>
          </div>

          <!-- Privacy Settings -->
          <div v-show="activeTab === 'privacy'" class="space-y-6">
            <div>
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Privacy Controls</h2>
              <div class="space-y-4">
                <div>
                  <label for="profile-visibility" class="block text-sm font-medium text-gray-700 mb-2">Profile Visibility</label>
                  <select
                    id="profile-visibility"
                    v-model="privacySettings.profileVisibility"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                  >
                    <option value="public">Public</option>
                    <option value="friends">Friends Only</option>
                    <option value="private">Private</option>
                  </select>
                </div>
                
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Show Purchase History</p>
                    <p class="text-xs text-gray-500">Allow others to see your purchase history</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input v-model="privacySettings.showPurchaseHistory" type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                  </label>
                </div>
                
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Data Collection</p>
                    <p class="text-xs text-gray-500">Help us improve by sharing usage data</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input v-model="privacySettings.dataCollection" type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                  </label>
                </div>
                
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Third-Party Sharing</p>
                    <p class="text-xs text-gray-500">Share data with trusted partners</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input v-model="privacySettings.thirdPartySharing" type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                  </label>
                </div>
              </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-200">
              <button
                @click="savePrivacySettings"
                class="px-6 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors"
              >
                Save Settings
              </button>
            </div>
          </div>

          <!-- Preferences -->
          <div v-show="activeTab === 'preferences'" class="space-y-6">
            <div>
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Display Preferences</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="language" class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                  <select
                    id="language"
                    v-model="preferenceSettings.language"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                  >
                    <option>English</option>
                    <option>Filipino</option>
                    <option>Tagalog</option>
                  </select>
                </div>
                
                <div>
                  <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                  <select
                    id="currency"
                    v-model="preferenceSettings.currency"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                  >
                    <option>PHP (₱)</option>
                    <option>USD ($)</option>
                    <option>EUR (€)</option>
                  </select>
                </div>
                
                <div>
                  <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                  <select
                    id="timezone"
                    v-model="preferenceSettings.timezone"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                  >
                    <option>Asia/Manila</option>
                    <option>Asia/Tokyo</option>
                    <option>America/New_York</option>
                  </select>
                </div>
                
                <div>
                  <label for="items-per-page" class="block text-sm font-medium text-gray-700 mb-2">Items Per Page</label>
                  <select
                    id="items-per-page"
                    v-model="preferenceSettings.itemsPerPage"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                  >
                    <option :value="9">9</option>
                    <option :value="12">12</option>
                    <option :value="24">24</option>
                    <option :value="48">48</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-200">
              <button
                @click="savePreferences"
                class="px-6 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors"
              >
                Save Preferences
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="mt-8 bg-red-50 border border-red-200 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-red-900 mb-2">Danger Zone</h2>
        <p class="text-sm text-red-700 mb-4">These actions are permanent and cannot be undone.</p>
        <button
          @click="info('Coming Soon', 'Account deletion functionality will be available soon.')"
          class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"
        >
          Delete Account
        </button>
        </div>
        </div>
      </div>
    </div>
  </div>
</template>

