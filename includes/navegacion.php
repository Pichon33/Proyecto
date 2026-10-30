<?php $paginaActual = isset($_GET['ruta']) && is_string($_GET['ruta']) ? $_GET['ruta'] : 'home'; ?>
<header class="iv-header">
    <div class="mx-auto flex min-h-18 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6">
        <a href="index.php?ruta=home" class="shrink-0" aria-label="iVento, inicio"><img src="public/img/ivento-logo-horizontal-blanco.png" alt="iVento" class="h-12 w-32 object-contain sm:w-36"></a>
        <nav class="hidden items-center gap-1 lg:flex" aria-label="Navegación principal">
            <a class="iv-nav-link" href="index.php?ruta=mapa">Mapa</a>
            <a class="iv-nav-link" href="index.php?ruta=eventos">Eventos</a>
            <a class="iv-nav-link" href="index.php?ruta=comunidad">Comunidad</a>
            <a class="iv-nav-link" href="index.php?ruta=favoritos">Guardados</a>
        </nav>
        <div class="flex items-center gap-2">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="iv-nav-link max-sm:hidden" href="index.php?ruta=crear-evento"><?= iconoSvg('mas') ?> Crear</a>
                <a class="iv-nav-link" href="index.php?ruta=notificaciones" aria-label="Notificaciones"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span id="contador-avisos" class="hidden rounded-full bg-violet-500 px-1.5 text-xs"></span></a>
                <a class="iv-nav-link max-sm:hidden" href="index.php?ruta=menu">Mi espacio</a>
            <?php else: ?>
                <a class="iv-nav-link text-sm max-sm:hidden" href="index.php?ruta=login">Ingresar</a>
                <a class="iv-nav-link border border-white/40 max-sm:hidden" href="index.php?ruta=registro">Crear cuenta</a>
            <?php endif; ?>
            <span class="shrink-0 whitespace-nowrap rounded-full border border-white/30 bg-white/10 px-2 py-1.5 text-xs text-white max-[360px]:hidden"><?= iconoSvg('pin') ?> Juan Lacaze</span>
            <details class="relative lg:hidden">
                <summary class="iv-nav-link cursor-pointer list-none px-2" aria-label="Abrir menú"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg></summary>
                <nav aria-label="Más opciones" class="absolute right-0 top-full z-[1200] mt-2 flex w-56 flex-col rounded-2xl border border-white/15 bg-[#101a42] p-2 text-white shadow-xl">
                    <?php if (!isset($_SESSION['user_id'])): ?><a class="iv-nav-link justify-start" href="index.php?ruta=login">Ingresar</a><a class="iv-nav-link justify-start" href="index.php?ruta=registro">Crear cuenta</a><?php endif; ?>
                    <a class="iv-nav-link justify-start" href="index.php?ruta=mapa">Mapa</a>
                    <a class="iv-nav-link justify-start" href="index.php?ruta=eventos">Eventos</a>
                    <a class="iv-nav-link justify-start" href="index.php?ruta=menu">Mi espacio</a>
                    <a class="iv-nav-link justify-start" href="index.php?ruta=comunidad">Comunidad</a>
                    <a class="iv-nav-link justify-start" href="index.php?ruta=entradas">Mis entradas</a>
                    <a class="iv-nav-link justify-start" href="index.php?ruta=crear-evento">Crear publicación</a>
                    <a class="iv-nav-link justify-start" href="index.php?ruta=perfil">Mi perfil</a>
                    <?php if (($_SESSION['rol'] ?? '') === 'Administrador'): ?><a class="iv-nav-link justify-start" href="index.php?ruta=admin">Administración</a><?php endif; ?>
                </nav>
            </details>
        </div>
    </div>
</header>
