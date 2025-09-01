<script setup lang="ts">
const currentYear = new Date().getFullYear()

const brandInfo = {
  name: "YourBrand",
  tagline: "Quality products for modern living"
}

const footerLinks = [
  { name: 'Events', url: '#' },
  { name: 'About', url: '#' },
  { name: 'Contact', url: '#' },
  { name: 'Privacy', url: '#' }
]

const socialLinks = [
  { name: 'Facebook', icon: 'mdi:facebook', url: '#' },
  { name: 'Twitter', icon: 'mdi:twitter', url: '#' },
  { name: 'Instagram', icon: 'mdi:instagram', url: '#' }
]

const contactInfo = {
  email: 'hello@example.com',
  phone: '(123) 456-7890',
  address: '123 Main Street, City'
}
</script>

<template>
  <footer class="bg-zinc-900 text-gray-300 py-12">
    <div class="container mx-auto px-4">
      <!-- Main Content -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
        <!-- First Column - Brand Info and Social Links -->
        <div class="flex flex-col">
          <div class="mb-6">
            <h2 class="text-2xl font-bold text-white mb-1">{{ brandInfo.name }}</h2>
            <p class="text-gray-400">{{ brandInfo.tagline }}</p>
          </div>
          <div>
            <h3 class="text-white text-lg font-semibold mb-4">Follow Us</h3>
            <div class="flex gap-4">
              <a 
                v-for="social in socialLinks" 
                :key="social.name"
                :href="social.url"
                :aria-label="social.name"
                class="text-gray-400 hover:text-white transition-colors"
              >
                <Icon :name="social.icon" class="w-5 h-5" />
              </a>
            </div>
          </div>
        </div>

        <!-- Second Column - Contact Info -->
        <div>
          <h3 class="text-white text-lg font-semibold mb-4">Contact Us</h3>
          <address class="not-italic space-y-2">
            <p class="flex items-start">
              <Icon name="mdi:map-marker" class="mt-1 mr-2 flex-shrink-0" />
              <span>{{ contactInfo.address }}</span>
            </p>
            <p class="flex items-center">
              <Icon name="mdi:email" class="mr-2" />
              <a :href="`mailto:${contactInfo.email}`" class="hover:text-white transition-colors">
                {{ contactInfo.email }}
              </a>
            </p>
            <p class="flex items-center">
              <Icon name="mdi:phone" class="mr-2" />
              <a :href="`tel:${contactInfo.phone.replace(/[^0-9]/g, '')}`" class="hover:text-white transition-colors">
                {{ contactInfo.phone }}
              </a>
            </p>
          </address>
        </div>

        <!-- Third Column - Links -->
        <div>
          <h3 class="text-white text-lg font-semibold mb-4">Links</h3>
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
        &copy; {{ currentYear }} {{ brandInfo.name }}. All rights reserved.
      </div>
    </div>
  </footer>
</template>