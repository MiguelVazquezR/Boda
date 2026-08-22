<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import GroupsManagerModal from './GroupsManagerModal.vue';
import { estadosMexico } from '@/Data/mexico.js';

const props = defineProps({
    guest: { type: Object, default: null },
    groups: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.guest);

const form = useForm({
    first_name: props.guest?.first_name ?? '',
    last_name: props.guest?.last_name ?? '',
    age: props.guest?.age ?? '',
    gender: props.guest?.gender ?? '',
    guest_group_id: props.guest?.guest_group_id ?? null,
    phone: props.guest?.phone ?? '',
    origin: props.guest?.origin ?? '',
    state: props.guest?.state ?? '',
    city: props.guest?.city ?? '',
    table_group: props.guest?.table_group ?? '',
});

// ── Opciones ──
const genderOptions = [
    { value: 'femenino', label: 'Femenino' },
    { value: 'masculino', label: 'Masculino' },
];

const originOptions = [
    { value: 'local', label: 'Local' },
    { value: 'foraneo', label: 'Foráneo' },
];

const groupOptions = computed(() =>
    props.groups.map((g) => ({ value: g.id, label: g.name }))
);

const stateOptions = computed(() =>
    estadosMexico.map((s) => ({ value: s.estado, label: s.estado }))
);

const cityOptions = computed(() => {
    const state = estadosMexico.find((s) => s.estado === form.state);
    if (!state) return [];
    return state.ciudades.map((c) => ({ value: c, label: c }));
});

// Al cambiar el estado, se limpia la ciudad seleccionada
watch(() => form.state, () => {
    form.city = '';
});

// ── Celular: solo 10 dígitos ──
function sanitizePhone(value) {
    return String(value ?? '').replace(/\D/g, '').slice(0, 10);
}

// ── Guardar ──
function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => router.visit(route('admin.guests.index')),
    };

    if (isEdit.value) {
        form.put(route('admin.guests.update', props.guest.id), options);
    } else {
        form.post(route('admin.guests.store'), options);
    }
}

// ── Gestión de grupos (modal) ──
const showGroupsModal = ref(false);

function onGroupsUpdated() {
    // Si el grupo seleccionado fue eliminado, se limpia la selección
    if (form.guest_group_id && !props.groups.some((g) => g.id === form.guest_group_id)) {
        form.guest_group_id = null;
    }
}
</script>

<template>
    <div class="bg-white rounded-2xl border border-cuero/10 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-cuero/10 flex items-center justify-between gap-4">
            <div>
                <h3 class="font-slab text-lg text-cuero">
                    {{ isEdit ? 'Editar invitado' : 'Nuevo invitado' }}
                </h3>
                <p class="text-sm text-cuero/50 mt-0.5">
                    Completa la información del invitado. Los campos con * son obligatorios.
                </p>
            </div>
            <Link
                :href="route('admin.guests.index')"
                class="text-sm text-cuero/50 hover:text-cuero transition-colors"
            >
                ← Volver a invitados
            </Link>
        </div>

        <form @submit.prevent="submit" class="px-6 py-6 space-y-8">
            <!-- ── Datos del invitado ── -->
            <section>
                <h4 class="text-xs uppercase tracking-widest text-dorado font-semibold mb-4">
                    Datos del invitado
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Nombre *" />
                        <TextInput
                            v-model="form.first_name"
                            class="w-full mt-1"
                            placeholder="Ej. María"
                            :class="{ 'border-red-400': form.errors.first_name }"
                        />
                        <InputError :message="form.errors.first_name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Apellidos" />
                        <TextInput
                            v-model="form.last_name"
                            class="w-full mt-1"
                            placeholder="Ej. García López"
                            :class="{ 'border-red-400': form.errors.last_name }"
                        />
                        <InputError :message="form.errors.last_name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Edad" />
                        <TextInput
                            v-model="form.age"
                            type="number"
                            min="1"
                            max="120"
                            class="w-full mt-1"
                            placeholder="Ej. 30"
                        />
                        <InputError :message="form.errors.age" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Género" />
                        <select
                            v-model="form.gender"
                            class="mt-1 w-full rounded-xl border border-cuero/20 bg-white text-sm text-cuero px-3.5 py-2.5 focus:border-dorado focus:ring-dorado/20"
                        >
                            <option value="">Seleccionar...</option>
                            <option v-for="opt in genderOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.gender" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <div class="flex items-center justify-between mb-1">
                            <InputLabel value="Grupo (amigos del novio, familiares, etc.)" />
                            <button
                                type="button"
                                @click="showGroupsModal = true"
                                class="text-xs text-mezclilla hover:text-mezclilla-light font-medium transition-colors"
                            >
                                Gestionar grupos
                            </button>
                        </div>
                        <div>
                            <SearchableSelect
                                v-model="form.guest_group_id"
                                :options="groupOptions"
                                placeholder="Seleccionar grupo..."
                                empty-message="No hay grupos. Crea uno nuevo."
                            />
                            <InputError :message="form.errors.guest_group_id" class="mt-1" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Contacto ── -->
            <section class="pt-6 border-t border-cuero/10">
                <h4 class="text-xs uppercase tracking-widest text-dorado font-semibold mb-4">
                    Información de contacto
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Celular (10 dígitos)" />
                        <TextInput
                            :model-value="form.phone"
                            @update:model-value="form.phone = sanitizePhone($event)"
                            inputmode="numeric"
                            maxlength="10"
                            class="w-full mt-1"
                            placeholder="Ej. 3312345678"
                        />
                        <InputError :message="form.errors.phone" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Origen" />
                        <select
                            v-model="form.origin"
                            class="mt-1 w-full rounded-xl border border-cuero/20 bg-white text-sm text-cuero px-3.5 py-2.5 focus:border-dorado focus:ring-dorado/20"
                        >
                            <option value="">Seleccionar...</option>
                            <option v-for="opt in originOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.origin" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Estado" />
                        <SearchableSelect
                            v-model="form.state"
                            :options="stateOptions"
                            placeholder="Seleccionar estado..."
                            empty-message="No se encontró el estado"
                        />
                        <InputError :message="form.errors.state" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Ciudad" />
                        <SearchableSelect
                            v-model="form.city"
                            :options="cityOptions"
                            placeholder="Primero selecciona el estado"
                            empty-message="No hay ciudades para el estado"
                            :disabled="!form.state"
                        />
                        <InputError :message="form.errors.city" class="mt-1" />
                    </div>
                </div>
            </section>

            <!-- ── Mesa / asignación ── -->
            <section class="pt-6 border-t border-cuero/10">
                <h4 class="text-xs uppercase tracking-widest text-dorado font-semibold mb-4">
                    Asignación
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Mesa (opcional)" />
                        <TextInput
                            v-model="form.table_group"
                            class="w-full mt-1"
                            placeholder="Ej. 5"
                        />
                        <InputError :message="form.errors.table_group" class="mt-1" />
                    </div>
                </div>
            </section>

            <!-- ── Errores generales ── -->
            <div v-if="Object.keys(form.errors).length" class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-600">
                Revisa los campos marcados en rojo antes de guardar.
            </div>

            <!-- ── Acciones ── -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-cuero/10">
                <SecondaryButton type="button" @click="router.visit(route('admin.guests.index'))">
                    Cancelar
                </SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing" :class="{ 'opacity-50': form.processing }">
                    {{ form.processing ? 'Guardando...' : 'Guardar invitado' }}
                </PrimaryButton>
            </div>
        </form>

        <!-- ── Modal: gestionar grupos ── -->
        <GroupsManagerModal
            :show="showGroupsModal"
            :groups="groups"
            @close="showGroupsModal = false"
            @updated="onGroupsUpdated"
        />
    </div>
</template>
