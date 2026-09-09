<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'Administrador') {
    die("Acceso denegado");
}

require_once dirname(__DIR__, 3) . '/includes/config.php';

$busqueda = trim($_GET['busqueda'] ?? '');
$textoBusqueda = '%' . $busqueda . '%';

$stmt = $pdo->prepare(
    "SELECT * FROM Usuario
     WHERE username LIKE ?
     ORDER BY id_usuario DESC"
);
$stmt->execute([$textoBusqueda]);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios</title>
    <link rel="stylesheet" href="public/css/admin.css">
</head>
<body class="admin-page">

<main class="listado-admin">
    <a class="volver-admin" href="index.php?ruta=admin">← Volver al panel</a>

    <header class="encabezado-listado">
        <div>
            <span class="etiqueta-admin">Administración</span>
            <h1>Usuarios</h1>
            <p>Gestioná las cuentas y los permisos de acceso a iVento.</p>
        </div>

        <a class="boton-admin" href="index.php?ruta=admin/usuarios/crear">Nuevo usuario</a>
    </header>

    <form id="formularioBusquedaUsuarios" class="buscador-admin" method="GET">
        <label for="busquedaUsuarios">Buscar por nombre de usuario</label>
        <input id="busquedaUsuarios" type="search" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Ej.: Santi" autocomplete="off">
    </form>

    <section class="panel-tabla-admin">
        <div class="panel-tabla-cabecera">
            <h2>Usuarios registrados</h2>
            <span><?= count($usuarios) ?> en total</span>
        </div>

        <div class="tabla-scroll">
            <table class="tabla-listado">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Correo electrónico</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= $usuario['id_usuario'] ?></td>
                            <td><?= htmlspecialchars($usuario['username']) ?></td>
                            <td><?= htmlspecialchars($usuario['email']) ?></td>
                            <td><span class="rol-listado"><?= htmlspecialchars($usuario['rol']) ?></span></td>
                            <td>
                                <a class="accion-editar" href="index.php?ruta=admin/usuarios/editar?id=<?= $usuario['id_usuario'] ?>">Editar</a>
                                <form class="accion-formulario" action="index.php?ruta=admin/usuarios/eliminar" method="POST" onsubmit="return confirm('¿Está seguro que desea eliminar este usuario?')">
                                    <input type="hidden" name="id" value="<?= $usuario['id_usuario'] ?>">
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

<script src="public/js/admin/busquedaUsuarios.js"></script>

</body>
</html>
