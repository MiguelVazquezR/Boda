<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    icon: { type: Object, default: null },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    ctaLabel: { type: String, default: '' },
    ctaLink: { type: String, default: '' },
});
</script>

<template>
    <div class="text-center py-16 px-4">
        <div v-if="icon" class="w-16 h-16 bg-cuero/5 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <component :is="icon" class="w-8 h-8 text-cuero/30" />
        </div>
        <h3 class="font-slab text-lg text-cuero mb-2">{{ title }}</h3>
        <p class="text-cuero/50 text-sm max-w-md mx-auto mb-6">{{ description }}</p>
        <Link v-if="ctaLabel && ctaLink" :href="ctaLink"
            class="inline-flex items-center gap-2 bg-mezclilla hover:bg-cuero text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
            {{ ctaLabel }}
        </Link>
    </div>
</template>
