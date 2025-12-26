<template>
  <div class="min-h-screen bg-white">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-zinc-900 to-zinc-700 text-white py-20">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 class="text-4xl md:text-5xl font-poppins mb-6">About monogram</h1>
          <p class="text-xl md:text-2xl text-gray-300 max-w-3xl mx-auto">
            Premium brands and quality clothing for every style
          </p>
        </div>
      </div>
    </div>

    <!-- Our Story Section -->
    <div class="py-16 bg-gray-50">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-1 gap-12 items-center">
          <div>
            <h2 class="text-3xl font-poppins text-gray-900 mb-6">Our Story</h2>
            <p class="text-lg text-gray-600 mb-6">
              Born from a passion for quality and style, monogram started as a dream to bring premium brands 
              and quality clothing to the Philippines. We're not just another e-commerce platform - we're a 
              destination that celebrates quality fashion, diverse brands, and exceptional customer service 
              across the islands.
            </p>
            <p class="text-lg text-gray-600 mb-6">
              From local favorites to international brands, we've curated the best selection for you. Our 
              products aren't just clothing - they're expressions of style, quality craftsmanship, and 
              timeless fashion. We're here to serve the next generation of fashion-conscious Filipinos 
              with the best brands and event experiences.
            </p>
             <!-- Stats Section -->
             <div v-if="loading">
               <StatsSkeleton />
             </div>
             <div v-else class="flex flex-wrap gap-4">
               <div class="bg-white p-4 rounded-lg shadow-sm">
                 <h3 class="font-semibold text-gray-900">
                   {{ formatNumber(stats.totalCustomers) }}
                 </h3>
                 <p class="text-sm text-gray-600">Happy Customers</p>
               </div>
               <div class="bg-white p-4 rounded-lg shadow-sm">
                 <h3 class="font-semibold text-gray-900">
                   {{ formatNumber(stats.totalProducts) }}
                 </h3>
                 <p class="text-sm text-gray-600">Products</p>
               </div>
               <div class="bg-white p-4 rounded-lg shadow-sm">
                 <h3 class="font-semibold text-gray-900">
                   {{ formatNumber(stats.totalOrders) }}
                 </h3>
                 <p class="text-sm text-gray-600">Orders Delivered</p>
               </div>
             </div>
          </div>
          <div class="relative">
            <img 
              src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
              alt="Our Story" 
              class="w-full h-96 object-cover rounded-lg shadow-lg"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Mission & Values Section -->
    <div class="py-16">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-poppins text-gray-900 mb-4">Our Mission & Values</h2>
          <p class="text-lg text-gray-600 max-w-2xl mx-auto">
            We're committed to providing exceptional products and service while maintaining our core values
          </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="text-center">
            <div class="bg-zinc-300 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-zinc-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-poppins text-gray-900 mb-3">Quality First</h3>
            <p class="text-gray-600">
              We carefully select every product to ensure it meets our high standards for quality and durability.
            </p>
          </div>
          
          <div class="text-center">
            <div class="bg-zinc-300 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-zinc-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-poppins text-gray-900 mb-3">Customer Focus</h3>
            <p class="text-gray-600">
              Your satisfaction is our priority. We're here to help you find exactly what you're looking for.
            </p>
          </div>
          
          <div class="text-center">
            <div class="bg-zinc-300 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-zinc-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="text-xl font-poppins text-gray-900 mb-3">Innovation</h3>
            <p class="text-gray-600">
              We continuously improve our platform and services to provide the best shopping experience.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Team Section -->
    <div class="py-16 bg-gray-50">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-poppins text-gray-900 mb-4">Meet Our Team</h2>
          <p class="text-lg text-gray-600">
            The real ones behind the brand - Visayas' finest
          </p>
        </div>
        
         <!-- Loading State -->
         <TeamSkeleton v-if="loading" />

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
          <div class="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md mx-auto">
            <svg class="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <h3 class="text-lg font-medium text-red-800 mb-2">Failed to Load Team</h3>
            <p class="text-red-600">{{ error }}</p>
          </div>
        </div>

        <!-- Team Members -->
        <div v-else-if="teamMembers.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div 
            v-for="member in teamMembers" 
            :key="member.name"
            class="bg-white rounded-lg shadow-sm p-6 text-center"
          >
            <img 
              :src="member.image ? getImageUrl(member.image) : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80'" 
              :alt="member.name" 
              class="w-24 h-24 rounded-full mx-auto mb-4 object-cover"
               @error="($event.target as HTMLImageElement).src = 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80'"
            />
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ member.name }}</h3>
            <p class="text-blue-600 mb-3">{{ member.position }}</p>
            <p class="text-gray-600 text-sm">
              {{ member.email }}
            </p>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <div class="bg-gray-50 rounded-lg p-8 max-w-md mx-auto">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="text-lg font-poppins text-gray-900 mb-2">Crew Coming Soon</h3>
            <p class="text-gray-600">The squad is getting ready to drop their profiles. Stay tuned for the real ones!</p>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <CTA />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useDashboardStore } from '~/stores/dashboard'
import { useSettingsStore } from '~/stores/settings'
import { getImageUrl } from '~/helpers/imageHelper'
import type { TeamMember } from '~/types/settings'
import StatsSkeleton from '~/components/skeleton/StatsSkeleton.vue'
import TeamSkeleton from '~/components/skeleton/TeamSkeleton.vue'

// Set page title and meta
useHead({
  title: 'About Us | monogram',
  meta: [
    { name: 'description', content: 'Meet monogram - Your premier destination for premium brands and quality clothing. Discover our story, mission, and commitment to bringing you the best fashion and event experiences in the Philippines.' }
  ]
})

const dashboardStore = useDashboardStore()
const settingsStore = useSettingsStore()

// Reactive data
const stats = ref({
  totalCustomers: 0,
  totalProducts: 0,
  totalOrders: 0
})

const teamMembers = ref<TeamMember[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

// Fetch data on mount
onMounted(async () => {
  try {
    loading.value = true
    
    // Fetch dashboard statistics
    await dashboardStore.fetchStatistics()
    if (dashboardStore.stats) {
      stats.value = {
        totalCustomers: dashboardStore.stats.total_customers,
        totalProducts: dashboardStore.stats.total_products,
        totalOrders: dashboardStore.stats.total_orders
      }
    }
    
    // Fetch team members from settings
    await settingsStore.fetchSettings()
    if (settingsStore.settings.team?.team_members) {
      teamMembers.value = settingsStore.settings.team.team_members
    }
    
  } catch (err: any) {
    error.value = err.message || 'Failed to load data'
    console.error('Error loading about page data:', err)
  } finally {
    loading.value = false
  }
})

// Format numbers with K suffix for large numbers
const formatNumber = (num: number) => {
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K+'
  }
  return num.toString()
}
</script>