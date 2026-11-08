<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SeatPlanViewer from '@/Components/FloorPlan/SeatPlanViewer.vue';

defineProps({
    invitation: Object,
    plan: Object,
    coupleNames: String,
});
</script>

<template>
    <Head :title="`Tu mesa · ${coupleNames}`" />

    <div class="min-h-screen bg-niebla">
        <header class="border-b border-tinta/10 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.3em] text-secondary">Encuentra tu mesa</p>
                    <h1 class="font-script text-2xl leading-tight text-tinta md:text-3xl">Tu lugar en el salón</h1>
                </div>
                <Link :href="route('home')"
                    class="rounded-full border border-tinta/20 px-4 py-2 text-xs font-medium text-tinta/70 transition-colors hover:bg-niebla">
                    Ir al sitio
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <div class="rounded-3xl border border-tinta/10 bg-white p-5 shadow-sm md:p-8">
                <p class="text-xs text-tinta/50">Invitación de</p>
                <h2 class="font-slab text-xl text-tinta">{{ invitation.display_name }}</h2>

                <template v-if="plan">
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span v-for="member in invitation.members" :key="member.id"
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium"
                            :class="member.table_group ? 'bg-primary/10 text-primary' : 'bg-tinta/5 text-tinta/50'">
                            {{ member.full_name }}
                            <template v-if="member.table_group">
                                · Mesa {{ member.table_group }}<template v-if="member.seat_number">, asiento {{ member.seat_number }}</template>
                            </template>
                            <template v-else>· Sin mesa asignada</template>
                        </span>
                    </div>

                    <div class="mt-5">
                        <SeatPlanViewer
                            :venue="plan.venue"
                            :tables="plan.tables"
                            :elements="plan.elements"
                            :seats="plan.seats"
                            :highlight-tables="plan.highlight_tables"
                        />
                    </div>
                </template>

                <div v-else class="py-12 text-center">
                    <p class="font-slab text-lg text-tinta">Aún estamos acomodando las mesas</p>
                    <p class="mt-2 text-sm text-tinta/60">
                        Vuelve a abrir este enlace en unos días para ver tu mesa y tu asiento.
                    </p>
                </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    <Link :href="invitation.rsvp_url"
                        class="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-primary-dark">
                        Confirmar asistencia
                    </Link>
                    <Link :href="route('home') + '#mesas'"
                        class="inline-flex items-center gap-2 rounded-full border border-tinta/20 px-5 py-2.5 text-sm font-medium text-tinta/70 transition-colors hover:bg-niebla">
                        Encuentra tu mesa
                    </Link>
                </div>
            </div>

            <p class="mt-4 text-center text-xs text-tinta/40">{{ coupleNames }}</p>
        </main>
    </div>
</template>
