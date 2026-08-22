<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    accentColor: { type: String, default: 'cuero' },
    percentage: { type: String, default: null },
    total: { type: Number, default: null },
});
</script>

<template>
    <div class="bg-white rounded-2xl shadow-lg p-5 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
        <div class="flex items-start justify-between mb-3">
            <div :class="[
                'w-10 h-10 rounded-full flex items-center justify-center',
                accentColor === 'olivo' ? 'bg-olivo/10' :
                accentColor === 'red' ? 'bg-red-50' :
                accentColor === 'dorado' ? 'bg-dorado/10' :
                'bg-cuero/10',
            ]">
                <component
                    :is="icon"
                    :class="[
                        'w-5 h-5',
                        accentColor === 'olivo' ? 'text-olivo' :
                        accentColor === 'red' ? 'text-red-600' :
                        accentColor === 'dorado' ? 'text-dorado' :
                        'text-cuero',
                    ]"
                />
            </div>
            <span v-if="percentage !== null" class="text-xs font-medium text-cuero/40">
                {{ percentage }}
            </span>
        </div>
        <p class="text-3xl font-slab font-bold text-cuero">{{ value }}</p>
        <p class="text-sm text-cuero/60 mt-0.5">{{ label }}</p>
    </div>
</template>
