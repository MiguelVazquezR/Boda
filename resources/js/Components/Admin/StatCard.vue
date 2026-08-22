<script setup>
defineProps({
    icon: { type: Object, default: null },
    value: { type: [String, Number], required: true },
    label: { type: String, required: true },
    accent: { type: String, default: 'cuero' }, // cuero | olivo | dorado | mezclilla
});

const accentClasses = {
    cuero: 'text-cuero bg-cuero/5',
    olivo: 'text-olivo bg-olivo/5',
    dorado: 'text-dorado bg-dorado/5',
    mezclilla: 'text-mezclilla bg-mezclilla/5',
};
</script>

<template>
    <div class="bg-white rounded-2xl p-5 border border-cuero/10 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-2xl md:text-3xl font-bold text-cuero">{{ value }}</p>
                <p class="text-cuero/50 text-sm mt-1">{{ label }}</p>
            </div>
            <div v-if="icon" :class="[accentClasses[accent] ?? accentClasses.cuero]" class="w-10 h-10 rounded-xl flex items-center justify-center">
                <component :is="icon" class="w-5 h-5" />
            </div>
        </div>
    </div>
</template>
