import { defineStore } from "pinia";
import { useCustomFetch } from "~/composables/useCustomFetch";
import type { Brand, BrandPayload } from "~/types";

export const useBrandStore = defineStore("brand", {
  state: () => ({
    brands: [] as Brand[],
    brand: null as Brand | null,
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchBrands() {
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<Brand[]>("/api/brands");
        this.brands = data;
        return data;
      } catch (error: any) {
        this.error =
          error.data?.message || error.message || "Failed to fetch brands";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchBrand(id: number) {
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<Brand>(`/api/brands/${id}`);
        this.brand = data;
        return data;
      } catch (error: any) {
        this.error =
          error.data?.message || error.message || "Failed to fetch brand";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createBrand(payload: BrandPayload & { logo?: File }) {
      this.loading = true;
      this.error = null;
      try {
        const formData = new FormData();
        formData.append("name", payload.name);
        if (payload.logo) formData.append("logo", payload.logo);
        if (payload.meta_title) formData.append("meta_title", payload.meta_title);
        if (payload.meta_description)
          formData.append("meta_description", payload.meta_description);

        const data = await useCustomFetch<Brand>("/api/brands", {
          method: "POST",
          body: formData,
        });

        this.brands.push(data);
        return data;
      } catch (error: any) {
        this.error =
          error.data?.message || error.message || "Failed to create brand";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateBrand(id: number, payload: BrandPayload & { logo?: File }) {
      this.loading = true;
      this.error = null;
      try {
        const formData = new FormData();
        if (payload.name) formData.append("name", payload.name);
        if (payload.logo) formData.append("logo", payload.logo);
        if (payload.meta_title) formData.append("meta_title", payload.meta_title);
        if (payload.meta_description)
          formData.append("meta_description", payload.meta_description);

        const data = await useCustomFetch<Brand>(`/api/brands/${id}`, {
          method: "POST", 
          body: formData,
          headers: {
            "X-HTTP-Method-Override": "PUT", // tell Laravel it’s actually PUT
          },
        });

        this.brands = this.brands.map((b) => (b.id === id ? data : b));
        if (this.brand?.id === id) {
          this.brand = data;
        }
        return data;
      } catch (error: any) {
        this.error =
          error.data?.message || error.message || "Failed to update brand";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteBrand(id: number) {
      this.loading = true;
      this.error = null;
      try {
        await useCustomFetch(`/api/brands/${id}`, { method: "DELETE" });
        this.brands = this.brands.filter((b) => b.id !== id);
        if (this.brand?.id === id) {
          this.brand = null;
        }
      } catch (error: any) {
        this.error =
          error.data?.message || error.message || "Failed to delete brand";
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },

  persist: {
    paths: ['brands', 'brand', 'error'] // Exclude loading state from persistence
  }
});
