<?php
// Iniciar o reanudar la sesión actual
// (necesario para poder destruirla después)
session_start();

// Destruir todos los datos de la sesión
// Esto cierra la sesión del usuario (logout)
session_destroy();

// Redirigir al usuario de nuevo a la página de login
header('Location: login.php');
?>