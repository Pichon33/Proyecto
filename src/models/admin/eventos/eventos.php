<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require '../../../../includes/config.php';

$stmt = $pdo->query(
    "SELECT e.id_evento, e.titulo, e.fecha, e.hora, e.estado, c.nombre AS categoria, u.nombre_lugar AS ubicacion
     FROM Evento e
     INNER JOIN Categoria c ON e.id_categoria = c.id_categoria
     INNER JOIN Ubicacion u ON e.id_ubicacion = u.id_ubicacion
     ORDER BY e.fecha DESC, e.hora DESC"
);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eventos</title>
</head>
<body>

<h1>Administrar Eventos</h1>

<a href="crearEventos.php">Nuevo evento</a>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Título</th>
        <th>Fecha</th>
        <th>Hora</th>
        <th>Estado</th>
        <th>Categoría</th>
        <th>Ubicación</th>
        <th>Acciones</th>
    </tr>

    <?php foreach ($eventos as $evento): ?>
    <tr>
        <td><?= $evento['id_evento'] ?></td>
        <td><?= htmlspecialchars($evento['titulo']) ?></td>
        <td><?= $evento['fecha'] ?></td>
        <td><?= $evento['hora'] ?></td>
        <td><?= htmlspecialchars($evento['estado']) ?></td>
        <td><?= htmlspecialchars($evento['categoria']) ?></td>
        <td><?= htmlspecialchars($evento['ubicacion']) ?></td>
        <td>
            <a href="editarEventos.php?id=<?= $evento['id_evento'] ?>">Editar</a>
            |
            <a href="eliminarEventos.php?id=<?= $evento['id_evento'] ?>"
               onclick="return confirm('¿Está seguro que desea eliminar este evento?')">
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
