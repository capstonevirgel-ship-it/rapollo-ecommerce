<script setup lang="ts">
import { computed } from 'vue'
import { useSettingsStore } from '~/stores/settings'

const settingsStore = useSettingsStore()
const currentYear = new Date().getFullYear()

const footerLinks = [
  { name: 'Events', url: '/events' },
  { name: 'About', url: '/about' },
  { name: 'Contact', url: '/contact' },
  { name: 'Privacy', url: '/privacy' }
]

// Dynamic social links from settings
const socialLinks = computed(() => {
  const links = []
  if (settingsStore.contactFacebook) {
    links.push({ name: 'Facebook', icon: 'mdi:facebook', url: settingsStore.contactFacebook })
  }
  if (settingsStore.contactTwitter) {
    links.push({ name: 'Twitter', icon: 'mdi:twitter', url: settingsStore.contactTwitter })
  }
  if (settingsStore.contactInstagram) {
    links.push({ name: 'Instagram', icon: 'mdi:instagram', url: settingsStore.contactInstagram })
  }
  return links
})
</script>

<template>
  <footer class="bg-zinc-900 text-gray-300 py-12">
    <div class="container mx-auto px-4">
      <!-- Main Content -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
        <!-- First Column - Brand Info and Social Links -->
        <div class="flex flex-col">
          <div class="mb-6">
            <h2 class="text-2xl font-bold text-white mb-1 font-winner-extra-bold">{{ settingsStore.siteName || 'Rapollo E-Commerce' }}</h2>
            <p class="text-gray-400">{{ settingsStore.siteAbout || 'Welcome to our e-commerce store. We offer quality products at affordable prices.' }}</p>
          </div>
          <div>
            <h3 class="text-white text-lg font-semibold mb-4 font-winner-extra-bold">Follow Us</h3>
            <div class="flex gap-4">
              <a 
                v-for="social in socialLinks" 
                :key="social.name"
                :href="social.url"
                :aria-label="social.name"
                target="_blank"
                rel="noopener noreferrer"
                class="text-gray-400 hover:text-white transition-colors"
              >
                <Icon :name="social.icon" class="w-5 h-5" />
              </a>
            </div>
          </div>
        </div>

        <!-- Second Column - Contact Info -->
        <div>
          <h3 class="text-white text-lg font-semibold mb-4 font-winner-extra-bold">Contact Us</h3>
          <address class="not-italic space-y-2">
            <p class="flex items-start">
              <Icon name="mdi:map-marker" class="mt-1 mr-2 flex-shrink-0" />
              <span>{{ settingsStore.contactAddress || '123 Main Street, Manila, Philippines' }}</span>
            </p>
            <p class="flex items-center">
              <Icon name="mdi:email" class="mr-2" />
              <a :href="`mailto:${settingsStore.contactEmail || 'info@rapollo.com'}`" class="hover:text-white transition-colors">
                {{ settingsStore.contactEmail || 'info@rapollo.com' }}
              </a>
            </p>
            <p class="flex items-center">
              <Icon name="mdi:phone" class="mr-2" />
              <a :href="`tel:${(settingsStore.contactPhone || '+63 123 456 7890').replace(/[^0-9]/g, '')}`" class="hover:text-white transition-colors">
                {{ settingsStore.contactPhone || '+63 123 456 7890' }}
              </a>
            </p>
          </address>
        </div>

        <!-- Third Column - Links -->
        <div>
          <h3 class="text-white text-lg font-semibold mb-4 font-winner-extra-bold">Links</h3>
          <ul class="space-y-2">
            <li v-for="link in footerLinks" :key="link.name">
              <a :href="link.url" class="hover:text-white transition-colors">
                {{ link.name }}
              </a>
            </li>
          </ul>
        </div>
      </div>

       <!-- Copyright -->
       <div class="text-center text-sm text-gray-500 border-t border-gray-800 pt-6">
         &copy; {{ currentYear }} {{ settingsStore.siteName || 'Rapollo E-Commerce' }}. All rights reserved.
       </div>
    </div>
  </footer>
</template>