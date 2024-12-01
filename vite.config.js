import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import crossOriginIsolation from 'vite-plugin-cross-origin-isolation';

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/app.css',
            'resources/js/app.js'
        ]),
        vue(),
        crossOriginIsolation()
    ],
    build: {
        target: 'es2022',
    }
});