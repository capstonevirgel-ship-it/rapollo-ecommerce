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
        // Build query string manually to handle arrays properly
        const queryParts: string[] = [];
        
        for (const [key, value] of Object.entries(params)) {
          if (value !== null && value !== undefined && value !== '') {
            if (Array.isArray(value)) {
              // Handle arrays by adding multiple parameters with []
              value.forEach(item => {
                if (item !== null && item !== undefined && item !== '') {
                  queryParts.push(`${key}[]=${encodeURIComponent(item)}`);
                }
              });
            } else {
              queryParts.push(`${key}=${encodeURIComponent(value)}`);
            }
          }
        }
        
        const query = queryParts.join('&');
        const url = query ? `/api/products?${query}` : "/api/products";

        const response = await useCustomFetch<any>(url, { method: "GET" });
        this.products = response.data || [];
        return response; // Return full response to include pagination meta
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

    async fetchRelatedProducts(slug: string) {
      try {
        const data = await useCustomFetch<Product[]>(`/api/products/${slug}/related`, { method: "GET" });
        return Array.isArray(data) ? data : [];
      } catch (error: any) {
        console.error('Failed to fetch related products:', error);
        return [];
      }
    },

    async createProduct(payload: ProductPayload) {
      this.loading = true;
      this.error = null;

      const formData = new FormData();

      // Basic fields
      if (payload.subcategory_id !== undefined) formData.append("subcategory_id", String(payload.subcategory_id));
      if (payload.brand_id !== undefined) formData.append("brand_id", String(payload.brand_id));
      else if (payload.brand_name) formData.append("brand_name", payload.brand_name);
      formData.append("name", payload.name);
      if (payload.description) formData.append("description", payload.description);
      if (payload.meta_title) formData.append("meta_title", payload.meta_title);
      if (payload.meta_description) formData.append("meta_description", payload.meta_description);
      if (payload.meta_keywords) formData.append("meta_keywords", payload.meta_keywords);
      if (payload.canonical_url) formData.append("canonical_url", payload.canonical_url);
      if (payload.robots) formData.append("robots", payload.robots);
      if (payload.is_active !== undefined) formData.append("is_active", String(payload.is_active));
      if (payload.is_featured !== undefined) formData.append("is_featured", String(payload.is_featured));
      if (payload.is_hot !== undefined) formData.append("is_hot", String(payload.is_hot));
      if (payload.is_new !== undefined) formData.append("is_new", String(payload.is_new));
      
      // Product base_price (for products without variants)
      if (payload.base_price !== undefined && payload.base_price !== null) {
        formData.append("base_price", String(payload.base_price));
      }

      // Product stock and sku (for products without variants)
      if (payload.stock !== undefined && payload.stock !== null) {
        formData.append("stock", String(payload.stock));
      }
      if (payload.sku !== undefined && payload.sku !== null && payload.sku !== '') {
        formData.append("sku", payload.sku);
      }

      // Default color
      if (payload.default_color_id !== undefined && payload.default_color_id !== null) {
        formData.append("default_color_id", String(payload.default_color_id));
      } else if (payload.default_color_name) {
        formData.append("default_color_name", payload.default_color_name);
        if (payload.default_color_hex) {
          formData.append("default_color_hex", payload.default_color_hex);
        }
      }

      // Sizes
      if (payload.sizes) {
        payload.sizes.forEach(sizeId => formData.append("sizes[]", String(sizeId)));
      }

      // Size stocks (for products with sizes but no color variants)
      if (payload.size_stocks) {
        Object.entries(payload.size_stocks).forEach(([sizeId, stock]) => {
          formData.append(`size_stocks[${sizeId}]`, String(stock));
        });
      }

      // Images
      payload.images?.forEach(file => formData.append("images[]", file));

      // Variants
      if (payload.variants && Array.isArray(payload.variants)) {
        payload.variants.forEach((variant, vIndex) => {
        if (variant.color_id) formData.append(`variants[${vIndex}][color_id]`, String(variant.color_id));
        else if (variant.color_name) {
          formData.append(`variants[${vIndex}][color_name]`, variant.color_name);
          if (variant.color_hex) formData.append(`variants[${vIndex}][color_hex]`, variant.color_hex);
        }

        if (variant.size_id) formData.append(`variants[${vIndex}][size_id]`, String(variant.size_id));
        else if (variant.size_name) formData.append(`variants[${vIndex}][size_name]`, variant.size_name);

        if (variant.available_sizes) {
          variant.available_sizes.forEach(sizeId => formData.append(`variants[${vIndex}][available_sizes][]`, String(sizeId)));
        }

        // Add individual stock per size
        if (variant.size_stocks) {
          Object.entries(variant.size_stocks).forEach(([sizeId, stock]) => {
            formData.append(`variants[${vIndex}][size_stocks][${sizeId}]`, String(stock));
          });
        }

        formData.append(`variants[${vIndex}][stock]`, String(variant.stock));
        formData.append(`variants[${vIndex}][sku]`, variant.sku);

        variant.images?.forEach(file => formData.append(`variants[${vIndex}][images][]`, file));
        });
      }

      // Debug: Log the FormData being sent
      console.log('=== FORM DATA BEING SENT TO BACKEND ===');
      for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
          console.log(`${key}:`, `[File: ${value.name}, Size: ${value.size} bytes, Type: ${value.type}]`);
        } else {
          console.log(`${key}:`, value);
        }
      }
      console.log('=== END FORM DATA ===');

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

    async deleteProduct(slug: string) {
      this.loading = true;
      this.error = null;
      try {
        await useCustomFetch(`/api/products/${slug}`, { method: "DELETE" });
        
        // Remove from local state
        this.products = this.products.filter(p => p.slug !== slug);
        
        return true;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to delete product";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateProduct(slug: string, payload: Partial<ProductPayload>) {
      this.loading = true;
      this.error = null;

      const formData = new FormData();

      // Basic fields
      if (payload.subcategory_id !== undefined) formData.append("subcategory_id", String(payload.subcategory_id));
      if (payload.brand_id !== undefined) formData.append("brand_id", String(payload.brand_id));
      formData.append("name", payload.name || "");
      if (payload.description !== undefined) formData.append("description", payload.description || "");
      if (payload.meta_title !== undefined) formData.append("meta_title", payload.meta_title || "");
      if (payload.meta_description !== undefined) formData.append("meta_description", payload.meta_description || "");
      if (payload.meta_keywords !== undefined) formData.append("meta_keywords", payload.meta_keywords || "");
      if (payload.canonical_url !== undefined) formData.append("canonical_url", payload.canonical_url || "");
      if (payload.robots !== undefined) formData.append("robots", payload.robots || "");
      if (payload.is_active !== undefined) formData.append("is_active", String(payload.is_active));
      if (payload.is_featured !== undefined) formData.append("is_featured", String(payload.is_featured));
      if (payload.is_hot !== undefined) formData.append("is_hot", String(payload.is_hot));
      if (payload.is_new !== undefined) formData.append("is_new", String(payload.is_new));
      
      // Product base_price
      if (payload.base_price !== undefined && payload.base_price !== null) {
        formData.append("base_price", String(payload.base_price));
      }

      // Product stock and sku (for products without variants)
      if (payload.stock !== undefined && payload.stock !== null) {
        formData.append("stock", String(payload.stock));
      }
      if (payload.sku !== undefined && payload.sku !== null && payload.sku !== '') {
        formData.append("sku", payload.sku);
      }

      // Default color
      if (payload.default_color_id !== undefined && payload.default_color_id !== null) {
        formData.append("default_color_id", String(payload.default_color_id));
      } else if (payload.default_color_name) {
        formData.append("default_color_name", payload.default_color_name);
        if (payload.default_color_hex) {
          formData.append("default_color_hex", payload.default_color_hex);
        }
      }

      // Sizes
      if (payload.sizes) {
        payload.sizes.forEach(sizeId => formData.append("sizes[]", String(sizeId)));
      }

      // Size stocks (for products with sizes but no color variants)
      if (payload.size_stocks) {
        Object.entries(payload.size_stocks).forEach(([sizeId, stock]) => {
          formData.append(`size_stocks[${sizeId}]`, String(stock));
        });
      }

      // Product Images - existing images to keep
      if (payload.existing_image_ids) {
        payload.existing_image_ids.forEach(imageId => formData.append("existing_image_ids[]", String(imageId)));
      }

      // Product Images - images to delete
      if (payload.images_to_delete) {
        payload.images_to_delete.forEach(imageId => formData.append("images_to_delete[]", String(imageId)));
      }

      // Product Images - new images
      if (payload.new_images) {
        payload.new_images.forEach(file => formData.append("new_images[]", file));
      }

      // Product Images - primary image selection
      if (payload.primary_existing_image_id !== undefined && payload.primary_existing_image_id !== null) {
        formData.append("primary_existing_image_id", String(payload.primary_existing_image_id));
      }
      if (payload.primary_new_image_index !== undefined && payload.primary_new_image_index !== null) {
        formData.append("primary_new_image_index", String(payload.primary_new_image_index));
      }

      // Variants
      if (payload.variants && Array.isArray(payload.variants)) {
        payload.variants.forEach((variant, vIndex) => {
          // Variant ID for updates - send all variant IDs to prevent deletion
          // The backend needs all variant IDs to know which ones to keep
          if (variant.variant_ids && Array.isArray(variant.variant_ids) && variant.variant_ids.length > 0) {
            // Send first ID as the main variant ID (for backward compatibility)
            formData.append(`variants[${vIndex}][id]`, String(variant.variant_ids[0]));
            // Send all other variant IDs as additional IDs to preserve
            variant.variant_ids.slice(1).forEach(vId => {
              formData.append(`variants[${vIndex}][variant_ids][]`, String(vId));
            });
          } else if (variant.id) {
            // Fallback to single ID if variant_ids not available
            formData.append(`variants[${vIndex}][id]`, String(variant.id));
          }

          // Color
          if (variant.color_id) formData.append(`variants[${vIndex}][color_id]`, String(variant.color_id));
          else if (variant.color_name) {
            formData.append(`variants[${vIndex}][color_name]`, variant.color_name);
            if (variant.color_hex) formData.append(`variants[${vIndex}][color_hex]`, variant.color_hex);
          }

          // Available sizes
          if (variant.available_sizes) {
            variant.available_sizes.forEach(sizeId => formData.append(`variants[${vIndex}][available_sizes][]`, String(sizeId)));
          }

          // Size stocks
          if (variant.size_stocks) {
            Object.entries(variant.size_stocks).forEach(([sizeId, stock]) => {
              formData.append(`variants[${vIndex}][size_stocks][${sizeId}]`, String(stock));
            });
          }

          formData.append(`variants[${vIndex}][stock]`, String(variant.stock));
          formData.append(`variants[${vIndex}][sku]`, variant.sku);

          // Variant Images - existing images to keep
          if (variant.existing_images) {
            variant.existing_images.forEach(imageId => formData.append(`variants[${vIndex}][existing_images][]`, String(imageId)));
          }

          // Variant Images - images to delete
          if (variant.images_to_delete) {
            console.log(`🔴 FRONTEND: Adding images_to_delete for variant ${vIndex}:`, variant.images_to_delete);
            variant.images_to_delete.forEach(imageId => {
              console.log(`🔴 FRONTEND: Appending image ID ${imageId} to FormData`);
              formData.append(`variants[${vIndex}][images_to_delete][]`, String(imageId));
            });
          } else {
            console.log(`🔴 FRONTEND: No images_to_delete for variant ${vIndex}`);
          }

          // Variant Images - new images
          if (variant.new_images) {
            variant.new_images.forEach(file => formData.append(`variants[${vIndex}][new_images][]`, file));
          }

          // Variant Images - primary image selection
          if (variant.primary_existing_image_id !== undefined && variant.primary_existing_image_id !== null) {
            formData.append(`variants[${vIndex}][primary_existing_image_id]`, String(variant.primary_existing_image_id));
          }
          if (variant.primary_new_image_index !== undefined && variant.primary_new_image_index !== null) {
            formData.append(`variants[${vIndex}][primary_new_image_index]`, String(variant.primary_new_image_index));
          }
        });
      }

      // Use POST method for file uploads (changed from PUT for better file handling)
      try {
        const data = await useCustomFetch<Product>(`/api/products/${slug}/update`, { 
          method: "POST", 
          body: formData
        });
        
        // Update local state
        const index = this.products.findIndex(p => p.slug === slug);
        if (index !== -1) {
          this.products[index] = data;
        }
        if (this.product?.slug === slug) {
          this.product = data;
        }
        
        return data;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to update product";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async toggleProductActive(slug: string, isActive: boolean) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>(`/api/products/${slug}`, {
          method: "PUT",
          body: { is_active: isActive }
        });

        // Update local state
        const index = this.products.findIndex(p => p.slug === slug);
        if (index !== -1) {
          this.products[index].is_active = isActive ? 1 : 0;
        }
        if (this.product?.slug === slug) {
          this.product.is_active = isActive ? 1 : 0;
        }

        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to toggle product status";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async bulkUpdateLabels(products: Array<{ id: number; is_featured?: boolean; is_hot?: boolean; is_new?: boolean }>) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>("/api/products/bulk-update-labels", {
          method: "PATCH",
          body: { products }
        });

        // Update local state
        products.forEach(productData => {
          const index = this.products.findIndex(p => p.id === productData.id);
          if (index !== -1) {
            if (productData.is_featured !== undefined) {
              this.products[index].is_featured = productData.is_featured ? 1 : 0;
            }
            if (productData.is_hot !== undefined) {
              this.products[index].is_hot = productData.is_hot ? 1 : 0;
            }
            if (productData.is_new !== undefined) {
              this.products[index].is_new = productData.is_new ? 1 : 0;
            }
          }
          if (this.product?.id === productData.id) {
            if (productData.is_featured !== undefined) {
              this.product.is_featured = productData.is_featured ? 1 : 0;
            }
            if (productData.is_hot !== undefined) {
              this.product.is_hot = productData.is_hot ? 1 : 0;
            }
            if (productData.is_new !== undefined) {
              this.product.is_new = productData.is_new ? 1 : 0;
            }
          }
        });

        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to update product labels";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async bulkUpdateActiveStatus(productIds: number[], isActive: boolean) {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>("/api/products/bulk-update-active-status", {
          method: "PATCH",
          body: { product_ids: productIds, is_active: isActive }
        });

        // Update local state
        productIds.forEach(productId => {
          const index = this.products.findIndex(p => p.id === productId);
          if (index !== -1) {
            this.products[index].is_active = isActive ? 1 : 0;
          }
          if (this.product?.id === productId) {
            this.product.is_active = isActive ? 1 : 0;
          }
        });

        return response;
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to update product status";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchFeaturedProducts() {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>("/api/products/homepage/featured", { method: "GET" });
        return response.data || [];
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to fetch featured products";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchTrendingProducts() {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>("/api/products/homepage/trending", { method: "GET" });
        return response.data || [];
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to fetch trending products";
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchNewArrivals() {
      this.loading = true;
      this.error = null;
      try {
        const response = await useCustomFetch<any>("/api/products/homepage/new-arrivals", { method: "GET" });
        return response.data || [];
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to fetch new arrivals";
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },

  persist: {
    paths: ['products', 'product', 'error'] // Exclude loading state from persistence
  }
});
