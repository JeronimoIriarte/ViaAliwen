import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Las fuentes se descargan al compilar (npm run build) y se sirven desde el proyecto:
            // la página funciona sin conexión a internet. Se incluyen con la directiva @fonts.
            fonts: [
                bunny('Montserrat', { weights: [600, 700, 800] }),
                bunny('Nunito Sans', { weights: [400, 600, 700] }),
            ],
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
