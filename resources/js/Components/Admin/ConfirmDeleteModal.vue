<script setup>
import { ref } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    title: { type: String, default: '¿Estás seguro?' },
    message: { type: String, required: true },
    confirmLabel: { type: String, default: 'Eliminar' },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm']);
const open = ref(false);

function confirm() { emit('confirm'); }
</script>

<template>
    <slot :open="() => open = true" :close="() => open = false" />
    <ConfirmationModal :show="open" @close="open = false">
        <template #title>{{ title }}</template>
        <template #content>
            <p class="text-tinta/70">{{ message }}</p>
        </template>
        <template #footer>
            <SecondaryButton @click="open = false" :disabled="processing">Cancelar</SecondaryButton>
            <DangerButton @click="confirm" :disabled="processing" class="ms-3" :class="{ 'opacity-25': processing }">
                {{ processing ? 'Eliminando...' : confirmLabel }}
            </DangerButton>
        </template>
    </ConfirmationModal>
</template>
