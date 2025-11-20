import { defineStore } from "pinia"
import { useCustomFetch } from "~/composables/useCustomFetch"
import { useAlert } from "~/composables/useAlert"
import { useAuthStore } from "~/stores/auth"
import type { Cart, CartPayload } from "~/types"

const GUEST_CART_KEY = "guest_cart"

export const useCartStore = defineStore("cart", {
  state: () => ({
    cart: [] as Cart[],
    loading: false,
    error: null as string | null,
    guestVersion: 0,
    isSyncing: false, // Flag to prevent multiple syncs
  }),

  getters: {
    cartCount: (state) => state.cart.reduce((sum, item) => sum + item.quantity, 0),
    cartTotal: (state) => state.cart.reduce((sum, item) => sum + item.quantity * (item.variant?.product?.price || item.variant?.price || 0), 0),
  },

  actions: {
    // Helper: build a minimal Cart object for guest storage
    buildGuestCartItem(variant_id: number, quantity: number, variantData?: any): Cart {
      const placeholderProduct = {
        price: 0,
        id: 0,
        subcategory_id: 0,
        brand_id: 0,
        name: 'Product',
        slug: '',
        description: '',
        meta_title: '',
        meta_description: '',
        is_active: 0,
        is_featured: 0,
        is_hot: 0,
        is_new: 0,
        created_at: '',
        updated_at: '',
        brand: {
          id: 0,
          name: '',
          slug: '',
          logo_url: null,
          meta_title: null,
          meta_description: null,
          created_at: '',
          updated_at: ''
        },
        subcategory: {
          id: 0,
          category_id: 0,
          name: '',
          slug: '',
          description: '',
          meta_title: '',
          meta_description: '',
          created_at: '',
          updated_at: '',
          category: {
            id: 0,
            name: '',
            slug: '',
            meta_title: '',
            meta_description: '',
            created_at: '',
            updated_at: ''
          },
          products: []
        },
        variants: [],
        images: []
      }

      // Use provided variant data if available, otherwise use placeholders
      const variant = variantData || {
        id: variant_id,
        product_id: 0,
        color_id: 0,
        size_id: 0,
        price: 0,
        stock: 0,
        sku: '',
        created_at: '',
        updated_at: '',
        product: placeholderProduct,
        color: { id: 0, name: '', hex_code: '' },
        size: { id: 0, name: '', description: null },
        images: []
      }

      const cartItem: Cart = {
        id: 0,
        user_id: 0,
        variant_id,
        quantity,
        created_at: '',
        updated_at: '',
        variant
      }

      return cartItem
    },
    // Fetch all cart items (only if logged in) via index()
    async fetchCart() {
      return this.index()
    },

    // REST-style index(): fetch cart items from backend
    async index() {
      this.loading = true
      this.error = null
      try {
        const data = await useCustomFetch<Cart[]>("/api/cart")
        this.cart = data
        return data
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load cart"
        throw error
      } finally {
        this.loading = false
      }
    },

    // ✅ Add item to cart (handles guest + logged in)
    async addToCart(payload: CartPayload, isLoggedIn: boolean, guestItem?: Cart) {
      this.error = null

      // Prevent admins from adding to cart
      if (isLoggedIn) {
        const authStore = useAuthStore()
        if (authStore.isAdmin) {
          const { error } = useAlert()
          error('Admin Restriction', 'Administrators cannot add items to cart. Please use a customer account to make purchases.')
          throw new Error('Administrators cannot add items to cart')
        }
      }

      try {
        if (isLoggedIn) {
          // 🔹 Delegate to REST-style store()
          return await this.store(payload)
        } else {
          // 🔹 Save as guest (localStorage) using Cart[]
          if (!import.meta.client) return payload

          const guestCart: Cart[] = this.loadGuestCart()
          const index = guestCart.findIndex((item) => item.variant_id === payload.variant_id)
          if (index !== -1) {
            guestCart[index].quantity += payload.quantity
            if (guestCart[index].quantity <= 0) {
              guestCart.splice(index, 1)
            }
          } else if (payload.quantity > 0) {
            // Use the provided guestItem if available, otherwise build one
            const itemToAdd = guestItem || this.buildGuestCartItem(payload.variant_id, payload.quantity)
            guestCart.push(itemToAdd)
          }

          localStorage.setItem(GUEST_CART_KEY, JSON.stringify(guestCart))
          this.guestVersion++
          
          // Update the store's cart array with the latest guest cart
          this.cart = guestCart
          
          // Show success message for guest
          const { success } = useAlert()
          success('Added to Cart', 'Item has been added to your cart!')
          
          return payload
        }
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to add to cart"
        throw error
      }
    },

    // REST-style store(): add a cart item in backend
    async store(payload: CartPayload) {
      // Prevent admins from adding to cart
      const authStore = useAuthStore()
      if (authStore.isAdmin) {
        const { error } = useAlert()
        error('Admin Restriction', 'Administrators cannot add items to cart. Please use a customer account to make purchases.')
        throw new Error('Administrators cannot add items to cart')
      }

      this.loading = true
      this.error = null
      
      const { success, error } = useAlert()
      
      try {
        const data = await useCustomFetch<Cart>("/api/cart", {
          method: "POST",
          body: payload,
        })

        const index = this.cart.findIndex((item) => item.variant_id === payload.variant_id)
        if (index !== -1) {
          this.cart[index] = data
        } else {
          this.cart.push(data)
        }

        // Show success message
        success('Added to Cart', 'Item has been added to your cart!')
        
        return data
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to add to cart"
        
        // Show error message
        error('Failed to Add Item', this.error)
        
        throw error
      } finally {
        this.loading = false
      }
    },

    // ✅ Update item quantity
    async updateCart(idOrVariantId: number, quantity: number, isLoggedIn: boolean = true) {
      this.error = null
      if (!isLoggedIn) {
        if (!import.meta.client) return
        const guestCart: Cart[] = this.loadGuestCart()
        const index = guestCart.findIndex((item) => item.variant_id === idOrVariantId)
        if (index !== -1) {
          if (quantity <= 0) {
            guestCart.splice(index, 1)
          } else {
            guestCart[index].quantity = quantity
          }
          localStorage.setItem(GUEST_CART_KEY, JSON.stringify(guestCart))
          this.guestVersion++
          
          // Update the store's cart array with the latest guest cart
          this.cart = guestCart
        }
        return
      }

      this.loading = true
      try {
        const data = await useCustomFetch<Cart>(`/api/cart/${idOrVariantId}`, {
          method: "PUT",
          body: { quantity },
        })

        const index = this.cart.findIndex((item) => item.id === idOrVariantId)
        if (index !== -1) {
          this.cart[index] = data
        }

        return data
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to update cart"
        throw error
      } finally {
        this.loading = false
      }
    },

    // ✅ Remove item from cart
    async removeFromCart(idOrVariantId: number, isLoggedIn: boolean = true) {
      this.error = null
      
      const { success, error } = useAlert()
      
      if (!isLoggedIn) {
        if (!import.meta.client) return
        const guestCart: Cart[] = this.loadGuestCart()
        const after = guestCart.filter((item) => item.variant_id !== idOrVariantId)
        localStorage.setItem(GUEST_CART_KEY, JSON.stringify(after))
        this.guestVersion++
        
        // Update the store's cart array with the latest guest cart
        this.cart = after
        
        // Show success message for guest
        success('Removed from Cart', 'Item has been removed from your cart!')
        return
      }

      this.loading = true
      try {
        await useCustomFetch(`/api/cart/${idOrVariantId}`, { method: "DELETE" })
        this.cart = this.cart.filter((item) => item.id !== idOrVariantId)
        
        // Show success message
        success('Removed from Cart', 'Item has been removed from your cart!')
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to remove item"
        
        // Show error message
        error('Failed to Remove Item', this.error)
        
        throw error
      } finally {
        this.loading = false
      }
    },

    // ✅ Clear cart locally (DB + store)
    clearCart() {
      this.cart = []
      this.isSyncing = false
      if (import.meta.client) {
        localStorage.removeItem(GUEST_CART_KEY)
      }
    },

    // ✅ Store purchase ID for success page
    setLastPurchaseId(purchaseId: number) {
      if (import.meta.client) {
        sessionStorage.setItem('last_purchase_id', purchaseId.toString())
      }
    },

    // ✅ Load guest cart from localStorage
    loadGuestCart(): Cart[] {
      if (!import.meta.client) return []
      try {
        return JSON.parse(localStorage.getItem(GUEST_CART_KEY) || "[]")
      } catch {
        return []
      }
    },

    // ✅ Load guest cart into main cart array for display
    loadGuestCartIntoStore() {
      if (!import.meta.client) return
      const guestCart = this.loadGuestCart()
      this.cart = guestCart
    },

    // ✅ Sync guest cart to DB after login
    async syncGuestCart() {
      if (!import.meta.client) return
      
      // Prevent multiple simultaneous syncs
      if (this.isSyncing) {
        console.log('🛒 Sync already in progress, skipping')
        return
      }
      
      const guestCart = this.loadGuestCart()
      if (!guestCart.length) return

      console.log('🛒 Syncing guest cart:', guestCart)
      this.isSyncing = true

      try {
        // Clear any persisted cart state first to avoid interference
        this.cart = []
        
        // Process each guest cart item - backend now handles the logic properly
        for (const guestItem of guestCart) {
          console.log(`🛒 Syncing item ${guestItem.variant_id} with quantity ${guestItem.quantity}`)
          await this.store({ variant_id: guestItem.variant_id, quantity: guestItem.quantity })
        }

        localStorage.removeItem(GUEST_CART_KEY)
        this.guestVersion++
        // Refresh authoritative cart from backend
        await this.index().catch(() => {})
        console.log('🛒 Sync completed, final cart:', this.cart)
      } catch (error) {
        console.error('Failed to sync guest cart:', error)
        // Don't clear guest cart if sync fails
      } finally {
        this.isSyncing = false
      }
    },
  },

  persist: true, // keep store in localStorage (for logged-in cart)
})
