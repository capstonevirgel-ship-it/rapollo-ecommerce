// https://nuxt.com/docs/api/configuration/nuxt-config
import { resolve } from 'path';
import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000',
      baseURL: 'http://localhost:3000'
    }
  },
  alias: { "@": resolve(__dirname, ".")},
  compatibilityDate: '2025-05-15',
  devtools: { enabled: true },
  css: ['~/assets/css/main.css'],
  vite: {
    plugins: [
      tailwindcss(),
    ]
  },
  nitro: {
    devProxy: {
      '/api': {
        target: 'http://localhost:8000/api',
        changeOrigin: true,
        prependPath: true
      }
    }
  },
  modules: ["@nuxt/icon", "@pinia/nuxt", 'pinia-plugin-persistedstate/nuxt'],
})