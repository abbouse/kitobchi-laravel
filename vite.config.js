import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        tailwindcss(), // 1-o'ringa o'tkazildi
        react(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/a122-admin.css',
                'resources/css/a122-bootstrap-admin.css',
                'resources/css/kitobchi-hubdesk.css',
                'resources/css/marketing-tokens.css',
                'resources/css/legal-pages.css',
                'resources/css/legal-doc-static.css',
                'resources/css/kitobchi-popcorn.css',
                'resources/js/app.js',
                'resources/js/a122-admin.js',
                'resources/js/boshqaruv/main.tsx',
            ],
            refresh: true,
        }),
    ],
});
