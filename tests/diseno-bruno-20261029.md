# Bruno, iconografía y modo oscuro — 29 de octubre de 2026

Cambios locales, sin publicar en GitHub ni InfinityFree. Se conservaron las rutas PHP, la base de datos y los colores de marca del encabezado.

## Resultado

- La tarjeta del inicio ahora presenta a Bruno, tres detalles del plan y un acceso al catálogo. Se eliminaron el fondo degradado de esa tarjeta y la repetición del logo.
- Bruno también acompaña búsquedas sin resultados, Guardados vacío y listas vacías. En el mapa el mensaje ocupa toda la fila y la ilustración es más pequeña. No hay una mascota flotante.
- Los iconos decorativos de las vistas y scripts propios son SVG locales. `public/img/iconos.svg` reúne 32 símbolos; PHP usa `iconoSvg()` y JavaScript usa `icono()`. No requieren una fuente, CDN ni paquete nuevo. Los símbolos de Leaflet para acercar, alejar y cerrar se conservan como controles de la biblioteca.
- El modo oscuro usa fondo azul pizarra `#161c2d`, superficies `#202a40` y campos `#192237`. Los títulos del inicio son claros, los textos secundarios tienen más contraste y las etiquetas tienen su propio fondo oscuro. Los acentos siguen siendo violetas.
- Todos los estilos nuevos están en `src/tailwind.css`, compilados mediante `tailwind.cmd build`.

## Archivos de Bruno

- `public/img/bruno-transparente.png`: PNG con transparencia real, 1254 × 1254, 1.564.577 bytes.
- `public/img/bruno.webp`: versión usada por la web, 640 × 640, 70.970 bytes (aproximadamente 69 KiB). Se conserva el canal alfa.
- El original de Descargas no se modificó.

Se utilizó la herramienta integrada de edición de imágenes. Prompt final:

> Use case: background-extraction. Edit target: the supplied Bruno iVento dog mascot image. Remove only the opaque black background, replacing it with actual alpha transparency. Preserve Bruno's exact illustration, full body, paws, tail, ears, wink, magnifying glass and glass appearance, detailed fur, blue-purple bandana and exact white text 'iVento'. Preserve original colors, pose, framing and all character details. Clean transparent edges around fur, no black halo. Do not redraw, redesign, add anything, crop body or change lettering. Output a transparent PNG cutout suitable for the existing website.

Después se exportó la copia WebP reducida con GD, habilitado solo para ese comando; no se cambió la configuración de PHP. La edición generativa conserva visualmente el personaje, pero no se presenta como una copia idéntica píxel por píxel.

## Comprobaciones

- Compilación Tailwind correcta.
- 33 archivos PHP de vistas y ayudantes sin errores de sintaxis.
- Sintaxis de los scripts modificados correcta y 12 regresiones JavaScript aprobadas.
- SVG válido con 32 símbolos; búsqueda sin emojis ni glifos decorativos anteriores en las vistas y scripts propios.
- Transparencia del PNG comprobada mediante su canal alfa y revisada sobre fondos claro y oscuro en Brave.
- Inicio sin desbordamiento horizontal a 320, 390, 600, 601, 800, 801 y 1280 píxeles. Revisión adicional en el simulador iPhone abierto por el usuario.
- Catálogo: buscar un texto sin coincidencias muestra a Bruno; “Limpiar filtros” recuperó los cinco eventos locales.
- Mapa: apertura del evento “Minecraft en el Parque” con clic y con Enter. Se corrigió la interceptación del clic por el SVG del pin con `pointer-events: none` en ese elemento decorativo.
- Mapa sin resultados a 320 píxeles: mensaje de ancho completo, sin desbordamiento.
- Registro a 320 píxeles en claro y oscuro: logo correspondiente, campos y ayudas legibles, sin desbordamiento. No se creó una cuenta.
- No se observaron errores ni advertencias en la consola de las pantallas comprobadas.

La aparición en Guardados/listas vacías está implementada, pero no se abrió una sesión privada para comprobar esas pantallas en este pase. La prueba en un teléfono físico sigue pendiente.

Capturas locales: `tests/diseno-bruno-telefono.png` y `tests/diseno-sobre-oscuro.png`. Las capturas están excluidas de Git y el directorio de pruebas no se publica por HTTP.
