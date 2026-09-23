# Invitación digital

## Cómo funciona

```
/i/{token}                 Puerta de apertura (sobre elegante con el nombre del invitado o de la pareja)
        │  botón «Abrir invitación»
        ▼
/i/{token}/abrir           Guarda en una cookie quién abrió la invitación y redirige a Canva
        ▼
https://rrnb.my.canva.site/jose-eli-141126      ← arte animado, con música y transiciones
        │  botón «Confirmar asistencia» dentro de Canva  →  /rsvp
        ▼
/rsvp                      Home.vue abierto en la sección de confirmación, con los nombres ya cargados
```

El arte animado vive en **Canva** porque la música y las transiciones no se exportan en el SVG
ni se pueden embeber en un iframe (el sitio de Canva responde `X-Frame-Options: SAMEORIGIN`).
Por eso la invitación propia es la *puerta de apertura*: un sobre azul con el nombre de los
invitados y, al entrar, se pasa al sitio de Canva.

## Qué se configura (sin tocar código)

| Qué | Dónde |
|---|---|
| URL del sitio de Canva | Panel → **Configuración → Invitación digital** (columna `wedding_settings.canva_url`). Si está vacío se usa `config/wedding.php` (`WEDDING_CANVA_URL`) |
| Nombres de los novios (texto del sobre) | `config/wedding.php` → `couple_names` (`WEDDING_COUPLE_NAMES`) |
| Parejas / links de cada invitado | Panel → **Invitados → botón «Parejas / Links»** (crear, editar, copiar link, WhatsApp, regenerar) |
| Botón «Confirmar asistencia» dentro de Canva | Debe apuntar a `https://TU-DOMINIO/rsvp`. Al abrir la invitación se recuerda quién es el invitado, así que ese link le muestra su confirmación con su nombre cargado |

Cada invitación agrupa a **1 o 2 personas** (pareja, o persona sola) y genera un token corto
(`/i/abc234defg`). El token se puede regenerar si un link se compartió por error.

## Arte y respaldo offline

El arte de la invitación vive en **Canva** y **no se guarda en el proyecto**: el enlace de
Canva es la fuente de verdad y es lo que ven los invitados. No hace falta ningún SVG ni
ningún MP3 en el repositorio.

### Papel y sello del sobre (`public/img/`)

La puerta de apertura no usa el arte de Canva: su sobre (papel y sello) se copió de dos
capturas de referencia temporales (no versionadas) y quedó servido desde `public/img/`:

| Archivo | De dónde sale |
|---|---|
| `public/img/sobre-textura.png` | Recorte de la tela (344x152, 38,1 KB) con los píxeles reales de la captura de referencia, a escala 1:1 (bandas de hilo cada ~9,5 px). El color base medido es `#17233D` y vive en la escala `sobre` de `tailwind.config.js` |
| `public/img/sello.png` | El sello de la captura de referencia, recortado en círculo (176x167, 36,6 KB). La máscara «cálida» deja fuera las esquinas con la textura del sobre y hace de canal alfa; el PNG se cuantiza a 6 bits/canal (error máximo de 2 niveles) porque pesa la mitad |

Ambos se pintan con la clase `.superficie-sobre` (`resources/css/app.css`) y en
`resources/js/Pages/Invitacion.vue`. Si algún día se cambia el papel o el sello, basta con
volver a capturar esas dos referencias con los nombres `textura_sobre.png` y `sello.png`
en una carpeta temporal que no se versione (por ejemplo `public/temp/`).

La tela se recorta más grande que la cara más grande del sobre (336x136) y con el alto
múltiplo del periodo de la trama (152 = 8 x 19 px = 16 periodos), así que no se repite en
ninguna cara y, si alguna creciera, la repetición seguiría alineada. Las motas claras de la
captura se sustituyeron por la media de sus vecinos. Si la trama se viera grande o pequeña,
se ajusta con `background-size` en `.superficie-sobre`.

### `build-artwork.mjs` (respaldo opcional, no se usa en producción)

Si algún día Canva dejara de estar disponible, este script convierte un export de Canva
(SVG) en tiras WebP ligeras (~2 MB en vez de 26 MB) listas para servir desde
`public/invitacion/`:

```bash
npm i -D sharp                                    # dependencia sólo para este script
# Exporta de nuevo la invitación desde Canva y guárdala como:
#   resources/invitacion/invitacion-original.svg
node resources/invitacion/build-artwork.mjs
```

Genera `public/invitacion/pagina-XX.webp` + `manifest.json` (con la equivalencia
coordenada-diseño ↔ píxel-render y un placeholder borroso por tira) y `og-preview.jpg`
para la vista previa de WhatsApp. Si el render automático no respetara alguna máscara del
export, se puede pasar un PNG generado con Chrome headless: `--src=ruta/render.png`.

> Los export de Canva no traen nodos `<text>`: **todos los textos están rasterizados**, así que
> no se puede editar una palabra del arte por código. Los nombres de los invitados sí son
> texto HTML real (los pinta `resources/js/Pages/Invitacion.vue`).

