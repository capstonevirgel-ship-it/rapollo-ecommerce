// stores/auth.ts
import { defineStore } from "pinia";
import { useCustomFetch } from "~/composables/useCustomFetch";
import { useCartStore } from "~/stores/cart";
import type { ForgotPasswordRequest, ResetPasswordRequest } from "~/types";

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as null | {id: number, user_name: string, email: string, role: string, is_suspended?: boolean},
        isAuthenticated: false,
        loading: false,
        error: null as string | null,
    }),

    getters: {
        isAdmin: (state) => state.user?.role === 'admin',
        isSuspended: (state) => state.user?.is_suspended === true,
    },

    actions: {
        async login(credentials: {login: string; password: string; remember: boolean }) {
            this.loading = true;
            this.error = null;

            try {
                const response = await useCustomFetch('/api/login', {
                    method: 'POST',
                    body: credentials
                }) as any;
                
                // Store the token for future requests
                if (response.token) {
                    const token = useCookie('auth-token', {
                        default: () => null,
                        maxAge: credentials.remember ? 60 * 60 * 24 * 30 : 60 * 60 * 24, // 30 days or 1 day
                        secure: true,
                        sameSite: 'strict'
                    });
                    token.value = response.token;
                }
                
                await this.fetchUser();
                // Redirect based on user role
                if (this.isAdmin) {
                    return navigateTo('/admin/dashboard');
                } else {
                    return navigateTo('/');
                }
            } catch (error: any) {
                this.error = error.data?.message || error.message || 'Login failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async register(userData: {user_name: string; email: string; password: string; password_confirmation: string}) {
            this.loading = true;
            this.error = null;

            try {
                const response = await useCustomFetch('/register', {
                    method: 'POST',
                    body: userData
                }) as any;
                
                if (response.message === 'Registration successful') {
                    // Store the token for future requests
                    if (response.token) {
                        const token = useCookie('auth-token');
                        token.value = response.token;
                    }
                    
                    // Set user data and authentication state
                    this.user = response.user;
                    this.isAuthenticated = true;
                    
                    // Sync cart after successful registration
                    try {
                        const cartStore = useCartStore();
                        await cartStore.syncGuestCart();
                        await cartStore.index();
                    } catch (error) {
                        console.error('Cart sync failed after registration:', error);
                    }
                    
                    // Redirect based on user role
                    if (this.isAdmin) {
                        return navigateTo('/admin/dashboard');
                    } else {
                        return navigateTo('/');
                    }
                }
            } catch (error: any) {
                this.error = error.data?.message || error.message || 'Registration failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchUser() {
            try {
                const user = await useCustomFetch<{ id: number; user_name: string; email: string; role: string; is_suspended?: boolean }>('/api/user');
                this.user = user;
                this.isAuthenticated = true;
                // After authenticating, sync any guest cart items to DB and refresh
                try {
                    console.log('🔐 User authenticated, syncing cart...')
                    const cartStore = useCartStore();
                    await cartStore.syncGuestCart();
                    await cartStore.index();
                    console.log('🔐 Cart sync completed')
                } catch (error) {
                    console.error('🔐 Cart sync failed:', error)
                }
            } catch (error) {
                this.logout();
                throw error;
            }
        },

        async logout() {
            try {
                await useCustomFetch('/api/logout', {
                    method: 'POST'
                });
            } finally {
                // Clear the auth token
                const token = useCookie('auth-token');
                token.value = null;
                
                // Clear cart state when logging out
                const cartStore = useCartStore();
                cartStore.clearCart();
                
                this.$reset();
                return navigateTo('/login');
            }
        },

        async forgotPassword(email: string) {
            this.loading = true;
            this.error = null;

            try {
                const response = await useCustomFetch('/forgot-password', {
                    method: 'POST',
                    body: { email } as ForgotPasswordRequest
                }) as any;
                
                return response;
            } catch (error: any) {
                this.error = error.data?.message || error.message || 'Failed to send password reset link';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async resetPassword(data: ResetPasswordRequest) {
            this.loading = true;
            this.error = null;

            try {
                const response = await useCustomFetch('/reset-password', {
                    method: 'POST',
                    body: data
                }) as any;
                
                return response;
            } catch (error: any) {
                this.error = error.data?.message || error.message || 'Failed to reset password';
                throw error;
            } finally {
                this.loading = false;
            }
        }
    },

    persist: true
});