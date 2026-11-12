import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import { VitePWA } from 'vite-plugin-pwa'
import { version } from './package.json'

// Laravel, reached through the Compose nginx on the laptop (APP_PORT in the root .env).
const backend = process.env.BACKEND_URL ?? 'http://127.0.0.1:23417'

// https://vite.dev/config/
export default defineConfig({
  define: {
    // YEAR.M.P, bumped by scripts/release.sh.
    __APP_VERSION__: JSON.stringify(version),
    __APP_BUILD_TIME__: JSON.stringify(new Date().toISOString()),
  },
  plugins: [
    vue(),
    vueDevTools(),
    VitePWA({
      // A new deploy waits for the user to tap "Reload" instead of swapping
      // code under them mid-task. See src/components/AppUpdatePrompt.vue.
      registerType: 'prompt',
      injectRegister: false,
      // Icons and their manifest/head entries are generated from public/logo.svg
      // at build time (pwa-assets.config.ts).
      pwaAssets: { config: true, overrideManifestIcons: true },
      manifest: {
        id: '/',
        name: 'Kam Apps',
        short_name: 'Kam Apps',
        description: 'Gifts, food and money tools for the family.',
        start_url: '/',
        scope: '/',
        display: 'standalone',
        theme_color: '#0f766e',
        background_color: '#ffffff',
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,svg,png,ico,webmanifest}'],
        navigateFallback: '/index.html',
        // Laravel routes must always hit the network, never the cached app shell.
        navigateFallbackDenylist: [/^\/api\//, /^\/sanctum\//, /^\/up$/],
        cleanupOutdatedCaches: true,
      },
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    // Same-origin in dev too: Vite forwards Laravel routes, keeping the Host
    // header so Sanctum treats the dev server as a stateful origin.
    proxy: {
      '/api': backend,
      '/sanctum': backend,
      '/up': backend,
    },
  },
})
