/**
 * Composable: useRelativeTime
 * Returns a human-readable relative time string (e.g. "hace 10 minutos").
 * Updates automatically every 60 seconds.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';

const UNITS = [
    { max: 60, unit: 'segundo', plural: 'segundos', div: 1 },
    { max: 3600, unit: 'minuto', plural: 'minutos', div: 60 },
    { max: 86400, unit: 'hora', plural: 'horas', div: 3600 },
    { max: 604800, unit: 'día', plural: 'días', div: 86400 },
    { max: 2592000, unit: 'semana', plural: 'semanas', div: 604800 },
    { max: Infinity, unit: 'mes', plural: 'meses', div: 2592000 },
];

function relativeTime(dateString) {
    if (!dateString) return '';

    const then = new Date(dateString).getTime();
    const now = Date.now();
    const diffSeconds = Math.floor((now - then) / 1000);

    if (diffSeconds < 10) return 'ahora mismo';
    if (diffSeconds < 60) return `hace ${diffSeconds} segundos`;

    for (const tier of UNITS) {
        const value = Math.floor(diffSeconds / tier.div);
        if (diffSeconds < tier.max) {
            return value === 1 ? `hace 1 ${tier.unit}` : `hace ${value} ${tier.plural}`;
        }
    }

    return new Date(dateString).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
}

export function useRelativeTime(dateStringOrRef) {
    const now = ref(Date.now());
    let timer = null;

    const text = computed(() => {
        // Support both raw string and ref
        const value = typeof dateStringOrRef === 'function' || dateStringOrRef?.value !== undefined
            ? dateStringOrRef.value ?? dateStringOrRef
            : dateStringOrRef;
        return relativeTime(value);
    });

    onMounted(() => {
        timer = setInterval(() => { now.value = Date.now(); }, 60000);
    });

    onUnmounted(() => {
        if (timer) clearInterval(timer);
    });

    return { text, now };
}
