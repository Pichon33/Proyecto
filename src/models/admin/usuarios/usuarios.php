<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

$stmt = $pdo->query("SELECT * FROM Usuario ORDER BY id_usuario DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios</title>
</head>
<body>

<h1>Administrar Usuarios</h1>

<a href="crearUsuarios.php">Nuevo usuario</a>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Rol</th>
        <th>Acciones</th>
    </tr>

    <?php foreach ($usuarios as $usuario): ?>
    <tr>
        <td><?= $usuario['id_usuario'] ?></td>
        <td><?= htmlspecialchars($usuario['username']) ?></td>
        <td><?= htmlspecialchars($usuario['email']) ?></td>
        <td><?= htmlspecialchars($usuario['rol']) ?></td>
        <td>
            <a href="editarUsuarios.php?id=<?= $usuario['id_usuario'] ?>">Editar</a>
            |
            <a href="eliminarUsuarios.php?id=<?= $usuario['id_usuario'] ?>"
               onclick="return confirm('¿Está seguro que desea eliminar este usuario?')">
               Eliminar
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<br>
<a href="../panelAdmin.php">Volver al panel</a>

</body>
</html>
