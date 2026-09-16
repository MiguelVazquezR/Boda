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
                // Fondo base del sitio: azul muy tenue (contrasta con el primario y el secundario)
                niebla: {
                    DEFAULT: '#F4F8FD',
                    light: '#FFFFFF',
                    dark: '#E3EDF8',
                },
                // Tinta: azul intenso para textos y superficies oscuras
                tinta: {
                    DEFAULT: '#123B5E',
                    light: '#2F6389',
                    dark: '#0B2A45',
                },
                // Color primario de la marca
                primary: {
                    DEFAULT: '#58B4FF',
                    light: '#8CCBFF',
                    dark: '#2E96E8',
                },
                // Color secundario de la marca
                secondary: {
                    DEFAULT: '#F7A79E',
                    light: '#FBC7C0',
                    dark: '#E8827A',
                },
                mezclilla: {
                    DEFAULT: '#1F3A52',
                    light: '#2C5170',
                    dark: '#152738',
                },
                // Tela del sobre de la apertura de la invitación. Medida sobre
                // una captura temporal de la tela (ya eliminada): base #17233D
                // (promedio RGB exacto de la muestra) con los tonos de la trama
                // a ±3σ (L 34,4 y desviación 5,6): claro #213358 y oscuro
                // #0C1321. `deep` es el tono de sombra con el que se oscurecen
                // las solapas del sobre. La textura va en `.superficie-sobre`
                // (resources/css/app.css).
                sobre: {
                    DEFAULT: '#17233D',
                    light: '#213358',
                    dark: '#0C1321',
                    deep: '#070E24',
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
