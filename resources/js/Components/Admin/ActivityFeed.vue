<script setup>
import ActivityFeedItem from '@/Components/Admin/ActivityFeedItem.vue';
import { ClockIcon } from '@heroicons/vue/24/outline';

defineProps({
    activities: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-tinta/10 overflow-hidden">
        <div class="px-5 py-4 border-b border-tinta/5">
            <h2 class="font-slab text-lg text-tinta">Actividad Reciente</h2>
        </div>

        <div v-if="activities && activities.length > 0" class="px-5 py-2 divide-y divide-tinta/5">
            <ActivityFeedItem
                v-for="activity in activities"
                :key="activity.id"
                :guest="activity"
            />
        </div>

        <div v-else class="px-5 py-10 text-center">
            <ClockIcon class="w-10 h-10 text-tinta/20 mx-auto mb-3" />
            <p class="text-tinta/50 text-sm">Aquí verás las confirmaciones en cuanto empiecen a llegar 🎉</p>
        </div>
    </div>
</template>
