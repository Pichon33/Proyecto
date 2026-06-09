<?php
// Incluir el archivo de configuración de la base de datos y la clase User
require '../database/database.php';
require '../src/User.php';

$error = '';

// Verificar si se recibió una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear una instancia de la clase User, pasando la conexión PDO como argumento
    $user = new User($pdo);
    // Sanitizar la entrada del usuario e intenta iniciar sesión
    $loggedInUser = $user->login(htmlspecialchars($_POST['username']), htmlspecialchars($_POST['password']), htmlspecialchars($_POST['email']));
    // Verificar si se pudo iniciar sesión correctamente
    if ($loggedInUser) {
        // Iniciar sesión de PHP para mantener al usuario autenticado
        session_start();
        // Almacenar el ID de usuario en la sesión para identificar al usuario
        $_SESSION['user_id'] = $loggedInUser['id'];
        // Redirigir al usuario a la página de bienvenida después de iniciar sesión
        header('Location: welcome.php');
        exit;
    } else {
        // Mostrar un mensaje de error si las credenciales de inicio de sesión son incorrectas
        $error = 'Credenciales de inicio de sesión inválidas';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    
    <div class="container">
        <img class="logo" src="img/logonegro.png" alt="Logo" class="logo">
        <hr class="hr">
        <br>
        <h2>Inicia sesión</h2>
        <?php if (!empty($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <input type="text" name="username" required placeholder="Nombre de usuario">
            <input type="email" name="email" required placeholder="Correo electrónico">
            <input type="password" name="password" required placeholder="Contraseña">
            <button type="submit">Iniciar Sesión</button>
        </form>
        <div class="form-link">
            ¿No tienes cuenta? <a href="signup.php">Registrarse</a>
        </div>
    </div>
</body>
</html>
