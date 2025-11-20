<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useSettingsStore } from '~/stores/settings'
import { useShippingStore, type ShippingPrice, type ShippingPriceForm } from '~/stores/shipping'
import { useTaxStore, type TaxPrice, type TaxPriceForm } from '~/stores/tax'
import { useAlert } from '~/composables/useAlert'
import { getImageUrl } from '~/helpers/imageHelper'
import Dialog from '~/components/Dialog.vue'
import DataTable from '@/components/DataTable.vue'
import AdminActionButton from '@/components/AdminActionButton.vue'
import AdminAddButton from '@/components/AdminAddButton.vue'
import Toggle from '@/components/Toggle.vue'
import ActiveInactiveToggle from '@/components/ActiveInactiveToggle.vue'
import Select from '@/components/Select.vue'
import type { TeamMember } from '~/types/settings'

interface Props {
  activeTab: 'site' | 'contact' | 'social' | 'team' | 'shipping'
  isEditingMode: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'toggle-edit-mode': []
}>()

const settingsStore = useSettingsStore()
const shippingStore = useShippingStore()
const taxStore = useTaxStore()
const { success: showSuccess, error: showError } = useAlert()

// Site Identity
const siteName = ref('')
const siteAbout = ref('')
const siteLogo = ref<string | null>(null)
const logoFile = ref<File | null>(null)
const logoPreview = ref<string | null>(null)
const markLogoForDeletion = ref(false)

// Contact Information
const contactEmail = ref('')
const contactPhone = ref('')
const contactAddress = ref('')

// Store Address (structured) under Contact tab
const storeAddressModel = ref<{ street?: string; barangay?: string; city?: string; province?: string; zipcode?: string } | null>(null)
const storeStreet = ref('')
const storeBarangay = ref('')
const storeCity = ref('')
const storeProvince = ref('')
const storeZipcode = ref('')
const iframeLink = ref('')
const gmapLink = ref('')

// Social Links
const contactFacebook = ref('')
const contactInstagram = ref('')
const contactTwitter = ref('')

// Team Members
const teamMembers = ref<TeamMember[]>([])
const showAddMemberModal = ref(false)
const editingMemberIndex = ref<number | null>(null)
const newMember = ref<TeamMember>({
  name: '',
  position: '',
  email: '',
  image: null
})
const memberImageFile = ref<File | null>(null)
const memberImagePreview = ref<string | null>(null)


// Shipping Management
const showCreateShippingDialog = ref(false)
const showEditShippingDialog = ref(false)
const selectedShippingPrice = ref<ShippingPrice | null>(null)
const isShippingSubmitting = ref(false)

// Tax Management
const showCreateTaxDialog = ref(false)
const showEditTaxDialog = ref(false)
const selectedTaxPrice = ref<TaxPrice | null>(null)
const isTaxSubmitting = ref(false)

const createShippingForm = ref<ShippingPriceForm>({
  region: '',
  price: 0,
  description: '',
  is_active: true
})

const editShippingForm = ref<Partial<ShippingPriceForm>>({})

const createTaxForm = ref<TaxPriceForm>({
  name: '',
  rate: 0,
  description: '',
  is_active: true
})

const editTaxForm = ref<Partial<TaxPriceForm>>({})

// DataTable columns for shipping
const shippingColumns = [
  { label: "Region", key: "region" },
  { label: "Price", key: "price" },
  { label: "Status", key: "status" },
  { label: "Actions", key: "actions" }
]

// DataTable columns for tax
const taxColumns = [
  { label: "Name", key: "name" },
  { label: "Rate (%)", key: "rate" },
  { label: "Status", key: "status" },
  { label: "Actions", key: "actions" }
]

// Loading states
const saving = ref(false)
const togglingShipping = ref<Set<number>>(new Set())
const togglingTax = ref<Set<number>>(new Set())

// Computed region options for Select component
const regionOptions = computed(() => {
  return Object.entries(shippingStore.availableRegions).map(([value, label]) => ({
    value,
    label: label as string
  }))
})

// Computed
const logoUrl = computed(() => {
  if (logoPreview.value) return logoPreview.value
  if (siteLogo.value) return getImageUrl(siteLogo.value)
  return null
})

// Shipping computed properties
const shippingData = computed(() => {
  return shippingStore.shippingPrices.map((shipping) => ({
    id: shipping.id,
    region: shippingStore.availableRegions[shipping.region] || shipping.region,
    price: new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP'
    }).format(shipping.price),
    description: shipping.description || '-',
    status: shipping.is_active ? 'Active' : 'Inactive',
    rawData: shipping
  }))
})

// Load settings and populate fields
const loadSettings = async () => {
  try {
    await settingsStore.fetchSettings()
    const settings = settingsStore.settings
    
    if (settings) {
      // Site Identity
      const site = settings.site || {}
      siteName.value = site.site_name || ''
      siteAbout.value = site.site_about || ''
      siteLogo.value = site.site_logo || null

      // Contact Information
      const contact = settings.contact || {}
      contactEmail.value = contact.contact_email || ''
      contactPhone.value = contact.contact_phone || ''
      contactAddress.value = contact.contact_address || ''

      // Social Links
      contactFacebook.value = contact.contact_facebook || ''
      contactInstagram.value = contact.contact_instagram || ''
      contactTwitter.value = contact.contact_twitter || ''

      // Store Address + Map Links
      const store = settings.store || {}
      storeStreet.value = store.store_street || ''
      storeBarangay.value = store.store_barangay || ''
      storeCity.value = store.store_city || ''
      storeProvince.value = store.store_province || ''
      storeZipcode.value = store.store_zipcode || ''
      iframeLink.value = store.iframe_link || ''
      gmapLink.value = store.gmap_link || ''
      storeAddressModel.value = {
        street: storeStreet.value,
        barangay: storeBarangay.value,
        city: storeCity.value,
        province: storeProvince.value,
        zipcode: storeZipcode.value,
      }

      // Team Members
      const team = settings.team || {}
      teamMembers.value = team.team_members || []

    }
  } catch (error) {
    console.error('Failed to load settings:', error)
    showError('Failed to load settings')
  }
}

// Handle logo file selection
const handleLogoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    logoFile.value = target.files[0]
    markLogoForDeletion.value = false
    const reader = new FileReader()
    reader.onload = (e) => {
      logoPreview.value = e.target?.result as string
    }
    reader.readAsDataURL(target.files[0])
  }
}

// Toggle edit mode
const toggleEditMode = () => {
  emit('toggle-edit-mode')
  if (!props.isEditingMode) {
    loadSettings()
    logoFile.value = null
    logoPreview.value = null
    markLogoForDeletion.value = false
  }
}

// Save site identity
const saveSiteIdentity = async () => {
  try {
    saving.value = true

    if (markLogoForDeletion.value && siteLogo.value) {
      await settingsStore.deleteLogo()
      siteLogo.value = null
    }

    if (logoFile.value) {
      await settingsStore.uploadLogo(logoFile.value)
      logoFile.value = null
      logoPreview.value = null
    }

    const settings = [
      { key: 'site_name', value: siteName.value, group: 'site', type: 'text' },
      { key: 'site_about', value: siteAbout.value, group: 'site', type: 'textarea' }
    ]

    await settingsStore.updateSettings({ settings })
    await loadSettings()
    showSuccess('Site identity updated successfully')
    emit('toggle-edit-mode')
    markLogoForDeletion.value = false
  } catch (error) {
    console.error('Failed to update site identity:', error)
    showError('Failed to update site identity')
  } finally {
    saving.value = false
  }
}

// Save store address (structured)
const saveStoreAddress = async () => {
  try {
    saving.value = true
    const addr = storeAddressModel.value || {}
    const settings = [
      { key: 'store_street', value: addr.street ?? storeStreet.value, group: 'store', type: 'text' },
      { key: 'store_barangay', value: addr.barangay ?? storeBarangay.value, group: 'store', type: 'text' },
      { key: 'store_city', value: addr.city ?? storeCity.value, group: 'store', type: 'text' },
      { key: 'store_province', value: addr.province ?? storeProvince.value, group: 'store', type: 'text' },
      { key: 'store_zipcode', value: addr.zipcode ?? storeZipcode.value, group: 'store', type: 'text' },
    ]
    await settingsStore.updateSettings({ settings })
    await loadSettings()
    showSuccess('Store address updated successfully')
    emit('toggle-edit-mode')
  } catch (error) {
    console.error('Failed to update store address:', error)
    showError('Failed to update store address')
  } finally {
    saving.value = false
  }
}

// Save contact information (includes structured store address + map links)
const saveContactInformation = async () => {
  try {
    saving.value = true

    const addr = storeAddressModel.value || {}
    const settings = [
      { key: 'contact_email', value: contactEmail.value, group: 'contact', type: 'text' },
      { key: 'contact_phone', value: contactPhone.value, group: 'contact', type: 'text' },
      // structured store address
      { key: 'store_street', value: addr.street ?? storeStreet.value, group: 'store', type: 'text' },
      { key: 'store_barangay', value: addr.barangay ?? storeBarangay.value, group: 'store', type: 'text' },
      { key: 'store_city', value: addr.city ?? storeCity.value, group: 'store', type: 'text' },
      { key: 'store_province', value: addr.province ?? storeProvince.value, group: 'store', type: 'text' },
      { key: 'store_zipcode', value: addr.zipcode ?? storeZipcode.value, group: 'store', type: 'text' },
      // map links
      { key: 'iframe_link', value: iframeLink.value, group: 'store', type: 'text' },
      { key: 'gmap_link', value: gmapLink.value, group: 'store', type: 'text' },
    ]

    await settingsStore.updateSettings({ settings })
    await loadSettings()
    showSuccess('Contact information updated successfully')
    emit('toggle-edit-mode')
  } catch (error) {
    console.error('Failed to update contact information:', error)
    showError('Failed to update contact information')
  } finally {
    saving.value = false
  }
}

// Save social links
const saveSocialLinks = async () => {
  try {
    saving.value = true

    const settings = [
      { key: 'contact_facebook', value: contactFacebook.value, group: 'contact', type: 'text' },
      { key: 'contact_instagram', value: contactInstagram.value, group: 'contact', type: 'text' },
      { key: 'contact_twitter', value: contactTwitter.value, group: 'contact', type: 'text' }
    ]

    await settingsStore.updateSettings({ settings })
    await loadSettings()
    showSuccess('Social links updated successfully')
    emit('toggle-edit-mode')
  } catch (error) {
    console.error('Failed to update social links:', error)
    showError('Failed to update social links')
  } finally {
    saving.value = false
  }
}

// Handle member image file selection
const handleMemberImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    memberImageFile.value = target.files[0]
    const reader = new FileReader()
    reader.onload = (e) => {
      memberImagePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(target.files[0])
  }
}

// Get member image URL
const getMemberImageUrl = (image: string | null): string | undefined => {
  if (memberImagePreview.value) return memberImagePreview.value
  if (image) return getImageUrl(image)
  return undefined
}

// Team member functions
const openAddMemberModal = () => {
  newMember.value = { name: '', position: '', email: '', image: null }
  editingMemberIndex.value = null
  memberImageFile.value = null
  memberImagePreview.value = null
  showAddMemberModal.value = true
}

const editMember = (index: number) => {
  newMember.value = { ...teamMembers.value[index] }
  editingMemberIndex.value = index
  memberImageFile.value = null
  memberImagePreview.value = null
  showAddMemberModal.value = true
}

const saveMember = async () => {
  if (!newMember.value.name || !newMember.value.position) {
    showError('Please fill in required fields')
    return
  }

  try {
    saving.value = true

    if (memberImageFile.value) {
      const uploadResponse = await settingsStore.uploadTeamMemberImage(memberImageFile.value)
      newMember.value.image = uploadResponse.path
    }

    if (editingMemberIndex.value !== null && memberImageFile.value) {
      const oldMember = teamMembers.value[editingMemberIndex.value]
      if (oldMember.image && oldMember.image !== newMember.value.image) {
        await settingsStore.deleteTeamMemberImage(oldMember.image)
      }
    }

    if (editingMemberIndex.value !== null) {
      teamMembers.value[editingMemberIndex.value] = { ...newMember.value }
    } else {
      teamMembers.value.push({ ...newMember.value })
    }

    showAddMemberModal.value = false
    memberImageFile.value = null
    memberImagePreview.value = null
    await saveTeamMembers()
  } catch (error) {
    console.error('Failed to save team member:', error)
    showError('Failed to save team member')
  } finally {
    saving.value = false
  }
}

const removeMember = async (index: number) => {
  if (!confirm('Are you sure you want to remove this team member?')) return
  
  try {
    saving.value = true
    const member = teamMembers.value[index]
    
    if (member.image) {
      await settingsStore.deleteTeamMemberImage(member.image)
    }
    
    teamMembers.value.splice(index, 1)
    await saveTeamMembers()
  } catch (error) {
    console.error('Failed to remove team member:', error)
    showError('Failed to remove team member')
  } finally {
    saving.value = false
  }
}

const saveTeamMembers = async () => {
  try {
    saving.value = true
    const settings = [
      { key: 'team_members', value: JSON.stringify(teamMembers.value), group: 'team', type: 'json' }
    ]
    await settingsStore.updateSettings({ settings })
    await loadSettings()
    showSuccess('Team members updated successfully')
  } catch (error) {
    console.error('Failed to update team members:', error)
    showError('Failed to update team members')
  } finally {
    saving.value = false
  }
}


// Shipping Management Functions
const loadShippingData = async () => {
  try {
    await shippingStore.fetchShippingPrices()
  } catch (err) {
    console.error('Failed to load shipping data:', err)
    showError('Failed to Load', 'Could not load shipping prices')
  }
}

const openCreateShippingDialog = () => {
  createShippingForm.value = {
    region: '',
    price: 0,
    description: '',
    is_active: true
  }
  showCreateShippingDialog.value = true
}

const handleCreateShipping = async () => {
  if (!createShippingForm.value.region || createShippingForm.value.price < 0) {
    showError('Validation Error', 'Please fill in all required fields')
    return
  }

  isShippingSubmitting.value = true
  try {
    await shippingStore.createShippingPrice(createShippingForm.value)
    showSuccess('Success', 'Shipping price created successfully')
    showCreateShippingDialog.value = false
  } catch (err) {
    showError('Failed to Create', 'Could not create shipping price')
  } finally {
    isShippingSubmitting.value = false
  }
}

const openEditShippingDialog = (shippingPrice: ShippingPrice) => {
  selectedShippingPrice.value = shippingPrice
  editShippingForm.value = {
    region: shippingPrice.region,
    price: shippingPrice.price,
    description: shippingPrice.description || '',
    is_active: shippingPrice.is_active ?? true
  }
  showEditShippingDialog.value = true
}

const handleEditShipping = async () => {
  if (!selectedShippingPrice.value || !editShippingForm.value.price || editShippingForm.value.price < 0) {
    showError('Validation Error', 'Please fill in all required fields')
    return
  }

  isShippingSubmitting.value = true
  try {
    await shippingStore.updateShippingPrice(selectedShippingPrice.value.id, editShippingForm.value)
    showSuccess('Success', 'Shipping price updated successfully')
    showEditShippingDialog.value = false
  } catch (err) {
    showError('Failed to Update', 'Could not update shipping price')
  } finally {
    isShippingSubmitting.value = false
  }
}

const handleDeleteShipping = async (shippingPrice: ShippingPrice) => {
  if (!confirm(`Are you sure you want to delete shipping price for ${shippingStore.availableRegions[shippingPrice.region]}?`)) {
    return
  }

  try {
    await shippingStore.deleteShippingPrice(shippingPrice.id)
    showSuccess('Success', 'Shipping price deleted successfully')
  } catch (err) {
    showError('Failed to Delete', 'Could not delete shipping price')
  }
}


// Tax Management Functions
const loadTaxData = async () => {
  try {
    await taxStore.fetchTaxPrices()
  } catch (err) {
    console.error('Failed to load tax data:', err)
    showError('Failed to Load', 'Could not load tax prices')
  }
}

const openCreateTaxDialog = () => {
  createTaxForm.value = {
    name: '',
    rate: 0,
    description: '',
    is_active: true
  }
  showCreateTaxDialog.value = true
}

const handleCreateTax = async () => {
  if (!createTaxForm.value.name || createTaxForm.value.rate < 0 || createTaxForm.value.rate > 100) {
    showError('Validation Error', 'Please fill in all required fields. Rate must be between 0 and 100.')
    return
  }

  isTaxSubmitting.value = true
  try {
    await taxStore.createTaxPrice(createTaxForm.value)
    showSuccess('Success', 'Tax price created successfully')
    showCreateTaxDialog.value = false
  } catch (err) {
    showError('Failed to Create', 'Could not create tax price')
  } finally {
    isTaxSubmitting.value = false
  }
}

const openEditTaxDialog = (taxPrice: TaxPrice) => {
  selectedTaxPrice.value = taxPrice
  editTaxForm.value = {
    name: taxPrice.name,
    rate: taxPrice.rate,
    description: taxPrice.description || '',
    is_active: taxPrice.is_active ?? true
  }
  showEditTaxDialog.value = true
}

const handleEditTax = async () => {
  if (!selectedTaxPrice.value || !editTaxForm.value.rate || editTaxForm.value.rate < 0 || editTaxForm.value.rate > 100) {
    showError('Validation Error', 'Please fill in all required fields. Rate must be between 0 and 100.')
    return
  }

  isTaxSubmitting.value = true
  try {
    await taxStore.updateTaxPrice(selectedTaxPrice.value.id, editTaxForm.value)
    showSuccess('Success', 'Tax price updated successfully')
    showEditTaxDialog.value = false
  } catch (err) {
    showError('Failed to Update', 'Could not update tax price')
  } finally {
    isTaxSubmitting.value = false
  }
}

const handleDeleteTax = async (taxPrice: TaxPrice) => {
  if (!confirm(`Are you sure you want to delete tax "${taxPrice.name}"?`)) {
    return
  }

  try {
    await taxStore.deleteTaxPrice(taxPrice.id)
    showSuccess('Success', 'Tax price deleted successfully')
  } catch (err) {
    showError('Failed to Delete', 'Could not delete tax price')
  }
}

// Toggle shipping price active status
const toggleShippingActive = async (id: number, currentStatus: boolean) => {
  const newStatus = !currentStatus
  togglingShipping.value.add(id)

  try {
    // Optimistic update
    const shippingPrice = shippingStore.shippingPrices.find(p => p.id === id)
    if (shippingPrice) {
      shippingPrice.is_active = newStatus
    }

    await shippingStore.toggleShippingActive(id, newStatus)
    showSuccess('Status Updated', `Shipping price has been ${newStatus ? 'activated' : 'deactivated'} successfully.`)
  } catch (err: any) {
    // Revert optimistic update
    const shippingPrice = shippingStore.shippingPrices.find(p => p.id === id)
    if (shippingPrice) {
      shippingPrice.is_active = currentStatus
    }
    showError('Update Failed', `Failed to ${newStatus ? 'activate' : 'deactivate'} shipping price. Please try again.`)
  } finally {
    togglingShipping.value.delete(id)
  }
}

// Toggle tax price active status
const toggleTaxActive = async (id: number, currentStatus: boolean) => {
  const newStatus = !currentStatus
  togglingTax.value.add(id)

  try {
    // Optimistic update
    const taxPrice = taxStore.taxPrices.find(p => p.id === id)
    if (taxPrice) {
      taxPrice.is_active = newStatus
    }

    await taxStore.toggleTaxActive(id, newStatus)
    showSuccess('Status Updated', `Tax price has been ${newStatus ? 'activated' : 'deactivated'} successfully.`)
  } catch (err: any) {
    // Revert optimistic update
    const taxPrice = taxStore.taxPrices.find(p => p.id === id)
    if (taxPrice) {
      taxPrice.is_active = currentStatus
    }
    showError('Update Failed', `Failed to ${newStatus ? 'activate' : 'deactivate'} tax price. Please try again.`)
  } finally {
    togglingTax.value.delete(id)
  }
}

// Computed tax data for DataTable
const taxData = computed(() => {
  return taxStore.taxPrices.map((tax) => ({
    id: tax.id,
    name: tax.name,
    rate: `${tax.rate}%`,
    description: tax.description || '-',
    status: tax.is_active ? 'Active' : 'Inactive',
    rawData: tax
  }))
})

// Initialize data
loadSettings()

// Load shipping and tax data when component mounts or tab changes
const loadShippingAndTaxData = async () => {
  if (props.activeTab === 'shipping') {
    await Promise.all([
      loadShippingData(),
      loadTaxData()
    ])
  }
}

// Load data on mount if shipping tab is active
onMounted(() => {
  loadShippingAndTaxData()
})

// Watch for tab changes and load data when shipping tab is activated
watch(() => props.activeTab, (newTab) => {
  if (newTab === 'shipping') {
    loadShippingAndTaxData()
  }
})
</script>

<template>
  <div class="space-y-8">
    <!-- Site Identity Tab -->
    <div v-if="activeTab === 'site'" class="p-8">
      <!-- Logo -->
      <div class="mb-8">
        <label class="block text-sm font-medium text-gray-700 mb-3">Site Logo</label>
        <div class="flex items-start gap-6">
          <div class="w-32 h-32 bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden border-2 border-gray-200">
            <img v-if="logoUrl" :src="logoUrl" alt="Site Logo" class="max-w-full max-h-full object-contain" />
            <Icon v-else name="mdi:image-outline" class="text-5xl text-gray-300" />
          </div>
          <div class="flex-1" v-if="props.isEditingMode">
            <input
              type="file"
              @change="handleLogoChange"
              accept="image/*"
              class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-900 file:text-white hover:file:bg-zinc-800 file:cursor-pointer"
            />
            <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF, SVG up to 2MB. Logo will be uploaded when you click Save Changes.</p>
            <div class="mt-3 flex flex-col gap-2">
              <button
                v-if="logoPreview"
                @click="logoFile = null; logoPreview = null"
                class="text-sm text-red-600 hover:text-red-700 font-medium inline-flex items-center"
              >
                <Icon name="mdi:close-circle" class="mr-1" />
                Remove Selected Image
              </button>
              <label v-if="siteLogo && !logoPreview" class="inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  v-model="markLogoForDeletion"
                  class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
                />
                <span class="ml-2 text-sm text-gray-700">Delete current logo</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Site Name -->
      <div class="mb-8">
        <label class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
        <input
          v-if="props.isEditingMode"
          v-model="siteName"
          type="text"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
          placeholder="Enter site name"
        />
        <p v-else class="text-gray-900 py-2">{{ siteName || 'Not set' }}</p>
      </div>

      <!-- About -->
      <div class="mb-8">
        <label class="block text-sm font-medium text-gray-700 mb-2">About</label>
        <textarea
          v-if="props.isEditingMode"
          v-model="siteAbout"
          rows="4"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
          placeholder="Tell us about your site"
        ></textarea>
        <p v-else class="text-gray-900 py-2 whitespace-pre-wrap">{{ siteAbout || 'Not set' }}</p>
      </div>

      <button
        v-if="props.isEditingMode"
        @click="saveSiteIdentity"
        :disabled="saving"
        class="px-6 py-3 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 disabled:opacity-50 font-medium"
      >
        <Icon name="mdi:content-save" class="inline-block mr-2" />
        {{ saving ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <!-- Contact Information Tab -->
    <div v-if="activeTab === 'contact'" class="p-8">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Email -->
        <div>
          <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <Icon name="mdi:email-outline" class="text-lg mr-2" />
            Email
          </label>
          <input
            v-if="props.isEditingMode"
            v-model="contactEmail"
            type="email"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="contact@example.com"
          />
          <p v-else class="text-gray-900 py-2">{{ contactEmail || 'Not set' }}</p>
        </div>

        <!-- Phone -->
        <div>
          <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <Icon name="mdi:phone-outline" class="text-lg mr-2" />
            Phone
          </label>
          <input
            v-if="props.isEditingMode"
            v-model="contactPhone"
            type="tel"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="+63 123 456 7890"
          />
          <p v-else class="text-gray-900 py-2">{{ contactPhone || 'Not set' }}</p>
        </div>
      </div>

      <!-- Structured Store Address -->
      <div class="mb-8">
        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
          <Icon name="mdi:map-marker-outline" class="text-lg mr-2" />
          Store Address
        </label>
        <div v-if="props.isEditingMode">
          <AddressForm v-model="storeAddressModel" />
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Street</label>
            <p class="text-gray-900">{{ storeStreet || 'Not provided' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Barangay</label>
            <p class="text-gray-900">{{ storeBarangay || 'Not provided' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">City / Municipality</label>
            <p class="text-gray-900">{{ storeCity || 'Not provided' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Province</label>
            <p class="text-gray-900">{{ storeProvince || 'Not provided' }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Zip Code</label>
            <p class="text-gray-900">{{ storeZipcode || 'Not provided' }}</p>
          </div>
        </div>
      </div>

      <!-- Map Links -->
      <div class="grid grid-cols-1 gap-6 mb-8">
        <div>
          <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <Icon name="mdi:map" class="text-lg mr-2" />
            Google Maps Iframe URL
          </label>
          <input
            v-if="props.isEditingMode"
            v-model="iframeLink"
            type="url"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="https://www.google.com/maps/embed?..."
          />
          <p v-else class="text-gray-900 py-2 break-all">{{ iframeLink || 'Not set' }}</p>
        </div>
        <div>
          <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <Icon name="mdi:link" class="text-lg mr-2" />
            Google Maps Place URL
          </label>
          <input
            v-if="props.isEditingMode"
            v-model="gmapLink"
            type="url"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="https://www.google.com/maps/place/..."
          />
          <p v-else class="text-gray-900 py-2 break-all">{{ gmapLink || 'Not set' }}</p>
        </div>
      </div>

      <button
        v-if="props.isEditingMode"
        @click="saveContactInformation"
        :disabled="saving"
        class="px-6 py-3 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 disabled:opacity-50 font-medium"
      >
        <Icon name="mdi:content-save" class="inline-block mr-2" />
        {{ saving ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <!-- Social Links Tab -->
    <div v-if="activeTab === 'social'" class="p-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Facebook -->
        <div>
          <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <Icon name="mdi:facebook" class="text-lg mr-2" />
            Facebook
          </label>
          <input
            v-if="props.isEditingMode"
            v-model="contactFacebook"
            type="url"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="https://facebook.com/yourpage"
          />
          <p v-else class="text-gray-900 py-2 text-sm break-all">{{ contactFacebook || 'Not set' }}</p>
        </div>

        <!-- Instagram -->
        <div>
          <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <Icon name="mdi:instagram" class="text-lg mr-2" />
            Instagram
          </label>
          <input
            v-if="props.isEditingMode"
            v-model="contactInstagram"
            type="url"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="https://instagram.com/yourpage"
          />
          <p v-else class="text-gray-900 py-2 text-sm break-all">{{ contactInstagram || 'Not set' }}</p>
        </div>

        <!-- Twitter -->
        <div>
          <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
            <Icon name="mdi:twitter" class="text-lg mr-2" />
            Twitter
          </label>
          <input
            v-if="props.isEditingMode"
            v-model="contactTwitter"
            type="url"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="https://twitter.com/yourpage"
          />
          <p v-else class="text-gray-900 py-2 text-sm break-all">{{ contactTwitter || 'Not set' }}</p>
        </div>
      </div>

      <button
        v-if="props.isEditingMode"
        @click="saveSocialLinks"
        :disabled="saving"
        class="px-6 py-3 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 disabled:opacity-50 font-medium"
      >
        <Icon name="mdi:content-save" class="inline-block mr-2" />
        {{ saving ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <!-- Store Address Tab removed; merged into Contact -->

    <!-- Team Tab -->
    <div v-if="activeTab === 'team'" class="p-8">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Team Members</h2>
        <button
          @click="openAddMemberModal"
          class="px-4 py-2 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 font-medium"
        >
          <Icon name="mdi:plus" class="inline-block mr-2" />
          Add Member
        </button>
      </div>

      <!-- Team Members List -->
      <div v-if="teamMembers.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="(member, index) in teamMembers"
          :key="index"
          class="bg-gray-50 rounded-lg p-6 border border-gray-200 hover:border-gray-300 transition-colors"
        >
          <div class="flex items-start gap-4 mb-4">
            <!-- Member Image -->
            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden flex-shrink-0">
              <img 
                v-if="member.image" 
                :src="getMemberImageUrl(member.image)" 
                :alt="member.name"
                class="w-full h-full object-cover"
              />
              <Icon v-else name="mdi:account" class="text-3xl text-gray-400" />
            </div>
            
            <!-- Member Info -->
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-gray-900 text-lg truncate">{{ member.name }}</h3>
              <p class="text-sm text-gray-600 mt-1">{{ member.position }}</p>
              <p class="text-sm text-gray-500 mt-1 truncate">{{ member.email }}</p>
            </div>
          </div>
          
          <!-- Actions -->
          <div class="flex gap-2 justify-end pt-3 border-t border-gray-200">
            <button
              @click="editMember(index)"
              class="px-3 py-1.5 text-sm text-zinc-900 hover:bg-zinc-100 rounded-lg transition-colors flex items-center gap-1"
              title="Edit"
            >
              <Icon name="mdi:pencil" class="text-lg" />
              Edit
            </button>
            <button
              @click="removeMember(index)"
              class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors flex items-center gap-1"
              title="Delete"
            >
              <Icon name="mdi:delete" class="text-lg" />
              Delete
            </button>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
        <Icon name="mdi:account-group-outline" class="text-6xl text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500">No team members added yet</p>
        <button
          @click="openAddMemberModal"
          class="mt-4 px-4 py-2 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 font-medium"
        >
          <Icon name="mdi:plus" class="inline-block mr-2" />
          Add Your First Member
        </button>
      </div>
    </div>

    <!-- Shipping & Tax Tab -->
    <div v-if="activeTab === 'shipping'" class="p-8">
      <div class="space-y-12">
        <!-- Shipping Prices Section -->
        <div class="space-y-6">
          <!-- Header -->
          <div class="flex flex-col max-[489px]:flex-col min-[490px]:flex-row min-[490px]:justify-between min-[490px]:items-center gap-4">
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Shipping Price Management</h2>
              <p class="text-gray-600 mt-1">Manage shipping prices for different regions</p>
            </div>
            <AdminAddButton text="Add Shipping Price" @click="openCreateShippingDialog" />
          </div>

        <!-- Loading State -->
        <div v-if="shippingStore.loading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-zinc-800"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="shippingData.length === 0" class="text-center py-8">
          <div class="text-gray-500">No shipping prices found</div>
          <AdminAddButton text="Add First Shipping Price" @click="openCreateShippingDialog" class="mt-4" />
        </div>

        <!-- Shipping Prices DataTable -->
        <DataTable
          v-else
          :columns="shippingColumns"
          :rows="shippingData"
          :show-checkboxes="false"
        >
          <template #cell-region="{ row }">
            <div class="min-w-0">
              <div class="font-medium text-gray-900 truncate">{{ row.region }}</div>
              <div class="text-sm text-gray-500 truncate">{{ row.rawData.region }}</div>
            </div>
          </template>

          <template #cell-price="{ row }">
            <div class="font-medium text-gray-900">{{ row.price }}</div>
          </template>

          <template #cell-status="{ row }">
            <div class="flex items-center justify-start">
              <ActiveInactiveToggle
                :model-value="!!row.rawData.is_active"
                :disabled="togglingShipping.has(row.rawData.id)"
                @update:model-value="toggleShippingActive(row.rawData.id, !!row.rawData.is_active)"
              />
            </div>
          </template>

          <template #cell-actions="{ row }">
            <div class="flex gap-2 justify-start">
              <AdminActionButton
                icon="mdi:pencil"
                text="Edit"
                variant="primary"
                @click="openEditShippingDialog(row.rawData)"
              />
              <AdminActionButton
                icon="mdi:delete"
                text="Delete"
                variant="danger"
                @click="handleDeleteShipping(row.rawData)"
              />
            </div>
          </template>
        </DataTable>
        </div>

        <!-- Tax Prices Section -->
        <div class="space-y-6 border-t border-gray-200 pt-8">
          <!-- Header -->
          <div class="flex flex-col max-[489px]:flex-col min-[490px]:flex-row min-[490px]:justify-between min-[490px]:items-center gap-4">
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Tax Price Management</h2>
              <p class="text-gray-600 mt-1">Manage tax rates (e.g., VAT, GST, Sales Tax). Multiple active taxes will be summed.</p>
            </div>
            <AdminAddButton text="Add Tax Price" @click="openCreateTaxDialog" />
          </div>

          <!-- Loading State -->
          <div v-if="taxStore.loading" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-zinc-800"></div>
          </div>

          <!-- Empty State -->
          <div v-else-if="taxData.length === 0" class="text-center py-8">
            <div class="text-gray-500">No tax prices found</div>
            <AdminAddButton text="Add First Tax Price" @click="openCreateTaxDialog" class="mt-4" />
          </div>

          <!-- Tax Prices DataTable -->
          <DataTable
            v-else
            :columns="taxColumns"
            :rows="taxData"
            :show-checkboxes="false"
          >
            <template #cell-name="{ row }">
              <div class="font-medium text-gray-900 truncate min-w-0">{{ row.name }}</div>
            </template>

            <template #cell-rate="{ row }">
              <div class="font-medium text-gray-900">{{ row.rate }}</div>
            </template>

            <template #cell-status="{ row }">
              <div class="flex items-center justify-start">
                <ActiveInactiveToggle
                  :model-value="!!row.rawData.is_active"
                  :disabled="togglingTax.has(row.rawData.id)"
                  @update:model-value="toggleTaxActive(row.rawData.id, !!row.rawData.is_active)"
                />
              </div>
            </template>

            <template #cell-actions="{ row }">
              <div class="flex gap-2 justify-start">
                <AdminActionButton
                  icon="mdi:pencil"
                  text="Edit"
                  variant="primary"
                  @click="openEditTaxDialog(row.rawData)"
                />
                <AdminActionButton
                  icon="mdi:delete"
                  text="Delete"
                  variant="danger"
                  @click="handleDeleteTax(row.rawData)"
                />
              </div>
            </template>
          </DataTable>
        </div>
      </div>
    </div>

    <!-- Add/Edit Member Dialog -->
    <Dialog
      v-model="showAddMemberModal"
      :title="`${editingMemberIndex !== null ? 'Edit' : 'Add'} Team Member`"
      width="600px"
    >
      <div class="space-y-5">
        <!-- Profile Image Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">Profile Image</label>
          <div class="flex items-start gap-4">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center overflow-hidden border-2 border-gray-200">
              <img 
                v-if="getMemberImageUrl(newMember.image)" 
                :src="getMemberImageUrl(newMember.image)" 
                alt="Profile preview"
                class="w-full h-full object-cover"
              />
              <Icon v-else name="mdi:account" class="text-4xl text-gray-400" />
            </div>
            <div class="flex-1">
              <input
                type="file"
                @change="handleMemberImageChange"
                accept="image/*"
                class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-900 file:text-white hover:file:bg-zinc-800 file:cursor-pointer"
              />
              <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF, SVG up to 2MB. Recommended: Square image.</p>
            </div>
          </div>
        </div>

        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Name *
          </label>
          <input
            v-model="newMember.name"
            type="text"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="John Doe"
          />
        </div>

        <!-- Position -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Position *
          </label>
          <input
            v-model="newMember.position"
            type="text"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="CEO"
          />
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Email
          </label>
          <input
            v-model="newMember.email"
            type="email"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            placeholder="john@example.com"
          />
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
        <button
          @click="showAddMemberModal = false"
          class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors"
        >
          Cancel
        </button>
        <button
          @click="saveMember"
          :disabled="saving"
          class="px-5 py-2.5 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 font-medium disabled:opacity-50 transition-colors"
        >
          {{ saving ? 'Saving...' : (editingMemberIndex !== null ? 'Update Member' : 'Add Member') }}
        </button>
      </div>
    </Dialog>

    <!-- Create Shipping Price Dialog -->
    <Dialog v-model="showCreateShippingDialog" title="Add Shipping Price" width="500px">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Region *</label>
          <Select
            v-model="createShippingForm.region"
            :options="regionOptions"
            placeholder="Select a region"
            size="md"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Price (₱) *</label>
          <input
            v-model.number="createShippingForm.price"
            type="number"
            step="0.01"
            min="0"
            placeholder="0.00"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <textarea
            v-model="createShippingForm.description"
            rows="3"
            placeholder="Optional description"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button
            @click="showCreateShippingDialog = false"
            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="handleCreateShipping"
            :disabled="isShippingSubmitting"
            class="px-4 py-2 bg-zinc-800 text-white rounded-lg hover:bg-zinc-700 transition-colors disabled:opacity-50"
          >
            {{ isShippingSubmitting ? 'Creating...' : 'Create' }}
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Edit Shipping Price Dialog -->
    <Dialog v-model="showEditShippingDialog" title="Edit Shipping Price" width="500px">
      <div v-if="selectedShippingPrice" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Region *</label>
          <Select
            v-model="editShippingForm.region!"
            :options="regionOptions"
            placeholder="Select a region"
            size="md"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Price (₱) *</label>
          <input
            v-model.number="editShippingForm.price"
            type="number"
            step="0.01"
            min="0"
            placeholder="0.00"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <textarea
            v-model="editShippingForm.description"
            rows="3"
            placeholder="Optional description"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button
            @click="showEditShippingDialog = false"
            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="handleEditShipping"
            :disabled="isShippingSubmitting"
            class="px-4 py-2 bg-zinc-800 text-white rounded-lg hover:bg-zinc-700 transition-colors disabled:opacity-50"
          >
            {{ isShippingSubmitting ? 'Updating...' : 'Update' }}
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Create Tax Price Dialog -->
    <Dialog v-model="showCreateTaxDialog" title="Add Tax Price" width="500px">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
          <input
            v-model="createTaxForm.name"
            type="text"
            placeholder="e.g., VAT, GST, Sales Tax"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rate (%) *</label>
          <input
            v-model.number="createTaxForm.rate"
            type="number"
            step="0.01"
            min="0"
            max="100"
            placeholder="0.00"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            required
          />
          <p class="text-xs text-gray-500 mt-1">Enter percentage (e.g., 8 for 8%, 20 for 20%)</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <textarea
            v-model="createTaxForm.description"
            rows="3"
            placeholder="Optional description"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button
            @click="showCreateTaxDialog = false"
            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="handleCreateTax"
            :disabled="isTaxSubmitting"
            class="px-4 py-2 bg-zinc-800 text-white rounded-lg hover:bg-zinc-700 transition-colors disabled:opacity-50"
          >
            {{ isTaxSubmitting ? 'Creating...' : 'Create' }}
          </button>
        </div>
      </div>
    </Dialog>

    <!-- Edit Tax Price Dialog -->
    <Dialog v-model="showEditTaxDialog" title="Edit Tax Price" width="500px">
      <div v-if="selectedTaxPrice" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
          <input
            v-model="editTaxForm.name"
            type="text"
            placeholder="e.g., VAT, GST, Sales Tax"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rate (%) *</label>
          <input
            v-model.number="editTaxForm.rate"
            type="number"
            step="0.01"
            min="0"
            max="100"
            placeholder="0.00"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
            required
          />
          <p class="text-xs text-gray-500 mt-1">Enter percentage (e.g., 8 for 8%, 20 for 20%)</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <textarea
            v-model="editTaxForm.description"
            rows="3"
            placeholder="Optional description"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900"
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button
            @click="showEditTaxDialog = false"
            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="handleEditTax"
            :disabled="isTaxSubmitting"
            class="px-4 py-2 bg-zinc-800 text-white rounded-lg hover:bg-zinc-700 transition-colors disabled:opacity-50"
          >
            {{ isTaxSubmitting ? 'Updating...' : 'Update' }}
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>
