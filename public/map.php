<?php
// Iniciar sesión para verificar si el usuario está logueado
session_start();

// Verificar si el usuario NO ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    // Si no está logueado, redirigir a login
    header('Location: login.php');
    exit();
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
            <a href="welcome.php">← Volver</a>
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
        // ========================================
        // DATOS DE EVENTOS (ejemplo básico)
        // ========================================
        const eventos = [
            {
                id: 1,
                nombre: "-",
                descripcion: "-",
                tipo: "-",
                lat: -34.437655,
                lng: -57.434721,
                fecha: "-"
            },
            {
                id: 2,
                nombre: "-",
                descripcion: "-",
                tipo: "-",
                lat: -34.440,
                lng: -57.430,
                fecha: "-"
            },
            {
                id: 3,
                nombre: "-",
                descripcion: "-",
                tipo: "-",
                lat: -34.435,
                lng: -57.438,
                fecha: "-"
            }
        ];

        // ========================================
        // INICIALIZAR MAPA CON LÍMITES DE URUGUAY
        // ========================================
        // Límites aproximados de Uruguay
        const limiteUruguay = L.latLngBounds([
            [-35.5, -59.0], // suroeste
            [-30.0, -53.0]  // noreste
        ]);

        const mapa = L.map('mapa', {
            maxBounds: limiteUruguay,
            maxBoundsViscosity: 1.0,
            minZoom: 7,
            maxZoom: 19
        }).setView([-34.43105, -57.43541], 7);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19
        }).addTo(mapa);

        // ========================================
        // FUNCIÓN: Crear marcadores en el mapa
        // ========================================
        function crearMarcadores() {
            eventos.forEach(evento => {
                const marcador = L.marker([evento.lat, evento.lng]).addTo(mapa);
                const contenidoPopup = `
                    <div style="font-weight: bold; color: var(--color-primary);">${evento.nombre}</div>
                    <div style="font-size: 12px; color: #666;">${evento.descripcion}</div>
                    <div style="margin-top: 5px; font-size: 12px;">
                        <strong>Tipo:</strong> ${evento.tipo}<br>
                        <strong>Fecha:</strong> ${evento.fecha}
                    </div>
                `;
                marcador.bindPopup(contenidoPopup);
            });
        }

        // ========================================
        // FUNCIÓN: Llenar lista de eventos en la barra lateral
        // ========================================
        function llenarListaEventos() {
            const lista = document.getElementById('lista-eventos');
            lista.innerHTML = '';

            eventos.forEach(evento => {
                const div = document.createElement('div');
                div.className = 'elemento-evento';
                div.innerHTML = `
                    <h3>${evento.nombre}</h3>
                    <p>${evento.descripcion}</p>
                    <p><strong>${evento.fecha}</strong></p>
                    <span class="tipo-evento">${evento.tipo}</span>
                `;

                // Al hacer click en un evento, centrar mapa y mostrar popup
                div.addEventListener('click', () => {
                    mapa.setView([evento.lat, evento.lng], 15);
                });

                lista.appendChild(div);
            });
        }

        // ========================================
        // EJECUTAR AL CARGAR
        // ========================================
        crearMarcadores();
        llenarListaEventos();
    </script>
</body>
</html>