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

const props = defineProps<{
  columns: Column[]
  rows: Row[]
  rowsPerPage?: number
  selected?: number[]
}>()

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

const columnWidths = ref<number[]>([])

onMounted(() => {
  const tempWidths: number[] = []

  const ctx = document.createElement('canvas').getContext('2d')
  if (ctx) {
    ctx.font = getComputedStyle(document.body).font

    props.columns.forEach((col) => {
      let maxWidth = ctx.measureText(col.label).width

      props.rows.forEach((row) => {
        const text = String(row[col.key] ?? '')
        const width = ctx.measureText(text).width
        if (width > maxWidth) maxWidth = width
      })

      tempWidths.push(Math.max(Math.ceil(maxWidth), 175))
    })
  }

  columnWidths.value = tempWidths
})
</script>

<template>
  <div class="w-full p-4 bg-white rounded">
    <!-- Search -->
    <div class="flex items-center gap-2 mb-4 w-80">
      <Icon name="mdi:magnify" class="text-gray-500" />
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search..."
        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:ring-blue-200"
      />
    </div>

    <!-- Table -->
    <div class="overflow-auto">
      <table class="table-auto border-collapse">
        <thead class="bg-gray-100">
          <tr class="text-left text-sm">
            <th class="p-2 w-4">
              <input type="checkbox" :checked="areAllSelected" @change="toggleSelectAll" />
            </th>
            <th
              v-for="(col, i) in columns"
              :key="col.key"
              :style="{ width: columnWidths[i] + 'px' }"
              class="p-2 select-none"
            >
              <div class="flex items-center gap-1 cursor-pointer" @click="toggleSort(col.key)">
                {{ col.label }}
                <Icon
                  v-if="sortKey === col.key"
                  :name="sortOrder === 'asc' ? 'mdi:arrow-up' : 'mdi:arrow-down'"
                  class="text-gray-500 text-base"
                />
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in paginatedRows"
            :key="row.id"
            class="hover:bg-gray-50 text-sm"
            @click="emit('row-click', row)"
          >
            <td class="p-2">
              <input type="checkbox" v-model="localSelected" :value="row.id" />
            </td>
            <td
              v-for="(col, i) in columns"
              :key="col.key"
              :style="{ width: columnWidths[i] + 'px' }"
              class="p-2"
            >
              <slot :name="`cell-${col.key}`" :row="row">
                {{ row[col.key] }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center mt-4 text-sm">
      <div>Page {{ currentPage }} of {{ totalPages }}</div>
      <div class="flex gap-2">
        <button @click="prevPage" :disabled="currentPage === 1" class="px-2 py-1 border rounded disabled:opacity-50">
          Prev
        </button>
        <button @click="nextPage" :disabled="currentPage === totalPages" class="px-2 py-1 border rounded disabled:opacity-50">
          Next
        </button>
      </div>
    </div>
  </div>
</template>
