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
    $email = htmlspecialchars($_POST['email']);
    $contrasena = htmlspecialchars($_POST['password']);
    
    try {
        $usuarioObj = new User($pdo);
        $usuarioIniciado = $usuarioObj->login($contrasena, $email);
        
        if ($usuarioIniciado) {
            $_SESSION['user_id'] = $usuarioIniciado['id_usuario'];
            $_SESSION['username'] = $usuarioIniciado['username'];
            $_SESSION['email'] = $usuarioIniciado['email'];
            $rolesValidos = [
                'usuario' => 'Usuario',
                'organizador' => 'Organizador',
                'administrador' => 'Administrador',
                'autoridadmunicipal' => 'AutoridadMunicipal'
            ];
            $_SESSION['rol'] = $rolesValidos[strtolower($usuarioIniciado['rol'])] ?? 'Usuario';
            header('Location: home.php');
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
<body class="pagina-autenticacion">
    <header class="cabecera">
        <div class="contenedor-cabecera">
            <a href="home.php" class="logo-header">
                <img src="img/ivento-logo.jpeg" alt="iVento">
            </a>
            <div class="nav-buttons">
                <a href="signup.php" class="boton-registrarse">Registrarse</a>
            </div>
        </div>
    </header>
    <div class="enlace-volver">
        <a href="home.php">← Volver</a>
    </div>
    <main class="contenedor">
        <img class="logo" src="img/ivento-logo.jpeg" alt="iVento">

        <h2 class="h2">Inicia sesión</h2>

        <hr class="hr">
        
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-exito">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form class="formulario" method="POST" action="login.php">
            <input type="email" name="email" required placeholder="Correo electrónico">
            <input type="password" name="password" required placeholder="Contraseña">
            <button class="boton" type="submit">Iniciar Sesión</button>
        </form>
        
        <div class="enlace-formulario">
            ¿No tienes cuenta? <a href="signup.php">Registrarse</a>
        </div>
    </main>
    <footer class="pie-pagina">
        <div class="pie-contenedor">
            <p>&copy; 2026 iVento · ApexDigital SA</p>
            <nav class="pie-enlaces">
                <a href="home.php">Inicio</a>
                <a href="signup.php">Registrarse</a>
                <a href="login.php">Ingresar</a>
            </nav>
        </div>
    </footer>
</body>
</html>
