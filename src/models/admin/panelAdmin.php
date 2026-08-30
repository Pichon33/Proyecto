<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../public/login.php");
    exit();
}

if ($_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de administración</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="pagina-admin">
<div class="estructura-admin">
    <aside class="barra-lateral">
        <h2 class="marca-admin">iVento</h2>
        <p class="rol-admin">Panel de administrador</p>
        <nav class="navegacion-admin">
            <a href="../../../public/home.php">Inicio</a>
            <a href="../../../public/menu.php">Menú público</a>
            <a href="usuarios/usuarios.php">Usuarios</a>
            <a href="eventos/eventos.php">Eventos</a>
            <a href="categorias/categorias.php">Categorías</a>
            <a href="ubicaciones/ubicaciones.php">Ubicaciones</a>
            <a href="cortes/cortes.php">Cortes de calle</a>
        </nav>
    </aside>
    <main class="contenido-admin">
        <header class="encabezado-admin">
            <h1>Panel de gestión</h1>
            <p>Administrá usuarios, eventos, categorías y ubicaciones desde un solo lugar.</p>
        </header>
        <section class="fila-tarjetas">
            <a class="tarjeta-admin" href="usuarios/usuarios.php">
                <h3>Usuarios</h3>
                <p>Revisá permisos y mantené el control de quienes usan la plataforma.</p>
            </a>
            <a class="tarjeta-admin" href="eventos/eventos.php">
                <h3>Eventos</h3>
                <p>Controlá las publicaciones y su estado.</p>
            </a>
            <a class="tarjeta-admin" href="categorias/categorias.php">
                <h3>Categorías</h3>
                <p>Organizá los tipos de eventos disponibles.</p>
            </a>
            <a class="tarjeta-admin" href="ubicaciones/ubicaciones.php">
                <h3>Ubicaciones</h3>
                <p>Actualizá los espacios donde se realizan los eventos.</p>
            </a>
            <a class="tarjeta-admin" href="cortes/cortes.php">
                <h3>Cortes de calle</h3>
                <p>Revisá y aprobá las solicitudes vinculadas a eventos.</p>
            </a>
        </section>
    </main>
</div>
</body>
</html>
