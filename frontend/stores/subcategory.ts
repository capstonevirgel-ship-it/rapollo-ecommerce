import { defineStore } from "pinia";
import { useCustomFetch } from "~/composables/useCustomFetch";
import type { Subcategory } from "~/types";

export const useSubcategoryStore = defineStore("subcategory", {
  state: () => ({
    subcategories: [] as Subcategory[],
    subcategory: null as Subcategory | null,
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchSubcategories() {
      if (this.loading) return;
      
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<Subcategory[]>("/api/subcategories");
        this.subcategories = data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load subcategories";
      } finally {
        this.loading = false;
      }
    },

    async fetchSubcategory(slug: string) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>(`/api/subcategories/${slug}`);
        // Handle both direct response and wrapped response
        this.subcategory = response.data || response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load subcategory";
      } finally {
        this.loading = false;
      }
    },

    async fetchSubcategoryById(id: number) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>(`/api/subcategories/by-id/${id}`);
        // Handle both direct response and wrapped response
        this.subcategory = response.data || response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load subcategory";
      } finally {
        this.loading = false;
      }
    },

    async fetchSubcategoryBySlug(slug: string) {
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<Subcategory>(`/api/subcategories/${slug}`);
        this.subcategory = data;
        return data;
      } catch (error: any) {
        // Handle 404 errors gracefully - subcategory might not exist
        if (error.status === 404 || error.statusCode === 404) {
          console.warn(`Subcategory with slug '${slug}' not found`);
          this.subcategory = null;
          return null;
        }
        this.error = error.data?.message || error.message || "Failed to load subcategory";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createSubcategory(payload: Omit<Subcategory, "id">) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>("/api/subcategories", {
          method: "POST",
          body: payload,
        });
        // Handle both direct response and wrapped response
        const data = response.data || response;
        this.subcategories.push(data);
        return data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to create subcategory";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateSubcategory(id: number, payload: Partial<Subcategory>) {
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<Subcategory>(`/api/subcategories/${id}`, {
          method: "PUT",
          body: payload,
        });
        const index = this.subcategories.findIndex((sub) => sub.id === id);
        if (index !== -1) {
          this.subcategories[index] = data;
        }
        return data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to update subcategory";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteSubcategory(id: number) {
      this.loading = true;
      this.error = null;
      try {
        await useCustomFetch(`/api/subcategories/${id}`, { method: "DELETE" });
        this.subcategories = this.subcategories.filter((sub) => sub.id !== id);
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to delete subcategory";
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },

  persist: {
    paths: ['subcategories', 'subcategory', 'error'] // Exclude loading state from persistence
  }
});
