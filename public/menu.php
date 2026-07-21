<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$usuario = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="welcome-page">
    <div class="welcome-header">
        <img class="BannerBlanco" src="img/BannerBlanco2.png" alt="ApexDigital">
        <div class="user-info">
            <span>Bienvenido, <strong><?php echo $usuario; ?></strong></span>
            <div class="AdminPanel">
            <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'Administrador'): ?>
                <a href="../src/models/admin/panelAdmin.php">Admin Panel</a>
            <?php endif; ?>
            </div>
            <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="welcome-container">
        <h2 class="welcome-title">Dashboard</h2>
        <p class="welcome-subtitle">Explora nuestras funciones</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🗺️</div>
                <h2>Mapa de Eventos</h2>
                <p>Explora eventos disponibles en Juan Lacaze.</p>
                <a href="map.php">Ver Mapa</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">👤</div>
                <h2>Mi Perfil</h2>
                <p>Gestiona tu información personal.</p>
                <a href="#">Mi Perfil</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">❤️</div>
                <h2>Favoritos</h2>
                <p>Guarda tus eventos preferidos.</p>
                <a href="#">Mis Favoritos</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">➕</div>
                <h2>Crear Evento</h2>
                <p>Publica tu propio evento.</p>
                <a href="#">Crear</a>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2026 ApexDigital</p>
    </footer>
</body>
</html>
