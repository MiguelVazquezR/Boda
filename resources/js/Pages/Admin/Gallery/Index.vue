<script setup>
import { ref } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import DialogModal from '@/Components/DialogModal.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import { PhotoIcon, CheckCircleIcon, XCircleIcon, TrashIcon, ArrowDownTrayIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    photos: Object,
    counts: Object,
});

const page = usePage();
const currentTab = ref(page.props.ziggy?.query?.status ?? '');

function setTab(status) {
    currentTab.value = status;
    router.get(route('admin.gallery.index'), { status: status || undefined }, { preserveState: true, replace: true });
}

// ── Actions ──
const approveForm = useForm({});
function approve(photo) { approveForm.patch(route('admin.gallery.approve', photo.id), { preserveScroll: true }); }

const rejectForm = useForm({});
function reject(photo) { rejectForm.patch(route('admin.gallery.reject', photo.id), { preserveScroll: true }); }

const deletePhoto = ref(null);
const deleteForm = useForm({});
function confirmDelete(photo) { deletePhoto.value = photo; }
function doDelete() {
    if (!deletePhoto.value) return;
    deleteForm.delete(route('admin.gallery.destroy', deletePhoto.value.id), {
        preserveScroll: true,
        onSuccess: () => { deletePhoto.value = null; },
    });
}

// ── Lightbox ──
const lightboxImage = ref(null);
function openLightbox(url) { lightboxImage.value = url; }
function closeLightbox() { lightboxImage.value = null; }

// ── Downloads ──
function downloadPhoto(photo) {
    window.open(route('admin.gallery.download', photo.id), '_blank');
}

const isDownloadingAll = ref(false);
async function downloadAll() {
    if (!props.photos?.data?.length) return;
    isDownloadingAll.value = true;
    try {
        for (const photo of props.photos.data) {
            downloadPhoto(photo);
            await new Promise(r => setTimeout(r, 400));
        }
    } finally {
        isDownloadingAll.value = false;
    }
}

// ── Upload ──
const showUploadModal = ref(false);
const uploadFiles = ref([]);
const uploadForm = useForm({ images: [] });

function onFilesSelected(e) {
    uploadFiles.value = Array.from(e.target.files);
    uploadForm.images = uploadFiles.value;
}

function doUpload() {
    if (!uploadFiles.value.length) return;
    uploadForm.post(route('admin.gallery.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            uploadFiles.value = [];
            uploadForm.reset();
        },
    });
}
</script>

<template>
    <AppLayout title="Galería">
        <template #header>
            <h2 class="font-slab text-xl text-tinta leading-tight">Galería — Moderación</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Tabs + Actions -->
            <div class="flex gap-2 mb-8 flex-wrap items-center justify-between">
                <div class="flex gap-2 flex-wrap items-center">
                <button @click="setTab('')"
                    :class="!currentTab ? 'bg-primary text-white' : 'bg-white text-tinta/60 hover:bg-niebla'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    Todas ({{ counts?.total ?? 0 }})
                </button>
                <button @click="setTab('pending')"
                    :class="currentTab === 'pending' ? 'bg-primary text-white' : 'bg-white text-tinta/60 hover:bg-niebla'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    Pendientes ({{ counts?.pending ?? 0 }})
                </button>
                <button @click="setTab('approved')"
                    :class="currentTab === 'approved' ? 'bg-secondary text-white' : 'bg-white text-tinta/60 hover:bg-niebla'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    Aprobadas ({{ counts?.approved ?? 0 }})
                </button>
                <button @click="setTab('rejected')"
                    :class="currentTab === 'rejected' ? 'bg-red-500 text-white' : 'bg-white text-tinta/60 hover:bg-niebla'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    Rechazadas ({{ counts?.rejected ?? 0 }})
                </button>
                </div>
                <div class="flex gap-2">
                    <button @click="showUploadModal = true"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all bg-primary hover:bg-primary-dark text-white">
                        <ArrowUpTrayIcon class="w-4 h-4" />
                        Subir fotos
                    </button>
                    <button v-if="photos?.data?.length > 0" @click="downloadAll" :disabled="isDownloadingAll"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all bg-primary hover:bg-primary-dark text-white disabled:opacity-50"
                        title="Descargar todas las fotos visibles">
                        <ArrowDownTrayIcon class="w-4 h-4" />
                        {{ isDownloadingAll ? 'Descargando...' : 'Descargar todas' }}
                    </button>
                </div>
            </div>

            <!-- Photo Grid -->
            <div v-if="photos?.data?.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-4">
                <div v-for="photo in photos.data" :key="photo.id"
                    class="relative aspect-square rounded-2xl overflow-hidden group shadow-sm hover:shadow-lg transition-all duration-300">
                    <img :src="'/storage/' + photo.image_path" alt="Foto" class="w-full h-full object-cover" @click="openLightbox('/storage/' + photo.image_path)" />

                    <!-- Status badge -->
                    <div class="absolute top-2 right-2">
                        <StatusBadge :status="photo.status" variant="gallery" />
                    </div>

                    <!-- Overlay con acciones -->
                    <div class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/40 transition-all flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
                        <button @click.stop="downloadPhoto(photo)"
                            class="w-9 h-9 bg-white/90 hover:bg-white text-tinta rounded-full flex items-center justify-center transition-colors shadow-lg" title="Descargar">
                            <ArrowDownTrayIcon class="w-4 h-4" />
                        </button>
                        <button v-if="photo.status !== 'approved'" @click.stop="approve(photo)"
                            class="w-9 h-9 bg-secondary hover:bg-secondary-dark text-white rounded-full flex items-center justify-center transition-colors shadow-lg" title="Aprobar">
                            <CheckCircleIcon class="w-5 h-5" />
                        </button>
                        <button v-if="photo.status !== 'rejected'" @click.stop="reject(photo)"
                            class="w-9 h-9 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors shadow-lg" title="Rechazar">
                            <XCircleIcon class="w-5 h-5" />
                        </button>
                        <ConfirmDeleteModal :message="'¿Eliminar esta foto permanentemente? No se puede deshacer.'" @confirm="doDelete">
                            <template #default="{ open: openDel }">
                                <button @click.stop="confirmDelete(photo); openDel()"
                                    class="w-9 h-9 bg-primary hover:bg-primary-dark text-white rounded-full flex items-center justify-center transition-colors shadow-lg" title="Eliminar">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </template>
                        </ConfirmDeleteModal>
                    </div>

                    <!-- Uploader name -->
                    <div v-if="photo.uploader_name" class="absolute bottom-2 left-2">
                        <span class="bg-tinta/70 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-lg">
                            {{ photo.uploader_name }}
                        </span>
                    </div>
                </div>
            </div>

            <EmptyState v-else :icon="PhotoIcon"
                :title="currentTab === 'pending' ? 'No hay fotos pendientes de revisión 🎉' : currentTab === 'approved' ? 'No hay fotos aprobadas' : 'No hay fotos rechazadas'"
                :description="currentTab === 'pending' ? '¡Todo en orden! Las fotos nuevas aparecerán aquí.' : ''" />

            <!-- Pagination -->
            <div v-if="photos?.links?.length > 3" class="mt-8 flex items-center justify-between">
                <span class="text-xs text-tinta/50">{{ photos.from }}–{{ photos.to }} de {{ photos.total }}</span>
                <div class="flex gap-1">
                    <a v-for="link in photos.links" :key="link.label"
                        :href="link.url ?? '#'" v-html="link.label"
                        :class="['px-3 py-1.5 rounded-lg text-sm transition-colors', link.active ? 'bg-primary text-white' : link.url ? 'text-tinta/60 hover:bg-niebla' : 'text-tinta/20 cursor-default']"></a>
                </div>
            </div>

            <!-- Lightbox -->
            <Teleport to="body">
                <div v-if="lightboxImage" @click="closeLightbox"
                    class="fixed inset-0 z-[100] bg-tinta/95 backdrop-blur-sm flex items-center justify-center p-4 cursor-pointer">
                    <button @click="closeLightbox" class="absolute top-6 right-6 text-white/60 hover:text-white transition-colors">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <img :src="lightboxImage" alt="Foto ampliada" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl" @click.stop />
                </div>
            </Teleport>

            <!-- Upload Modal -->
            <DialogModal :show="showUploadModal" @close="showUploadModal = false; uploadFiles = [];">
                <template #title>Subir Fotos a la Galería</template>
                <template #content>
                    <div class="space-y-4">
                        <p class="text-sm text-tinta/60">Las fotos subidas desde aquí se aprobarán automáticamente. Puedes seleccionar múltiples archivos.</p>
                        <div class="border-2 border-dashed border-tinta/20 rounded-2xl p-8 text-center hover:border-primary/40 transition-colors">
                            <PhotoIcon class="w-10 h-10 text-tinta/20 mx-auto mb-3" />
                            <label class="cursor-pointer inline-flex items-center gap-2 bg-niebla hover:bg-niebla-dark text-tinta px-5 py-2.5 rounded-xl text-sm font-medium border border-tinta/20 transition-colors">
                                <ArrowUpTrayIcon class="w-4 h-4" />
                                Seleccionar fotos
                                <input type="file" accept="image/*" multiple class="hidden" @change="onFilesSelected" />
                            </label>
                            <p v-if="uploadFiles.length > 0" class="text-tinta text-sm mt-3 font-medium">{{ uploadFiles.length }} foto(s) seleccionada(s)</p>
                            <div v-if="uploadFiles.length > 0" class="flex flex-wrap gap-2 mt-3 justify-center">
                                <span v-for="(f, i) in uploadFiles" :key="i" class="text-xs bg-niebla px-2 py-1 rounded-lg text-tinta/70 truncate max-w-[150px]">{{ f.name }}</span>
                            </div>
                        </div>
                    </div>
                </template>
                <template #footer>
                    <SecondaryButton @click="showUploadModal = false; uploadFiles = [];">Cancelar</SecondaryButton>
                    <PrimaryButton @click="doUpload" :disabled="!uploadFiles.length || uploadForm.processing" class="ms-3" :class="{ 'opacity-50': !uploadFiles.length || uploadForm.processing }">
                        {{ uploadForm.processing ? 'Subiendo...' : `Subir ${uploadFiles.length ? uploadFiles.length : ''} foto(s)` }}
                    </PrimaryButton>
                </template>
            </DialogModal>
        </div>
    </div>
    </AppLayout>
</template>
