import { defineStore } from "pinia";
import { useCustomFetch } from "~/composables/useCustomFetch";
import type { Category } from "~/types";

export const useCategoryStore = defineStore("category", {
  state: () => ({
    categories: [] as Category[],
    category: null as Category | null,
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchCategories() {
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<Category[]>("/api/categories");
        this.categories = data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load categories";
      } finally {
        this.loading = false;
      }
    },

    async fetchCategory(id: number) {
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<Category>(`/api/categories/${id}`);
        this.category = data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load category";
      } finally {
        this.loading = false;
      }
    },

    async createCategory(payload: Omit<Category, "id">) {
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<Category>("/api/categories", {
          method: "POST",
          body: payload,
        });
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
        const data = await useCustomFetch<Category>(`/api/categories/${id}`, {
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

  persist: true,
});
