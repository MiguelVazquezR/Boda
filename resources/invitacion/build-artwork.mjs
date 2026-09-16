/**
 * build-artwork.mjs — Prepara el arte de la invitación (export de Canva → tiras WebP).
 *
 * Por qué existe:
 *   El export de Canva es un SVG de ~26 MB en una sola línea, con las 49 imágenes
 *   incrustadas en base64 (los textos están rasterizados, no hay nodos <text>).
 *   Servirlo tal cual obliga al invitado a descargar 26 MB y provoca un render
 *   muy pesado en móvil. Este script lo convierte UNA sola vez en tiras WebP ligeras.
 *
 * Qué hace:
 *   1. Lee resources/invitacion/invitacion-original.svg.
 *   2. Quita el manifiesto <metadata> (c2pa) que no aporta nada al navegador.
 *   3. Renderiza el SVG una sola vez al ancho final (por defecto 1366 px).
 *   4. Trocea el resultado en tiras verticales WebP → public/invitacion/pagina-XX.webp.
 *   5. Genera un placeholder diminuto (LQIP) por tira y public/invitacion/og-preview.jpg
 *      para la vista previa de WhatsApp.
 *   6. Escribe public/invitacion/manifest.json con la correspondencia
 *      coordenada-diseño (viewBox) ↔ píxel-render, para posicionar los overlays HTML
 *      (nombre de los invitados y botones) con precisión sobre el arte.
 *
 * Uso:
 *   node resources/invitacion/build-artwork.mjs
 *   node resources/invitacion/build-artwork.mjs --src=otro.svg --width=1366 --slice=1600 --quality=80
 *
 * Si el render automático (librsvg) no respetara máscaras o filtros del export,
 * el script lo avisa: en ese caso se genera el PNG con Chrome/Edge headless
 * (ver resources/invitacion/README.md) y se pasa como --src=ruta.png.
 */
import { mkdir, readFile, rm, stat, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

// ── Rutas del proyecto ───────────────────────────────────────────
const projectRoot = path.resolve(fileURLToPath(new URL('../../', import.meta.url)));

// ── Argumentos de línea de comandos ──────────────────────────────
const args = Object.fromEntries(
    process.argv.slice(2)
        .filter((arg) => arg.startsWith('--'))
        .map((arg) => {
            const [key, value] = arg.replace(/^--/, '').split('=');
            return [key, value ?? true];
        }),
);

const SRC = path.resolve(projectRoot, args.src ?? 'resources/invitacion/invitacion-original.svg');
const OUT_DIR = path.resolve(projectRoot, args.out ?? 'public/invitacion');
const RENDER_WIDTH = Number(args.width ?? 1366);
const SLICE_HEIGHT = Number(args.slice ?? 1600);
const QUALITY = Number(args.quality ?? 80);

// ── Utilidades ───────────────────────────────────────────────────
const kb = (bytes) => `${(bytes / 1024).toFixed(0)} KB`;
const mb = (bytes) => `${(bytes / 1024 / 1024).toFixed(2)} MB`;
const relative = (target) => path.relative(projectRoot, target).replace(/\\/g, '/');

/** Pipeline de sharp sobre el raster ya renderizado (sin volver a copiar memoria). */
function rasterPipeline(raster, info) {
    return sharp(raster, {
        raw: { width: info.width, height: info.height, channels: info.channels },
    });
}

async function main() {
    // ── 1. Leer el SVG original ──────────────────────────────────
    const original = await readFile(SRC, 'utf8');
    const originalBytes = Buffer.byteLength(original, 'utf8');

    console.log(`▸ Origen            : ${relative(SRC)}  (${mb(originalBytes)})`);

    // El viewBox define el sistema de coordenadas del diseño: es el que usan los
    // overlays HTML para posicionarse en porcentajes.
    const viewBoxMatch = original.match(/<svg[^>]*viewBox="([^"]+)"/i);
    const viewBox = viewBoxMatch
        ? viewBoxMatch[1].trim().split(/[\s,]+/).map(Number)
        : [0, 0, RENDER_WIDTH, RENDER_WIDTH];

    const designWidth = Number.isFinite(viewBox[2]) ? viewBox[2] : RENDER_WIDTH;
    const designHeight = Number.isFinite(viewBox[3]) ? viewBox[3] : RENDER_WIDTH;

    console.log(`▸ Diseño (viewBox)  : ${designWidth} × ${designHeight}`);

    // ── 2. Quitar el manifiesto c2pa ─────────────────────────────
    const cleaned = original.replace(/<metadata>[\s\S]*?<\/metadata>/i, '');
    console.log(`▸ Sin <metadata>    : ${mb(Buffer.byteLength(cleaned, 'utf8'))}`);

    // ── 3. Render único al ancho final ───────────────────────────
    const startedAt = Date.now();
    const { data: raster, info } = await sharp(Buffer.from(cleaned, 'utf8'), { density: 96 })
        .resize({ width: RENDER_WIDTH })
        .raw()
        .toBuffer({ resolveWithObject: true });

    const scale = info.width / designWidth;

    console.log(
        `▸ Render            : ${info.width} × ${info.height} px `
        + `(${info.channels} canales, ${((Date.now() - startedAt) / 1000).toFixed(1)} s)`,
    );
    console.log(`▸ Escala            : ${scale.toFixed(4)} px de render por unidad de diseño`);

    // ── 4. Trocear en tiras WebP ─────────────────────────────────
    await rm(OUT_DIR, { recursive: true, force: true });
    await mkdir(OUT_DIR, { recursive: true });

    const slices = [];
    let totalBytes = 0;

    for (let top = 0, index = 1; top < info.height; top += SLICE_HEIGHT, index += 1) {
        const height = Math.min(SLICE_HEIGHT, info.height - top);
        const file = `pagina-${String(index).padStart(2, '0')}.webp`;

        const written = await rasterPipeline(raster, info)
            .extract({ left: 0, top, width: info.width, height })
            .webp({ quality: QUALITY, effort: 5 })
            .toFile(path.join(OUT_DIR, file));

        // Placeholder diminuto: se muestra borroso mientras carga la tira real.
        const lqip = await rasterPipeline(raster, info)
            .extract({ left: 0, top, width: info.width, height })
            .resize({ width: 24 })
            .webp({ quality: 30 })
            .toBuffer();

        const designTop = Number((top / scale).toFixed(2));
        const designBottom = Number(((top + height) / scale).toFixed(2));

        totalBytes += written.size;
        slices.push({
            file,
            width: written.width,
            height: written.height,
            bytes: written.size,
            render: { top, height },
            design: { top: designTop, height: Number((height / scale).toFixed(2)) },
            lqip: `data:image/webp;base64,${lqip.toString('base64')}`,
        });

        console.log(
            `  · ${file}  ${written.width}×${written.height}  ${kb(written.size)}`
            + `  (diseño y ${designTop} → ${designBottom})`,
        );
    }

    // ── 5. Vista previa para WhatsApp (1200×630) ─────────────────
    const ogFile = 'og-preview.jpg';
    const ogCropHeight = Math.min(info.height, Math.round((info.width * 630) / 1200));

    await rasterPipeline(raster, info)
        .extract({ left: 0, top: 0, width: info.width, height: ogCropHeight })
        .resize({ width: 1200, height: 630, fit: 'cover' })
        .jpeg({ quality: 82 })
        .toFile(path.join(OUT_DIR, ogFile));

    const ogSize = (await stat(path.join(OUT_DIR, ogFile))).size;

    // ── 6. Manifiesto ────────────────────────────────────────────
    const manifest = {
        generatedAt: new Date().toISOString(),
        source: relative(SRC),
        design: { width: designWidth, height: designHeight },
        render: { width: info.width, height: info.height, scale },
        ogImage: ogFile,
        slices,
    };

    await writeFile(
        path.join(OUT_DIR, 'manifest.json'),
        `${JSON.stringify(manifest, null, 2)}\n`,
        'utf8',
    );

    // ── 7. Resumen ───────────────────────────────────────────────
    const optimizedBytes = totalBytes + ogSize;

    console.log('');
    console.log(`✔ ${slices.length} tiras + ${ogFile} (${kb(ogSize)}) → ${relative(OUT_DIR)}`);
    console.log(
        `✔ Peso total        : ${mb(optimizedBytes)} (antes ${mb(originalBytes)}, `
        + `-${(100 - (optimizedBytes / originalBytes) * 100).toFixed(1)}%)`,
    );
    console.log('✔ manifest.json     : listo (lo consume resources/js/Pages/Invitacion.vue)');
}

main().catch((error) => {
    console.error('');
    console.error('✖ No se pudo generar el arte:', error.message);
    console.error('  Pista: si el error viene de librsvg, genera el PNG con Chrome headless');
    console.error('  y vuelve a ejecutar con --src=ruta/al/render.png');
    process.exit(1);
});
