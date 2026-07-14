<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DialogModal from '@/Components/DialogModal.vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import { ChevronUpIcon, ChevronDownIcon, PencilIcon, TrashIcon, PlusIcon, QuestionMarkCircleIcon } from '@heroicons/vue/24/outline';


const props = defineProps({ faqs: Array });

// ── FAQ Form Modal ──
const showFaqModal = ref(false);
const editingFaq = ref(null);

const faqForm = useForm({
    question: '',
    answer: '',
    order: 0,
    is_published: true,
});

function openCreateFaq() {
    editingFaq.value = null;
    faqForm.reset();
    faqForm.order = (props.faqs?.length ?? 0) + 1;
    faqForm.is_published = true;
    showFaqModal.value = true;
}

function openEditFaq(faq) {
    editingFaq.value = faq;
    faqForm.question = faq.question;
    faqForm.answer = faq.answer;
    faqForm.order = faq.order;
    faqForm.is_published = faq.is_published;
    showFaqModal.value = true;
}

function saveFaq() {
    if (editingFaq.value) {
        faqForm.put(route('admin.faqs.update', editingFaq.value.id), {
            preserveScroll: true,
            onSuccess: () => { showFaqModal.value = false; },
        });
    } else {
        faqForm.post(route('admin.faqs.store'), {
            preserveScroll: true,
            onSuccess: () => { showFaqModal.value = false; },
        });
    }
}

// ── Reorder ──
function moveUp(index) {
    if (index === 0) return;
    const faq = props.faqs[index];
    useForm({ order: index }).put(route('admin.faqs.update', faq.id), { preserveScroll: true });
}

function moveDown(index) {
    if (index >= (props.faqs?.length ?? 0) - 1) return;
    const faq = props.faqs[index];
    useForm({ order: index + 2 }).put(route('admin.faqs.update', faq.id), { preserveScroll: true });
}

// ── Toggle publish ──
function togglePublished(faq) {
    useForm({ is_published: !faq.is_published }).put(route('admin.faqs.update', faq.id), { preserveScroll: true });
}

// ── Delete ──
const deleteFaqId = ref(null);
function confirmDelete(faq) { deleteFaqId.value = faq; }
const deleteForm = useForm({});
function doDelete() {
    if (!deleteFaqId.value) return;
    deleteForm.delete(route('admin.faqs.destroy', deleteFaqId.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteFaqId.value = null; },
    });
}
</script>

<template>
    <AppLayout title="Preguntas Frecuentes">
        <template #header>
            <h2 class="font-slab text-xl text-cuero leading-tight">Preguntas Frecuentes</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <PrimaryButton @click="openCreateFaq" class="flex items-center gap-2">
                    <PlusIcon class="w-4 h-4" />
                    Nueva Pregunta
                </PrimaryButton>
            </div>

            <ActionMessage :on="faqForm.recentlySuccessful" class="mb-4">Guardado.</ActionMessage>

            <!-- FAQ list -->
            <div v-if="faqs && faqs.length > 0" class="space-y-3">
                <div v-for="(faq, i) in faqs" :key="faq.id"
                    class="bg-white rounded-2xl border border-cuero/10 p-5 hover:shadow-sm transition-shadow">
                    <div class="flex items-start gap-4">
                        <!-- Reorder arrows -->
                        <div class="flex flex-col gap-0.5 pt-0.5">
                            <button @click="moveUp(i)" :disabled="i === 0"
                                class="p-0.5 text-cuero/30 hover:text-cuero disabled:opacity-20 transition-colors" :aria-label="'Subir ' + faq.question">
                                <ChevronUpIcon class="w-4 h-4" />
                            </button>
                            <button @click="moveDown(i)" :disabled="i >= faqs.length - 1"
                                class="p-0.5 text-cuero/30 hover:text-cuero disabled:opacity-20 transition-colors" :aria-label="'Bajar ' + faq.question">
                                <ChevronDownIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="font-slab text-cuero truncate">{{ faq.question }}</h3>
                                <StatusBadge :status="faq.is_published ? 'approved' : 'rejected'" variant="gallery" />
                            </div>
                            <p class="text-cuero/50 text-sm line-clamp-2">{{ faq.answer }}</p>
                        </div>

                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button @click="togglePublished(faq)" class="p-2 text-cuero/30 hover:text-dorado transition-colors rounded-lg hover:bg-dorado/5" :title="faq.is_published ? 'Ocultar' : 'Publicar'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="faq.is_published ? 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' : 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21'"/></svg>
                            </button>
                            <button @click="openEditFaq(faq)" class="p-2 text-cuero/30 hover:text-mezclilla transition-colors rounded-lg hover:bg-mezclilla/5" title="Editar">
                                <PencilIcon class="w-4 h-4" />
                            </button>
                            <ConfirmDeleteModal :message="`¿Eliminar la pregunta «${faq.question.substring(0, 50)}...»?`" @confirm="doDelete">
                                <template #default="{ open: openDel }">
                                    <button @click="confirmDelete(faq); openDel()" class="p-2 text-cuero/30 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50" title="Eliminar">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </template>
                            </ConfirmDeleteModal>
                        </div>
                    </div>
                </div>
            </div>

            <EmptyState v-else :icon="QuestionMarkCircleIcon" title="Sin preguntas frecuentes"
                description="Agrega la primera pregunta para que aparezca en el sitio público."
                cta-label="Nueva Pregunta" @click="openCreateFaq" />

            <!-- FAQ Form Modal -->
            <DialogModal :show="showFaqModal" @close="showFaqModal = false">
                <template #title>{{ editingFaq ? 'Editar Pregunta' : 'Nueva Pregunta' }}</template>
                <template #content>
                    <div class="space-y-4">
                        <div>
                            <InputLabel value="Pregunta" />
                            <TextInput v-model="faqForm.question" class="w-full mt-1" placeholder="Ej. ¿Hay estacionamiento?" />
                            <InputError :message="faqForm.errors.question" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Respuesta" />
                            <textarea v-model="faqForm.answer" rows="4"
                                class="w-full mt-1 rounded-xl border-cuero/20 focus:border-dorado focus:ring-dorado/20"
                                placeholder="Escribe la respuesta..."></textarea>
                            <InputError :message="faqForm.errors.answer" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Orden" />
                            <TextInput v-model="faqForm.order" type="number" class="w-24 mt-1" />
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <Checkbox v-model:checked="faqForm.is_published" />
                            <span class="text-sm text-cuero/70">Mostrar en el sitio público</span>
                        </label>
                    </div>
                </template>
                <template #footer>
                    <SecondaryButton @click="showFaqModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton @click="saveFaq" :disabled="faqForm.processing" class="ms-3" :class="{ 'opacity-50': faqForm.processing }">
                        {{ faqForm.processing ? 'Guardando...' : 'Guardar' }}
                    </PrimaryButton>
                </template>
            </DialogModal>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
