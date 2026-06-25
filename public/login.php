<?php
session_start();

require '../includes/config.php';
require '../src/user.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['password']);
    
    $user = new User($pdo);
    $loggedInUser = $user->login($username, $password, $email);
    
    if ($loggedInUser) {
        $_SESSION['user_id'] = $loggedInUser['id'];
        $_SESSION['username'] = $loggedInUser['username'];
        header('Location: welcome.php');
        exit;
    } else {
        $error = 'Credenciales inválidas';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="auth-page">
    <div class="back-link">
        <a href="home.php">← Volver al inicio</a>
    </div>
    
    <div class="container">
        <img class="logo" src="img/LogoNegro1.png" alt="Logo" class="logo">
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
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <a href="home.php" style="display: block; text-align: center; padding: 10px; background-color: #f0f0f0; color: #333; text-decoration: none; border-radius: 8px; font-weight: 500;">← Volver al Inicio</a>
        </div>
    </div>
</body>
</html>
