<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { TagIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    groups: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'updated']);

// ── Crear / editar ──
const editingId = ref(null);
const form = useForm({ name: '' });

const deleteId = ref(null);
const deleteForm = useForm({});

function openCreate() {
    editingId.value = null;
    deleteId.value = null;
    form.reset();
    form.clearErrors();
}

function openEdit(group) {
    editingId.value = group.id;
    deleteId.value = null;
    form.name = group.name;
    form.clearErrors();
}

function save() {
    if (editingId.value) {
        form.put(route('admin.groups.update', editingId.value), {
            preserveScroll: true,
            onSuccess: afterSave,
        });
    } else {
        form.post(route('admin.groups.store'), {
            preserveScroll: true,
            onSuccess: afterSave,
        });
    }
}

function afterSave() {
    form.reset();
    editingId.value = null;
    router.reload({
        only: ['groups'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => emit('updated'),
    });
}

// ── Eliminar (con confirmación inline) ──
function confirmDelete(group) {
    deleteId.value = group.id;
    form.clearErrors();
}

function cancelDelete() {
    deleteId.value = null;
}

function doDelete() {
    if (!deleteId.value) return;
    deleteForm.delete(route('admin.groups.destroy', deleteId.value), {
        preserveScroll: true,
        onSuccess: () => {
            deleteId.value = null;
            router.reload({
                only: ['groups'],
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => emit('updated'),
            });
        },
    });
}
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" max-width="lg">
        <template #title>Gestionar grupos</template>
        <template #content>
            <div class="space-y-5">
                <!-- Form crear / editar -->
                <div class="bg-arena/50 rounded-xl p-4">
                    <InputLabel :value="editingId ? 'Editar grupo' : 'Nuevo grupo'" />
                    <div class="flex items-start gap-2 mt-1">
                        <div class="flex-1">
                            <TextInput
                                v-model="form.name"
                                class="w-full"
                                placeholder="Ej. Amigos del novio"
                                @keyup.enter="save"
                            />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>
                        <PrimaryButton
                            @click="save"
                            :disabled="form.processing || !form.name.trim()"
                            :class="{ 'opacity-50': form.processing || !form.name.trim() }"
                            class="flex-shrink-0"
                        >
                            {{ editingId ? 'Guardar' : 'Agregar' }}
                        </PrimaryButton>
                    </div>
                    <button
                        v-if="editingId"
                        type="button"
                        @click="openCreate"
                        class="text-xs text-cuero/50 hover:text-cuero mt-2 transition-colors"
                    >
                        Cancelar edición
                    </button>
                </div>

                <!-- Lista de grupos -->
                <div v-if="groups.length" class="space-y-2">
                    <div
                        v-for="group in groups"
                        :key="group.id"
                        class="flex items-center justify-between gap-3 bg-white rounded-xl border border-cuero/10 px-4 py-3"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 bg-dorado/10 rounded-full flex items-center justify-center flex-shrink-0">
                                <TagIcon class="w-4 h-4 text-dorado" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-cuero truncate text-sm">{{ group.name }}</p>
                                <p class="text-xs text-cuero/50">{{ group.guests_count ?? 0 }} invitado(s)</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button
                                @click="openEdit(group)"
                                class="p-2 text-cuero/30 hover:text-mezclilla transition-colors rounded-lg hover:bg-mezclilla/5"
                                title="Editar"
                            >
                                <PencilIcon class="w-4 h-4" />
                            </button>
                            <button
                                @click="confirmDelete(group)"
                                class="p-2 text-cuero/30 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50"
                                title="Eliminar"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-cuero/50 italic text-center py-4">
                    Aún no hay grupos. Crea el primero arriba.
                </p>

                <!-- Confirmación de eliminación -->
                <div v-if="deleteId" class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <p class="text-sm text-red-700 mb-3">
                        ¿Eliminar el grupo «{{ groups.find((g) => g.id === deleteId)?.name }}»?
                        Los invitados que lo tenían quedarán sin grupo asignado.
                    </p>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton @click="cancelDelete">Cancelar</SecondaryButton>
                        <DangerButton @click="doDelete" :disabled="deleteForm.processing" :class="{ 'opacity-50': deleteForm.processing }">
                            {{ deleteForm.processing ? 'Eliminando...' : 'Eliminar' }}
                        </DangerButton>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="emit('close')">Cerrar</SecondaryButton>
        </template>
    </DialogModal>
</template>
