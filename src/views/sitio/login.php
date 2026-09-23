<?php
session_start();
$urlBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$urlCanonica = $urlBase . '/index.php?ruta=login';

require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/models/user.php';

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
            header('Location: index.php?ruta=home');
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
    <meta name="description" content="Iniciá sesión en iVento para acceder a tus eventos favoritos, tu perfil y las publicaciones de tu zona.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($urlCanonica); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Iniciar sesión | iVento">
    <meta property="og:description" content="Accedé a tu cuenta de iVento para gestionar tus eventos y favoritos.">
    <meta property="og:image" content="<?php echo htmlspecialchars($urlBase . '/public/img/ivento-logo-horizontal.png'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($urlCanonica); ?>">
    <meta property="og:locale" content="es_UY">
    <link rel="stylesheet" href="public/css/styles.css">
</head>
<body class="pagina-autenticacion">
    <header class="cabecera">
        <div class="contenedor-cabecera">
            <a href="index.php?ruta=home" class="logo-header">
                <img src="public/img/ivento-logo-horizontal-blanco.png" alt="iVento">
            </a>
            <div class="nav-buttons">
                <a href="index.php?ruta=registro" class="boton-registrarse">Registrarse</a>
            </div>
        </div>
    </header>
    <div class="enlace-volver">
        <a href="index.php?ruta=home">← Volver</a>
    </div>
    <main class="contenedor">
        <img class="logo" src="public/img/ivento-logo-horizontal.png" alt="iVento">

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

        <form class="formulario" method="POST" action="index.php?ruta=login">
            <input type="email" name="email" required placeholder="Correo electrónico">
            <input type="password" name="password" required placeholder="Contraseña">
            <button class="boton" type="submit">Iniciar Sesión</button>
        </form>

        <div class="enlace-formulario">
            ¿No tienes cuenta? <a href="index.php?ruta=registro">Registrarse</a>
        </div>
    </main>
    <footer class="pie-pagina">
        <div class="pie-contenedor">
            <p>&copy; 2026 iVento · ApexDigital SA</p>
            <nav class="pie-enlaces">
                <a href="index.php?ruta=home">Inicio</a>
                <a href="index.php?ruta=registro">Registrarse</a>
                <a href="index.php?ruta=login">Ingresar</a>
            </nav>
        </div>
    </footer>
</body>
</html>
