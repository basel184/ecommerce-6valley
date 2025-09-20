import { defineNuxtConfig } from 'nuxt/config'
// Nuxt 3 configuration
export default defineNuxtConfig({
  ssr: true,
  srcDir: '.',
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000/api',
    }
  },
  nitro: {
    preset: 'node-server',
    compatibilityDate: '2025-09-11'
  },
  typescript: {
    strict: true
  },
  modules: [
    '@nuxtjs/i18n'
  ],
  // @ts-expect-error i18n module runtime typing
  i18n: {
    strategy: 'prefix_except_default', // default (ar) without prefix, others with prefix (/en)
    defaultLocale: 'ar',
    detectBrowserLanguage: false,
    langDir: 'locales',
    locales: [
      { code: 'ar', language: 'ar', name: 'العربية', dir: 'rtl', file: 'ar.json' },
      { code: 'en', language: 'en', name: 'English', dir: 'ltr', file: 'en.json' }
    ],
    vueI18n: './i18n.config.ts'
  }
})
