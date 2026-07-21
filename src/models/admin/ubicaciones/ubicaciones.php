<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

$stmt = $pdo->query("SELECT * FROM Ubicacion ORDER BY nombre_lugar");
$ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ubicaciones</title>
</head>
<body>

<h1>Administrar Ubicaciones</h1>

<a href="crearUbicaciones.php">Nueva ubicación</a>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Dirección</th>
        <th>Latitud</th>
        <th>Longitud</th>
        <th>Acciones</th>
    </tr>

    <?php foreach ($ubicaciones as $ubicacion): ?>
    <tr>
        <td><?= $ubicacion['id_ubicacion'] ?></td>
        <td><?= htmlspecialchars($ubicacion['nombre_lugar']) ?></td>
        <td><?= htmlspecialchars($ubicacion['direccion']) ?></td>
        <td><?= $ubicacion['latitud'] ?></td>
        <td><?= $ubicacion['longitud'] ?></td>
        <td>
            <a href="editarUbicaciones.php?id=<?= $ubicacion['id_ubicacion'] ?>">Editar</a>
            |
            <a href="eliminarUbicaciones.php?id=<?= $ubicacion['id_ubicacion'] ?>"
               onclick="return confirm('¿Está seguro que desea eliminar esta ubicación?')">
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
