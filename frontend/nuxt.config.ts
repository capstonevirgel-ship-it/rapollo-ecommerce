// https://nuxt.com/docs/api/configuration/nuxt-config
import { resolve } from 'path';
import tailwindcss from "@tailwindcss/vite";
import { icons as mdi } from "@iconify-json/mdi";

export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000/api',
      baseURL: 'http://localhost:3000',
      websocketUrl: process.env.WEBSOCKET_URL || 'http://localhost:6001'
    }
  },
  alias: { "@": resolve(__dirname, ".")},
  compatibilityDate: '2025-05-15',
  devtools: { enabled: true },
  devServer: {
    host: '0.0.0.0',
    port: 3000
  },
  css: ['~/assets/css/main.css'],
  vite: {
    plugins: [
      tailwindcss(),
    ]
  },
  nitro: {
    minify: false, // Disable minification for prettier HTML in development
    compressPublicAssets: false, // Disable compression for cleaner output
  },
  experimental: {
    payloadExtraction: false, // Helps with cleaner HTML structure
  },
  app: {
    head: {
      htmlAttrs: {
        lang: 'en'
      }
    }
  },
  modules: ["@nuxt/icon", "@pinia/nuxt", 'pinia-plugin-persistedstate/nuxt'],
})