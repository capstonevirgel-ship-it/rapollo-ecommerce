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
    // Fetch all cart items (only if logged in)
    async fetchCart() {
      this.loading = true
      this.error = null
      try {
        const data = await useCustomFetch<Cart[]>("/api/cart")
        this.cart = data
      } catch (error: any) {
        this.error = error.data?.message || error.message || "Failed to load cart"
      } finally {
        this.loading = false
      }
    },

    // ✅ Add item to cart (handles guest + logged in)
    async addToCart(payload: CartPayload, isLoggedIn: boolean) {
      this.loading = true
      this.error = null

      try {
        if (isLoggedIn) {
          // 🔹 Add to DB
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
        } else {
          // 🔹 Save as guest (localStorage)
          const guestCart: CartPayload[] = JSON.parse(localStorage.getItem(GUEST_CART_KEY) || "[]")

          const index = guestCart.findIndex((item) => item.variant_id === payload.variant_id)
          if (index !== -1) {
            guestCart[index].quantity += payload.quantity
          } else {
            guestCart.push(payload)
          }

          localStorage.setItem(GUEST_CART_KEY, JSON.stringify(guestCart))
          return payload
        }
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
      localStorage.removeItem(GUEST_CART_KEY)
    },

    // ✅ Load guest cart from localStorage
    loadGuestCart(): CartPayload[] {
      return JSON.parse(localStorage.getItem(GUEST_CART_KEY) || "[]")
    },

    // ✅ Sync guest cart to DB after login
    async syncGuestCart() {
      const guestCart = this.loadGuestCart()
      if (!guestCart.length) return

      for (const item of guestCart) {
        await this.addToCart(item, true) // true = logged in
      }

      localStorage.removeItem(GUEST_CART_KEY)
    },
  },

  persist: true, // keep store in localStorage (for logged-in cart)
})
