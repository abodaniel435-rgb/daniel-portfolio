import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// The SPA talks to the API on the same origin by default (/api). In dev, proxy
// that path to the Django server on :8001 so no CORS or env var is needed.
// In production on cPanel, /api is served by Django from the same domain.
export default defineConfig({
  plugins: [react()],
  server: {
    host: '127.0.0.1',
    port: 5174,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8001',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
})
