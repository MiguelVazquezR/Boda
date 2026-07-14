<script setup>
import DressCodeItem from './DressCodeItem.vue';

const props = defineProps({
    items: { type: Array, required: true },
});
</script>

<template>
    <div class="grid grid-cols-2 gap-3">
        <DressCodeItem
            v-for="(item, idx) in items"
            :key="idx"
            :image-url="item.imageUrl"
            :description="item.description"
            :label="item.label"
            :fallback-icon="item.fallbackIcon"
        />
    </div>
</template>
