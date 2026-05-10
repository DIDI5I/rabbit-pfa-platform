import { defineConfig } from 'vite'
import { resolve } from 'path'
import imagemin from 'vite-plugin-imagemin'

export default defineConfig({
  root: '.',
  plugins: [
    imagemin({
      // Configuration globale pour l'optimisation des images
      gifsicle: {
        optimizationLevel: 7,
        interlaced: false,
      },
      optipng: {
        optimizationLevel: 7,
      },
      mozjpeg: {
        quality: 80,
      },
      pngquant: {
        quality: [0.8, 0.9],
        speed: 4,
      },
      svgo: {
        plugins: [
          {
            name: 'removeViewBox',
          },
          {
            name: 'removeEmptyAttrs',
            active: false,
          },
        ],
      },
    }),
  ],
  build: {
    outDir: 'dist',
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        landing: resolve(__dirname, 'landing.html'),
        login: resolve(__dirname, 'login.html'),
        signup: resolve(__dirname, 'signup.html'),
        'owner-login': resolve(__dirname, 'owner-login.html'),
        client: resolve(__dirname, 'client.html'),
        owner: resolve(__dirname, 'owner.html'),
        supplier: resolve(__dirname, 'supplier.html')
      },
      // Ne pas traiter les scripts externes
      external: (id) => id.includes('/js/'),
    },
    // Optimisation supplémentaire du build
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
  },
  server: {
    port: 3000,
    open: true
  }
})