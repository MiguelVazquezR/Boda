<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import DialogModal from '@/Components/DialogModal.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import GuestDetailModal from './Partials/GuestDetailModal.vue';
import { PlusIcon, PencilIcon, TrashIcon, UserGroupIcon, ArrowUpTrayIcon, DocumentTextIcon, MagnifyingGlassIcon, EyeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    guests: Array,
    filters: Object,
    statusCounts: Object,
});

// ── Filtros de la tabla (client-side: aplican a TODOS los registros, no solo a la página actual) ──
const globalFilter = ref(props.filters?.search ?? '');

const filters = ref({
    rsvp_status: { value: props.filters?.status ?? null, matchMode: 'equals' },
    gender: { value: null, matchMode: 'equals' },
    'group.name': { value: null, matchMode: 'equals' },
    origin: { value: null, matchMode: 'equals' },
    city: { value: null, matchMode: 'in' },
    age: { value: null, matchMode: 'equals' },
    table_group: { value: null, matchMode: 'equals' },
});

function clearFilters() {
    globalFilter.value = '';
    filters.value = {
        rsvp_status: { value: null, matchMode: 'equals' },
        gender: { value: null, matchMode: 'equals' },
        'group.name': { value: null, matchMode: 'equals' },
        origin: { value: null, matchMode: 'equals' },
        city: { value: null, matchMode: 'in' },
        age: { value: null, matchMode: 'equals' },
        table_group: { value: null, matchMode: 'equals' },
    };
}

// ── Opciones de filtros ──
const tableStatusOptions = [
    { label: 'Pendiente', value: 'pending' },
    { label: 'Confirmado', value: 'confirmed' },
    { label: 'No Asistirá', value: 'declined' },
];

const genderOptions = [
    { label: 'Femenino', value: 'femenino' },
    { label: 'Masculino', value: 'masculino' },
];

const originOptions = [
    { label: 'Local', value: 'local' },
    { label: 'Foráneo', value: 'foraneo' },
];

const groupOptions = computed(() => {
    const names = [...new Set(props.guests.map((g) => g.group?.name).filter(Boolean))].sort();
    return names.map((n) => ({ label: n, value: n }));
});

const cityOptions = computed(() => {
    const cities = [...new Set(props.guests.map((g) => g.city).filter(Boolean))].sort();
    return cities.map((c) => ({ label: c, value: c }));
});

const ageOptions = computed(() => {
    const ages = [...new Set(props.guests.map((g) => g.age).filter((a) => a !== null && a !== undefined))].sort((a, b) => a - b);
    return ages.map((a) => ({ label: String(a), value: a }));
});

const tableGroupOptions = computed(() => {
    const tables = [...new Set(props.guests.map((g) => g.table_group).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'es', { numeric: true }));
    return tables.map((t) => ({ label: t, value: t }));
});

// ── Formato ──
function formatGender(g) {
    if (!g) return '—';
    return g.charAt(0).toUpperCase() + g.slice(1);
}

function formatOrigin(o) {
    if (o === 'foraneo') return 'Foráneo';
    if (o === 'local') return 'Local';
    return '—';
}

// ── Navegación (Create / Edit en páginas separadas) ──
function goToCreate() {
    router.get(route('admin.guests.create'));
}

function goToEdit(guest) {
    router.get(route('admin.guests.edit', guest.id));
}

// ── Import CSV Modal ──
const showImportModal = ref(false);
const importForm = useForm({ csv_file: null });
const importResult = ref(null);
const importResultIsError = ref(false);
const csvInput = ref(null);

function onCsvFile(e) {
    importForm.csv_file = e.target.files[0];
    importResult.value = null;
    importResultIsError.value = false;
}

function doImport() {
    importForm.post(route('admin.guests.import'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: (page) => {
            const flash = page.props.flash ?? {};
            importResultIsError.value = !!flash.error;
            importResult.value = flash.error || flash.success || 'Importación completada.';
            // Permitir reimportar otro archivo
            if (csvInput.value) csvInput.value.value = '';
            importForm.csv_file = null;
        },
    });
}

// ── Delete ──
const deleteGuest = ref(null);
const deleteForm = useForm({});
function confirmDelete(guest) { deleteGuest.value = guest; }
function doDelete() {
    if (!deleteGuest.value) return;
    deleteForm.delete(route('admin.guests.destroy', deleteGuest.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteGuest.value = null; },
    });
}

// ── Detail Modal ──
const viewingGuest = ref(null);
function openDetail(guest) { viewingGuest.value = guest; }
</script>

<template>
    <AppLayout title="Invitados">
        <template #header>
            <h2 class="font-slab text-xl text-cuero leading-tight">Invitados</h2>
        </template>

        <div class="py-6">
            <div class="max-w-screen-2xl mx-auto px-2 sm:px-3 lg:px-4">
                <div class="max-w-screen-2xl mx-auto">
                    <!-- Toolbar: total + búsqueda + acciones -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm text-cuero/60">
                                Total: <strong class="text-cuero">{{ guests.length }}</strong> invitados
                            </span>
                            <Button
                                label="Limpiar filtros"
                                severity="secondary"
                                outlined
                                size="small"
                                @click="clearFilters"
                            />
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative flex-1 min-w-56">
                                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-cuero/30 z-10" />
                                <InputText
                                    v-model="globalFilter"
                                    placeholder="Buscar invitado..."
                                    class="!w-full !pl-9"
                                />
                            </div>
                            <SecondaryButton @click="showImportModal = true" class="flex items-center gap-2 whitespace-nowrap">
                                <ArrowUpTrayIcon class="w-4 h-4" />
                                Importar CSV
                            </SecondaryButton>
                            <PrimaryButton @click="goToCreate" class="flex items-center gap-2 whitespace-nowrap">
                                <PlusIcon class="w-4 h-4" />
                                Agregar Invitado
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Tabla con filtros por columna y paginación -->
                    <div v-if="guests.length > 0" class="bg-white rounded-2xl border border-cuero/10 shadow-sm overflow-hidden">
                        <DataTable
                            v-model:filters="filters"
                            v-model:globalFilter="globalFilter"
                            :value="guests"
                            :globalFilterFields="['full_name', 'phone', 'city', 'state', 'table_group', 'group.name']"
                            filterDisplay="row"
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 25, 50, 100]"
                            dataKey="id"
                            sortField="full_name"
                            :sortOrder="1"
                            stripedRows
                            :paginatorTemplate="'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport'"
                            :currentPageReportTemplate="'Mostrando {first} a {last} de {totalRecords} invitados'"
                        >
                            <template #empty>
                                <p class="py-6 text-center text-cuero/50 text-sm">
                                    No se encontraron invitados con los filtros aplicados.
                                </p>
                            </template>

                            <Column field="full_name" header="Nombre" sortable :showFilterMenu="false" style="min-width: 260px">
                                <template #body="{ data }">
                                    <span class="font-medium text-cuero">{{ data.full_name }}</span>
                                </template>
                            </Column>

                            <Column field="age" header="Edad" sortable :showFilterMenu="false" filterMatchMode="equals">
                                <template #body="{ data }">
                                    <span class="text-cuero/70">{{ data.age ?? '—' }}</span>
                                </template>
                                <template #filter>
                                    <Select v-model="filters['age'].value" :options="ageOptions" optionLabel="label" optionValue="value" placeholder="Edad" showClear class="!w-full" />
                                </template>
                            </Column>

                            <Column field="gender" header="Género" :showFilterMenu="false">
                                <template #body="{ data }">
                                    <span class="text-cuero/70">{{ formatGender(data.gender) }}</span>
                                </template>
                                <template #filter>
                                    <Select v-model="filters['gender'].value" :options="genderOptions" optionLabel="label" optionValue="value" placeholder="Género" showClear class="!w-full" />
                                </template>
                            </Column>

                            <Column field="group.name" header="Grupo" :showFilterMenu="false">
                                <template #body="{ data }">
                                    <span class="text-cuero/50">{{ data.group?.name || data.table_group || '—' }}</span>
                                </template>
                                <template #filter>
                                    <Select v-model="filters['group.name'].value" :options="groupOptions" optionLabel="label" optionValue="value" placeholder="Grupo" showClear class="!w-full" />
                                </template>
                            </Column>

                            <Column field="phone" header="Celular" sortable :showFilterMenu="false">
                                <template #body="{ data }">
                                    <span class="text-cuero/70">{{ data.phone || '—' }}</span>
                                </template>
                            </Column>

                            <Column field="origin" header="Origen" :showFilterMenu="false">
                                <template #body="{ data }">
                                    <span class="text-cuero/70">{{ formatOrigin(data.origin) }}</span>
                                </template>
                                <template #filter>
                                    <Select v-model="filters['origin'].value" :options="originOptions" optionLabel="label" optionValue="value" placeholder="Origen" showClear class="!w-full" />
                                </template>
                            </Column>

                            <Column field="city" header="Ciudad" :showFilterMenu="false">
                                <template #body="{ data }">
                                    <span class="text-cuero/50">{{ data.city || '—' }}</span>
                                </template>
                                <template #filter>
                                    <MultiSelect
                                        v-model="filters['city'].value"
                                        :options="cityOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Ciudades"
                                        display="chip"
                                        :maxSelectedLabels="2"
                                        :filter="true"
                                        filterPlaceholder="Buscar ciudad..."
                                        class="!w-full"
                                    />
                                </template>
                            </Column>

                            <Column field="table_group" header="Mesa" sortable :showFilterMenu="false" filterMatchMode="equals" style="min-width: 110px">
                                <template #body="{ data }">
                                    <span class="text-cuero/70">{{ data.table_group || '—' }}</span>
                                </template>
                                <template #filter>
                                    <Select v-model="filters['table_group'].value" :options="tableGroupOptions" optionLabel="label" optionValue="value" placeholder="Mesa" showClear class="!w-full" />
                                </template>
                            </Column>

                            <Column field="rsvp_status" header="Estado" :showFilterMenu="false">
                                <template #body="{ data }">
                                    <StatusBadge :status="data.rsvp_status" variant="guest" />
                                </template>
                                <template #filter>
                                    <Select v-model="filters['rsvp_status'].value" :options="tableStatusOptions" optionLabel="label" optionValue="value" placeholder="Estado" showClear class="!w-full" />
                                </template>
                            </Column>

                            <Column header="Acciones" :exportable="false" :showFilterMenu="false">
                                <template #body="{ data }">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openDetail(data)" class="p-2 text-cuero/30 hover:text-dorado transition-colors rounded-lg hover:bg-dorado/5" title="Ver detalle">
                                            <EyeIcon class="w-4 h-4" />
                                        </button>
                                        <button @click="goToEdit(data)" class="p-2 text-cuero/30 hover:text-mezclilla transition-colors rounded-lg hover:bg-mezclilla/5" title="Editar">
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                        <ConfirmDeleteModal :message="`¿Eliminar a «${data.full_name}»? Esta acción no se puede deshacer y perderá su confirmación de asistencia si ya respondió.`" @confirm="doDelete">
                                            <template #default="{ open: openDel }">
                                                <button @click="confirmDelete(data); openDel()" class="p-2 text-cuero/30 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50" title="Eliminar">
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </template>
                                        </ConfirmDeleteModal>
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <EmptyState v-else :icon="UserGroupIcon" title="Sin invitados"
                        description="Aún no agregas invitados. Empieza agregando uno o importando tu lista desde un archivo CSV."
                        :cta-label="'Agregar Invitado'" @click="goToCreate" />

                    <!-- ── Import CSV Modal ── -->
                    <DialogModal :show="showImportModal" @close="showImportModal = false; importResult = null;">
                        <template #title>Importar Invitados (CSV)</template>
                        <template #content>
                            <div class="space-y-4">
                                <p class="text-sm text-cuero/60">
                                    El archivo debe incluir la columna <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">first_name</code> (o <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">full_name</code>). Puede incluir: <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">last_name</code>, <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">age</code>, <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">gender</code>, <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">group</code>, <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">phone</code>, <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">origin</code>, <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">state</code>, <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">city</code> y <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">table_group</code>. También acepta nombres en español (nombre, apellidos, edad, género, grupo, celular, origen, estado, ciudad, mesa).
                                </p>
                                <div class="border-2 border-dashed border-cuero/20 rounded-xl p-6 text-center">
                                    <DocumentTextIcon class="w-8 h-8 text-cuero/30 mx-auto mb-2" />
                                    <label class="cursor-pointer text-mezclilla hover:text-mezclilla-light text-sm font-medium">
                                        Seleccionar archivo CSV
                                        <input ref="csvInput" type="file" accept=".csv,.txt" class="hidden" @change="onCsvFile" />
                                    </label>
                                    <p v-if="importForm.csv_file" class="text-cuero/50 text-xs mt-2">{{ importForm.csv_file.name }}</p>
                                </div>
                                <InputError :message="importForm.errors.csv_file" />

                                <div v-if="importResult" :class="importResultIsError
                                    ? 'bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-600'
                                    : 'bg-olivo/10 border border-olivo/20 rounded-xl p-4 text-sm text-olivo'">
                                    {{ importResult }}
                                </div>
                            </div>
                        </template>
                        <template #footer>
                            <SecondaryButton @click="showImportModal = false; importResult = null;">Cerrar</SecondaryButton>
                            <PrimaryButton v-if="!importResult" @click="doImport" :disabled="!importForm.csv_file || importForm.processing" class="ms-3" :class="{ 'opacity-50': !importForm.csv_file || importForm.processing }">
                                {{ importForm.processing ? 'Importando...' : 'Importar' }}
                            </PrimaryButton>
                        </template>
                    </DialogModal>

                    <!-- ── Guest Detail Modal (componente separado) ── -->
                    <GuestDetailModal :guest="viewingGuest" @close="viewingGuest = null" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
