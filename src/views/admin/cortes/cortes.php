<?php
session_start();
require_once dirname(__DIR__, 4) . '/includes/config.php';
exigirAdministracion(true);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCorte = filter_var($_POST['id_corte'] ?? null,FILTER_VALIDATE_INT);
    $estado = $_POST['estado'] ?? '';
    if ($idCorte===false || $idCorte<1 || !in_array($estado,['Aprobado','Rechazado'],true)) { http_response_code(400); exit('Solicitud o estado inválido.'); }
    $consultaCorte=$pdo->prepare('SELECT 1 FROM CorteCalle WHERE id_corte=?');
    $consultaCorte->execute([$idCorte]);
    if(!$consultaCorte->fetchColumn()) { http_response_code(404); exit('Solicitud no encontrada.'); }
    $actualizar = $pdo->prepare('UPDATE CorteCalle SET estado = ? WHERE id_corte = ?');
    $actualizar->execute([$estado, $idCorte]);

    header('Location: index.php?ruta=admin/cortes');
    exit;
}

$consulta = $pdo->query(
    "SELECT c.*, e.titulo AS evento, u.username
     FROM CorteCalle c
     LEFT JOIN Evento e ON c.id_evento = e.id_evento
     INNER JOIN Usuario u ON c.id_usuario = u.id_usuario
     ORDER BY FIELD(c.estado, 'Pendiente', 'Aprobado', 'Rechazado'), c.fecha_inicio ASC"
);
$cortes = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cortes de calle | iVento</title>
    <link rel="stylesheet" href="public/css/admin.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,4).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,4).'/public/js/tema.js') ?>"></script>
</head>
<body class="admin-page max-md:py-7 max-md:px-4">
    <main class="listado-admin">
        <?php if ($_SESSION['rol'] === 'Administrador'): ?>
            <a class="volver-admin" href="index.php?ruta=admin"><?= iconoSvg('volver') ?> Volver al panel</a>
        <?php else: ?>
            <a class="volver-admin" href="index.php?ruta=menu"><?= iconoSvg('volver') ?> Volver a mi espacio</a>
        <?php endif; ?>

        <header class="encabezado-listado max-md:items-start max-md:flex-col">
            <div>
                <span class="etiqueta-admin">Gestión municipal</span>
                <h1>Cortes de calle</h1>
                <p>Solo las solicitudes aprobadas se muestran en el mapa público.</p>
            </div>
        </header>

        <section class="panel-tabla-admin">
            <div class="panel-tabla-cabecera">
                <h2>Solicitudes recibidas</h2>
                <span><?= count($cortes) ?> en total</span>
            </div>

            <div class="tabla-scroll">
                <table class="tabla-listado">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Solicitante</th>
                            <th>Ciudad</th>
                            <th>Horario</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cortes as $corte): ?>
                            <tr>
                                <td><?= htmlspecialchars($corte['evento'] ?? 'Evento eliminado') ?></td>
                                <td><?= htmlspecialchars($corte['username']) ?></td>
                                <td><?= htmlspecialchars($corte['ciudad']) ?></td>
                                <td><?= htmlspecialchars($corte['fecha_inicio']) ?><br><?= htmlspecialchars($corte['fecha_fin']) ?></td>
                                <td><?= htmlspecialchars($corte['descripcion']) ?></td>
                                <td><span class="estado-listado"><?= htmlspecialchars($corte['estado']) ?></span></td>
                                <td>
                                    <?php if ($corte['estado'] === 'Pendiente'): ?>
                                        <form class="accion-formulario" method="POST">
    <?= campoCsrf() ?>
                                            <input type="hidden" name="id_corte" value="<?= $corte['id_corte'] ?>">
                                            <input type="hidden" name="estado" value="Aprobado">
                                            <button class="accion-editar" type="submit">Aprobar</button>
                                        </form>
                                        <form class="accion-formulario" method="POST">
    <?= campoCsrf() ?>
                                            <input type="hidden" name="id_corte" value="<?= $corte['id_corte'] ?>">
                                            <input type="hidden" name="estado" value="Rechazado">
                                            <button class="accion-eliminar" type="submit">Rechazar</button>
                                        </form>
                                    <?php else: ?>
                                        Revisado
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
