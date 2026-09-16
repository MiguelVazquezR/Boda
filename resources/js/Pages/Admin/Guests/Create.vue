<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import GuestForm from './Partials/GuestForm.vue';

defineProps({
    groups: Array,
});
</script>

<template>
    <AppLayout title="Nuevo Invitado">
        <template #header>
            <h2 class="font-slab text-xl text-tinta leading-tight">Nuevo Invitado</h2>
        </template>

        <div class="py-6">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <GuestForm :guest="null" :groups="groups" />
            </div>
        </div>
    </AppLayout>
</template>
