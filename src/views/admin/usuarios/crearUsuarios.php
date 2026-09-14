<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require_once dirname(__DIR__, 4) . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = trim($_POST['rol']);

    if (!in_array($rol, ['Usuario', 'Organizador', 'Administrador', 'AutoridadMunicipal'], true)) {
        $rol = 'Usuario';
    }

    $stmt = $pdo->prepare(
        "INSERT INTO Usuario (username, email, password, rol) VALUES (?, ?, ?, ?)"
    );

    try {
        $stmt->execute([$username, $email, $password, $rol]);
        header("Location: index.php?ruta=admin/usuarios");
        exit();
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="public/css/admin.css">
</head>
<body class="admin-page">

<h1>Nuevo Usuario</h1>

<form method="POST">
    <label>Username</label><br>
    <input type="text" name="username" required><br><br>

    <label>Email</label><br>
    <input type="email" name="email" required><br><br>

    <label>Contraseña</label><br>
    <input type="password" name="password" required><br><br>

    <label>Rol</label><br>
    <select name="rol">
        <option value="Usuario">Usuario</option>
        <option value="Organizador">Organizador</option>
        <option value="Administrador">Administrador</option>
        <option value="AutoridadMunicipal">Autoridad municipal</option>
    </select><br><br>

    <button type="submit">Guardar</button>
</form>

<br>
<a href="index.php?ruta=admin/usuarios">Volver a usuarios</a>

</body>
</html>
