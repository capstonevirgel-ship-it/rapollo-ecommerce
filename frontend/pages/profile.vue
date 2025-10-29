<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useAlert } from '~/composables/useAlert'

definePageMeta({
  layout: 'default',
  middleware: 'auth'
})

useHead({
  title: 'My Profile | RAPOLLO',
  meta: [
    { name: 'description', content: 'View and manage your profile information at Rapollo E-commerce.' }
  ]
})

const authStore = useAuthStore()
const { success, error: showError } = useAlert()
const route = useRoute()

// Profile data
const profile = ref<any>(null)
const isLoading = ref(false)
const isEditing = ref(false)

// Form data for editing
const form = reactive({
  full_name: '',
  phone: '',
  address: '',
  city: '',
  postal_code: '',
  country: 'Philippines'
})

// Fetch profile on mount
onMounted(async () => {
  await fetchProfile()
})

const fetchProfile = async () => {
  isLoading.value = true
  try {
    const response = await $fetch('/api/profile')
    profile.value = response
    
    // Populate form with existing data
    if (profile.value) {
      form.full_name = profile.value.full_name || ''
      form.phone = profile.value.phone || ''
      form.address = profile.value.address || ''
      form.city = profile.value.city || ''
      form.postal_code = profile.value.postal_code || ''
      form.country = profile.value.country || 'Philippines'
    }
  } catch (err: any) {
    console.error('Failed to fetch profile:', err)
  } finally {
    isLoading.value = false
  }
}

const toggleEdit = () => {
  isEditing.value = !isEditing.value
  
  // Reset form if canceling
  if (!isEditing.value && profile.value) {
    form.full_name = profile.value.full_name || ''
    form.phone = profile.value.phone || ''
    form.address = profile.value.address || ''
    form.city = profile.value.city || ''
    form.postal_code = profile.value.postal_code || ''
    form.country = profile.value.country || 'Philippines'
  }
}

const saveProfile = async () => {
  isLoading.value = true
  try {
    const response = await $fetch('/api/profile', {
      method: 'PUT',
      body: form
    })
    
    profile.value = response
    isEditing.value = false
    success('Profile Updated', 'Your profile has been updated successfully.')
  } catch (err: any) {
    showError('Update Failed', err?.data?.message || 'Failed to update profile. Please try again.')
  } finally {
    isLoading.value = false
  }
}

</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">My Profile</h1>
        <p class="text-gray-600">Manage your personal information</p>
      </div>

      <!-- Profile Content -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Left Sidebar - Customer Navigation -->
        <div class="lg:col-span-1">
          <div class="lg:sticky lg:top-8 lg:self-start">
            <CustomerSidebar />
          </div>
        </div>

        <!-- Right Content - Profile Details -->
        <div class="lg:col-span-3">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <!-- Header with Edit Button -->
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900">Personal Information</h2>
              <button
                @click="toggleEdit"
                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors"
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

            <!-- Loading State -->
            <div v-if="isLoading && !profile" class="py-12 text-center">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
              <p class="mt-2 text-sm text-gray-600">Loading profile...</p>
            </div>

            <!-- View Mode -->
            <div v-else-if="!isEditing" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Full Name</label>
                  <p class="text-gray-900">{{ profile?.full_name || 'Not provided' }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Phone Number</label>
                  <p class="text-gray-900">{{ profile?.phone || 'Not provided' }}</p>
                </div>
                
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-500 mb-1">Address</label>
                  <p class="text-gray-900">{{ profile?.address || 'Not provided' }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">City</label>
                  <p class="text-gray-900">{{ profile?.city || 'Not provided' }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Postal Code</label>
                  <p class="text-gray-900">{{ profile?.postal_code || 'Not provided' }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Country</label>
                  <p class="text-gray-900">{{ profile?.country || 'Not provided' }}</p>
                </div>
              </div>
            </div>

            <!-- Edit Mode -->
            <form v-else @submit.prevent="saveProfile" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
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
                
                <div>
                  <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                    Postal Code
                  </label>
                  <input
                    id="postal_code"
                    v-model="form.postal_code"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors"
                    placeholder="1000"
                  />
                </div>
                
                <div class="md:col-span-2">
                  <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                    Address
                  </label>
                  <textarea
                    id="address"
                    v-model="form.address"
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors resize-none"
                    placeholder="Enter your full address"
                  ></textarea>
                </div>
                
                <div>
                  <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                    City
                  </label>
                  <input
                    id="city"
                    v-model="form.city"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors"
                    placeholder="Manila"
                  />
                </div>
                
                <div>
                  <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                    Country
                  </label>
                  <input
                    id="country"
                    v-model="form.country"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors"
                    placeholder="Philippines"
                  />
                </div>
              </div>

              <!-- Form Actions -->
              <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
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
    </div>
  </div>
</template>

