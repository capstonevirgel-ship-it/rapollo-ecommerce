<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, ArcElement, BarElement } from 'chart.js'
import { Line, Doughnut, Bar } from 'vue-chartjs'

// Register Chart.js components
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, ArcElement, BarElement)

definePageMeta({
  layout: 'admin'
})

// Set page title
useHead({
  title: 'Admin Dashboard - Rapollo E-commerce',
  meta: [
    { name: 'description', content: 'Manage your Rapollo E-commerce store from the admin dashboard.' }
  ]
})

const dashboardStore = useDashboardStore()


// Fetch data on mount
onMounted(async () => {
  try {
    // Check if user is authenticated and is admin
    const authStore = useAuthStore()
    if (!authStore.isAuthenticated) {
      console.error('User not authenticated')
      return
    }
    
    if (authStore.user?.role !== 'admin') {
      console.error('User is not admin')
      return
    }
    
    await dashboardStore.fetchAllDashboardData()
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  }
})

// Chart data computed properties
const revenueChartData = computed(() => {
  if (!dashboardStore.revenueChart) {
    return {
      labels: [],
      datasets: []
    }
  }
  
  return {
    labels: dashboardStore.revenueChart.labels,
    datasets: [
      {
        label: 'Revenue ($)',
        data: dashboardStore.revenueChart.revenue,
        borderColor: '#71717a',
        backgroundColor: 'rgba(113, 113, 122, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4
      },
      {
        label: 'Orders',
        data: dashboardStore.revenueChart.orders,
        borderColor: '#52525b',
        backgroundColor: 'rgba(82, 82, 91, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4
      }
    ]
  }
})

const ordersChartData = computed(() => {
  if (!dashboardStore.orderStatus) {
    return {
      labels: [],
      datasets: []
    }
  }
  
  const statusData = dashboardStore.orderStatus
  return {
    labels: ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'],
    datasets: [
      {
        data: [
          statusData.pending,
          statusData.processing,
          statusData.shipped,
          statusData.completed,
          statusData.cancelled
        ],
        backgroundColor: [
          '#fbbf24',
          '#3b82f6', 
          '#10b981',
          '#71717a',
          '#ef4444'
        ],
        borderWidth: 0
      }
    ]
  }
})

const categoryChartData = computed(() => {
  if (!dashboardStore.categoryData) {
    return {
      labels: [],
      datasets: []
    }
  }
  
  return {
    labels: dashboardStore.categoryData.labels,
    datasets: [
      {
        data: dashboardStore.categoryData.data,
        backgroundColor: ['#71717a', '#52525b', '#3f3f46', '#27272a', '#18181b'],
        borderWidth: 0
      }
    ]
  }
})

const topProductsChartData = computed(() => {
  if (!dashboardStore.topProducts.length) {
    return {
      labels: [],
      datasets: []
    }
  }
  
  return {
    labels: dashboardStore.topProducts.map(product => product.name),
    datasets: [
      {
        label: 'Sales',
        data: dashboardStore.topProducts.map(product => product.total_sales),
        backgroundColor: 'rgba(113, 113, 122, 0.8)',
        borderColor: '#71717a',
        borderWidth: 2,
        borderRadius: 4
      }
    ]
  }
})

// Chart options
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
      labels: {
        usePointStyle: true,
        padding: 20
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.05)'
      }
    },
    x: {
      grid: {
        color: 'rgba(0, 0, 0, 0.05)'
      }
    }
  }
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        usePointStyle: true,
        padding: 20
      }
    }
  }
}

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.05)'
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  }
}

// Utility functions
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount)
}

const getStatusColor = (status: string) => {
  const colors = {
    completed: 'bg-green-100 text-green-800',
    processing: 'bg-blue-100 text-blue-800',
    pending: 'bg-yellow-100 text-yellow-800',
    shipped: 'bg-purple-100 text-purple-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

const getGrowthColor = (growth: number) => {
  return growth >= 0 ? 'text-green-600' : 'text-red-600'
}

const getGrowthIcon = (growth: number) => {
  return growth >= 0 ? '↗' : '↘'
}

const refreshData = async () => {
  try {
    await dashboardStore.fetchAllDashboardData()
  } catch (error) {
    console.error('Failed to refresh dashboard data:', error)
  }
}
</script>

<template>
  <div class="p-8 space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-1">Welcome back! Here's what's happening with your business.</p>
      </div>
      <div class="flex items-center space-x-3">
        <div class="text-sm text-gray-500">
          Last updated: {{ new Date().toLocaleString() }}
        </div>
        <button 
          @click="refreshData"
          :disabled="dashboardStore.loading"
          class="inline-flex items-center px-4 py-2 bg-zinc-900 text-white rounded-lg hover:bg-zinc-800 transition-colors disabled:opacity-50"
        >
          <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': dashboardStore.loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ dashboardStore.loading ? 'Loading...' : 'Refresh' }}
        </button>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="dashboardStore.error" class="bg-red-50 border border-red-200 rounded-lg p-6">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error loading dashboard data</h3>
          <div class="mt-2 text-sm text-red-700">
            {{ dashboardStore.error }}
          </div>
          <div class="mt-2">
            <p class="text-sm text-red-600">Please make sure you are logged in as an admin user.</p>
          </div>
        </div>
      </div>
    </div>


    <!-- Loading State -->
    <div v-if="dashboardStore.loading && !dashboardStore.isDataLoaded">
      <LoadingSpinner 
        size="lg" 
        color="primary" 
        text="Loading dashboard data..." 
        :show-text="true"
      />
    </div>

    <!-- Dashboard Content -->
    <div v-else-if="dashboardStore.isDataLoaded">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Revenue</p>
              <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(dashboardStore.stats?.total_revenue || 0) }}</p>
              <div class="flex items-center mt-2">
                <span :class="['text-sm font-medium', getGrowthColor(dashboardStore.stats?.monthly_growth || 0)]">
                  {{ getGrowthIcon(dashboardStore.stats?.monthly_growth || 0) }} {{ Math.abs(dashboardStore.stats?.monthly_growth || 0) }}%
                </span>
                <span class="text-sm text-gray-500 ml-1">vs last month</span>
              </div>
            </div>
            <div class="p-3 bg-zinc-100 rounded-lg">
              <svg class="w-6 h-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Orders</p>
              <p class="text-3xl font-bold text-gray-900">{{ (dashboardStore.stats?.total_orders || 0).toLocaleString() }}</p>
              <div class="flex items-center mt-2">
                <span :class="['text-sm font-medium', getGrowthColor(dashboardStore.stats?.order_growth || 0)]">
                  {{ getGrowthIcon(dashboardStore.stats?.order_growth || 0) }} {{ Math.abs(dashboardStore.stats?.order_growth || 0) }}%
                </span>
                <span class="text-sm text-gray-500 ml-1">vs last month</span>
              </div>
            </div>
            <div class="p-3 bg-zinc-100 rounded-lg">
              <svg class="w-6 h-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Products</p>
              <p class="text-3xl font-bold text-gray-900">{{ dashboardStore.stats?.total_products || 0 }}</p>
              <div class="flex items-center mt-2">
                <span :class="['text-sm font-medium', getGrowthColor(dashboardStore.stats?.product_growth || 0)]">
                  {{ getGrowthIcon(dashboardStore.stats?.product_growth || 0) }} {{ Math.abs(dashboardStore.stats?.product_growth || 0) }}%
                </span>
                <span class="text-sm text-gray-500 ml-1">vs last month</span>
              </div>
            </div>
            <div class="p-3 bg-zinc-100 rounded-lg">
              <svg class="w-6 h-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Customers</p>
              <p class="text-3xl font-bold text-gray-900">{{ dashboardStore.stats?.total_customers || 0 }}</p>
              <div class="flex items-center mt-2">
                <span :class="['text-sm font-medium', getGrowthColor(dashboardStore.stats?.customer_growth || 0)]">
                  {{ getGrowthIcon(dashboardStore.stats?.customer_growth || 0) }} {{ Math.abs(dashboardStore.stats?.customer_growth || 0) }}%
                </span>
                <span class="text-sm text-gray-500 ml-1">vs last month</span>
              </div>
            </div>
            <div class="p-3 bg-zinc-100 rounded-lg">
              <svg class="w-6 h-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="mt-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Revenue Chart -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Revenue & Orders Trend</h3>
              <div class="flex space-x-2">
                <button class="px-3 py-1 text-sm bg-zinc-100 text-zinc-700 rounded-lg hover:bg-zinc-200">12M</button>
                <button class="px-3 py-1 text-sm text-gray-500 rounded-lg hover:bg-gray-100">6M</button>
                <button class="px-3 py-1 text-sm text-gray-500 rounded-lg hover:bg-gray-100">3M</button>
              </div>
            </div>
            <div class="h-80">
              <Line :data="revenueChartData" :options="chartOptions" />
            </div>
          </div>

          <!-- Orders Status Chart -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Orders by Status</h3>
              <div class="text-sm text-gray-500">
                Total: {{ dashboardStore.stats?.total_orders || 0 }} orders
              </div>
            </div>
            <div class="h-80">
              <Doughnut :data="ordersChartData" :options="doughnutOptions" />
            </div>
          </div>
        </div>
      </div>

      <!-- Second Charts Row -->
      <div class="mt-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Sales by Category -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Sales by Category</h3>
              <div class="text-sm text-gray-500">
                Total: {{ dashboardStore.categoryData?.data.reduce((sum, val) => sum + val, 0) || 0 }} sales
              </div>
            </div>
            <div class="h-80">
              <Doughnut :data="categoryChartData" :options="doughnutOptions" />
            </div>
          </div>

          <!-- Top Products -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Top Performing Products</h3>
                <NuxtLink to="/admin/products" class="text-sm text-zinc-600 hover:text-zinc-800">View all</NuxtLink>
            </div>
            <div class="h-80">
              <Bar :data="topProductsChartData" :options="barOptions" />
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Row -->
      <div class="mt-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Recent Orders -->
          <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Recent Orders</h3>
                <NuxtLink to="/admin/orders" class="text-sm text-zinc-600 hover:text-zinc-800">View all</NuxtLink>
            </div>
    <div class="space-y-4">
              <div v-for="order in dashboardStore.recentOrders" :key="order.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-4">
                  <div class="p-2 bg-white rounded-lg shadow-sm">
                    <svg class="w-5 h-5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                  </div>
                    <div>
                    <p class="font-medium text-gray-900">#{{ order.id }}</p>
                    <p class="text-sm text-gray-500">{{ order.customer }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-gray-900">{{ formatCurrency(order.amount) }}</p>
                  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusColor(order.status)]">
                    {{ order.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Quick Actions</h3>
            <div class="space-y-3">
                <NuxtLink to="/admin/add-product" class="flex items-center p-3 bg-zinc-50 rounded-lg hover:bg-zinc-100 transition-colors">
                <div class="p-2 bg-zinc-900 text-white rounded-lg mr-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
              </div>
                <div>
                <p class="font-medium text-gray-900">Add Product</p>
                <p class="text-sm text-gray-500">Create new product</p>
              </div>
            </NuxtLink>

              <NuxtLink to="/admin/events" class="flex items-center p-3 bg-zinc-50 rounded-lg hover:bg-zinc-100 transition-colors">
                <div class="p-2 bg-zinc-900 text-white rounded-lg mr-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
                <div>
                <p class="font-medium text-gray-900">Manage Events</p>
                <p class="text-sm text-gray-500">Create & manage events</p>
              </div>
            </NuxtLink>

              <NuxtLink to="/admin/tickets" class="flex items-center p-3 bg-zinc-50 rounded-lg hover:bg-zinc-100 transition-colors">
                <div class="p-2 bg-zinc-900 text-white rounded-lg mr-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>
              </div>
                <div>
                <p class="font-medium text-gray-900">View Tickets</p>
                <p class="text-sm text-gray-500">Manage bookings</p>
              </div>
            </NuxtLink>

              <NuxtLink to="/admin/categories" class="flex items-center p-3 bg-zinc-50 rounded-lg hover:bg-zinc-100 transition-colors">
                <div class="p-2 bg-zinc-900 text-white rounded-lg mr-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </div>
                <div>
                <p class="font-medium text-gray-900">Categories</p>
                <p class="text-sm text-gray-500">Manage categories</p>
              </div>
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!dashboardStore.loading && !dashboardStore.isDataLoaded && !dashboardStore.error" class="text-center py-12">
      <div class="mx-auto h-24 w-24 text-gray-400">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No dashboard data available</h3>
      <p class="mt-1 text-sm text-gray-500">Unable to load dashboard statistics.</p>
      <div class="mt-6">
        <button @click="refreshData" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-zinc-900 hover:bg-zinc-800">
          Try Again
        </button>
      </div>
    </div>
  </div>
</template>