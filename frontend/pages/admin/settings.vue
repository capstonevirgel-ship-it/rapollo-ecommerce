<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSettingsStore } from '~/stores/settings'
import SettingsTabs from '~/components/SettingsTabs.vue'

definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Settings - Admin | monogram',
  meta: [
    { name: 'description', content: 'Manage store settings and configuration in your monogram E-commerce store.' }
  ]
})

const settingsStore = useSettingsStore()

// Active tab
const activeTab = ref<'site' | 'contact' | 'social' | 'team' | 'shipping'>('site')

// Loading states
const isEditingMode = ref(false)

// Toggle edit mode
const toggleEditMode = () => {
  isEditingMode.value = !isEditingMode.value
}

// Load settings on mount
onMounted(() => {
  settingsStore.fetchSettings()
})
</script>

<template>
  <div class="space-y-8 sm:space-y-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Settings</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Manage your store configuration and preferences</p>
      </div>
      <div class="flex items-center gap-3">
          <button
          v-if="activeTab !== 'team' && activeTab !== 'shipping'"
          @click="toggleEditMode"
          class="inline-flex items-center px-4 py-2 rounded-lg transition-colors"
          :class="isEditingMode 
            ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' 
            : 'bg-zinc-900 text-white hover:bg-zinc-800'"
        >
          <Icon :name="isEditingMode ? 'mdi:close' : 'mdi:pencil'" class="mr-2" />
          {{ isEditingMode ? 'Cancel' : 'Edit' }}
          </button>
        </div>
      </div>

    <!-- Loading State -->
    <div v-if="settingsStore.loading && !settingsStore.settings" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-zinc-900"></div>
    </div>

    <!-- Tabs -->
    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="border-b border-gray-200">
        <nav class="flex -mb-px overflow-x-auto">
          <button
            @click="activeTab = 'site'; isEditingMode = false"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
              activeTab === 'site'
                ? 'border-zinc-900 text-zinc-900'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <Icon name="mdi:web" class="inline-block mr-2" />
            Site Identity
          </button>
          <button
            @click="activeTab = 'contact'; isEditingMode = false"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
              activeTab === 'contact'
                ? 'border-zinc-900 text-zinc-900'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <Icon name="mdi:phone" class="inline-block mr-2" />
            Contact Information
          </button>
          <button
            @click="activeTab = 'social'; isEditingMode = false"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
              activeTab === 'social'
                ? 'border-zinc-900 text-zinc-900'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <Icon name="mdi:share-variant" class="inline-block mr-2" />
            Social Links
          </button>
          <button
            @click="activeTab = 'team'; isEditingMode = false"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
              activeTab === 'team'
                ? 'border-zinc-900 text-zinc-900'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <Icon name="mdi:account-group" class="inline-block mr-2" />
            Team
          </button>
          <button
            @click="activeTab = 'shipping'; isEditingMode = false"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
              activeTab === 'shipping'
                ? 'border-zinc-900 text-zinc-900'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <Icon name="mdi:truck" class="inline-block mr-2" />
            Shipping & Tax
          </button>
        </nav>
            </div>

      <!-- Tab Content -->
      <SettingsTabs :active-tab="activeTab" :is-editing-mode="isEditingMode" @toggle-edit-mode="toggleEditMode" />
    </div>
  </div>
</template>