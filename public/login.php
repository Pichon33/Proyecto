<?php
session_start();

require '../includes/config.php';
require '../src/models/user.php';

$error = '';
$mensaje = '';

if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $contrasena = htmlspecialchars($_POST['password']);
    
    try {
        $usuarioObj = new User($pdo);
        $usuarioIniciado = $usuarioObj->login($usuario, $contrasena, $email);
        
        if ($usuarioIniciado) {
            $_SESSION['user_id'] = $usuarioIniciado['id_usuario'];
            $_SESSION['username'] = $usuarioIniciado['username'];
            $_SESSION['rol'] = $usuarioIniciado['rol'];
            header('Location: menu.php');
            exit;
        } else {
            $error = 'Credenciales inválidas';
        }
    } catch (Exception $e) {
        $error = 'Error al iniciar sesión';
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
    <header>
        <nav>
            <div class="logo-header">
                <img class="BannerBlanco" src="img/BannerBlanco2.png" alt="ApexDigital"> 
            </div>
            <ul>
                
            </ul>
            <div class="nav-buttons">
                <a href="signup.php" class="signup-btn">Registrarse</a>
            </div>
        </nav>
    </header>
    <div class="back-link">
        <a href="home.php">← Volver</a>
    </div>
    <div class="container">
        <img class="logo" src="img/LogoNegro1.png" alt="Logo">
        <hr class="hr">
        <h2>Inicia sesión</h2>
        
        <?php if (!empty($mensaje)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
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
    <footer>
        <p>&copy; 2026 ApexDigital</p>
    </footer>
</body>
</html>
