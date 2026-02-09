import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        
    ],
    server : {
        host: '10.116.168.125', 
        // host: '192.168.137.147', 
        // host: '192.168.207.35', 
        port: 5173
    }
});
