<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

type Column = {
  label: string
  key: string
}

type Row = {
  id: number
  [key: string]: any
}

const props = withDefaults(defineProps<{
  columns: Column[]
  rows: Row[]
  rowsPerPage?: number
  selected?: number[]
  title?: string
  showCheckboxes?: boolean
}>(), {
  rowsPerPage: 10,
  title: 'Data',
  showCheckboxes: true
})

const emit = defineEmits<{
  (e: 'update:selected', value: number[]): void
  (e: 'row-click', row: Row): void
}>()

const searchQuery = ref('')
const sortKey = ref('')
const sortOrder = ref<'asc' | 'desc'>('asc')
const currentPage = ref(1)
const localSelected = ref<number[]>(props.selected ?? [])
const perPage = computed(() => props.rowsPerPage || 5)

watch(localSelected, (val) => {
  emit('update:selected', val)
})

const filteredRows = computed(() =>
  props.rows.filter(row =>
    Object.values(row).some(val =>
      String(val).toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  )
)

const sortedRows = computed(() => {
  if (!sortKey.value) return filteredRows.value
  return [...filteredRows.value].sort((a, b) => {
    const valA = a[sortKey.value]
    const valB = b[sortKey.value]
    if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1
    if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1
    return 0
  })
})

const totalPages = computed(() => Math.ceil(sortedRows.value.length / perPage.value))

const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return sortedRows.value.slice(start, start + perPage.value)
})

const areAllSelected = computed(() =>
  paginatedRows.value.every(row => localSelected.value.includes(row.id))
)

function toggleSort(key: string) {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'asc'
  }
}

function toggleSelectAll() {
  const currentIds = paginatedRows.value.map(r => r.id)
  const allSelected = currentIds.every(id => localSelected.value.includes(id))

  if (allSelected) {
    localSelected.value = localSelected.value.filter(id => !currentIds.includes(id))
  } else {
    localSelected.value = Array.from(new Set([...localSelected.value, ...currentIds]))
  }
}

function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++
}

function prevPage() {
  if (currentPage.value > 1) currentPage.value--
}

// Helper functions for responsive column classes
function getColumnClasses(key: string): string {
  const classes = []
  
  // Set minimum widths for different column types
  if (key === 'actions') {
    classes.push('w-32 min-w-[8rem]')
  } else if (key === 'image' || key === 'logo') {
    classes.push('w-20 min-w-[5rem]')
  } else if (key === 'name' || key === 'title') {
    classes.push('min-w-[12rem] max-w-[20rem]')
  } else if (key === 'meta_description') {
    classes.push('min-w-[15rem] max-w-[25rem]')
  } else if (key === 'meta_title') {
    classes.push('min-w-[10rem] max-w-[18rem]')
  } else if (key === 'slug') {
    classes.push('min-w-[8rem] max-w-[15rem]')
  } else if (key === 'price' || key === 'status') {
    classes.push('w-24 min-w-[6rem]')
  } else {
    classes.push('min-w-[8rem]')
  }
  
  return classes.join(' ')
}

function getCellClasses(key: string): string {
  const classes = []
  
  // Different text wrapping for different column types
  if (key === 'meta_description') {
    classes.push('whitespace-normal break-words')
  } else if (key === 'name' || key === 'title' || key === 'meta_title') {
    classes.push('whitespace-nowrap')
  } else if (key === 'actions') {
    classes.push('whitespace-nowrap')
  } else {
    classes.push('whitespace-nowrap')
  }
  
  return classes.join(' ')
}

const columnWidths = ref<string[]>([])

onMounted(() => {
  // Calculate percentage-based widths for better responsive design
  const totalColumns = props.columns.length
  const checkboxColumnWidth = 48 // Fixed width for checkbox column (w-12 = 48px)
  const remainingWidth = 100 - (checkboxColumnWidth / 16) // Subtract checkbox width in percentage
  
  // Check if this is a brands table (Logo, Name, Actions)
  const isBrandsTable = props.columns.length === 3 && 
    props.columns.some(col => col.key === 'logo') && 
    props.columns.some(col => col.key === 'name') && 
    props.columns.some(col => col.key === 'actions')
  
  // Distribute remaining width among columns
  const tempWidths: string[] = []
  
  if (isBrandsTable) {
    // Special distribution for brands table
    props.columns.forEach((col) => {
      if (col.key === 'logo') {
        tempWidths.push('20%') // Logo column - enough space for image
      } else if (col.key === 'name') {
        tempWidths.push('50%') // Name column - most space for brand names
      } else if (col.key === 'actions') {
        tempWidths.push('30%') // Actions column - space for delete button
      }
    })
  } else {
    // Default distribution for other tables
    props.columns.forEach((col, index) => {
      let widthPercentage: number
      
      // Adjust width based on column content type
      if (col.key === 'image' || col.key === 'logo') {
        widthPercentage = 15 // Image/Logo columns - narrow but not too narrow
      } else if (col.key === 'name' || col.key === 'slug') {
        widthPercentage = 25 // Name and slug - medium-wide
      } else if (col.key === 'price' || col.key === 'status' || col.key === 'category' || col.key === 'subcategory') {
        widthPercentage = 15 // Price, status, category columns - medium
      } else if (col.key === 'meta_title') {
        widthPercentage = 25 // Meta title - wider
      } else if (col.key === 'meta_description') {
        widthPercentage = 30 // Meta description - widest
      } else if (col.key === 'actions') {
        widthPercentage = 20 // Actions column - wider for buttons
      } else {
        widthPercentage = remainingWidth / totalColumns // Default distribution
      }
      
      tempWidths.push(`${widthPercentage}%`)
    })
  }

  columnWidths.value = tempWidths
})
</script>

<template>
  <div class="bg-white shadow grid grid-cols-1 overflow-hidden sm:rounded-md">
    <div class="px-4 py-5 sm:p-6">
      <!-- Search and Filters -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
          <!-- Search -->
          <div class="relative w-full sm:w-80">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg leading-6 font-medium text-gray-900 truncate">
          {{ title || 'Data' }} ({{ filteredRows.length }})
        </h3>
      </div>

      <!-- Table Container -->
      <div class="overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th v-if="showCheckboxes" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                  <input type="checkbox" :checked="areAllSelected" @change="toggleSelectAll" />
                </th>
                <th
                  v-for="(col, i) in columns"
                  :key="col.key"
                  class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  :class="getColumnClasses(col.key)"
                >
                  <div class="flex items-center gap-1 cursor-pointer" @click="toggleSort(col.key)">
                    <span class="truncate">{{ col.label }}</span>
                    <svg
                      v-if="sortKey === col.key"
                      class="h-4 w-4 text-gray-400 flex-shrink-0"
                      :class="{ 'transform rotate-180': sortOrder === 'desc' }"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="row in paginatedRows"
                :key="row.id"
                class="hover:bg-gray-50"
                @click="emit('row-click', row)"
              >
                <td v-if="showCheckboxes" class="px-3 py-4 w-12">
                  <input type="checkbox" v-model="localSelected" :value="row.id" />
                </td>
                <td
                  v-for="(col, i) in columns"
                  :key="col.key"
                  class="px-3 py-4 text-sm text-gray-900"
                  :class="getCellClasses(col.key)"
                >
                  <slot :name="`cell-${col.key}`" :row="row">
                    <div class="truncate">{{ row[col.key] }}</div>
                  </slot>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="paginatedRows.length === 0" class="text-center py-12">
        <div class="mx-auto h-24 w-24 text-gray-400">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No data found</h3>
        <p class="mt-1 text-sm text-gray-500">No items match your current search criteria.</p>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-6 text-sm space-y-4 sm:space-y-0">
        <div class="text-gray-700 order-2 sm:order-1">
          Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, filteredRows.length) }} of {{ filteredRows.length }} results
        </div>
        <div class="flex gap-2 order-1 sm:order-2">
          <button 
            @click="prevPage" 
            :disabled="currentPage === 1" 
            class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
          >
            Previous
          </button>
          <button 
            @click="nextPage" 
            :disabled="currentPage === totalPages" 
            class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
