<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useAlert } from '~/composables/useAlert'
import { getImageUrl } from '~/utils/imageHelper'
import { useCustomFetch } from '~/composables/useCustomFetch'

definePageMeta({
  layout: 'default',
  middleware: 'auth'
})

useHead({
  title: 'My Profile | monogram',
  meta: [
    { name: 'description', content: 'View and manage your profile information at monogram E-commerce.' }
  ]
})

const authStore = useAuthStore()
const { success, error: showError, info } = useAlert()

// Profile data
const profile = ref<any>(null)
const isLoading = ref(false)
const isEditing = ref(false)

const form = reactive({
  full_name: '',
  phone: '',
  street: '',
  barangay: '',
  city: '',
  province: '',
  zipcode: '',
  avatar_url: '',
  user_name: ''
})

const email = ref(authStore.user?.email || '')
const addressModel = ref<{ street?: string; barangay?: string; city?: string; province?: string; zipcode?: string } | null>(null)
const passwordForm = reactive({
  current: '',
  newPassword: '',
  confirmPassword: ''
})

// Avatar handling
const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(null)
const avatarObjectUrl = ref<string | null>(null)
const avatarInput = ref<HTMLInputElement | null>(null)

const clearAvatarPreview = () => {
  if (avatarObjectUrl.value) {
    URL.revokeObjectURL(avatarObjectUrl.value)
    avatarObjectUrl.value = null
  }
  avatarPreview.value = null
}

const currentAvatar = computed(() => {
  if (avatarPreview.value) return avatarPreview.value
  if (form.avatar_url) return getImageUrl(form.avatar_url, 'avatar')
  return getImageUrl(null, 'avatar') // Use placeholder image instead of null
})

const userInitials = computed(() => {
  const name = form.user_name || authStore.user?.user_name || ''
  return name ? name.slice(0, 2).toUpperCase() : 'U'
})

const syncFormWithProfile = () => {
  form.full_name = profile.value?.full_name || ''
  form.phone = profile.value?.phone || ''
  form.street = profile.value?.street || ''
  form.barangay = profile.value?.barangay || ''
  form.city = profile.value?.city || ''
  form.province = profile.value?.province || ''
  form.zipcode = profile.value?.zipcode || ''
  form.avatar_url = profile.value?.avatar_url || ''
  form.user_name = authStore.user?.user_name || ''
  email.value = authStore.user?.email || ''
  addressModel.value = {
    street: form.street,
    barangay: form.barangay,
    city: form.city,
    province: form.province,
    zipcode: form.zipcode
  }
  avatarFile.value = null
  clearAvatarPreview()
}

const fetchProfile = async () => {
  isLoading.value = true
  try {
    const response = await useCustomFetch('/api/profile')
    profile.value = response
    syncFormWithProfile()
  } catch (err: any) {
    console.error('Failed to fetch profile:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await fetchProfile()
})

onBeforeUnmount(() => {
  clearAvatarPreview()
})

const toggleEdit = () => {
  if (isEditing.value) {
    isEditing.value = false
    if (profile.value) {
      syncFormWithProfile()
    }
  } else {
    isEditing.value = true
  }
}

const handleAvatarChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target?.files?.length) return

  const file = target.files[0]
  avatarFile.value = file

  if (avatarObjectUrl.value) {
    URL.revokeObjectURL(avatarObjectUrl.value)
    avatarObjectUrl.value = null
  }

  const objectUrl = URL.createObjectURL(file)
  avatarPreview.value = objectUrl
  avatarObjectUrl.value = objectUrl
  target.value = ''
}

const saveProfile = async () => {
  isLoading.value = true
  try {
    let avatarPath = form.avatar_url

    if (avatarFile.value) {
      const formData = new FormData()
      formData.append('avatar', avatarFile.value)
      const uploadResponse: any = await useCustomFetch('/api/profile/avatar', {
        method: 'POST',
        body: formData
      })
      avatarPath = uploadResponse?.avatar_path || avatarPath
      // Update form with new avatar path
      form.avatar_url = avatarPath
      // Clear preview and file since it's now saved
      clearAvatarPreview()
      avatarFile.value = null
    }

    // Use addressModel if it has meaningful data, otherwise fall back to form values to preserve existing data
    const address = addressModel.value || {}
    
    // Helper function to check if a value is meaningful (not empty/null/undefined)
    const hasValue = (val: any) => val && String(val).trim() !== ''
    
    // Determine which values to use - prefer addressModel if it has data, otherwise use form values
    const finalAddress = {
      street: hasValue(address.street) ? address.street : form.street,
      barangay: hasValue(address.barangay) ? address.barangay : form.barangay,
      city: hasValue(address.city) ? address.city : form.city,
      province: hasValue(address.province) ? address.province : form.province,
      zipcode: hasValue(address.zipcode) ? address.zipcode : form.zipcode
    }
    
    const response = await useCustomFetch('/api/profile', {
      method: 'PUT',
      body: {
        user_name: form.user_name,
        email: email.value,
        full_name: form.full_name,
        phone: form.phone,
        country: 'Philippines',
        ...finalAddress,
        avatar_url: avatarPath
      }
    })

    profile.value = response
    await authStore.fetchUser()
    syncFormWithProfile()
    isEditing.value = false
    success('Profile Updated', 'Your profile has been updated successfully.')
  } catch (err: any) {
    showError('Update Failed', err?.data?.message || 'Failed to update profile. Please try again.')
  } finally {
    isLoading.value = false
  }
}

const handlePasswordPlaceholder = () => {
  info('Email Integration Pending', 'Password changes will be handled via email soon.')
  passwordForm.current = ''
  passwordForm.newPassword = ''
  passwordForm.confirmPassword = ''
}

const handleDeleteAccount = () => {
  info('Coming Soon', 'Account deletion functionality will be available soon.')
}

</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">My Profile</h1>
        <p class="text-gray-600">Manage your personal information</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
          <div class="lg:sticky lg:top-8 lg:self-start">
            <CustomerSidebar />
          </div>
        </div>

        <div class="lg:col-span-3 space-y-6">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 border-b border-gray-100 p-6">
              <div class="flex items-center gap-4">
                <!-- Avatar with Camera Icon -->
                <div class="relative">
                  <div class="relative w-24 h-24 rounded-full overflow-hidden bg-gradient-to-br from-gray-900 to-gray-700 flex items-center justify-center">
                    <img
                      :src="currentAvatar"
                      :alt="form.user_name || 'User'"
                      class="w-full h-full object-cover"
                    />
                    
                    <!-- Camera Icon Overlay (only shown in edit mode) -->
                    <div
                      v-if="isEditing"
                      class="absolute inset-0 bg-black/50 flex items-center justify-center cursor-pointer transition-opacity hover:bg-black/60"
                      @click="avatarInput?.click()"
                    >
                      <Icon name="mdi:camera" class="w-6 h-6 text-white" />
                    </div>
                  </div>
                  
                  <!-- Hidden File Input -->
                  <input
                    v-if="isEditing"
                    ref="avatarInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="handleAvatarChange"
                  />
                </div>
                
                <div>
                  <h3 class="text-lg font-bold text-gray-900">{{ form.user_name || 'User' }}</h3>
                  <p class="text-sm text-gray-600">{{ email || 'No email' }}</p>
                </div>
              </div>

              <button
                @click="toggleEdit"
                :disabled="isLoading && isEditing"
                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :class="isEditing ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-gray-900 text-white hover:bg-gray-800'"
              >
                <svg v-if="!isEditing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                {{ isEditing ? 'Cancel' : 'Edit Profile' }}
              </button>
            </div>

            <div class="p-6">
              <div v-if="isLoading && !profile" class="py-12 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                <p class="mt-2 text-sm text-gray-600">Loading profile...</p>
              </div>

              <div v-else>
                <div v-if="!isEditing" class="space-y-8">
                  <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Personal Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Username</label>
                        <p class="text-gray-900 break-all">{{ form.user_name || 'Not provided' }}</p>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Full Name</label>
                        <p class="text-gray-900">{{ profile?.full_name || 'Not provided' }}</p>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Email Address</label>
                        <p class="text-gray-900 break-all">{{ email || 'Not provided' }}</p>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Phone Number</label>
                        <p class="text-gray-900">{{ profile?.phone || 'Not provided' }}</p>
                      </div>
                    </div>
                  </div>

                  <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Address</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-500 mb-1">Complete Address</label>
                        <p class="text-gray-900">{{ profile?.complete_address || 'Not provided' }}</p>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Province</label>
                        <p class="text-gray-900">{{ profile?.province || 'Not provided' }}</p>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">City / Municipality</label>
                        <p class="text-gray-900">{{ profile?.city || 'Not provided' }}</p>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Barangay</label>
                        <p class="text-gray-900">{{ profile?.barangay || 'Not provided' }}</p>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Zip Code</label>
                        <p class="text-gray-900">{{ profile?.zipcode || 'Not provided' }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <form v-else @submit.prevent="saveProfile" class="space-y-6">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label for="user_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Username <span class="text-red-500">*</span>
                      </label>
                      <input
                        id="user_name"
                        v-model="form.user_name"
                        type="text"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors"
                        placeholder="Enter your username"
                      />
                    </div>

                    <div>
                      <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                      </label>
                      <input
                        id="email"
                        v-model="email"
                        type="email"
                        readonly
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
                      />
                      <p class="mt-1 text-xs text-gray-500">Email updates are handled via support.</p>
                    </div>

                    <div>
                      <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name <span class="text-red-500">*</span>
                      </label>
                      <input
                        id="full_name"
                        v-model="form.full_name"
                        type="text"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors"
                        placeholder="Enter your full name"
                      />
                    </div>

                    <div>
                      <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number
                      </label>
                      <input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors"
                        placeholder="+63 XXX XXX XXXX"
                      />
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Address Details
                    </label>
                    <AddressForm v-model="addressModel" />
                  </div>

                  <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button
                      type="button"
                      @click="toggleEdit"
                      class="px-6 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors"
                      :disabled="isLoading"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      class="px-6 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      :disabled="isLoading"
                    >
                      <span v-if="!isLoading">Save Changes</span>
                      <span v-else class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                      </span>
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h2 class="text-lg font-semibold text-gray-900">Change Password</h2>
                <p class="text-sm text-gray-500">A secure email confirmation will be required.</p>
              </div>
              <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Coming Soon</span>
            </div>
            <form class="space-y-4" @submit.prevent="handlePasswordPlaceholder">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                  <input
                    id="current_password"
                    v-model="passwordForm.current"
                    type="password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors"
                    placeholder="Enter current password"
                  />
                </div>
                <div>
                  <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                  <input
                    id="new_password"
                    v-model="passwordForm.newPassword"
                    type="password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors"
                    placeholder="Create a new password"
                  />
                </div>
                <div>
                  <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                  <input
                    id="confirm_password"
                    v-model="passwordForm.confirmPassword"
                    type="password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors"
                    placeholder="Re-enter password"
                  />
                </div>
              </div>
              <div class="flex justify-end">
                <button
                  type="submit"
                  class="px-6 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors"
                >
                  Notify Me
                </button>
              </div>
            </form>
          </div> -->

        </div>
      </div>
    </div>
  </div>
</template>

