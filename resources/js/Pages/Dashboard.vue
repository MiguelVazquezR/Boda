<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import KpiCard from '@/Components/Admin/KpiCard.vue';
import ActivityFeed from '@/Components/Admin/ActivityFeed.vue';
import {
    UserGroupIcon, CheckCircleIcon, XCircleIcon, ClockIcon,
    PhotoIcon, SparklesIcon, QuestionMarkCircleIcon,
    CalendarDaysIcon, ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    kpis: Object,
    recentActivity: Array,
    adminSettings: Object,
    pendingPhotosCount: Number,
});

const countdownDays = ref(0);
let countdownTimer = null;

function updateCountdown() {
    const eventDate = props.adminSettings?.event_datetime
        ? new Date(props.adminSettings.event_datetime)
        : new Date('2026-11-14T16:00:00');
    const diff = eventDate - new Date();
    countdownDays.value = diff > 0 ? Math.floor(diff / (1000 * 60 * 60 * 24)) : 0;
}

const total = computed(() => props.kpis?.total ?? 0);

function pct(count) {
    if (!total.value || total.value === 0) return null;
    return Math.round((count / total.value) * 100) + '%';
}

const quickLinks = [
    { label: 'Gestionar Invitados', route: 'admin.guests.index', icon: UserGroupIcon },
    { label: 'Preguntas Frecuentes', route: 'admin.faqs.index', icon: QuestionMarkCircleIcon },
    { label: 'Galería de Fotos', route: 'admin.gallery.index', icon: PhotoIcon, badge: props.pendingPhotosCount },
    { label: 'Configuración', route: 'admin.settings.edit', icon: SparklesIcon },
];

const rsvpDeadline = computed(() => {
    if (!props.adminSettings?.rsvp_deadline) return null;
    const d = new Date(props.adminSettings.rsvp_deadline);
    return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });
});

let pollTimer = null;

onMounted(() => {
    updateCountdown();
    countdownTimer = setInterval(updateCountdown, 60000);

    pollTimer = setInterval(() => {
        router.reload({
            only: ['kpis', 'recentActivity', 'pendingPhotosCount'],
            preserveState: true,
            preserveScroll: true,
        });
    }, 30000);
});

onUnmounted(() => {
    if (countdownTimer) clearInterval(countdownTimer);
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-slab text-xl text-cuero leading-tight">Dashboard</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="relative bg-gradient-to-r from-cuero to-cuero-light rounded-2xl p-6 md:p-10 overflow-hidden">
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-dorado/50" />
                        <div class="absolute -bottom-8 -left-8 w-32 h-32 rounded-full bg-dorado/30" />
                    </div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-white/80 text-sm uppercase tracking-widest mb-1">Panel de Administración</h1>
                            <p class="text-white font-slab text-2xl md:text-3xl">¡Todo listo para el gran día!</p>
                        </div>
                        <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3">
                            <CalendarDaysIcon class="w-5 h-5 text-dorado" />
                            <div>
                                <p class="text-white/60 text-xs">Faltan</p>
                                <p class="text-white font-slab text-3xl font-bold leading-none">{{ countdownDays }}</p>
                                <p class="text-white/60 text-xs">días para la boda</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 -mt-12 relative z-20 px-2">
                    <KpiCard :icon="UserGroupIcon" label="Total de invitados" :value="kpis?.total ?? 0" accent-color="cuero" />
                    <KpiCard :icon="CheckCircleIcon" label="Confirmaron" :value="kpis?.confirmed ?? 0" accent-color="olivo" :percentage="pct(kpis?.confirmed)" />
                    <KpiCard :icon="XCircleIcon" label="No asistirán" :value="kpis?.declined ?? 0" accent-color="red" :percentage="pct(kpis?.declined)" />
                    <KpiCard :icon="ClockIcon" label="Pendientes" :value="kpis?.pending ?? 0" accent-color="dorado" :percentage="pct(kpis?.pending)" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <ActivityFeed :activities="recentActivity" />
                    </div>
                    <div class="space-y-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-cuero/10 overflow-hidden">
                            <div class="px-5 py-4 border-b border-cuero/5">
                                <h2 class="font-slab text-lg text-cuero">Accesos Rápidos</h2>
                            </div>
                            <div class="p-3 space-y-1">
                                <Link v-for="link in quickLinks" :key="link.label" :href="route(link.route)"
                                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-cuero/70 hover:text-cuero hover:bg-arena transition-colors group">
                                    <component :is="link.icon" class="w-5 h-5 text-cuero/40 group-hover:text-dorado transition-colors" />
                                    <span class="flex-1">{{ link.label }}</span>
                                    <span v-if="link.badge" class="bg-dorado text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ link.badge }}</span>
                                    <ArrowRightIcon class="w-4 h-4 text-cuero/20 group-hover:text-dorado transition-colors" />
                                </Link>
                            </div>
                        </div>

                        <div v-if="rsvpDeadline" class="bg-gradient-to-br from-dorado/5 to-dorado/10 rounded-2xl border border-dorado/20 p-5">
                            <p class="text-xs uppercase tracking-wider text-cuero/50 mb-1">Fecha límite RSVP</p>
                            <p class="font-slab text-xl text-cuero">{{ rsvpDeadline }}</p>
                            <p class="text-sm text-cuero/50 mt-2">Los invitados tienen hasta esta fecha para confirmar su asistencia.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
