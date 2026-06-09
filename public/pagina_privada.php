<?php
// Inicia la sesión
session_start();

// Verifica si el usuario está autenticado comprobando si está definida la variable de sesión 'user_id'
if (!isset($_SESSION['user_id'])) {
    // Si el usuario no está autenticado, redirige a la página de inicio de sesión
    header('Location: public/login.php');
    exit(); // Finaliza el script para evitar que se ejecute más código
}

// Si el usuario está autenticado, muestra el contenido de la página privada
?>

<!DOCTYPE html>
<html>
<head>
    <title>Página Privada</title>
</head>
<body>
    <h1>Bienvenido a la página privada</h1>
    <p>Este es el contenido de la página privada al que solo pueden acceder usuarios autenticados.</p>
    <p>Aquí puedes incluir cualquier contenido adicional que desees mostrar a los usuarios autenticados.</p>
</body>
</html>