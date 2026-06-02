import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Dev server proxies API calls to the Laravel backend on :8000.
export default defineConfig({
  plugins: [vue()],
  server: {
    port: 3000,
    proxy: {
      '/api': { target: 'http://localhost:8000', changeOrigin: true },
    },
  },
})
