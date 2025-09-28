<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-6">Billing Information</h3>
    
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Personal Information -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="firstName" class="block text-sm font-medium text-gray-700 mb-1">
            First Name *
          </label>
          <input
            id="firstName"
            v-model="form.firstName"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.firstName }"
          />
          <p v-if="errors.firstName" class="mt-1 text-sm text-red-600">{{ errors.firstName }}</p>
        </div>
        
        <div>
          <label for="lastName" class="block text-sm font-medium text-gray-700 mb-1">
            Last Name *
          </label>
          <input
            id="lastName"
            v-model="form.lastName"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.lastName }"
          />
          <p v-if="errors.lastName" class="mt-1 text-sm text-red-600">{{ errors.lastName }}</p>
        </div>
      </div>

      <!-- Contact Information -->
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
          Email Address *
        </label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{ 'border-red-500': errors.email }"
        />
        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
      </div>

      <div>
        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
          Phone Number *
        </label>
        <input
          id="phone"
          v-model="form.phone"
          type="tel"
          required
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{ 'border-red-500': errors.phone }"
        />
        <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</p>
      </div>

      <!-- Billing Address -->
      <div>
        <h4 class="text-md font-medium text-gray-900 mb-3">Billing Address</h4>
        <div class="space-y-4">
          <div>
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
              Street Address *
            </label>
            <input
              id="address"
              v-model="form.address"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.address }"
            />
            <p v-if="errors.address" class="mt-1 text-sm text-red-600">{{ errors.address }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                City *
              </label>
              <input
                id="city"
                v-model="form.city"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.city }"
              />
              <p v-if="errors.city" class="mt-1 text-sm text-red-600">{{ errors.city }}</p>
            </div>

            <div>
              <label for="state" class="block text-sm font-medium text-gray-700 mb-1">
                State/Province *
              </label>
              <input
                id="state"
                v-model="form.state"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.state }"
              />
              <p v-if="errors.state" class="mt-1 text-sm text-red-600">{{ errors.state }}</p>
            </div>

            <div>
              <label for="zipCode" class="block text-sm font-medium text-gray-700 mb-1">
                ZIP/Postal Code *
              </label>
              <input
                id="zipCode"
                v-model="form.zipCode"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.zipCode }"
              />
              <p v-if="errors.zipCode" class="mt-1 text-sm text-red-600">{{ errors.zipCode }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Method Selection -->
      <div>
        <h4 class="text-md font-medium text-gray-900 mb-3">Payment Method</h4>
        <div class="space-y-3">
          <label class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
            <input
              v-model="form.paymentMethod"
              type="radio"
              value="cod"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
            />
            <div class="ml-3 flex items-center">
              <svg class="h-6 w-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
              <span class="text-sm font-medium text-gray-700">Cash on Delivery (COD)</span>
              <span class="ml-auto text-xs text-gray-500">Pay when delivered</span>
            </div>
          </label>

          <label class="flex items-center p-3 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
            <input
              v-model="form.paymentMethod"
              type="radio"
              value="card"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
            />
            <div class="ml-3 flex items-center">
              <svg class="h-6 w-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              <span class="text-sm font-medium text-gray-700">Credit/Debit Card</span>
              <span class="ml-auto text-xs text-gray-500">Visa, Mastercard, etc.</span>
            </div>
          </label>
        </div>
        <p v-if="errors.paymentMethod" class="mt-1 text-sm text-red-600">{{ errors.paymentMethod }}</p>
      </div>

      <!-- Submit Button -->
      <div class="pt-4 border-t">
        <button
          type="submit"
          :disabled="isLoading"
          class="w-full bg-blue-600 text-white py-3 px-4 rounded-md font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="isLoading">Processing...</span>
          <span v-else>Continue to Review</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

interface BillingFormData {
  firstName: string
  lastName: string
  email: string
  phone: string
  address: string
  city: string
  state: string
  zipCode: string
  paymentMethod: string
}

const emit = defineEmits<{
  submit: [data: BillingFormData]
}>()

const isLoading = ref(false)
const errors = ref<Partial<BillingFormData>>({})

const form = reactive<BillingFormData>({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  zipCode: '',
  paymentMethod: ''
})

const validateForm = (): boolean => {
  errors.value = {}
  
  if (!form.firstName.trim()) errors.value.firstName = 'First name is required'
  if (!form.lastName.trim()) errors.value.lastName = 'Last name is required'
  if (!form.email.trim()) errors.value.email = 'Email is required'
  if (!form.phone.trim()) errors.value.phone = 'Phone number is required'
  if (!form.address.trim()) errors.value.address = 'Address is required'
  if (!form.city.trim()) errors.value.city = 'City is required'
  if (!form.state.trim()) errors.value.state = 'State/Province is required'
  if (!form.zipCode.trim()) errors.value.zipCode = 'ZIP/Postal code is required'
  if (!form.paymentMethod) errors.value.paymentMethod = 'Please select a payment method'
  
  // Email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (form.email && !emailRegex.test(form.email)) {
    errors.value.email = 'Please enter a valid email address'
  }
  
  // Phone validation
  const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/
  if (form.phone && !phoneRegex.test(form.phone.replace(/[\s\-\(\)]/g, ''))) {
    errors.value.phone = 'Please enter a valid phone number'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = () => {
  if (!validateForm()) return
  
  isLoading.value = true
  
  // Simulate processing delay
  setTimeout(() => {
    emit('submit', { ...form })
    isLoading.value = false
  }, 500)
}
</script>
