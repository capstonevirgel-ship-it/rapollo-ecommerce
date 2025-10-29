<script setup lang="ts">
import { useSettingsStore } from '~/stores/settings'

// Set page title
useHead({
  title: 'Find Store | RAPOLLO',
  meta: [
    { name: 'description', content: 'Find Rapollo E-commerce store locations near you.' }
  ]
})

const settingsStore = useSettingsStore()

// Load settings on mount
onMounted(async () => {
  await settingsStore.fetchSettings()
})

// Google Maps URL for directions
const googleMapsUrl = 'https://www.google.com/maps/place/Apollo+Sports+Bar/@10.3495858,123.9487349,17z/data=!3m1!4b1!4m6!3m5!1s0x33a999da5b97a80f:0xa12258794ff22b56!8m2!3d10.3495858!4d123.9487349!16s%2Fg%2F11c0q8q8q8'
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Find Our Store</h1>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          Visit us at our physical location and experience our products in person
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <!-- Google Maps -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <div class="p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center">
              <Icon name="mdi:map" class="mr-3" />
              Location Map
            </h2>
          </div>
          <div class="relative">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d245.30648046083556!2d123.94873493250614!3d10.349585840747995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33a999da5b97a80f%3A0xa12258794ff22b56!2sApollo%20Sports%20Bar!5e0!3m2!1sen!2sph!4v1761154289778!5m2!1sen!2sph"
              width="100%"
              height="450"
              style="border:0;"
              allowfullscreen="false"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              class="w-full"
            ></iframe>
          </div>
        </div>

        <!-- Store Information -->
        <div class="space-y-8">
          <!-- Store Details -->
          <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="flex items-center mb-6">
              <Icon name="mdi:store" class="text-3xl text-zinc-900 mr-3" />
              <h2 class="text-2xl font-bold text-gray-900">Store Information</h2>
            </div>
            
            <div class="space-y-6">
              <!-- Store Name -->
              <div class="flex items-start">
                <Icon name="mdi:storefront" class="text-xl text-gray-500 mr-3 mt-1" />
                <div>
                  <h3 class="font-semibold text-gray-900 text-lg">Apollo Sports Bar</h3>
                  <p class="text-gray-600">Our main store location</p>
                </div>
              </div>

              <!-- Address -->
              <div class="flex items-start">
                <Icon name="mdi:map-marker" class="text-xl text-gray-500 mr-3 mt-1" />
                <div>
                  <h4 class="font-semibold text-gray-900">Address</h4>
                  <p class="text-gray-600 leading-relaxed">
                    {{ settingsStore.contactAddress || '123 Main Street, Manila, Philippines' }}
                  </p>
                </div>
              </div>

              <!-- Phone -->
              <div class="flex items-start">
                <Icon name="mdi:phone" class="text-xl text-gray-500 mr-3 mt-1" />
                <div>
                  <h4 class="font-semibold text-gray-900">Phone</h4>
                  <a 
                    :href="`tel:${settingsStore.contactPhone || '+63 123 456 7890'}`"
                    class="text-zinc-900 hover:text-zinc-700 transition-colors"
                  >
                    {{ settingsStore.contactPhone || '+63 123 456 7890' }}
                  </a>
                </div>
              </div>

              <!-- Email -->
              <div class="flex items-start">
                <Icon name="mdi:email" class="text-xl text-gray-500 mr-3 mt-1" />
                <div>
                  <h4 class="font-semibold text-gray-900">Email</h4>
                  <a 
                    :href="`mailto:${settingsStore.contactEmail || 'info@rapollo.com'}`"
                    class="text-zinc-900 hover:text-zinc-700 transition-colors"
                  >
                    {{ settingsStore.contactEmail || 'info@rapollo.com' }}
                  </a>
                </div>
              </div>
            </div>
          </div>


          <!-- Action Buttons -->
          <div class="space-y-4">
            <a
              :href="googleMapsUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="w-full bg-zinc-900 text-white py-4 px-6 rounded-lg hover:bg-zinc-800 transition-colors flex items-center justify-center font-semibold"
            >
              <Icon name="mdi:map-marker" class="mr-2" />
              Get Directions
            </a>
            
            <a
              :href="`tel:${settingsStore.contactPhone || '+63 123 456 7890'}`"
              class="w-full bg-gray-100 text-gray-900 py-4 px-6 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center font-semibold"
            >
              <Icon name="mdi:phone" class="mr-2" />
              Call Store
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>