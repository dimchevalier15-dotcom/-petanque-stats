import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',
    port: 5173,

    allowedHosts: [
      'petanque.liegermaster.fr',
    ],

    hmr: {
      host: 'petanque.liegermaster.fr',
      protocol: 'wss',
      clientPort: 443,
    },

    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
      },
    },
  },
})