import { defineStore } from "pinia";
import { useCustomFetch } from "~/composables/useCustomFetch";

export interface AdminUser {
  id: number;
  user_name: string;
  email: string;
  role: string;
  is_suspended: boolean;
  suspended_at: string | null;
  suspension_reason: string | null;
  created_at: string;
  updated_at: string;
  profile?: {
    id: number;
    user_id: number;
    full_name: string | null;
    phone: string | null;
    complete_address: string | null;
    avatar_url: string | null;
  };
  product_purchases_count?: number;
  ticket_purchases_count?: number;
  cancellation_count?: number;
}

export interface UserTransactions {
  product_purchases: {
    data: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  ticket_purchases: {
    data: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export const useAdminUsersStore = defineStore("admin-users", {
  state: () => ({
    users: [] as AdminUser[],
    selectedUser: null as AdminUser | null,
    userTransactions: null as UserTransactions | null,
    loading: false,
    error: null as string | null,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
    },
  }),

  getters: {
    suspendedUsers: (state) => state.users.filter((user) => user.is_suspended),
    activeUsers: (state) => state.users.filter((user) => !user.is_suspended),
  },

  actions: {
    async fetchUsers(params: Record<string, any> = {}) {
      this.loading = true;
      this.error = null;
      try {
        const queryParams = new URLSearchParams();
        if (params.search) queryParams.append("search", params.search);
        if (params.is_suspended !== undefined)
          queryParams.append("is_suspended", params.is_suspended);
        if (params.page) queryParams.append("page", params.page.toString());
        if (params.per_page) queryParams.append("per_page", params.per_page.toString());

        const query = queryParams.toString();
        const url = query ? `/api/admin/users?${query}` : "/api/admin/users";

        const response = await useCustomFetch<any>(url, { method: "GET" });
        this.users = response.data || [];
        
        // Update pagination
        if (response.current_page) {
          this.pagination = {
            current_page: response.current_page,
            last_page: response.last_page,
            per_page: response.per_page,
            total: response.total,
          };
        }

        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to fetch users";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchUser(id: number) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<{ data: AdminUser }>(
          `/api/admin/users/${id}`,
          { method: "GET" }
        );
        this.selectedUser = response.data;
        return response.data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to fetch user";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async suspendUser(id: number, reason?: string) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<{ data: AdminUser; message: string }>(
          `/api/admin/users/${id}/suspend`,
          {
            method: "PUT",
            body: { reason: reason || null },
          }
        );
        
        // Update user in list
        const index = this.users.findIndex((u) => u.id === id);
        if (index !== -1) {
          this.users[index] = response.data;
        }
        
        // Update selected user if it's the same
        if (this.selectedUser?.id === id) {
          this.selectedUser = response.data;
        }
        
        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to suspend user";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async unsuspendUser(id: number) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<{ data: AdminUser; message: string }>(
          `/api/admin/users/${id}/unsuspend`,
          {
            method: "PUT",
          }
        );
        
        // Update user in list
        const index = this.users.findIndex((u) => u.id === id);
        if (index !== -1) {
          this.users[index] = response.data;
        }
        
        // Update selected user if it's the same
        if (this.selectedUser?.id === id) {
          this.selectedUser = response.data;
        }
        
        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to unsuspend user";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchUserTransactions(id: number, page: number = 1) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<UserTransactions>(
          `/api/admin/users/${id}/transactions?product_page=${page}&ticket_page=${page}`,
          { method: "GET" }
        );
        this.userTransactions = response;
        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to fetch transactions";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    clearSelectedUser() {
      this.selectedUser = null;
      this.userTransactions = null;
    },
  },
});
