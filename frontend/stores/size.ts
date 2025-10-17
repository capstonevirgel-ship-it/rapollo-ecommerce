import { defineStore } from "pinia";
import { useCustomFetch } from "~/composables/useCustomFetch";
import type { Size } from "~/types";

export const useSizeStore = defineStore("size", {
  state: () => ({
    sizes: [] as Size[],
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchSizes() {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<Size[]>("/api/sizes", { method: "GET" });
        this.sizes = response || [];
        return this.sizes;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to fetch sizes";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createSize(payload: { name: string; description?: string; sort_order?: number }) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<Size>("/api/sizes", {
          method: "POST",
          body: payload,
        });
        this.sizes.push(response);
        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to create size";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateSize(id: number, payload: { name: string; description?: string; sort_order?: number }) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<Size>(`/api/sizes/${id}`, {
          method: "PUT",
          body: payload,
        });
        const index = this.sizes.findIndex(size => size.id === id);
        if (index !== -1) {
          this.sizes[index] = response;
        }
        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to update size";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteSize(id: number) {
      this.loading = true;
      this.error = null;
      try {
        await useCustomFetch(`/api/sizes/${id}`, { method: "DELETE" });
        this.sizes = this.sizes.filter(size => size.id !== id);
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to delete size";
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});
