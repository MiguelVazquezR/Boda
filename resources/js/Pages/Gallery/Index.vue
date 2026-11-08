<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    galleryPhotos: Array,
});

const lightboxIndex = ref(null);
const lightboxPhoto = computed(() => lightboxIndex.value === null
    ? null
    : props.galleryPhotos[lightboxIndex.value]);

function openLightbox(index) {
    lightboxIndex.value = index;
}

function closeLightbox() {
    lightboxIndex.value = null;
}

function showNextPhoto() {
    if (lightboxIndex.value === null) return;
    lightboxIndex.value = (lightboxIndex.value + 1) % props.galleryPhotos.length;
}

function showPreviousPhoto() {
    if (lightboxIndex.value === null) return;
    lightboxIndex.value = (lightboxIndex.value - 1 + props.galleryPhotos.length) % props.galleryPhotos.length;
}

function imageUrl(photo) {
    return `/storage/${photo.image_path}`;
}
</script>

<template>
    <Head title="Galería" />

    <main class="min-h-screen bg-secondary/5 px-4 py-10 md:py-16">
        <div class="max-w-7xl mx-auto">
            <Link href="/#galeria" class="inline-flex items-center gap-2 text-tinta/60 hover:text-primary transition-colors mb-8">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver a momentos inolvidables
            </Link>

            <header class="text-center mb-12">
                <div class="flex items-center justify-center gap-4 mb-4">
                    <div class="h-px w-10 bg-primary"></div>
                    <span class="text-secondary text-xs tracking-[0.3em] uppercase font-medium">Galería</span>
                    <div class="h-px w-10 bg-primary"></div>
                </div>
                <h1 class="font-script text-4xl sm:text-5xl md:text-6xl text-tinta">Momentos Inolvidables</h1>
                <p class="text-tinta/60 mt-4">
                    {{ galleryPhotos.length }} {{ galleryPhotos.length === 1 ? 'foto' : 'fotos' }} compartidas
                </p>
            </header>

            <div v-if="galleryPhotos.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-5">
                <article v-for="(photo, index) in galleryPhotos" :key="photo.id"
                    class="relative aspect-square rounded-2xl overflow-hidden group bg-white shadow-md">
                    <button type="button" class="w-full h-full cursor-zoom-in" @click="openLightbox(index)"
                        :aria-label="`Ampliar foto ${index + 1}`">
                        <img :src="imageUrl(photo)" alt="Foto de boda"
                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" />
                    </button>
                    <a :href="route('gallery.download', photo.id)" download
                        @click.stop
                        class="absolute right-3 bottom-3 flex items-center justify-center w-10 h-10 rounded-full bg-white/95 text-tinta shadow-lg hover:bg-primary hover:text-white transition-colors"
                        :aria-label="`Descargar foto ${index + 1}`" title="Descargar foto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                        </svg>
                    </a>
                    <p v-if="photo.uploader_name"
                        class="absolute left-3 right-14 bottom-3 truncate text-white text-xs drop-shadow">
                        Por: {{ photo.uploader_name }}
                    </p>
                </article>
            </div>

            <div v-else class="text-center text-tinta/50 py-20">
                <p class="font-slab text-xl">Aún no hay fotos en la galería</p>
                <Link href="/#galeria" class="inline-block mt-4 text-primary hover:underline">Comparte tus fotos</Link>
            </div>
        </div>
    </main>

    <Teleport to="body">
        <div v-if="lightboxPhoto" @click="closeLightbox"
            class="fixed inset-0 z-[100] bg-tinta/95 backdrop-blur-sm flex items-center justify-center p-4">
            <button type="button" @click="closeLightbox" aria-label="Cerrar foto"
                class="absolute top-5 right-5 text-white/70 hover:text-white transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <a :href="route('gallery.download', lightboxPhoto.id)" download @click.stop
                class="absolute top-6 right-16 text-white/70 hover:text-white transition-colors"
                aria-label="Descargar foto" title="Descargar foto">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                </svg>
            </a>
            <button v-if="galleryPhotos.length > 1" type="button" @click.stop="showPreviousPhoto"
                aria-label="Ver foto anterior"
                class="absolute left-2 md:left-8 text-white/80 hover:text-white p-3 rounded-full bg-black/20 hover:bg-black/40 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <img :src="imageUrl(lightboxPhoto)" alt="Foto ampliada"
                class="max-w-full max-h-[88vh] rounded-2xl shadow-2xl object-contain" @click.stop />
            <button v-if="galleryPhotos.length > 1" type="button" @click.stop="showNextPhoto"
                aria-label="Ver siguiente foto"
                class="absolute right-2 md:right-8 text-white/80 hover:text-white p-3 rounded-full bg-black/20 hover:bg-black/40 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </Teleport>
</template>
