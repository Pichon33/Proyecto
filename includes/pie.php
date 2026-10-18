<footer class="iv-footer">
    <a href="index.php?ruta=home" class="iv-footer-marca">© <?= date('Y') ?> iVento · ApexDigital</a>
    <div data-selector-tema></div>
    <nav class="iv-footer-enlaces" aria-label="Enlaces del pie">
        <a href="index.php?ruta=home#nosotros">Sobre iVento</a>
        <a href="index.php?ruta=menu">Mi espacio</a>
        <a href="index.php?ruta=comunidad">Comunidad</a>
    </nav>
</footer>
<?php if (!empty($mostrarNavegacionInferior)): ?>
    <nav class="iv-bottom-nav" aria-label="Navegación móvil">
        <?php foreach ([['mapa','Mapa','M3 5l6-2 6 2 6-2v16l-6 2-6-2-6 2V5M9 3v16M15 5v16'],['eventos','Eventos','M4 5h16v16H4V5M7 2v6M17 2v6M4 10h16M8 14h3'],['favoritos','Guardados','M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z'],['perfil','Perfil','M20 21v-2a7 7 0 0 0-14 0v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z']] as [$rutaNav,$textoNav,$trazo]): ?>
        <a href="index.php?ruta=<?= $rutaNav ?>" <?= ($pagina ?? $_GET['ruta'] ?? '')===$rutaNav?'aria-current="page"':'' ?>><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="<?= $trazo ?>"/></svg><span><?= $textoNav ?></span></a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>
