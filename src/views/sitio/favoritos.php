<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?ruta=login');
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/config.php';

$idUsuario = (int) $_SESSION['user_id'];
$busqueda = trim($_GET['busqueda'] ?? '');
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idEvento = (int) ($_POST['id_evento'] ?? 0);
    $accion = $_POST['accion'] ?? '';

    if ($idEvento > 0 && $accion === 'agregar') {
        $agregar = $pdo->prepare('INSERT IGNORE INTO EventoFavorito (id_usuario, id_evento) VALUES (?, ?)');
        $agregar->execute([$idUsuario, $idEvento]);
        $mensaje = 'El evento se agregó a tus favoritos.';
    }

    if ($idEvento > 0 && $accion === 'quitar') {
        $quitar = $pdo->prepare('DELETE FROM EventoFavorito WHERE id_usuario = ? AND id_evento = ?');
        $quitar->execute([$idUsuario, $idEvento]);
        $mensaje = 'El evento se quitó de tus favoritos.';
    }
}

$consulta = $pdo->prepare(
    "SELECT e.id_evento, e.titulo, e.descripcion, e.fecha, e.hora,
            c.nombre AS categoria, u.nombre_lugar,
            CASE WHEN f.id_evento IS NULL THEN 0 ELSE 1 END AS es_favorito
     FROM Evento e
     INNER JOIN Categoria c ON e.id_categoria = c.id_categoria
     INNER JOIN Ubicacion u ON e.id_ubicacion = u.id_ubicacion
     LEFT JOIN EventoFavorito f ON f.id_evento = e.id_evento AND f.id_usuario = ?
     WHERE e.titulo LIKE ?
       AND e.estado = 'Aprobado'
     ORDER BY es_favorito DESC, e.fecha ASC, e.hora ASC"
);
$consulta->execute([$idUsuario, '%' . $busqueda . '%']);
$eventos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es" class="motion-reduce:scroll-auto">
<head>
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos favoritos | iVento</title>
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="stylesheet" href="public/css/tailwind.css">
</head>
<body class="pagina-favoritos">
    <header class="cabecera max-sm:static">
        <div class="contenedor-cabecera max-sm:min-h-0 max-sm:pt-2 max-sm:pb-3 max-sm:flex-wrap max-sm:gap-x-3 max-sm:gap-y-1.5">
            <a href="index.php?ruta=home" class="logo-header max-sm:[&_img]:w-32.5 max-sm:[&_img]:h-11">
                <img src="public/img/ivento-logo-horizontal-blanco.png" alt="iVento">
            </a>
            <nav class="navegacion-principal max-sm:basis-full max-sm:ml-0 max-sm:justify-start max-sm:gap-1 max-sm:[&_a]:flex-auto max-sm:[&_a]:px-1.75 max-sm:[&_a]:py-2.25 max-sm:[&_a]:text-[0.875rem]">
                <a href="index.php?ruta=home">Inicio</a>
                <a href="index.php?ruta=mapa">Mapa</a>
                <a href="index.php?ruta=menu">Mi espacio</a>
                <a href="index.php?ruta=perfil">Perfil</a>
            </nav>
        </div>
    </header>

    <main class="contenedor-favoritos">
        <section class="encabezado-pagina">
            <span class="etiqueta-portada">Tus planes</span>
            <h1>Eventos favoritos</h1>
            <p>Buscá un evento por nombre y guardalo para volver a encontrarlo rápidamente.</p>
        </section>

        <?php if ($mensaje !== ''): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <form class="buscador-eventos max-sm:items-stretch max-sm:flex-col max-sm:[&_.boton]:w-full" method="GET" action="index.php">
            <input type="hidden" name="ruta" value="favoritos">
            <label>
                Buscar por nombre
                <input type="search" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Ej.: Carnaval">
            </label>
            <button class="boton" type="submit">Buscar</button>
        </form>

        <section class="lista-favoritos">
            <?php if (empty($eventos)): ?>
                <p class="sin-resultados">No encontramos eventos con ese nombre. Cuando un organizador publique uno, aparecerá en esta lista.</p>
            <?php endif; ?>

            <?php foreach ($eventos as $evento): ?>
                <article class="tarjeta-evento max-sm:items-stretch max-sm:flex-col">
                    <div>
                        <h2><?php echo htmlspecialchars($evento['titulo']); ?></h2>
                        <p><?php echo htmlspecialchars($evento['descripcion']); ?></p>
                        <p><strong><?php echo htmlspecialchars($evento['fecha']); ?></strong> · <?php echo htmlspecialchars(substr($evento['hora'], 0, 5)); ?> · <?php echo htmlspecialchars($evento['nombre_lugar']); ?></p>
                        <p class="tipo-evento"><?php echo htmlspecialchars($evento['categoria']); ?></p>
                        <?php if ($evento['es_favorito']): ?>
                            <p class="etiqueta-favorito">★ Guardado en favoritos</p>
                        <?php endif; ?>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="id_evento" value="<?php echo (int) $evento['id_evento']; ?>">
                        <?php if ($evento['es_favorito']): ?>
                            <input type="hidden" name="accion" value="quitar">
                            <button class="boton-favorito activo max-sm:w-full" type="submit">Quitar favorito</button>
                        <?php else: ?>
                            <input type="hidden" name="accion" value="agregar">
                            <button class="boton-favorito max-sm:w-full" type="submit">★ Agregar</button>
                        <?php endif; ?>
                    </form>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <footer class="pie-pagina">
        <div class="pie-contenedor max-sm:items-start max-sm:flex-col">
            <p>&copy; 2026 iVento · ApexDigital SA</p>
            <nav class="pie-enlaces">
                <a href="index.php?ruta=home">Inicio</a>
                <a href="index.php?ruta=mapa">Mapa</a>
                <a href="index.php?ruta=menu">Mi espacio</a>
            </nav>
        </div>
    </footer>
</body>
</html>
