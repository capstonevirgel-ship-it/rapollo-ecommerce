<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import Select from '@/components/Select.vue'

// Emits: update:modelValue with the payload of address fields
const props = defineProps<{
  modelValue?: {
    street?: string
    barangay?: string
    city?: string
    province?: string
    zipcode?: string
  } | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: any): void
}>()

const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])

const street = ref<string>('')
const zipcode = ref<string>('')
const provinceId = ref<string>('')
const provinceName = ref<string>('')
const cityId = ref<string>('')
const cityName = ref<string>('')
const barangayName = ref<string>('')

const loading = ref<{ provinces: boolean; cities: boolean; barangays: boolean }>({ provinces: false, cities: false, barangays: false })

const fetchProvinces = async () => {
  loading.value.provinces = true
  try {
    const res = await $fetch<any[]>('/api/psgc/provinces')
    provinces.value = Array.isArray(res) ? res : []
  } finally {
    loading.value.provinces = false
  }
}

const fetchCities = async (provId: string) => {
  if (!provId) { cities.value = []; return }
  loading.value.cities = true
  try {
    // The backend expects the province PSGC id and will call /municipal-city?id={provinceId}
    const res = await $fetch<any[]>('/api/psgc/cities-municipalities', { params: { provinceId: provId } })
    const list = Array.isArray(res) ? res : []
    // Ensure only City/Municipality entries populate this select
    cities.value = list.filter((row: any) => {
      const lvl = String(row?.geographic_level || '').toLowerCase()
      return lvl === 'city' || lvl === 'mun'
    })
  } finally {
    loading.value.cities = false
  }
}

const fetchBarangays = async (cityMunId: string) => {
  if (!cityMunId) { barangays.value = []; return }
  loading.value.barangays = true
  try {
    const res = await $fetch<any[]>('/api/psgc/barangays', { params: { cityMunicipalityId: cityMunId } })
    barangays.value = Array.isArray(res) ? res : []
  } finally {
    loading.value.barangays = false
  }
}

watch(provinceId, async (id) => {
  const sel = provinces.value.find(p => p.psgc_id === id)
  provinceName.value = sel?.name || ''
  cityId.value = ''
  cityName.value = ''
  barangayName.value = ''
  await fetchCities(id)
  emitAddress()
})

watch(cityId, async (id) => {
  const sel = cities.value.find(c => c.psgc_id === id)
  cityName.value = sel?.name || ''
  barangayName.value = ''
  await fetchBarangays(id)
  emitAddress()
})

watch([street, zipcode, barangayName], () => emitAddress())

const emitAddress = () => {
  emit('update:modelValue', {
    street: street.value?.trim() || '',
    barangay: barangayName.value?.trim() || '',
    city: cityName.value?.trim() || '',
    province: provinceName.value?.trim() || '',
    zipcode: zipcode.value?.trim() || ''
  })
}

onMounted(async () => {
  await fetchProvinces()

  // Optionally hydrate from v-model
  if (props.modelValue) {
    street.value = props.modelValue.street || ''
    zipcode.value = props.modelValue.zipcode || ''
  }
})

// Build Select.vue options
const provinceOptions = computed(() => (provinces.value || []).map((p: any) => ({ value: p.psgc_id, label: p.name })))
const cityOptions = computed(() => (cities.value || []).map((c: any) => ({ value: c.psgc_id, label: c.name })))
const barangayOptions = computed(() => (barangays.value || []).map((b: any) => ({ value: b.name, label: b.name })))
</script>

<template>
  <div class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
      <Select
        v-model="provinceId"
        :options="provinceOptions"
        placeholder="-- Select Province --"
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">City / Municipality</label>
      <Select
        v-model="cityId"
        :options="cityOptions"
        :disabled="!provinceId"
        placeholder="-- Select City/Municipality --"
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
      <Select
        v-model="barangayName"
        :options="barangayOptions"
        :disabled="!cityId"
        placeholder="-- Select Barangay --"
      />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Street</label>
        <input v-model="street" type="text" class="w-full border rounded px-3 py-2" placeholder="House No., Street" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Zip Code</label>
        <input v-model="zipcode" type="text" class="w-full border rounded px-3 py-2" placeholder="e.g., 6000" />
      </div>
    </div>
  </div>
</template>


