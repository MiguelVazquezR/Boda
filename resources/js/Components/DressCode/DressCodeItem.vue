<script setup>
const props = defineProps({
    imageUrl: { type: String, default: null },
    description: { type: String, default: '' },
    label: { type: String, required: true },
    fallbackIcon: { type: String, default: '✨' },
});
</script>

<template>
    <div class="bg-niebla-dark/50 rounded-xl overflow-hidden flex flex-col h-full">
        <!-- Image or fallback icon -->
        <div class="aspect-square relative flex items-center justify-center bg-niebla-dark/30">
            <img
                v-if="imageUrl"
                :src="imageUrl"
                :alt="label"
                class="absolute inset-0 w-full h-full object-cover"
            />
            <span
                v-else
                class="text-3xl"
                role="img"
                :aria-label="label"
            >{{ fallbackIcon }}</span>
        </div>
        <!-- Label + Description -->
        <div class="p-3 text-center flex-1 flex flex-col justify-center">
            <span class="font-medium text-tinta/80 text-sm">{{ label }}</span>
            <p
                v-if="description"
                class="text-xs text-tinta/50 mt-1"
            >{{ description }}</p>
        </div>
    </div>
</template>
