/**
 * Utilidades de fecha/hora del evento.
 *
 * El evento sucede en una zona horaria fija: la del lugar de la boda. Para que la
 * invitación y el panel muestren SIEMPRE la hora que se configuró (por ejemplo
 * "20:50"), sin depender de la zona horaria del dispositivo que la visita:
 *
 *  - Si el valor trae zona horaria explícita ("2026-11-15T02:50:00Z" o
 *    "...-06:00"), se convierte a la zona del evento.
 *  - Si viene sin zona ("2026-11-14T20:50"), se toma tal cual (hora de pared).
 */
export const VENUE_TIME_ZONE = 'America/Mexico_City';

/** true si el valor incluye zona horaria explícita (Z u offset ±HH:MM). */
export function hasExplicitTimeZone(value) {
    return /z$/i.test(String(value)) || /[+-]\d{2}:?\d{2}$/.test(String(value));
}

/** Convierte un valor de fecha del backend a un Date con la hora del lugar del evento. */
export function parseEventDate(value) {
    if (!value) return null;

    let raw = String(value);

    if (hasExplicitTimeZone(raw)) {
        raw = toVenueDateTimeString(raw);
    }

    const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?)?/);
    if (match) {
        return new Date(
            Number(match[1]),
            Number(match[2]) - 1,
            Number(match[3]),
            Number(match[4] ?? 0),
            Number(match[5] ?? 0),
            Number(match[6] ?? 0),
        );
    }

    const parsed = new Date(value);

    return isNaN(parsed.getTime()) ? null : parsed;
}

/** Fecha larga en español (ej. "sábado, 14 de noviembre de 2026"). */
export function formatEventDate(value, fallback = 'Fecha por anunciar') {
    const date = parseEventDate(value);
    if (!date) return fallback;

    return date.toLocaleDateString('es-MX', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
}

/** Hora en formato 12 h (ej. "08:50 p.m."). */
export function formatEventTime(value) {
    const date = parseEventDate(value);
    if (!date) return '';

    return date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
}

/** Valor para un input type="datetime-local" (yyyy-MM-ddTHH:mm). */
export function toDatetimeLocal(value) {
    const date = parseEventDate(value);
    if (!date) return '';

    const pad = (n) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/** Valor para un input type="date" (yyyy-MM-dd). */
export function toDateInput(value) {
    const date = parseEventDate(value);
    if (!date) return '';

    const pad = (n) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/**
 * Devuelve "yyyy-MM-ddTHH:mm:ss" con la hora del lugar del evento.
 * Se usa internamente para normalizar valores que llegan en otra zona horaria.
 */
function toVenueDateTimeString(value) {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: VENUE_TIME_ZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(new Date(value));

    const get = (type) => parts.find((part) => part.type === type)?.value ?? '00';

    return `${get('year')}-${get('month')}-${get('day')}T${get('hour')}:${get('minute')}:${get('second')}`;
}
