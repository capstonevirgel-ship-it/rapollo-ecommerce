// stores/auth.ts
import { defineStore } from "pinia";
import { useCustomFetch } from "~/composables/useCustomFetch";
import { useCartStore } from "~/stores/cart";

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as null | {id: number, user_name: string, email: string},
        isAuthenticated: false,
        loading: false,
        error: null as string | null,
    }),

    actions: {
        async login(credentials: {login: string; password: string; remember: boolean }) {
            this.loading = true;
            this.error = null;

            try {
                await useCustomFetch('/login', {
                    method: 'POST',
                    body: credentials
                });
                
                await this.fetchUser();
                return navigateTo('/admin/dashboard');
            } catch (error: any) {
                this.error = error.data?.message || error.message || 'Login failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchUser() {
            try {
                const user = await useCustomFetch<{ id: number; user_name: string; email: string }>('/api/user');
                this.user = user;
                this.isAuthenticated = true;
                // After authenticating, sync any guest cart items to DB and refresh
                try {
                    const cartStore = useCartStore();
                    await cartStore.syncGuestCart();
                    await cartStore.index();
                } catch (_) {}
            } catch (error) {
                this.logout();
                throw error;
            }
        },

        async logout() {
            try {
                await useCustomFetch('/logout', {
                    method: 'POST'
                });
            } finally {
                this.$reset();
                return navigateTo('/login');
            }
        }
    },

    persist: true
});