import './bootstrap';
import '../css/app.css';
import 'primeicons/primeicons.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

// Tema PrimeVue con la paleta de la marca (primario azul #58B4FF / secundario coral #F7A79E)
const BodaPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '#ECF6FF',
            100: '#D6EBFF',
            200: '#B4DBFF',
            300: '#8BC9FF',
            400: '#6FBFFF',
            500: '#58B4FF',
            600: '#2E9AF0',
            700: '#1B7CD1',
            800: '#1C61A5',
            900: '#1E5286',
            950: '#16345A',
            color: '{primary.500}',
            contrastColor: '#ffffff',
            hoverColor: '{primary.600}',
            activeColor: '{primary.700}',
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
            .use(ToastService)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
