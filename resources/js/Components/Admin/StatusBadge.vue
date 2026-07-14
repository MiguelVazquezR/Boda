<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    variant: { type: String, default: 'guest' }, // 'guest' | 'gallery'
});

const config = {
    guest: {
        confirmed: { bg: 'bg-olivo/10', text: 'text-olivo', dot: 'bg-olivo', label: 'Confirmado' },
        pending: { bg: 'bg-dorado/10', text: 'text-dorado', dot: 'bg-dorado', label: 'Pendiente' },
        declined: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', label: 'No asistirá' },
    },
    gallery: {
        approved: { bg: 'bg-olivo/10', text: 'text-olivo', dot: 'bg-olivo', label: 'Aprobada' },
        pending: { bg: 'bg-dorado/10', text: 'text-dorado', dot: 'bg-dorado', label: 'Pendiente' },
        rejected: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', label: 'Rechazada' },
    },
};

const style = computed(() => config[props.variant]?.[props.status] ?? config.guest.pending);
</script>

<template>
    <span :class="[style.bg, style.text]" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium">
        <span :class="style.dot" class="w-1.5 h-1.5 rounded-full"></span>
        {{ style.label }}
    </span>
</template>
