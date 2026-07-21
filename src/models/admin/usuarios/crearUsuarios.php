<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = trim($_POST['rol']);

    $stmt = $pdo->prepare(
        "INSERT INTO Usuario (username, email, password, rol) VALUES (?, ?, ?, ?)"
    );

    try {
        $stmt->execute([$username, $email, $password, $rol]);
        header("Location: usuarios.php");
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
</head>
<body>

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
        <option value="usuario">Usuario</option>
        <option value="Administrador">Administrador</option>
    </select><br><br>

    <button type="submit">Guardar</button>
</form>

<br>
<a href="usuarios.php">Volver a usuarios</a>

</body>
</html>
