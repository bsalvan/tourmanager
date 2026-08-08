import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: true, // Permet d'accéder à Vite via 192.168.0.44 ou localhost
    port: 5173
  }
})
