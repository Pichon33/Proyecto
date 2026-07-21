<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM Usuario WHERE id_usuario = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $rol = trim($_POST['rol']);

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE Usuario SET username = ?, email = ?, password = ?, rol = ? WHERE id_usuario = ?");
        $update->execute([$username, $email, $password, $rol, $id]);
    } else {
        $update = $pdo->prepare("UPDATE Usuario SET username = ?, email = ?, rol = ? WHERE id_usuario = ?");
        $update->execute([$username, $email, $rol, $id]);
    }

    header("Location: usuarios.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
</head>
<body>

<h1>Editar Usuario</h1>

<form method="POST">
    <label>Username</label><br>
    <input type="text" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" required><br><br>

    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required><br><br>

    <label>Nueva Contraseña</label><br>
    <input type="password" name="password"><br><br>

    <label>Rol</label><br>
    <select name="rol">
        <option value="usuario" <?= $usuario['rol'] == 'usuario' ? 'selected' : '' ?>>Usuario</option>
        <option value="Administrador" <?= $usuario['rol'] == 'Administrador' ? 'selected' : '' ?>>Administrador</option>
    </select><br><br>

    <button type="submit">Actualizar</button>
</form>

<br>
<a href="usuarios.php">Volver a usuarios</a>

</body>
</html>
