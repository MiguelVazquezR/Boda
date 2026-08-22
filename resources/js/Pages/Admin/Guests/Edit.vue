<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import GuestForm from './Partials/GuestForm.vue';

defineProps({
    guest: Object,
    groups: Array,
});
</script>

<template>
    <AppLayout :title="`Editar ${guest?.full_name ?? 'Invitado'}`">
        <template #header>
            <h2 class="font-slab text-xl text-cuero leading-tight">Editar Invitado</h2>
        </template>

        <div class="py-6">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <GuestForm :guest="guest" :groups="groups" />
            </div>
        </div>
    </AppLayout>
</template>
