<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import MultiSelect from 'primevue/multiselect';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ClipboardDocumentIcon, CheckIcon, ChatBubbleLeftRightIcon, ArrowPathIcon, PencilIcon, TrashIcon, UserPlusIcon, LinkIcon } from '@heroicons/vue/24/outline';

/**
 * Gestión de invitaciones digitales (parejas o personas solas).
 *
 * Una invitación agrupa a 1 o 2 personas y genera el link público
 * /i/{token} que se comparte con los invitados. Se abre desde la sección
 * «Invitados» y también con los invitados ya seleccionados en la tabla.
 */
const props = defineProps({
    show: Boolean,
    guests: { type: Array, default: () => [] },
    invitations: { type: Array, default: () => [] },
    // Invitados preseleccionados al abrir desde la tabla
    initialMemberIds: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'updated']);

/**
 * Visibilidad del Dialog de PrimeVue. El padre controla el modal con la prop
 * `show`, así que se traduce a `v-model:visible`; cuando se cierra desde el
 * modal (clic en el fondo, Escape o el botón «Cerrar») se avisa al padre.
 */
const visible = computed({
    get: () => props.show,
    set: (value) => {
        if (!value) emit('close');
    },
});

/**
 * Ajustes del Dialog para que se vea como el resto de los modales del panel:
 * mismos rellenos, separadores y color de pie. El `!` es necesario porque el
 * tema de PrimeVue inyecta sus reglas después de las clases de Tailwind.
 */
const dialogPt = {
    root: { class: '!rounded-2xl' },
    header: { class: '!px-6 !py-4 border-b border-tinta/10' },
    content: { class: '!px-6 !py-5' },
    footer: { class: '!px-6 !py-4 bg-niebla border-t border-tinta/10 !gap-2' },
};

const editingId = ref(null);
const copiedId = ref(null);

const form = useForm({ display_name: '', members: [] });
const actionForm = useForm({});
// Check «enviada» de cada invitación creada (columna derecha de la lista)
const sentForm = useForm({ sent: false });

/** Invitación que se está editando (null = creando una nueva). */
const editingInvitation = computed(
    () => props.invitations.find((invitation) => invitation.id === editingId.value) ?? null,
);

/** Invitados elegibles: los libres, más los que ya son de la invitación en edición. */
const memberOptions = computed(() => {
    const editingMembers = editingInvitation.value?.members?.map((member) => member.id) ?? [];

    return props.guests
        .filter((guest) => !guest.invitation_id || editingMembers.includes(guest.id))
        .map((guest) => ({ label: guest.full_name, value: guest.id }));
});

/** Nombre sugerido a partir de los invitados elegidos, con su nombre completo ("Ana Pérez & Luis García"). */
const suggestedName = computed(() => {
    const names = form.members
        .map((id) => props.guests.find((guest) => guest.id === id))
        .filter(Boolean)
        .map((guest) => guest.full_name);

    return names.join(' & ');
});

// Último nombre escrito automáticamente en el campo «Nombre en la invitación».
// Solo se reemplaza si el campo está vacío o conserva el valor automático, para
// no pisar un nombre que se haya escrito a mano.
const autoFilledName = ref('');

watch(suggestedName, (name) => {
    const current = form.display_name ?? '';

    if (current === '' || current === autoFilledName.value) {
        form.display_name = name;
        autoFilledName.value = name;
    }
});

/** Aplica el nombre sugerido al campo (y lo recuerda como valor automático). */
function useSuggestedName() {
    form.display_name = suggestedName.value;
    autoFilledName.value = suggestedName.value;
}

/** Invitados que aún no tienen invitación (para el atajo de crear individuales). */
const guestsWithoutInvitation = computed(() => props.guests.filter((guest) => !guest.invitation_id));

// Al abrir desde la tabla con invitados seleccionados, se precargan en el formulario.
watch(
    () => [props.show, props.initialMemberIds],
    ([isOpen]) => {
        if (isOpen) {
            openCreate(props.initialMemberIds);
        }
    },
    { deep: true },
);

function openCreate(prefill = []) {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    // El nombre se vuelve a sugerir a partir de los invitados elegidos
    autoFilledName.value = '';
    form.members = prefill.slice(0, 2);
}

function openEdit(invitation) {
    editingId.value = invitation.id;
    form.clearErrors();
    // En edición se respeta el nombre guardado (el botón «Usar …» sigue disponible)
    autoFilledName.value = '';
    form.display_name = invitation.display_name;
    form.members = invitation.members.map((member) => member.id);
}

function save() {
    const options = { preserveScroll: true, onSuccess: afterSave };

    if (editingId.value) {
        form.put(route('admin.invitations.update', editingId.value), options);
    } else {
        form.post(route('admin.invitations.store'), options);
    }
}

function afterSave() {
    form.reset();
    editingId.value = null;
    refresh();
}

/** Recarga los invitados y las invitaciones conservando filtros y scroll. */
function refresh() {
    router.reload({
        only: ['guests', 'invitations'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => emit('updated'),
    });
}

async function copyLink(invitation) {
    try {
        await navigator.clipboard.writeText(invitation.public_url);
        copiedId.value = invitation.id;
        setTimeout(() => {
            if (copiedId.value === invitation.id) copiedId.value = null;
        }, 2000);
    } catch {
        window.prompt('Copia el link de la invitación:', invitation.public_url);
    }
}

// Las confirmaciones (regenerar link, eliminar, crear en bloque) usan el mismo
// modal de confirmación que el resto del panel: ConfirmDeleteModal.
function regenerate(invitation) {
    actionForm.post(route('admin.invitations.token', invitation.id), {
        preserveScroll: true,
        onSuccess: refresh,
    });
}

function destroy(invitation) {
    actionForm.delete(route('admin.invitations.destroy', invitation.id), {
        preserveScroll: true,
        onSuccess: refresh,
    });
}

function createSingles() {
    actionForm.post(route('admin.invitations.single'), {
        preserveScroll: true,
        onSuccess: refresh,
    });
}

/**
 * Marca o desmarca la invitación como ya enviada a los invitados.
 * Se conserva el estado del modal (preserveState) para que el check se
 * actualice en el momento sin cerrarlo.
 */
function toggleSent(invitation, checked) {
    sentForm.sent = checked;
    sentForm.put(route('admin.invitations.sent', invitation.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: refresh,
    });
}

/** Tooltip del check: fecha en que se envió si ya está marcada. */
function sentTooltip(invitation) {
    if (!invitation.sent_at) return 'Marcar como ya enviada';

    const date = new Date(invitation.sent_at).toLocaleDateString('es-MX', {
        day: 'numeric',
        month: 'long',
    });

    return `Enviada el ${date}`;
}
</script>

<template>
    <!--
        Se usa el Dialog de PrimeVue en lugar de Components/DialogModal.vue porque
        ese se apoya en <dialog showModal()>, que el navegador dibuja en su capa
        superior (top layer): los overlays flotantes de los componentes hijos
        (la lista del MultiSelect, los selectores de fecha, etc.) se montan en
        <body> y quedaban por debajo del modal, sin poder verse. El Dialog de
        PrimeVue reparte los z-index con ZIndex entre modal y overlays, así que
        la lista del selector siempre aparece encima.
    -->
    <Dialog
        v-model:visible="visible"
        modal
        dismissableMask
        :closable="false"
        :draggable="false"
        :style="{ width: '42rem', maxWidth: '94vw' }"
        :pt="dialogPt"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center">
                    <LinkIcon class="w-4 h-4 text-primary" />
                </div>
                <span class="text-lg font-medium text-tinta">Parejas e invitaciones</span>
            </div>
        </template>

        <template #default>
            <div class="space-y-5">
                <p class="text-sm text-tinta/60">
                    Cada invitación se comparte con 1 o 2 personas (una pareja) mediante un link único.
                    El invitado ve su nombre en un sobre elegante y desde ahí entra a la invitación animada.
                </p>

                <!-- Formulario crear / editar -->
                <div class="bg-niebla/50 rounded-xl p-4 space-y-3">
                    <InputLabel :value="editingId ? 'Editar invitación' : 'Nueva invitación'" />

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <InputLabel value="Invitados (máximo 2)" class="mb-1 text-xs" />
                            <MultiSelect
                                v-model="form.members"
                                :options="memberOptions"
                                optionLabel="label"
                                optionValue="value"
                                :selectionLimit="2"
                                filter
                                display="chip"
                                placeholder="Elige 1 o 2 invitados"
                                class="!w-full"
                            />
                            <InputError :message="form.errors.members" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Nombre en la invitación" class="mb-1 text-xs" />
                            <TextInput
                                v-model="form.display_name"
                                class="w-full"
                                :placeholder="suggestedName || 'Ej. Ana Pérez & Luis García'"
                                @keyup.enter="save"
                            />
                            <InputError :message="form.errors.display_name" class="mt-1" />
                            <button
                                v-if="suggestedName && form.display_name !== suggestedName"
                                type="button"
                                @click="useSuggestedName"
                                class="text-xs text-primary hover:text-primary-dark mt-1 transition-colors"
                            >
                                Usar «{{ suggestedName }}»
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <PrimaryButton
                            @click="save"
                            :disabled="!form.members.length || form.processing"
                            :class="{ 'opacity-50': !form.members.length || form.processing }"
                        >
                            {{ form.processing ? 'Guardando...' : (editingId ? 'Guardar cambios' : 'Crear invitación') }}
                        </PrimaryButton>
                        <SecondaryButton v-if="editingId" @click="openCreate()">Cancelar edición</SecondaryButton>
                    </div>
                </div>

                <!-- Atajo: una invitación individual para cada invitado sin invitación -->
                <div v-if="guestsWithoutInvitation.length"
                    class="flex flex-wrap items-center justify-between gap-3 bg-primary/5 border border-primary/10 rounded-xl px-4 py-3">
                    <p class="text-sm text-tinta/70">
                        <span class="font-medium text-tinta">{{ guestsWithoutInvitation.length }}</span>
                        invitado(s) aún sin invitación.
                    </p>
                    <ConfirmDeleteModal
                        title="Crear invitaciones individuales"
                        :message="`Se creará una invitación individual para ${guestsWithoutInvitation.length} invitado(s). Después podrás unir parejas editándolas.`"
                        confirm-label="Crear"
                        :processing="actionForm.processing"
                        @confirm="createSingles"
                    >
                        <template #default="{ open }">
                            <button
                                @click="open()"
                                class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:text-primary-dark transition-colors"
                            >
                                <UserPlusIcon class="w-4 h-4" />
                                Crear una por invitado
                            </button>
                        </template>
                    </ConfirmDeleteModal>
                </div>
                <!-- Lista de invitaciones (parejas y personas solas) -->
                <div v-if="invitations.length" class="space-y-2">
                    <div
                        v-for="invitation in invitations"
                        :key="invitation.id"
                        class="bg-white rounded-xl border px-4 py-3 space-y-2 transition-colors"
                        :class="invitation.sent_at ? 'border-green-200' : 'border-tinta/10'"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-tinta text-sm truncate">{{ invitation.display_name }}</p>
                                <p class="text-xs text-tinta/50 truncate">
                                    {{ invitation.members.map((member) => member.full_name).join(' · ') || 'Sin invitados' }}
                                    <span v-if="invitation.pending_count"> · {{ invitation.pending_count }} pendiente(s)</span>
                                </p>
                            </div>

                            <div class="flex items-center gap-1 flex-shrink-0">
                                <label
                                    class="inline-flex items-center gap-2 rounded-lg px-2 py-1 mr-1 cursor-pointer select-none transition-colors"
                                    :class="invitation.sent_at ? 'bg-green-50' : 'hover:bg-niebla'"
                                    :title="sentTooltip(invitation)"
                                >
                                    <input
                                        type="checkbox"
                                        class="w-4 h-4 rounded cursor-pointer text-green-600 focus:ring-green-500/40"
                                        :checked="!!invitation.sent_at"
                                        :disabled="sentForm.processing"
                                        @change="toggleSent(invitation, $event.target.checked)"
                                    />
                                    <span
                                        class="text-xs font-medium whitespace-nowrap"
                                        :class="invitation.sent_at ? 'text-green-600' : 'text-tinta/50'"
                                    >
                                        {{ invitation.sent_at ? 'Enviada' : 'Marcar enviada' }}
                                    </span>
                                </label>

                                <button
                                    @click="copyLink(invitation)"
                                    class="p-2 transition-colors rounded-lg"
                                    :class="copiedId === invitation.id
                                        ? 'text-secondary'
                                        : 'text-tinta/30 hover:text-primary hover:bg-primary/5'"
                                    :title="copiedId === invitation.id ? 'Link copiado' : 'Copiar link'"
                                >
                                    <CheckIcon v-if="copiedId === invitation.id" class="w-4 h-4" />
                                    <ClipboardDocumentIcon v-else class="w-4 h-4" />
                                </button>

                                <a
                                    :href="invitation.whatsapp_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="p-2 text-tinta/30 hover:text-green-500 transition-colors rounded-lg hover:bg-green-50"
                                    title="Compartir por WhatsApp"
                                >
                                    <ChatBubbleLeftRightIcon class="w-4 h-4" />
                                </a>

                                <button
                                    @click="openEdit(invitation)"
                                    class="p-2 text-tinta/30 hover:text-primary transition-colors rounded-lg hover:bg-primary/5"
                                    title="Editar"
                                >
                                    <PencilIcon class="w-4 h-4" />
                                </button>

                                <ConfirmDeleteModal
                                    title="Regenerar link"
                                    :message="`¿Generar un link nuevo para «${invitation.display_name}»? El link anterior dejará de funcionar.`"
                                    confirm-label="Regenerar"
                                    :processing="actionForm.processing"
                                    @confirm="regenerate(invitation)"
                                >
                                    <template #default="{ open }">
                                        <button
                                            @click="open()"
                                            class="p-2 text-tinta/30 hover:text-primary transition-colors rounded-lg hover:bg-primary/5"
                                            title="Regenerar link"
                                        >
                                            <ArrowPathIcon class="w-4 h-4" />
                                        </button>
                                    </template>
                                </ConfirmDeleteModal>

                                <ConfirmDeleteModal
                                    :message="`¿Eliminar la invitación de «${invitation.display_name}»? Los invitados se conservan, sólo quedan sin invitación.`"
                                    :processing="actionForm.processing"
                                    @confirm="destroy(invitation)"
                                >
                                    <template #default="{ open }">
                                        <button
                                            @click="open()"
                                            class="p-2 text-tinta/30 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50"
                                            title="Eliminar"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </template>
                                </ConfirmDeleteModal>
                            </div>
                        </div>

                        <p class="text-[11px] text-tinta/40 font-mono truncate">{{ invitation.public_url }}</p>
                    </div>
                </div>

                <p v-else class="text-sm text-tinta/50 italic text-center py-4">
                    Aún no hay invitaciones. Crea la primera aquí arriba.
                </p>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="emit('close')">Cerrar</SecondaryButton>
        </template>
    </Dialog>
</template>
