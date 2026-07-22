import { fileURLToPath, URL } from 'node:url'

import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig(() => {
  const backendTarget = 'http://127.0.0.1:8000'

  const proxy = {
    '/api': {
      target: backendTarget,
      changeOrigin: true,
      secure: false,
    },

    '/storage': {
      target: backendTarget,
      changeOrigin: true,
      secure: false,
    },
  }

  const plugins = [
    vue(),
    tailwindcss(),
    VitePWA({
      registerType: 'autoUpdate',
      useCredentials: true, // penting: supaya fetch manifest ikut kirim cookie bypass ngrok

      devOptions: {
        enabled: true,
      },

      includeAssets: [
        'favicon.ico',
        'apple-touch-icon.png',
      ],

      manifest: {
        id: '/',
        name: 'Jurnal 7KAIH',
        short_name: '7KAIH',
        description:
          'Aplikasi jurnal 7 Kebiasaan Anak Indonesia Hebat.',
        lang: 'id-ID',
        start_url: '/',
        scope: '/',
        display: 'standalone',
        orientation: 'portrait',
        background_color: '#f8fafc',
        theme_color: '#0284c7',

        icons: [
          {
            src: '/pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png',
          },
          {
            src: '/pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
          },
          {
            src: '/pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable',
          },
        ],
      },

      workbox: {
        cleanupOutdatedCaches: true,
        navigateFallback: '/index.html',
      },
    }),
  ]

  return {
    plugins,

    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,

      allowedHosts: [
        '.ngrok-free.app',
        '.ngrok-free.dev',
        '.ngrok.app',
      ],

      proxy,
    },

    preview: {
      host: '0.0.0.0',
      port: 4173,
      strictPort: true,

      allowedHosts: [
        '.ngrok-free.app',
        '.ngrok-free.dev',
        '.ngrok.app',
      ],

      proxy,
    },

    resolve: {
      alias: {
        '@': fileURLToPath(
          new URL('./src', import.meta.url),
        ),
      },
    },

    optimizeDeps: {
      exclude: ['oh-vue-icons/icons']
    },
  }
})