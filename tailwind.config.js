import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                pink: {
                    50: '#FFF5F8',
                    100: '#FCE4EC',
                    200: '#F8BBD0',
                    500: '#D81B60',
                    600: '#C2185B',
                    700: '#9C1049',
                },
            },
        },
    },

    plugins: [forms],
};
