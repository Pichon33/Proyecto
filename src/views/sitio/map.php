<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?ruta=login');
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/config.php';

$consultaEventos = $pdo->query(
    "SELECT e.id_evento, e.titulo, e.descripcion, e.fecha, e.hora, e.fecha_fin, e.hora_fin,
            e.tipo_publicacion, e.precio_uyu, e.edad_minima, e.imagen, e.latitud, e.longitud,
            c.nombre AS categoria, r.puntos
     FROM Evento e
     INNER JOIN Categoria c ON e.id_categoria = c.id_categoria
     INNER JOIN Ubicacion u ON e.id_ubicacion = u.id_ubicacion
     LEFT JOIN Recorrido r ON e.id_evento = r.id_evento
     WHERE e.estado = 'Aprobado'
     ORDER BY e.fecha ASC, e.hora ASC"
);
$eventosBase = [];

foreach ($consultaEventos->fetchAll(PDO::FETCH_ASSOC) as $evento) {
    $eventosBase[] = [
        'nombre' => $evento['titulo'],
        'descripcion' => $evento['descripcion'],
        'tipo' => $evento['categoria'],
        'fecha' => $evento['fecha'],
        'hora' => $evento['hora'],
        'fecha_fin' => $evento['fecha_fin'],
        'hora_fin' => $evento['hora_fin'],
        'tipo_publicacion' => $evento['tipo_publicacion'],
        'precio_uyu' => $evento['precio_uyu'],
        'edad_minima' => $evento['edad_minima'],
        'imagen' => $evento['imagen'],
        'lat' => $evento['latitud'] === null ? null : (float) $evento['latitud'],
        'lng' => $evento['longitud'] === null ? null : (float) $evento['longitud'],
        'recorrido' => $evento['puntos'] ? json_decode($evento['puntos'], true) : []
    ];
}

$consultaCortes = $pdo->query(
    "SELECT ciudad, descripcion, fecha_inicio, fecha_fin, recorrido
     FROM CorteCalle
     WHERE estado = 'Aprobado'
     ORDER BY fecha_inicio ASC"
);
$cortesAprobados = $consultaCortes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de eventos | iVento</title>
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="stylesheet" href="public/assets/map/leaflet.css">
</head>
<body class="mapa-pagina">
    <header class="cabecera">
        <div class="contenedor-cabecera">
            <a href="index.php?ruta=home" class="logo-header">
                <img src="public/img/ivento-logo-horizontal-blanco.png" alt="iVento">
            </a>
            <nav class="navegacion-principal">
                <a href="index.php?ruta=home">Inicio</a>
                <a href="index.php?ruta=menu">Mi espacio</a>
                <a href="index.php?ruta=perfil">Perfil</a>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador'): ?>
                    <a href="index.php?ruta=admin" class="boton-nav">Administración</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="contenedor-mapa-completo">
        <aside class="barra-lateral-mapa">
            <h2>Eventos cercanos</h2>
            <div id="lista-eventos"></div>
        </aside>
        <div id="mapa" aria-label="Mapa de eventos"></div>
    </main>

    <script src="public/assets/map/leaflet.js"></script>
    <script>
        const eventos = [];
        const eventosBase = <?php echo json_encode($eventosBase, JSON_UNESCAPED_UNICODE); ?>;
        const cortesAprobados = <?php echo json_encode($cortesAprobados, JSON_UNESCAPED_UNICODE); ?>;
        const marcadores = [];
        const limiteUruguay = L.latLngBounds([[-35.5, -59.0], [-30.0, -53.0]]);

        const mapa = L.map('mapa', {
            maxBounds: limiteUruguay,
            maxBoundsViscosity: 1.0,
            minZoom: 7,
            maxZoom: 19,
            worldCopyJump: false
        }).setView([-34.43105, -57.43541], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19,
            noWrap: true
        }).addTo(mapa);

        const iconoIvento = L.icon({
            iconUrl: 'public/img/ivento-marker.png',
            iconSize: [36, 44],
            iconAnchor: [18, 41],
            popupAnchor: [0, -38]
        });

        function crearMarcadores() {
            eventos.forEach(function (evento, indice) {
                if (Array.isArray(evento.recorrido) && evento.recorrido.length >= 2) {
                    L.polyline(evento.recorrido, {
                        color: '#6d28d9',
                        weight: 4
                    }).addTo(mapa);
                }

                if (evento.lat === null || evento.lng === null) {
                    marcadores[indice] = null;
                    return;
                }

                const marcador = L.marker([evento.lat, evento.lng], { icon: iconoIvento }).addTo(mapa);
                const contenido = crearContenidoPopup(evento);

                marcador.bindPopup(contenido);
                marcadores[indice] = marcador;
            });
        }

        function mostrarCortesAprobados() {
            cortesAprobados.forEach(function (corte) {
                const recorrido = corte.recorrido ? JSON.parse(corte.recorrido) : [];

                if (!Array.isArray(recorrido) || recorrido.length < 2) {
                    return;
                }

                L.polyline(recorrido, {
                    color: '#dc2626',
                    weight: 5,
                    dashArray: '10 8'
                }).addTo(mapa).bindPopup(
                    '<strong>Corte de calle aprobado</strong><br>' +
                    textoSeguro(corte.ciudad) + '<br>' +
                    textoSeguro(corte.descripcion) + '<br>' +
                    textoSeguro(corte.fecha_inicio) + ' a ' + textoSeguro(corte.fecha_fin)
                );
            });
        }

        function textoSeguro(texto) {
            return String(texto || '').replace(/[&<>'"]/g, function (caracter) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[caracter];
            });
        }

        function crearContenidoPopup(evento) {
            const horarioInicio = String(evento.hora || '').slice(0, 5);
            const horarioFin = evento.hora_fin ? String(evento.hora_fin).slice(0, 5) : '';
            const finalizacion = evento.fecha_fin ? '<p><strong>Finaliza:</strong> ' + textoSeguro(evento.fecha_fin) + (horarioFin ? ' · ' + textoSeguro(horarioFin) : '') + '</p>' : '';
            const precio = Number(evento.precio_uyu || 0) > 0 ? '$ ' + Number(evento.precio_uyu).toFixed(0) + ' UYU' : 'Entrada gratis';
            const edad = Number(evento.edad_minima || 0) > 0 ? '+' + evento.edad_minima + ' años' : 'Todas las edades';
            const imagen = evento.imagen ? '<img src="' + textoSeguro(evento.imagen) + '" alt="Portada de ' + textoSeguro(evento.nombre) + '">' : '';

            return '<div class="popup-evento">' + imagen +
                '<span class="etiqueta-tipo">' + textoSeguro(evento.tipo_publicacion || 'Evento') + '</span>' +
                '<h3>' + textoSeguro(evento.nombre) + '</h3>' +
                '<p>' + textoSeguro(evento.descripcion) + '</p>' +
                '<p><strong>' + textoSeguro(evento.fecha) + '</strong>' + (horarioInicio ? ' · ' + textoSeguro(horarioInicio) : '') + '</p>' +
                finalizacion +
                '<p>' + textoSeguro(evento.tipo) + ' · ' + precio + '</p>' +
                '<p>' + edad + '</p></div>';
        }

        function llenarListaEventos() {
            const lista = document.getElementById('lista-eventos');
            lista.innerHTML = '';

            eventos.forEach(function (evento, indice) {
                const tarjeta = document.createElement('article');
                tarjeta.className = 'elemento-evento';
                tarjeta.innerHTML = '<h3>' + textoSeguro(evento.nombre) + '</h3><p>' + textoSeguro(evento.descripcion) + '</p><p><strong>' + textoSeguro(evento.fecha) + '</strong></p><span class="tipo-evento">' + textoSeguro(evento.tipo_publicacion || evento.tipo) + '</span>';

                tarjeta.addEventListener('click', function () {
                    if (marcadores[indice]) {
                        mapa.setView([evento.lat, evento.lng], 15);
                        marcadores[indice].openPopup();
                    }
                });

                lista.appendChild(tarjeta);
            });
        }

        function cargarEventos(datos) {
            datos.forEach(function (evento) {
                eventos.push(evento);
            });

            crearMarcadores();
            llenarListaEventos();
        }

        mostrarCortesAprobados();

        if (eventosBase.length === 0) {
            document.getElementById('lista-eventos').innerHTML = '<p>Todavía no hay eventos publicados.</p>';
        } else {
            cargarEventos(eventosBase);
        }
    </script>
</body>
</html>
