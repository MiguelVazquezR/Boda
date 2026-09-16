<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { TableCellsIcon, PencilIcon, TrashIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    tables: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'updated']);

// ── Crear / renombrar ──
const editingId = ref(null);
const form = useForm({ name: '' });

// ── Eliminar (confirmación dentro del modal) ──
const deleteId = ref(null);
const deleteForm = useForm({});

// ── Invitados visibles de cada mesa ──
const expanded = ref([]);

const editingTable = computed(() => props.tables.find((table) => table.id === editingId.value) ?? null);
const deleteTable = computed(() => props.tables.find((table) => table.id === deleteId.value) ?? null);

/** Vuelve a pedir al backend las mesas, los invitados y los KPIs. */
function refresh() {
    router.reload({
        only: ['tables', 'guests', 'stats'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => emit('updated'),
    });
}

function openCreate() {
    editingId.value = null;
    deleteId.value = null;
    form.reset();
    form.clearErrors();
}

function openEdit(table) {
    editingId.value = table.id;
    deleteId.value = null;
    form.name = table.name;
    form.clearErrors();
}

function save() {
    if (editingId.value) {
        form.put(route('admin.tables.update', editingId.value), {
            preserveScroll: true,
            onSuccess: afterSave,
        });
    } else {
        form.post(route('admin.tables.store'), {
            preserveScroll: true,
            onSuccess: afterSave,
        });
    }
}

function afterSave() {
    form.reset();
    editingId.value = null;
    refresh();
}

function confirmDelete(table) {
    deleteId.value = table.id;
    editingId.value = null;
    form.clearErrors();
}

function cancelDelete() {
    deleteId.value = null;
}

function doDelete() {
    if (!deleteId.value) return;

    deleteForm.delete(route('admin.tables.destroy', deleteId.value), {
        preserveScroll: true,
        onSuccess: () => {
            deleteId.value = null;
            refresh();
        },
    });
}

function toggleGuests(name) {
    expanded.value = expanded.value.includes(name)
        ? expanded.value.filter((item) => item !== name)
        : [...expanded.value, name];
}

function close() {
    openCreate();
    cancelDelete();
    expanded.value = [];
    emit('close');
}
</script>

<template>
    <DialogModal :show="show" @close="close" max-width="2xl">
        <template #title>Gestionar mesas</template>
        <template #content>
            <div class="space-y-5">
                <!-- Crear / renombrar -->
                <div class="bg-niebla/50 rounded-xl p-4">
                    <InputLabel :value="editingId ? 'Renombrar mesa' : 'Nueva mesa'" />
                    <div class="flex items-start gap-2 mt-1">
                        <div class="flex-1">
                            <TextInput
                                v-model="form.name"
                                class="w-full"
                                placeholder="Ej. 5 o Familia López"
                                @keyup.enter="save"
                            />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>
                        <PrimaryButton
                            type="button"
                            @click="save"
                            :disabled="!form.name || form.processing"
                            :class="{ 'opacity-50': !form.name || form.processing }"
                        >
                            {{ editingId
                                ? (form.processing ? 'Guardando...' : 'Guardar')
                                : (form.processing ? 'Creando...' : 'Crear mesa') }}
                        </PrimaryButton>
                    </div>

                    <p v-if="editingTable" class="text-xs text-tinta/50 mt-2">
                        Los {{ editingTable.total }} invitado(s) de la mesa «{{ editingTable.name }}» se moverán al nuevo nombre.
                    </p>
                    <button
                        v-if="editingId"
                        type="button"
                        @click="openCreate"
                        class="text-xs text-tinta/50 hover:text-tinta mt-2 transition-colors"
                    >
                        Cancelar edición
                    </button>
                </div>

                <!-- Lista de mesas -->
                <div v-if="tables.length" class="space-y-2">
                    <div
                        v-for="table in tables"
                        :key="table.name"
                        class="bg-white rounded-xl border border-tinta/10 px-4 py-3"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <button
                                type="button"
                                class="flex items-center gap-3 min-w-0 text-left"
                                @click="toggleGuests(table.name)"
                            >
                                <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                                    <TableCellsIcon class="w-4 h-4 text-primary" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-tinta truncate text-sm">{{ table.name }}</p>
                                    <p class="text-xs text-tinta/50">
                                        {{ table.total }} invitado(s) · {{ table.confirmed }} confirmado(s)
                                    </p>
                                </div>
                            </button>

                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button
                                    type="button"
                                    @click="openEdit(table)"
                                    class="p-2 text-tinta/30 hover:text-primary transition-colors rounded-lg hover:bg-primary/5"
                                    title="Renombrar mesa"
                                >
                                    <PencilIcon class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="confirmDelete(table)"
                                    class="p-2 text-tinta/30 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50"
                                    title="Eliminar mesa"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                                <button
                                    v-if="table.guests.length"
                                    type="button"
                                    @click="toggleGuests(table.name)"
                                    class="p-2 text-tinta/30 hover:text-tinta transition-colors rounded-lg hover:bg-niebla"
                                    :title="expanded.includes(table.name) ? 'Ocultar invitados' : 'Ver invitados'"
                                >
                                    <ChevronDownIcon
                                        class="w-4 h-4 transition-transform"
                                        :class="{ 'rotate-180': expanded.includes(table.name) }"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- Invitados de la mesa -->
                        <ul
                            v-if="expanded.includes(table.name) && table.guests.length"
                            class="mt-3 pt-3 border-t border-tinta/5 space-y-1 max-h-40 overflow-y-auto pr-1"
                        >
                            <li v-for="(name, i) in table.guests" :key="i" class="text-sm text-tinta/70 flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary mt-1.5 shrink-0"></span>
                                {{ name }}
                            </li>
                        </ul>
                    </div>
                </div>
                <p v-else class="text-sm text-tinta/50 italic text-center py-4">
                    Aún no hay mesas. Crea la primera arriba.
                </p>

                <!-- Confirmación de eliminación -->
                <div v-if="deleteTable" class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <p class="text-sm text-red-700 mb-3">
                        ¿Eliminar la mesa «{{ deleteTable.name }}»?
                        {{ deleteTable.total > 0
                            ? `Sus ${deleteTable.total} invitado(s) quedarán sin mesa asignada.`
                            : 'No tiene invitados asignados.' }}
                    </p>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton @click="cancelDelete">Cancelar</SecondaryButton>
                        <DangerButton
                            @click="doDelete"
                            :disabled="deleteForm.processing"
                            :class="{ 'opacity-50': deleteForm.processing }"
                        >
                            {{ deleteForm.processing ? 'Eliminando...' : 'Eliminar mesa' }}
                        </DangerButton>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="close">Cerrar</SecondaryButton>
        </template>
    </DialogModal>
</template>
