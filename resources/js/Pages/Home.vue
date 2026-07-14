<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Squares2X2Icon, Square3Stack3DIcon } from '@heroicons/vue/24/outline';
import DressCodeGrid from '@/Components/DressCode/DressCodeGrid.vue';
import DressCodeCarousel from '@/Components/DressCode/DressCodeCarousel.vue';

// ── Props ────────────────────────────────────────────────────────
const props = defineProps({
    settings: Object,
    faqs: Array,
    galleryPhotos: Array,
});

// ── Navbar scroll state ──────────────────────────────────────────
const isScrolled = ref(false);
const mobileMenuOpen = ref(false);

function onScroll() {
    isScrolled.value = window.scrollY > 60;
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }));
onUnmounted(() => window.removeEventListener('scroll', onScroll));

function scrollTo(id) {
    mobileMenuOpen.value = false;
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

// ── Countdown ────────────────────────────────────────────────────
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
let countdownInterval = null;

function updateCountdown() {
    const eventDate = props.settings?.event_datetime
        ? new Date(props.settings.event_datetime)
        : new Date('2026-11-14T16:00:00');

    const now = new Date();
    const diff = eventDate - now;

    if (diff <= 0) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
        return;
    }

    countdown.value = {
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((diff / (1000 * 60)) % 60),
        seconds: Math.floor((diff / 1000) % 60),
    };
}

onMounted(() => {
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
});
onUnmounted(() => { if (countdownInterval) clearInterval(countdownInterval); });

// ── Formatted date ───────────────────────────────────────────────
const eventDateFormatted = computed(() => {
    if (!props.settings?.event_datetime) return 'Fecha por anunciar';
    return new Date(props.settings.event_datetime).toLocaleDateString('es-MX', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
});

const eventTimeFormatted = computed(() => {
    if (!props.settings?.event_datetime) return '';
    return new Date(props.settings.event_datetime).toLocaleTimeString('es-MX', {
        hour: '2-digit', minute: '2-digit',
    });
});

// ── Ceremony / Celebration formatted dates ─────────────────────
const ceremonyDateFormatted = computed(() => {
    if (!props.settings?.ceremony_datetime) return 'Fecha por anunciar';
    return new Date(props.settings.ceremony_datetime).toLocaleDateString('es-MX', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
});
const ceremonyTimeFormatted = computed(() => {
    if (!props.settings?.ceremony_datetime) return '';
    return new Date(props.settings.ceremony_datetime).toLocaleTimeString('es-MX', {
        hour: '2-digit', minute: '2-digit',
    });
});
const celebrationDateFormatted = computed(() => {
    if (!props.settings?.celebration_datetime) return 'Fecha por anunciar';
    return new Date(props.settings.celebration_datetime).toLocaleDateString('es-MX', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
});
const celebrationTimeFormatted = computed(() => {
    if (!props.settings?.celebration_datetime) return '';
    return new Date(props.settings.celebration_datetime).toLocaleTimeString('es-MX', {
        hour: '2-digit', minute: '2-digit',
    });
});

// ── Google Maps / Waze links ─────────────────────────────────────
function makeMapsUrl(lat, lng, addr) {
    if (lat && lng) return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
    const encoded = encodeURIComponent(addr || '');
    return `https://www.google.com/maps/search/?api=1&query=${encoded}`;
}
function makeWazeUrl(lat, lng) {
    if (lat && lng) return `https://waze.com/ul?ll=${lat},${lng}&navigate=yes`;
    return 'https://waze.com/ul';
}

const ceremonyMapsUrl = computed(() => makeMapsUrl(props.settings?.ceremony_lat, props.settings?.ceremony_lng, props.settings?.ceremony_address));
const ceremonyWazeUrl = computed(() => makeWazeUrl(props.settings?.ceremony_lat, props.settings?.ceremony_lng));
const celebrationMapsUrl = computed(() => makeMapsUrl(props.settings?.celebration_lat, props.settings?.celebration_lng, props.settings?.celebration_address));
const celebrationWazeUrl = computed(() => makeWazeUrl(props.settings?.celebration_lat, props.settings?.celebration_lng));

// Keep old mapsUrl for backwards compat
const mapsUrl = computed(() => makeMapsUrl(props.settings?.venue_lat, props.settings?.venue_lng, props.settings?.venue_address));
const wazeUrl = computed(() => makeWazeUrl(props.settings?.venue_lat, props.settings?.venue_lng));

// ── RSVP State ───────────────────────────────────────────────────
const rsvpQuery = ref('');
const rsvpResults = ref([]);
const rsvpLoading = ref(false);
const selectedGuest = ref(null);
const rsvpSuccess = ref(false);
let debounceTimer = null;

async function searchGuests() {
    const q = rsvpQuery.value.trim();
    if (q.length < 2) { rsvpResults.value = []; return; }

    rsvpLoading.value = true;
    try {
        const { data } = await axios.get('/rsvp/buscar', { params: { q } });
        rsvpResults.value = data;
    } catch {
        rsvpResults.value = [];
    } finally {
        rsvpLoading.value = false;
    }
}

function onRsvpInput() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(searchGuests, 350);
}

function selectGuest(guest) {
    if (guest.rsvp_status !== 'pending') {
        alert(guest.rsvp_status === 'confirmed'
            ? 'Este invitado ya confirmó su asistencia.'
            : 'Este invitado ya declinó la invitación.');
        return;
    }
    selectedGuest.value = guest;
    rsvpQuery.value = guest.full_name;
    rsvpResults.value = [];

    // Resetear formulario
    attending.value = null;
    confirmedPasses.value = 1;
    confirmedByName.value = '';
    rsvpMessage.value = '';
}

function resetRsvp() {
    selectedGuest.value = null;
    rsvpQuery.value = '';
    attending.value = null;
}

// ── RSVP Form ────────────────────────────────────────────────────
const attending = ref(null);
const confirmedPasses = ref(1);
const confirmedByName = ref('');
const rsvpMessage = ref('');

const rsvpForm = useForm({
    attending: null,
    confirmed_passes: 1,
    confirmed_by_name: '',
    rsvp_message: '',
});

function submitRsvp() {
    if (!selectedGuest.value || attending.value === null) return;

    rsvpForm.attending = attending.value;
    rsvpForm.confirmed_passes = attending.value ? confirmedPasses.value : 0;
    rsvpForm.confirmed_by_name = confirmedByName.value;
    rsvpForm.rsvp_message = rsvpMessage.value;

    rsvpForm.post(route('rsvp.confirm', selectedGuest.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            rsvpSuccess.value = true;
            selectedGuest.value = null;
            rsvpQuery.value = '';
            attending.value = null;
            rsvpForm.reset();
        },
    });
}

// ── Dress Code ──────────────────────────────────────────────────
const viewMode = ref('grid');

const womenItems = computed(() => [
    { label: 'Vestido', imageUrl: props.settings?.dress_code_women_dress_url ?? null, description: props.settings?.dress_code_women_dress_desc ?? '', fallbackIcon: '👗' },
    { label: 'Zapatos', imageUrl: props.settings?.dress_code_women_shoes_url ?? null, description: props.settings?.dress_code_women_shoes_desc ?? '', fallbackIcon: '👢' },
    { label: 'Accesorios', imageUrl: props.settings?.dress_code_women_accessories_url ?? null, description: props.settings?.dress_code_women_accessories_desc ?? '', fallbackIcon: '💍' },
    { label: 'Otro', imageUrl: props.settings?.dress_code_women_other_url ?? null, description: props.settings?.dress_code_women_other_desc ?? '', fallbackIcon: '✨' },
]);

const menItems = computed(() => [
    { label: 'Traje', imageUrl: props.settings?.dress_code_men_suit_url ?? null, description: props.settings?.dress_code_men_suit_desc ?? '', fallbackIcon: '🤵' },
    { label: 'Zapatos', imageUrl: props.settings?.dress_code_men_shoes_url ?? null, description: props.settings?.dress_code_men_shoes_desc ?? '', fallbackIcon: '👞' },
    { label: 'Accesorios', imageUrl: props.settings?.dress_code_men_accessories_url ?? null, description: props.settings?.dress_code_men_accessories_desc ?? '', fallbackIcon: '🎩' },
    { label: 'Otro', imageUrl: props.settings?.dress_code_men_other_url ?? null, description: props.settings?.dress_code_men_other_desc ?? '', fallbackIcon: '✨' },
]);

// ── FAQ Accordion ────────────────────────────────────────────────
const activeFaq = ref(null);
function toggleFaq(id) { activeFaq.value = activeFaq.value === id ? null : id; }

// ── Gallery ──────────────────────────────────────────────────────
const galleryForm = useForm({
    image: null,
    uploader_name: '',
});
const galleryPreview = ref(null);
const gallerySuccess = ref(false);

function onGalleryFile(e) {
    const file = e.target.files[0];
    if (!file) return;
    galleryForm.image = file;
    galleryPreview.value = URL.createObjectURL(file);
}

function submitGallery() {
    galleryForm.post('/galeria', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            galleryForm.reset();
            galleryPreview.value = null;
            gallerySuccess.value = true;
            setTimeout(() => { gallerySuccess.value = false; }, 5000);
        },
    });
}

// ── Lightbox ─────────────────────────────────────────────────────
const lightboxImage = ref(null);
function openLightbox(url) { lightboxImage.value = url; }
function closeLightbox() { lightboxImage.value = null; }

// ── WhatsApp fallback ────────────────────────────────────────────
const whatsappLink = 'https://wa.me/?text=Hola%2C%20no%20aparecemos%20en%20la%20lista%20de%20invitados%20de%20la%20boda.%20%C2%BFNos%20pueden%20ayudar%3F';
</script>

<template>
    <div class="bg-arena min-h-screen text-cuero">

        <!-- ════════════════ NAVBAR ════════════════ -->
        <nav
            :class="[
                'fixed top-0 z-50 w-full transition-all duration-500',
                isScrolled
                    ? 'bg-arena/95 backdrop-blur-md shadow-lg shadow-cuero/5 py-2'
                    : 'bg-transparent py-4'
            ]"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between">
                <div class="flex items-center gap-5"> 
                    <img src="/img/logo.png" alt="Logo" class="h-8 pb-1" />
                    <button @click="scrollTo('hero')" class="font-script text-2xl md:text-3xl text-dorado transition-colors hover:text-dorado-dark">
                        Boda
                    </button>
                </div>  

                <!-- Desktop nav -->
                <div class="hidden md:flex items-center gap-1 text-sm font-medium">
                    <button @click="scrollTo('historia')" class="px-4 py-2 rounded-full text-cuero/80 hover:text-cuero hover:bg-arena-dark/30 transition-all">Historia</button>
                    <button @click="scrollTo('evento')" class="px-4 py-2 rounded-full text-cuero/80 hover:text-cuero hover:bg-arena-dark/30 transition-all">Evento</button>
                    <button @click="scrollTo('dresscode')" class="px-4 py-2 rounded-full text-cuero/80 hover:text-cuero hover:bg-arena-dark/30 transition-all">Dress Code</button>
                    <button @click="scrollTo('rsvp')" class="px-4 py-2 rounded-full text-cuero/80 hover:text-cuero hover:bg-arena-dark/30 transition-all">RSVP</button>
                    <button @click="scrollTo('faq')" class="px-4 py-2 rounded-full text-cuero/80 hover:text-cuero hover:bg-arena-dark/30 transition-all">FAQ</button>
                    <button @click="scrollTo('galeria')" class="px-4 py-2 rounded-full text-cuero/80 hover:text-cuero hover:bg-arena-dark/30 transition-all">Galería</button>
                </div>

                <!-- Mobile hamburger -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-cuero">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile menu -->
            <div v-if="mobileMenuOpen" class="md:hidden bg-white backdrop-blur-md border-t border-cuero/10 px-4 pb-4 animate-fade-in shadow-lg">
                <div class="flex flex-col gap-1 pt-2">
                    <button @click="scrollTo('historia')" class="text-left px-4 py-3 rounded-lg text-cuero/80 hover:text-cuero hover:bg-arena-dark/30">Nuestra Historia</button>
                    <button @click="scrollTo('evento')" class="text-left px-4 py-3 rounded-lg text-cuero/80 hover:text-cuero hover:bg-arena-dark/30">El Evento</button>
                    <button @click="scrollTo('dresscode')" class="text-left px-4 py-3 rounded-lg text-cuero/80 hover:text-cuero hover:bg-arena-dark/30">Dress Code</button>
                    <button @click="scrollTo('rsvp')" class="text-left px-4 py-3 rounded-lg text-cuero/80 hover:text-cuero hover:bg-arena-dark/30">Confirmar Asistencia</button>
                    <button @click="scrollTo('faq')" class="text-left px-4 py-3 rounded-lg text-cuero/80 hover:text-cuero hover:bg-arena-dark/30">Preguntas Frecuentes</button>
                    <button @click="scrollTo('galeria')" class="text-left px-4 py-3 rounded-lg text-cuero/80 hover:text-cuero hover:bg-arena-dark/30">Galería</button>
                </div>
            </div>
        </nav>

        <!-- ════════════════ HERO ════════════════ -->
        <section id="hero" class="relative min-h-screen flex items-center justify-center overflow-hidden">
            <!-- Background image with overlay -->
            <div class="absolute inset-0 z-0">
                <div class="w-full h-full bg-gradient-to-br from-cuero via-olivo-dark to-mezclilla"></div>
                <!-- Overlays -->
                <div class="absolute inset-0 bg-gradient-to-b from-cuero/60 via-cuero/30 to-cuero/70"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-arena/90 via-transparent to-transparent"></div>
            </div>

            <!-- Hero content -->
            <div class="relative z-10 text-center px-4 max-w-4xl mx-auto animate-fade-in">
                <!-- Decorative top line -->
                <div class="flex items-center justify-center gap-4 mb-8">
                    <div class="h-px w-12 bg-dorado/60"></div>
                    <span class="text-dorado text-sm tracking-[0.3em] uppercase font-medium">Nos casamos</span>
                    <div class="h-px w-12 bg-dorado/60"></div>
                </div>

                <!-- Names -->
                <h1 class="font-script text-5xl sm:text-6xl md:text-7xl lg:text-8xl text-white drop-shadow-lg mb-6 leading-tight">
                    <span class="block">José Rodríguez</span>
                    <span class="text-dorado text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-slab font-normal">&</span>
                    <span class="block">Elizabeth Mendoza</span>
                </h1>

                <!-- Date -->
                <div class="inline-block bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-8 py-3 mb-10">
                    <p class="text-white/90 font-slab text-lg md:text-xl tracking-wide capitalize">{{ eventDateFormatted }}</p>
                </div>

                <!-- Countdown -->
                <div class="grid grid-cols-4 gap-3 sm:gap-5 max-w-lg mx-auto mb-12">
                    <div v-for="(unit, key) in [
                        { label: 'Días', value: countdown.days },
                        { label: 'Horas', value: countdown.hours },
                        { label: 'Minutos', value: countdown.minutes },
                        { label: 'Segundos', value: countdown.seconds },
                    ]" :key="key"
                    class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-4 sm:p-5">
                        <span class="block font-slab text-2xl sm:text-3xl md:text-4xl text-white font-bold">{{ String(unit.value).padStart(2, '0') }}</span>
                        <span class="block text-white/60 text-xs sm:text-sm mt-1 tracking-wide uppercase">{{ unit.label }}</span>
                    </div>
                </div>

                <!-- CTA Button -->
                <button @click="scrollTo('rsvp')"
                    class="inline-flex items-center gap-2 bg-dorado hover:bg-dorado-dark text-white font-slab font-bold px-10 py-4 rounded-full text-lg transition-all duration-300 shadow-lg shadow-dorado/30 hover:shadow-xl hover:shadow-dorado/40 hover:scale-105 active:scale-95">
                    Confirmar Asistencia
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>

            <!-- Scroll indicator -->
            <button @click="scrollTo('historia')" class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 animate-pulse-soft">
                <svg class="w-8 h-8 text-dorado" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            </button>
        </section>

        <!-- ════════════════ NUESTRA HISTORIA ════════════════ -->
        <section id="historia" class="py-24 md:py-32 px-4 bg-arena">
            <div class="max-w-5xl mx-auto">
                <!-- Section header -->
                <div class="text-center mb-16">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="h-px w-10 bg-dorado"></div>
                        <span class="text-olivo text-xs tracking-[0.3em] uppercase font-medium">Nuestra Historia</span>
                        <div class="h-px w-10 bg-dorado"></div>
                    </div>
                    <h2 class="font-script text-4xl sm:text-5xl md:text-6xl text-cuero">Cómo nos conocimos</h2>
                </div>

                <!-- Story block 1: Cómo nos conocimos -->
                <div class="grid md:grid-cols-2 gap-10 md:gap-16 items-center mb-20">
                    <div class="order-2 md:order-1 animate-slide-up">
                        <p class="text-cuero/80 text-lg leading-relaxed">
                            {{ settings?.how_we_met_story || 'Aquí puedes escribir tu historia. Cómo se conocieron, ese momento mágico en que sus miradas se cruzaron y supieron que algo especial estaba por comenzar.' }}
                        </p>
                    </div>
                    <div class="order-1 md:order-2 animate-slide-up">
                        <div class="relative">
                            <div class="w-full h-72 md:h-96 rounded-2xl bg-gradient-to-br from-olivo/20 to-cuero/10 border border-cuero/10 overflow-hidden shadow-lg">
                                <img v-if="settings?.how_we_met_photo_path" :src="'/storage/' + settings.how_we_met_photo_path" alt="Cómo nos conocimos" class="w-full h-full object-cover" />
                                <div v-else class="flex items-center justify-center h-full text-cuero/30 font-script text-6xl">Foto 1</div>
                            </div>
                            <div class="absolute -bottom-3 -right-3 w-full h-full rounded-2xl border-2 border-dorado/40 -z-10"></div>
                        </div>
                    </div>
                </div>

                <!-- Story block 2: La propuesta -->
                <div class="grid md:grid-cols-2 gap-10 md:gap-16 items-center">
                    <div class="animate-slide-up">
                        <div class="relative">
                            <div class="w-full h-72 md:h-96 rounded-2xl bg-gradient-to-bl from-olivo/20 to-cuero/10 border border-cuero/10 overflow-hidden shadow-lg">
                                <img v-if="settings?.proposal_photo_path" :src="'/storage/' + settings.proposal_photo_path" alt="La propuesta" class="w-full h-full object-cover" />
                                <div v-else class="flex items-center justify-center h-full text-cuero/30 font-script text-6xl">Foto 2</div>
                            </div>
                            <div class="absolute -bottom-3 -left-3 w-full h-full rounded-2xl border-2 border-dorado/40 -z-10"></div>
                        </div>
                    </div>
                    <div class="animate-slide-up">
                        <p class="text-cuero/80 text-lg leading-relaxed">
                            {{ settings?.proposal_story || 'Y luego vino la propuesta... Un momento que cambió sus vidas para siempre. Rodeados del paisaje que tanto aman, con el corazón latiendo fuerte, la pregunta que lo selló todo.' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ════════════════ EL EVENTO ════════════════ -->
        <section id="evento" class="py-24 md:py-32 px-4 bg-olivo/5">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-16">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="h-px w-10 bg-dorado"></div>
                        <span class="text-olivo text-xs tracking-[0.3em] uppercase font-medium">Cuándo & Dónde</span>
                        <div class="h-px w-10 bg-dorado"></div>
                    </div>
                    <h2 class="font-script text-4xl sm:text-5xl md:text-6xl text-cuero">El Gran Día</h2>
                </div>

                <div class="grid md:grid-cols-2 gap-8 md:gap-16">
                    <!-- Ceremonia -->
                    <div class="bg-white/70 backdrop-blur-sm rounded-3xl p-8 md:p-10 border border-cuero/10 shadow-lg shadow-cuero/5 animate-slide-up">
                        <div class="w-14 h-14 bg-olivo/10 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-olivo" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <h3 class="font-slab text-xl text-cuero mb-2">{{ settings?.ceremony_title || 'La Ceremonia' }}</h3>
                        <p class="text-cuero/60 text-sm mb-1">{{ ceremonyDateFormatted }}</p>
                        <p class="text-cuero/60 text-sm mb-4" v-if="settings?.ceremony_datetime">{{ ceremonyTimeFormatted }} hrs</p>
                        <p class="text-cuero/70 mb-1">{{ settings?.ceremony_address || 'Por definir' }}</p>
                        <!-- Ceremony photo -->
                        <div v-if="settings?.ceremony_photo_path" class="mb-4 rounded-xl overflow-hidden">
                            <img :src="'/storage/' + settings.ceremony_photo_path" alt="Ceremonia" class="w-full h-40 object-cover rounded-xl" />
                        </div>
                        <div class="flex flex-wrap gap-3" v-if="settings?.ceremony_address">
                            <a :href="ceremonyMapsUrl" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 bg-mezclilla hover:bg-mezclilla-light text-white px-5 py-3 rounded-xl text-sm font-medium transition-all hover:shadow-lg">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                                Google Maps
                            </a>
                            <a :href="ceremonyWazeUrl" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 bg-cuero hover:bg-cuero-light text-white px-5 py-3 rounded-xl text-sm font-medium transition-all hover:shadow-lg">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 22h20L12 2zm0 4l7 14H5l7-14z"/></svg>
                                Waze
                            </a>
                        </div>
                    </div>

                    <!-- Celebración -->
                    <div class="bg-white/70 backdrop-blur-sm rounded-3xl p-8 md:p-10 border border-cuero/10 shadow-lg shadow-cuero/5 animate-slide-up" style="animation-delay: 0.2s">
                        <div class="w-14 h-14 bg-dorado/10 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-dorado" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A2.702 2.702 0 003 15.546M21 12.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A2.702 2.702 0 003 12.546"/>
                            </svg>
                        </div>
                        <h3 class="font-slab text-xl text-cuero mb-2">{{ settings?.celebration_title || 'La Celebración' }}</h3>
                        <p class="text-cuero/60 text-sm mb-1">{{ celebrationDateFormatted }}</p>
                        <p class="text-cuero/60 text-sm mb-4" v-if="settings?.celebration_datetime">{{ celebrationTimeFormatted }} hrs</p>
                        <p class="text-cuero/70 mb-1">{{ settings?.celebration_address || 'Por definir' }}</p>
                        <!-- Celebration photo -->
                        <div v-if="settings?.celebration_photo_path" class="mb-4 rounded-xl overflow-hidden">
                            <img :src="'/storage/' + settings.celebration_photo_path" alt="Celebración" class="w-full h-40 object-cover rounded-xl" />
                        </div>
                        <div class="flex flex-wrap gap-3" v-if="settings?.celebration_address">
                            <a :href="celebrationMapsUrl" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 bg-mezclilla hover:bg-mezclilla-light text-white px-5 py-3 rounded-xl text-sm font-medium transition-all hover:shadow-lg">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                                Google Maps
                            </a>
                            <a :href="celebrationWazeUrl" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 bg-cuero hover:bg-cuero-light text-white px-5 py-3 rounded-xl text-sm font-medium transition-all hover:shadow-lg">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 22h20L12 2zm0 4l7 14H5l7-14z"/></svg>
                                Waze
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ════════════════ DRESS CODE ════════════════ -->
        <section id="dresscode" class="py-24 md:py-32 px-4 bg-arena">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-16">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="h-px w-10 bg-dorado"></div>
                        <span class="text-olivo text-xs tracking-[0.3em] uppercase font-medium">Dress Code</span>
                        <div class="h-px w-10 bg-dorado"></div>
                    </div>
                    <h2 class="font-script text-4xl sm:text-5xl md:text-6xl text-cuero">Código de Vestimenta</h2>
                    <p class="text-cuero/60 mt-4 max-w-xl mx-auto">Western Chic — Vaquero Formal</p>
                </div>

                <div class="bg-white/70 backdrop-blur-sm rounded-3xl p-8 md:p-12 border border-cuero/10 shadow-lg shadow-cuero/5">
                    <!-- Texto general -->
                    <p class="text-cuero/80 text-lg leading-relaxed mb-10 text-center max-w-3xl mx-auto">
                        {{ settings?.dress_code_general || 'Queremos que te sientas espectacular. Te invitamos a unirte a nuestra celebración con un look Western Chic: elegante, sofisticado, con ese toque vaquero que tanto amamos. La paleta sugerida son tonos tierra, neutros cálidos, verdes olivo sutiles y toques de dorado.' }}
                    </p>

                    <!-- Imagen de referencia general -->
                    <div v-if="settings?.dress_code_image_url" class="mb-10">
                        <img
                            :src="settings.dress_code_image_url"
                            alt="Referencia de vestimenta"
                            class="w-full max-w-2xl mx-auto rounded-2xl object-cover max-h-96 shadow-lg"
                        />
                    </div>

                    <!-- Toggle de vista -->
                    <div class="flex justify-center gap-2 mb-8">
                        <button
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-mezclilla text-white' : 'bg-white text-cuero/50 hover:bg-arena'"
                            class="p-2 rounded-lg transition-colors border border-cuero/10"
                            aria-label="Vista de cuadrícula"
                        >
                            <Squares2X2Icon class="w-5 h-5" />
                        </button>
                        <button
                            @click="viewMode = 'carousel'"
                            :class="viewMode === 'carousel' ? 'bg-mezclilla text-white' : 'bg-white text-cuero/50 hover:bg-arena'"
                            class="p-2 rounded-lg transition-colors border border-cuero/10"
                            aria-label="Vista de carrusel"
                        >
                            <Square3Stack3DIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Bloques Ellas / Ellos -->
                    <div class="grid md:grid-cols-2 gap-10">
                        <!-- Damas -->
                        <div class="text-center">
                            <div class="inline-flex items-center gap-3 mb-6">
                                <svg class="w-6 h-6 text-dorado" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                                <h3 class="font-slab text-xl text-cuero">Damas</h3>
                            </div>
                            <DressCodeGrid v-if="viewMode === 'grid'" :items="womenItems" />
                            <DressCodeCarousel v-else :items="womenItems" />
                        </div>

                        <!-- Caballeros -->
                        <div class="text-center">
                            <div class="inline-flex items-center gap-3 mb-6">
                                <svg class="w-6 h-6 text-dorado" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                <h3 class="font-slab text-xl text-cuero">Caballeros</h3>
                            </div>
                            <DressCodeGrid v-if="viewMode === 'grid'" :items="menItems" />
                            <DressCodeCarousel v-else :items="menItems" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ════════════════ RSVP ════════════════ -->
        <section id="rsvp" class="py-24 md:py-32 px-4 bg-olivo/5">
            <div class="max-w-2xl mx-auto">
                <div class="text-center mb-16">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="h-px w-10 bg-dorado"></div>
                        <span class="text-olivo text-xs tracking-[0.3em] uppercase font-medium">RSVP</span>
                        <div class="h-px w-10 bg-dorado"></div>
                    </div>
                    <h2 class="font-script text-4xl sm:text-5xl md:text-6xl text-cuero">Confirma tu Asistencia</h2>
                    <p class="text-cuero/60 mt-4" v-if="settings?.rsvp_deadline">
                        Fecha límite: {{ new Date(settings.rsvp_deadline).toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </p>
                </div>

                <!-- Success state -->
                <div v-if="rsvpSuccess" class="text-center bg-olivo/10 border border-olivo/20 rounded-3xl p-10 animate-fade-in">
                    <div class="w-16 h-16 bg-olivo/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-olivo" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="font-slab text-xl text-cuero mb-2">¡Gracias por confirmar!</h3>
                    <p class="text-cuero/60">Hemos registrado tu respuesta. ¡Nos vemos en la boda!</p>
                </div>

                <!-- Search / Form -->
                <div v-else class="bg-white/80 backdrop-blur-sm rounded-3xl p-8 md:p-10 border border-cuero/10 shadow-lg shadow-cuero/5">
                    <!-- Phase 1: Search -->
                    <div v-if="!selectedGuest">
                        <label class="block font-slab text-cuero mb-2 text-lg">Busca tu nombre</label>
                        <p class="text-cuero/50 text-sm mb-4">Escribe tu nombre completo como aparece en la invitación</p>
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-cuero/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input
                                v-model="rsvpQuery"
                                @input="onRsvpInput"
                                type="text"
                                placeholder="Ej. María García López"
                                class="w-full pl-12 pr-4 py-4 rounded-2xl border-2 border-cuero/20 bg-white text-cuero placeholder-cuero/30 focus:border-dorado focus:ring-2 focus:ring-dorado/20 transition-all outline-none text-lg"
                            />
                        </div>

                        <!-- Loading -->
                        <div v-if="rsvpLoading" class="flex items-center justify-center gap-2 mt-4 text-cuero/50">
                            <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Buscando...
                        </div>

                        <!-- Results -->
                        <div v-if="rsvpResults.length > 0" class="mt-4 space-y-2">
                            <div
                                v-for="guest in rsvpResults" :key="guest.id"
                                @click="selectGuest(guest)"
                                class="flex items-center justify-between p-4 rounded-xl border border-cuero/10 bg-arena hover:bg-arena-dark/50 hover:border-dorado/30 cursor-pointer transition-all group"
                            >
                                <div>
                                    <p class="font-medium text-cuero group-hover:text-cuero">{{ guest.full_name }}</p>
                                    <p class="text-xs text-cuero/50">{{ guest.allowed_passes }} pase(s)</p>
                                </div>
                                <span
                                    v-if="guest.rsvp_status === 'confirmed'"
                                    class="text-xs bg-olivo/10 text-olivo px-3 py-1 rounded-full font-medium"
                                >Confirmado</span>
                                <span
                                    v-else-if="guest.rsvp_status === 'declined'"
                                    class="text-xs bg-red-100 text-red-500 px-3 py-1 rounded-full font-medium"
                                >Declinado</span>
                                <svg v-else class="w-5 h-5 text-cuero/30 group-hover:text-dorado transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>

                        <!-- No results -->
                        <div v-if="rsvpQuery.length >= 2 && !rsvpLoading && rsvpResults.length === 0" class="mt-6 text-center">
                            <p class="text-cuero/60 mb-4">No encontramos tu nombre en la lista.</p>
                            <a :href="whatsappLink" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-5 py-3 rounded-xl text-sm font-medium transition-all">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.652-2.059-.174-.297-.02-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Contáctanos por WhatsApp
                            </a>
                        </div>
                    </div>

                    <!-- Phase 2: Confirmation Form -->
                    <div v-else class="animate-fade-in">
                        <button @click="resetRsvp" class="flex items-center gap-2 text-cuero/50 hover:text-cuero mb-6 transition-colors text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Buscar otro invitado
                        </button>

                        <p class="font-slab text-xl text-cuero mb-1">¡Hola, {{ selectedGuest.full_name }}!</p>
                        <p class="text-cuero/60 mb-8">Tienes <strong class="text-cuero">{{ selectedGuest.allowed_passes }}</strong> pase(s) asignado(s).</p>

                        <!-- Attending toggle -->
                        <label class="block font-medium text-cuero mb-3">¿Asistirás?</label>
                        <div class="grid grid-cols-2 gap-3 mb-6">
                            <button
                                @click="attending = true"
                                :class="attending === true
                                    ? 'bg-olivo text-white border-olivo shadow-lg shadow-olivo/20'
                                    : 'bg-white text-cuero/60 border-cuero/20 hover:border-olivo/50'"
                                class="p-4 rounded-2xl border-2 transition-all text-center font-medium"
                            >
                                <span class="text-2xl block mb-1">🤠</span>
                                Sí, asistiré
                            </button>
                            <button
                                @click="attending = false"
                                :class="attending === false
                                    ? 'bg-cuero text-white border-cuero shadow-lg shadow-cuero/20'
                                    : 'bg-white text-cuero/60 border-cuero/20 hover:border-cuero/50'"
                                class="p-4 rounded-2xl border-2 transition-all text-center font-medium"
                            >
                                <span class="text-2xl block mb-1">😔</span>
                                No podré asistir
                            </button>
                        </div>

                        <!-- Confirmed passes -->
                        <div v-if="attending" class="mb-5">
                            <label class="block font-medium text-cuero mb-2">¿Cuántos pases usarás?</label>
                            <select v-model.number="confirmedPasses"
                                class="w-full px-4 py-3 rounded-2xl border-2 border-cuero/20 bg-white text-cuero focus:border-dorado focus:ring-2 focus:ring-dorado/20 outline-none transition-all">
                                <option v-for="n in selectedGuest.allowed_passes" :key="n" :value="n">{{ n }} pase(s)</option>
                            </select>
                        </div>

                        <!-- Name of person confirming -->
                        <div class="mb-5">
                            <label class="block font-medium text-cuero mb-2">¿Quién confirma?</label>
                            <input v-model="confirmedByName" type="text" placeholder="Tu nombre"
                                class="w-full px-4 py-3 rounded-2xl border-2 border-cuero/20 bg-white text-cuero placeholder-cuero/30 focus:border-dorado focus:ring-2 focus:ring-dorado/20 outline-none transition-all" />
                        </div>

                        <!-- Message -->
                        <div class="mb-8">
                            <label class="block font-medium text-cuero mb-2">Mensaje para los novios <span class="text-cuero/40 font-normal">(opcional)</span></label>
                            <textarea v-model="rsvpMessage" rows="3" placeholder="Déjanos un mensaje, felicitación o nota..."
                                class="w-full px-4 py-3 rounded-2xl border-2 border-cuero/20 bg-white text-cuero placeholder-cuero/30 focus:border-dorado focus:ring-2 focus:ring-dorado/20 outline-none transition-all resize-none"></textarea>
                        </div>

                        <!-- Submit -->
                        <button
                            @click="submitRsvp"
                            :disabled="attending === null || rsvpForm.processing"
                            class="w-full py-4 rounded-2xl font-slab font-bold text-lg transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="attending === false
                                ? 'bg-cuero hover:bg-cuero-light text-white shadow-lg shadow-cuero/20'
                                : 'bg-dorado hover:bg-dorado-dark text-white shadow-lg shadow-dorado/20'"
                        >
                            <span v-if="rsvpForm.processing" class="inline-flex items-center gap-2">
                                <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Enviando...
                            </span>
                            <span v-else>{{ attending ? 'Confirmar Asistencia 🤠' : 'Confirmar que no asistiré' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ════════════════ FAQ ════════════════ -->
        <section id="faq" class="py-24 md:py-32 px-4 bg-arena">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-16">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="h-px w-10 bg-dorado"></div>
                        <span class="text-olivo text-xs tracking-[0.3em] uppercase font-medium">FAQ</span>
                        <div class="h-px w-10 bg-dorado"></div>
                    </div>
                    <h2 class="font-script text-4xl sm:text-5xl md:text-6xl text-cuero">Preguntas Frecuentes</h2>
                </div>

                <div v-if="faqs && faqs.length > 0" class="space-y-3">
                    <div
                        v-for="faq in faqs" :key="faq.id"
                        class="bg-white/80 rounded-2xl border border-cuero/10 overflow-hidden transition-all duration-300"
                        :class="{ 'shadow-md': activeFaq === faq.id }"
                    >
                        <button
                            @click="toggleFaq(faq.id)"
                            class="w-full flex items-center justify-between p-5 md:p-6 text-left"
                        >
                            <span class="font-slab text-cuero pr-4">{{ faq.question }}</span>
                            <svg
                                class="w-5 h-5 text-dorado flex-shrink-0 transition-transform duration-300"
                                :class="{ 'rotate-180': activeFaq === faq.id }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            v-if="activeFaq === faq.id"
                            class="px-5 md:px-6 pb-5 md:pb-6 text-cuero/70 leading-relaxed animate-fade-in"
                            v-html="faq.answer.replace(/\n/g, '<br>')"
                        ></div>
                    </div>
                </div>

                <!-- Default FAQs if none from DB -->
                <div v-else class="space-y-3">
                    <div v-for="(faq, i) in [
                        { q: '¿Hay estacionamiento o valet parking?', a: 'Sí, el lugar cuenta con estacionamiento privado gratuito y servicio de valet parking durante todo el evento.' },
                        { q: '¿Se permiten niños?', a: 'Amamos a sus pequeños, pero hemos decidido celebrar nuestra boda en una atmósfera exclusiva para adultos. ¡Esperamos que comprendan y puedan disfrutar de la noche libres de preocupaciones!' },
                        { q: '¿Puedo llevar a alguien más?', a: 'Los pases asignados en tu confirmación de asistencia están calculados de manera estricta conforme al cupo del lugar. No es posible añadir pases adicionales.' },
                        { q: '¿Cuál es la fecha límite para confirmar?', a: 'Agradecemos tu confirmación antes de la fecha límite indicada para asegurar tu lugar en el banquete.' },
                        { q: '¿Qué pasa si confirmo y luego no puedo asistir?', a: 'Te pedimos que nos avises con la mayor anticipación posible a través de la misma página o contactando directamente a los novios para poder reajustar los espacios.' },
                    ]" :key="i"
                        class="bg-white/80 rounded-2xl border border-cuero/10 overflow-hidden transition-all duration-300"
                        :class="{ 'shadow-md': activeFaq === i }"
                    >
                        <button @click="toggleFaq(i)" class="w-full flex items-center justify-between p-5 md:p-6 text-left">
                            <span class="font-slab text-cuero pr-4">{{ faq.q }}</span>
                            <svg class="w-5 h-5 text-dorado flex-shrink-0 transition-transform duration-300" :class="{ 'rotate-180': activeFaq === i }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div v-if="activeFaq === i" class="px-5 md:px-6 pb-5 md:pb-6 text-cuero/70 leading-relaxed animate-fade-in">
                            {{ faq.a }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ════════════════ GALERÍA ════════════════ -->
        <section id="galeria" class="py-24 md:py-32 px-4 bg-olivo/5">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-16">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="h-px w-10 bg-dorado"></div>
                        <span class="text-olivo text-xs tracking-[0.3em] uppercase font-medium">Galería</span>
                        <div class="h-px w-10 bg-dorado"></div>
                    </div>
                    <h2 class="font-script text-4xl sm:text-5xl md:text-6xl text-cuero">Momentos Inolvidables</h2>
                    <p class="text-cuero/60 mt-4">Comparte tus fotos con nosotros</p>
                </div>

                <!-- Upload area -->
                <div class="bg-white/80 backdrop-blur-sm rounded-3xl p-8 md:p-10 border border-cuero/10 shadow-lg mb-10">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-dorado/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-dorado" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="font-slab text-xl text-cuero mb-2">📸 Sube tus fotos</h3>
                        <p class="text-cuero/50 text-sm mb-6">Las fotos serán revisadas antes de publicarse</p>

                        <!-- Drag & Drop + Input -->
                        <label
                            class="block border-2 border-dashed border-cuero/20 hover:border-dorado/50 rounded-2xl p-8 md:p-10 cursor-pointer transition-all bg-arena/50 hover:bg-arena-dark/30"
                            @dragover.prevent
                            @drop.prevent="(e) => { if (e.dataTransfer.files[0]) { galleryForm.image = e.dataTransfer.files[0]; galleryPreview = URL.createObjectURL(e.dataTransfer.files[0]); } }"
                        >
                            <input type="file" accept="image/*" class="hidden" @change="onGalleryFile" />
                            <div v-if="!galleryPreview">
                                <svg class="w-10 h-10 text-cuero/30 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <span class="text-cuero/50 text-sm">Arrastra tu foto aquí o haz clic para seleccionar</span>
                            </div>
                            <img v-else :src="galleryPreview" class="max-h-48 mx-auto rounded-xl shadow-md" />
                        </label>

                        <!-- Uploader name -->
                        <div class="mt-4">
                            <input v-model="galleryForm.uploader_name" type="text" placeholder="Tu nombre (opcional)"
                                class="w-full max-w-sm px-4 py-3 rounded-2xl border-2 border-cuero/20 bg-white text-cuero placeholder-cuero/30 focus:border-dorado focus:ring-2 focus:ring-dorado/20 outline-none transition-all text-center" />
                        </div>

                        <!-- Upload button -->
                        <button
                            @click="submitGallery"
                            :disabled="!galleryForm.image || galleryForm.processing"
                            class="mt-4 inline-flex items-center gap-2 bg-dorado hover:bg-dorado-dark disabled:bg-cuero/20 disabled:text-cuero/30 text-white font-slab font-bold px-8 py-3 rounded-full transition-all duration-300 shadow-lg shadow-dorado/20 hover:shadow-xl disabled:shadow-none"
                        >
                            <span v-if="galleryForm.processing">
                                <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Subiendo...
                            </span>
                            <span v-else>Subir Foto</span>
                        </button>

                        <!-- Success -->
                        <div v-if="gallerySuccess" class="mt-4 text-olivo animate-fade-in bg-olivo/10 rounded-xl py-3 px-4 inline-block">
                            ✅ ¡Foto subida con éxito! Se mostrará al ser aprobada.
                        </div>
                    </div>
                </div>

                <!-- Gallery grid -->
                <div v-if="galleryPhotos && galleryPhotos.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 md:gap-4">
                    <div
                        v-for="photo in galleryPhotos" :key="photo.id"
                        @click="openLightbox('/storage/' + photo.image_path)"
                        class="relative aspect-square rounded-2xl overflow-hidden cursor-pointer group shadow-md hover:shadow-xl transition-all duration-300 hover:scale-[1.02]"
                    >
                        <img :src="'/storage/' + photo.image_path" alt="Foto de boda" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-cuero/0 group-hover:bg-cuero/20 transition-all duration-300 flex items-end p-3">
                            <span v-if="photo.uploader_name" class="text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-300">Por: {{ photo.uploader_name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Empty state before wedding -->
                <div v-else class="text-center text-cuero/40 py-10">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="font-slab">La galería se activará el día del evento</p>
                    <p class="text-sm mt-1">¡Vuelve para compartir tus momentos favoritos!</p>
                </div>
            </div>
        </section>

        <!-- ════════════════ LIGHTBOX ════════════════ -->
        <Teleport to="body">
            <div
                v-if="lightboxImage"
                @click="closeLightbox"
                class="fixed inset-0 z-[100] bg-cuero/95 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
            >
                <button @click="closeLightbox" class="absolute top-6 right-6 text-white/60 hover:text-white transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <img :src="lightboxImage" alt="Foto ampliada" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl" @click.stop />
            </div>
        </Teleport>

        <!-- ════════════════ FOOTER ════════════════ -->
        <footer class="bg-cuero text-white/70 py-12 px-4">
            <div class="max-w-5xl mx-auto text-center">
                <p class="font-script text-4xl text-dorado mb-4">Gracias</p>
                <p class="text-white/50 text-sm">Con amor, los novios</p>
                <div class="flex items-center justify-center gap-3 mt-6">
                    <div class="h-px w-8 bg-white/20"></div>
                    <span class="text-xs tracking-[0.3em] uppercase text-white/40">{{ new Date().getFullYear() }}</span>
                    <div class="h-px w-8 bg-white/20"></div>
                </div>
            </div>
        </footer>

    </div>
</template>
