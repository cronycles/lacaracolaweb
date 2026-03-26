import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // Entry points: main CSS (PostCSS) + main TypeScript
            input: ['resources/css/app.css', 'resources/ts/app.ts'],
            refresh: [
                'resources/views/**',
                'lang/**',
                'routes/**',
            ],
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/ts',
        },
    },
    css: {
        // PostCSS config loaded from postcss.config.js automatically
        devSourcemap: true,
    },
    build: {
        // Generate source maps for production debugging
        sourcemap: false,
        // Minify output
        minify: 'esbuild',
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
