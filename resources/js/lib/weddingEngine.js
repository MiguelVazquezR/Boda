/**
 * Motor de datos del Diseñador de Salón (Boda Eli y José).
 * Funciones puras / mutaciones de estado, sin DOM.
 */
const WeddingEngine = (function () {
  const SCHEMA_VERSION = 3;
  const PX_PER_METER = 100;

  const DEFAULT_GROUPS = [
    { id: "g_novio", name: "Familia del novio", color: "#8c6558" },
    { id: "g_novia", name: "Familia de la novia", color: "#a37c6e" },
    { id: "g_amigos", name: "Amigos", color: "#64748b" },
    { id: "g_trabajo", name: "Compañeros de trabajo", color: "#475569" },
    { id: "g_otros", name: "Otros", color: "#94a3b8" }
  ];

  const RELATION_TYPES = {
    couple: "Pareja",
    marriage: "Matrimonio",
    family: "Familia",
    companion: "Acompañante"
  };

  const ELEMENT_PRESETS = [
    { kind: "dance_floor", name: "Pista de baile", icon: "fa-music", color: "#f1f5f9", width: 400, height: 400 },
    { kind: "head_table", name: "Mesa de los novios", icon: "fa-heart", color: "#f8e8e4", width: 380, height: 90 },
    { kind: "gifts", name: "Mesa de regalos", icon: "fa-gift", color: "#fef3c7", width: 220, height: 90 },
    { kind: "stage", name: "Escenario", icon: "fa-microphone", color: "#e2e8f0", width: 420, height: 180 },
    { kind: "dj", name: "Cabina DJ", icon: "fa-headphones", color: "#e0e7ff", width: 160, height: 120 },
    { kind: "buffet", name: "Buffet", icon: "fa-utensils", color: "#ffedd5", width: 360, height: 100 },
    { kind: "bar", name: "Barra de bebidas", icon: "fa-wine-glass", color: "#fce7f3", width: 280, height: 90 },
    { kind: "door", name: "Puerta / Acceso", icon: "fa-door-open", color: "#fef08a", width: 150, height: 40 },
    { kind: "window", name: "Ventana", icon: "fa-window-maximize", color: "#bae6fd", width: 200, height: 30 },
    { kind: "column", name: "Columna", icon: "fa-cube", color: "#94a3b8", width: 60, height: 60 },
    { kind: "wall", name: "Pared", icon: "fa-square", color: "#cbd5e1", width: 400, height: 24 },
    { kind: "restroom", name: "Baños", icon: "fa-restroom", color: "#dcfce7", width: 180, height: 140 },
    { kind: "custom", name: "Elemento", icon: "fa-shapes", color: "#e2e8f0", width: 180, height: 120 }
  ];

  function uid(prefix) {
    return prefix + "_" + Date.now().toString(36) + "_" + Math.random().toString(36).slice(2, 8);
  }

  function clone(obj) {
    return JSON.parse(JSON.stringify(obj));
  }

  function sameId(a, b) {
    return a != null && b != null && String(a) === String(b);
  }

  function defaultVenue() {
    return {
      title: "Salón Principal — Recepción",
      width: 1500,
      height: 3000,
      gridSize: 20,
      showGrid: true,
      snapToGrid: false,
      shape: "rect",
      cutWidth: 500,
      cutHeight: 1000,
      sections: []
    };
  }

  function createInitialState() {
    return {
      schemaVersion: SCHEMA_VERSION,
      venue: defaultVenue(),
      tables: [],
      elements: [],
      guests: [],
      groups: clone(DEFAULT_GROUPS),
      relations: [],
      versions: [],
      nextTableNumber: 1,
      nextGuestId: 1,
      nextGroupId: 1,
      nextRelationId: 1
    };
  }

  function ensureGroups(state) {
    if (!Array.isArray(state.groups) || !state.groups.length) {
      state.groups = clone(DEFAULT_GROUPS);
    }
  }

  function migrateState(raw) {
    const state = createInitialState();
    if (!raw || typeof raw !== "object") return state;

    if (raw.venue) state.venue = Object.assign(state.venue, raw.venue);
    if (!Array.isArray(state.venue.sections)) state.venue.sections = [];
    state.venue.sections.forEach((section) => {
      if (!section.id) section.id = uid("sec");
      if (!section.name) section.name = "Sección";
      section.kind = "section";
      section.x = Number(section.x) || 0;
      section.y = Number(section.y) || 0;
      section.width = Number(section.width) || PX_PER_METER * 4;
      section.height = Number(section.height) || PX_PER_METER * 4;
    });
    state.tables = Array.isArray(raw.tables) ? raw.tables : [];
    state.elements = Array.isArray(raw.elements) ? raw.elements : [];
    state.guests = Array.isArray(raw.guests) ? raw.guests : [];
    state.groups = Array.isArray(raw.groups) && raw.groups.length ? raw.groups : clone(DEFAULT_GROUPS);
    state.relations = Array.isArray(raw.relations) ? raw.relations : [];
    state.versions = Array.isArray(raw.versions) ? raw.versions : [];
    state.nextTableNumber = raw.nextTableNumber || 1;
    state.nextGuestId = raw.nextGuestId || 1;
    state.nextGroupId = raw.nextGroupId || 1;
    state.nextRelationId = raw.nextRelationId || 1;

    state.tables.forEach((table) => {
      if (!table.id) table.id = uid("table");
      if (typeof table.number !== "number") table.number = 0;
      if (!table.name) table.name = "Mesa " + table.number;
      if (!table.shape) table.shape = "round";
      table.x = Number(table.x) || 0;
      table.y = Number(table.y) || 0;
      table.width = Number(table.width) || 180;
      table.height = Number(table.height) || 180;
      table.rotation = Number(table.rotation) || 0;
      table.capacity = Number(table.capacity) || (table.seats && table.seats.length) || 8;
      if (!table.seatDistribution) {
        table.seatDistribution = defaultSeatDistribution(table.shape, table.capacity);
      }
      if (!Array.isArray(table.seats) || table.seats.length !== table.capacity) {
        rebuildSeatsKeepingAssignments(table, table.capacity);
      } else {
        table.seats.forEach((seat, i) => {
          if (!seat.id) seat.id = table.id + "_seat_" + (i + 1);
          if (!seat.number) seat.number = i + 1;
          if (seat.guestId === undefined) seat.guestId = null;
        });
      }
    });

    const maxTableNum = state.tables.reduce((m, t) => Math.max(m, t.number || 0), 0);
    if (state.nextTableNumber <= maxTableNum) state.nextTableNumber = maxTableNum + 1;

    state.elements.forEach((el) => {
      if (!el.id) el.id = uid("el");
      if (!el.kind) el.kind = "custom";
      if (!el.icon) el.icon = "fa-shapes";
      el.rotation = Number(el.rotation) || 0;
      el.width = Number(el.width) || 180;
      el.height = Number(el.height) || 120;
    });

    let maxGuest = 0;
    state.guests.forEach((g) => {
      const n = Number(g.id);
      if (!Number.isNaN(n)) maxGuest = Math.max(maxGuest, n);
      if (!g.status) g.status = g.tableId ? "assigned" : "pending";
      if (!g.groupId) g.groupId = "g_otros";
      if (!g.category) g.category = "";
      if (g.tableId === undefined) g.tableId = null;
      if (g.seatId === undefined) g.seatId = null;
    });
    if (state.nextGuestId <= maxGuest) state.nextGuestId = maxGuest + 1;

    reconcileAssignments(state);
    state.schemaVersion = SCHEMA_VERSION;
    ensureGroups(state);
    return state;
  }

  function defaultSeatDistribution(shape, capacity) {
    const count = Math.max(0, Number(capacity) || 0);
    if (shape === "round") return { top: 0, right: 0, bottom: 0, left: 0 };
    const top = Math.ceil(count / 4);
    const bottom = Math.ceil(count / 4);
    const left = Math.floor((count - top - bottom) / 2);
    const right = count - top - bottom - left;
    return { top, right, bottom, left };
  }

  function findTable(state, tableId) {
    return state.tables.find((t) => sameId(t.id, tableId));
  }

  function findGuest(state, guestId) {
    return state.guests.find((g) => sameId(g.id, guestId));
  }

  function findSeat(table, seatId) {
    if (!table || !table.seats) return null;
    return table.seats.find((s) => sameId(s.id, seatId));
  }

  function findGroup(state, groupId) {
    return (state.groups || []).find((g) => sameId(g.id, groupId));
  }

  function rebuildSeatsKeepingAssignments(table, newCapacity) {
    const current = Array.isArray(table.seats) ? table.seats.slice() : [];
    const occupied = current.filter((s) => s.guestId != null);
    if (newCapacity < occupied.length) {
      return { ok: false, error: "occupied_exceeds_capacity", occupied: occupied.length };
    }
    const seats = current.slice();
    while (seats.length > newCapacity) {
      let emptyIndex = -1;
      for (let i = seats.length - 1; i >= 0; i--) {
        if (seats[i].guestId == null) {
          emptyIndex = i;
          break;
        }
      }
      if (emptyIndex === -1) {
        return { ok: false, error: "occupied_exceeds_capacity", occupied: occupied.length };
      }
      seats.splice(emptyIndex, 1);
    }
    while (seats.length < newCapacity) {
      const n = seats.length + 1;
      seats.push({
        id: table.id + "_seat_" + n + "_" + uid("s"),
        number: n,
        guestId: null
      });
    }
    seats.forEach((seat, i) => {
      if (!seat.id) seat.id = table.id + "_seat_" + (i + 1);
      seat.number = i + 1;
      if (seat.guestId === undefined) seat.guestId = null;
    });
    table.seats = seats;
    table.capacity = newCapacity;
    return { ok: true };
  }

  function defaultTableSize(shape) {
    if (shape === "rect") return { width: 240, height: 120 };
    if (shape === "square") return { width: 160, height: 160 };
    return { width: 180, height: 180 };
  }

  function addTable(state, options) {
    const shape = options.shape || "round";
    const capacity = Math.max(1, Math.min(40, Number(options.capacity) || (shape === "round" ? 10 : 8)));
    const size = defaultTableSize(shape);
    const tableNum = state.nextTableNumber++;
    const id = uid("table");
    const seats = [];
    for (let i = 1; i <= capacity; i++) {
      seats.push({ id: id + "_seat_" + i, number: i, guestId: null });
    }
    const table = {
      id,
      number: tableNum,
      name: options.name || "Mesa " + tableNum,
      shape,
      x: options.x != null ? options.x : 400,
      y: options.y != null ? options.y : 400,
      width: options.width || size.width,
      height: options.height || size.height,
      capacity,
      seatDistribution: options.seatDistribution || defaultSeatDistribution(shape, capacity),
      rotation: options.rotation || 0,
      seats
    };
    state.tables.push(table);
    return table;
  }

  function duplicateTable(state, tableId) {
    const source = findTable(state, tableId);
    if (!source) return null;
    const copy = addTable(state, {
      shape: source.shape,
      capacity: source.capacity,
      name: source.name + " (copia)",
      x: source.x + 40,
      y: source.y + 40,
      width: source.width,
      height: source.height,
      rotation: source.rotation,
      seatDistribution: clone(source.seatDistribution)
    });
    return copy;
  }

  function deleteTable(state, tableId) {
    const table = findTable(state, tableId);
    if (!table) return [];
    const released = [];
    (table.seats || []).forEach((seat) => {
      if (seat.guestId != null) {
        const guest = findGuest(state, seat.guestId);
        if (guest) {
          guest.tableId = null;
          guest.seatId = null;
          guest.status = "pending";
          released.push(guest.id);
        }
      }
    });
    state.tables = state.tables.filter((t) => !sameId(t.id, tableId));
    return released;
  }

  function updateTableCapacity(state, tableId, newCapacity) {
    const table = findTable(state, tableId);
    if (!table) return { ok: false, error: "missing_table" };
    newCapacity = parseInt(newCapacity, 10);
    if (isNaN(newCapacity) || newCapacity < 1 || newCapacity > 40) {
      return { ok: false, error: "invalid_capacity" };
    }
    const result = rebuildSeatsKeepingAssignments(table, newCapacity);
    if (!result.ok) return result;
    if (table.shape !== "round") {
      const d = table.seatDistribution || { top: 0, right: 0, bottom: 0, left: 0 };
      const sum = (Number(d.top) || 0) + (Number(d.right) || 0) + (Number(d.bottom) || 0) + (Number(d.left) || 0);
      if (sum !== newCapacity) table.seatDistribution = defaultSeatDistribution(table.shape, newCapacity);
    }
    reconcileAssignments(state);
    return { ok: true, table };
  }

  function updateSeatDistribution(state, tableId, side, value) {
    const table = findTable(state, tableId);
    if (!table) return { ok: false, error: "missing_table" };
    if (!table.seatDistribution) table.seatDistribution = { top: 0, right: 0, bottom: 0, left: 0 };
    const previous = table.seatDistribution[side];
    const parsed = parseInt(value, 10);
    table.seatDistribution[side] = Number.isNaN(parsed) ? 0 : Math.max(0, parsed);
    const d = table.seatDistribution;
    const newCapacity = (Number(d.top) || 0) + (Number(d.right) || 0) + (Number(d.bottom) || 0) + (Number(d.left) || 0);
    if (newCapacity < 1) {
      table.seatDistribution[side] = previous;
      return { ok: false, error: "empty_distribution" };
    }
    const chosen = clone(table.seatDistribution);
    const result = updateTableCapacity(state, tableId, newCapacity);
    if (!result.ok) {
      table.seatDistribution[side] = previous;
      return result;
    }
    table.seatDistribution = chosen;
    return result;
  }

  function clearGuestSeat(state, guest) {
    if (!guest) return;
    if (guest.tableId) {
      const table = findTable(state, guest.tableId);
      if (table) {
        table.seats.forEach((seat) => {
          if (sameId(seat.guestId, guest.id) || sameId(seat.id, guest.seatId)) {
            seat.guestId = null;
          }
        });
      }
    }
    guest.tableId = null;
    guest.seatId = null;
    guest.status = "pending";
  }

  function relatedGuestsOnOtherTables(state, guest, targetTableId) {
    const relatedIds = new Set();
    (state.relations || []).forEach((rel) => {
      if ((rel.guestIds || []).some((id) => sameId(id, guest.id))) {
        rel.guestIds.forEach((id) => {
          if (!sameId(id, guest.id)) relatedIds.add(String(id));
        });
      }
    });
    const warnings = [];
    relatedIds.forEach((id) => {
      const other = findGuest(state, id);
      if (other && other.status === "assigned" && other.tableId && !sameId(other.tableId, targetTableId)) {
        const otherTable = findTable(state, other.tableId);
        warnings.push({
          guest: other,
          tableName: otherTable ? otherTable.name : "otra mesa"
        });
      }
    });
    return warnings;
  }

  function assignGuestToSeat(state, guestId, tableId, seatId, options) {
    options = options || {};
    const guest = findGuest(state, guestId);
    const table = findTable(state, tableId);
    if (!guest) return { ok: false, error: "missing_guest" };
    if (!table) return { ok: false, error: "missing_table" };
    const seat = findSeat(table, seatId);
    if (!seat) return { ok: false, error: "missing_seat" };

    if (sameId(seat.guestId, guest.id)) {
      return { ok: true, noop: true, guest, table, seat };
    }

    if (seat.guestId != null && !sameId(seat.guestId, guest.id)) {
      const occupant = findGuest(state, seat.guestId);
      if (options.mode === "swap" && occupant) {
        return swapGuests(state, guest.id, occupant.id);
      }
      if (options.mode !== "replace") {
        return { ok: false, error: "seat_occupied", occupant, table, seat };
      }
      clearGuestSeat(state, occupant);
    }

    const alreadyElsewhere = state.tables.some((t) =>
      t.seats.some((s) => sameId(s.guestId, guest.id) && !sameId(s.id, seat.id))
    );
    if (alreadyElsewhere || guest.tableId) {
      clearGuestSeat(state, guest);
    }

    seat.guestId = guest.id;
    guest.tableId = table.id;
    guest.seatId = seat.id;
    guest.status = "assigned";

    const relationWarnings = relatedGuestsOnOtherTables(state, guest, table.id);
    return { ok: true, guest, table, seat, relationWarnings };
  }

  function unassignSeat(state, tableId, seatId) {
    const table = findTable(state, tableId);
    if (!table) return { ok: false, error: "missing_table" };
    const seat = findSeat(table, seatId);
    if (!seat || seat.guestId == null) return { ok: false, error: "empty_seat" };
    const guest = findGuest(state, seat.guestId);
    seat.guestId = null;
    if (guest) {
      guest.tableId = null;
      guest.seatId = null;
      guest.status = "pending";
    }
    return { ok: true, guest, table, seat };
  }

  function unassignGuest(state, guestId) {
    const guest = findGuest(state, guestId);
    if (!guest) return { ok: false, error: "missing_guest" };
    clearGuestSeat(state, guest);
    return { ok: true, guest };
  }

  function swapGuests(state, guestIdA, guestIdB) {
    const a = findGuest(state, guestIdA);
    const b = findGuest(state, guestIdB);
    if (!a || !b) return { ok: false, error: "missing_guest" };
    const tableA = findTable(state, a.tableId);
    const tableB = findTable(state, b.tableId);
    const seatA = tableA ? findSeat(tableA, a.seatId) : null;
    const seatB = tableB ? findSeat(tableB, b.seatId) : null;
    if (!seatA || !seatB) return { ok: false, error: "missing_seat" };

    const aTableId = a.tableId;
    const aSeatId = a.seatId;
    const bTableId = b.tableId;
    const bSeatId = b.seatId;

    seatA.guestId = b.id;
    seatB.guestId = a.id;
    a.tableId = bTableId;
    a.seatId = bSeatId;
    b.tableId = aTableId;
    b.seatId = aSeatId;
    a.status = "assigned";
    b.status = "assigned";
    return { ok: true, a, b };
  }

  function assignGuestsToTable(state, guestIds, tableId, options) {
    const table = findTable(state, tableId);
    if (!table) return { ok: false, error: "missing_table" };
    const emptySeats = table.seats.filter((s) => s.guestId == null);
    const results = [];
    const skipped = [];
    guestIds.forEach((gid) => {
      const guest = findGuest(state, gid);
      if (!guest) return;
      if (sameId(guest.tableId, tableId)) {
        skipped.push({ guest, reason: "already_here" });
        return;
      }
      const seat = emptySeats.shift();
      if (!seat) {
        skipped.push({ guest, reason: "no_seat" });
        return;
      }
      results.push(assignGuestToSeat(state, guest.id, table.id, seat.id, options || { mode: "replace" }));
    });
    return { ok: true, results, skipped, table };
  }

  function addGuest(state, data) {
    const guest = {
      id: state.nextGuestId++,
      name: (data.name || "").trim(),
      groupId: data.groupId || "g_otros",
      category: data.category || "",
      status: "pending",
      tableId: null,
      seatId: null
    };
    if (!guest.name) return null;
    state.guests.push(guest);
    return guest;
  }

  function updateGuest(state, guestId, patch) {
    const guest = findGuest(state, guestId);
    if (!guest) return null;
    if (patch.name != null) {
      const name = String(patch.name).trim();
      if (name) guest.name = name;
    }
    if (patch.groupId != null && findGroup(state, patch.groupId)) guest.groupId = patch.groupId;
    if (patch.category != null) guest.category = String(patch.category);
    return guest;
  }

  function importGuestRows(state, rows) {
    if (!Array.isArray(rows)) return { imported: 0 };
    let start = 0;
    const first = rows[0] && String(rows[0][0] || "").trim().toLowerCase();
    if (["nombre", "invitado", "name", "nombre completo"].includes(first)) start = 1;
    let imported = 0;
    for (let i = start; i < rows.length; i++) {
      const row = rows[i];
      if (!row || row[0] == null) continue;
      const name = String(row[0]).trim();
      if (!name) continue;
      const groupName = row[1] != null ? String(row[1]).trim() : "";
      let groupId = "g_otros";
      if (groupName) {
        const existing = state.groups.find((g) => g.name.toLowerCase() === groupName.toLowerCase());
        groupId = existing ? existing.id : addGroup(state, groupName, "#94a3b8").id;
      }
      const guest = addGuest(state, {
        name,
        groupId,
        category: row[2] != null ? String(row[2]).trim() : ""
      });
      if (guest) imported += 1;
    }
    return { imported };
  }

  function deleteGuest(state, guestId) {
    const guest = findGuest(state, guestId);
    if (!guest) return false;
    clearGuestSeat(state, guest);
    state.relations.forEach((rel) => {
      rel.guestIds = (rel.guestIds || []).filter((id) => !sameId(id, guestId));
    });
    state.relations = state.relations.filter((rel) => (rel.guestIds || []).length >= 2);
    state.guests = state.guests.filter((g) => !sameId(g.id, guestId));
    return true;
  }

  function addGroup(state, name, color) {
    const group = {
      id: "g_custom_" + (state.nextGroupId++),
      name: name || "Grupo",
      color: color || "#94a3b8"
    };
    state.groups.push(group);
    return group;
  }

  function addRelation(state, type, guestIds) {
    const ids = Array.from(new Set((guestIds || []).map(String))).filter(Boolean);
    if (ids.length < 2) return null;
    const rel = {
      id: "rel_" + (state.nextRelationId++),
      type: RELATION_TYPES[type] ? type : "family",
      guestIds: ids.map((id) => {
        const n = Number(id);
        return Number.isNaN(n) ? id : n;
      })
    };
    state.relations.push(rel);
    return rel;
  }

  function addElement(state, presetOrData) {
    const preset = ELEMENT_PRESETS.find((p) => p.kind === (presetOrData.kind || presetOrData)) || ELEMENT_PRESETS[ELEMENT_PRESETS.length - 1];
    const data = typeof presetOrData === "object" ? presetOrData : {};
    const el = {
      id: uid("el"),
      kind: data.kind || preset.kind,
      name: data.name || preset.name,
      type: "decor",
      icon: data.icon || preset.icon,
      color: data.color || preset.color,
      x: data.x != null ? data.x : 120,
      y: data.y != null ? data.y : 120,
      width: data.width || preset.width,
      height: data.height || preset.height,
      rotation: data.rotation || 0
    };
    state.elements.push(el);
    return el;
  }

  function deleteElement(state, id) {
    state.elements = state.elements.filter((el) => !sameId(el.id, id));
  }

  function reconcileAssignments(state) {
    const seatMap = new Map();
    state.tables.forEach((table) => {
      (table.seats || []).forEach((seat) => {
        if (seat.guestId == null) return;
        const key = String(seat.guestId);
        if (seatMap.has(key)) {
          seat.guestId = null;
        } else {
          seatMap.set(key, { table, seat });
        }
      });
    });
    state.guests.forEach((guest) => {
      const found = seatMap.get(String(guest.id));
      if (found) {
        guest.tableId = found.table.id;
        guest.seatId = found.seat.id;
        guest.status = "assigned";
        found.seat.guestId = guest.id;
      } else {
        guest.tableId = null;
        guest.seatId = null;
        guest.status = "pending";
      }
    });
  }

  function computeStats(state) {
    const totalGuests = state.guests.length;
    const assignedGuests = state.guests.filter((g) => g.status === "assigned").length;
    let totalSeats = 0;
    let occupiedSeats = 0;
    let fullTables = 0;
    let tablesWithSpace = 0;
    state.tables.forEach((t) => {
      const cap = t.capacity || (t.seats && t.seats.length) || 0;
      const occ = (t.seats || []).filter((s) => s.guestId != null).length;
      totalSeats += cap;
      occupiedSeats += occ;
      if (cap > 0 && occ >= cap) fullTables += 1;
      else if (occ < cap) tablesWithSpace += 1;
    });
    const occupancy = totalSeats ? Math.round((occupiedSeats / totalSeats) * 100) : 0;
    return {
      totalGuests,
      assignedGuests,
      pendingGuests: totalGuests - assignedGuests,
      totalTables: state.tables.length,
      totalSeats,
      occupiedSeats,
      freeSeats: Math.max(0, totalSeats - occupiedSeats),
      occupancy,
      fullTables,
      tablesWithSpace
    };
  }

  function snapValue(value, grid, enabled) {
    if (!enabled) return value;
    const g = grid || 20;
    return Math.round(value / g) * g;
  }

  function metersToPx(meters) {
    const px = Math.round(Number(meters) * PX_PER_METER);
    return Number.isFinite(px) ? px : 0;
  }

  function pxToMeters(px) {
    return Number(px) / PX_PER_METER;
  }

  function setVenueMeters(state, key, meters) {
    if (key !== "width" && key !== "height") return state.venue[key];
    const minPx = 2 * PX_PER_METER;
    const maxPx = 80 * PX_PER_METER;
    const px = metersToPx(meters);
    state.venue[key] = Math.max(minPx, Math.min(maxPx, px || minPx));
    return state.venue[key];
  }

  function findVenueSection(state, id) {
    return (state.venue.sections || []).find((section) => sameId(section.id, id));
  }

  function addVenueSection(state, options) {
    options = options || {};
    if (!Array.isArray(state.venue.sections)) state.venue.sections = [];
    const width = Math.max(PX_PER_METER, metersToPx(options.widthMeters || 6) || PX_PER_METER * 6);
    const height = Math.max(PX_PER_METER, metersToPx(options.heightMeters || 6) || PX_PER_METER * 6);
    const section = {
      id: uid("sec"),
      kind: "section",
      name: options.name || "Sección " + (state.venue.sections.length + 1),
      x: options.x != null ? options.x : state.venue.width,
      y: options.y != null ? options.y : state.venue.sections.length * 30,
      width,
      height
    };
    state.venue.sections.push(section);
    return section;
  }

  function setVenueSectionMeters(state, id, key, meters) {
    const section = findVenueSection(state, id);
    if (!section || (key !== "width" && key !== "height")) return null;
    const px = metersToPx(meters);
    section[key] = Math.max(PX_PER_METER, Math.min(40 * PX_PER_METER, px || PX_PER_METER));
    return section;
  }

  function deleteVenueSection(state, id) {
    state.venue.sections = (state.venue.sections || []).filter((section) => !sameId(section.id, id));
  }

  function clampVenueSection(state, section) {
    const pad = 40 * PX_PER_METER;
    section.x = Math.max(-pad, Math.min(state.venue.width + pad - Math.min(section.width, pad), section.x));
    section.y = Math.max(-pad, Math.min(state.venue.height + pad - Math.min(section.height, pad), section.y));
  }

  function clampToVenue(state, obj) {
    const maxX = Math.max(0, state.venue.width - obj.width);
    const maxY = Math.max(0, state.venue.height - obj.height);
    obj.x = Math.max(0, Math.min(maxX, obj.x));
    obj.y = Math.max(0, Math.min(maxY, obj.y));
  }

  function computeSeatPositions(table) {
    const seatRadius = 14;
    const margin = 28;
    const count = table.seats ? table.seats.length : table.capacity;
    const positions = [];
    const d = table.seatDistribution || defaultSeatDistribution(table.shape, count);

    table.seats.forEach((seat, index) => {
      let posX = 0;
      let posY = 0;
      let angleDeg = 0;
      let side = "round";

      if (table.shape === "round") {
        const angle = (index / Math.max(count, 1)) * (2 * Math.PI) - Math.PI / 2;
        const rx = table.width / 2 + margin;
        const ry = table.height / 2 + margin;
        posX = table.width / 2 + rx * Math.cos(angle) - seatRadius;
        posY = table.height / 2 + ry * Math.sin(angle) - seatRadius;
        angleDeg = (angle * 180) / Math.PI + 90;
      } else {
        const dist = {
          top: Number(d.top) || 0,
          right: Number(d.right) || 0,
          bottom: Number(d.bottom) || 0,
          left: Number(d.left) || 0
        };
        let i = index;
        if (dist.top > 0 && i < dist.top) side = "top";
        else {
          i -= dist.top;
          if (dist.right > 0 && i < dist.right) side = "right";
          else {
            i -= dist.right;
            if (dist.bottom > 0 && i < dist.bottom) side = "bottom";
            else {
              i -= dist.bottom;
              if (dist.left > 0 && i < dist.left) side = "left";
            }
          }
        }
        if (side === "top") {
          posX = (table.width / (dist.top + 1)) * (index + 1) - seatRadius;
          posY = -margin - seatRadius;
          angleDeg = 180;
        } else if (side === "right") {
          posX = table.width + margin - seatRadius;
          posY = (table.height / (dist.right + 1)) * (i + 1) - seatRadius;
          angleDeg = 270;
        } else if (side === "bottom") {
          posX = table.width - (table.width / (dist.bottom + 1)) * (i + 1) - seatRadius;
          posY = table.height + margin - seatRadius;
          angleDeg = 0;
        } else if (side === "left") {
          posX = -margin - seatRadius;
          posY = table.height - (table.height / (dist.left + 1)) * (i + 1) - seatRadius;
          angleDeg = 90;
        } else {
          return;
        }
      }
      positions.push({ seat, x: posX, y: posY, angleDeg, side, number: seat.number || index + 1 });
    });
    return positions;
  }

  function exportProject(state) {
    return {
      schemaVersion: SCHEMA_VERSION,
      exportedAt: new Date().toISOString(),
      venue: state.venue,
      tables: state.tables,
      elements: state.elements,
      guests: state.guests,
      groups: state.groups,
      relations: state.relations,
      versions: state.versions,
      nextTableNumber: state.nextTableNumber,
      nextGuestId: state.nextGuestId,
      nextGroupId: state.nextGroupId,
      nextRelationId: state.nextRelationId
    };
  }

  function saveVersion(state, name) {
    const version = {
      id: uid("ver"),
      name: name || "Versión " + (state.versions.length + 1),
      createdAt: new Date().toISOString(),
      snapshot: {
        venue: clone(state.venue),
        tables: clone(state.tables),
        elements: clone(state.elements),
        guests: clone(state.guests),
        groups: clone(state.groups),
        relations: clone(state.relations),
        nextTableNumber: state.nextTableNumber,
        nextGuestId: state.nextGuestId,
        nextGroupId: state.nextGroupId,
        nextRelationId: state.nextRelationId
      }
    };
    state.versions.push(version);
    return version;
  }

  function restoreVersion(state, versionId) {
    const version = (state.versions || []).find((v) => sameId(v.id, versionId));
    if (!version) return null;
    const snap = version.snapshot;
    state.venue = Object.assign(defaultVenue(), snap.venue);
    state.tables = clone(snap.tables);
    state.elements = clone(snap.elements);
    state.guests = clone(snap.guests);
    state.groups = clone(snap.groups);
    state.relations = clone(snap.relations);
    state.nextTableNumber = snap.nextTableNumber;
    state.nextGuestId = snap.nextGuestId;
    state.nextGroupId = snap.nextGroupId;
    state.nextRelationId = snap.nextRelationId;
    return migrateState(state);
  }

  const Engine = {
    SCHEMA_VERSION,
    PX_PER_METER,
    DEFAULT_GROUPS,
    RELATION_TYPES,
    ELEMENT_PRESETS,
    uid,
    clone,
    sameId,
    createInitialState,
    migrateState,
    defaultSeatDistribution,
    defaultTableSize,
    findTable,
    findGuest,
    findSeat,
    findGroup,
    addTable,
    duplicateTable,
    deleteTable,
    updateTableCapacity,
    updateSeatDistribution,
    assignGuestToSeat,
    unassignSeat,
    unassignGuest,
    swapGuests,
    assignGuestsToTable,
    addGuest,
    updateGuest,
    importGuestRows,
    deleteGuest,
    addGroup,
    addRelation,
    addElement,
    deleteElement,
    reconcileAssignments,
    computeStats,
    snapValue,
    metersToPx,
    pxToMeters,
    setVenueMeters,
    findVenueSection,
    addVenueSection,
    setVenueSectionMeters,
    deleteVenueSection,
    clampVenueSection,
    clampToVenue,
    computeSeatPositions,
    relatedGuestsOnOtherTables,
    exportProject,
    saveVersion,
    restoreVersion
  };

  return Engine;
})();

export default WeddingEngine;
