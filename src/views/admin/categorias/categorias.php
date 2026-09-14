<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require_once dirname(__DIR__, 4) . '/includes/config.php';

$busqueda = trim($_GET['busqueda'] ?? '');
$textoBusqueda = '%' . $busqueda . '%';

$stmt = $pdo->prepare(
    "SELECT * FROM Categoria
     WHERE nombre LIKE ?
     ORDER BY nombre"
);
$stmt->execute([$textoBusqueda]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Categorías</title>
    <link rel="stylesheet" href="public/css/admin.css">
</head>
<body class="admin-page">

<main class="listado-admin">
    <a class="volver-admin" href="index.php?ruta=admin">← Volver al panel</a>

    <header class="encabezado-listado">
        <div>
            <span class="etiqueta-admin">Administración</span>
            <h1>Categorías</h1>
            <p>Ordená los tipos de actividades que se pueden publicar.</p>
        </div>

        <a class="boton-admin" href="index.php?ruta=admin/categorias/crear">Nueva categoría</a>
    </header>

    <form id="formularioBusquedaCategorias" class="buscador-admin" method="GET">
        <label for="busquedaCategorias">Buscar por nombre de categoría</label>
        <input id="busquedaCategorias" type="search" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Ej.: Música" autocomplete="off">
    </form>

    <section class="panel-tabla-admin">
        <div class="panel-tabla-cabecera">
            <h2>Categorías disponibles</h2>
            <span><?= count($categorias) ?> en total</span>
        </div>

        <div class="tabla-scroll">
            <table class="tabla-listado">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td><?= $categoria['id_categoria'] ?></td>
                            <td><?= htmlspecialchars($categoria['nombre']) ?></td>
                            <td><?= htmlspecialchars($categoria['tipo']) ?></td>
                            <td>
                                <a class="accion-editar" href="index.php?ruta=admin/categorias/editar?id=<?= $categoria['id_categoria'] ?>">Editar</a>
                                <form class="accion-formulario" action="index.php?ruta=admin/categorias/eliminar" method="POST" onsubmit="return confirm('¿Está seguro que desea eliminar esta categoría?')">
                                    <input type="hidden" name="id" value="<?= $categoria['id_categoria'] ?>">
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

<script src="public/js/admin/busquedaCategorias.js"></script>

</body>
</html>
