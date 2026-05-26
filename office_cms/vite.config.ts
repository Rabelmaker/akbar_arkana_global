import { fileURLToPath } from 'node:url'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'
import vuetify from 'vite-plugin-vuetify'
import svgLoader from 'vite-svg-loader'

export default defineConfig({
  plugins: [
    vue({
      template: {
        compilerOptions: {
          isCustomElement: tag => tag === 'swiper-container' || tag === 'swiper-slide',
        },
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    laravel({
      input: ['resources/js/app.js'],
      refresh: true,
    }),
    vuetify({
      styles: {
        configFile: 'resources/styles/variables/_vuetify.scss',
      },
    }),
    Components({
      dirs: [
        'resources/js/@core/components',
        'resources/js/components',
        'resources/js/layouts/components',
      ],
      dts: false,
    }),
    AutoImport({
      imports: ['vue', 'vue-router', '@vueuse/core', '@vueuse/math', 'vue-i18n', 'pinia'],
      dirs: [
        './resources/js/@core/utils',
        './resources/js/@core/composable',
        './resources/js/composables',
        './resources/js/utils',
      ],
      vueTemplate: true,
    }),
    svgLoader(),
  ],
  define: {
    'process.env': {},
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
      '@core': fileURLToPath(new URL('./resources/js/@core', import.meta.url)),
      '@layouts': fileURLToPath(new URL('./resources/js/@layouts', import.meta.url)),
      '@core-scss': fileURLToPath(new URL('./resources/styles/@core', import.meta.url)),
      '@images': fileURLToPath(new URL('./resources/images', import.meta.url)),
      '@styles': fileURLToPath(new URL('./resources/styles', import.meta.url)),
      '@themeConfig': fileURLToPath(new URL('./themeConfig.js', import.meta.url)),
      '@configured-variables': fileURLToPath(new URL('./resources/styles/variables/_template.scss', import.meta.url)),
    },
  },
  optimizeDeps: {
    exclude: ['vuetify'],
    entries: ['./resources/js/**/*.vue'],
  },
  build: {
    chunkSizeWarningLimit: 5000,
  },
})
