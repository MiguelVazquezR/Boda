<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import DialogModal from '@/Components/DialogModal.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import { PlusIcon, PencilIcon, TrashIcon, ArrowUpIcon, ArrowDownIcon, ClockIcon, PhotoIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    items: Array,
});

// ── Form modal (crear / editar) ──
const showModal = ref(false);
const editingItem = ref(null);
const previewUrl = ref(null);

const form = useForm({
    time: '',
    title: '',
    description: '',
    image: null,
    is_active: true,
});

const fileInputRef = ref(null);

function resetFormFields() {
    form.time = '';
    form.title = '';
    form.description = '';
    form.image = null;
    form.is_active = true;
    form.clearErrors();
    previewUrl.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
}

function openCreate() {
    editingItem.value = null;
    resetFormFields();
    showModal.value = true;
}

function openEdit(item) {
    editingItem.value = item;
    form.time = item.time ?? '';
    form.title = item.title;
    form.description = item.description ?? '';
    form.image = null;
    form.is_active = item.is_active;
    previewUrl.value = item.image_path ? '/storage/' + item.image_path : null;
    if (fileInputRef.value) fileInputRef.value.value = '';
    showModal.value = true;
}

function onImageSelect(e) {
    const file = e.target.files[0];
    form.image = file || null;
    previewUrl.value = file ? URL.createObjectURL(file) : null;
}

function save() {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showModal.value = false;
            resetFormFields();
        },
    };

    if (editingItem.value) {
        form.put(route('admin.schedule.update', editingItem.value.id), options);
    } else {
        form.post(route('admin.schedule.store'), options);
    }
}

// ── Reordenar ──
function move(item, direction) {
    router.post(route('admin.schedule.move', item.id), { direction }, { preserveScroll: true });
}

// ── Eliminar ──
const deleteItem = ref(null);
const deleteForm = useForm({});
function confirmDelete(item) { deleteItem.value = item; }
function doDelete() {
    if (!deleteItem.value) return;
    deleteForm.delete(route('admin.schedule.destroy', deleteItem.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteItem.value = null; },
    });
}
</script>

<template>
    <AppLayout title="Tiempos (Itinerario)">
        <template #header>
            <h2 class="font-slab text-xl text-tinta leading-tight">Tiempos (Itinerario)</h2>
        </template>

        <div class="py-6">
            <div class="max-w-screen-2xl mx-auto px-2 sm:px-3 lg:px-4">
                <div class="flex items-center justify-between gap-4 mb-8">
                    <div>
                        <p class="text-sm text-tinta/60">
                            Administra el itinerario que se muestra en la landing: hora, qué se hará y su imagen.
                        </p>
                    </div>
                    <PrimaryButton @click="openCreate" class="flex items-center gap-2 flex-shrink-0">
                        <PlusIcon class="w-4 h-4" />
                        Agregar Momento
                    </PrimaryButton>
                </div>

                <!-- Lista de momentos -->
                <div v-if="items.length > 0" class="space-y-3">
                    <div
                        v-for="(item, index) in items"
                        :key="item.id"
                        class="bg-white rounded-2xl border border-tinta/10 shadow-sm overflow-hidden"
                    >
                        <div class="flex flex-col sm:flex-row items-stretch">
                            <!-- Imagen -->
                            <div class="sm:w-52 shrink-0 bg-niebla/50">
                                <img
                                    v-if="item.image_path"
                                    :src="'/storage/' + item.image_path"
                                    :alt="item.title"
                                    class="w-full h-40 sm:h-full object-cover"
                                />
                                <div v-else class="w-full h-40 sm:h-full flex items-center justify-center text-tinta/30">
                                    <PhotoIcon class="w-10 h-10" />
                                </div>
                            </div>

                            <!-- Contenido -->
                            <div class="flex-1 px-5 py-4 flex items-start justify-between gap-4 min-w-0">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span v-if="item.time" class="inline-flex items-center gap-1 text-primary font-slab font-bold">
                                            <ClockIcon class="w-4 h-4" />
                                            {{ item.time }}
                                        </span>
                                        <span
                                            v-if="!item.is_active"
                                            class="text-xs bg-tinta/10 text-tinta/50 px-2 py-0.5 rounded-full font-medium"
                                        >
                                            Oculto
                                        </span>
                                    </div>
                                    <h3 class="font-slab text-lg text-tinta">{{ item.title }}</h3>
                                    <p v-if="item.description" class="text-sm text-tinta/60 mt-1 leading-relaxed">
                                        {{ item.description }}
                                    </p>
                                </div>

                                <!-- Acciones -->
                                <div class="flex flex-col items-center gap-1 flex-shrink-0">
                                    <div class="flex items-center gap-1">
                                        <button
                                            @click="move(item, 'up')"
                                            :disabled="index === 0"
                                            class="p-1.5 text-tinta/40 hover:text-tinta rounded-lg hover:bg-niebla disabled:opacity-30 disabled:cursor-not-allowed"
                                            title="Subir"
                                        >
                                            <ArrowUpIcon class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="move(item, 'down')"
                                            :disabled="index === items.length - 1"
                                            class="p-1.5 text-tinta/40 hover:text-tinta rounded-lg hover:bg-niebla disabled:opacity-30 disabled:cursor-not-allowed"
                                            title="Bajar"
                                        >
                                            <ArrowDownIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button @click="openEdit(item)" class="p-2 text-tinta/30 hover:text-primary transition-colors rounded-lg hover:bg-primary/5" title="Editar">
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                        <ConfirmDeleteModal :message="`¿Eliminar «${item.title}» del itinerario?`" @confirm="doDelete">
                                            <template #default="{ open: openDel }">
                                                <button @click="confirmDelete(item); openDel()" class="p-2 text-tinta/30 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50" title="Eliminar">
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </template>
                                        </ConfirmDeleteModal>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState
                    v-else
                    :icon="ClockIcon"
                    title="Sin itinerario"
                    description="Aún no has agregado momentos. Agrega el primero: hora, título, descripción e imagen."
                    :cta-label="'Agregar Momento'"
                    @click="openCreate"
                />

                <!-- ── Modal crear / editar momento ── -->
                <DialogModal :show="showModal" @close="showModal = false" max-width="lg">
                    <template #title>{{ editingItem ? 'Editar Momento' : 'Agregar Momento' }}</template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Hora" />
                                    <TextInput v-model="form.time" class="w-full mt-1" placeholder="Ej. 19:15 PM" />
                                    <InputError :message="form.errors.time" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel value="Título *" />
                                    <TextInput v-model="form.title" class="w-full mt-1" placeholder="Ej. Bienvenida y cócteles" />
                                    <InputError :message="form.errors.title" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Descripción" />
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="¿Qué se hará en este momento?"
                                    class="mt-1 w-full rounded-xl border border-tinta/20 bg-white text-sm text-tinta px-3.5 py-2.5 placeholder-tinta/30 focus:border-primary focus:ring-primary/20 outline-none resize-none"
                                ></textarea>
                                <InputError :message="form.errors.description" class="mt-1" />
                            </div>

                            <!-- Imagen -->
                            <div>
                                <InputLabel value="Imagen" />
                                <div class="mt-1 border-2 border-dashed border-tinta/20 rounded-xl p-4 text-center">
                                    <img
                                        v-if="previewUrl"
                                        :src="previewUrl"
                                        alt="Vista previa"
                                        class="w-full h-40 object-cover rounded-lg mb-3"
                                    />
                                    <div v-else class="flex flex-col items-center text-tinta/40 py-2">
                                        <PhotoIcon class="w-8 h-8 mb-1" />
                                        <span class="text-xs">Sin imagen</span>
                                    </div>
                                    <label class="cursor-pointer inline-block mt-1 text-primary hover:text-primary-dark text-sm font-medium">
                                        {{ previewUrl ? 'Cambiar imagen' : 'Subir imagen' }}
                                        <input ref="fileInputRef" type="file" accept="image/*" class="hidden" @change="onImageSelect" />
                                    </label>
                                    <p v-if="editingItem?.image_path" class="text-xs text-tinta/40 mt-1">
                                        Deja el campo vacío para conservar la imagen actual.
                                    </p>
                                </div>
                                <InputError :message="form.errors.image" class="mt-1" />
                            </div>

                            <!-- Activo -->
                            <label class="flex items-center gap-2 text-sm text-tinta cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="rounded border-tinta/20 text-primary focus:ring-primary/20" />
                                Mostrar en la landing
                            </label>
                        </div>
                    </template>
                    <template #footer>
                        <SecondaryButton @click="showModal = false">Cancelar</SecondaryButton>
                        <PrimaryButton @click="save" :disabled="form.processing" class="ms-3" :class="{ 'opacity-50': form.processing }">
                            {{ form.processing ? 'Guardando...' : 'Guardar' }}
                        </PrimaryButton>
                    </template>
                </DialogModal>
            </div>
        </div>
    </AppLayout>
</template>
