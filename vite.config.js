import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        // Üretimde vendor chunk'ları: tarayıcı önbelleği + daha küçük ana bundle (deploy sonrası daha az indirme)
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('recharts')) return 'charts';
                    if (id.includes('antd')) return 'antd';
                    if (id.includes('@tanstack/react-query')) return 'react-query';
                    if (id.includes('@inertiajs')) return 'inertia';
                    if (id.includes('react-dom') || id.includes('/react/')) return 'react-vendor';
                },
            },
        },
        chunkSizeWarningLimit: 900,
    },
});
