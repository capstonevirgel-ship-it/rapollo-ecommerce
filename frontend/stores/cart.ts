import { defineStore } from "pinia"
import { useCustomFetch } from "~/composables/useCustomFetch"
import type { Cart, CartPayload } from "~/types"

const GUEST_CART_KEY = "guest_cart"

export const useCartStore = defineStore("cart", {
  state: () => ({
    cart: [] as Cart[],
    loading: false,
    error: null as string | null,
  }),

  getters: {
    cartCount: (state) => state.cart.reduce((sum, item) => sum + item.quantity, 0),
    cartTotal: (state) => state.cart.reduce((sum, item) => sum + item.quantity * item.variant.price, 0),
  },

  actions: {
    // Fetch all cart items (only if logged in) via index()
    async fetchCart() {
      return this.index()
    },

    // REST-style index(): fetch cart items from backend
    async index() {
      this.loading = true
      this.error = null
      try {
        // If on client and there is a guest cart, sync it first after login
        if (import.meta.client) {
          const guestItems = this.loadGuestCart()
          if (guestItems.length) {
            await this.syncGuestCart()
          }
        }

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
    async addToCart(payload: CartPayload, isLoggedIn: boolean) {
      this.error = null

      try {
        if (isLoggedIn) {
          // 🔹 Delegate to REST-style store()
          return await this.store(payload)
        } else {
          // 🔹 Save as guest (localStorage)
          if (!import.meta.client) return payload

          const guestCart: CartPayload[] = this.loadGuestCart()
          const index = guestCart.findIndex((item) => item.variant_id === payload.variant_id)
          if (index !== -1) {
            guestCart[index].quantity += payload.quantity
            // Remove if quantity drops to 0 or less
            if (guestCart[index].quantity <= 0) {
              guestCart.splice(index, 1)
            }
          } else if (payload.quantity > 0) {
            guestCart.push(payload)
          }

          localStorage.setItem(GUEST_CART_KEY, JSON.stringify(guestCart))
          return payload
        }
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to add to cart"
        throw error
      }
    },

    // REST-style store(): add a cart item in backend
    async store(payload: CartPayload) {
      this.loading = true
      this.error = null
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

        return data
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to add to cart"
        throw error
      } finally {
        this.loading = false
      }
    },

    // ✅ Update item quantity
    async updateCart(id: number, quantity: number) {
      this.loading = true
      this.error = null
      try {
        const data = await useCustomFetch<Cart>(`/api/cart/${id}`, {
          method: "PUT",
          body: { quantity },
        })

        const index = this.cart.findIndex((item) => item.id === id)
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
    async removeFromCart(id: number) {
      this.loading = true
      this.error = null
      try {
        await useCustomFetch(`/api/cart/${id}`, { method: "DELETE" })
        this.cart = this.cart.filter((item) => item.id !== id)
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to remove item"
        throw error
      } finally {
        this.loading = false
      }
    },

    // ✅ Clear cart locally (DB + store)
    clearCart() {
      this.cart = []
      if (import.meta.client) {
        localStorage.removeItem(GUEST_CART_KEY)
      }
    },

    // ✅ Load guest cart from localStorage
    loadGuestCart(): CartPayload[] {
      if (!import.meta.client) return []
      try {
        return JSON.parse(localStorage.getItem(GUEST_CART_KEY) || "[]")
      } catch {
        return []
      }
    },

    // ✅ Sync guest cart to DB after login
    async syncGuestCart() {
      if (!import.meta.client) return
      const guestCart = this.loadGuestCart()
      if (!guestCart.length) return

      for (const item of guestCart) {
        await this.store(item)
      }

      localStorage.removeItem(GUEST_CART_KEY)
      // Refresh authoritative cart from backend
      await this.index().catch(() => {})
    },
  },

  persist: true, // keep store in localStorage (for logged-in cart)
})
