<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import DialogModal from '@/Components/DialogModal.vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import { PlusIcon, PencilIcon, TrashIcon, UserGroupIcon, ArrowUpTrayIcon, DocumentTextIcon, MagnifyingGlassIcon, EyeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    guests: Object, // paginated
    filters: Object,
    statusCounts: Object,
});

// ── Search & Filter ──
const search = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');

watch(search, (val) => {
    router.get(route('admin.guests.index'), { search: val, status: statusFilter.value }, { preserveState: true, replace: true });
});

function setStatusFilter(status) {
    statusFilter.value = status;
    router.get(route('admin.guests.index'), { search: search.value, status }, { preserveState: true, replace: true });
}

// ── Guest Form Modal ──
const showGuestModal = ref(false);
const editingGuest = ref(null);

const guestForm = useForm({
    full_name: '',
    allowed_passes: 1,
    table_group: '',
});

function openCreate() {
    editingGuest.value = null;
    guestForm.reset();
    guestForm.allowed_passes = 1;
    showGuestModal.value = true;
}

function openEdit(guest) {
    editingGuest.value = guest;
    guestForm.full_name = guest.full_name;
    guestForm.allowed_passes = guest.allowed_passes;
    guestForm.table_group = guest.table_group ?? '';
    showGuestModal.value = true;
}

function saveGuest() {
    if (editingGuest.value) {
        guestForm.put(route('admin.guests.update', editingGuest.value.id), {
            preserveScroll: true,
            onSuccess: () => { showGuestModal.value = false; },
        });
    } else {
        guestForm.post(route('admin.guests.store'), {
            preserveScroll: true,
            onSuccess: () => { showGuestModal.value = false; },
        });
    }
}

// ── Import CSV Modal ──
const showImportModal = ref(false);
const importForm = useForm({ csv_file: null });
const importResult = ref(null);

function onCsvFile(e) {
    importForm.csv_file = e.target.files[0];
}

function doImport() {
    importForm.post(route('admin.guests.import'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: (page) => {
            importResult.value = page.props.flash?.success ?? 'Importación completada.';
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

// ── Format date ──
function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
}
function formatDateTime(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
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
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <SecondaryButton @click="showImportModal = true" class="flex items-center gap-2">
                        <ArrowUpTrayIcon class="w-4 h-4" />
                        Importar CSV
                    </SecondaryButton>
                    <PrimaryButton @click="openCreate" class="flex items-center gap-2">
                        <PlusIcon class="w-4 h-4" />
                        Agregar Invitado
                    </PrimaryButton>
                </div>
            </div>

            <ActionMessage :on="guestForm.recentlySuccessful" class="mb-4">Guardado.</ActionMessage>

            <!-- Status tabs + search -->
            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <div class="flex gap-2 flex-wrap">
                    <button @click="setStatusFilter('')"
                        :class="!statusFilter ? 'bg-cuero text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                        Todos ({{ statusCounts?.total ?? 0 }})
                    </button>
                    <button @click="setStatusFilter('pending')"
                        :class="statusFilter === 'pending' ? 'bg-dorado text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                        Pendientes ({{ statusCounts?.pending ?? 0 }})
                    </button>
                    <button @click="setStatusFilter('confirmed')"
                        :class="statusFilter === 'confirmed' ? 'bg-olivo text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                        Confirmados ({{ statusCounts?.confirmed ?? 0 }})
                    </button>
                    <button @click="setStatusFilter('declined')"
                        :class="statusFilter === 'declined' ? 'bg-red-500 text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                        No Asistirán ({{ statusCounts?.declined ?? 0 }})
                    </button>
                </div>
                <div class="relative flex-1 max-w-sm">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-cuero/30" />
                    <input v-model="search" type="text" placeholder="Buscar invitado..."
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-cuero/20 text-sm text-cuero placeholder-cuero/30 focus:border-dorado focus:ring-dorado/20" />
                </div>
            </div>

            <!-- Guests table -->
            <div v-if="guests?.data?.length > 0" class="bg-white rounded-2xl border border-cuero/10 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-cuero/10 bg-arena/50">
                                <th class="text-left px-5 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium">Nombre</th>
                                <th class="text-center px-3 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium">Pases</th>
                                <th class="text-left px-3 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium">Estado</th>
                                <th class="text-center px-3 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium">Confirmados</th>
                                <th class="text-left px-3 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium hidden md:table-cell">Grupo</th>
                                <th class="text-right px-5 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cuero/5">
                            <tr v-for="guest in guests.data" :key="guest.id" class="hover:bg-arena/30 transition-colors">
                                <td class="px-5 py-3.5 font-medium text-cuero">{{ guest.full_name }}</td>
                                <td class="px-3 py-3.5 text-center text-cuero/70">{{ guest.allowed_passes }}</td>
                                <td class="px-3 py-3.5"><StatusBadge :status="guest.rsvp_status" variant="guest" /></td>
                                <td class="px-3 py-3.5 text-center text-cuero/70">
                                    {{ guest.rsvp_status === 'confirmed' ? guest.confirmed_passes : '—' }}
                                </td>
                                <td class="px-3 py-3.5 text-cuero/50 hidden md:table-cell">{{ guest.table_group || '—' }}</td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openDetail(guest)" class="p-2 text-cuero/30 hover:text-dorado transition-colors rounded-lg hover:bg-dorado/5" title="Ver detalle">
                                            <EyeIcon class="w-4 h-4" />
                                        </button>
                                        <button @click="openEdit(guest)" class="p-2 text-cuero/30 hover:text-mezclilla transition-colors rounded-lg hover:bg-mezclilla/5" title="Editar">
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                        <ConfirmDeleteModal :message="`¿Eliminar a «${guest.full_name}»? Esta acción no se puede deshacer y perderá su confirmación de asistencia si ya respondió.`" @confirm="doDelete">
                                            <template #default="{ open: openDel }">
                                                <button @click="confirmDelete(guest); openDel()" class="p-2 text-cuero/30 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50" title="Eliminar">
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </template>
                                        </ConfirmDeleteModal>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="guests.links?.length > 3" class="px-5 py-3 border-t border-cuero/10 flex items-center justify-between">
                    <span class="text-xs text-cuero/50">
                        {{ guests.from }}–{{ guests.to }} de {{ guests.total }}
                    </span>
                    <div class="flex gap-1">
                        <a v-for="link in guests.links" :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-sm transition-colors',
                                link.active ? 'bg-cuero text-white' : link.url ? 'text-cuero/60 hover:bg-arena' : 'text-cuero/20 cursor-default',
                            ]"
                        ></a>
                    </div>
                </div>
            </div>

            <EmptyState v-else :icon="UserGroupIcon" title="Sin invitados"
                description="Aún no agregas invitados. Empieza agregando uno o importando tu lista desde un archivo CSV."
                :cta-label="'Agregar Invitado'" @click="openCreate" />

            <!-- ── Guest Form Modal ── -->
            <DialogModal :show="showGuestModal" @close="showGuestModal = false">
                <template #title>{{ editingGuest ? 'Editar Invitado' : 'Agregar Invitado' }}</template>
                <template #content>
                    <div class="space-y-4">
                        <div>
                            <InputLabel value="Nombre Completo" />
                            <TextInput v-model="guestForm.full_name" class="w-full mt-1" placeholder="Ej. María García López" />
                            <InputError :message="guestForm.errors.full_name" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Pases Asignados" />
                            <TextInput v-model="guestForm.allowed_passes" type="number" min="1" class="w-24 mt-1" />
                            <InputError :message="guestForm.errors.allowed_passes" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Grupo / Mesa (opcional)" />
                            <TextInput v-model="guestForm.table_group" class="w-full mt-1" placeholder="Ej. Familia López" />
                        </div>

                        <div v-if="editingGuest" class="mt-4 pt-4 border-t border-cuero/10">
                            <p class="text-xs text-cuero/50 mb-2">Estado de confirmación (no editable aquí):</p>
                            <StatusBadge :status="editingGuest.rsvp_status" variant="guest" />
                            <p v-if="editingGuest.rsvp_status !== 'pending'" class="text-xs text-cuero/50 mt-1">
                                {{ editingGuest.rsvp_status === 'confirmed' ? `Confirmó ${editingGuest.confirmed_passes} pase(s)` : 'Declinó la invitación' }}
                                — {{ new Date(editingGuest.rsvp_responded_at).toLocaleDateString('es-MX') }}
                            </p>
                        </div>
                    </div>
                </template>
                <template #footer>
                    <SecondaryButton @click="showGuestModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton @click="saveGuest" :disabled="guestForm.processing" class="ms-3" :class="{ 'opacity-50': guestForm.processing }">
                        {{ guestForm.processing ? 'Guardando...' : 'Guardar' }}
                    </PrimaryButton>
                </template>
            </DialogModal>

            <!-- ── Import CSV Modal ── -->
            <DialogModal :show="showImportModal" @close="showImportModal = false; importResult = null;">
                <template #title>Importar Invitados (CSV)</template>
                <template #content>
                    <div class="space-y-4">
                        <p class="text-sm text-cuero/60">
                            El archivo debe tener las columnas <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">full_name</code> y <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">allowed_passes</code> (opcional, default 1).
                        </p>
                        <div class="border-2 border-dashed border-cuero/20 rounded-xl p-6 text-center">
                            <DocumentTextIcon class="w-8 h-8 text-cuero/30 mx-auto mb-2" />
                            <label class="cursor-pointer text-mezclilla hover:text-mezclilla-light text-sm font-medium">
                                Seleccionar archivo CSV
                                <input type="file" accept=".csv,.txt" class="hidden" @change="onCsvFile" />
                            </label>
                            <p v-if="importForm.csv_file" class="text-cuero/50 text-xs mt-2">{{ importForm.csv_file.name }}</p>
                        </div>
                        <InputError :message="importForm.errors.csv_file" />

                        <div v-if="importResult" class="bg-olivo/10 border border-olivo/20 rounded-xl p-4 text-sm text-olivo">
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

            <!-- ── Guest Detail Modal ── -->
            <DialogModal :show="!!viewingGuest" @close="viewingGuest = null" maxWidth="lg">
                <template #title>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-dorado/10 rounded-full flex items-center justify-center">
                            <UserGroupIcon class="w-5 h-5 text-dorado" />
                        </div>
                        <span>{{ viewingGuest?.full_name }}</span>
                    </div>
                </template>
                <template #content>
                    <div v-if="viewingGuest" class="space-y-5">
                        <!-- Estado RSVP -->
                        <div class="bg-arena/50 rounded-2xl p-5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs uppercase tracking-wide text-cuero/50 font-medium">Estado de Confirmación</span>
                                <StatusBadge :status="viewingGuest.rsvp_status" variant="guest" />
                            </div>
                            <div v-if="viewingGuest.rsvp_status !== 'pending'" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="bg-white rounded-xl p-3">
                                        <span class="text-cuero/40 text-xs">Pases asignados</span>
                                        <p class="font-medium text-cuero mt-0.5">{{ viewingGuest.allowed_passes }}</p>
                                    </div>
                                    <div class="bg-white rounded-xl p-3">
                                        <span class="text-cuero/40 text-xs">Confirmaron</span>
                                        <p class="font-medium text-cuero mt-0.5">{{ viewingGuest.confirmed_passes ?? '—' }}</p>
                                    </div>
                                </div>
                                <div v-if="viewingGuest.confirmed_by_name" class="bg-white rounded-xl p-3">
                                    <span class="text-cuero/40 text-xs">Confirmado por</span>
                                    <p class="font-medium text-cuero mt-0.5">{{ viewingGuest.confirmed_by_name }}</p>
                                </div>
                                <div class="bg-white rounded-xl p-3">
                                    <span class="text-cuero/40 text-xs">Fecha de respuesta</span>
                                    <p class="font-medium text-cuero mt-0.5">{{ formatDateTime(viewingGuest.rsvp_responded_at) }}</p>
                                </div>
                            </div>
                            <div v-else class="text-sm text-cuero/50 italic mt-1">
                                Este invitado aún no ha respondido a la invitación.
                            </div>
                        </div>

                        <!-- Mensaje del invitado -->
                        <div v-if="viewingGuest.rsvp_message" class="bg-dorado/5 border border-dorado/10 rounded-2xl p-5">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-dorado mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <div>
                                    <span class="text-xs uppercase tracking-wide text-dorado/70 font-medium block mb-1">Mensaje del invitado</span>
                                    <p class="text-cuero text-sm leading-relaxed italic">"{{ viewingGuest.rsvp_message }}"</p>
                                </div>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-arena/50 rounded-xl p-4">
                                <span class="text-cuero/40 text-xs">Grupo / Mesa</span>
                                <p class="font-medium text-cuero mt-1">{{ viewingGuest.table_group || 'Sin asignar' }}</p>
                            </div>
                            <div class="bg-arena/50 rounded-xl p-4">
                                <span class="text-cuero/40 text-xs">Pases disponibles</span>
                                <p class="font-medium text-cuero mt-1">{{ viewingGuest.allowed_passes }}</p>
                            </div>
                        </div>
                    </div>
                </template>
                <template #footer>
                    <SecondaryButton @click="viewingGuest = null">Cerrar</SecondaryButton>
                </template>
            </DialogModal>
            </div>
        </div>
    </AppLayout>
</template>
