<script setup>
defineProps({
    icon: { type: Object, default: null },
    value: { type: [String, Number], required: true },
    label: { type: String, required: true },
    accent: { type: String, default: 'tinta' }, // tinta | secondary | primary
});

const accentClasses = {
    tinta: 'text-tinta bg-tinta/5',
    secondary: 'text-secondary bg-secondary/5',
    primary: 'text-primary bg-primary/5',
};
</script>

<template>
    <div class="bg-white rounded-2xl p-5 border border-tinta/10 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-2xl md:text-3xl font-bold text-tinta">{{ value }}</p>
                <p class="text-tinta/50 text-sm mt-1">{{ label }}</p>
            </div>
            <div v-if="icon" :class="[accentClasses[accent] ?? accentClasses.tinta]" class="w-10 h-10 rounded-xl flex items-center justify-center">
                <component :is="icon" class="w-5 h-5" />
            </div>
        </div>

        <!-- Acción opcional del KPI (ej. gestionar mesas) -->
        <slot name="action" />
    </div>
</template>
