<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require_once dirname(__DIR__, 4) . '/includes/config.php';

$busqueda = trim($_GET['busqueda'] ?? '');
$textoBusqueda = '%' . $busqueda . '%';

$stmt = $pdo->prepare(
    "SELECT * FROM Ubicacion
     WHERE nombre_lugar LIKE ?
     ORDER BY nombre_lugar"
);
$stmt->execute([$textoBusqueda]);
$ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <title>Ubicaciones</title>
    <link rel="stylesheet" href="public/css/admin.css">
</head>
<body class="admin-page">

<main class="listado-admin">
    <a class="volver-admin" href="index.php?ruta=admin">← Volver al panel</a>

    <header class="encabezado-listado">
        <div>
            <span class="etiqueta-admin">Administración</span>
            <h1>Ubicaciones</h1>
            <p>Gestioná los lugares y sus coordenadas para los eventos.</p>
        </div>

        <a class="boton-admin" href="index.php?ruta=admin/ubicaciones/crear">Nueva ubicación</a>
    </header>

    <form id="formularioBusquedaUbicaciones" class="buscador-admin" method="GET">
        <label for="busquedaUbicaciones">Buscar por nombre del lugar</label>
        <input id="busquedaUbicaciones" type="search" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Ej.: Plaza" autocomplete="off">
    </form>

    <section class="panel-tabla-admin">
        <div class="panel-tabla-cabecera">
            <h2>Ubicaciones guardadas</h2>
            <span><?= count($ubicaciones) ?> en total</span>
        </div>

        <div class="tabla-scroll">
            <table class="tabla-listado">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Lugar</th>
                        <th>Dirección</th>
                        <th>Latitud</th>
                        <th>Longitud</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ubicaciones as $ubicacion): ?>
                        <tr>
                            <td><?= $ubicacion['id_ubicacion'] ?></td>
                            <td><?= htmlspecialchars($ubicacion['nombre_lugar']) ?></td>
                            <td><?= htmlspecialchars($ubicacion['direccion']) ?></td>
                            <td><?= htmlspecialchars($ubicacion['latitud']) ?></td>
                            <td><?= htmlspecialchars($ubicacion['longitud']) ?></td>
                            <td>
                                <a class="accion-editar" href="index.php?ruta=admin/ubicaciones/editar?id=<?= $ubicacion['id_ubicacion'] ?>">Editar</a>
                                <form class="accion-formulario" action="index.php?ruta=admin/ubicaciones/eliminar" method="POST" onsubmit="return confirm('¿Está seguro que desea eliminar esta ubicación?')">
                                    <input type="hidden" name="id" value="<?= $ubicacion['id_ubicacion'] ?>">
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

<script src="public/js/admin/busquedaUbicaciones.js"></script>

</body>
</html>
