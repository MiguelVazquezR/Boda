<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Popover from 'primevue/popover';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import StatCard from '@/Components/Admin/StatCard.vue';
import TablesManagerModal from './Partials/TablesManagerModal.vue';
import {
    TableCellsIcon,
    UserGroupIcon,
    CheckCircleIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    InformationCircleIcon,
    CheckIcon,
    PlusIcon,
    Cog6ToothIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    guests: Array,
    tables: Array,
    stats: Object,
});

const page = usePage();
const toast = useToast();

// ── Avisos de la vista (Toast de PrimeVue) ──
// Los movimientos (asignar mesa y crear, renombrar o eliminar mesas)
// regresan como flash desde el backend y se muestran como aviso flotante.
watch(() => page.props.flash?.success, (message) => notify('success', message));
watch(() => page.props.flash?.error, (message) => notify('error', message));

function notify(severity, message) {
    if (!message) return;

    toast.add({
        severity,
        summary: severity === 'success' ? 'Listo' : 'Ocurrió un problema',
        detail: message,
        life: severity === 'success' ? 3500 : 5000,
    });
}

// ── Gestor de mesas (crear, renombrar y eliminar) ──
const showTablesModal = ref(false);

// ── Búsqueda y filtros de la tabla de invitados (client-side) ──
const search = ref('');
const groupFilter = ref(null);

function normalizeText(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

const groupOptions = computed(() => {
    const names = [...new Set(props.guests.map((guest) => guest.group?.name).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'es'));

    return names.map((name) => ({ label: name, value: name }));
});

const filteredGuests = computed(() => {
    const query = normalizeText(search.value);

    return props.guests.filter((guest) => {
        if (groupFilter.value && (guest.group?.name ?? null) !== groupFilter.value) {
            return false;
        }

        if (!query) return true;

        return normalizeText(`${guest.full_name} ${guest.table_group ?? ''} ${guest.group?.name ?? ''}`).includes(query);
    });
});

const hasFilters = computed(() => search.value !== '' || groupFilter.value !== null);

function clearFilters() {
    search.value = '';
    groupFilter.value = null;
}

// ── Asignación masiva de mesa (desde el Popover) ──
const selectedGuests = ref([]);
const mesaPopover = ref(null);
const mesaSearch = ref('');
const form = useForm({ guest_ids: [], table_group: '' });

const hasSelection = computed(() => selectedGuests.value.length > 0);

/** Seleccionados que ya tienen mesa: se moverán a la que se elija. */
const guestsWithTable = computed(() =>
    selectedGuests.value.filter((guest) => !!guest.table_group).length,
);

/** Mesa que comparten todos los seleccionados (null si no comparten o no tienen). */
const currentTable = computed(() => {
    const tables = new Set(selectedGuests.value.map((guest) => guest.table_group ?? null));

    return tables.size === 1 ? [...tables][0] : null;
});

/** Mesas disponibles con su total de invitados (catálogo + mesas ya usadas). */
const tableOptions = computed(() => {
    const counts = new Map();

    props.guests.forEach((guest) => {
        if (guest.table_group) {
            counts.set(guest.table_group, (counts.get(guest.table_group) ?? 0) + 1);
        }
    });

    const names = new Set([...props.tables.map((table) => table.name), ...counts.keys()]);

    return [...names]
        .sort((a, b) => a.localeCompare(b, 'es', { numeric: true }))
        .map((name) => ({ name, total: counts.get(name) ?? 0 }));
});

/** Mesas filtradas por la búsqueda del popover. */
const filteredTableOptions = computed(() => {
    const query = normalizeText(mesaSearch.value);

    if (!query) return tableOptions.value;

    return tableOptions.value.filter((option) => normalizeText(option.name).includes(query));
});

/** Cuántos de los invitados seleccionados ya están en esa mesa. */
function selectedInTable(name) {
    return selectedGuests.value.filter((guest) => guest.table_group === name).length;
}

/** true si la mesa ya es la de todos los invitados seleccionados. */
function isCurrentTable(name) {
    return currentTable.value === name;
}

function clearSelection() {
    selectedGuests.value = [];
}

function warn(summary, detail) {
    toast.add({ severity: 'warn', summary, detail, life: 3500 });
}

/** Abre la lista de mesas para asignar la elegida a los invitados marcados. */
function openMesaPopover(event) {
    if (!hasSelection.value) {
        warn('Sin invitados seleccionados', 'Marca al menos un invitado en la tabla.');
        return;
    }

    mesaSearch.value = '';
    mesaPopover.value?.toggle(event);
}

/** Abre el gestor de mesas (crear, renombrar, eliminar). */
function openTablesManager() {
    mesaPopover.value?.hide();
    showTablesModal.value = true;
}

/** Asigna la mesa elegida en el popover a los invitados marcados. */
function assignTable(name) {
    if (!hasSelection.value || !name) return;

    form.guest_ids = selectedGuests.value.map((guest) => guest.id);
    form.table_group = name;

    form.post(route('admin.tables.assign'), {
        preserveScroll: true,
        onSuccess: () => {
            mesaPopover.value?.hide();
            clearSelection();
        },
        onError: (errors) => notify('error', Object.values(errors)[0] ?? 'No se pudo asignar la mesa.'),
    });
}
</script>

<template>
    <AppLayout title="Mesas">
        <template #header>
            <h2 class="font-slab text-xl text-tinta leading-tight">Mesas</h2>
        </template>

        <div class="py-6">
            <div class="max-w-screen-2xl mx-auto px-2 sm:px-3 lg:px-4">
                <!-- KPIs -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <StatCard :icon="TableCellsIcon" :value="stats?.tables ?? 0" label="Mesas" accent="primary">
                        <template #action>
                            <button
                                type="button"
                                @click="showTablesModal = true"
                                class="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-xl border border-primary/30 bg-primary/5 px-3 py-2 text-xs font-semibold text-primary-dark hover:bg-primary/10 hover:border-primary/40 transition-colors"
                            >
                                <Cog6ToothIcon class="w-4 h-4" />
                                Gestionar mesas
                            </button>
                        </template>
                    </StatCard>
                    <StatCard :icon="CheckCircleIcon" :value="stats?.assigned ?? 0" label="Invitados con mesa" accent="secondary" />
                    <StatCard :icon="UserGroupIcon" :value="stats?.unassigned ?? 0" label="Invitados sin mesa" accent="tinta" />
                </div>

                <!-- Sin invitados -->
                <div v-if="!guests || guests.length === 0" class="bg-white rounded-2xl border border-tinta/10 shadow-sm">
                    <EmptyState
                        :icon="UserGroupIcon"
                        title="Sin invitados"
                        description="Primero agrega o importa tus invitados; después podrás repartirlos por mesas."
                        cta-label="Ir a Invitados"
                        :cta-link="route('admin.guests.index')"
                    />
                </div>

                <template v-else>
                    <!-- ── Instrucciones ── -->
                    <div class="bg-primary/5 border border-primary/15 rounded-2xl p-5 mb-6">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 bg-primary/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <InformationCircleIcon class="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <h3 class="font-slab text-base text-tinta">Así se asignan las mesas</h3>
                                <p class="text-sm text-tinta/70 mt-1">
                                    Crear mesas →
                                    <span class="font-medium">Seleccionar invitados de la tabla</span> →
                                    clic en «Asignar mesa».
                                </p>
                                <ul class="mt-3 space-y-1.5 text-sm text-tinta/60">
                                    <li class="flex items-start gap-2">
                                        <span class="w-5 h-5 rounded-full bg-primary/15 text-primary-dark text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                        <span>Crea las mesas con el botón <span class="font-medium text-tinta">Gestionar mesas</span>, en la tarjeta «Mesas».</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-5 h-5 rounded-full bg-primary/15 text-primary-dark text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                        <span>Marca en la tabla a los invitados de esa mesa (puedes buscarlos o filtrarlos por grupo).</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="w-5 h-5 rounded-full bg-primary/15 text-primary-dark text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                        <span>Haz clic en «Asignar mesa» (arriba de la tabla) y elige la mesa en la lista.</span>
                                    </li>
                                </ul>
                                <p class="text-xs text-tinta/50 mt-3">
                                    Si uno o varios invitados ya tenían mesa, se moverán a la última mesa asignada.
                                    El botón «Asignar mesa» se habilita al marcar invitados en la tabla.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ── Tabla de invitados ── -->
                    <div class="bg-white rounded-2xl border border-tinta/10 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-tinta/5 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="font-slab text-lg text-tinta">Invitados</h3>
                                <span class="text-sm text-tinta/50">
                                    Total: <strong class="text-tinta">{{ guests.length }}</strong>
                                    <span v-if="hasFilters"> · {{ filteredGuests.length }} con los filtros aplicados</span>
                                </span>
                                <span
                                    v-if="hasSelection"
                                    class="inline-flex items-center gap-1.5 bg-primary/10 text-primary-dark text-xs font-semibold px-3 py-1 rounded-full"
                                >
                                    <CheckCircleIcon class="w-3.5 h-3.5" />
                                    {{ selectedGuests.length }} seleccionado(s)
                                    <button
                                        type="button"
                                        @click="clearSelection"
                                        class="hover:text-tinta transition-colors"
                                        title="Limpiar selección"
                                    >
                                        <XMarkIcon class="w-3.5 h-3.5" />
                                    </button>
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <Select
                                    v-model="groupFilter"
                                    :options="groupOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Todos los grupos"
                                    filter
                                    showClear
                                    class="!w-44"
                                />
                                <div class="relative flex-1 min-w-52">
                                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-tinta/30 z-10" />
                                    <InputText v-model="search" placeholder="Buscar invitado o mesa..." class="!w-full !pl-9" />
                                </div>
                                <SecondaryButton v-if="hasFilters" type="button" @click="clearFilters" class="whitespace-nowrap">
                                    Limpiar
                                </SecondaryButton>
                                <PrimaryButton
                                    type="button"
                                    @click="openMesaPopover"
                                    :disabled="!hasSelection || form.processing"
                                    :class="{ 'opacity-50': !hasSelection || form.processing }"
                                    class="flex items-center gap-1.5 whitespace-nowrap"
                                    :title="hasSelection
                                        ? 'Elegir la mesa de los invitados seleccionados'
                                        : 'Marca invitados en la tabla para asignarles mesa'"
                                >
                                    <TableCellsIcon class="w-4 h-4" />
                                    {{ form.processing ? 'Guardando...' : 'Asignar mesa' }}
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Popover: elegir la mesa de los invitados seleccionados -->
                        <Popover ref="mesaPopover">
                            <div class="w-80 max-w-[calc(100vw-2rem)]">
                                <div class="px-4 pt-3.5 pb-3 border-b border-tinta/10">
                                    <p class="font-slab text-sm text-tinta">
                                        Asignar mesa a {{ selectedGuests.length }} invitado(s)
                                    </p>
                                    <p class="text-xs text-tinta/50 mt-0.5">
                                        {{ guestsWithTable > 0
                                            ? `${guestsWithTable} ya tienen mesa y se moverán a la que elijas.`
                                            : 'Elige la mesa en la que se sentarán.' }}
                                    </p>
                                </div>

                                <div v-if="tableOptions.length" class="p-3 border-b border-tinta/10">
                                    <InputText v-model="mesaSearch" placeholder="Buscar mesa..." class="!w-full !text-sm" />
                                </div>

                                <ul v-if="filteredTableOptions.length" class="max-h-64 overflow-y-auto py-1">
                                    <li v-for="option in filteredTableOptions" :key="option.name">
                                        <button
                                            type="button"
                                            @click="assignTable(option.name)"
                                            :disabled="form.processing"
                                            class="w-full px-4 py-2.5 text-left transition-colors hover:bg-primary/5 disabled:opacity-50"
                                        >
                                            <span class="flex items-center gap-1.5">
                                                <span class="font-medium text-tinta text-sm truncate">{{ option.name }}</span>
                                                <CheckIcon v-if="isCurrentTable(option.name)" class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                            </span>
                                            <span class="block text-xs text-tinta/50">
                                                {{ option.total }} invitado(s)
                                                <span v-if="selectedInTable(option.name)">
                                                    · {{ selectedInTable(option.name) }} de los seleccionados
                                                </span>
                                            </span>
                                        </button>
                                    </li>
                                </ul>
                                <p v-else class="px-4 py-6 text-center text-sm text-tinta/40 italic">
                                    {{ tableOptions.length ? 'No hay mesas con ese nombre.' : 'Todavía no hay mesas creadas.' }}
                                </p>

                                <div v-if="!tableOptions.length" class="px-3 pb-3">
                                    <SecondaryButton
                                        type="button"
                                        @click="openTablesManager"
                                        class="w-full flex items-center justify-center gap-1.5"
                                    >
                                        <PlusIcon class="w-4 h-4" />
                                        Crear mesas
                                    </SecondaryButton>
                                </div>
                            </div>
                        </Popover>

                        <DataTable
                            v-model:selection="selectedGuests"
                            :value="filteredGuests"
                            dataKey="id"
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 25, 50, 100]"
                            stripedRows
                            :paginatorTemplate="'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport'"
                            :currentPageReportTemplate="'Mostrando {first} a {last} de {totalRecords} invitados'"
                        >
                            <template #empty>
                                <p class="py-6 text-center text-tinta/50 text-sm">
                                    No se encontraron invitados con esa búsqueda.
                                </p>
                            </template>

                            <Column selectionMode="multiple" headerStyle="width: 3rem" :exportable="false" />

                            <Column field="full_name" header="Nombre" sortable style="min-width: 240px">
                                <template #body="{ data }">
                                    <span class="font-medium text-tinta">{{ data.full_name }}</span>
                                </template>
                            </Column>

                            <Column field="group.name" header="Grupo">
                                <template #body="{ data }">
                                    <span class="text-tinta/50">{{ data.group?.name || '—' }}</span>
                                </template>
                            </Column>

                            <Column field="rsvp_status" header="Estado">
                                <template #body="{ data }">
                                    <StatusBadge :status="data.rsvp_status" variant="guest" />
                                </template>
                            </Column>

                            <Column field="table_group" header="Mesa" sortable style="min-width: 110px">
                                <template #body="{ data }">
                                    <span :class="data.table_group ? 'text-tinta font-medium' : 'text-tinta/30 italic'">
                                        {{ data.table_group || 'Sin asignar' }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </div>
        </div>

        <!-- ── Modal: crear, renombrar y eliminar mesas ── -->
        <TablesManagerModal
            :show="showTablesModal"
            :tables="tables"
            @close="showTablesModal = false"
        />
    </AppLayout>
</template>
