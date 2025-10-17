<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useSettingsStore } from '~/stores/settings'
import { useAlert } from '~/composables/useAlert'
import { getImageUrl } from '~/helpers/imageHelper'
import Dialog from '~/components/Dialog.vue'
import type { TeamMember } from '~/types/settings'

definePageMeta({
  layout: 'admin'
})

const settingsStore = useSettingsStore()
const { success: showSuccess, error: showError } = useAlert()

// Active tab
const activeTab = ref<'site' | 'contact' | 'team' | 'maintenance'>('site')

// Site Identity
const siteName = ref('')
const siteAbout = ref('')
const siteLogo = ref<string | null>(null)
const logoFile = ref<File | null>(null)
const logoPreview = ref<string | null>(null)
const markLogoForDeletion = ref(false)

// Contact Information
const contactEmail = ref('')
const contactPhone = ref('')
const contactAddress = ref('')
const contactFacebook = ref('')
const contactInstagram = ref('')
const contactTwitter = ref('')

// Team Members
const teamMembers = ref<TeamMember[]>([])
const showAddMemberModal = ref(false)
const editingMemberIndex = ref<number | null>(null)
const newMember = ref<TeamMember>({
  name: '',
  position: '',
  email: '',
  image: null
})
const memberImageFile = ref<File | null>(null)
const memberImagePreview = ref<string | null>(null)

// Maintenance Mode
const maintenanceMode = ref(false)
const maintenanceMessage = ref('')

// Loading states
const saving = ref(false)
const isEditingMode = ref(false)

// Computed
const logoUrl = computed(() => {
  if (logoPreview.value) return logoPreview.value
  if (siteLogo.value) return getImageUrl(siteLogo.value)
  return null
})

// Load settings and populate fields
const loadSettings = async () => {
  try {
    await settingsStore.fetchSettings()
    const settings = settingsStore.settings
    
    console.log('Settings loaded:', settings)
    
    if (settings) {
      // Site Identity
      const site = settings.site || {}
      siteName.value = site.site_name || ''
      siteAbout.value = site.site_about || ''
      siteLogo.value = site.site_logo || null

      // Contact Information
      const contact = settings.contact || {}
      contactEmail.value = contact.contact_email || ''
      contactPhone.value = contact.contact_phone || ''
      contactAddress.value = contact.contact_address || ''
      contactFacebook.value = contact.contact_facebook || ''
      contactInstagram.value = contact.contact_instagram || ''
      contactTwitter.value = contact.contact_twitter || ''

      // Team Members
      const team = settings.team || {}
      teamMembers.value = team.team_members || []

      // Maintenance Mode
      const maintenance = settings.maintenance || {}
      maintenanceMode.value = maintenance.maintenance_mode || false
      maintenanceMessage.value = maintenance.maintenance_message || ''
    }
  } catch (error) {
    console.error('Failed to load settings:', error)
    showError('Failed to load settings')
  }
}

// Load settings on mount
onMounted(() => {
  loadSettings()
})

// Handle logo file selection
const handleLogoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    logoFile.value = target.files[0]
    markLogoForDeletion.value = false // Uncheck delete if selecting new image
    const reader = new FileReader()
    reader.onload = (e) => {
      logoPreview.value = e.target?.result as string
    }
    reader.readAsDataURL(target.files[0])
  }
}

// Toggle edit mode
const toggleEditMode = () => {
  isEditingMode.value = !isEditingMode.value
  if (!isEditingMode.value) {
    // Reload data when canceling edit
    loadSettings()
    logoFile.value = null
    logoPreview.value = null
    markLogoForDeletion.value = false
  }
}

// Save site identity
const saveSiteIdentity = async () => {
  try {
  saving.value = true

    // Delete logo if marked for deletion
    if (markLogoForDeletion.value && siteLogo.value) {
      await settingsStore.deleteLogo()
      siteLogo.value = null
    }

    // Upload logo if a new one was selected
    if (logoFile.value) {
      await settingsStore.uploadLogo(logoFile.value)
      logoFile.value = null
      logoPreview.value = null
    }

    const settings = [
      { key: 'site_name', value: siteName.value, group: 'site', type: 'text' },
      { key: 'site_about', value: siteAbout.value, group: 'site', type: 'textarea' }
    ]

    await settingsStore.updateSettings({ settings })
    await loadSettings()
    showSuccess('Site identity updated successfully')
    isEditingMode.value = false
    markLogoForDeletion.value = false
  } catch (error) {
    console.error('Failed to update site identity:', error)
    showError('Failed to update site identity')
  } finally {
    saving.value = false
  }
}

// Save contact information
const saveContactInformation = async () => {
  try {
  saving.value = true

    const settings = [
      { key: 'contact_email', value: contactEmail.value, group: 'contact', type: 'text' },
      { key: 'contact_phone', value: contactPhone.value, group: 'contact', type: 'text' },
      { key: 'contact_address', value: contactAddress.value, group: 'contact', type: 'textarea' },
      { key: 'contact_facebook', value: contactFacebook.value, group: 'contact', type: 'text' },
      { key: 'contact_instagram', value: contactInstagram.value, group: 'contact', type: 'text' },
      { key: 'contact_twitter', value: contactTwitter.value, group: 'contact', type: 'text' }
    ]

    await settingsStore.updateSettings({ settings })
    await loadSettings()
    showSuccess('Contact information updated successfully')
    isEditingMode.value = false
  } catch (error) {
    console.error('Failed to update contact information:', error)
    showError('Failed to update contact information')
  } finally {
    saving.value = false
  }
}

// Handle member image file selection
const handleMemberImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    memberImageFile.value = target.files[0]
    const reader = new FileReader()
    reader.onload = (e) => {
      memberImagePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(target.files[0])
  }
}

// Get member image URL
const getMemberImageUrl = (image: string | null): string | undefined => {
  if (memberImagePreview.value) return memberImagePreview.value
  if (image) return getImageUrl(image)
  return undefined
}

// Team member functions
const openAddMemberModal = () => {
  newMember.value = { name: '', position: '', email: '', image: null }
  editingMemberIndex.value = null
  memberImageFile.value = null
  memberImagePreview.value = null
  showAddMemberModal.value = true
}

const editMember = (index: number) => {
  newMember.value = { ...teamMembers.value[index] }
  editingMemberIndex.value = index
  memberImageFile.value = null
  memberImagePreview.value = null
  showAddMemberModal.value = true
}

const saveMember = async () => {
  if (!newMember.value.name || !newMember.value.position) {
    showError('Please fill in required fields')
    return
  }

  try {
    saving.value = true

    // Upload image if a new one was selected
    if (memberImageFile.value) {
      const uploadResponse = await settingsStore.uploadTeamMemberImage(memberImageFile.value)
      newMember.value.image = uploadResponse.path
    }

    // If editing and old image exists but being replaced, delete old image
    if (editingMemberIndex.value !== null && memberImageFile.value) {
      const oldMember = teamMembers.value[editingMemberIndex.value]
      if (oldMember.image && oldMember.image !== newMember.value.image) {
        await settingsStore.deleteTeamMemberImage(oldMember.image)
      }
  }

  if (editingMemberIndex.value !== null) {
    teamMembers.value[editingMemberIndex.value] = { ...newMember.value }
  } else {
      teamMembers.value.push({ ...newMember.value })
    }

    showAddMemberModal.value = false
    memberImageFile.value = null
    memberImagePreview.value = null
    await saveTeamMembers()
  } catch (error) {
    console.error('Failed to save team member:', error)
    showError('Failed to save team member')
  } finally {
    saving.value = false
  }
}

const removeMember = async (index: number) => {
  if (!confirm('Are you sure you want to remove this team member?')) return
  
  try {
    saving.value = true
    const member = teamMembers.value[index]
    
    // Delete image file if it exists
    if (member.image) {
      await settingsStore.deleteTeamMemberImage(member.image)
    }
    
    teamMembers.value.splice(index, 1)
    await saveTeamMembers()
  } catch (error) {
    console.error('Failed to remove team member:', error)
    showError('Failed to remove team member')
  } finally {
    saving.value = false
  }
}

const saveTeamMembers = async () => {
  try {
    saving.value = true
    const settings = [
      { key: 'team_members', value: JSON.stringify(teamMembers.value), group: 'team', type: 'json' }
    ]
    await settingsStore.updateSettings({ settings })
    await loadSettings()
    showSuccess('Team members updated successfully')
  } catch (error) {
    console.error('Failed to update team members:', error)
    showError('Failed to update team members')
  } finally {
    saving.value = false
  }
}

// Toggle maintenance mode
const toggleMaintenance = async () => {
  try {
  saving.value = true
    await settingsStore.toggleMaintenance(maintenanceMode.value, maintenanceMessage.value)
    await loadSettings()
    showSuccess(`Maintenance mode ${maintenanceMode.value ? 'enabled' : 'disabled'}`)
  } catch (error) {
    console.error('Failed to toggle maintenance mode:', error)
    showError('Failed to toggle maintenance mode')
  } finally {
    saving.value = false
  }
}

// Save maintenance message
const saveMaintenanceMessage = async () => {
  try {
  saving.value = true
    const settings = [
      { key: 'maintenance_message', value: maintenanceMessage.value, group: 'maintenance', type: 'textarea' }
    ]
    await settingsStore.updateSettings({ settings })
    await loadSettings()
    showSuccess('Maintenance message updated successfully')
    isEditingMode.value = false
  } catch (error) {
    console.error('Failed to update maintenance message:', error)
    showError('Failed to update maintenance message')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="p-8 space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
        <p class="text-gray-600 mt-1">Manage your store configuration and preferences</p>
      </div>
      <div class="flex items-center gap-3">
          <button
          v-if="activeTab !== 'team' && activeTab !== 'maintenance'"
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
            @click="activeTab = 'maintenance'; isEditingMode = false"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
              activeTab === 'maintenance'
                ? 'border-zinc-900 text-zinc-900'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <Icon name="mdi:wrench" class="inline-block mr-2" />
            Maintenance Mode
          </button>
        </nav>
            </div>

      <!-- Site Identity Tab -->
      <div v-if="activeTab === 'site'" class="p-8">
            <!-- Logo -->
        <div class="mb-8">
          <label class="block text-sm font-medium text-gray-700 mb-3">Site Logo</label>
          <div class="flex items-start gap-6">
            <div class="w-32 h-32 bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden border-2 border-gray-200">
              <img v-if="logoUrl" :src="logoUrl" alt="Site Logo" class="max-w-full max-h-full object-contain" />
              <Icon v-else name="mdi:image-outline" class="text-5xl text-gray-300" />
                </div>
            <div class="flex-1" v-if="isEditingMode">
                  <input
                    type="file"
                @change="handleLogoChange"
                    accept="image/*"
                class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-900 file:text-white hover:file:bg-zinc-800 file:cursor-pointer"
              />
              <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF up to 2MB. Logo will be uploaded when you click Save Changes.</p>
              <div class="mt-3 flex flex-col gap-2">
                <button
                  v-if="logoPreview"
                  @click="logoFile = null; logoPreview = null"
                  class="text-sm text-red-600 hover:text-red-700 font-medium inline-flex items-center"
                >
                  <Icon name="mdi:close-circle" class="mr-1" />
                  Remove Selected Image
                </button>
                <label v-if="siteLogo && !logoPreview" class="inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="markLogoForDeletion"
                    class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
                  />
                  <span class="ml-2 text-sm text-gray-700">Delete current logo</span>
                  </label>
              </div>
                </div>
              </div>
        </div>

        <!-- Site Name -->
        <div class="mb-8">
          <label class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
          <input
            v-if="isEditingMode"
            v-model="siteName"
            type="text"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="Enter site name"
          />
          <p v-else class="text-gray-900 py-2">{{ siteName || 'Not set' }}</p>
            </div>

            <!-- About -->
        <div class="mb-8">
              <label class="block text-sm font-medium text-gray-700 mb-2">About</label>
              <textarea
            v-if="isEditingMode"
            v-model="siteAbout"
                rows="4"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="Tell us about your site"
              ></textarea>
          <p v-else class="text-gray-900 py-2 whitespace-pre-wrap">{{ siteAbout || 'Not set' }}</p>
            </div>

            <button
          v-if="isEditingMode"
              @click="saveSiteIdentity"
              :disabled="saving"
          class="px-6 py-3 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 disabled:opacity-50 font-medium"
            >
          <Icon name="mdi:content-save" class="inline-block mr-2" />
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
        </div>

      <!-- Contact Information Tab -->
      <div v-if="activeTab === 'contact'" class="p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
              <!-- Email -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input
              v-if="isEditingMode"
              v-model="contactEmail"
                  type="email"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
              placeholder="contact@example.com"
                />
            <p v-else class="text-gray-900 py-2">{{ contactEmail || 'Not set' }}</p>
              </div>

              <!-- Phone -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input
              v-if="isEditingMode"
              v-model="contactPhone"
              type="tel"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                  placeholder="+63 123 456 7890"
                />
            <p v-else class="text-gray-900 py-2">{{ contactPhone || 'Not set' }}</p>
              </div>
            </div>

            <!-- Address -->
        <div class="mb-8">
              <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
              <textarea
            v-if="isEditingMode"
            v-model="contactAddress"
                rows="3"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="123 Main Street, Manila, Philippines"
              ></textarea>
          <p v-else class="text-gray-900 py-2 whitespace-pre-wrap">{{ contactAddress || 'Not set' }}</p>
            </div>

            <!-- Social Media -->
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Social Media</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <!-- Facebook -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
              <Icon name="mdi:facebook" class="inline-block mr-1" />
              Facebook
                  </label>
                  <input
              v-if="isEditingMode"
              v-model="contactFacebook"
                    type="url"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    placeholder="https://facebook.com/yourpage"
                  />
            <p v-else class="text-gray-900 py-2 text-sm break-all">{{ contactFacebook || 'Not set' }}</p>
                </div>

          <!-- Instagram -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
              <Icon name="mdi:instagram" class="inline-block mr-1" />
              Instagram
                  </label>
                  <input
              v-if="isEditingMode"
              v-model="contactInstagram"
                    type="url"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
              placeholder="https://instagram.com/yourpage"
                  />
            <p v-else class="text-gray-900 py-2 text-sm break-all">{{ contactInstagram || 'Not set' }}</p>
                </div>

          <!-- Twitter -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
              <Icon name="mdi:twitter" class="inline-block mr-1" />
              Twitter
                  </label>
                  <input
              v-if="isEditingMode"
              v-model="contactTwitter"
                    type="url"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
              placeholder="https://twitter.com/yourpage"
                  />
            <p v-else class="text-gray-900 py-2 text-sm break-all">{{ contactTwitter || 'Not set' }}</p>
              </div>
            </div>

            <button
          v-if="isEditingMode"
          @click="saveContactInformation"
              :disabled="saving"
          class="px-6 py-3 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 disabled:opacity-50 font-medium"
            >
          <Icon name="mdi:content-save" class="inline-block mr-2" />
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
        </div>

      <!-- Team Tab -->
      <div v-if="activeTab === 'team'" class="p-8">
          <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-semibold text-gray-900">Team Members</h2>
            <button
            @click="openAddMemberModal"
            class="px-4 py-2 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 font-medium"
            >
            <Icon name="mdi:plus" class="inline-block mr-2" />
            Add Member
            </button>
          </div>

          <!-- Team Members List -->
        <div v-if="teamMembers.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="(member, index) in teamMembers"
            :key="index"
            class="bg-gray-50 rounded-lg p-6 border border-gray-200 hover:border-gray-300 transition-colors"
          >
            <div class="flex items-start gap-4 mb-4">
              <!-- Member Image -->
              <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden flex-shrink-0">
                <img 
                  v-if="member.image" 
                  :src="getMemberImageUrl(member.image)" 
                  :alt="member.name"
                  class="w-full h-full object-cover"
                />
                <Icon v-else name="mdi:account" class="text-3xl text-gray-400" />
                </div>
              
              <!-- Member Info -->
                <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 text-lg truncate">{{ member.name }}</h3>
                <p class="text-sm text-gray-600 mt-1">{{ member.position }}</p>
                <p class="text-sm text-gray-500 mt-1 truncate">{{ member.email }}</p>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="flex gap-2 justify-end pt-3 border-t border-gray-200">
                    <button
                @click="editMember(index)"
                class="px-3 py-1.5 text-sm text-zinc-900 hover:bg-zinc-100 rounded-lg transition-colors flex items-center gap-1"
                title="Edit"
                    >
                <Icon name="mdi:pencil" class="text-lg" />
                      Edit
                    </button>
                    <button
                @click="removeMember(index)"
                class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors flex items-center gap-1"
                title="Delete"
                    >
                <Icon name="mdi:delete" class="text-lg" />
                Delete
                    </button>
                  </div>
                </div>
              </div>
        <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
          <Icon name="mdi:account-group-outline" class="text-6xl text-gray-300 mx-auto mb-3" />
          <p class="text-gray-500">No team members added yet</p>
          <button
            @click="openAddMemberModal"
            class="mt-4 px-4 py-2 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 font-medium"
          >
            <Icon name="mdi:plus" class="inline-block mr-2" />
            Add Your First Member
          </button>
            </div>
          </div>

      <!-- Maintenance Mode Tab -->
      <div v-if="activeTab === 'maintenance'" class="p-8">
        <div class="bg-yellow-50 border-2 border-yellow-200 rounded-lg p-6 mb-8">
          <div class="flex items-start">
            <Icon name="mdi:alert" class="text-yellow-600 text-2xl mr-3 mt-0.5 flex-shrink-0" />
            <div>
              <h3 class="font-semibold text-yellow-900 text-lg">Warning</h3>
              <p class="text-sm text-yellow-700 mt-2">
                Enabling maintenance mode will make your site unavailable to regular users.
                Only administrators will be able to access the site.
              </p>
            </div>
          </div>
        </div>

            <!-- Toggle -->
        <div class="mb-8">
          <label class="flex items-center cursor-pointer group">
            <div class="relative">
                <input
                v-model="maintenanceMode"
                  type="checkbox"
                class="sr-only"
                @change="toggleMaintenance"
              />
              <div
                :class="[
                  'w-14 h-8 rounded-full transition-colors',
                  maintenanceMode ? 'bg-red-500' : 'bg-gray-300'
                ]"
              >
                <div
                  :class="[
                    'absolute top-1 left-1 w-6 h-6 bg-white rounded-full transition-transform shadow-md',
                    maintenanceMode ? 'transform translate-x-6' : ''
                  ]"
                ></div>
              </div>
            </div>
            <span class="ml-4 text-base font-medium text-gray-900">
              {{ maintenanceMode ? 'Maintenance Mode is ON' : 'Maintenance Mode is OFF' }}
            </span>
              </label>
            </div>

            <!-- Message -->
        <div class="mb-8">
              <label class="block text-sm font-medium text-gray-700 mb-2">Maintenance Message</label>
              <textarea
            v-model="maintenanceMessage"
                rows="4"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="Enter a message to display to users"
            :disabled="!maintenanceMode"
              ></textarea>
          <p class="text-xs text-gray-500 mt-2">
            This message will be displayed to users when they try to access your site during maintenance.
          </p>
            </div>

            <button
          v-if="maintenanceMode"
          @click="saveMaintenanceMessage"
              :disabled="saving"
          class="px-6 py-3 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 disabled:opacity-50 font-medium"
            >
          <Icon name="mdi:content-save" class="inline-block mr-2" />
          {{ saving ? 'Saving...' : 'Save Message' }}
            </button>
          </div>
    </div>

    <!-- Add/Edit Member Dialog -->
    <Dialog
      v-model="showAddMemberModal"
      :title="`${editingMemberIndex !== null ? 'Edit' : 'Add'} Team Member`"
      width="600px"
    >
      <div class="space-y-5">
        <!-- Profile Image Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">Profile Image</label>
          <div class="flex items-start gap-4">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center overflow-hidden border-2 border-gray-200">
              <img 
                v-if="getMemberImageUrl(newMember.image)" 
                :src="getMemberImageUrl(newMember.image)" 
                alt="Profile preview"
                class="w-full h-full object-cover"
              />
              <Icon v-else name="mdi:account" class="text-4xl text-gray-400" />
            </div>
            <div class="flex-1">
              <input
                type="file"
                @change="handleMemberImageChange"
                accept="image/*"
                class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-900 file:text-white hover:file:bg-zinc-800 file:cursor-pointer"
              />
              <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF up to 2MB. Recommended: Square image.</p>
        </div>
      </div>
    </div>

        <!-- Name -->
                <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Name *
          </label>
                  <input
                    v-model="newMember.name"
                    type="text"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    placeholder="John Doe"
                  />
                </div>

        <!-- Position -->
                <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Position *
          </label>
                  <input
                    v-model="newMember.position"
                    type="text"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    placeholder="CEO"
                  />
              </div>

        <!-- Email -->
              <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Email
          </label>
                  <input
                    v-model="newMember.email"
                    type="email"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
                    placeholder="john@example.com"
                  />
                </div>
              </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <button
          @click="showAddMemberModal = false"
          class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors"
                >
                  Cancel
                </button>
                <button
          @click="saveMember"
          :disabled="saving"
          class="px-5 py-2.5 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 font-medium disabled:opacity-50 transition-colors"
                >
          {{ saving ? 'Saving...' : (editingMemberIndex !== null ? 'Update Member' : 'Add Member') }}
                </button>
              </div>
    </Dialog>
  </div>
</template>
