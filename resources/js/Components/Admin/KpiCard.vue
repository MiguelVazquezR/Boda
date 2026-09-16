<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    accentColor: { type: String, default: 'tinta' },
    percentage: { type: String, default: null },
    total: { type: Number, default: null },
});
</script>

<template>
    <div class="bg-white rounded-2xl shadow-lg p-5 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
        <div class="flex items-start justify-between mb-3">
            <div :class="[
                'w-10 h-10 rounded-full flex items-center justify-center',
                accentColor === 'secondary' ? 'bg-secondary/10' :
                accentColor === 'red' ? 'bg-red-50' :
                accentColor === 'primary' ? 'bg-primary/10' :
                'bg-tinta/10',
            ]">
                <component
                    :is="icon"
                    :class="[
                        'w-5 h-5',
                        accentColor === 'secondary' ? 'text-secondary' :
                        accentColor === 'red' ? 'text-red-600' :
                        accentColor === 'primary' ? 'text-primary' :
                        'text-tinta',
                    ]"
                />
            </div>
            <span v-if="percentage !== null" class="text-xs font-medium text-tinta/40">
                {{ percentage }}
            </span>
        </div>
        <p class="text-3xl font-slab font-bold text-tinta">{{ value }}</p>
        <p class="text-sm text-tinta/60 mt-0.5">{{ label }}</p>
    </div>
</template>
