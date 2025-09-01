import { defineStore } from "pinia";
import { useCustomFetch } from "~/composables/useCustomFetch";
import type { Product, ProductPayload } from "~/types";

export const useProductStore = defineStore("product", {
  state: () => ({
    products: [] as Product[],
    product: null as Product | null,
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchProducts(params: Record<string, any> = {}) {
      this.loading = true;
      this.error = null;
      try {
        const query = new URLSearchParams(params).toString();
        const url = query ? `/api/products?${query}` : "/api/products";

        const response = await useCustomFetch<any>(url, { method: "GET" });
        this.products = response.data || [];
        return this.products;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to fetch products";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchProduct(slug: string) {
      this.loading = true;
      this.error = null;
      try {
        const data = await useCustomFetch<Product>(`/api/products/${slug}`, { method: "GET" });
        this.product = data;
        return data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to fetch product";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createProduct(payload: ProductPayload) {
      this.loading = true;
      this.error = null;

      const formData = new FormData();

      // Basic fields
      if (payload.subcategory_id) formData.append("subcategory_id", String(payload.subcategory_id));
      if (payload.brand_id) formData.append("brand_id", String(payload.brand_id));
      else if (payload.brand_name) formData.append("brand_name", payload.brand_name);
      formData.append("name", payload.name);
      if (payload.description) formData.append("description", payload.description);
      if (payload.meta_title) formData.append("meta_title", payload.meta_title);
      if (payload.meta_description) formData.append("meta_description", payload.meta_description);
      if (payload.is_active !== undefined) formData.append("is_active", String(payload.is_active));
      if (payload.is_featured !== undefined) formData.append("is_featured", String(payload.is_featured));
      if (payload.is_hot !== undefined) formData.append("is_hot", String(payload.is_hot));
      if (payload.is_new !== undefined) formData.append("is_new", String(payload.is_new));

      // Images
      payload.images?.forEach(file => formData.append("images[]", file));

      // Variants
      payload.variants.forEach((variant, vIndex) => {
        if (variant.color_id) formData.append(`variants[${vIndex}][color_id]`, String(variant.color_id));
        else if (variant.color_name) {
          formData.append(`variants[${vIndex}][color_name]`, variant.color_name);
          if (variant.color_hex) formData.append(`variants[${vIndex}][color_hex]`, variant.color_hex);
        }

        if (variant.size_id) formData.append(`variants[${vIndex}][size_id]`, String(variant.size_id));
        else if (variant.size_name) formData.append(`variants[${vIndex}][size_name]`, variant.size_name);

        formData.append(`variants[${vIndex}][price]`, String(variant.price));
        formData.append(`variants[${vIndex}][stock]`, String(variant.stock));
        formData.append(`variants[${vIndex}][sku]`, variant.sku);

        variant.images?.forEach(file => formData.append(`variants[${vIndex}][images][]`, file));
      });

      try {
        const data = await useCustomFetch<Product>("/api/products", { method: "POST", body: formData });
        this.products.push(data);
        return data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to create product";
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },

  persist: true,
});
