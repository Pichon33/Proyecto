<?php
// Iniciar la sesión de PHP.
// Esto permite guardar y recuperar datos del usuario mientras navega.
session_start();

// Verificar si el usuario NO ha iniciado sesión.
// Revisamos si existe la variable 'user_id' en la sesión.
if (!isset($_SESSION['user_id'])) {
    
    // Si no existe, significa que el usuario no está logueado.
    // Entonces lo redirigimos a la página de login.
    header('Location: login.php');
    
    // Detenemos el script para que no ejecute nada más.
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
</head>
<body>
    <h2>Bienvenido</h2>
    <p>Ya estas logueado en el sitio!</p>
    <a href="logout.php">Logout</a>
</body>
</html>
