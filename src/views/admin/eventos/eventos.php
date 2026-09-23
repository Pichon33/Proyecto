<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require_once dirname(__DIR__, 4) . '/includes/config.php';

$busqueda = trim($_GET['busqueda'] ?? '');
$textoBusqueda = '%' . $busqueda . '%';

$stmt = $pdo->prepare(
    "SELECT e.id_evento, e.titulo, e.fecha, e.hora, e.estado, e.latitud, e.longitud,
            c.nombre AS categoria, u.nombre_lugar AS ubicacion
     FROM Evento e
     INNER JOIN Categoria c ON e.id_categoria = c.id_categoria
     INNER JOIN Ubicacion u ON e.id_ubicacion = u.id_ubicacion
     WHERE e.titulo LIKE ?
     ORDER BY e.fecha DESC, e.hora DESC"
);
$stmt->execute([$textoBusqueda]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <title>Eventos</title>
    <link rel="stylesheet" href="public/css/admin.css">
</head>
<body class="admin-page">

<main class="listado-admin">
    <a class="volver-admin" href="index.php?ruta=admin">← Volver al panel</a>

    <header class="encabezado-listado">
        <div>
            <span class="etiqueta-admin">Administración</span>
            <h1>Eventos</h1>
            <p>Revisá las publicaciones, sus fechas y sus ubicaciones.</p>
        </div>

        <a class="boton-admin" href="index.php?ruta=admin/eventos/crear">Nuevo evento</a>
    </header>

    <form id="formularioBusquedaEventos" class="buscador-admin" method="GET">
        <label for="busquedaEventos">Buscar por nombre del evento</label>
        <input id="busquedaEventos" type="search" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Ej.: Feria" autocomplete="off">
    </form>

    <section class="panel-tabla-admin">
        <div class="panel-tabla-cabecera">
            <h2>Eventos publicados</h2>
            <span><?= count($eventos) ?> en total</span>
        </div>

        <div class="tabla-scroll">
            <table class="tabla-listado">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Evento</th>
                        <th>Fecha y hora</th>
                        <th>Estado</th>
                        <th>Categoría</th>
                        <th>Ubicación</th>
                        <th>Coordenadas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $evento): ?>
                        <tr>
                            <td><?= $evento['id_evento'] ?></td>
                            <td><?= htmlspecialchars($evento['titulo']) ?></td>
                            <td><?= htmlspecialchars($evento['fecha']) ?><br><?= htmlspecialchars(substr($evento['hora'], 0, 5)) ?></td>
                            <td><span class="estado-listado"><?= htmlspecialchars($evento['estado']) ?></span></td>
                            <td><?= htmlspecialchars($evento['categoria']) ?></td>
                            <td><?= htmlspecialchars($evento['ubicacion']) ?></td>
                            <td><?= htmlspecialchars($evento['latitud']) ?>, <?= htmlspecialchars($evento['longitud']) ?></td>
                            <td>
                                <a class="accion-editar" href="index.php?ruta=admin/eventos/editar?id=<?= $evento['id_evento'] ?>">Editar</a>
                                <form class="accion-formulario" action="index.php?ruta=admin/eventos/eliminar" method="POST" onsubmit="return confirm('¿Está seguro que desea eliminar este evento?')">
                                    <input type="hidden" name="id" value="<?= $evento['id_evento'] ?>">
                                    <button class="accion-eliminar" type="submit">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="public/js/admin/busquedaEventos.js"></script>

</body>
</html>
