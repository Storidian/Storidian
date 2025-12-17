import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import autoprefixer from 'autoprefixer'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    server: {
      host: true,
      port: parseInt(env.VITE_PORT) || 5173,
      strictPort: true,
      cors: true,
      hmr: {
        host: env.VITE_DEV_HOST || 'localhost'
      }
    },
    plugins: [
      laravel({
        input: ['resources/js/app.js'],
        refresh: true
      }),
      vue()
    ],
    css: {
      postcss: {
        plugins: [autoprefixer]
      }
    }
  }
})

