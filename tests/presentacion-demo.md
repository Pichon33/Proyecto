# Muestra para presentación — 18 de octubre de 2026

Publicada en https://ivento.site.je/Proyecto-master/ tras la confirmación del usuario.

- Se reemplazaron los 4 eventos anteriores por 10 eventos ficticios en Juan Lacaze.
- 8 próximos (23 de octubre al 8 de noviembre) y 2 finalizados (10 y 17 de octubre).
- 4 perfiles ficticios sin contraseña utilizable; se conservaron las 8 cuentas existentes.
- 12 comentarios, 4 valoraciones, 20 favoritos, 20 participaciones, 4 asistencias y 1 recorrido ilustrativo.
- Todos los títulos llevan “Demo”. Las descripciones aclaran que no son convocatorias reales y que las sedes/puntos son aproximados.
- Seis portadas generadas con IA, reutilizadas en diez tarjetas. No son fotografías de Juan Lacaze ni de sedes reales.

## Archivos

Datos: `database/presentacion-demo.json`. Generador: `tools/preparar-demo.php`. SQL: `database/presentacion-demo.sql`. Portadas: `public/img/demo/`. Vista previa local: `tests/presentacion-demo.html`.

El generador solo escribe SQL y HTML: no conecta a ninguna base. **Importar el SQL reemplaza todos los eventos y sus interacciones vinculadas**; no volver a importarlo sin revisar y confirmar el destino. Las fechas son fijas para esta presentación.

## Verificación realizada

`php tests/verificar-demo.php` pasó en una base temporal, incluida la mezcla de cotejamientos unicode_ci/general_ci del hosting. Comprobó cantidades, fechas, ciudad, eliminación en cascada y conservación de una cuenta previa. La base temporal se eliminó al terminar.

La primera importación remota falló por mezcla de cotejamientos; se verificó que la transacción conservara los 4 eventos, 8 usuarios y 2 favoritos anteriores. Se corrigió el cotejamiento explícito de los textos del SQL. La importación corregida completó 156 consultas sin errores.

La consulta final del hosting devolvió: 10 eventos, 8 próximos, 12 usuarios, 12 comentarios, 4 valoraciones y 10 eventos con ciudad Juan Lacaze. En el navegador se verificaron las diez imágenes cargadas, diez marcadores y el detalle de Jam acústica con 2 reseñas y promedio 4,5. Captura: `tests/presentacion-demo-hosting.png`.

## Prompts de las portadas

Prompt común:

> Generate ONE landscape 3:2 event cover for a fictional school-project events app. Premium editorial illustration, semi-realistic painterly depth, coherent photographic lighting, inviting and modern, colorful but tasteful, clear composition readable in a small mobile card. No words, no letters, no logos, no watermarks. Illustrative scene, not documentary or a real venue.

Se agregó una escena por imagen:

- `musica.jpg`: An intimate outdoor live music stage by a wide calm Uruguayan river at sunset, acoustic guitar and percussion, crowd as soft silhouettes, purple and cyan stage lighting.
- `deportes.jpg`: Amateur sports day in a green Uruguayan coastal town, foreground football and basketball on grass, cycling helmets and running shoes, community recreation, sunny blue sky.
- `feria.jpg`: Cozy Uruguayan outdoor food and artisan market, wooden stalls with bread, pastries, mate gourds, ceramic bowls and plants, warm hanging fairy lights at dusk.
- `cultura.jpg`: Outdoor community arts and cinema evening, blank glowing projection screen, books and paint brushes on a table, folding chairs beneath trees, warm purple-blue twilight.
- `naturaleza.jpg`: Peaceful sandy Rio de la Plata beach beside a small Uruguayan town, grassy banks, bicycles parked by the promenade, people strolling far away, soft golden sunrise, no mountains.
- `tecnologia.jpg`: Welcoming student technology and board game workshop, laptops with abstract colorful screens, small unbranded robot, game pieces and cards on tables, violet and cyan accents.

Se inspeccionaron las seis imágenes; se convirtieron a JPEG calidad 82 conservando 1536 × 1024 píxeles. Tamaño total aproximado: 1,85 MB.
