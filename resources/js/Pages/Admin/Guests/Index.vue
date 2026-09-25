<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
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
import InvitationsManagerModal from './Partials/InvitationsManagerModal.vue';
import { PlusIcon, PencilIcon, TrashIcon, UserGroupIcon, ArrowUpTrayIcon, DocumentTextIcon, MagnifyingGlassIcon, EyeIcon, LinkIcon, CheckIcon, ClipboardDocumentIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    guests: Array,
    filters: Object,
    statusCounts: Object,
    // Invitaciones digitales (parejas o personas solas) con su link público
    invitations: { type: Array, default: () => [] },
    // Catálogos para la edición rápida de celdas (grupos y mesas)
    groups: { type: Array, default: () => [] },
    tables: { type: Array, default: () => [] },
});

// ── Filtros de la tabla (client-side: aplican a TODOS los registros, no solo a la página actual) ──
// En PrimeVue 5 el buscador global vive dentro del objeto `filters` (clave `global`);
// la prop `globalFilter` de versiones anteriores ya no existe en DataTable.
const searchInput = ref(props.filters?.search ?? '');

const filters = ref({
    global: { value: normalizeText(props.filters?.search ?? '') || null, matchMode: 'contains' },
    rsvp_status: { value: props.filters?.status ?? null, matchMode: 'equals' },
    gender: { value: null, matchMode: 'equals' },
    guest_group_id: { value: null, matchMode: 'equals' },
    origin: { value: null, matchMode: 'equals' },
    table_group: { value: null, matchMode: 'equals' },
    invitation_state: { value: null, matchMode: 'equals' },
});

// Cada invitado con el campo virtual `invitation_state` ('with' / 'without') que usa
// el filtro de la columna "Invitación" para ver quiénes ya tienen link y quiénes faltan.
const tableGuests = computed(() =>
    props.guests.map((guest) => ({
        ...guest,
        invitation_state: guest.invitation ? 'with' : 'without',
    })),
);

/**
 * Normaliza texto para buscar: minúsculas, sin acentos y sin caracteres especiales.
 * Coincide con el campo `search_slug` que genera el backend, así buscar "jose"
 * encuentra a "José" y buscar "garcia" encuentra a "García".
 */
function normalizeText(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

/** Se conserva el texto escrito por el usuario y se filtra por su versión normalizada. */
function onSearchInput(value) {
    searchInput.value = value ?? '';
    filters.value.global.value = normalizeText(value) || null;
}

function clearFilters() {
    searchInput.value = '';
    filters.value = {
        global: { value: null, matchMode: 'contains' },
        rsvp_status: { value: null, matchMode: 'equals' },
        gender: { value: null, matchMode: 'equals' },
        guest_group_id: { value: null, matchMode: 'equals' },
        origin: { value: null, matchMode: 'equals' },
        table_group: { value: null, matchMode: 'equals' },
        invitation_state: { value: null, matchMode: 'equals' },
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

// Columna "Invitación": quiénes ya tienen su link digital y a quiénes les falta.
const invitationOptions = [
    { label: 'Con invitación', value: 'with' },
    { label: 'Sin invitación', value: 'without' },
];

/** Grupos (catálogo completo) para el filtro y la edición rápida de la tabla. */
const groupOptions = computed(() =>
    props.groups.map((group) => ({ label: group.name, value: group.id })),
);

/** Mesas para el filtro y la edición rápida: catálogo + las ya asignadas. */
const tableOptions = computed(() => {
    const names = new Set([
        ...props.tables,
        ...props.guests.map((g) => g.table_group).filter(Boolean),
    ]);

    return [...names]
        .sort((a, b) => a.localeCompare(b, 'es', { numeric: true }))
        .map((name) => ({ label: name, value: name }));
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

// ── Edición rápida de celdas (origen, celular, grupo y mesa) ──
/**
 * Guarda el cambio hecho directamente en una celda de la tabla. PrimeVue avisa
 * aquí al confirmar la edición (Enter, elegir una opción o clic fuera).
 */
function onCellEditComplete(event) {
    const { data, field, newValue } = event;

    // El celular debe tener exactamente 10 dígitos (o quedar vacío para borrarlo).
    if (field === 'phone' && newValue && String(newValue).length !== 10) {
        window.alert('El celular debe tener exactamente 10 dígitos.');
        event.preventDefault(); // mantiene la celda en edición
        return;
    }

    const value = newValue === '' || newValue === undefined ? null : newValue;

    router.patch(route('admin.guests.cell', data.id), { field, value }, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            window.alert(Object.values(errors)[0] ?? 'No se pudo guardar el cambio.');
        },
    });
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

// ── Invitaciones digitales (parejas / links) ──
const showInvitationsModal = ref(false);
// Invitados con los que se abre el modal (los seleccionados en la tabla)
const invitationMemberIds = ref([]);
// Filas seleccionadas en la tabla, para crear una invitación en un clic
const selectedGuests = ref([]);

const selectedGuestIds = computed(() => selectedGuests.value.map((guest) => guest.id));

function openInvitations(prefill = []) {
    invitationMemberIds.value = prefill;
    showInvitationsModal.value = true;
}

/** Abre el modal con los 1 o 2 invitados seleccionados en la tabla. */
function createInvitationFromSelection() {
    if (selectedGuestIds.value.length < 1 || selectedGuestIds.value.length > 2) return;

    openInvitations(selectedGuestIds.value);
}

function closeInvitations() {
    showInvitationsModal.value = false;
    invitationMemberIds.value = [];
    selectedGuests.value = [];
}

// ── Copiar link y marcar como enviada desde la columna «Invitación» ──
const copiedInvitationId = ref(null);
const copiedInvitationTextId = ref(null);
const invitationSentForm = useForm({ sent: false });

/**
 * Copia texto al portapapeles, con respaldo para navegadores o contextos sin
 * Clipboard API (por ejemplo, la página abierta por http:// en la red local).
 */
async function copyToClipboard(text) {
    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            // Continúa con el respaldo (textarea + execCommand).
        }
    }

    try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        const copied = document.execCommand('copy');
        document.body.removeChild(textarea);
        return copied;
    } catch {
        return false;
    }
}

/** Copia el link público de la invitación (con confirmación visual de 2s). */
async function copyInvitationLink(invitation) {
    const url = invitation.public_url;

    if (!url) {
        window.alert('El link de la invitación no está disponible en esta página. Recárgala e intenta de nuevo.');
        return;
    }

    if (!(await copyToClipboard(url))) {
        window.prompt('Copia el link de la invitación:', url);
        return;
    }

    copiedInvitationId.value = invitation.id;
    setTimeout(() => {
        if (copiedInvitationId.value === invitation.id) copiedInvitationId.value = null;
    }, 2000);
}

/**
 * Copia el texto completo de la invitación (nombres, lugares reservados y
 * links) para pegarlo en WhatsApp (con confirmación visual de 2s).
 */
async function copyInvitationMessage(invitation) {
    const text = invitation.share_message;

    // Si la página se abrió antes de generar los textos, hay que recargarla.
    if (!text) {
        window.alert('El texto de la invitación aún no está disponible en esta página. Recárgala (F5) e intenta de nuevo.');
        return;
    }

    if (!(await copyToClipboard(text))) {
        window.prompt('Copia el texto de la invitación:', text);
        return;
    }

    copiedInvitationTextId.value = invitation.id;
    setTimeout(() => {
        if (copiedInvitationTextId.value === invitation.id) copiedInvitationTextId.value = null;
    }, 2000);
}

/** Marca o desmarca la invitación como enviada sin salir de la tabla. */
function toggleInvitationSent(invitation, checked) {
    invitationSentForm.sent = checked;
    invitationSentForm.put(route('admin.invitations.sent', invitation.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

/** Tooltip del check «enviada»: fecha en que se envió, si ya está marcada. */
function sentTooltip(invitation) {
    if (!invitation.sent_at) return 'Marcar como enviada';

    const date = new Date(invitation.sent_at).toLocaleDateString('es-MX', {
        day: 'numeric',
        month: 'long',
    });

    return `Enviada el ${date}`;
}
</script>

<template>
    <AppLayout title="Invitados">
        <template #header>
            <h2 class="font-slab text-xl text-tinta leading-tight">Invitados</h2>
        </template>

        <div class="py-6">
            <div class="max-w-screen-2xl mx-auto px-2 sm:px-3 lg:px-4">
                <div class="max-w-screen-2xl mx-auto">
                    <!-- Toolbar: total + búsqueda + acciones -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm text-tinta/60">
                                Total: <strong class="text-tinta">{{ guests.length }}</strong> invitados
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
                                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-tinta/30 z-10" />
                                <InputText
                                    :model-value="searchInput"
                                    @update:model-value="onSearchInput"
                                    placeholder="Buscar invitado..."
                                    class="!w-full !pl-9"
                                />
                            </div>
                            <SecondaryButton @click="openInvitations()" class="flex items-center gap-2 whitespace-nowrap">
                                <LinkIcon class="w-4 h-4" />
                                Parejas / Links
                            </SecondaryButton>
                            <PrimaryButton
                                v-if="selectedGuestIds.length && selectedGuestIds.length <= 2"
                                @click="createInvitationFromSelection"
                                class="flex items-center gap-2 whitespace-nowrap"
                            >
                                <UserGroupIcon class="w-4 h-4" />
                                Crear invitación ({{ selectedGuestIds.length }})
                            </PrimaryButton>
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

                    <!-- Tabla con filtros por columna (sin paginación: se muestran todos los invitados) -->
                    <div v-if="guests.length > 0" class="bg-white rounded-2xl border border-tinta/10 shadow-sm overflow-hidden">
                        <DataTable
                            v-model:filters="filters"
                            v-model:selection="selectedGuests"
                            :value="tableGuests"
                            :globalFilterFields="['search_slug', 'full_name', 'phone', 'city', 'state', 'table_group', 'group.name']"
                            filterDisplay="row"
                            dataKey="id"
                            sortField="full_name"
                            :sortOrder="1"
                            stripedRows
                            editMode="cell"
                            @cell-edit-complete="onCellEditComplete"
                        >
                            <template #empty>
                                <p class="py-6 text-center text-tinta/50 text-sm">
                                    No se encontraron invitados con los filtros aplicados.
                                </p>
                            </template>

                            <Column selectionMode="multiple" :exportable="false" :showFilterMenu="false" headerStyle="width: 3rem" />

                            <Column field="full_name" header="Nombre" sortable :showFilterMenu="false" style="min-width: 260px">
                                <template #body="{ data }">
                                    <span class="font-medium text-tinta">{{ data.full_name }}</span>
                                </template>
                            </Column>

                            <Column field="gender" header="Género" :showFilterMenu="false">
                                <template #body="{ data }">
                                    <span class="text-tinta/70">{{ formatGender(data.gender) }}</span>
                                </template>
                                <template #editor="{ data, field, editorSaveCallback }">
                                    <Select
                                        v-model="data[field]"
                                        :options="genderOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Género"
                                        showClear
                                        class="!w-full"
                                        @update:model-value="editorSaveCallback"
                                    />
                                </template>
                                <template #filter>
                                    <Select v-model="filters['gender'].value" :options="genderOptions" optionLabel="label" optionValue="value" placeholder="Género" showClear class="!w-full" />
                                </template>
                            </Column>

                            <Column field="guest_group_id" header="Grupo" :showFilterMenu="false">
                                <template #body="{ data }">
                                    <span class="text-tinta/50">{{ data.group?.name || data.table_group || '—' }}</span>
                                </template>
                                <template #editor="{ data, field, editorSaveCallback }">
                                    <Select
                                        v-model="data[field]"
                                        :options="groupOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Sin grupo"
                                        showClear
                                        filter
                                        class="!w-full"
                                        @update:model-value="editorSaveCallback"
                                    />
                                </template>
                                <template #filter>
                                    <Select v-model="filters['guest_group_id'].value" :options="groupOptions" optionLabel="label" optionValue="value" placeholder="Grupo" showClear class="!w-full" />
                                </template>
                            </Column>

                            <Column field="phone" header="Celular" sortable :showFilterMenu="false">
                                <template #body="{ data }">
                                    <span class="text-tinta/70">{{ data.phone || '—' }}</span>
                                </template>
                                <template #editor="{ data, field }">
                                    <InputText
                                        :model-value="data[field]"
                                        maxlength="10"
                                        class="!w-full"
                                        @update:model-value="(value) => data[field] = String(value ?? '').replace(/\D/g, '').slice(0, 10)"
                                    />
                                </template>
                            </Column>

                            <Column field="origin" header="Origen" :showFilterMenu="false">
                                <template #body="{ data }">
                                    <span class="text-tinta/70">{{ formatOrigin(data.origin) }}</span>
                                </template>
                                <template #editor="{ data, field, editorSaveCallback }">
                                    <Select
                                        v-model="data[field]"
                                        :options="originOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Origen"
                                        showClear
                                        class="!w-full"
                                        @update:model-value="editorSaveCallback"
                                    />
                                </template>
                                <template #filter>
                                    <Select v-model="filters['origin'].value" :options="originOptions" optionLabel="label" optionValue="value" placeholder="Origen" showClear class="!w-full" />
                                </template>
                            </Column>

                            <Column field="invitation_state" header="Invitación" :showFilterMenu="false" style="min-width: 230px">
                                <template #body="{ data }">
                                    <div v-if="data.invitation" class="flex items-center gap-1.5">
                                        <span
                                            class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-medium"
                                            :class="data.invitation.sent_at ? 'bg-green-100 text-green-700' : 'bg-primary/10 text-primary'"
                                            :title="data.invitation.sent_at ? 'Invitación enviada' : 'Invitación creada, aún sin enviar'">
                                            <CheckIcon v-if="data.invitation.sent_at" class="w-3.5 h-3.5" />
                                            <LinkIcon v-else class="w-3.5 h-3.5" />
                                            {{ data.invitation.display_name }}
                                        </span>

                                        <!-- Copiar el link de la invitación -->
                                        <button
                                            @click="copyInvitationLink(data.invitation)"
                                            class="p-1.5 transition-colors rounded-lg"
                                            :class="copiedInvitationId === data.invitation.id
                                                ? 'text-secondary'
                                                : 'text-tinta/30 hover:text-primary hover:bg-primary/5'"
                                            :title="copiedInvitationId === data.invitation.id ? 'Link copiado' : 'Copiar link'"
                                        >
                                            <CheckIcon v-if="copiedInvitationId === data.invitation.id" class="w-4 h-4" />
                                            <ClipboardDocumentIcon v-else class="w-4 h-4" />
                                        </button>

                                        <!-- Copiar el texto completo para WhatsApp -->
                                        <button
                                            @click="copyInvitationMessage(data.invitation)"
                                            class="p-1.5 transition-colors rounded-lg"
                                            :class="copiedInvitationTextId === data.invitation.id
                                                ? 'text-green-600'
                                                : 'text-tinta/30 hover:text-green-500 hover:bg-green-50'"
                                            :title="copiedInvitationTextId === data.invitation.id ? 'Texto copiado' : 'Copiar texto para WhatsApp'"
                                        >
                                            <CheckIcon v-if="copiedInvitationTextId === data.invitation.id" class="w-4 h-4" />
                                            <ChatBubbleLeftRightIcon v-else class="w-4 h-4" />
                                        </button>

                                        <!-- Marcar como enviada -->
                                        <label
                                            class="inline-flex items-center cursor-pointer"
                                            :title="sentTooltip(data.invitation)"
                                        >
                                            <input
                                                type="checkbox"
                                                class="w-4 h-4 rounded cursor-pointer text-green-600 focus:ring-green-500/40"
                                                :checked="!!data.invitation.sent_at"
                                                :disabled="invitationSentForm.processing"
                                                @change="toggleInvitationSent(data.invitation, $event.target.checked)"
                                            />
                                        </label>
                                    </div>
                                    <span v-else class="text-tinta/40 text-xs">—</span>
                                </template>
                                <template #filter>
                                    <Select
                                        v-model="filters['invitation_state'].value"
                                        :options="invitationOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Invitación"
                                        showClear
                                        class="!w-full"
                                    />
                                </template>
                            </Column>

                            <Column field="table_group" header="Mesa" sortable :showFilterMenu="false" filterMatchMode="equals" style="min-width: 110px">
                                <template #body="{ data }">
                                    <span class="text-tinta/70">{{ data.table_group || '—' }}</span>
                                </template>
                                <template #editor="{ data, field, editorSaveCallback }">
                                    <Select
                                        v-model="data[field]"
                                        :options="tableOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Sin mesa"
                                        showClear
                                        class="!w-full"
                                        @update:model-value="editorSaveCallback"
                                    />
                                </template>
                                <template #filter>
                                    <Select v-model="filters['table_group'].value" :options="tableOptions" optionLabel="label" optionValue="value" placeholder="Mesa" showClear class="!w-full" />
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
                                        <button @click="openDetail(data)" class="p-2 text-tinta/30 hover:text-primary transition-colors rounded-lg hover:bg-primary/5" title="Ver detalle">
                                            <EyeIcon class="w-4 h-4" />
                                        </button>
                                        <button @click="goToEdit(data)" class="p-2 text-tinta/30 hover:text-primary transition-colors rounded-lg hover:bg-primary/5" title="Editar">
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                        <ConfirmDeleteModal :message="`¿Eliminar a «${data.full_name}»? Esta acción no se puede deshacer y perderá su confirmación de asistencia si ya respondió.`" @confirm="doDelete">
                                            <template #default="{ open: openDel }">
                                                <button @click="confirmDelete(data); openDel()" class="p-2 text-tinta/30 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50" title="Eliminar">
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
                                <p class="text-sm text-tinta/60">
                                    El archivo debe incluir la columna <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">first_name</code> (o <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">full_name</code>). Puede incluir: <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">last_name</code>, <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">age</code>, <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">gender</code>, <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">group</code>, <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">phone</code>, <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">origin</code>, <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">state</code>, <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">city</code> y <code class="bg-niebla px-1.5 py-0.5 rounded text-tinta">table_group</code>. También acepta nombres en español (nombre, apellidos, edad, género, grupo, celular, origen, estado, ciudad, mesa).
                                </p>
                                <div class="border-2 border-dashed border-tinta/20 rounded-xl p-6 text-center">
                                    <DocumentTextIcon class="w-8 h-8 text-tinta/30 mx-auto mb-2" />
                                    <label class="cursor-pointer text-primary hover:text-primary-dark text-sm font-medium">
                                        Seleccionar archivo CSV
                                        <input ref="csvInput" type="file" accept=".csv,.txt" class="hidden" @change="onCsvFile" />
                                    </label>
                                    <p v-if="importForm.csv_file" class="text-tinta/50 text-xs mt-2">{{ importForm.csv_file.name }}</p>
                                </div>
                                <InputError :message="importForm.errors.csv_file" />

                                <div v-if="importResult" :class="importResultIsError
                                    ? 'bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-600'
                                    : 'bg-secondary/10 border border-secondary/20 rounded-xl p-4 text-sm text-secondary'">
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

                    <!-- ── Invitaciones digitales (parejas / links) ── -->
                    <InvitationsManagerModal
                        :show="showInvitationsModal"
                        :guests="guests"
                        :invitations="invitations"
                        :initial-member-ids="invitationMemberIds"
                        @close="closeInvitations"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
