<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSettingsStore } from '~/stores/settings'
import { useAuthStore } from '~/stores/auth'
import Select from '~/components/Select.vue'

const settingsStore = useSettingsStore()
const authStore = useAuthStore()

// Subject options for the select dropdown
const subjectOptions = [
  { value: 'general', label: 'General Inquiry' },
  { value: 'order', label: 'Order Support' },
  { value: 'return', label: 'Returns & Exchanges' },
  { value: 'technical', label: 'Technical Support' },
  { value: 'partnership', label: 'Partnership' },
  { value: 'other', label: 'Other' }
]

// Form data
const form = ref({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  subject: '',
  message: ''
})

// Form state
const isSubmitting = ref(false)
const message = ref('')
const messageType = ref('')

// Load settings on mount
onMounted(async () => {
  await settingsStore.fetchSettings()
})

// Submit form
const submitForm = async () => {
  isSubmitting.value = true
  message.value = ''
  
  try {
    await $fetch('/api/contact', {
      method: 'POST',
      body: form.value
    })
    
    // Redirect to thank you page
    await navigateTo('/thank-you')
  } catch (error: any) {
    message.value = error.data?.message || 'Sorry, there was an error sending your message. Please try again.'
    messageType.value = 'error'
    isSubmitting.value = false
  }
}

// Set page title and meta
useHead({
  title: 'Contact Us | RAPOLLO',
  meta: [
    { name: 'description', content: 'Get in touch with Rapollo. Contact our customer support team for any questions about orders, products, or services.' }
  ]
})
</script>

<template>
  <div class="min-h-screen bg-white">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-zinc-900 to-zinc-700 text-white py-20">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 class="text-4xl md:text-5xl font-winner-extra-bold mb-6">Contact Us</h1>
          <p class="text-xl md:text-2xl text-gray-300 max-w-3xl mx-auto">
            We'd love to hear from you. Get in touch with our team!
          </p>
        </div>
      </div>
    </div>

    <!-- Contact Form & Info Section -->
    <div class="py-16">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
          <!-- Contact Form -->
          <div>
            <h2 class="text-3xl font-winner-extra-bold text-gray-900 mb-6">Send us a Message</h2>
            <form @submit.prevent="submitForm" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">
                    First Name *
                  </label>
                  <input
                    type="text"
                    id="firstName"
                    v-model="form.firstName"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter your first name"
                  />
                </div>
                <div>
                  <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">
                    Last Name *
                  </label>
                  <input
                    type="text"
                    id="lastName"
                    v-model="form.lastName"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter your last name"
                  />
                </div>
              </div>

              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                  Email Address *
                </label>
                <input
                  type="email"
                  id="email"
                  v-model="form.email"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter your email address"
                />
              </div>

              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                  Phone Number
                </label>
                <input
                  type="tel"
                  id="phone"
                  v-model="form.phone"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter your phone number"
                />
              </div>

              <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                  Subject *
                </label>
                <Select
                  v-model="form.subject"
                  :options="subjectOptions"
                  placeholder="Select a subject"
                  size="lg"
                />
              </div>

              <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                  Message *
                </label>
                <textarea
                  id="message"
                  v-model="form.message"
                  required
                  rows="6"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Tell us how we can help you..."
                ></textarea>
              </div>

              <button
                type="submit"
                :disabled="isSubmitting || authStore.isAdmin"
                class="w-full bg-zinc-900 text-white py-3 px-6 rounded-lg font-semibold hover:bg-zinc-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isSubmitting" class="flex items-center justify-center">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Sending...
                </span>
                <span v-else-if="authStore.isAdmin">Contact form disabled for administrators</span>
                <span v-else>Send Message</span>
              </button>
            </form>

            <!-- Success/Error Messages -->
            <div v-if="message" class="mt-4 p-4 rounded-lg" :class="messageType === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
              {{ message }}
            </div>
          </div>

          <!-- Contact Information -->
          <div>
            <h2 class="text-3xl font-winner-extra-bold text-gray-900 mb-6">Get in Touch</h2>
            <p class="text-lg text-gray-600 mb-8">
              We're here to help! Reach out to us through any of the channels below, and we'll get back to you as soon as possible.
            </p>

            <div class="space-y-6">
              <!-- Email -->
              <div class="flex items-start">
                <div class="bg-zinc-300 px-3 py-2 rounded-lg mr-4">
                  <Icon name="mdi:email" class="w-6 h-6 text-black" />
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Email</h3>
                  <a 
                    :href="`mailto:${settingsStore.contactEmail || 'info@rapollo.com'}`"
                    class="text-gray-600 hover:text-zinc-900 transition-colors"
                  >
                    {{ settingsStore.contactEmail || 'info@rapollo.com' }}
                  </a>
                </div>
              </div>

              <!-- Phone -->
              <div class="flex items-start">
                <div class="bg-zinc-300 px-3 py-2 rounded-lg mr-4">
                  <Icon name="mdi:phone" class="w-6 h-6 text-zinc-800" />
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Phone</h3>
                  <a 
                    :href="`tel:${settingsStore.contactPhone || '+63 123 456 7890'}`"
                    class="text-gray-600 hover:text-zinc-900 transition-colors"
                  >
                    {{ settingsStore.contactPhone || '+63 123 456 7890' }}
                  </a>
                </div>
              </div>

              <!-- Address -->
              <div class="flex items-start">
                <div class="bg-zinc-300 px-3 py-2 rounded-lg mr-4">
                  <Icon name="mdi:map-marker" class="w-6 h-6 text-zinc-800" />
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Address</h3>
                  <p class="text-gray-600 whitespace-pre-line">
                    {{ settingsStore.contactAddress || '123 Main Street, Manila, Philippines' }}
                  </p>
                </div>
              </div>

              <!-- Social Media -->
              <div v-if="settingsStore.contactFacebook || settingsStore.contactInstagram || settingsStore.contactTwitter" class="flex items-start">
                <div class="bg-zinc-300 px-3 py-2 rounded-lg mr-4">
                  <Icon name="mdi:share-variant" class="w-6 h-6 text-zinc-800" />
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Follow Us</h3>
                  <div class="flex space-x-4 mt-2">
                    <a 
                      v-if="settingsStore.contactFacebook"
                      :href="settingsStore.contactFacebook"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-gray-600 hover:text-zinc-900 transition-colors flex items-center"
                    >
                      <Icon name="mdi:facebook" class="w-5 h-5 mr-1" />
                      Facebook
                    </a>
                    <a 
                      v-if="settingsStore.contactInstagram"
                      :href="settingsStore.contactInstagram"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-gray-600 hover:text-zinc-900 transition-colors flex items-center"
                    >
                      <Icon name="mdi:instagram" class="w-5 h-5 mr-1" />
                      Instagram
                    </a>
                    <a 
                      v-if="settingsStore.contactTwitter"
                      :href="settingsStore.contactTwitter"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-gray-600 hover:text-zinc-900 transition-colors flex items-center"
                    >
                      <Icon name="mdi:twitter" class="w-5 h-5 mr-1" />
                      Twitter
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>


    <!-- CTA Section -->
    <CTA 
      title="Have Questions About Our Products?"
      description="Our team is here to help you find the perfect items for your style and needs"
      button-text="Browse Products"
      link="/shop"
    />
  </div>
</template>

