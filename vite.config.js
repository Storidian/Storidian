import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import autoprefixer from 'autoprefixer'
import path from 'path'

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
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'resources/js'),
        '@components': path.resolve(__dirname, 'resources/js/components'),
        '@composables': path.resolve(__dirname, 'resources/js/composables'),
        '@api': path.resolve(__dirname, 'resources/js/api'),
        '@stores': path.resolve(__dirname, 'resources/js/stores'),
        '@utils': path.resolve(__dirname, 'resources/js/utils')
      }
    },
    css: {
      postcss: {
        plugins: [autoprefixer]
      }
    }
  }
})


