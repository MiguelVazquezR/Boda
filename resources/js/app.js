import './bootstrap';
import '../css/app.css';
import 'primeicons/primeicons.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

// Tema PrimeVue con la paleta de la marca (café / cuero)
const BodaPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '#f7f3ee',
            100: '#e9ded2',
            200: '#d6c0ab',
            300: '#c2a284',
            400: '#a98866',
            500: '#8f6c4b',
            600: '#6b5340',
            700: '#57432f',
            800: '#4a3525',
            900: '#3a2a1d',
            950: '#2a1f15',
            color: '{primary.800}',
            contrastColor: '#ffffff',
            hoverColor: '{primary.700}',
            activeColor: '{primary.600}',
        },
    },
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, { theme: { preset: BodaPreset, options: { darkModeSelector: false } } })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
