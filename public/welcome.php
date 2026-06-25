<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ApexDigital</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="welcome-page">
    <!-- Header -->
    <div class="welcome-header">
        <h1>ApexDigital</h1>
        <div class="user-info">
            <span>Bienvenido, <strong><?php echo $username; ?></strong></span>
            <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </div>
    <div class="welcome-container">
        <h2 class="welcome-title">Dashboard</h2>
        <p class="welcome-subtitle">Accede a todas las funciones de ApexDigital</p>
        <div class="features-grid">

            <div class="feature-card">
                <div class="feature-icon">🗺️</div>
                <h2>Mapa de Eventos</h2>
                <p>Explora todos los eventos disponibles en Juan Lacaze en nuestro mapa interactivo.</p>
                <a href="map.php">Ir al Mapa</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">👤</div>
                <h2>Mi Perfil</h2>
                <p>Gestiona tu información personal y preferencias de la plataforma.</p>
                <a href="#">Mi Perfil</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">⭐</div>
                <h2>Mis Eventos</h2>
                <p>Guarda y organiza tus eventos favoritos en una lista personalizada.</p>
                <a href="#">Mis Favoritos</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">➕</div>
                <h2>Crear Evento</h2>
                <p>Publica tu propio evento para que otros usuarios lo encuentren.</p>
                <a href="#">Crear Evento</a>
            </div>
        </div>

        <div class="quick-info">
            <h2>Información Rápida</h2>
            <div class="info-grid">
                <div class="info-box">
                    <strong>📌 ¿Qué es ApexDigital?</strong>
                    <p>Somos una plataforma dedicada a conectar eventos y entretenimiento local en Juan Lacaze.</p>
                </div>
                <div class="info-box">
                    <strong>🎯 Nuestra Misión</strong>
                    <p>Facilitar el descubrimiento de eventos y fiestas para todos los usuarios de la región.</p>
                </div>
                <div class="info-box">
                    <strong>🌟 Beneficios</strong>
                    <p>Acceso completo a eventos, mapas interactivos y notificaciones personalizadas.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
