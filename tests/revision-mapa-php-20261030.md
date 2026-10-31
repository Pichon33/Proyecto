# Mapa y revisión de PHP — 30/10/2026

## Corrección del mapa

En escritorio, la fila del mapa medía 711,5 px por la altura del panel de eventos,
pero Leaflet ocupaba solo 650 px. Los 61,5 px restantes mostraban el fondo celeste.
El contenedor ahora usa una columna flexible y el mapa conserva su altura base,
pero crece hasta completar la fila. Se establece también `flex-basis: auto` para
evitar que el antiguo `flex: 1` reduzca el mapa al plegar las tarjetas.

- Tailwind recompilado mediante `tailwind.cmd build`.
- Escritorio: mapa y contenedor de 711,5 px; diferencia inferior de 0 px.
- Tarjetas plegadas en escritorio: ambos de 650 px.
- Simulador del usuario, ancho 390 px: sin desbordamiento horizontal ni franja.
- Pantalla completa y vuelta a eventos verificadas en escritorio y simulador.
- Evidencia local: `mapa-sin-franja-20261030.png` (excluida de Git).
- La herramienta de cambio de viewport no aplicó los anchos solicitados; no se
  consideran comprobados otros tamaños por ese mecanismo.

## Seguridad de PHP y estado final

Se confirmó PHP 8.2.12 tanto por CLI como en la respuesta HTTP de Apache local.
Apache es 2.4.58 y su OpenSSL es 3.1.3. Escucha en todas las interfaces en los
puertos 80 y 443; no se comprobó acceso desde Internet ni la política del firewall.

PHP publicó correcciones para CVE-2026-109618 y CVE-2026-109619 el 21/10/2026.
La instalación local anterior a esas correcciones requiere actualización.
InfinityFree informó que completó la actualización de todos sus servidores el
22/10/2026; esta conclusión sobre el hosting se basa en el aviso del proveedor,
no en una inspección del binario privado de la cuenta.

Se descargó el paquete oficial PHP 8.2.35-1 TS x64 y se verificó su SHA256:
`811d13185e401ef6ec286b21a58dc004ab6d4e04494923d88add19551faa1117`.
Se probó una instalación temporal: pasaron 191 comprobaciones funcionales,
55 de seguridad, 30 de recuperación y la sintaxis de 60 archivos PHP.
Los correos de las pruebas quedaron en un capturador local.

La integración con Apache detectó dependencias antiguas que impedían cargar
cURL. Una copia aislada de Apache sí cargó las dependencias nuevas, pero la
comprobación posterior de la instalación activa falló y se restauraron sus
bibliotecas. El usuario pidió posponer la actualización: se restauró también
PHP original y se verificó HTTP 200, PHP 8.2.12 y cURL habilitado.

**PHP no quedó actualizado.** No considerar resuelta la vulnerabilidad local.
El paquete preparado y los respaldos quedan fuera del proyecto, en
`C:\xampp\respaldo-php-20261030`, para retomar el trabajo. No se publican en GitHub
ni en InfinityFree. Actualizar Apache/OpenSSL y migrar a una rama de PHP con más
tiempo de soporte queda pendiente. PHP 8.2 finaliza soporte el 31/12/2026.

## Fuentes oficiales

- [Avisos de PHP, octubre de 2026](https://www.php.net/archive/2026.php#2026-10-21-5).
- [Paquete oficial para Windows](https://www.php.net/downloads.php?os=windows&version=8.2).
- [Actualización de InfinityFree](https://status.infinityfree.com/issues/2026-10-22-php-security-vulnerabilities/).
