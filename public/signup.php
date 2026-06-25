<?php
require '../includes/config.php';
require '../src/user.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = htmlspecialchars($_POST['password']);
    $confirmPassword = htmlspecialchars($_POST['confirm_password']);
    
    if ($password !== $confirmPassword) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $user = new User($pdo);
        $user->register(
            htmlspecialchars($_POST['username']), 
            $password, 
            htmlspecialchars($_POST['email'])
        );
        header('Location: login.php');
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="auth-page">
    <div class="back-link">
        <a href="index.php">← Volver al inicio</a>
    </div>
    
    <div class="container">
        <img class="logo" src="img/LogoNegro1.png" alt="Logo" class="logo">
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
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <a href="index.php" style="display: block; text-align: center; padding: 10px; background-color: #f0f0f0; color: #333; text-decoration: none; border-radius: 8px; font-weight: 500;">← Volver al Inicio</a>
        </div>
    </div>
</body>
</html>
