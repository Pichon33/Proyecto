<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexDigital - Eventos y Entretenimiento Local</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="../assets/map/leaflet.css" />
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo-header">APEX<span class="logo-accent">DIGITAL</span></div>
            <ul>
                <li><a href="#about">Nosotros</a></li>
                <li><a href="#mision">Misión & Visión</a></li>
                <li><a href="#objetivos">Objetivos</a></li>
                <li><a href="#valores">Valores</a></li>
                <li><a href="#ubicacion">Ubicación</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="login.php" class="login-btn">Ingresar</a>
                <a href="signup.php" class="signup-btn">Registrarse</a>
            </div>
        </nav>
    </header>

    <!-- Hero -->
    <section class="hero">
        <h1>APEXDIGITAL</h1>
        <p>Atrapamos lo mejor del momento</p>
        <p class="hero-desc">Descubre eventos, fiestas y entretenimiento en Juan Lacaze y sus alrededores</p>
        <div class="cta-buttons">
            <a href="signup.php" class="cta-btn cta-primary">Explorar Ahora</a>
            <a href="#mision" class="cta-btn cta-secondary">Conoce Más</a>
        </div>
    </section>

    <!-- Nosotros -->
    <section id="about" class="about-section">
        <h2>Quiénes Somos</h2>
        <div class="about-content">
            Somos ApexDigital, una plataforma digital dedicada a conectar a la comunidad local con los mejores eventos y espacios de entretenimiento en Juan Lacaze y la región.
        </div>
    </section>

    <!-- Misión & Visión -->
    <section id="mision">
        <h2>Misión & Visión</h2>
        <div class="mv-container">
            <div class="mv-card">
                <h3>🎯 Misión</h3>
                <p>Conectar organizadores y usuarios a través de una plataforma práctica y accesible para eventos y entretenimiento local.</p>
            </div>
            <div class="mv-card">
                <h3>🚀 Visión</h3>
                <p>Ser la referencia digital en la región para descubrir y disfrutar de eventos, fiestas y actividades sociales.</p>
            </div>
        </div>
    </section>

    <!-- Objetivos -->
    <section id="objetivos" class="objectives-section">
        <h2>Nuestros Objetivos</h2>
        <div class="objectives-grid">
            <div class="objective-item">
                <h4>📱 Plataforma Intuitiva</h4>
                <p>Interfaz accesible para todos los usuarios</p>
            </div>
            <div class="objective-item">
                <h4>📢 Difusión Digital</h4>
                <p>Herramientas modernas de promoción local</p>
            </div>
            <div class="objective-item">
                <h4>📈 Crecimiento</h4>
                <p>Conectar eventos y locales con usuarios</p>
            </div>
            <div class="objective-item">
                <h4>🔍 Información Confiable</h4>
                <p>Datos actualizados y verificados</p>
            </div>
            <div class="objective-item">
                <h4>🎪 Variedad</h4>
                <p>Carnavales, fiestas, ferias y más</p>
            </div>
        </div>
    </section>

    <!-- Valores -->
    <section id="valores" class="values-section">
        <h2>Nuestros Valores</h2>
        <div class="values-grid">
            <div class="value-card">
                <h3>👥 Respeto</h3>
                <p>Trato justo con nuestro equipo y comunidad</p>
            </div>
            <div class="value-card">
                <h3>🤝 Transparencia</h3>
                <p>Actitud honesta y ética en cada acción</p>
            </div>
            <div class="value-card">
                <h3>🌍 Responsabilidad</h3>
                <p>Impacto positivo en la sociedad</p>
            </div>
            <div class="value-card">
                <h3>🔍 Análisis</h3>
                <p>Mejora continua y aprendizaje constante</p>
            </div>
        </div>
    </section>

    <!-- Ubicación -->
    <section id="ubicacion" class="location-section">
        <h2>Ubicación</h2>
        <div class="map-container">
            <div class="map-info">
                <h3>Juan Lacaze, Uruguay</h3>
                <p><strong>Proyecto digital</strong> sin local físico, desarrollado desde Rosario y Juan Lacaze.</p>
                <p>Nuestro alcance es la ciudad y sus alrededores, conectando eventos y entretenimiento local.</p>
            </div>
            <div id="map"></div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <h4>APEXDIGITAL</h4>
        <p>Plataforma de eventos y entretenimiento local</p>
        <div class="footer-links">
            <a href="login.php">Ingresar</a>
            <a href="signup.php">Registrarse</a>
            <a href="#about">Nosotros</a>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 ApexDigital</p>
        </div>
    </footer>

    <!-- Leaflet JS -->
    <script src="../assets/map/leaflet.js"></script>
    <script>
        var mapElement = document.getElementById('map');
        if (mapElement) {
            var coords = [-34.437655, -57.434721];
            
            var map = L.map('map', {
                center: coords,
                zoom: 12,
                minZoom: 10,
                maxZoom: 18
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19
            }).addTo(map);

            var marker = L.marker(coords).addTo(map);
            marker.bindPopup('<strong>ApexDigital</strong><br>Juan Lacaze, Uruguay').openPopup();
        }
    </script>
</body>
</html>