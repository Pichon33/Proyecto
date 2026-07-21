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
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="admin-layout">
    <div class="sidebar">
        <h2>HayPlan</h2>
        <a href="../../../public/menu.php">Dashboard</a>
        <a href="usuarios/usuarios.php">
            Gestionar Usuarios
        </a>
        <a href="eventos/eventos.php">
            Gestionar Eventos
        </a>
        <a href="categorias/categorias.php">
            Gestionar Categorías
        </a>
        <a href="ubicaciones/ubicaciones.php">
            Gestionar Ubicaciones
        </a>
        <a href="../../../public/logout.php">
            Cerrar Sesión
        </a>
    </div>
    <div class="content">
    <h1>Panel de Administración</h1>
    <div class="cards">
        <div class="card">
            <h3>Organizadores</h3>
            <p></p>
        </div>
        <div class="card">
            <h3>Eventos</h3>
            <p></p>
        </div>
        
        <div class="card">
            <h3>Usuarios</h3>
            <p></p>
        </div>
    </div>
    </div>
</div>
</body>
</html>