<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import PlanCanvas from '@/Components/FloorPlan/PlanCanvas.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import Engine from '@/lib/weddingEngine';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    LinkIcon,
    TrashIcon,
    Squares2X2Icon,
    TableCellsIcon,
    UserGroupIcon,
    CheckCircleIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    guests: Array,
    tables: Array,
    groups: Array,
    elements: Array,
    relations: Array,
    venue: Object,
});

const toast = useToast();

// ── Estado del plano ─────────────────────────────────────────────
// Los objetos se comparten con el lienzo: al arrastrar una mesa o un
// elemento se actualizan aquí y se guardan con un pequeño retardo.
const plan = reactive({
    venue: { ...props.venue, sections: props.venue?.sections ?? [] },
    tables: (props.tables ?? []).map((table) => ({
        ...table,
        seat_distribution: table.seat_distribution ?? null,
    })),
    elements: (props.elements ?? []).map((element) => ({ ...element })),
    guests: (props.guests ?? []).map((guest) => ({ ...guest })),
    relations: (props.relations ?? []).map((relation) => ({ ...relation })),
});

const groups = computed(() => props.groups ?? []);

// ── Selección ────────────────────────────────────────────────────
const selectedTableId = ref(null);
const selectedElementId = ref(null);
const selectedSeat = ref(null);
const seatLinkGuestId = ref('');
const seatLinkType = ref('couple');

const selectedTable = computed(
    () => plan.tables.find((table) => table.id === selectedTableId.value) ?? null,
);
const selectedElement = computed(
    () => plan.elements.find((element) => element.id === selectedElementId.value) ?? null,
);
const selectedSeatGuest = computed(
    () => (selectedSeat.value
        ? guestAtSeat(selectedSeat.value.tableId, selectedSeat.value.number)
        : null),
);

/** Invitados que comparten la invitación (pareja o acompañantes). */
function invitationCompanions(guest) {
    if (!guest?.invitation_id) return [];

    return plan.guests.filter(
        (item) => item.id !== guest.id && item.invitation_id === guest.invitation_id,
    );
}

/**
 * Compañeros de un invitado: vínculos manuales + integrantes de su misma
 * invitación, sin repetir a nadie.
 */
function companionsOf(guest) {
    if (!guest) return [];

    const seen = new Set([guest.id]);
    const list = [];

    relationsOf(guest.id).forEach((relation) => {
        if (!relation.guest || seen.has(relation.guest.id)) return;

        seen.add(relation.guest.id);
        list.push({ guest: relation.guest, label: relationLabel(relation.type) });
    });

    invitationCompanions(guest).forEach((companion) => {
        if (seen.has(companion.id)) return;

        seen.add(companion.id);
        list.push({ guest: companion, label: 'Misma invitación' });
    });

    return list;
}

/** Compañeros del invitado sentado en el asiento seleccionado. */
const seatCompanions = computed(() => companionsOf(selectedSeatGuest.value));

/**
 * Invitado del que se muestran los compañeros arriba de la lista:
 * el del asiento seleccionado o, si no, el primero seleccionado en la lista.
 */
const companionsPanelGuest = computed(() => {
    if (selectedSeatGuest.value) return selectedSeatGuest.value;

    const selectedId = selectedGuestIds.value[0];

    return selectedId ? plan.guests.find((guest) => guest.id === selectedId) ?? null : null;
});

const companionsPanelList = computed(() => companionsOf(companionsPanelGuest.value));

/** Sienta a un compañero junto al invitado del asiento seleccionado. */
async function seatNextTo(companion) {
    const anchor = companionsPanelGuest.value;

    if (!anchor?.tableId) {
        notify('warn', 'Primero coloca a esta persona en una mesa.');
        return;
    }

    const table = tableById(anchor.tableId);
    const seat = anchor.seatId ? nextSeatNear(table, anchor.seatId) : firstFreeSeat(table);

    if (!seat) {
        notify('warn', `La mesa «${table.name}» está llena.`);
        return;
    }

    await placeGuest(companion.id, table.id, seat);
    notify('success', `${companion.full_name} quedó junto a ${anchor.full_name}.`);
}

function selectTable(id) {
    selectedTableId.value = id;
    selectedSeat.value = null;
    selectedElementId.value = null;
    showInspector.value = true;
}

function selectSeat(tableId, number) {
    if (selectedSeat.value?.tableId !== tableId || selectedSeat.value?.number !== number) {
        seatLinkGuestId.value = '';
    }

    selectedTableId.value = tableId;
    selectedSeat.value = { tableId, number };
    selectedElementId.value = null;
    showInspector.value = true;
    showGuests.value = false;
}

function selectElement(id) {
    selectedElementId.value = id;
    selectedTableId.value = null;
    selectedSeat.value = null;
    showInspector.value = true;
}

// ── Ayudas de asientos ───────────────────────────────────────────
function tableById(id) {
    return plan.tables.find((table) => table.id === id) ?? null;
}

function tableNameFor(guest) {
    return tableById(guest.tableId)?.name ?? guest.table_group ?? '';
}

function guestAtSeat(tableId, seatNumber) {
    return plan.guests.find(
        (guest) => guest.tableId === tableId && guest.seatId === seatNumber,
    ) ?? null;
}

function firstFreeSeat(table) {
    const taken = new Set(
        plan.guests
            .filter((guest) => guest.tableId === table.id && guest.seatId)
            .map((guest) => guest.seatId),
    );

    for (let seat = 1; seat <= table.capacity; seat++) {
        if (!taken.has(seat)) return seat;
    }

    return null;
}

function seatDistribution(table) {
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

/**
 * Vuelve a calcular mesa y asiento de los invitados a partir de lo que
 * devolvió el servidor (incluye a quien perdió su asiento al reemplazarlo).
 */
function applyGuestRows(rows) {
    (rows ?? []).forEach((row) => {
        const guest = plan.guests.find((item) => item.id === row.id);
        if (!guest) return;

        guest.table_group = row.table_group;
        guest.seat_number = row.seat_number;

        const table = plan.tables.find((item) => item.name === row.table_group);

        guest.tableId = table?.id ?? null;
        guest.seatId = table && row.seat_number ? row.seat_number : null;
    });
}

function hydrateGuest(guest) {
    guest.tableId = null;
    guest.seatId = null;

    if (!guest.table_group) return;

    const table = plan.tables.find((item) => item.name === guest.table_group);
    if (!table) return;

    const wanted = Number(guest.seat_number);
    const seatIsValid = Number.isInteger(wanted) && wanted >= 1 && wanted <= table.capacity;

    guest.tableId = table.id;
    guest.seatId = seatIsValid && !guestAtSeat(table.id, wanted)
        ? wanted
        : firstFreeSeat(table);
}

plan.guests.forEach(hydrateGuest);

onMounted(() => {
    // Las mesas heredadas pueden tener invitados sin asiento asignado; se
    // guarda el asiento calculado para que el plano sea consistente.
    const pending = plan.guests.filter(
        (guest) => guest.table_group && guest.tableId && guest.seatId && !Number(guest.seat_number),
    );

    if (pending.length) {
        sendAssignments(pending.map((guest) => ({
            guest_id: guest.id,
            table_group: guest.table_group,
            seat_number: guest.seatId,
        })));
    }
});

// ── Guardado automático ─────────────────────────────────────────
let saveTimer = null;
const saving = ref(false);
const lastSavedAt = ref(null);

function scheduleSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(persistPlan, 700);
}

function planPayload() {
    return {
        venue: {
            title: plan.venue.title,
            width: plan.venue.width,
            height: plan.venue.height,
            gridSize: plan.venue.gridSize,
            showGrid: plan.venue.showGrid,
            snapToGrid: plan.venue.snapToGrid,
            shape: plan.venue.shape ?? 'rect',
            sections: plan.venue.sections ?? [],
        },
        tables: plan.tables.map((table) => ({
            id: table.id,
            name: table.name,
            number: table.number ?? null,
            shape: table.shape,
            capacity: table.capacity,
            x: table.x,
            y: table.y,
            width: table.width,
            height: table.height,
            rotation: table.rotation ?? 0,
            seat_distribution: table.seat_distribution ?? null,
        })),
        elements: plan.elements.map((element) => ({
            id: element.id ?? null,
            kind: element.kind,
            name: element.name,
            x: element.x,
            y: element.y,
            width: element.width,
            height: element.height,
            rotation: element.rotation ?? 0,
            color: element.color ?? null,
        })),
    };
}

async function persistPlan() {
    saving.value = true;

    try {
        const hadNewElements = plan.elements.some((element) => !element.id);
        const { data } = await axios.post(route('admin.floorplan.save'), planPayload());

        if (hadNewElements) {
            plan.elements = data.elements;
            selectedElementId.value = null;
        }

        lastSavedAt.value = new Date();
    } catch (error) {
        notify('error', errorMessage(error), 'No se pudo guardar el plano');
    } finally {
        saving.value = false;
    }
}

// ── Avisos ──────────────────────────────────────────────────────
function notify(severity, detail, summary = null) {
    if (!detail) return;

    toast.add({
        severity,
        summary: summary ?? (severity === 'success' ? 'Listo' : 'Revisa esto'),
        detail,
        life: severity === 'error' ? 6000 : 4000,
    });
}

function errorMessage(error) {
    const errors = error?.response?.data?.errors;

    if (errors) {
        return Object.values(errors).flat()[0];
    }

    return error?.response?.data?.message ?? 'No se pudo guardar el cambio.';
}

const saveStatus = computed(() => {
    if (saving.value) return 'Guardando…';
    if (!lastSavedAt.value) return 'Los cambios se guardan solos';

    return `Guardado ${lastSavedAt.value.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}`;
});

// ── Estadísticas ────────────────────────────────────────────────
const capacity = computed(
    () => plan.tables.reduce((total, table) => total + Number(table.capacity || 0), 0),
);
const assignedCount = computed(
    () => plan.guests.filter((guest) => guest.tableId).length,
);
const pendingCount = computed(() => plan.guests.length - assignedCount.value);

// ── Mesas ───────────────────────────────────────────────────────
function nextTableNumber() {
    return Math.max(0, ...plan.tables.map((table) => Number(table.number) || 0)) + 1;
}

function nextTableName() {
    let number = nextTableNumber();

    while (plan.tables.some((table) => table.name === `Mesa ${number}`)) {
        number++;
    }

    return `Mesa ${number}`;
}

async function addTable() {
    const number = nextTableNumber();

    try {
        const { data } = await axios.post(route('admin.floorplan.tables.store'), {
            name: nextTableName(),
            number,
            shape: 'round',
            capacity: 8,
            x: 120 + (plan.tables.length % 4) * 340,
            y: 120 + Math.floor(plan.tables.length / 4) * 340,
            width: 180,
            height: 180,
            rotation: 0,
        });

        plan.tables.push({ ...data.table });
        selectTable(data.table.id);
        notify('success', `Mesa «${data.table.name}» agregada.`);
    } catch (error) {
        notify('error', errorMessage(error));
    }
}

async function duplicateTable(table) {
    const name = `${table.name} (copia)`;

    try {
        const { data } = await axios.post(route('admin.floorplan.tables.store'), {
            name,
            number: nextTableNumber(),
            shape: table.shape,
            capacity: table.capacity,
            x: Math.round(table.x + 40),
            y: Math.round(table.y + 40),
            width: table.width,
            height: table.height,
            rotation: table.rotation ?? 0,
            seat_distribution: table.seat_distribution,
        });

        plan.tables.push({ ...data.table });
        selectTable(data.table.id);
    } catch (error) {
        notify('error', errorMessage(error));
    }
}

async function removeTable(table) {
    try {
        await axios.delete(route('admin.floorplan.tables.destroy', table.id));

        plan.guests.forEach((guest) => {
            if (guest.tableId === table.id) {
                guest.tableId = null;
                guest.seatId = null;
                guest.table_group = null;
                guest.seat_number = null;
            }
        });

        plan.tables = plan.tables.filter((item) => item.id !== table.id);

        if (selectedTableId.value === table.id) {
            selectTable(null);
        }

        notify('success', `Mesa «${table.name}» eliminada.`);
    } catch (error) {
        notify('error', errorMessage(error));
    }
}

function renameTable(table, value) {
    const name = String(value ?? '').trim();

    if (!name || name === table.name) {
        return;
    }

    if (plan.tables.some((item) => item.id !== table.id && item.name === name)) {
        notify('error', 'Ya existe una mesa con ese nombre.');
        return;
    }

    const previous = table.name;
    table.name = name;
    plan.guests.forEach((guest) => {
        if (guest.table_group === previous) guest.table_group = name;
    });
    scheduleSave();
}

function changeShape(table, shape) {
    table.shape = shape;
    const size = Engine.defaultTableSize(shape);
    table.width = size.width;
    table.height = size.height;
    table.seat_distribution = null;
    scheduleSave();
}

function setCapacity(table, value) {
    const nextCapacity = Math.max(1, Math.min(40, Number(value) || 1));
    const seated = plan.guests.filter((guest) => guest.tableId === table.id).length;

    if (nextCapacity < seated) {
        notify('error', `La mesa tiene ${seated} invitados; no puede tener menos asientos.`);
        return;
    }

    table.capacity = nextCapacity;
    table.seat_distribution = null;
    reflowSeats(table);
    scheduleSave();
}

function setDistribution(table, side, value) {
    const distribution = { ...seatDistribution(table), [side]: Math.max(0, Number(value) || 0) };
    const total = ['top', 'right', 'bottom', 'left']
        .reduce((sum, key) => sum + (Number(distribution[key]) || 0), 0);

    if (total < 1) return;

    const current = plan.guests.filter((guest) => guest.tableId === table.id && guest.seatId).length;

    if (total < current) {
        notify('error', 'No puedes dejar menos asientos que invitados sentados.');
        return;
    }

    table.seat_distribution = distribution;
    table.capacity = total;
    reflowSeats(table);
    scheduleSave();
}

/**
 * Reacomoda los asientos que quedaron fuera de la capacidad nueva.
 */
function reflowSeats(table) {
    const taken = new Set(
        plan.guests
            .filter((guest) => guest.tableId === table.id && guest.seatId && guest.seatId <= table.capacity)
            .map((guest) => guest.seatId),
    );

    const changed = [];

    plan.guests
        .filter((guest) => guest.tableId === table.id && guest.seatId > table.capacity)
        .forEach((guest) => {
            let seat = 1;
            while (taken.has(seat) && seat <= table.capacity) seat++;

            if (seat > table.capacity) return;

            taken.add(seat);
            guest.seatId = seat;
            changed.push(guest);
        });

    if (changed.length) {
        sendAssignments(changed.map((guest) => ({
            guest_id: guest.id,
            table_group: table.name,
            seat_number: guest.seatId,
        })));
    }
}

// ── Elementos del salón ─────────────────────────────────────────
const elementPresets = Engine.ELEMENT_PRESETS;

function addElement(kind) {
    const preset = Engine.ELEMENT_PRESETS.find((item) => item.kind === kind);

    if (!preset) return;

    plan.elements.push({
        id: null,
        kind: preset.kind,
        name: preset.name,
        x: 140 + (plan.elements.length % 3) * 200,
        y: 260 + Math.floor(plan.elements.length / 3) * 170,
        width: preset.width,
        height: preset.height,
        rotation: 0,
        color: preset.color,
    });

    scheduleSave();
    notify('success', `${preset.name} agregado al salón.`);
}

function removeElement(element) {
    plan.elements = plan.elements.filter((item) => item !== element);

    if (selectedElementId.value === element.id) {
        selectElement(null);
    }

    scheduleSave();
}

// ── Invitados: filtros y selección ──────────────────────────────
const showGuests = ref(false);
const showInspector = ref(false);

const search = ref('');
const groupFilter = ref('');
const statusTab = ref('pending');
const selectedGuestIds = ref([]);
const bulkTableId = ref('');
const relationType = ref('couple');
const newGuestName = ref('');
const newGuestGroup = ref('');

function normalizeText(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();
}

const filteredGuests = computed(() => {
    const query = normalizeText(search.value);

    return plan.guests
        .filter((guest) => {
            if (groupFilter.value && String(guest.group_id ?? '') !== String(groupFilter.value)) {
                return false;
            }

            if (statusTab.value === 'pending' && guest.tableId) return false;
            if (statusTab.value === 'assigned' && !guest.tableId) return false;

            return !query || normalizeText(guest.full_name).includes(query);
        })
        .sort((a, b) => a.full_name.localeCompare(b.full_name, 'es'));
});

const pendingTotal = computed(() => plan.guests.filter((guest) => !guest.tableId).length);
const assignedTotal = computed(() => plan.guests.filter((guest) => guest.tableId).length);

function isSelected(guest) {
    return selectedGuestIds.value.includes(guest.id);
}

function toggleGuest(guest) {
    if (isSelected(guest)) {
        selectedGuestIds.value = selectedGuestIds.value.filter((id) => id !== guest.id);
    } else {
        selectedGuestIds.value = [...selectedGuestIds.value, guest.id];
    }
}

function onGuestDragStart(guest, event) {
    event.dataTransfer.setData('application/x-guest-id', String(guest.id));
    event.dataTransfer.setData('text/plain', String(guest.id));
    event.dataTransfer.effectAllowed = 'move';
}

function groupColor(groupId) {
    const palette = ['#8c6558', '#a37c6e', '#64748b', '#475569', '#94a3b8', '#0ea5e9', '#f59e0b', '#10b981', '#8b5cf6', '#ef4444'];
    const index = groups.value.findIndex((group) => group.id === groupId);

    return palette[(index < 0 ? 0 : index) % palette.length];
}

// ── Asignaciones ────────────────────────────────────────────────
async function sendAssignments(assignments) {
    if (!assignments.length) return;

    try {
        const { data } = await axios.post(route('admin.floorplan.assign'), { assignments });
        applyGuestRows(data.guests);
    } catch (error) {
        notify('error', errorMessage(error));
    }
}

async function placeGuest(guestId, tableId, seatNumber = null) {
    const guest = plan.guests.find((item) => item.id === guestId);
    const table = tableById(tableId);

    if (!guest || !table) return;

    const seat = seatNumber ?? firstFreeSeat(table);

    if (!seat) {
        notify('warn', `La mesa «${table.name}» está llena.`);
        return;
    }

    await sendAssignments([{
        guest_id: guest.id,
        table_group: table.name,
        seat_number: seat,
    }]);

    // Se selecciona el asiento para mostrar enseguida a su pareja o acompañante.
    selectSeat(table.id, seat);
    warnRelationConflicts(guest);
}

function handleDropGuest(guestId, tableId, seatNumber) {
    placeGuest(guestId, tableId, seatNumber);
}

/**
 * Clic en una mesa: si hay invitados seleccionados se sientan ahí;
 * si no, solo se selecciona la mesa para editarla.
 */
async function handleTableClick(tableId) {
    if (tableId && selectedGuestIds.value.length) {
        await assignSelectedToTable(tableId);
        return;
    }

    selectTable(tableId);
}

/**
 * Clic en un asiento: con un invitado seleccionado lo sienta ahí; si el
 * asiento está ocupado o no hay selección, muestra sus datos.
 */
async function handleSeatClick(tableId, number) {
    if (selectedGuestIds.value.length && !guestAtSeat(tableId, number)) {
        await placeGuest(selectedGuestIds.value[0], tableId, number);
        selectedGuestIds.value = selectedGuestIds.value.slice(1);
        return;
    }

    selectSeat(tableId, number);
}

async function assignSelectedToTable(tableId = null) {
    const table = tableById(Number(tableId ?? bulkTableId.value));

    if (!table || !selectedGuestIds.value.length) return;

    const guests = plan.guests.filter((guest) => selectedGuestIds.value.includes(guest.id));
    const taken = new Set(
        plan.guests
            .filter((guest) => guest.tableId === table.id && guest.seatId)
            .map((guest) => guest.seatId),
    );

    const assignments = [];
    let skipped = 0;

    guests.forEach((guest) => {
        let seat = null;
        for (let candidate = 1; candidate <= table.capacity; candidate++) {
            if (!taken.has(candidate)) {
                seat = candidate;
                break;
            }
        }

        if (seat === null) {
            skipped++;
            return;
        }

        taken.add(seat);
        assignments.push({
            guest_id: guest.id,
            table_group: table.name,
            seat_number: seat,
        });
    });

    if (!assignments.length) {
        notify('warn', `La mesa «${table.name}» está llena.`);
        return;
    }

    await sendAssignments(assignments);
    assignments.forEach((assignment) => {
        const guest = plan.guests.find((item) => item.id === assignment.guest_id);
        if (guest) warnRelationConflicts(guest);
    });

    const last = assignments[assignments.length - 1];
    selectSeat(table.id, last.seat_number);

    selectedGuestIds.value = [];
    bulkTableId.value = '';

    notify('success', `${assignments.length} invitado(s) asignado(s) a «${table.name}»${skipped ? `; ${skipped} sin asiento` : ''}.`);
}

async function unassignGuests(guestIds) {
    if (!guestIds.length) return;

    try {
        await axios.post(route('admin.floorplan.unassign'), { guest_ids: guestIds });

        guestIds.forEach((id) => {
            const guest = plan.guests.find((item) => item.id === id);
            if (!guest) return;

            guest.tableId = null;
            guest.seatId = null;
            guest.table_group = null;
            guest.seat_number = null;
        });

        selectedGuestIds.value = [];
    } catch (error) {
        notify('error', errorMessage(error));
    }
}

function unassignSelected() {
    unassignGuests([...selectedGuestIds.value]);
}

function releaseSeat() {
    const guest = selectedSeatGuest.value;
    if (guest) unassignGuests([guest.id]);
}

// ── Vínculos entre invitados ────────────────────────────────────
const relationTypes = {
    couple: 'Pareja',
    marriage: 'Matrimonio',
    family: 'Familia',
    companion: 'Acompañante',
};

function guestById(id) {
    return plan.guests.find((guest) => guest.id === id) ?? null;
}

function relationsOf(guestId) {
    return plan.relations
        .filter((relation) => relation.guest_id === guestId || relation.related_guest_id === guestId)
        .map((relation) => ({
            ...relation,
            guest: guestById(
                relation.guest_id === guestId ? relation.related_guest_id : relation.guest_id,
            ),
        }))
        .filter((relation) => relation.guest);
}

function relationLabel(type) {
    return relationTypes[type] ?? type;
}

/** Invitados disponibles para vincular desde el panel del asiento. */
const seatLinkOptions = computed(() => {
    const current = selectedSeatGuest.value;

    return plan.guests
        .filter((guest) => guest.id !== current?.id)
        .slice()
        .sort((a, b) => a.full_name.localeCompare(b.full_name, 'es'));
});

async function linkSeatGuest() {
    const guest = selectedSeatGuest.value;
    const partnerId = Number(seatLinkGuestId.value);

    if (!guest || !partnerId) return;

    try {
        const { data } = await axios.post(route('admin.floorplan.relations.store'), {
            type: seatLinkType.value,
            guest_ids: [guest.id, partnerId],
        });

        plan.relations = data.relations;
        seatLinkGuestId.value = '';
        notify('success', 'Vínculo creado. Ya puedes sentarlos juntos.');
    } catch (error) {
        notify('error', errorMessage(error));
    }
}

/** Texto corto con la pareja/acompañante para mostrarlo en la lista. */
function partnerSummary(guest) {
    const relation = relationsOf(guest.id)[0];

    if (relation) {
        return `${relationLabel(relation.type)}: ${relation.guest.full_name}`;
    }

    const companion = invitationCompanions(guest)[0];

    return companion ? `Invitación: ${companion.full_name}` : '';
}

async function linkSelectedGuests() {
    if (selectedGuestIds.value.length < 2) return;

    try {
        const { data } = await axios.post(route('admin.floorplan.relations.store'), {
            type: relationType.value,
            guest_ids: selectedGuestIds.value,
        });

        plan.relations = data.relations;
        notify('success', 'Vínculo creado entre los invitados seleccionados.');
    } catch (error) {
        notify('error', errorMessage(error));
    }
}

async function removeRelation(relation) {
    try {
        await axios.delete(route('admin.floorplan.relations.destroy', relation.id));
        plan.relations = plan.relations.filter((item) => item.id !== relation.id);
    } catch (error) {
        notify('error', errorMessage(error));
    }
}

function warnRelationConflicts(guest) {
    if (!guest?.tableId) return;

    const others = relationsOf(guest.id)
        .map((relation) => relation.guest)
        .filter((other) => other && other.tableId && other.tableId !== guest.tableId);

    if (!others.length) return;

    notify(
        'warn',
        `${others.map((other) => `${other.full_name} (${tableNameFor(other)})`).join(', ')} está(n) en otra mesa.`,
        'Vínculo en mesas distintas',
    );
}

/**
 * Asiento libre más cercano, empezando por el siguiente.
 */
function nextSeatNear(table, seatNumber) {
    for (let offset = 1; offset <= table.capacity; offset++) {
        const after = seatNumber + offset;
        if (after <= table.capacity && !guestAtSeat(table.id, after)) return after;

        const before = seatNumber - offset;
        if (before >= 1 && !guestAtSeat(table.id, before)) return before;
    }

    return null;
}

async function joinRelation(relation) {
    const first = guestById(relation.guest_id);
    const second = guestById(relation.related_guest_id);

    if (!first || !second) return;

    const target = first.tableId ? first : second;
    const moving = first.tableId ? second : first;
    const table = target.tableId ? tableById(target.tableId) : null;

    if (!table || moving.tableId === table.id) return;

    await placeGuest(moving.id, table.id, null);
}

// ── Alta rápida de invitados ────────────────────────────────────
async function addGuest() {
    const name = newGuestName.value.trim();

    if (!name) return;

    const [firstName, ...rest] = name.split(/\s+/);

    try {
        const { data } = await axios.post(route('admin.floorplan.guests.store'), {
            first_name: firstName,
            last_name: rest.join(' ') || null,
            guest_group_id: newGuestGroup.value || null,
        });

        plan.guests.push({ ...data.guest, tableId: null, seatId: null });
        newGuestName.value = '';
        notify('success', `«${data.guest.full_name}» agregado a la lista.`);
    } catch (error) {
        notify('error', errorMessage(error));
    }
}

// ── Salón: medidas en metros ────────────────────────────────────
function venueMeters(side) {
    return Math.round((Number(plan.venue[side]) / 100) * 10) / 10;
}

function setVenueMeters(side, value) {
    const meters = Number(value);

    if (!Number.isFinite(meters) || meters < 2) return;

    plan.venue[side] = Math.round(meters * 100);
    scheduleSave();
}
</script>

<template>
    <AppLayout title="Plano de salón">
        <div class="flex h-[calc(100vh-4rem)] flex-col bg-niebla">
            <!-- Barra superior -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-tinta/10 bg-white px-4 py-3">
                <div>
                    <h1 class="font-slab text-lg leading-tight text-tinta">Plano de salón</h1>
                    <p class="text-xs text-tinta/50">Arrastra invitados a las mesas y acomoda el salón a tu gusto.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 rounded-lg bg-niebla px-2 py-1 text-tinta/60">
                        <TableCellsIcon class="h-4 w-4 text-primary" /> {{ plan.tables.length }} mesas
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-lg bg-niebla px-2 py-1 text-tinta/60">
                        <Squares2X2Icon class="h-4 w-4 text-primary" /> {{ capacity }} asientos
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-lg bg-niebla px-2 py-1 text-tinta/60">
                        <CheckCircleIcon class="h-4 w-4 text-secondary" /> {{ assignedCount }} asignados
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-lg bg-niebla px-2 py-1 text-tinta/60">
                        <ClockIcon class="h-4 w-4 text-tinta/40" /> {{ pendingCount }} pendientes
                    </span>
                    <span class="text-[11px] text-tinta/40">{{ saveStatus }}</span>

                    <button type="button" @click="showGuests = !showGuests"
                        class="inline-flex items-center gap-1 rounded-xl border border-tinta/20 px-3 py-2 font-medium text-tinta/70 hover:bg-niebla lg:hidden">
                        <UserGroupIcon class="h-4 w-4" /> Invitados
                    </button>
                    <button type="button" @click="showInspector = !showInspector"
                        class="inline-flex items-center gap-1 rounded-xl border border-tinta/20 px-3 py-2 font-medium text-tinta/70 hover:bg-niebla xl:hidden">
                        <Squares2X2Icon class="h-4 w-4" /> Propiedades
                    </button>
                    <button type="button" @click="addTable"
                        class="inline-flex items-center gap-1 rounded-xl bg-primary px-3 py-2 font-medium text-white hover:bg-primary-dark">
                        <PlusIcon class="h-4 w-4" /> Agregar mesa
                    </button>
                    <select
                        class="rounded-xl border border-tinta/20 bg-white px-2 py-2 text-xs text-tinta/70"
                        @change="addElement($event.target.value); $event.target.value = ''"
                    >
                        <option value="">Agregar elemento…</option>
                        <option v-for="preset in elementPresets" :key="preset.kind" :value="preset.kind">
                            {{ preset.name }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="relative flex min-h-0 flex-1 overflow-hidden">
                <!-- Panel de invitados -->
                <aside
                    class="absolute inset-y-0 left-0 z-40 w-80 shrink-0 flex-col border-r border-tinta/10 bg-white shadow-xl lg:static lg:z-auto lg:flex lg:shadow-none"
                    :class="showGuests ? 'flex' : 'hidden'"
                >
                    <div class="border-b border-tinta/10 p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <h2 class="flex items-center gap-1 text-sm font-semibold text-tinta">
                                <UserGroupIcon class="h-4 w-4 text-primary" /> Invitados
                            </h2>
                            <span class="text-[11px] text-tinta/40">{{ plan.guests.length }}</span>
                        </div>

                        <div class="flex gap-1">
                            <input v-model="newGuestName" type="text" placeholder="Nombre del invitado…"
                                class="min-w-0 flex-1 rounded-lg border border-tinta/20 px-2 py-1.5 text-xs"
                                @keydown.enter="addGuest" />
                            <button type="button" class="rounded-lg bg-primary px-2 text-white hover:bg-primary-dark" title="Agregar invitado" @click="addGuest">
                                <PlusIcon class="h-4 w-4" />
                            </button>
                        </div>
                        <select v-model="newGuestGroup" class="mt-1 w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs">
                            <option value="">Sin grupo</option>
                            <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                        </select>

                        <div class="relative mt-2">
                            <MagnifyingGlassIcon class="absolute left-2 top-2 h-4 w-4 text-tinta/30" />
                            <input v-model="search" type="text" placeholder="Buscar invitado…"
                                class="w-full rounded-lg border border-tinta/20 pl-7 pr-2 py-1.5 text-xs" />
                        </div>

                        <div class="mt-2 grid grid-cols-2 gap-1">
                            <select v-model="groupFilter" class="rounded-lg border border-tinta/20 px-1.5 py-1.5 text-xs">
                                <option value="">Todos los grupos</option>
                                <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                            </select>
                            <select v-model="statusTab" class="rounded-lg border border-tinta/20 px-1.5 py-1.5 text-xs">
                                <option value="pending">Pendientes ({{ pendingTotal }})</option>
                                <option value="assigned">Asignados ({{ assignedTotal }})</option>
                                <option value="all">Todos</option>
                            </select>
                        </div>
                    </div>

                    <!-- Compañeros de la misma invitación, listos para arrastrar -->
                    <div v-if="companionsPanelGuest && companionsPanelList.length"
                        class="border-b border-tinta/10 bg-primary/5 p-2">
                        <p class="mb-1 truncate text-[11px] font-semibold text-tinta">
                            Compañeros de {{ companionsPanelGuest.full_name }}
                        </p>

                        <div v-for="companion in companionsPanelList" :key="companion.guest.id"
                            draggable="true"
                            class="mb-1 flex cursor-grab items-center gap-2 rounded-lg bg-white px-2 py-1.5 shadow-sm active:cursor-grabbing"
                            @dragstart="onGuestDragStart(companion.guest, $event)"
                            @click="toggleGuest(companion.guest)">
                            <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: groupColor(companion.guest.group_id) }"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[11px] font-medium text-tinta">{{ companion.guest.full_name }}</p>
                                <p class="truncate text-[10px] text-tinta/40">{{ companion.label }}</p>
                            </div>

                            <span v-if="companion.guest.tableId"
                                class="shrink-0 rounded bg-secondary/15 px-1.5 py-0.5 text-[10px] font-medium text-secondary-dark">
                                {{ tableNameFor(companion.guest) }}{{ companion.guest.seatId ? ` · ${companion.guest.seatId}` : '' }}
                            </span>
                            <button v-else-if="companionsPanelGuest.tableId" type="button"
                                class="shrink-0 rounded-lg bg-secondary px-2 py-1 text-[10px] font-medium text-white hover:bg-secondary-dark"
                                @click.stop="seatNextTo(companion.guest)">
                                Sentar al lado
                            </button>
                            <span v-else class="shrink-0 text-[10px] text-tinta/40">Pendiente</span>
                        </div>
                    </div>

                    <!-- Acciones masivas -->
                    <div class="border-b border-tinta/10 p-2">
                        <div class="flex gap-1">
                            <select v-model="bulkTableId" class="min-w-0 flex-1 rounded-lg border border-tinta/20 px-1.5 py-1.5 text-xs">
                                <option value="">Asignar a mesa…</option>
                                <option v-for="table in plan.tables" :key="table.id" :value="table.id">{{ table.name }}</option>
                            </select>
                            <button type="button"
                                class="rounded-lg bg-secondary px-2 py-1 text-xs font-medium text-white hover:bg-secondary-dark disabled:opacity-40"
                                :disabled="!selectedGuestIds.length || !bulkTableId"
                                @click="assignSelectedToTable">
                                Asignar
                            </button>
                        </div>

                        <div class="mt-1 flex gap-1">
                            <select v-model="relationType" class="min-w-0 flex-1 rounded-lg border border-tinta/20 px-1.5 py-1.5 text-xs">
                                <option v-for="(label, type) in relationTypes" :key="type" :value="type">{{ label }}</option>
                            </select>
                            <button type="button"
                                class="inline-flex items-center gap-1 rounded-lg bg-tinta px-2 py-1 text-xs font-medium text-white hover:bg-tinta-light disabled:opacity-40"
                                :disabled="selectedGuestIds.length < 2"
                                @click="linkSelectedGuests">
                                <LinkIcon class="h-3.5 w-3.5" /> Vincular
                            </button>
                        </div>

                        <p v-if="selectedGuestIds.length" class="mt-1 rounded-lg bg-primary/10 px-2 py-1 text-[10px] text-primary-dark">
                            Haz clic en una mesa o asiento del plano para sentar a {{ selectedGuestIds.length }} invitado(s).
                        </p>

                        <button v-if="selectedGuestIds.length" type="button"
                            class="mt-1 w-full rounded-lg border border-tinta/20 py-1 text-xs text-tinta/60 hover:bg-niebla"
                            @click="unassignSelected">
                            Quitar mesa a {{ selectedGuestIds.length }} seleccionado(s)
                        </button>
                    </div>

                    <!-- Lista -->
                    <div class="min-h-0 flex-1 overflow-y-auto p-2">
                        <p v-if="!filteredGuests.length" class="px-2 py-6 text-center text-xs text-tinta/40">
                            No hay invitados con estos filtros.
                        </p>

                        <div
                            v-for="guest in filteredGuests"
                            :key="guest.id"
                            draggable="true"
                            class="mb-1 flex cursor-grab items-center gap-2 rounded-xl border px-2 py-1.5 text-sm active:cursor-grabbing"
                            :class="isSelected(guest) ? 'border-primary bg-primary/5' : 'border-transparent hover:bg-niebla'"
                            @dragstart="onGuestDragStart(guest, $event)"
                            @click="toggleGuest(guest)"
                        >
                            <input type="checkbox" :checked="isSelected(guest)" class="rounded border-tinta/30" @click.stop="toggleGuest(guest)" />
                            <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: groupColor(guest.group_id) }"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-medium text-tinta">{{ guest.full_name }}</p>
                                <p class="truncate text-[10px] text-tinta/40">{{ guest.group_name ?? 'Sin grupo' }}</p>
                                <p v-if="partnerSummary(guest)" class="truncate text-[10px] font-medium text-primary">
                                    🔗 {{ partnerSummary(guest) }}
                                </p>
                            </div>
                            <span v-if="guest.tableId" class="shrink-0 rounded-md bg-secondary/15 px-1.5 py-0.5 text-[10px] font-medium text-secondary-dark">
                                {{ tableNameFor(guest) }}
                            </span>
                            <span v-else class="shrink-0 rounded-md bg-tinta/5 px-1.5 py-0.5 text-[10px] text-tinta/40">Pendiente</span>
                        </div>
                    </div>
                </aside>

                <!-- Lienzo -->
                <PlanCanvas
                    :venue="plan.venue"
                    :tables="plan.tables"
                    :elements="plan.elements"
                    :guests="plan.guests"
                    :relations="plan.relations"
                    :selected-table-id="selectedTableId"
                    :selected-element-id="selectedElementId"
                    :selected-seat="selectedSeat"
                    @select-table="handleTableClick"
                    @select-seat="handleSeatClick"
                    @select-element="selectElement"
                    @drop-guest="handleDropGuest"
                    @layout-changed="scheduleSave"
                />

                <!-- Inspector -->
                <aside
                    class="absolute inset-y-0 right-0 z-40 w-80 shrink-0 overflow-y-auto border-l border-tinta/10 bg-white shadow-xl xl:static xl:z-auto xl:block xl:shadow-none"
                    :class="showInspector ? 'block' : 'hidden'"
                >
                    <!-- Salón -->
                    <section class="border-b border-tinta/10 p-3">
                        <h2 class="mb-2 text-sm font-semibold text-tinta">Salón</h2>

                        <label class="mb-1 block text-[11px] font-medium text-tinta/50">Nombre del salón</label>
                        <input v-model="plan.venue.title" type="text"
                            class="mb-2 w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs"
                            @change="scheduleSave" />

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-tinta/50">Ancho (m)</label>
                                <input :value="venueMeters('width')" type="number" min="2" step="0.5"
                                    class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs"
                                    @change="setVenueMeters('width', $event.target.value)" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-tinta/50">Largo (m)</label>
                                <input :value="venueMeters('height')" type="number" min="2" step="0.5"
                                    class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs"
                                    @change="setVenueMeters('height', $event.target.value)" />
                            </div>
                        </div>

                        <label class="mt-2 flex items-center gap-2 text-xs text-tinta/60">
                            <input v-model="plan.venue.showGrid" type="checkbox" class="rounded border-tinta/30" @change="scheduleSave" />
                            Mostrar cuadrícula
                        </label>
                        <label class="mt-1 flex items-center gap-2 text-xs text-tinta/60">
                            <input v-model="plan.venue.snapToGrid" type="checkbox" class="rounded border-tinta/30" @change="scheduleSave" />
                            Ajustar al acomodar
                        </label>
                    </section>

                    <!-- Mesa seleccionada -->
                    <section v-if="selectedTable" class="border-b border-tinta/10 p-3">
                        <h2 class="mb-2 text-sm font-semibold text-tinta">Mesa seleccionada</h2>

                        <label class="mb-1 block text-[11px] font-medium text-tinta/50">Nombre</label>
                        <input :value="selectedTable.name" type="text"
                            class="mb-2 w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs"
                            @change="renameTable(selectedTable, $event.target.value)" />

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-tinta/50">Número</label>
                                <input v-model.number="selectedTable.number" type="number" min="1"
                                    class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs" @change="scheduleSave" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-tinta/50">Forma</label>
                                <select :value="selectedTable.shape" class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs"
                                    @change="changeShape(selectedTable, $event.target.value)">
                                    <option value="round">Redonda</option>
                                    <option value="square">Cuadrada</option>
                                    <option value="rect">Rectangular</option>
                                </select>
                            </div>
                        </div>

                        <label class="mt-2 mb-1 block text-[11px] font-medium text-tinta/50">Asientos ({{ selectedTable.capacity }})</label>
                        <input :value="selectedTable.capacity" type="number" min="1" max="40"
                            class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs"
                            @change="setCapacity(selectedTable, $event.target.value)" />

                        <div v-if="selectedTable.shape !== 'round'" class="mt-2 grid grid-cols-4 gap-1">
                            <div v-for="side in ['top', 'right', 'bottom', 'left']" :key="side">
                                <label class="mb-1 block text-[10px] text-tinta/40">{{ { top: 'Arriba', right: 'Der.', bottom: 'Abajo', left: 'Izq.' }[side] }}</label>
                                <input :value="seatDistribution(selectedTable)[side]" type="number" min="0"
                                    class="w-full rounded-lg border border-tinta/20 px-1 py-1 text-xs"
                                    @change="setDistribution(selectedTable, side, $event.target.value)" />
                            </div>
                        </div>

                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-tinta/50">Ancho (px)</label>
                                <input v-model.number="selectedTable.width" type="number" min="40"
                                    class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs" @change="scheduleSave" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-tinta/50">Alto (px)</label>
                                <input v-model.number="selectedTable.height" type="number" min="40"
                                    class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs" @change="scheduleSave" />
                            </div>
                        </div>

                        <label class="mt-2 mb-1 block text-[11px] font-medium text-tinta/50">Rotación (grados)</label>
                        <input v-model.number="selectedTable.rotation" type="number" min="0" max="359"
                            class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs" @change="scheduleSave" />

                        <div class="mt-3 flex gap-1">
                            <button type="button" class="flex-1 rounded-lg border border-tinta/20 py-1.5 text-xs text-tinta/70 hover:bg-niebla"
                                @click="duplicateTable(selectedTable)">
                                Duplicar
                            </button>
                            <ConfirmDeleteModal
                                :message="`¿Eliminar la mesa «${selectedTable.name}»? Sus invitados quedarán sin mesa.`"
                                confirm-label="Eliminar mesa"
                                @confirm="removeTable(selectedTable)"
                            >
                                <template #default="{ open }">
                                    <button type="button" class="flex-1 rounded-lg bg-red-500 py-1.5 text-xs font-medium text-white hover:bg-red-600" @click="open()">
                                        Eliminar
                                    </button>
                                </template>
                            </ConfirmDeleteModal>
                        </div>
                    </section>

                    <!-- Asiento seleccionado -->
                    <section v-if="selectedSeat" class="border-b border-tinta/10 p-3">
                        <h2 class="mb-2 text-sm font-semibold text-tinta">
                            {{ tableById(selectedSeat.tableId)?.name }} · Asiento {{ selectedSeat.number }}
                        </h2>

                        <template v-if="selectedSeatGuest">
                            <p class="text-xs font-medium text-tinta">{{ selectedSeatGuest.full_name }}</p>
                            <p class="mb-2 text-[11px] text-tinta/40">{{ selectedSeatGuest.group_name ?? 'Sin grupo' }}</p>

                            <!-- Pareja / acompañantes del invitado -->
                            <div class="rounded-xl border border-tinta/10 bg-niebla/60 p-2">
                                <p class="text-[11px] font-semibold text-tinta/60">¿Quién va con esta persona?</p>

                                <p v-if="!seatCompanions.length" class="mt-1 text-[11px] text-tinta/40">
                                    Esta persona no tiene acompañantes en su invitación ni vínculos.
                                </p>

                                <div
                                    v-for="companion in seatCompanions"
                                    :key="companion.guest.id"
                                    class="mt-1.5 rounded-lg bg-white px-2 py-1.5"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-[11px] text-tinta">{{ companion.guest.full_name }}</p>
                                            <p class="text-[10px] text-tinta/40">{{ companion.label }}</p>
                                        </div>

                                        <span
                                            v-if="companion.guest.tableId === selectedSeat.tableId && companion.guest.seatId"
                                            class="shrink-0 rounded bg-secondary/15 px-1.5 py-0.5 text-[10px] font-medium text-secondary-dark"
                                        >
                                            Asiento {{ companion.guest.seatId }}
                                        </span>

                                        <button
                                            v-else
                                            type="button"
                                            class="shrink-0 rounded-lg bg-secondary px-2 py-1 text-[10px] font-medium text-white hover:bg-secondary-dark"
                                            :title="`Agregar a ${companion.guest.full_name} en esta mesa`"
                                            @click="seatNextTo(companion.guest)"
                                        >
                                            {{ companion.guest.tableId ? 'Traer a esta mesa' : 'Sentar en el siguiente asiento' }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Vincular desde el mismo panel del asiento -->
                                <div class="mt-2 border-t border-tinta/10 pt-2">
                                    <p class="text-[10px] text-tinta/40">Agregar pareja o acompañante</p>
                                    <select v-model="seatLinkGuestId"
                                        class="mt-1 w-full rounded-lg border border-tinta/20 px-1.5 py-1 text-[11px]">
                                        <option value="">Elegir invitado…</option>
                                        <option v-for="option in seatLinkOptions" :key="option.id" :value="option.id">
                                            {{ option.full_name }}
                                        </option>
                                    </select>
                                    <div class="mt-1 flex gap-1">
                                        <select v-model="seatLinkType"
                                            class="min-w-0 flex-1 rounded-lg border border-tinta/20 px-1.5 py-1 text-[11px]">
                                            <option v-for="(label, type) in relationTypes" :key="type" :value="type">
                                                {{ label }}
                                            </option>
                                        </select>
                                        <button type="button"
                                            class="rounded-lg bg-tinta px-2 py-1 text-[10px] font-medium text-white hover:bg-tinta-light disabled:opacity-40"
                                            :disabled="!seatLinkGuestId"
                                            @click="linkSeatGuest">
                                            Vincular
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="mt-2 w-full rounded-lg border border-tinta/20 py-1.5 text-xs text-tinta/70 hover:bg-niebla"
                                @click="releaseSeat">
                                Liberar asiento
                            </button>
                        </template>
                        <p v-else class="text-xs text-tinta/40">Asiento libre. Arrastra un invitado hasta aquí.</p>
                    </section>

                    <!-- Elemento seleccionado -->
                    <section v-if="selectedElement" class="border-b border-tinta/10 p-3">
                        <h2 class="mb-2 text-sm font-semibold text-tinta">Elemento del salón</h2>
                        <label class="mb-1 block text-[11px] font-medium text-tinta/50">Nombre</label>
                        <input v-model="selectedElement.name" type="text"
                            class="mb-2 w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs" @change="scheduleSave" />

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-tinta/50">Ancho (px)</label>
                                <input v-model.number="selectedElement.width" type="number" min="10"
                                    class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs" @change="scheduleSave" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-tinta/50">Alto (px)</label>
                                <input v-model.number="selectedElement.height" type="number" min="10"
                                    class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs" @change="scheduleSave" />
                            </div>
                        </div>

                        <label class="mt-2 mb-1 block text-[11px] font-medium text-tinta/50">Rotación (grados)</label>
                        <input v-model.number="selectedElement.rotation" type="number" min="0" max="359"
                            class="w-full rounded-lg border border-tinta/20 px-2 py-1.5 text-xs" @change="scheduleSave" />

                        <button type="button" class="mt-3 w-full rounded-lg bg-red-500 py-1.5 text-xs font-medium text-white hover:bg-red-600"
                            @click="removeElement(selectedElement)">
                            Eliminar elemento
                        </button>
                    </section>

                    <!-- Vínculos -->
                    <section class="p-3">
                        <h2 class="mb-2 text-sm font-semibold text-tinta">Vínculos entre invitados</h2>

                        <p v-if="!plan.relations.length" class="text-[11px] text-tinta/40">
                            Selecciona dos o más invitados y usa «Vincular» para avisar si quedan en mesas distintas.
                        </p>

                        <div v-for="relation in plan.relations" :key="relation.id"
                            class="mb-1.5 rounded-lg border border-tinta/10 px-2 py-1.5 text-[11px]">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-tinta">
                                        {{ guestById(relation.guest_id)?.full_name }}
                                        <span class="text-tinta/40">·</span>
                                        {{ guestById(relation.related_guest_id)?.full_name }}
                                    </p>
                                    <p class="text-tinta/40">{{ relationLabel(relation.type) }}</p>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <button v-if="guestById(relation.guest_id)?.tableId && guestById(relation.related_guest_id)?.tableId && guestById(relation.guest_id)?.tableId !== guestById(relation.related_guest_id)?.tableId"
                                        type="button" class="rounded p-1 text-tinta/40 hover:bg-niebla hover:text-primary"
                                        title="Juntar en la misma mesa" @click="joinRelation(relation)">
                                        <LinkIcon class="h-3.5 w-3.5" />
                                    </button>
                                    <button type="button" class="rounded p-1 text-tinta/40 hover:bg-red-50 hover:text-red-500"
                                        title="Eliminar vínculo" @click="removeRelation(relation)">
                                        <TrashIcon class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
