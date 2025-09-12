import { defineStore } from "pinia";
import { useCustomFetch } from "~/composables/useCustomFetch";
import type { Category, CategoryWithSubcategories } from "~/types";

export const useCategoryStore = defineStore("category", {
  state: () => ({
    categories: [] as CategoryWithSubcategories[],
    category: null as Category | null,
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchCategories() {
      if (this.loading) return;
      
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<{value: CategoryWithSubcategories[]}>("/api/categories");
        this.categories = response.value || response as any;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load categories";
      } finally {
        this.loading = false;
      }
    },

    async fetchCategory(slug: string) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>(`/api/categories/${slug}`);
        // Handle both direct response and wrapped response
        this.category = response.data || response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load category";
      } finally {
        this.loading = false;
      }
    },

    async fetchCategoryById(id: number) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>(`/api/categories/by-id/${id}`);
        // Handle both direct response and wrapped response
        this.category = response.data || response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load category";
      } finally {
        this.loading = false;
      }
    },

    async fetchCategoryBySlug(slug: string) {
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<Category>(`/api/categories/${slug}`);
        this.category = data;
        return data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load category";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createCategory(payload: Omit<Category, "id">) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>("/api/categories", {
          method: "POST",
          body: payload,
        });
        // Handle both direct response and wrapped response
        const data = response.data || response;
        this.categories.push(data);
        return data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to create category";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateCategory(id: number, payload: Partial<Category>) {
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<CategoryWithSubcategories>(`/api/categories/${id}`, {
          method: "PUT",
          body: payload,
        });
        const index = this.categories.findIndex((cat) => cat.id === id);
        if (index !== -1) {
          this.categories[index] = data;
        }
        return data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to update category";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteCategory(id: number) {
      this.loading = true;
      this.error = null;
      try {
        await useCustomFetch(`/api/categories/${id}`, { method: "DELETE" });
        this.categories = this.categories.filter((cat) => cat.id !== id);
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to delete category";
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },

  // persist: true, // Disabled - categories are static data, no need to cache
});
