<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import Engine from '@/lib/weddingEngine';

const props = defineProps({
    venue: { type: Object, required: true },
    tables: { type: Array, default: () => [] },
    elements: { type: Array, default: () => [] },
    seats: { type: Array, default: () => [] },
    highlightTables: { type: Array, default: () => [] },
});

const wrapper = ref(null);
const fitScale = ref(0.5);
// Se acerca un poco para que la mesa del invitado se vea grande de entrada.
const zoom = ref(1.4);

const scale = computed(() => fitScale.value * zoom.value);

const gridSize = computed(() => Number(props.venue.gridSize) || 20);

const gridStyle = computed(() => {
    const line = 'rgba(148, 163, 184, .3)';

    return {
        backgroundImage: `linear-gradient(to right, ${line} 1px, transparent 1px), linear-gradient(to bottom, ${line} 1px, transparent 1px)`,
        backgroundSize: `${gridSize.value}px ${gridSize.value}px`,
    };
});

/** Asientos resaltados: "mesaId:numero" → datos del asiento. */
const seatMap = computed(() => {
    const map = new Map();

    props.seats.forEach((seat) => {
        map.set(`${seat.table_id}:${seat.seat_number}`, seat);
    });

    return map;
});

const highlightedTableIds = computed(() => new Set(props.highlightTables));

function highlightedSeat(tableId, seatNumber) {
    return seatMap.value.get(`${tableId}:${seatNumber}`) ?? null;
}

/** Color de la mesa: primary (tú), secondary (acompañante) o ninguno. */
function tableTone(table) {
    const tableSeats = props.seats.filter((seat) => seat.table_id === table.id);

    if (tableSeats.some((seat) => seat.is_primary)) return 'primary';
    if (tableSeats.length) return 'secondary';

    return highlightedTableIds.value.has(table.id) ? 'primary' : null;
}

function tableClasses(table) {
    const tone = tableTone(table);

    if (tone === 'primary') {
        return 'border-primary bg-primary/10 text-tinta shadow-[0_0_28px_rgba(88,180,255,.55)]';
    }

    if (tone === 'secondary') {
        return 'border-secondary bg-secondary/10 text-tinta shadow-[0_0_28px_rgba(247,167,158,.55)]';
    }

    return 'border-slate-300 bg-slate-100 text-slate-400';
}

/** Etiqueta de la mesa resaltada: "TU MESA" o "ACOMPAÑANTE". */
function tableBadge(table) {
    const tone = tableTone(table);

    if (tone === 'primary') return 'TU MESA';
    if (tone === 'secondary') return 'ACOMPAÑANTE';

    return null;
}

function seatClass(tableId, seatNumber) {
    const seat = highlightedSeat(tableId, seatNumber);

    if (!seat) {
        return 'border-slate-300 bg-white text-slate-300';
    }

    return seat.is_primary
        ? 'border-primary bg-primary text-white shadow-[0_0_18px_rgba(88,180,255,.95)] animate-pulse-soft'
        : 'border-secondary bg-secondary text-white shadow-[0_0_18px_rgba(247,167,158,.95)] animate-pulse-soft';
}

function seatLabel(tableId, seatNumber) {
    const seat = highlightedSeat(tableId, seatNumber);

    return seat ? seat.guest_name : `Asiento ${seatNumber}`;
}

function normalizedDistribution(table) {
    if (table.shape === 'round') {
        return Engine.defaultSeatDistribution('round', table.capacity);
    }

    const custom = table.seat_distribution;
    const sum = custom
        ? ['top', 'right', 'bottom', 'left'].reduce((total, side) => total + (Number(custom[side]) || 0), 0)
        : 0;

    return sum === table.capacity
        ? custom
        : Engine.defaultSeatDistribution(table.shape, table.capacity);
}

function seatPositions(table) {
    const seats = Array.from({ length: table.capacity }, (_, index) => ({
        id: index + 1,
        number: index + 1,
    }));

    return Engine.computeSeatPositions({
        ...table,
        seats,
        seatDistribution: normalizedDistribution(table),
    });
}

function shapeClass(table) {
    return table.shape === 'round' ? 'rounded-full' : 'rounded-xl';
}

function fitToWidth() {
    if (!wrapper.value) return;

    const available = wrapper.value.clientWidth - 32;
    fitScale.value = Math.min(1, Math.max(0.4, available / props.venue.width));
}

/**
 * Centra la vista en la mesa del invitado para que su asiento se vea de
 * inmediato, sin tener que recorrer todo el salón.
 */
function scrollToSeat() {
    if (!wrapper.value) return;

    const tableId = props.seats[0]?.table_id ?? props.highlightTables[0] ?? null;
    const table = props.tables.find((item) => item.id === tableId);

    if (!table) return;

    const centerX = (table.x + table.width / 2) * scale.value;
    const centerY = (table.y + table.height / 2) * scale.value;

    wrapper.value.scrollLeft = Math.max(0, centerX - wrapper.value.clientWidth / 2);
    wrapper.value.scrollTop = Math.max(0, centerY - wrapper.value.clientHeight / 2);
}

function zoomIn() {
    zoom.value = Math.min(1.8, Math.round((zoom.value + 0.2) * 10) / 10);
}

function zoomOut() {
    zoom.value = Math.max(0.6, Math.round((zoom.value - 0.2) * 10) / 10);
}

onMounted(() => {
    fitToWidth();
    nextTick(scrollToSeat);
    window.addEventListener('resize', fitToWidth);
});

onUnmounted(() => window.removeEventListener('resize', fitToWidth));
</script>

<template>
    <div>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-xs text-tinta/60">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-full bg-primary shadow-[0_0_10px_rgba(88,180,255,.9)]"></span>
                    Tu lugar
                </span>
                <span v-if="seats.some((seat) => !seat.is_primary)" class="inline-flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-full bg-secondary shadow-[0_0_10px_rgba(247,167,158,.9)]"></span>
                    Tu pareja / acompañante
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-full bg-slate-300"></span>
                    Resto del salón
                </span>
            </div>

            <div class="flex items-center gap-1">
                <button type="button" class="h-7 w-7 rounded-lg border border-tinta/20 bg-white text-tinta/70 hover:bg-niebla" title="Alejar" @click="zoomOut">−</button>
                <button type="button" class="h-7 w-7 rounded-lg border border-tinta/20 bg-white text-tinta/70 hover:bg-niebla" title="Acercar" @click="zoomIn">+</button>
                <button type="button" class="rounded-lg border border-tinta/20 bg-white px-2 py-1 text-tinta/70 hover:bg-niebla" title="Ver todo el salón" @click="zoom = 1">
                    Ver todo
                </button>
            </div>
        </div>

        <div
            ref="wrapper"
            class="relative overflow-auto rounded-2xl border border-tinta/10 bg-slate-100"
            style="max-height: 64vh"
        >
            <div
                class="relative mx-auto"
                :style="{
                    width: `${venue.width * scale}px`,
                    height: `${venue.height * scale}px`,
                }"
            >
                <div
                    class="absolute origin-top-left bg-white"
                    :style="{
                        transform: `scale(${scale})`,
                        width: `${venue.width}px`,
                        height: `${venue.height}px`,
                        ...gridStyle,
                    }"
                >
                    <!-- Elementos del salón, en gris -->
                    <div
                        v-for="element in elements"
                        :key="`el-${element.id}`"
                        class="absolute flex items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-[11px] text-slate-400"
                        :style="{
                            left: `${element.x}px`,
                            top: `${element.y}px`,
                            width: `${element.width}px`,
                            height: `${element.height}px`,
                            transform: `rotate(${element.rotation || 0}deg)`,
                        }"
                    >
                        <span class="truncate px-1">{{ element.name }}</span>
                    </div>

                    <!-- Mesas -->
                    <div
                        v-for="table in tables"
                        :key="table.id"
                        class="absolute"
                        :style="{
                            left: `${table.x}px`,
                            top: `${table.y}px`,
                            width: `${table.width}px`,
                            height: `${table.height}px`,
                            transform: `rotate(${table.rotation || 0}deg)`,
                        }"
                    >
                        <div
                            class="flex h-full w-full flex-col items-center justify-center border-2 text-center transition-all"
                            :class="[shapeClass(table), tableClasses(table)]"
                        >
                            <span
                                v-if="tableBadge(table)"
                                class="text-[9px] font-semibold uppercase tracking-wider"
                                :class="tableTone(table) === 'secondary' ? 'text-secondary-dark' : 'text-primary'"
                            >
                                {{ tableBadge(table) }}
                            </span>
                            <span class="max-w-full truncate px-2 text-xs font-bold">{{ table.name }}</span>
                            <span class="text-[10px] opacity-70">{{ table.capacity }} lugares</span>
                        </div>

                        <!-- Asientos -->
                        <div
                            v-for="position in seatPositions(table)"
                            :key="`${table.id}-${position.number}`"
                            class="absolute flex h-8 w-8 items-center justify-center rounded-full border-2 text-[11px] font-bold transition-transform"
                            :class="[seatClass(table.id, position.number), highlightedSeat(table.id, position.number) ? 'scale-125' : '']"
                            :style="{ left: `${position.x - 2}px`, top: `${position.y - 2}px` }"
                            :title="seatLabel(table.id, position.number)"
                        >
                            {{ highlightedSeat(table.id, position.number) ? '★' : '' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
