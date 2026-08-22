import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                arena: {
                    DEFAULT: '#FAF5EE',
                    light: '#FDFBF7',
                    dark: '#EDE0D0',
                },
                cuero: {
                    DEFAULT: '#4A3525',
                    light: '#6B5340',
                    dark: '#3A2A1D',
                },
                olivo: {
                    DEFAULT: '#606C38',
                    light: '#7A8A4A',
                    dark: '#4F5D2F',
                },
                mezclilla: {
                    DEFAULT: '#1F3A52',
                    light: '#2C5170',
                    dark: '#152738',
                },
                dorado: {
                    DEFAULT: '#C5A059',
                    light: '#D4B878',
                    dark: '#A8883D',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                script: ['"Great Vibes"', 'cursive'],
                slab: ['Arvo', 'serif'],
                display: ['"Playfair Display"', 'serif'],
            },
            animation: {
                'fade-in': 'fadeIn 0.8s ease-out',
                'slide-up': 'slideUp 0.6s ease-out',
                'pulse-soft': 'pulseSoft 2s infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(30px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                pulseSoft: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.7' },
                },
            },
        },
    },

    plugins: [forms, typography],
};
