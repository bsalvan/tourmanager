import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000', // ⚠️ Remplace 8000 par le port réel de ton serveur PHP/Backend sous WSL2
        changeOrigin: true,
        secure: false,
      }
    }
  }
})
