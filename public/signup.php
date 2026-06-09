<?php
// Incluir el archivo de configuración de la base de datos y la clase User
require '../database/database.php';
require '../src/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    
    // Confirmar que la contraseña ingresada sea igual a la confirmación de contraseña
    if (isset($_POST['password']) && isset($_POST['confirm_password'])) {
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $error = "Las contraseñas no coinciden.";
        }
    }
    
    // Solo registrar si no hay errores
    if (empty($error)) {
        $user = new User($pdo);
        $user->register(htmlspecialchars($_POST['username']), htmlspecialchars($_POST['password']), htmlspecialchars($_POST['email']));
        // Redirigir al usuario a la página de inicio de sesión después del registro
        header('Location: login.php');
        exit;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <img class="logo" src="img/logonegro.png" alt="Logo" class="logo">
        <hr class="hr">
        <br>
        <h2>Registrarse</h2>
        <?php if (!empty($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="signup.php">
            <input type="text" name="username" required placeholder="Nombre de usuario">
            <input type="email" name="email" required placeholder="Correo electrónico">
            <input type="password" name="password" required placeholder="Contraseña">
            <input type="password" name="confirm_password" required placeholder="Confirmar contraseña">
            <button type="submit">Registrarse</button>
        </form>
        <div class="form-link">
            ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
        </div>
    </div>
</body>
</html>
