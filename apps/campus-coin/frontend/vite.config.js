import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  // The deployed build is served by Django under /static/spa/ so its asset
  // URLs have to carry that prefix. Dev and local builds stay at "/".
  base: process.env.VITE_BASE || '/',
  server: {
    host: '127.0.0.1',
    port: 5173,
  },
})
