<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { formatEventDate } from '@/Composables/useEventDate';

/**
 * Puerta de apertura de la invitación (mobile first).
 *
 * El sobre empieza CERRADO: al tocarlo se abre la solapa y sale la tarjeta con
 * «Para», los nombres y el texto «Leer invitación», que abre el sitio de Canva
 * (música y transiciones). No hay botones: todo es tipografía.
 *
 * El link `/i/{token}/abrir` deja registrado en una cookie quién abrió la
 * invitación, para que la confirmación de asistencia salga con su nombre.
 */
const props = defineProps({
    invitation: Object,
    coupleNames: String,
    eventDateTime: String,
});

const dateFormatted = computed(() => formatEventDate(props.eventDateTime));
const members = computed(() => props.invitation?.members ?? []);

/**
 * Tamaño tipográfico del nombre según su longitud: los nombres largos (una
 * pareja con apellidos completos, por ejemplo) usan una tipografía menor para
 * que la tarjeta no crezca más de lo que permite el escenario y su contenido
 * siempre quede visible.
 */
const nameSize = computed(() => {
    const length = (props.invitation?.display_name ?? '').length;

    if (length > 36) return 'text-[1.15rem] sm:text-[1.3rem]';
    if (length > 20) return 'text-[1.35rem] sm:text-[1.55rem]';

    return 'text-[1.55rem] sm:text-[1.75rem]';
});

// El sobre arranca cerrado; al tocarlo se abre la solapa y sale la tarjeta.
const isOpen = ref(false);
// La solapa pasa por detrás de la tarjeta cuando ya está de canto (a mitad de la
// animación), para que la tarjeta salga por delante del sobre.
const flapBehind = ref(false);

function openEnvelope() {
    if (isOpen.value) return;

    isOpen.value = true;
    window.setTimeout(() => { flapBehind.value = true; }, 300);
}
</script>

<template>
    <Head :title="`Invitación · ${invitation?.display_name ?? 'Boda'}`">
        <meta name="robots" content="noindex, nofollow" />
        <meta property="og:title" :content="`Invitación para ${invitation?.display_name ?? ''}`" />
        <meta property="og:description" :content="`${coupleNames} te invitan a su boda · ${dateFormatted}`" />
        <meta property="og:type" content="website" />
    </Head>

    <div class="min-h-screen bg-gradient-to-b from-tinta-dark via-tinta to-primary-dark flex flex-col items-center justify-center px-4 py-10 relative overflow-hidden"
        style="min-height: 100svh">
        <!-- Destellos decorativos de fondo -->
        <div class="pointer-events-none absolute inset-0 opacity-40">
            <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-primary/30 blur-3xl"></div>
            <div class="absolute -bottom-32 -right-20 w-80 h-80 rounded-full bg-secondary/20 blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-md text-center">
            <!-- Nombres de los novios: suben un poco al abrir el sobre -->
            <div class="animate-fade-in transition-transform duration-700 ease-out motion-reduce:transition-none"
                :class="isOpen ? '-translate-y-[3.25rem] sm:-translate-y-[3.75rem]' : 'translate-y-0'">
                <p class="font-script text-[2.1rem] leading-tight text-white/95 drop-shadow-lg sm:text-[2.6rem]">{{ coupleNames }}</p>
                <p class="mt-2 text-[10px] font-medium uppercase tracking-[0.32em] text-primary-light sm:text-xs">
                    {{ dateFormatted }}
                </p>
            </div>

            <!-- Escenario: sobre cerrado → se abre la solapa → sale la tarjeta.
                 El escenario es alto a propósito: el sobre se ancla abajo y la
                 tarjeta sube hacia el espacio libre de arriba, de modo que nunca
                 se recorta por el borde superior. -->
            <div class="relative mx-auto mt-4 h-[20rem] w-[17.5rem] max-w-[88vw] sm:mt-6 sm:h-[22rem] sm:w-[21rem]">

                <!-- Halo detrás del sobre: separa el papel marino del fondo azul
                     de la página, que pertenece a la misma familia de color. -->
                <div class="pointer-events-none absolute -inset-x-6 top-1/4 bottom-0 rounded-[45%] bg-niebla/10 blur-2xl"></div>

                <!-- Cuerpo trasero del sobre: apenas asoma detrás del bolsillo,
                     pero es el que proyecta la sombra que lo separa del fondo. -->
                <div class="superficie-sobre absolute inset-x-0 bottom-0 h-[7.5rem] rounded-[16px] shadow-[0_26px_50px_-22px_rgba(3,10,22,0.95)] sm:h-[8.5rem]">
                    <div class="absolute inset-0 rounded-[16px] bg-sobre-deep/50"></div>
                </div>

                <!-- Recorte: la tarjeta sólo puede verse dentro del escenario -->
                <div class="absolute inset-0 z-20 overflow-hidden">
                    <!-- Tarjeta que sale del sobre -->
                    <div class="absolute inset-x-0 mx-auto w-[15rem] max-w-[86%] rounded-[14px] bg-niebla-light px-5 pt-5 pb-10 text-center shadow-[0_20px_36px_-18px_rgba(6,26,45,0.75)] transition-[bottom] duration-700 delay-300 ease-[cubic-bezier(.22,.9,.24,1)] motion-reduce:transition-none sm:w-[18rem]"
                        :class="isOpen ? 'bottom-[6rem] sm:bottom-[7rem]' : 'bottom-[-8rem] sm:bottom-[-8.5rem]'">
                        <p class="text-[10px] font-medium uppercase tracking-[0.3em] text-tinta/45">Para</p>
                        <p class="mt-2 font-display leading-tight text-tinta" :class="nameSize">
                            {{ invitation?.display_name }}
                        </p>
                        <!-- Una sola línea siempre (con puntos suspensivos si es muy
                             largo): así la altura de la tarjeta es predecible y su
                             contenido nunca se sale del escenario. -->
                        <!-- <p v-if="members.length" class="mt-2 truncate text-[11px] leading-relaxed text-tinta/45">
                            {{ members.map((member) => member.full_name).join(' · ') }}
                        </p> -->

                        <!-- «Leer invitación»: abre el sitio de Canva (música y transiciones) -->
                        <a
                            :href="invitation?.open_url"
                            class="mt-4 inline-block rounded-lg px-3 py-1 font-slab text-[15px] text-primary-dark underline decoration-primary/40 decoration-[1.5px] underline-offset-4 transition-all duration-500 delay-[900ms] motion-reduce:transition-none sm:text-base"
                            :class="isOpen ? 'translate-y-0 opacity-100' : 'pointer-events-none translate-y-1 opacity-0'"
                        >
                            Leer invitación
                        </a>
                    </div>
                </div>
                <!-- Frente del sobre (bolsillo) -->
                <div class="superficie-sobre absolute inset-x-0 bottom-0 z-30 h-[7.5rem] overflow-hidden rounded-[16px] shadow-[inset_0_1px_0_rgba(255,255,255,0.14),inset_0_-20px_30px_-26px_rgba(0,0,0,0.7)] sm:h-[8.5rem]">
                    <!-- Luz sobre el papel: recibe luz arriba y se apaga abajo -->
                    <div class="absolute inset-0 bg-gradient-to-b from-white/[0.09] via-white/[0.04] to-transparent"></div>
                    <!-- Solapas laterales: en penumbra, marcan el doblez -->
                    <div class="absolute inset-0 bg-sobre-deep/45 [clip-path:polygon(0_0,0_100%,50%_64%)]"></div>
                    <div class="absolute inset-0 bg-sobre-deep/45 [clip-path:polygon(100%_0,100%_100%,50%_64%)]"></div>
                    <!-- Solapa inferior: la cara que cierra el sobre, la más iluminada -->
                    <div class="absolute inset-0 bg-gradient-to-b from-white/[0.08] via-white/[0.01] to-black/15 [clip-path:polygon(0_100%,50%_0,100%_100%)]"></div>
                </div>

                <!-- Solapa superior: gira sobre su borde para abrir el sobre.
                     El z-index se asigna SOLO desde la clase dinámica: si se dejara
                     un z-40 fijo, al abrir quedarían z-40 y z-10 en el elemento y
                     ganaría z-40 (orden del CSS), tapando la tarjeta. -->
                <div class="superficie-sobre absolute inset-x-0 bottom-[3rem] h-[4.5rem] rounded-t-[16px] shadow-[inset_0_1px_0_rgba(255,255,255,0.16)] [clip-path:polygon(0_0,100%_0,50%_100%)] [transform-origin:top] transition-transform duration-[600ms] ease-[cubic-bezier(.5,0,.2,1)] motion-reduce:transition-none sm:bottom-[3.5rem] sm:h-[5rem]"
                    :class="[isOpen ? '[transform:rotateX(-180deg)]' : '[transform:rotateX(0deg)]', flapBehind ? 'z-10' : 'z-40']">
                    <!-- La solapa nace iluminada en la bisagra y se apaga hacia la punta -->
                    <div class="absolute inset-0 bg-gradient-to-b from-white/[0.07] via-transparent to-sobre-deep/35"></div>
                </div>

                <!-- Sello de cera: es el sello real (dorado) recortado en círculo
                     desde la captura de referencia. Va centrado en el frente del
                     sobre y POR ENCIMA de la solapa; no puede ir dentro de ella
                     porque su recorte triangular lo cortaría. Se desvanece al
                     abrir la invitación, igual que antes. -->
                <img
                    src="/img/sello.png"
                    alt=""
                    aria-hidden="true"
                    draggable="false"
                    class="pointer-events-none absolute bottom-[3.75rem] left-1/2 z-40 w-[4.75rem] -translate-x-1/2 translate-y-1/2 select-none drop-shadow-[0_6px_10px_rgba(3,10,22,0.55)] transition-opacity duration-500 motion-reduce:transition-none sm:bottom-[4.25rem] sm:w-[5.5rem]"
                    :class="isOpen ? 'opacity-0' : 'opacity-100'"
                />

                <!-- Zona táctil de todo el sobre (invisible) -->
                <button
                    type="button"
                    :disabled="isOpen"
                    :aria-expanded="isOpen"
                    aria-label="Abrir el sobre de la invitación"
                    @click="openEnvelope"
                    class="absolute inset-x-0 bottom-0 z-50 h-[7.5rem] cursor-pointer appearance-none border-0 bg-transparent p-0 sm:h-[8.5rem]"
                    :class="isOpen ? 'pointer-events-none' : ''"
                ></button>

                <!-- Pista: desaparece al abrir el sobre -->
                <p class="absolute inset-x-0 bottom-[9.5rem] font-slab text-[10px] uppercase tracking-[0.3em] text-primary-light/75 transition-opacity duration-500 motion-reduce:transition-none sm:bottom-[10.5rem]"
                    :class="isOpen ? 'opacity-0' : 'animate-pulse-soft opacity-100'">
                    Toca el sobre
                </p>
            </div>

            <!-- Confirmar sin pasar por el sitio de Canva -->
            <a
                :href="invitation?.rsvp_url"
                class="mt-6 inline-block text-[11px] text-white/40 underline decoration-white/20 underline-offset-4 transition-all duration-500 delay-[1100ms] hover:text-white/75 motion-reduce:transition-none"
                :class="isOpen ? 'opacity-100' : 'pointer-events-none opacity-0'"
            >
                O solo confirmar mi asistencia
            </a>

            <p class="mt-4 text-[10px] text-white/25">
                Esta invitación es personal e intransferible.
            </p>
        </div>
    </div>
</template>
