<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require_once dirname(__DIR__, 3) . '/includes/config.php';

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM Usuario WHERE id_usuario = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $rol = trim($_POST['rol']);

    if (!in_array($rol, ['Usuario', 'Organizador', 'Administrador', 'AutoridadMunicipal'], true)) {
        $rol = 'Usuario';
    }

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE Usuario SET username = ?, email = ?, password = ?, rol = ? WHERE id_usuario = ?");
        $update->execute([$username, $email, $password, $rol, $id]);
    } else {
        $update = $pdo->prepare("UPDATE Usuario SET username = ?, email = ?, rol = ? WHERE id_usuario = ?");
        $update->execute([$username, $email, $rol, $id]);
    }

    header("Location: index.php?ruta=admin/usuarios");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="public/css/admin.css">
</head>
<body class="admin-page">

<a class="volver-admin" href="index.php?ruta=admin/usuarios">← Volver a usuarios</a>

<h1>Editar usuario</h1>
<p>Modificá sus datos y el nivel de acceso dentro de iVento.</p>

<form method="POST" class="formulario-admin">
    <div class="formulario-admin-grid">
        <div class="campo-admin">
            <label for="username">Nombre de usuario</label>
            <input id="username" type="text" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="password">Nueva contraseña</label>
            <input id="password" type="password" name="password">
            <p class="nota-admin">Dejala vacía para conservar la contraseña actual.</p>
        </div>

        <div class="campo-admin">
            <label for="rol">Rol</label>
            <select id="rol" name="rol">
                <option value="Usuario" <?= $usuario['rol'] == 'Usuario' ? 'selected' : '' ?>>Usuario</option>
                <option value="Organizador" <?= $usuario['rol'] == 'Organizador' ? 'selected' : '' ?>>Organizador</option>
                <option value="Administrador" <?= $usuario['rol'] == 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                <option value="AutoridadMunicipal" <?= $usuario['rol'] == 'AutoridadMunicipal' ? 'selected' : '' ?>>Autoridad municipal</option>
            </select>
        </div>
    </div>

    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>
