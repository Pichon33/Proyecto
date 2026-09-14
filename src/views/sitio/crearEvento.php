<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], ['Administrador', 'Organizador'], true)) {
    header('Location: index.php?ruta=menu');
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/config.php';

$categorias = [
    'Arte y cultura', 'Bienestar', 'Cine y teatro', 'Comunidad', 'Cultural', 'Deportivo',
    'Familia e infancia', 'Ferias y mercados', 'Gastronomía', 'Música', 'Naturaleza y aire libre',
    'Nocturnos', 'Talleres y aprendizaje', 'Tecnología y gaming', 'Voluntariado',
    'Negocios y emprendimientos'
];
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $fechaFin = $_POST['fecha_fin'] ?: null;
    $horaFin = $_POST['hora_fin'] ?: null;
    $tipoPublicacion = $_POST['tipo_publicacion'] ?? 'Evento';
    $categoria = $_POST['categoria'] ?? '';
    $lugar = trim($_POST['lugar'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $latitud = trim($_POST['latitud'] ?? '');
    $longitud = trim($_POST['longitud'] ?? '');
    $precio = str_replace(',', '.', trim($_POST['precio_uyu'] ?? '0'));
    $edadMinima = (int) ($_POST['edad_minima'] ?? 0);
    $puntosRecorrido = json_decode($_POST['recorrido'] ?? '[]', true);
    $solicitaCorte = isset($_POST['solicita_corte']);
    $ciudadCorte = trim($_POST['ciudad_corte'] ?? '');
    $imagen = '';

    if ($titulo === '' || $fecha === '' || $hora === '' || $lugar === '' || $direccion === '') {
        $error = 'Completá todos los campos obligatorios.';
    } elseif (!in_array($categoria, $categorias, true) || !in_array($tipoPublicacion, ['Evento', 'Negocio'], true)) {
        $error = 'Seleccioná una categoría y un tipo de publicación válidos.';
    } elseif (!is_numeric($latitud) || !is_numeric($longitud) || $latitud < -35.5 || $latitud > -30 || $longitud < -59 || $longitud > -53) {
        $error = 'Marcá la ubicación del evento o negocio en el mapa.';
    } elseif (!is_numeric($precio) || (float) $precio < 0 || $edadMinima < 0 || $edadMinima > 120) {
        $error = 'Revisá el precio y la edad mínima.';
    } elseif (($fechaFin === null) !== ($horaFin === null)) {
        $error = 'Completá fecha y hora de finalización juntas, o dejá ambas vacías.';
    } elseif ($fechaFin !== null && strtotime($fechaFin . ' ' . $horaFin) < strtotime($fecha . ' ' . $hora)) {
        $error = 'La finalización no puede ser anterior al inicio.';
    } elseif (!is_array($puntosRecorrido) || (isset($_POST['tiene_recorrido']) && count($puntosRecorrido) < 2)) {
        $error = 'Para un recorrido marcá al menos dos puntos en el mapa.';
    } elseif ($solicitaCorte && ($ciudadCorte === '' || $fechaFin === null || $horaFin === null || count($puntosRecorrido) < 2)) {
        $error = 'Para solicitar un corte indicá la ciudad, la finalización y al menos dos puntos del recorrido.';
    } else {
        if (!empty($_FILES['imagen']['name'])) {
            $tiposImagen = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $tipoImagen = mime_content_type($_FILES['imagen']['tmp_name']);

            if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK || $_FILES['imagen']['size'] > 5 * 1024 * 1024) {
                $error = 'La imagen debe pesar menos de 5 MB.';
            } elseif (!isset($tiposImagen[$tipoImagen]) || getimagesize($_FILES['imagen']['tmp_name']) === false) {
                $error = 'La imagen debe ser JPG, PNG o WEBP.';
            } else {
                $carpetaImagenes = dirname(__DIR__, 3) . '/public/img/eventos';
                if (!is_dir($carpetaImagenes)) {
                    mkdir($carpetaImagenes, 0755, true);
                }

                $nombreImagen = 'evento_' . time() . '_' . $_SESSION['user_id'] . '.' . $tiposImagen[$tipoImagen];
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $carpetaImagenes . '/' . $nombreImagen)) {
                    $imagen = 'public/img/eventos/' . $nombreImagen;
                } else {
                    $error = 'No se pudo guardar la imagen del evento.';
                }
            }
        }

        if ($error === '') {
            try {
                $buscarCategoria = $pdo->prepare('SELECT id_categoria FROM Categoria WHERE nombre = ? LIMIT 1');
                $buscarCategoria->execute([$categoria]);
                $idCategoria = $buscarCategoria->fetchColumn();

                if (!$idCategoria) {
                    $nuevaCategoria = $pdo->prepare('INSERT INTO Categoria (nombre, tipo) VALUES (?, ?)');
                    $nuevaCategoria->execute([$categoria, 'Evento']);
                    $idCategoria = $pdo->lastInsertId();
                }

                $buscarUbicacion = $pdo->prepare('SELECT id_ubicacion FROM Ubicacion WHERE nombre_lugar = ? AND direccion = ? LIMIT 1');
                $buscarUbicacion->execute([$lugar, $direccion]);
                $idUbicacion = $buscarUbicacion->fetchColumn();

                if (!$idUbicacion) {
                    $nuevaUbicacion = $pdo->prepare(
                        'INSERT INTO Ubicacion (nombre_lugar, direccion, latitud, longitud) VALUES (?, ?, ?, ?)'
                    );
                    $nuevaUbicacion->execute([$lugar, $direccion, $latitud, $longitud]);
                    $idUbicacion = $pdo->lastInsertId();
                }

                $estadoEvento = $_SESSION['rol'] === 'Administrador' ? 'Aprobado' : 'Pendiente';
                $crearEvento = $pdo->prepare(
                    'INSERT INTO Evento (titulo, descripcion, fecha, hora, fecha_fin, hora_fin, tipo_publicacion, precio_uyu, edad_minima, estado, imagen, latitud, longitud, id_usuario, id_categoria, id_ubicacion)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $crearEvento->execute([
                    $titulo, $descripcion, $fecha, $hora, $fechaFin, $horaFin, $tipoPublicacion,
                    $precio, $edadMinima, $estadoEvento, $imagen, $latitud, $longitud,
                    $_SESSION['user_id'], $idCategoria, $idUbicacion
                ]);
                $idEvento = $pdo->lastInsertId();

                if (isset($_POST['tiene_recorrido']) && count($puntosRecorrido) >= 2) {
                    $guardarRecorrido = $pdo->prepare('INSERT INTO Recorrido (id_evento, descripcion, puntos) VALUES (?, ?, ?)');
                    $guardarRecorrido->execute([$idEvento, 'Recorrido del evento', json_encode($puntosRecorrido)]);
                }

                if ($solicitaCorte) {
                    $guardarCorte = $pdo->prepare(
                        'INSERT INTO CorteCalle (id_evento, id_usuario, ciudad, descripcion, fecha_inicio, fecha_fin, recorrido)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $guardarCorte->execute([
                        $idEvento,
                        $_SESSION['user_id'],
                        $ciudadCorte,
                        'Solicitud vinculada al evento: ' . $titulo,
                        $fecha . ' ' . $hora,
                        $fechaFin . ' ' . $horaFin,
                        json_encode($puntosRecorrido)
                    ]);
                }

                $mensaje = $estadoEvento === 'Pendiente'
                    ? 'La publicación quedó pendiente de aprobación administrativa.'
                    : 'La publicación se creó y aprobó correctamente.';
            } catch (PDOException $e) {
                $error = 'No se pudo crear la publicación. Revisá los datos e intentá nuevamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear publicación | iVento</title>
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="stylesheet" href="public/assets/map/leaflet.css">
</head>
<body class="pagina-formulario">
    <header class="cabecera">
        <div class="contenedor-cabecera">
            <a href="index.php?ruta=home" class="logo-header">
                <img src="public/img/ivento-logo-horizontal-blanco.png" alt="iVento">
            </a>
            <nav class="navegacion-principal">
                <a href="index.php?ruta=home">Inicio</a>
                <a href="index.php?ruta=mapa">Mapa</a>
                <a href="index.php?ruta=menu">Mi espacio</a>
                <a href="index.php?ruta=perfil">Perfil</a>
            </nav>
        </div>
    </header>

    <main class="contenedor-formulario">
        <section class="encabezado-pagina">
            <span class="etiqueta-portada">Organización</span>
            <h1>Crear publicación</h1>
            <p>Publicá un evento o presentá un nuevo negocio, emprendimiento o puesto de venta.</p>
        </section>

        <?php if ($mensaje !== ''): ?>
            <div class="mensaje-exito"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="formulario-evento" method="POST" enctype="multipart/form-data">
            <div class="dos-columnas">
                <label>
                    Tipo de publicación
                    <select name="tipo_publicacion" required>
                        <option value="Evento">Evento</option>
                        <option value="Negocio">Nuevo negocio o emprendimiento</option>
                    </select>
                </label>
                <label>
                    Categoría
                    <select name="categoria" required>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo htmlspecialchars($categoria); ?>"><?php echo htmlspecialchars($categoria); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <label>
                Nombre del evento o negocio
                <input type="text" name="titulo" required>
            </label>

            <label>
                Descripción
                <textarea name="descripcion" placeholder="Contá de qué se trata, qué ofrece o qué se va a realizar."></textarea>
            </label>

            <div class="dos-columnas">
                <label>
                    Fecha de inicio
                    <input type="date" name="fecha" required>
                </label>
                <label>
                    Hora de inicio
                    <input type="time" name="hora" required>
                </label>
            </div>

            <div class="dos-columnas">
                <label>
                    Fecha de finalización
                    <input type="date" name="fecha_fin">
                </label>
                <label>
                    Hora de finalización
                    <input type="time" name="hora_fin">
                </label>
            </div>

            <div class="dos-columnas">
                <label>
                    Precio de entrada (UYU)
                    <input type="number" name="precio_uyu" min="0" step="0.01" value="0">
                </label>
                <label>
                    Edad mínima
                    <input type="number" name="edad_minima" min="0" max="120" value="0">
                </label>
            </div>

            <div class="dos-columnas">
                <label>
                    Lugar
                    <input type="text" name="lugar" placeholder="Ej.: Plaza de Deportes" required>
                </label>
                <label>
                    Dirección
                    <input type="text" name="direccion" placeholder="Ej.: Calle principal 123" required>
                </label>
            </div>

            <label class="campo-archivo">
                Imagen o folleto de portada (opcional)
                <input type="file" name="imagen" accept="image/png, image/jpeg, image/webp">
            </label>

            <div id="mapa-selector" class="mapa-selector" aria-label="Selector de ubicación"></div>
            <p id="ayuda-mapa" class="ayuda-mapa">Tocá o hacé clic en el mapa para marcar la ubicación obligatoria.</p>

            <div class="dos-columnas">
                <label>
                    Latitud
                    <input id="latitud" type="number" name="latitud" step="any" readonly required>
                </label>
                <label>
                    Longitud
                    <input id="longitud" type="number" name="longitud" step="any" readonly required>
                </label>
            </div>

            <label class="opcion-recorrido">
                <input id="tiene-recorrido" type="checkbox" name="tiene_recorrido">
                Este evento tiene un recorrido
            </label>

            <div id="controles-recorrido" class="controles-recorrido">
                <button id="limpiar-recorrido" class="boton-pequeno" type="button">Limpiar recorrido</button>
                <span>Con el recorrido activo, tocá el mapa para agregar puntos.</span>
            </div>

            <label class="opcion-recorrido">
                <input id="solicita-corte" type="checkbox" name="solicita_corte">
                Solicitar corte de calle para este evento
            </label>

            <div id="datos-corte" class="controles-recorrido">
                <label>
                    Ciudad del corte
                    <input type="text" name="ciudad_corte" placeholder="Ej.: Juan Lacaze">
                </label>
                <span>La solicitud queda pendiente hasta que la apruebe un administrador o autoridad municipal.</span>
            </div>

            <input id="recorrido" type="hidden" name="recorrido" value="[]">
            <button class="boton" type="submit">Publicar</button>
        </form>
    </main>

    <footer class="pie-pagina">
        <div class="pie-contenedor">
            <p>&copy; 2026 iVento · ApexDigital SA</p>
            <nav class="pie-enlaces">
                <a href="index.php?ruta=home">Inicio</a>
                <a href="index.php?ruta=menu">Mi espacio</a>
                <a href="index.php?ruta=favoritos">Favoritos</a>
            </nav>
        </div>
    </footer>

    <script src="public/assets/map/leaflet.js"></script>
    <script>
        const limiteUruguay = L.latLngBounds([[-35.5, -59.0], [-30.0, -53.0]]);
        const mapaSelector = L.map('mapa-selector', {
            maxBounds: limiteUruguay,
            maxBoundsViscosity: 1.0,
            minZoom: 7,
            maxZoom: 19,
            worldCopyJump: false
        }).setView([-34.43105, -57.43541], 13);
        const campoLatitud = document.getElementById('latitud');
        const campoLongitud = document.getElementById('longitud');
        const tieneRecorrido = document.getElementById('tiene-recorrido');
        const campoRecorrido = document.getElementById('recorrido');
        const ayudaMapa = document.getElementById('ayuda-mapa');
        const controlesRecorrido = document.getElementById('controles-recorrido');
        const solicitaCorte = document.getElementById('solicita-corte');
        const datosCorte = document.getElementById('datos-corte');
        const iconoIvento = L.icon({
            iconUrl: 'public/img/ivento-marker.png',
            iconSize: [36, 44],
            iconAnchor: [18, 41],
            popupAnchor: [0, -38]
        });
        let marcadorUbicacion = null;
        let lineaRecorrido = null;
        let puntosRecorrido = [];

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19,
            noWrap: true
        }).addTo(mapaSelector);

        function actualizarRecorrido() {
            campoRecorrido.value = JSON.stringify(puntosRecorrido);

            if (lineaRecorrido) {
                mapaSelector.removeLayer(lineaRecorrido);
            }

            if (puntosRecorrido.length > 0) {
                lineaRecorrido = L.polyline(puntosRecorrido, { color: '#6d28d9', weight: 4 }).addTo(mapaSelector);
            }
        }

        mapaSelector.on('click', function (evento) {
            const punto = evento.latlng;

            if (tieneRecorrido.checked) {
                puntosRecorrido.push([punto.lat, punto.lng]);
                actualizarRecorrido();

                if (!marcadorUbicacion) {
                    campoLatitud.value = punto.lat.toFixed(6);
                    campoLongitud.value = punto.lng.toFixed(6);
                    marcadorUbicacion = L.marker(punto, { icon: iconoIvento }).addTo(mapaSelector);
                }

                ayudaMapa.textContent = 'Puntos del recorrido: ' + puntosRecorrido.length + '. Marcá al menos dos o limpiá para empezar de nuevo.';
                return;
            }

            campoLatitud.value = punto.lat.toFixed(6);
            campoLongitud.value = punto.lng.toFixed(6);

            if (marcadorUbicacion) {
                marcadorUbicacion.setLatLng(punto);
            } else {
                marcadorUbicacion = L.marker(punto, { icon: iconoIvento }).addTo(mapaSelector);
            }

            ayudaMapa.textContent = 'Ubicación marcada. Ahora podés publicar o agregar un recorrido opcional.';
        });

        tieneRecorrido.addEventListener('change', function () {
            controlesRecorrido.classList.toggle('activo', tieneRecorrido.checked);
            ayudaMapa.textContent = tieneRecorrido.checked
                ? 'Tocá el mapa para marcar los puntos del recorrido.'
                : 'Tocá o hacé clic en el mapa para marcar la ubicación obligatoria.';
        });

        document.getElementById('limpiar-recorrido').addEventListener('click', function () {
            puntosRecorrido = [];
            actualizarRecorrido();
            ayudaMapa.textContent = 'Recorrido limpiado. Tocá el mapa para agregar nuevos puntos.';
        });

        solicitaCorte.addEventListener('change', function () {
            datosCorte.classList.toggle('activo', solicitaCorte.checked);

            if (solicitaCorte.checked && !tieneRecorrido.checked) {
                tieneRecorrido.checked = true;
                tieneRecorrido.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>
