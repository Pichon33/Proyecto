<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once dirname(__DIR__,3).'/includes/config.php';
$pagina = isset($_GET['ruta']) && is_string($_GET['ruta']) ? $_GET['ruta'] : 'mapa';
$privadas = ['menu','perfil','favoritos','comunidad','entradas','notificaciones','crear-evento','editar-evento','moderacion'];
if (in_array($pagina,$privadas,true) && !isset($_SESSION['user_id'])) { header('Location: index.php?ruta=login'); exit; }
$titulos = ['mapa'=>'Explorá cerca tuyo','eventos'=>'Encontrá tu próximo plan','evento'=>'Detalle del evento','perfil'=>'Mi perfil','menu'=>'Mi espacio','favoritos'=>'Tus planes guardados','comunidad'=>'Tu comunidad','entradas'=>'Mis entradas','notificaciones'=>'Notificaciones','crear-evento'=>'Crear publicación','editar-evento'=>'Editar publicación','moderacion'=>'Moderación','usuario'=>'Perfil público','lista'=>'Lista compartida'];
$titulo = $titulos[$pagina] ?? 'iVento';
?>
<!DOCTYPE html>
<html lang="es" class="motion-reduce:scroll-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= htmlspecialchars($titulo) ?> | iVento</title>
    <meta name="description" content="Descubrí eventos de Colonia, guardá tus planes y compartí recuerdos con tu comunidad.">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/tema.js') ?>"></script>
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,3).'/public/css/tailwind.css') ?>">
    <link rel="stylesheet" href="public/assets/map/leaflet.css">
    <link rel="icon" href="public/img/ivento-icono-app.png">
</head>
<body class="iv-app <?= in_array($pagina, ['crear-evento','editar-evento'], true) ? '' : 'iv-con-navegacion' ?>" data-pagina="<?= htmlspecialchars($pagina) ?>">
    <a class="enlace-saltar" href="#contenido">Ir al contenido</a>
    <?php require dirname(__DIR__,3).'/includes/navegacion.php'; ?>
    <main id="contenido" tabindex="-1" class="min-w-0">
        <div id="app-contenido" class="mx-auto max-w-7xl p-4 sm:p-6"><p role="status" class="iv-panel">Cargando iVento…</p></div>
        <noscript><p class="iv-panel">Activá JavaScript para explorar el mapa y usar las funciones interactivas. <a href="index.php?ruta=home">Volver al inicio</a>.</p></noscript>
    </main>
    <?php $mostrarNavegacionInferior = !in_array($pagina, ['crear-evento','editar-evento'], true); require dirname(__DIR__,3).'/includes/pie.php'; ?>
    <div id="aviso" role="status" aria-live="polite" class="iv-toast hidden"></div>
    <dialog id="dialogo" class="iv-dialog" aria-label="Acción de iVento"><form method="dialog"><button class="iv-icon ml-auto" aria-label="Cerrar diálogo"><?= iconoSvg('cerrar') ?></button></form><div id="dialogo-contenido"></div></dialog>
    <script src="public/assets/map/leaflet.js"></script>
    <?php if ($pagina === 'entradas'): ?><script src="public/assets/qrcode.js"></script><?php endif; ?>
    <?php if (in_array($pagina, ['crear-evento','editar-evento'], true)): ?><script src="public/js/crear-evento.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/crear-evento.js') ?>"></script><?php endif; ?>
    <script src="public/js/experiencia.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/experiencia.js') ?>"></script>
</body>
</html>
