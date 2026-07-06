<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Eventos - ApexDigital</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="../assets/map/leaflet.css" />
</head>
<body class="mapa-pagina">
    <div class="encabezado-mapa">
        <h1>🗺️ Mapa de Eventos - Juan Lacaze</h1>
        <div class="enlaces-navegacion">
            <a href="menu.php">← Volver</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <div class="contenedor-mapa-completo">
        <div class="barra-lateral-mapa">
            <h2>Eventos Cercanos</h2>
            <div id="lista-eventos">
            </div>
        </div>

        <div id="mapa"></div>
    </div>

    <script src="../assets/map/leaflet.js"></script>
    <script>
        let eventos = [];

        let limiteUruguay = L.latLngBounds([
            [-35.5, -59.0],
            [-30.0, -53.0]
        ]);

        let mapa = L.map('mapa', {
            maxBounds: limiteUruguay,
            maxBoundsViscosity: 1.0,
            minZoom: 7,
            maxZoom: 19
        }).setView([-34.43105, -57.43541], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19
        }).addTo(mapa);

        function crearMarcadores() {
            eventos.forEach(function(evento) {
                if (evento.lat && evento.lng) {
                    let marcador = L.marker([evento.lat, evento.lng]).addTo(mapa);
                    let popup = '<strong>' + evento.nombre + '</strong><br>' + evento.descripcion + '<br><strong>Tipo:</strong> ' + evento.tipo;
                    marcador.bindPopup(popup);
                }
            });
        }

        function llenarListaEventos() {
            let listaEventos = document.getElementById('lista-eventos');
            listaEventos.innerHTML = '';
            eventos.forEach(function(evento) {
                let div = document.createElement('div');
                div.className = 'elemento-evento';
                div.innerHTML = '<h3>' + evento.nombre + '</h3><p>' + evento.descripcion + '</p><p><strong>' + evento.fecha + '</strong></p><span class="tipo-evento">' + evento.tipo + '</span>';
                div.addEventListener('click', function() {
                    mapa.setView([evento.lat, evento.lng], 15);
                    let marcadores = mapa._layers;
                    for (let key in marcadores) {
                        if (marcadores[key].setOpacity) {
                            let pop = marcadores[key].getPopup();
                            if (pop && pop._content.includes(evento.nombre)) {
                                marcadores[key].openPopup();
                            }
                        }
                    }
                });
                listaEventos.appendChild(div);
            });
        }

        // Cargar eventos desde JSON
        fetch('map.json')
            .then(response => response.json())
            .then(data => {
                eventos = data.eventos;
                crearMarcadores();
                llenarListaEventos();
            })
            .catch(error => {
                console.error('Error cargando eventos:', error);
                let listaEventos = document.getElementById('lista-eventos');
                listaEventos.innerHTML = '<p style="color: #cc0000;">Error al cargar eventos</p>';
            });
    </script>
</body>
</html>