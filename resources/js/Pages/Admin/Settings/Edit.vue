<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import { toDatetimeLocal, toDateInput } from '@/Composables/useEventDate';
import { PhotoIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Object,
    // URL efectiva del sitio de Canva (BD o config/wedding.php)
    canvaUrl: String,
    // Link efectivo de la mesa de regalos (BD o config/wedding.php)
    giftRegistryUrl: String,
    // Datos efectivos de la cuenta bancaria (para precargar el formulario)
    giftBank: Object,
});

// Link fijo que debe usar el botón «Confirmar asistencia» dentro de Canva:
// al abrir la invitación se recuerda quién es el invitado, así que este link
// le muestra su confirmación con su nombre ya cargado.
const rsvpUrl = `${window.location.origin}/rsvp`;

const photoUrl = (path) => path ? '/storage/' + path : null;

// Los helpers toDatetimeLocal / toDateInput viven en @/Composables/useEventDate,
// junto con la lógica de la zona horaria del evento (America/Mexico_City).

const form = useForm({
    _method: 'PUT',
    // Nuestra Historia
    how_we_met_story: props.settings?.how_we_met_story ?? '',
    how_we_met_photo: null,
    proposal_story: props.settings?.proposal_story ?? '',
    proposal_photo: null,
    // Cuándo y Dónde
    event_datetime: toDatetimeLocal(props.settings?.event_datetime),
    ceremony_title: props.settings?.ceremony_title ?? '',
    ceremony_datetime: toDatetimeLocal(props.settings?.ceremony_datetime),
    ceremony_address: props.settings?.ceremony_address ?? '',
    ceremony_lat: props.settings?.ceremony_lat ?? '',
    ceremony_lng: props.settings?.ceremony_lng ?? '',
    ceremony_photo: null,
    celebration_title: props.settings?.celebration_title ?? '',
    celebration_datetime: toDatetimeLocal(props.settings?.celebration_datetime),
    celebration_address: props.settings?.celebration_address ?? '',
    celebration_lat: props.settings?.celebration_lat ?? '',
    celebration_lng: props.settings?.celebration_lng ?? '',
    celebration_photo: null,
    // Portada y Dress Code general
    cover_photo: null,
    dress_code_image: null,
    // Código de Vestimenta
    dress_code_general: props.settings?.dress_code_general ?? '',
    dress_code_women_dress: null,
    dress_code_women_dress_desc: props.settings?.dress_code_women_dress_desc ?? '',
    dress_code_women_shoes: null,
    dress_code_women_shoes_desc: props.settings?.dress_code_women_shoes_desc ?? '',
    dress_code_women_accessories: null,
    dress_code_women_accessories_desc: props.settings?.dress_code_women_accessories_desc ?? '',
    dress_code_women_other: null,
    dress_code_women_other_desc: props.settings?.dress_code_women_other_desc ?? '',
    dress_code_men_suit: null,
    dress_code_men_suit_desc: props.settings?.dress_code_men_suit_desc ?? '',
    dress_code_men_shoes: null,
    dress_code_men_shoes_desc: props.settings?.dress_code_men_shoes_desc ?? '',
    dress_code_men_accessories: null,
    dress_code_men_accessories_desc: props.settings?.dress_code_men_accessories_desc ?? '',
    dress_code_men_other: null,
    dress_code_men_other_desc: props.settings?.dress_code_men_other_desc ?? '',
    // General
    rsvp_deadline: toDateInput(props.settings?.rsvp_deadline),
    // Fecha de publicación de «Encuentra tu mesa» (hasta esa fecha se muestra el aviso)
    tables_reveal_date: toDateInput(props.settings?.tables_reveal_date),
    // Invitación digital (sitio de Canva con música y transiciones)
    canva_url: props.settings?.canva_url || props.canvaUrl || '',
    // Mesa de regalos (lista de sugerencias en Amazon)
    gift_registry_url: props.settings?.gift_registry_url || props.giftRegistryUrl || '',
    // Cuenta bancaria (segunda opción de regalo)
    gift_bank_name: props.settings?.gift_bank_name || props.giftBank?.bank || '',
    gift_bank_clabe: props.settings?.gift_bank_clabe || props.giftBank?.clabe || '',
    gift_bank_holder: props.settings?.gift_bank_holder || props.giftBank?.holder || '',
});

// Previews
const coverPhotoPreview = ref(photoUrl(props.settings?.cover_photo_path));
const dressCodeImagePreview = ref(photoUrl(props.settings?.dress_code_image_path));

const howWeMetPreview = ref(photoUrl(props.settings?.how_we_met_photo_path));
const proposalPreview = ref(photoUrl(props.settings?.proposal_photo_path));
const ceremonyPreview = ref(photoUrl(props.settings?.ceremony_photo_path));
const celebrationPreview = ref(photoUrl(props.settings?.celebration_photo_path));

const womenDressPreview = ref(photoUrl(props.settings?.dress_code_women_dress));
const womenShoesPreview = ref(photoUrl(props.settings?.dress_code_women_shoes));
const womenAccessoriesPreview = ref(photoUrl(props.settings?.dress_code_women_accessories));
const womenOtherPreview = ref(photoUrl(props.settings?.dress_code_women_other));

const menSuitPreview = ref(photoUrl(props.settings?.dress_code_men_suit));
const menShoesPreview = ref(photoUrl(props.settings?.dress_code_men_shoes));
const menAccessoriesPreview = ref(photoUrl(props.settings?.dress_code_men_accessories));
const menOtherPreview = ref(photoUrl(props.settings?.dress_code_men_other));

function setPreview(e, refKey) {
    const file = e.target.files[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    const previewMap = {
        cover_photo: coverPhotoPreview,
        dress_code_image: dressCodeImagePreview,
        how_we_met_photo: howWeMetPreview,
        proposal_photo: proposalPreview,
        ceremony_photo: ceremonyPreview,
        celebration_photo: celebrationPreview,
        dress_code_women_dress: womenDressPreview,
        dress_code_women_shoes: womenShoesPreview,
        dress_code_women_accessories: womenAccessoriesPreview,
        dress_code_women_other: womenOtherPreview,
        dress_code_men_suit: menSuitPreview,
        dress_code_men_shoes: menShoesPreview,
        dress_code_men_accessories: menAccessoriesPreview,
        dress_code_men_other: menOtherPreview,
    };
    form[refKey] = file;
    if (previewMap[refKey]) previewMap[refKey].value = url;
}

// ── Eliminar imagen ──────────────────────────────────────────────
const removeFlags = ref({});

function removeImage(field) {
    // Buscar el preview ref en el mapa existente
    const previewRef = {
        cover_photo: coverPhotoPreview,
        dress_code_image: dressCodeImagePreview,
        how_we_met_photo: howWeMetPreview,
        proposal_photo: proposalPreview,
        ceremony_photo: ceremonyPreview,
        celebration_photo: celebrationPreview,
        dress_code_women_dress: womenDressPreview,
        dress_code_women_shoes: womenShoesPreview,
        dress_code_women_accessories: womenAccessoriesPreview,
        dress_code_women_other: womenOtherPreview,
        dress_code_men_suit: menSuitPreview,
        dress_code_men_shoes: menShoesPreview,
        dress_code_men_accessories: menAccessoriesPreview,
        dress_code_men_other: menOtherPreview,
    }[field];
    if (previewRef) previewRef.value = null;
    removeFlags.value['_remove_' + field] = true;
    form[field] = null;
}

function submit() {
    form
        .transform(data => ({ ...data, ...removeFlags.value }))
        .post(route('admin.settings.update'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                removeFlags.value = {};
                // Las previews se regeneran con los datos frescos del servidor
            },
        });
}
</script>

<template>
    <AppLayout title="Configuración del Sitio">
        <template #header>
            <h2 class="font-slab text-xl text-tinta leading-tight">Configuración del Sitio</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto">

            <div class="space-y-8">

                <!-- ═══════════ NUESTRA HISTORIA ═══════════ -->
                <div class="bg-white rounded-2xl border border-tinta/10 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-tinta/5">
                        <h3 class="font-slab text-lg text-tinta">Nuestra Historia</h3>
                        <p class="text-sm text-tinta/50 mt-1">Cuenta cómo se conocieron y el momento de la propuesta.</p>
                    </div>

                    <!-- Parte 1: Cómo nos conocimos -->
                    <div class="p-6 border-b border-tinta/5">
                        <h4 class="font-medium text-tinta/70 mb-4 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary/10 text-primary rounded-full flex items-center justify-center text-sm font-bold">1</span>
                            Cómo nos conocimos
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel value="Historia" class="mb-1" />
                                <textarea v-model="form.how_we_met_story" rows="6"
                                    class="w-full rounded-xl border-tinta/20 focus:border-primary focus:ring-primary/20 text-sm"
                                    placeholder="Escribe cómo se conocieron..."></textarea>
                                <InputError :message="form.errors.how_we_met_story" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Foto" class="mb-2" />
                                <div class="relative group rounded-2xl overflow-hidden bg-niebla-dark/50 border border-tinta/10"
                                    :class="(howWeMetPreview ?? photoUrl(props.settings?.how_we_met_photo_path)) ? 'h-48' : 'h-40'">
                                    <img v-if="howWeMetPreview ?? photoUrl(props.settings?.how_we_met_photo_path)" :src="howWeMetPreview ?? photoUrl(props.settings?.how_we_met_photo_path)" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/30">
                                        <div class="text-center"><PhotoIcon class="w-8 h-8 mx-auto mb-1" /><span class="text-xs">Sin foto</span></div>
                                    </div>
                                    <div class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/30 transition-all flex items-center justify-center">
                                        <label class="cursor-pointer bg-white/90 hover:bg-white text-tinta px-4 py-2 rounded-xl text-sm font-medium opacity-0 group-hover:opacity-100 transition-all shadow-lg">
                                            {{ (howWeMetPreview ?? photoUrl(props.settings?.how_we_met_photo_path)) ? 'Cambiar' : 'Subir foto' }}
                                            <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'how_we_met_photo')" />
                                        </label>
                                    </div>
                                </div>
                                <InputError :message="form.errors.how_we_met_photo" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Parte 2: La propuesta -->
                    <div class="p-6">
                        <h4 class="font-medium text-tinta/70 mb-4 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary/10 text-primary rounded-full flex items-center justify-center text-sm font-bold">2</span>
                            La Propuesta
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel value="Historia" class="mb-1" />
                                <textarea v-model="form.proposal_story" rows="6"
                                    class="w-full rounded-xl border-tinta/20 focus:border-primary focus:ring-primary/20 text-sm"
                                    placeholder="Cuenta cómo fue la propuesta..."></textarea>
                                <InputError :message="form.errors.proposal_story" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Foto" class="mb-2" />
                                <div class="relative group rounded-2xl overflow-hidden bg-niebla-dark/50 border border-tinta/10"
                                    :class="(proposalPreview ?? photoUrl(props.settings?.proposal_photo_path)) ? 'h-48' : 'h-40'">
                                    <img v-if="proposalPreview ?? photoUrl(props.settings?.proposal_photo_path)" :src="proposalPreview ?? photoUrl(props.settings?.proposal_photo_path)" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/30">
                                        <div class="text-center"><PhotoIcon class="w-8 h-8 mx-auto mb-1" /><span class="text-xs">Sin foto</span></div>
                                    </div>
                                    <div class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/30 transition-all flex items-center justify-center">
                                        <label class="cursor-pointer bg-white/90 hover:bg-white text-tinta px-4 py-2 rounded-xl text-sm font-medium opacity-0 group-hover:opacity-100 transition-all shadow-lg">
                                            {{ (proposalPreview ?? photoUrl(props.settings?.proposal_photo_path)) ? 'Cambiar' : 'Subir foto' }}
                                            <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'proposal_photo')" />
                                        </label>
                                    </div>
                                </div>
                                <InputError :message="form.errors.proposal_photo" class="mt-1" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ CUÁNDO Y DÓNDE ═══════════ -->
                <div class="bg-white rounded-2xl border border-tinta/10 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-tinta/5">
                        <h3 class="font-slab text-lg text-tinta">Cuándo y Dónde</h3>
                        <p class="text-sm text-tinta/50 mt-1">Información de la ceremonia y la celebración.</p>
                    </div>

                    <!-- Fecha general -->
                    <div class="p-6 border-b border-tinta/5 bg-niebla/30">
                        <div class="max-w-md">
                            <InputLabel value="Fecha y Hora del Evento" class="mb-1" />
                            <input type="datetime-local" v-model="form.event_datetime"
                                class="w-full rounded-xl border-tinta/20 focus:border-primary focus:ring-primary/20 text-tinta" />
                            <InputError :message="form.errors.event_datetime" class="mt-1" />
                        </div>
                    </div>

                    <!-- Ceremonia -->
                    <div class="p-6 border-b border-tinta/5">
                        <h4 class="font-medium text-tinta/70 mb-4 flex items-center gap-2">
                            <span class="w-7 h-7 bg-secondary/10 text-secondary rounded-full flex items-center justify-center text-sm font-bold">💒</span>
                            Ceremonia
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <InputLabel value="Título / Nombre del lugar" class="mb-1" />
                                    <TextInput v-model="form.ceremony_title" class="w-full" placeholder="Ej. Parroquia de San Juan" />
                                </div>
                                <div>
                                    <InputLabel value="Fecha y Hora" class="mb-1" />
                                    <input type="datetime-local" v-model="form.ceremony_datetime"
                                        class="w-full rounded-xl border-tinta/20 focus:border-primary focus:ring-primary/20 text-tinta" />
                                </div>
                                <div>
                                    <InputLabel value="Dirección" class="mb-1" />
                                    <TextInput v-model="form.ceremony_address" class="w-full" placeholder="Ej. Calle Hidalgo 123, Centro" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="Latitud" class="mb-1" />
                                        <TextInput v-model="form.ceremony_lat" class="w-full" placeholder="20.6597" />
                                    </div>
                                    <div>
                                        <InputLabel value="Longitud" class="mb-1" />
                                        <TextInput v-model="form.ceremony_lng" class="w-full" placeholder="-103.3496" />
                                    </div>
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Foto del lugar (opcional)" class="mb-2" />
                                <div class="relative group rounded-2xl overflow-hidden bg-niebla-dark/50 border border-tinta/10"
                                    :class="(ceremonyPreview ?? photoUrl(props.settings?.ceremony_photo_path)) ? 'h-48' : 'h-40'">
                                    <img v-if="ceremonyPreview ?? photoUrl(props.settings?.ceremony_photo_path)" :src="ceremonyPreview ?? photoUrl(props.settings?.ceremony_photo_path)" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/30">
                                        <div class="text-center"><PhotoIcon class="w-8 h-8 mx-auto mb-1" /><span class="text-xs">Sin foto</span></div>
                                    </div>
                                    <div class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/30 transition-all flex items-center justify-center">
                                        <label class="cursor-pointer bg-white/90 hover:bg-white text-tinta px-4 py-2 rounded-xl text-sm font-medium opacity-0 group-hover:opacity-100 transition-all shadow-lg">
                                            {{ (ceremonyPreview ?? photoUrl(props.settings?.ceremony_photo_path)) ? 'Cambiar' : 'Subir foto' }}
                                            <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'ceremony_photo')" />
                                        </label>
                                    </div>
                                </div>
                                <InputError :message="form.errors.ceremony_photo" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Celebración -->
                    <div class="p-6">
                        <h4 class="font-medium text-tinta/70 mb-4 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary/10 text-primary rounded-full flex items-center justify-center text-sm font-bold">🥂</span>
                            Celebración
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <InputLabel value="Título / Nombre del lugar" class="mb-1" />
                                    <TextInput v-model="form.celebration_title" class="w-full" placeholder="Ej. Hacienda El Paraíso" />
                                </div>
                                <div>
                                    <InputLabel value="Fecha y Hora" class="mb-1" />
                                    <input type="datetime-local" v-model="form.celebration_datetime"
                                        class="w-full rounded-xl border-tinta/20 focus:border-primary focus:ring-primary/20 text-tinta" />
                                </div>
                                <div>
                                    <InputLabel value="Dirección" class="mb-1" />
                                    <TextInput v-model="form.celebration_address" class="w-full" placeholder="Ej. Av. Principal 456" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="Latitud" class="mb-1" />
                                        <TextInput v-model="form.celebration_lat" class="w-full" placeholder="20.6597" />
                                    </div>
                                    <div>
                                        <InputLabel value="Longitud" class="mb-1" />
                                        <TextInput v-model="form.celebration_lng" class="w-full" placeholder="-103.3496" />
                                    </div>
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Foto del lugar (opcional)" class="mb-2" />
                                <div class="relative group rounded-2xl overflow-hidden bg-niebla-dark/50 border border-tinta/10"
                                    :class="(celebrationPreview ?? photoUrl(props.settings?.celebration_photo_path)) ? 'h-48' : 'h-40'">
                                    <img v-if="celebrationPreview ?? photoUrl(props.settings?.celebration_photo_path)" :src="celebrationPreview ?? photoUrl(props.settings?.celebration_photo_path)" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/30">
                                        <div class="text-center"><PhotoIcon class="w-8 h-8 mx-auto mb-1" /><span class="text-xs">Sin foto</span></div>
                                    </div>
                                    <div class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/30 transition-all flex items-center justify-center">
                                        <label class="cursor-pointer bg-white/90 hover:bg-white text-tinta px-4 py-2 rounded-xl text-sm font-medium opacity-0 group-hover:opacity-100 transition-all shadow-lg">
                                            {{ (celebrationPreview ?? photoUrl(props.settings?.celebration_photo_path)) ? 'Cambiar' : 'Subir foto' }}
                                            <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'celebration_photo')" />
                                        </label>
                                    </div>
                                </div>
                                <InputError :message="form.errors.celebration_photo" class="mt-1" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ CÓDIGO DE VESTIMENTA ═══════════ -->
                <div class="bg-white rounded-2xl border border-tinta/10 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-tinta/5">
                        <h3 class="font-slab text-lg text-tinta">Código de Vestimenta</h3>
                        <p class="text-sm text-tinta/50 mt-1">Describe el dress code general y sube imágenes de referencia.</p>
                    </div>

                    <!-- Texto general -->
                    <div class="p-6 border-b border-tinta/5">
                        <InputLabel value="Descripción general" class="mb-1" />
                        <textarea v-model="form.dress_code_general" rows="3"
                            class="w-full rounded-xl border-tinta/20 focus:border-primary focus:ring-primary/20 text-sm"
                            placeholder="Ej. Formal — Los invitados deben vestir de gala en tonos pastel..."></textarea>
                    </div>

                    <!-- Imagen general de Dress Code -->
                    <div class="p-6 border-b border-tinta/5">
                        <InputLabel value="Imagen de referencia general (moodboard)" class="mb-2" />
                        <p class="text-xs text-tinta/50 mb-3">Una imagen general que ilustre el estilo del código de vestimenta.</p>
                        <div class="max-w-md">
                            <div class="relative group rounded-2xl overflow-hidden bg-niebla-dark/50 border border-tinta/10"
                                :class="dressCodeImagePreview ? 'h-48' : 'h-40'">
                                <img v-if="dressCodeImagePreview" :src="dressCodeImagePreview" class="w-full h-full object-cover" />
                                <div v-else class="flex items-center justify-center h-full text-tinta/30">
                                    <div class="text-center"><PhotoIcon class="w-8 h-8 mx-auto mb-1" /><span class="text-xs">Sin imagen</span></div>
                                </div>
                                <button v-if="dressCodeImagePreview" @click="removeImage('dress_code_image')" class="absolute top-2 right-2 z-10 bg-red-500/80 hover:bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-all shadow-md" type="button" aria-label="Eliminar imagen"><XMarkIcon class="w-4 h-4" /></button>
                                <<div class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/30 transition-all flex items-center justify-center">
                                    <label class="cursor-pointer bg-white/90 hover:bg-white text-tinta px-4 py-2 rounded-xl text-sm font-medium opacity-0 group-hover:opacity-100 transition-all shadow-lg">
                                        {{ dressCodeImagePreview ? 'Cambiar' : 'Subir imagen' }}
                                        <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'dress_code_image')" />
                                    </label>
                                </div>
                            </div>
                            <InputError :message="form.errors.dress_code_image" class="mt-1" />
                        </div>
                    </div>

                    <!-- Damas -->
                    <div class="p-6 border-b border-tinta/5">
                        <h4 class="font-medium text-tinta/70 mb-4">👩 Damas</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <InputLabel value="Vestido" class="mb-2 text-xs" />
                                <div class="relative group rounded-xl overflow-hidden bg-niebla-dark/50 border border-tinta/10 aspect-square">
                                    <!-- Simplificado aquí -->
                                    <img v-if="womenDressPreview" :src="womenDressPreview" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/20"><PhotoIcon class="w-6 h-6" /></div>
                                    
                                    <button v-if="womenDressPreview" @click="removeImage('dress_code_women_dress')" class="absolute top-1 right-1 z-10 bg-red-500/80 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-all shadow-md" type="button" aria-label="Eliminar imagen"><XMarkIcon class="w-3.5 h-3.5" /></button>
                                    
                                    <label class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/40 transition-all flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100">
                                        <span class="bg-white/90 text-tinta px-3 py-1.5 rounded-lg text-xs font-medium shadow-lg">Subir</span>
                                        <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'dress_code_women_dress')" />
                                    </label>
                                </div>
                                <input v-model="form.dress_code_women_dress_desc" type="text" placeholder="Ej. Largo, verde secondary"
                                    class="w-full mt-2 text-xs rounded-lg border-tinta/20 focus:border-primary focus:ring-primary/20" />
                                <InputError :message="form.errors.dress_code_women_dress" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Calzado" class="mb-2 text-xs" />
                                <div class="relative group rounded-xl overflow-hidden bg-niebla-dark/50 border border-tinta/10 aspect-square">
                                    <img v-if="womenShoesPreview" :src="womenShoesPreview" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/20"><PhotoIcon class="w-6 h-6" /></div>
                                    <button v-if="womenShoesPreview" @click="removeImage('dress_code_women_shoes')" class="absolute top-1 right-1 z-10 bg-red-500/80 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-all shadow-md" type="button" aria-label="Eliminar imagen"><XMarkIcon class="w-3.5 h-3.5" /></button>
                                    <label class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/40 transition-all flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100">
                                        <span class="bg-white/90 text-tinta px-3 py-1.5 rounded-lg text-xs font-medium shadow-lg">Subir</span>
                                        <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'dress_code_women_shoes')" />
                                    </label>
                                </div>
                                <input v-model="form.dress_code_women_shoes_desc" type="text" placeholder="Ej. Botas vaqueras"
                                    class="w-full mt-2 text-xs rounded-lg border-tinta/20 focus:border-primary focus:ring-primary/20" />
                                <InputError :message="form.errors.dress_code_women_shoes" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Accesorios" class="mb-2 text-xs" />
                                <div class="relative group rounded-xl overflow-hidden bg-niebla-dark/50 border border-tinta/10 aspect-square">
                                    <img v-if="womenAccessoriesPreview" :src="womenAccessoriesPreview" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/20"><PhotoIcon class="w-6 h-6" /></div>
                                    <button v-if="womenAccessoriesPreview" @click="removeImage('dress_code_women_accessories')" class="absolute top-1 right-1 z-10 bg-red-500/80 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-all shadow-md" type="button" aria-label="Eliminar imagen"><XMarkIcon class="w-3.5 h-3.5" /></button>
                                    <label class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/40 transition-all flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100">
                                        <span class="bg-white/90 text-tinta px-3 py-1.5 rounded-lg text-xs font-medium shadow-lg">Subir</span>
                                        <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'dress_code_women_accessories')" />
                                    </label>
                                </div>
                                <input v-model="form.dress_code_women_accessories_desc" type="text" placeholder="Ej. Joyería dorada"
                                    class="w-full mt-2 text-xs rounded-lg border-tinta/20 focus:border-primary focus:ring-primary/20" />
                                <InputError :message="form.errors.dress_code_women_accessories" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Otros" class="mb-2 text-xs" />
                                <div class="relative group rounded-xl overflow-hidden bg-niebla-dark/50 border border-tinta/10 aspect-square">
                                    <img v-if="womenOtherPreview" :src="womenOtherPreview" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/20"><PhotoIcon class="w-6 h-6" /></div>
                                    <button v-if="womenOtherPreview" @click="removeImage('dress_code_women_other')" class="absolute top-1 right-1 z-10 bg-red-500/80 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-all shadow-md" type="button" aria-label="Eliminar imagen"><XMarkIcon class="w-3.5 h-3.5" /></button>
                                    <label class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/40 transition-all flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100">
                                        <span class="bg-white/90 text-tinta px-3 py-1.5 rounded-lg text-xs font-medium shadow-lg">Subir</span>
                                        <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'dress_code_women_other')" />
                                    </label>
                                </div>
                                <input v-model="form.dress_code_women_other_desc" type="text" placeholder="Ej. Sombrero, ala corta"
                                    class="w-full mt-2 text-xs rounded-lg border-tinta/20 focus:border-primary focus:ring-primary/20" />
                                <InputError :message="form.errors.dress_code_women_other" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Caballeros -->
                    <div class="p-6">
                        <h4 class="font-medium text-tinta/70 mb-4">👨 Caballeros</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <InputLabel value="Traje" class="mb-2 text-xs" />
                                <div class="relative group rounded-xl overflow-hidden bg-niebla-dark/50 border border-tinta/10 aspect-square">
                                    <img v-if="menSuitPreview" :src="menSuitPreview" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/20"><PhotoIcon class="w-6 h-6" /></div>
                                    <button v-if="menSuitPreview" @click="removeImage('dress_code_men_suit')" class="absolute top-1 right-1 z-10 bg-red-500/80 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-all shadow-md" type="button" aria-label="Eliminar imagen"><XMarkIcon class="w-3.5 h-3.5" /></button>
                                    <label class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/40 transition-all flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100">
                                        <span class="bg-white/90 text-tinta px-3 py-1.5 rounded-lg text-xs font-medium shadow-lg">Subir</span>
                                        <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'dress_code_men_suit')" />
                                    </label>
                                </div>
                                <input v-model="form.dress_code_men_suit_desc" type="text" placeholder="Ej. Saco tweed, bolo tie"
                                    class="w-full mt-2 text-xs rounded-lg border-tinta/20 focus:border-primary focus:ring-primary/20" />
                                <InputError :message="form.errors.dress_code_men_suit" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Calzado" class="mb-2 text-xs" />
                                <div class="relative group rounded-xl overflow-hidden bg-niebla-dark/50 border border-tinta/10 aspect-square">
                                    <img v-if="menShoesPreview" :src="menShoesPreview" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/20"><PhotoIcon class="w-6 h-6" /></div>
                                    <button v-if="menShoesPreview" @click="removeImage('dress_code_men_shoes')" class="absolute top-1 right-1 z-10 bg-red-500/80 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-all shadow-md" type="button" aria-label="Eliminar imagen"><XMarkIcon class="w-3.5 h-3.5" /></button>
                                    <label class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/40 transition-all flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100">
                                        <span class="bg-white/90 text-tinta px-3 py-1.5 rounded-lg text-xs font-medium shadow-lg">Subir</span>
                                        <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'dress_code_men_shoes')" />
                                    </label>
                                </div>
                                <input v-model="form.dress_code_men_shoes_desc" type="text" placeholder="Ej. Botas de piel café"
                                    class="w-full mt-2 text-xs rounded-lg border-tinta/20 focus:border-primary focus:ring-primary/20" />
                                <InputError :message="form.errors.dress_code_men_shoes" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Accesorios" class="mb-2 text-xs" />
                                <div class="relative group rounded-xl overflow-hidden bg-niebla-dark/50 border border-tinta/10 aspect-square">
                                    <img v-if="menAccessoriesPreview" :src="menAccessoriesPreview" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/20"><PhotoIcon class="w-6 h-6" /></div>
                                    <button v-if="menAccessoriesPreview" @click="removeImage('dress_code_men_accessories')" class="absolute top-1 right-1 z-10 bg-red-500/80 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-all shadow-md" type="button" aria-label="Eliminar imagen"><XMarkIcon class="w-3.5 h-3.5" /></button>
                                    <label class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/40 transition-all flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100">
                                        <span class="bg-white/90 text-tinta px-3 py-1.5 rounded-lg text-xs font-medium shadow-lg">Subir</span>
                                        <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'dress_code_men_accessories')" />
                                    </label>
                                </div>
                                <input v-model="form.dress_code_men_accessories_desc" type="text" placeholder="Ej. Reloj, sombrero"
                                    class="w-full mt-2 text-xs rounded-lg border-tinta/20 focus:border-primary focus:ring-primary/20" />
                                <InputError :message="form.errors.dress_code_men_accessories" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Otros" class="mb-2 text-xs" />
                                <div class="relative group rounded-xl overflow-hidden bg-niebla-dark/50 border border-tinta/10 aspect-square">
                                    <img v-if="menOtherPreview" :src="menOtherPreview" class="w-full h-full object-cover" />
                                    <div v-else class="flex items-center justify-center h-full text-tinta/20"><PhotoIcon class="w-6 h-6" /></div>
                                    <button v-if="menOtherPreview" @click="removeImage('dress_code_men_other')" class="absolute top-1 right-1 z-10 bg-red-500/80 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-all shadow-md" type="button" aria-label="Eliminar imagen"><XMarkIcon class="w-3.5 h-3.5" /></button>
                                    <label class="absolute inset-0 bg-tinta/0 group-hover:bg-tinta/40 transition-all flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100">
                                        <span class="bg-white/90 text-tinta px-3 py-1.5 rounded-lg text-xs font-medium shadow-lg">Subir</span>
                                        <input type="file" accept="image/*" class="hidden" @change="setPreview($event, 'dress_code_men_other')" />
                                    </label>
                                </div>
                                <input v-model="form.dress_code_men_other_desc" type="text" placeholder="Ej. Chaleco, pañuelo"
                                    class="w-full mt-2 text-xs rounded-lg border-tinta/20 focus:border-primary focus:ring-primary/20" />
                                <InputError :message="form.errors.dress_code_men_other" class="mt-1" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ INVITACIÓN DIGITAL ═══════════ -->
                <div class="bg-white rounded-2xl border border-tinta/10 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-tinta/5">
                        <h3 class="font-slab text-lg text-tinta">Invitación digital</h3>
                        <p class="text-tinta/50 text-sm mt-1">
                            Al abrir el link personal de cada invitado se muestra un sobre elegante con su nombre y,
                            al entrar, se abre el sitio de Canva de la invitación (con música y transiciones).
                        </p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="max-w-xl">
                            <InputLabel value="URL de la invitación en Canva" class="mb-1" />
                            <TextInput v-model="form.canva_url" type="url" class="w-full"
                                placeholder="https://rrnb.my.canva.site/jose-eli-141126" />
                            <InputError :message="form.errors.canva_url" class="mt-1" />
                            <p class="text-xs text-tinta/40 mt-2">
                                Si lo dejas vacío se usa el diseño predeterminado del sitio.
                            </p>
                        </div>
                        <div class="bg-niebla/60 border border-tinta/10 rounded-xl p-4 text-sm text-tinta/70 max-w-2xl">
                            <p class="font-medium text-tinta mb-1">Botón «Confirmar asistencia» dentro de Canva</p>
                            <p>
                                En Canva, ese botón debe apuntar a
                                <code class="bg-white border border-tinta/10 px-1.5 py-0.5 rounded text-tinta">{{ rsvpUrl }}</code>.
                                Así el invitado verá su confirmación con su nombre ya cargado (el sistema recuerda quién
                                abrió la invitación). Si alguien entra sin link personal, siempre podrá buscar su nombre.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ MESA DE REGALOS ═══════════ -->
                <div class="bg-white rounded-2xl border border-tinta/10 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-tinta/5">
                        <h3 class="font-slab text-lg text-tinta">Mesa de regalos</h3>
                        <p class="text-tinta/50 text-sm mt-1">
                            Link de la lista de sugerencias (Amazon) que se muestra en la sección
                            «Mesa de Regalos» de la página.
                        </p>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="max-w-xl">
                            <InputLabel value="URL de la lista de regalos" class="mb-1" />
                            <TextInput v-model="form.gift_registry_url" type="url" class="w-full"
                                placeholder="https://www.amazon.com.mx/hz/wishlist/ls/..." />
                            <InputError :message="form.errors.gift_registry_url" class="mt-1" />
                            <p class="text-xs text-tinta/40 mt-2">
                                Si lo dejas vacío se usa el link predeterminado del sitio.
                            </p>
                        </div>

                        <div class="pt-6 border-t border-tinta/10">
                            <h4 class="font-slab text-tinta">Cuenta bancaria (opcional)</h4>
                            <p class="text-tinta/50 text-sm mt-1 mb-4">
                                Segunda opción de regalo: se muestra debajo de la lista de Amazon, para
                                quienes prefieran transferir o donar. Si borras la CLABE, el bloque
                                desaparece del sitio.
                            </p>
                            <div class="grid sm:grid-cols-2 gap-4 max-w-2xl">
                                <div>
                                    <InputLabel value="Banco" class="mb-1" />
                                    <TextInput v-model="form.gift_bank_name" class="w-full" placeholder="BBVA" />
                                    <InputError :message="form.errors.gift_bank_name" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel value="Titular de la cuenta" class="mb-1" />
                                    <TextInput v-model="form.gift_bank_holder" class="w-full" placeholder="Nombre completo" />
                                    <InputError :message="form.errors.gift_bank_holder" class="mt-1" />
                                </div>
                                <div class="sm:col-span-2">
                                    <InputLabel value="CLABE interbancaria" class="mb-1" />
                                    <TextInput v-model="form.gift_bank_clabe" class="w-full font-mono"
                                        placeholder="18 dígitos, ej. 012180015412256086" />
                                    <InputError :message="form.errors.gift_bank_clabe" class="mt-1" />
                                    <p class="text-xs text-tinta/40 mt-2">
                                        Puedes pegarla con espacios; se guardan solo los 18 dígitos.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ RSVP ═══════════ -->
                <div class="bg-white rounded-2xl border border-tinta/10 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-tinta/5">
                        <h3 class="font-slab text-lg text-tinta">Confirmación de Asistencia</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="max-w-sm">
                            <InputLabel value="Fecha límite para confirmar asistencia" class="mb-1" />
                            <input type="date" v-model="form.rsvp_deadline"
                                class="w-full rounded-xl border-tinta/20 focus:border-primary focus:ring-primary/20 text-tinta" />
                            <InputError :message="form.errors.rsvp_deadline" class="mt-1" />
                        </div>
                        <div class="max-w-sm">
                            <InputLabel value="Fecha de publicación de «Encuentra tu mesa»" class="mb-1" />
                            <input type="date" v-model="form.tables_reveal_date"
                                class="w-full rounded-xl border-tinta/20 focus:border-primary focus:ring-primary/20 text-tinta" />
                            <InputError :message="form.errors.tables_reveal_date" class="mt-1" />
                            <p class="text-xs text-tinta/40 mt-2">
                                Hasta esta fecha, la sección muestra el aviso «Vuelve el …» en lugar del buscador
                                (las mesas se distribuyen después de las confirmaciones). Déjala vacía para no
                                mostrarlo.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Save button -->
            <div class="flex items-center gap-4 mt-8 pb-12">
                <PrimaryButton @click="submit" :disabled="form.processing" :class="{ 'opacity-50': form.processing }">
                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                </PrimaryButton>
                <ActionMessage :on="form.recentlySuccessful" class="ms-3">¡Guardado!</ActionMessage>
            </div>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
