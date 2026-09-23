<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?ruta=login");
    exit();
}

if ($_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de administración</title>
    <link rel="stylesheet" href="public/css/admin.css">
</head>
<body class="pagina-admin">
<div class="estructura-admin">
    <aside class="barra-lateral">
        <h2 class="marca-admin">iVento</h2>
        <p class="rol-admin">Panel de administrador</p>
        <nav class="navegacion-admin">
            <a href="index.php?ruta=home">Inicio</a>
            <a href="index.php?ruta=menu">Menú público</a>
            <a href="index.php?ruta=admin/usuarios">Usuarios</a>
            <a href="index.php?ruta=admin/eventos">Eventos</a>
            <a href="index.php?ruta=admin/categorias">Categorías</a>
            <a href="index.php?ruta=admin/ubicaciones">Ubicaciones</a>
            <a href="index.php?ruta=admin/cortes">Cortes de calle</a>
        </nav>
    </aside>
    <main class="contenido-admin">
        <header class="encabezado-admin">
            <h1>Panel de gestión</h1>
            <p>Administrá usuarios, eventos, categorías y ubicaciones desde un solo lugar.</p>
        </header>
        <section class="fila-tarjetas">
            <a class="tarjeta-admin" href="index.php?ruta=admin/usuarios">
                <h3>Usuarios</h3>
                <p>Revisá permisos y mantené el control de quienes usan la plataforma.</p>
            </a>
            <a class="tarjeta-admin" href="index.php?ruta=admin/eventos">
                <h3>Eventos</h3>
                <p>Controlá las publicaciones y su estado.</p>
            </a>
            <a class="tarjeta-admin" href="index.php?ruta=admin/categorias">
                <h3>Categorías</h3>
                <p>Organizá los tipos de eventos disponibles.</p>
            </a>
            <a class="tarjeta-admin" href="index.php?ruta=admin/ubicaciones">
                <h3>Ubicaciones</h3>
                <p>Actualizá los espacios donde se realizan los eventos.</p>
            </a>
            <a class="tarjeta-admin" href="index.php?ruta=admin/cortes">
                <h3>Cortes de calle</h3>
                <p>Revisá y aprobá las solicitudes vinculadas a eventos.</p>
            </a>
        </section>
    </main>
</div>
</body>
</html>
