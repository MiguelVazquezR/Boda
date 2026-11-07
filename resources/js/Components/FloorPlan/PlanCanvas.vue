<script setup>
import { computed, ref } from 'vue';
import Engine from '@/lib/weddingEngine';

const props = defineProps({
    venue: { type: Object, required: true },
    tables: { type: Array, default: () => [] },
    elements: { type: Array, default: () => [] },
    guests: { type: Array, default: () => [] },
    selectedTableId: { type: Number, default: null },
    selectedElementId: { type: Number, default: null },
    selectedSeat: { type: Object, default: null },
});

const emit = defineEmits([
    'select-table',
    'select-seat',
    'select-element',
    'drop-guest',
    'layout-changed',
]);

const canvasRef = ref(null);
const zoom = ref(0.55);
const pan = ref({ x: 40, y: 60 });

const gridSize = computed(() => Number(props.venue.gridSize) || 20);

const gridStyle = computed(() => {
    if (!props.venue.showGrid) return {};

    const line = 'rgba(148, 163, 184, .35)';

    return {
        backgroundImage: `linear-gradient(to right, ${line} 1px, transparent 1px), linear-gradient(to bottom, ${line} 1px, transparent 1px)`,
        backgroundSize: `${gridSize.value}px ${gridSize.value}px`,
    };
});

/** Invitado que ocupa cada asiento: clave "mesaId:numeroAsiento". */
const seatOccupants = computed(() => {
    const map = new Map();

    props.guests.forEach((guest) => {
        if (guest.tableId && guest.seatId) {
            map.set(`${guest.tableId}:${guest.seatId}`, guest);
        }
    });

    return map;
});

const ELEMENT_ICONS = {
    dance_floor: '💃',
    head_table: '💍',
    gifts: '🎁',
    stage: '🎤',
    dj: '🎧',
    buffet: '🍽️',
    bar: '🍸',
    door: '🚪',
    window: '🪟',
    column: '◼️',
    wall: '▬',
    restroom: '🚻',
    custom: '✨',
};

function elementIcon(kind) {
    return ELEMENT_ICONS[kind] ?? '✨';
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

function occupied(table) {
    return props.guests.filter((guest) => guest.tableId === table.id).length;
}

function shapeClass(table) {
    return table.shape === 'round' ? 'rounded-full' : 'rounded-xl';
}

function dragGuestId(event) {
    return Number(
        event.dataTransfer?.getData('application/x-guest-id')
        || event.dataTransfer?.getData('text/plain'),
    );
}

function dropOnSeat(table, seatNumber, event) {
    const guestId = dragGuestId(event);

    if (guestId) emit('drop-guest', guestId, table.id, seatNumber);
}

function dropOnTable(table, event) {
    const guestId = dragGuestId(event);

    if (guestId) emit('drop-guest', guestId, table.id, null);
}

function panStart(event) {
    if (event.button !== 0) return;

    const start = { x: event.clientX, y: event.clientY, panX: pan.value.x, panY: pan.value.y };

    const move = (moveEvent) => {
        pan.value = {
            x: start.panX + (moveEvent.clientX - start.x),
            y: start.panY + (moveEvent.clientY - start.y),
        };
    };

    const stop = () => {
        window.removeEventListener('mousemove', move);
        window.removeEventListener('mouseup', stop);
    };

    window.addEventListener('mousemove', move);
    window.addEventListener('mouseup', stop);
}

function onVenueMouseDown(event) {
    emit('select-table', null);
    panStart(event);
}

function startDragTable(table, event) {
    if (event.button !== 0) return;

    const start = {
        x: event.clientX,
        y: event.clientY,
        originX: table.x,
        originY: table.y,
    };

    let moved = false;

    const move = (moveEvent) => {
        const dx = (moveEvent.clientX - start.x) / zoom.value;
        const dy = (moveEvent.clientY - start.y) / zoom.value;

        if (!moved && Math.abs(dx) < 3 && Math.abs(dy) < 3) return;

        moved = true;

        let nextX = start.originX + dx;
        let nextY = start.originY + dy;

        if (props.venue.snapToGrid) {
            nextX = Math.round(nextX / gridSize.value) * gridSize.value;
            nextY = Math.round(nextY / gridSize.value) * gridSize.value;
        }

        table.x = Math.round(Math.max(0, Math.min(props.venue.width - table.width, nextX)));
        table.y = Math.round(Math.max(0, Math.min(props.venue.height - table.height, nextY)));
    };

    const stop = () => {
        window.removeEventListener('mousemove', move);
        window.removeEventListener('mouseup', stop);

        if (moved) emit('layout-changed');
    };

    window.addEventListener('mousemove', move);
    window.addEventListener('mouseup', stop);
}

function startDragElement(element, event) {
    if (event.button !== 0) return;

    const start = {
        x: event.clientX,
        y: event.clientY,
        originX: element.x,
        originY: element.y,
    };

    let moved = false;

    const move = (moveEvent) => {
        const dx = (moveEvent.clientX - start.x) / zoom.value;
        const dy = (moveEvent.clientY - start.y) / zoom.value;

        if (!moved && Math.abs(dx) < 3 && Math.abs(dy) < 3) return;

        moved = true;

        let nextX = start.originX + dx;
        let nextY = start.originY + dy;

        if (props.venue.snapToGrid) {
            nextX = Math.round(nextX / gridSize.value) * gridSize.value;
            nextY = Math.round(nextY / gridSize.value) * gridSize.value;
        }

        element.x = Math.round(Math.max(0, Math.min(props.venue.width - element.width, nextX)));
        element.y = Math.round(Math.max(0, Math.min(props.venue.height - element.height, nextY)));
    };

    const stop = () => {
        window.removeEventListener('mousemove', move);
        window.removeEventListener('mouseup', stop);

        if (moved) emit('layout-changed');
    };

    window.addEventListener('mousemove', move);
    window.addEventListener('mouseup', stop);
}

function onWheel(event) {
    const next = zoom.value * (event.deltaY > 0 ? 0.9 : 1.1);
    zoom.value = Math.min(1.6, Math.max(0.25, next));
}

function zoomIn() {
    zoom.value = Math.min(1.6, zoom.value + 0.1);
}

function zoomOut() {
    zoom.value = Math.max(0.25, zoom.value - 0.1);
}

function fitToScreen() {
    if (!canvasRef.value) return;

    const available = canvasRef.value.clientWidth - 80;
    zoom.value = Math.min(1.2, Math.max(0.2, available / props.venue.width));
    pan.value = { x: 40, y: 40 };
}
</script>

<template>
    <div
        ref="canvasRef"
        class="relative flex-1 overflow-hidden bg-niebla-dark/40 select-none"
        @mousedown.self="panStart"
        @wheel.prevent="onWheel"
    >
        <div
            class="absolute origin-top-left"
            :style="{ transform: `translate(${pan.x}px, ${pan.y}px) scale(${zoom})` }"
        >
            <div
                class="relative border border-tinta/10 bg-white shadow-xl"
                :style="{ width: `${venue.width}px`, height: `${venue.height}px`, ...gridStyle }"
                @mousedown.self="onVenueMouseDown"
            >
                <p class="absolute -top-7 left-0 text-xs font-semibold text-tinta/50">
                    {{ venue.title }}
                </p>

                <!-- Elementos del salón -->
                <div
                    v-for="element in elements"
                    :key="`el-${element.id ?? element.kind}-${element.name}`"
                    class="absolute flex cursor-move items-center justify-center gap-1 rounded-lg border text-[11px] text-tinta/70 shadow-sm"
                    :class="selectedElementId === element.id ? 'border-primary ring-2 ring-primary/40' : 'border-tinta/15'"
                    :style="{
                        left: `${element.x}px`,
                        top: `${element.y}px`,
                        width: `${element.width}px`,
                        height: `${element.height}px`,
                        transform: `rotate(${element.rotation || 0}deg)`,
                        backgroundColor: element.color || '#e2e8f0',
                    }"
                    @mousedown.stop="startDragElement(element, $event)"
                    @click.stop="emit('select-element', element.id)"
                >
                    <span>{{ elementIcon(element.kind) }}</span>
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
                        class="flex h-full w-full cursor-move flex-col items-center justify-center border-2 bg-white/95 text-center shadow-sm transition-colors"
                        :class="[
                            shapeClass(table),
                            selectedTableId === table.id
                                ? 'border-primary ring-2 ring-primary/30'
                                : 'border-tinta/25 hover:border-primary/60',
                        ]"
                        @mousedown.stop="startDragTable(table, $event)"
                        @click.stop="emit('select-table', table.id)"
                        @dragover.prevent
                        @drop.prevent.stop="dropOnTable(table, $event)"
                    >
                        <span class="max-w-full truncate px-2 text-xs font-bold text-tinta">{{ table.name }}</span>
                        <span class="text-[10px] text-tinta/50">{{ occupied(table) }}/{{ table.capacity }}</span>
                    </div>

                    <!-- Asientos -->
                    <button
                        v-for="position in seatPositions(table)"
                        :key="`${table.id}-${position.number}`"
                        type="button"
                        class="absolute flex h-7 w-7 items-center justify-center rounded-full border-2 text-[10px] font-bold transition-transform hover:scale-110"
                        :class="[
                            seatOccupants.get(`${table.id}:${position.number}`)
                                ? 'border-secondary bg-secondary text-white'
                                : 'border-tinta/25 bg-white text-tinta/40',
                            selectedSeat && selectedSeat.tableId === table.id && selectedSeat.number === position.number
                                ? 'ring-2 ring-primary ring-offset-1'
                                : '',
                        ]"
                        :style="{ left: `${position.x}px`, top: `${position.y}px` }"
                        :title="seatOccupants.get(`${table.id}:${position.number}`)?.full_name ?? `Asiento ${position.number}`"
                        @click.stop="emit('select-seat', table.id, position.number)"
                        @dragover.prevent
                        @drop.prevent.stop="dropOnSeat(table, position.number, $event)"
                    >
                        {{ seatOccupants.get(`${table.id}:${position.number}`) ? '👤' : position.number }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Controles de zoom -->
        <div class="absolute bottom-4 right-4 flex items-center gap-1 rounded-xl border border-tinta/10 bg-white p-1 shadow-lg">
            <button type="button" class="h-8 w-8 rounded-lg text-tinta/70 hover:bg-niebla" title="Alejar" @click="zoomOut">−</button>
            <span class="w-12 text-center text-xs font-medium text-tinta/60">{{ Math.round(zoom * 100) }}%</span>
            <button type="button" class="h-8 w-8 rounded-lg text-tinta/70 hover:bg-niebla" title="Acercar" @click="zoomIn">+</button>
            <button type="button" class="rounded-lg px-2 py-1 text-xs text-tinta/60 hover:bg-niebla" title="Ajustar a la pantalla" @click="fitToScreen">
                Ajustar
            </button>
        </div>

        <p class="pointer-events-none absolute bottom-4 left-4 rounded-lg bg-white/80 px-2 py-1 text-[11px] text-tinta/50">
            Arrastra las mesas para acomodarlas · Rueda del ratón para acercar
        </p>
    </div>
</template>
