<template>
  <div class="min-h-screen bg-white">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-zinc-900 to-zinc-700 text-white py-20">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 class="text-4xl md:text-5xl font-bold mb-6">About Rapollo</h1>
          <p class="text-xl md:text-2xl text-gray-300 max-w-3xl mx-auto">
            Your premier destination for quality fashion and lifestyle products
          </p>
        </div>
      </div>
    </div>

    <!-- Our Story Section -->
    <div class="py-16 bg-gray-50">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <div>
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Our Story</h2>
            <p class="text-lg text-gray-600 mb-6">
              Founded with a passion for bringing quality fashion and lifestyle products to our customers, 
              Rapollo has grown from a small startup to a trusted e-commerce platform. We believe that 
              everyone deserves access to stylish, affordable, and high-quality products.
            </p>
            <p class="text-lg text-gray-600 mb-6">
              Our journey began with a simple mission: to make shopping for clothes, accessories, and 
              lifestyle products an enjoyable and convenient experience. Today, we're proud to serve 
              thousands of customers with our carefully curated selection of products.
            </p>
            <div class="flex flex-wrap gap-4">
              <div class="bg-white p-4 rounded-lg shadow-sm">
                <h3 class="font-semibold text-gray-900">10K+</h3>
                <p class="text-sm text-gray-600">Happy Customers</p>
              </div>
              <div class="bg-white p-4 rounded-lg shadow-sm">
                <h3 class="font-semibold text-gray-900">500+</h3>
                <p class="text-sm text-gray-600">Products</p>
              </div>
              <div class="bg-white p-4 rounded-lg shadow-sm">
                <h3 class="font-semibold text-gray-900">50+</h3>
                <p class="text-sm text-gray-600">Brands</p>
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
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Our Mission & Values</h2>
          <p class="text-lg text-gray-600 max-w-2xl mx-auto">
            We're committed to providing exceptional products and service while maintaining our core values
          </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="text-center">
            <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Quality First</h3>
            <p class="text-gray-600">
              We carefully select every product to ensure it meets our high standards for quality and durability.
            </p>
          </div>
          
          <div class="text-center">
            <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Customer Focus</h3>
            <p class="text-gray-600">
              Your satisfaction is our priority. We're here to help you find exactly what you're looking for.
            </p>
          </div>
          
          <div class="text-center">
            <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Innovation</h3>
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
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Meet Our Team</h2>
          <p class="text-lg text-gray-600">
            The passionate people behind Rapollo
          </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <img 
              src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80" 
              alt="Team Member" 
              class="w-24 h-24 rounded-full mx-auto mb-4 object-cover"
            />
            <h3 class="text-xl font-semibold text-gray-900 mb-2">John Smith</h3>
            <p class="text-blue-600 mb-3">CEO & Founder</p>
            <p class="text-gray-600 text-sm">
              Passionate about creating amazing shopping experiences and building lasting customer relationships.
            </p>
          </div>
          
          <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <img 
              src="https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80" 
              alt="Team Member" 
              class="w-24 h-24 rounded-full mx-auto mb-4 object-cover"
            />
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Sarah Johnson</h3>
            <p class="text-blue-600 mb-3">Head of Operations</p>
            <p class="text-gray-600 text-sm">
              Ensures smooth operations and exceptional customer service across all touchpoints.
            </p>
          </div>
          
          <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <img 
              src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80" 
              alt="Team Member" 
              class="w-24 h-24 rounded-full mx-auto mb-4 object-cover"
            />
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Mike Chen</h3>
            <p class="text-blue-600 mb-3">Head of Technology</p>
            <p class="text-gray-600 text-sm">
              Leads our technical team in building innovative solutions for better user experiences.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <CTA />
  </div>
</template>

<script setup lang="ts">
// Set page title and meta
useHead({
  title: 'About Us - Rapollo E-commerce',
  meta: [
    { name: 'description', content: 'Learn about Rapollo, your premier destination for quality fashion and lifestyle products. Discover our story, mission, and the team behind our success.' }
  ]
})
</script>