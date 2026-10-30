<?php
session_start();
require_once dirname(__DIR__, 4) . '/includes/config.php';
exigirAdministracion();


$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

$stmtEvento = $pdo->prepare(
    "SELECT e.*, u.nombre_lugar, u.direccion, e.latitud AS latitud, e.longitud AS longitud
     FROM Evento e
     INNER JOIN Ubicacion u ON e.id_ubicacion = u.id_ubicacion
     WHERE e.id_evento = ?"
);
$stmtEvento->execute([$id]);
$evento = $stmtEvento->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    header("Location: index.php?ruta=admin/eventos");
    exit();
}

$stmtCategorias = $pdo->query("SELECT id_categoria, nombre FROM Categoria ORDER BY nombre");
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

$stmtUbicaciones = $pdo->query(
    "SELECT id_ubicacion, nombre_lugar, direccion, latitud, longitud
     FROM Ubicacion
     ORDER BY nombre_lugar"
);
$ubicaciones = $stmtUbicaciones->fetchAll(PDO::FETCH_ASSOC);

date_default_timezone_set('America/Montevideo');
$hoy=date('Y-m-d');
$limiteFecha=(new DateTimeImmutable('today'))->modify('+1 year')->format('Y-m-d');
$minInicio=min($hoy,$evento['fecha']);
$minFin=min($hoy,$evento['fecha_fin']?:$hoy);
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = datoFormulario('titulo');
    $descripcion = datoFormulario('descripcion');
    $fecha = datoFormulario('fecha');
    $hora = datoFormulario('hora');
    $fecha_fin = datoFormulario('fecha_fin') ?: null;
    $hora_fin = datoFormulario('hora_fin') ?: null;
    $tipo_publicacion = datoFormulario('tipo_publicacion');
    $precio_uyu = str_replace(',', '.', datoFormulario('precio_uyu'));
    $edad_minima = filter_var(datoFormulario('edad_minima'), FILTER_VALIDATE_INT);
    $estado = datoFormulario('estado');
    $imagen = datoFormulario('imagen');
    $id_categoria = filter_var($_POST['id_categoria'] ?? null,FILTER_VALIDATE_INT) ?: 0;
    $id_ubicacion = filter_var($_POST['id_ubicacion'] ?? null,FILTER_VALIDATE_INT) ?: 0;
    $latitud = str_replace(',', '.', datoFormulario('latitud'));
    $longitud = str_replace(',', '.', datoFormulario('longitud'));

    $inicioValido=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);
    $finValido=$fecha_fin ? DateTimeImmutable::createFromFormat('!Y-m-d',$fecha_fin) : null;
    $historica=$fecha===$evento['fecha'] && $fecha_fin===($evento['fecha_fin']?:null);
    if (!$inicioValido || $inicioValido->format('Y-m-d')!==$fecha || (!$historica && $fecha<$hoy) || $fecha>$limiteFecha ||
        ($fecha_fin && (!$finValido || $finValido->format('Y-m-d')!==$fecha_fin || (!$historica && $fecha_fin<$hoy) || $fecha_fin>$limiteFecha))) {
        $error='Las nuevas fechas deben estar entre hoy y dentro de un año. Podés conservar la fecha histórica original.';
    } elseif ($titulo === '' || mb_strlen($titulo)>150 || $descripcion === '' || mb_strlen($imagen)>255) {
        $error = 'Completá título y descripción; revisá la longitud de la imagen.';
    } elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/',$hora) || ($hora_fin !== null && !preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/',$hora_fin))) {
        $error = 'Revisá las horas de inicio y finalización.';
    } elseif (!in_array($id_categoria,array_column($categorias,'id_categoria')) || !in_array($id_ubicacion,array_column($ubicaciones,'id_ubicacion'))) {
        $error = 'Seleccioná una categoría y una ubicación existentes.';
    } elseif (!in_array($tipo_publicacion, ['Evento', 'Negocio'], true) || !in_array($estado, ['Pendiente', 'Aprobado', 'Rechazado'], true)) {
        $error = 'Seleccioná un tipo de publicación y un estado válidos.';
    } elseif (!is_numeric($precio_uyu) || (float) $precio_uyu < 0 || (float)$precio_uyu > 99999999 || $edad_minima === false || $edad_minima < 0 || $edad_minima > 120) {
        $error = 'Revisá el precio y la edad mínima.';
    } elseif (($fecha_fin === null) !== ($hora_fin === null)) {
        $error = 'Completá la fecha y hora de finalización juntas, o dejá ambas vacías.';
    } elseif ($fecha_fin !== null && strtotime($fecha_fin . ' ' . $hora_fin) < strtotime($fecha . ' ' . $hora)) {
        $error = 'La finalización no puede ser anterior al inicio.';
    } elseif (!is_numeric($latitud) || !is_numeric($longitud) || $latitud < -35.5 || $latitud > -30 || $longitud < -59 || $longitud > -53) {
        $error = 'Marcá una ubicación válida dentro de Uruguay.';
    } elseif (($fecha_fin === null) !== ($hora_fin === null)) {
        $error = 'Completá la fecha y la hora de finalización, o dejá ambos campos vacíos.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "UPDATE Evento
                 SET titulo = ?, descripcion = ?, fecha = ?, hora = ?, fecha_fin = ?, hora_fin = ?,
                     tipo_publicacion = ?, precio_uyu = ?, edad_minima = ?, estado = ?, imagen = ?, latitud = ?, longitud = ?,
                     id_categoria = ?, id_ubicacion = ?
                 WHERE id_evento = ?"
            );

            $stmt->execute([
                $titulo,
                $descripcion,
                $fecha,
                $hora,
                $fecha_fin,
                $hora_fin,
                $tipo_publicacion,
                $precio_uyu === '' ? 0 : $precio_uyu,
                $edad_minima,
                $estado,
                $imagen,
                $latitud,
                $longitud,
                $id_categoria,
                $id_ubicacion,
                $id
            ]);

            $pdo->commit();
            header("Location: index.php?ruta=admin/eventos");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'No se pudieron guardar los cambios. Revisá los datos e intentá nuevamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Evento</title>
    <link rel="stylesheet" href="public/css/admin.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,4).'/public/css/tailwind.css') ?>">
    <link rel="stylesheet" href="public/assets/map/leaflet.css">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,4).'/public/js/tema.js') ?>"></script>
</head>
<body class="admin-page max-md:py-7 max-md:px-4">

<a class="volver-admin" href="index.php?ruta=admin/eventos"><?= iconoSvg('volver') ?> Volver a eventos</a>

<h1>Editar evento</h1>
<p>Revisá sus datos y elegí la ubicación exacta desde el mapa.</p>

<?php if ($error !== ''): ?>
    <p class="nota-admin"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" class="formulario-admin">
    <?= campoCsrf() ?>
    <div class="formulario-admin-grid max-md:grid-cols-1">
        <div class="campo-admin">
            <label for="titulo">Título</label>
            <input id="titulo" type="text" name="titulo" value="<?= htmlspecialchars($evento['titulo']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="tipo_publicacion">Tipo de publicación</label>
            <select id="tipo_publicacion" name="tipo_publicacion">
                <option value="Evento" <?= $evento['tipo_publicacion'] === 'Evento' ? 'selected' : '' ?>>Evento</option>
                <option value="Negocio" <?= $evento['tipo_publicacion'] === 'Negocio' ? 'selected' : '' ?>>Negocio o emprendimiento</option>
            </select>
        </div>

        <div class="campo-admin campo-admin-completo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" required><?= htmlspecialchars($evento['descripcion']) ?></textarea>
        </div>

        <div class="campo-admin">
            <label for="fecha">Fecha de inicio</label>
            <input id="fecha" type="date" name="fecha" min="<?= htmlspecialchars($minInicio) ?>" max="<?= htmlspecialchars($limiteFecha) ?>" value="<?= htmlspecialchars($evento['fecha']) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="hora">Hora de inicio</label>
            <input id="hora" type="time" name="hora" value="<?= htmlspecialchars(substr($evento['hora'], 0, 5)) ?>" required>
        </div>

        <div class="campo-admin">
            <label for="fecha_fin">Fecha de finalización</label>
            <input id="fecha_fin" type="date" name="fecha_fin" min="<?= htmlspecialchars($minFin) ?>" max="<?= htmlspecialchars($limiteFecha) ?>" value="<?= htmlspecialchars($evento['fecha_fin'] ?? '') ?>">
        </div>

        <div class="campo-admin">
            <label for="hora_fin">Hora de finalización</label>
            <input id="hora_fin" type="time" name="hora_fin" value="<?= htmlspecialchars($evento['hora_fin'] ? substr($evento['hora_fin'], 0, 5) : '') ?>">
        </div>

        <div class="campo-admin">
            <label for="precio_uyu">Entrada (UYU)</label>
            <input id="precio_uyu" type="number" name="precio_uyu" min="0" step="0.01" value="<?= htmlspecialchars($evento['precio_uyu']) ?>">
        </div>

        <div class="campo-admin">
            <label for="edad_minima">Edad mínima</label>
            <input id="edad_minima" type="number" name="edad_minima" min="0" step="1" value="<?= htmlspecialchars($evento['edad_minima']) ?>">
        </div>

        <div class="campo-admin">
            <label for="estado">Estado</label>
            <select id="estado" name="estado" required>
                <option value="Pendiente" <?= $evento['estado'] === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="Aprobado" <?= $evento['estado'] === 'Aprobado' ? 'selected' : '' ?>>Aprobado</option>
                <option value="Rechazado" <?= $evento['estado'] === 'Rechazado' ? 'selected' : '' ?>>Rechazado</option>
            </select>
        </div>

        <div class="campo-admin">
            <label for="imagen">Imagen o banner</label>
            <input id="imagen" type="text" name="imagen" value="<?= htmlspecialchars($evento['imagen'] ?? '') ?>">
        </div>

        <div class="campo-admin">
            <label for="id_categoria">Categoría</label>
            <select id="id_categoria" name="id_categoria" required>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= $categoria['id_categoria'] ?>" <?= $categoria['id_categoria'] == $evento['id_categoria'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($categoria['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo-admin">
            <label for="id_ubicacion">Ubicación</label>
            <select id="id_ubicacion" name="id_ubicacion" required>
                <?php foreach ($ubicaciones as $ubicacion): ?>
                    <option value="<?= $ubicacion['id_ubicacion'] ?>" <?= $ubicacion['id_ubicacion'] == $evento['id_ubicacion'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ubicacion['nombre_lugar']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="nota-admin">El lugar describe el evento; las coordenadas de abajo se guardan solo en este evento.</p>
        </div>

        <div class="campo-admin campo-admin-completo">
            <label>Ubicación precisa</label>
            <p class="nota-admin">Hacé clic o tocá el mapa para modificar la latitud y longitud.</p>

            <input id="latitud" type="hidden" name="latitud" value="<?= htmlspecialchars($evento['latitud']) ?>">
            <input id="longitud" type="hidden" name="longitud" value="<?= htmlspecialchars($evento['longitud']) ?>">
            <div id="mapa-admin" class="mapa-admin max-md:h-80"></div><p id="coordenadas-info" class="nota-admin text-xs" aria-live="polite"></p>
        </div>
    </div>

    <button type="submit">Guardar cambios</button>
</form>

<script src="public/assets/map/leaflet.js"></script>
<script>
    const ubicaciones = <?= json_encode($ubicaciones, JSON_UNESCAPED_UNICODE) ?>;
    const selectorUbicacion = document.getElementById('id_ubicacion');
    const campoLatitud = document.getElementById('latitud');
    const campoLongitud = document.getElementById('longitud');
    const limiteUruguay = L.latLngBounds([[-35.5, -59.0], [-30.0, -53.0]]);

    const mapa = L.map('mapa-admin', {
        maxBounds: limiteUruguay,
        maxBoundsViscosity: 1.0,
        minZoom: 7,
        maxZoom: 19,
        worldCopyJump: false
    }).setView([-34.43105, -57.43541], 9);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
        noWrap: true
    }).addTo(mapa);

    const iconoIvento = L.icon({
        iconUrl: 'public/img/ivento-pin.png',
        iconSize: [72, 72],
        iconAnchor: [36, 64],
        popupAnchor: [0, -58]
    });

    let marcador = null;

    function actualizarMarcador(centrarMapa) {
        const latitud = parseFloat(campoLatitud.value);
        const longitud = parseFloat(campoLongitud.value);

        if (Number.isNaN(latitud) || Number.isNaN(longitud)) {
            return;
        }

        document.getElementById('coordenadas-info').textContent = 'Latitud (Y): '+latitud.toFixed(6)+' · Longitud (X): '+longitud.toFixed(6);
        const punto = [latitud, longitud];

        if (marcador) {
            marcador.setLatLng(punto);
        } else {
            marcador = L.marker(punto, { icon: iconoIvento }).addTo(mapa);
        }

        if (centrarMapa) {
            mapa.setView(punto, 15);
        }
    }

    function cargarCoordenadasUbicacion() {
        const ubicacion = ubicaciones.find(function (item) {
            return String(item.id_ubicacion) === selectorUbicacion.value;
        });

        if (!ubicacion) {
            return;
        }

        campoLatitud.value = Number(ubicacion.latitud).toFixed(6);
        campoLongitud.value = Number(ubicacion.longitud).toFixed(6);
        actualizarMarcador(true);
    }

    mapa.on('click', function (eventoMapa) {
        campoLatitud.value = eventoMapa.latlng.lat.toFixed(6);
        campoLongitud.value = eventoMapa.latlng.lng.toFixed(6);
        actualizarMarcador(false);
    });

    selectorUbicacion.addEventListener('change', cargarCoordenadasUbicacion);
    campoLatitud.addEventListener('change', function () {
        actualizarMarcador(true);
    });
    campoLongitud.addEventListener('change', function () {
        actualizarMarcador(true);
    });

    actualizarMarcador(true);
</script>

</body>
</html>
