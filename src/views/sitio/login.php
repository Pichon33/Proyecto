<?php
session_start();
$urlBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$urlCanonica = $urlBase . '/index.php?ruta=login';

require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/models/user.php';

$error = '';
$mensaje = '';
$email = '';

if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    // La contraseña se compara tal como se escribió, sin escapar ni quitar espacios.
    $contrasena = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresá un correo válido.';
    } elseif ($contrasena === '') {
        $error = 'Completá la contraseña.';
    } else {
        try {
            $usuarioObj = new User($pdo);
            $usuarioIniciado = $usuarioObj->login($contrasena, $email);

            if ($usuarioIniciado) {
                session_regenerate_id(true);
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
                $error = 'El correo o la contraseña no son correctos. Revisalos e intentá de nuevo.';
            }
        } catch (Exception $e) {
            $error = 'No pudimos iniciar la sesión. Intentá de nuevo en unos minutos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es" class="motion-reduce:scroll-auto">
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
    <link rel="stylesheet" href="public/css/tailwind.css">
</head>
<body class="pagina-autenticacion">
    <header class="cabecera max-sm:static">
        <div class="contenedor-cabecera max-sm:min-h-0 max-sm:pt-2 max-sm:pb-3 max-sm:flex-wrap max-sm:gap-x-3 max-sm:gap-y-1.5">
            <a href="index.php?ruta=home" class="logo-header max-sm:[&_img]:w-32.5 max-sm:[&_img]:h-11">
                <img src="public/img/ivento-logo-horizontal-blanco.png" alt="iVento">
            </a>
            <div class="nav-buttons max-sm:gap-1 max-sm:[&_a]:text-[0.875rem]">
                <a href="index.php?ruta=registro" class="boton-registrarse">Registrarse</a>
            </div>
        </div>
    </header>
    <div class="enlace-volver">
        <a href="index.php?ruta=home">← Volver</a>
    </div>
    <main class="contenedor max-sm:p-[22px]">
        <img class="logo" src="public/img/ivento-logo-horizontal.png" alt="iVento">

        <h1 class="titulo-formulario">Iniciá sesión</h1>
        <p class="introduccion-formulario">Ingresá con tu correo y contraseña.</p>

        <hr class="hr">

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-exito" role="status">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error" id="error-formulario" role="alert" tabindex="-1">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form class="formulario" method="POST" action="index.php?ruta=login">
            <div class="campo-formulario">
                <label for="email">Correo electrónico</label>
                <input type="email" name="email" id="email" required autocomplete="username"
                    autocapitalize="none" spellcheck="false" placeholder="Ej.: nombre@correo.com"
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="campo-formulario">
                <label for="contrasena">Contraseña</label>
                <input type="password" name="password" id="contrasena" required
                    autocomplete="current-password" data-contrasena>
            </div>
            <label class="opcion-contrasena" hidden>
                <input type="checkbox" id="mostrarContrasenas"> Mostrar contraseña
            </label>
            <button class="boton" type="submit">Iniciar sesión</button>
        </form>

        <div class="enlace-formulario">
            ¿No tenés cuenta? <a href="index.php?ruta=registro">Creá tu cuenta</a>
        </div>
    </main>
    <footer class="pie-pagina">
        <div class="pie-contenedor max-sm:items-start max-sm:flex-col">
            <p>&copy; 2026 iVento · ApexDigital SA</p>
            <nav class="pie-enlaces">
                <a href="index.php?ruta=home">Inicio</a>
                <a href="index.php?ruta=registro">Registrarse</a>
                <a href="index.php?ruta=login">Ingresar</a>
            </nav>
        </div>
    </footer>
    <script src="public/js/java.js"></script>
</body>
</html>
