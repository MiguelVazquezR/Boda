<script setup>
import { useRelativeTime } from '@/Composables/useRelativeTime';
import { computed, toRef } from 'vue';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    guest: { type: Object, required: true },
});

const isConfirmed = computed(() => props.guest.rsvp_status === 'confirmed');

const respondedAt = computed(() => props.guest.rsvp_responded_at);
const { text: relativeTimeText } = useRelativeTime(respondedAt);
</script>

<template>
    <div class="flex items-start gap-3 py-3">
        <!-- Timeline dot + line -->
        <div class="flex flex-col items-center">
            <div :class="[
                'w-3 h-3 rounded-full flex-shrink-0 mt-1.5',
                isConfirmed ? 'bg-olivo' : 'bg-red-500',
            ]" />
            <div class="w-px h-full bg-cuero/10 mt-1" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0 pb-1">
            <p class="text-sm text-cuero">
                <span class="font-semibold">{{ guest.full_name }}</span>
                <template v-if="isConfirmed">
                    confirmó su asistencia
                </template>
                <template v-else>
                    no podrá asistir
                </template>
            </p>
            <p v-if="isConfirmed && guest.rsvp_message" class="text-xs text-cuero/40 italic mt-0.5 line-clamp-1">
                "{{ guest.rsvp_message }}"
            </p>
            <p class="text-xs text-cuero/40 mt-0.5">{{ relativeTimeText }}</p>
        </div>

        <!-- Status icon -->
        <component
            :is="isConfirmed ? CheckCircleIcon : XCircleIcon"
            :class="[
                'w-4 h-4 flex-shrink-0 mt-1',
                isConfirmed ? 'text-olivo' : 'text-red-400',
            ]"
        />
    </div>
</template>
