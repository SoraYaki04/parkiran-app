import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Inter', 'sans-serif'],
            },
            colors: {
                primary: {
                    DEFAULT: '#facc14',
                    50: '#fefce8',
                    100: '#fef9c3',
                    200: '#fef08a',
                    300: '#fde047',
                    400: '#facc15',
                    500: '#eab308',
                    600: '#ca8a04',
                    700: '#a16207',
                    800: '#854d0e',
                    900: '#713f12',
                    950: '#422006',
                },
                background: {
                    light: '#f8f8f5',
                    dark: '#1F2933',
                },
                card: {
                    dark: '#111827',
                },
            },
            borderRadius: {
                'xl': '0.75rem',
            },
            boxShadow: {
                '2xl': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
            },
        },
    },

    plugins: [forms],
};