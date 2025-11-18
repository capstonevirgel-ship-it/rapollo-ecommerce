import { defineStore } from 'pinia'
import { useCustomFetch } from '~/composables/useCustomFetch'

interface DashboardStats {
  total_revenue: number
  total_orders: number
  total_products: number
  total_customers: number
  total_events: number
  total_tickets: number
  monthly_growth: number
  order_growth: number
  product_growth: number
  customer_growth: number
  monthly_revenue: number
}

interface RevenueChartData {
  labels: string[]
  revenue: number[]
  orders: number[]
}

interface TicketSalesChartData {
  labels: string[]
  sales: number[]
  revenue: number[]
}

interface OrderStatusData {
  pending: number
  processing: number
  shipped: number
  completed: number
  cancelled: number
}

interface CategoryData {
  labels: string[]
  data: number[]
}

interface TopProduct {
  name: string
  slug: string
  total_sales: number
  total_revenue: number
}

interface RecentOrder {
  id: number
  customer: string
  email: string
  amount: number
  status: string
  date: string
  items: number
}

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: null as DashboardStats | null,
    revenueChart: null as RevenueChartData | null,
    ticketSalesChart: null as TicketSalesChartData | null,
    orderStatus: null as OrderStatusData | null,
    categoryData: null as CategoryData | null,
    topProducts: [] as TopProduct[],
    recentOrders: [] as RecentOrder[],
    loading: false,
    error: null as string | null
  }),

  getters: {
    isDataLoaded: (state) => {
      return state.stats && 
             state.revenueChart && 
             state.ticketSalesChart &&
             state.orderStatus && 
             state.categoryData &&
             Array.isArray(state.topProducts) &&
             Array.isArray(state.recentOrders)
    }
  },

  actions: {
    async fetchStatistics() {
      this.loading = true
      this.error = null
      
      try {
        const stats = await useCustomFetch<DashboardStats>('/api/dashboard/statistics')
        this.stats = stats
        return stats
      } catch (error: any) {
        this.error = error.message || 'Failed to fetch statistics'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchRevenueChart() {
      this.loading = true
      this.error = null
      
      try {
        const revenueChart = await useCustomFetch<RevenueChartData>('/api/dashboard/revenue-chart')
        this.revenueChart = revenueChart
        return revenueChart
      } catch (error: any) {
        this.error = error.message || 'Failed to fetch revenue chart data'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchTicketSalesChart() {
      this.loading = true
      this.error = null
      
      try {
        const ticketSalesChart = await useCustomFetch<TicketSalesChartData>('/api/dashboard/ticket-sales-chart')
        this.ticketSalesChart = ticketSalesChart
        return ticketSalesChart
      } catch (error: any) {
        this.error = error.message || 'Failed to fetch ticket sales chart data'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchOrderStatusChart() {
      this.loading = true
      this.error = null
      
      try {
        const orderStatus = await useCustomFetch<OrderStatusData>('/api/dashboard/order-status-chart')
        this.orderStatus = orderStatus
        return orderStatus
      } catch (error: any) {
        this.error = error.message || 'Failed to fetch order status data'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchCategoryChart() {
      this.loading = true
      this.error = null
      
      try {
        const categoryData = await useCustomFetch<CategoryData>('/api/dashboard/category-chart')
        this.categoryData = categoryData
        return categoryData
      } catch (error: any) {
        this.error = error.message || 'Failed to fetch category data'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchTopProducts() {
      this.loading = true
      this.error = null
      
      try {
        const topProducts = await useCustomFetch<TopProduct[]>('/api/dashboard/top-products')
        this.topProducts = topProducts
        return topProducts
      } catch (error: any) {
        this.error = error.message || 'Failed to fetch top products'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchRecentOrders() {
      this.loading = true
      this.error = null
      
      try {
        const recentOrders = await useCustomFetch<RecentOrder[]>('/api/dashboard/recent-orders')
        this.recentOrders = recentOrders
        return recentOrders
      } catch (error: any) {
        this.error = error.message || 'Failed to fetch recent orders'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchAllDashboardData() {
      this.loading = true
      this.error = null
      
      try {
        // Fetch all data in parallel
        await Promise.all([
          this.fetchStatistics(),
          this.fetchRevenueChart(),
          this.fetchTicketSalesChart(),
          this.fetchOrderStatusChart(),
          this.fetchCategoryChart(),
          this.fetchTopProducts(),
          this.fetchRecentOrders()
        ])
      } catch (error: any) {
        this.error = error.message || 'Failed to fetch dashboard data'
        throw error
      } finally {
        this.loading = false
      }
    },

    clearError() {
      this.error = null
    }
  }
})
