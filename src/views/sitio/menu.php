<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?ruta=login');
    exit;
}

$usuario = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuario';
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'Usuario';
$puedeCrearEventos = in_array($rol, ['Administrador', 'Organizador'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi espacio | iVento</title>
    <link rel="stylesheet" href="public/css/styles.css">
</head>
<body class="pagina-bienvenida">
    <header class="cabecera">
        <div class="contenedor-cabecera">
            <a href="index.php?ruta=home" class="logo-header">
                <img src="public/img/ivento-logo.jpeg" alt="iVento">
            </a>
            <nav class="navegacion-principal">
                <a href="index.php?ruta=home">Inicio</a>
                <a href="index.php?ruta=mapa">Mapa</a>
                <a href="index.php?ruta=perfil">Perfil</a>
                <?php if ($rol === 'Administrador'): ?>
                    <a href="index.php?ruta=admin" class="boton-nav">Administración</a>
                <?php endif; ?>
                <?php if ($rol === 'AutoridadMunicipal'): ?>
                    <a href="index.php?ruta=admin/cortes" class="boton-nav">Cortes de calle</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="contenedor-bienvenida">
        <section class="texto-portada">
            <span class="etiqueta-portada">Tu cuenta</span>
            <h1 class="titulo-bienvenida">Hola, <?php echo $usuario; ?></h1>
            <p class="subtitulo-bienvenida">Elegí una opción para continuar usando iVento.</p>
        </section>

        <section class="cuadrilla-caracteristicas">
            <article class="tarjeta-caracteristica">
                <div class="icono-caracteristica">📍</div>
                <h2>Mapa de eventos</h2>
                <p>Explorá actividades disponibles y encontrá su ubicación.</p>
                <a href="index.php?ruta=mapa">Ver mapa</a>
            </article>

            <article class="tarjeta-caracteristica">
                <div class="icono-caracteristica">👤</div>
                <h2>Mi perfil</h2>
                <p>Revisá o modificá la información básica de tu cuenta.</p>
                <a href="index.php?ruta=perfil">Ir a mi perfil</a>
            </article>

            <article class="tarjeta-caracteristica">
                <div class="icono-caracteristica">⭐</div>
                <h2>Eventos favoritos</h2>
                <p>Buscá eventos por nombre y guardá los que más te interesen.</p>
                <a href="index.php?ruta=favoritos">Ver favoritos</a>
            </article>

            <?php if ($puedeCrearEventos): ?>
                <article class="tarjeta-caracteristica">
                    <div class="icono-caracteristica">➕</div>
                    <h2>Crear evento</h2>
                    <p>Publicá un evento y hacelo visible para la comunidad.</p>
                    <a href="index.php?ruta=crear-evento">Crear evento</a>
                </article>
            <?php endif; ?>
        </section>
    </main>

    <footer class="pie-pagina">
        <div class="pie-contenedor">
            <p>&copy; 2026 iVento · ApexDigital SA</p>
            <nav class="pie-enlaces">
                <a href="index.php?ruta=home">Inicio</a>
                <a href="index.php?ruta=mapa">Mapa</a>
                <a href="index.php?ruta=perfil">Perfil</a>
            </nav>
        </div>
    </footer>
</body>
</html>
