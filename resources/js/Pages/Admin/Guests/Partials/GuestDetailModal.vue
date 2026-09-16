<script setup>
import { computed, ref } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import { UserGroupIcon, LinkIcon, ClipboardDocumentIcon, CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    guest: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const show = computed(() => !!props.guest);

// ── Invitación digital del invitado ──
const copied = ref(false);

async function copyInvitationLink() {
    const url = props.guest?.invitation?.public_url;
    if (!url) return;

    try {
        await navigator.clipboard.writeText(url);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch {
        window.prompt('Copia el link de la invitación:', url);
    }
}

const fullName = computed(() =>
    props.guest?.full_name
    || [props.guest?.first_name, props.guest?.last_name].filter(Boolean).join(' ')
    || 'Invitado'
);

function formatDateTime(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('es-MX', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatGender(g) {
    if (!g) return '—';
    return g.charAt(0).toUpperCase() + g.slice(1);
}

function formatOrigin(o) {
    if (o === 'foraneo') return 'Foráneo';
    if (o === 'local') return 'Local';
    return '—';
}
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" maxWidth="lg">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                    <UserGroupIcon class="w-5 h-5 text-primary" />
                </div>
                <span>{{ fullName }}</span>
            </div>
        </template>

        <template #content>
            <div v-if="guest" class="space-y-5">
                <!-- Estado RSVP -->
                <div class="bg-niebla/50 rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs uppercase tracking-wide text-tinta/50 font-medium">Estado de Confirmación</span>
                        <StatusBadge :status="guest.rsvp_status" variant="guest" />
                    </div>
                    <div v-if="guest.rsvp_status !== 'pending'" class="space-y-3">
                        <div class="bg-white rounded-xl p-3">
                            <span class="text-tinta/40 text-xs">Fecha de respuesta</span>
                            <p class="font-medium text-tinta mt-0.5">{{ formatDateTime(guest.rsvp_responded_at) }}</p>
                        </div>
                    </div>
                    <div v-else class="text-sm text-tinta/50 italic mt-1">
                        Este invitado aún no ha respondido a la invitación.
                    </div>
                </div>

                <!-- Mensaje del invitado -->
                <div v-if="guest.rsvp_message" class="bg-primary/5 border border-primary/10 rounded-2xl p-5">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-primary/70 font-medium block mb-1">Mensaje del invitado</span>
                            <p class="text-tinta text-sm leading-relaxed italic">"{{ guest.rsvp_message }}"</p>
                        </div>
                    </div>
                </div>

                <!-- Invitación digital (pareja o persona sola) -->
                <div class="bg-niebla/50 rounded-2xl p-5">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <span class="text-xs uppercase tracking-wide text-tinta/50 font-medium">Invitación digital</span>
                        <button
                            v-if="guest.invitation"
                            @click="copyInvitationLink"
                            class="inline-flex items-center gap-1.5 text-xs font-medium transition-colors"
                            :class="copied ? 'text-secondary' : 'text-primary hover:text-primary-dark'"
                        >
                            <CheckIcon v-if="copied" class="w-3.5 h-3.5" />
                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                            {{ copied ? 'Link copiado' : 'Copiar link' }}
                        </button>
                    </div>

                    <template v-if="guest.invitation">
                        <p class="font-medium text-tinta">{{ guest.invitation.display_name }}</p>
                        <p class="text-[11px] text-tinta/50 font-mono truncate mt-1">{{ guest.invitation.public_url }}</p>
                        <a
                            :href="guest.invitation.public_url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 text-xs text-primary hover:text-primary-dark mt-2 transition-colors"
                        >
                            <LinkIcon class="w-3.5 h-3.5" />
                            Ver la puerta de apertura
                        </a>
                    </template>
                    <p v-else class="text-sm text-tinta/50 italic">
                        Este invitado aún no tiene invitación. Asígnale una desde «Parejas / Links».
                    </p>
                </div>

                <!-- Información del invitado -->
                <div class="bg-niebla/50 rounded-2xl p-5">
                    <span class="text-xs uppercase tracking-wide text-tinta/50 font-medium block mb-3">Información del invitado</span>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white rounded-xl p-3">
                            <span class="text-tinta/40 text-xs">Edad</span>
                            <p class="font-medium text-tinta mt-0.5">{{ guest.age ?? '—' }}</p>
                        </div>
                        <div class="bg-white rounded-xl p-3">
                            <span class="text-tinta/40 text-xs">Género</span>
                            <p class="font-medium text-tinta mt-0.5">{{ formatGender(guest.gender) }}</p>
                        </div>
                        <div class="bg-white rounded-xl p-3">
                            <span class="text-tinta/40 text-xs">Grupo</span>
                            <p class="font-medium text-tinta mt-0.5">{{ guest.group?.name || 'Sin asignar' }}</p>
                        </div>
                        <div class="bg-white rounded-xl p-3">
                            <span class="text-tinta/40 text-xs">Mesa</span>
                            <p class="font-medium text-tinta mt-0.5">{{ guest.table_group || 'Sin asignar' }}</p>
                        </div>
                        <div class="bg-white rounded-xl p-3">
                            <span class="text-tinta/40 text-xs">Celular</span>
                            <p class="font-medium text-tinta mt-0.5">{{ guest.phone || '—' }}</p>
                        </div>
                        <div class="bg-white rounded-xl p-3">
                            <span class="text-tinta/40 text-xs">Origen</span>
                            <p class="font-medium text-tinta mt-0.5">{{ formatOrigin(guest.origin) }}</p>
                        </div>
                        <div class="bg-white rounded-xl p-3">
                            <span class="text-tinta/40 text-xs">Estado</span>
                            <p class="font-medium text-tinta mt-0.5">{{ guest.state || '—' }}</p>
                        </div>
                        <div class="bg-white rounded-xl p-3">
                            <span class="text-tinta/40 text-xs">Ciudad</span>
                            <p class="font-medium text-tinta mt-0.5">{{ guest.city || '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="emit('close')">Cerrar</SecondaryButton>
        </template>
    </DialogModal>
</template>
