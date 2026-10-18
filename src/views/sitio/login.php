<?php
session_start();
$urlBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$urlCanonica = $urlBase . '/index.php?ruta=login';

require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/models/user.php';
require_once dirname(__DIR__, 3) . '/includes/seguridad.php';
header('Cache-Control: no-store');

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

    if (!csrfValido($_POST['csrf'] ?? null)) {
        http_response_code(403);
        $error = 'La sesión cambió. Recargá el formulario e intentá nuevamente.';
    } else {
        try {
            $accion = datoFormulario('accion');
            if ($accion === 'cancelar') {
                unset($_SESSION['auth_login']);
                header('Location: index.php?ruta=login',true,303); exit;
            } elseif (in_array($accion,['confirmar','reenviar'],true)) {
                if (empty($_SESSION['auth_login'])) { throw new RuntimeException('Primero ingresá tu correo y contraseña.'); }
                if ($accion==='reenviar') {
                    seguridadEnviar($_SESSION['auth_login']);
                    $mensaje='Enviamos un nuevo código. El anterior ya no sirve.';
                } else {
                    $resultado=seguridadConfirmar($_SESSION['auth_login'],datoFormulario('codigo'));
                    completarAcceso($resultado['cuenta']);
                    header('Location: index.php?ruta=home'); exit;
                }
            } else {
                limitarFormularioAcceso('login',$email);
                if (!filter_var($email,FILTER_VALIDATE_EMAIL) || $contrasena==='' || strlen($contrasena)>72) {
                    throw new RuntimeException('Ingresá un correo y una contraseña válidos.');
                }
                $usuarioIniciado=(new User($pdo))->login($contrasena,$email);
                if (!$usuarioIniciado) { throw new RuntimeException('El correo o la contraseña no son correctos. Revisalos e intentá de nuevo.'); }
                $u=seguridadCuenta((int)$usuarioIniciado['id_usuario']);
                if (!hash_equals($usuarioIniciado['password'],$u['password'])) { throw new RuntimeException('La cuenta cambió. Volvé a iniciar sesión.'); }
                if ($u['dos_pasos']) {
                    // La contraseña sola NO crea una sesión autenticada.
                    session_regenerate_id(true);
                    unset($_SESSION['user_id'],$_SESSION['username'],$_SESSION['email'],$_SESSION['rol']);
                    $enviado=seguridadIniciar($_SESSION['auth_login'],$u,'login');
                    $mensaje=$enviado ? 'Te enviamos un código de seis números. Vence en 5 minutos.' :
                        'Ya te enviamos un código. Usá el mismo para completar el ingreso.';
                } else {
                    completarAcceso($u);
                    header('Location: index.php?ruta=home'); exit;
                }
            }
        } catch (PDOException $e) {
            $error = 'No pudimos iniciar la sesión. Intentá de nuevo en unos minutos.';
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'No pudimos iniciar la sesión. Intentá de nuevo en unos minutos.';
        }
    }
}
$pendiente = !empty($_SESSION['auth_login']);
if ($pendiente && $_SESSION['auth_login']['vence'] < time()) {
    unset($_SESSION['auth_login']); $pendiente=false;
    $error='El tiempo para ingresar terminó. Volvé a escribir tu correo y contraseña.';
}
$esperaEnvio=$pendiente ? seguridadEsperaEnvio($_SESSION['auth_login']['usuario']) : 0;
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
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,3).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/tema.js') ?>"></script>
</head>
<body class="iv-app pagina-autenticacion">
    <?php require dirname(__DIR__,3).'/includes/navegacion.php'; ?>
    <div class="enlace-volver">
        <a href="index.php?ruta=home">← Volver</a>
    </div>
    <main class="iv-auth-card">
        <div class="iv-logo-tematico"><img class="iv-logo-claro" src="public/img/ivento-logo-horizontal.png" alt="iVento"><img class="iv-logo-oscuro" src="public/img/ivento-logo-horizontal-blanco.png" alt="iVento"></div>

        <h1 class="titulo-formulario"><?= $pendiente ? 'Confirmá que sos vos' : 'Iniciá sesión' ?></h1>
        <p class="introduccion-formulario"><?= $pendiente ? 'Completá el segundo paso para entrar a tu cuenta.' : 'Ingresá con tu correo y contraseña.' ?></p>



        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-exito" role="status" data-resultado-acceso tabindex="-1">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error" id="error-formulario" role="alert" tabindex="-1">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($pendiente): ?>
        <form class="formulario" method="POST" action="index.php?ruta=login" data-acceso>
            <?= campoCsrf() ?>
            <input type="hidden" name="accion" value="confirmar">
            <label class="iv-label" for="codigo">Código de seguridad</label>
            <input class="iv-input text-center font-mono tracking-widest" id="codigo" name="codigo" type="text" autocomplete="one-time-code" inputmode="text" maxlength="29" placeholder="123456" required autofocus aria-describedby="ayuda-codigo">
            <p id="ayuda-codigo" class="iv-muted text-sm">Revisá tu correo y la carpeta de spam. También podés pegar uno de tus códigos de recuperación.</p>
            <button class="iv-btn w-full" type="submit">Verificar e ingresar</button>
        </form>
        <div class="mt-4 grid gap-2 sm:grid-cols-2">
            <form method="post" data-acceso><?= campoCsrf() ?><button class="iv-btn-light w-full" name="accion" value="reenviar" type="submit" data-reenviar data-espera="<?= $esperaEnvio ?>">Reenviar código</button></form>
            <form method="post" data-acceso><?= campoCsrf() ?><button class="iv-btn-light w-full" name="accion" value="cancelar" type="submit">Volver al ingreso</button></form>
        </div>
        <p class="iv-muted text-center text-xs">Esperá al menos 60 segundos entre envíos.</p>
        <?php else: ?>
        <form class="formulario" method="POST" action="index.php?ruta=login" data-acceso>
            <?= campoCsrf() ?>
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
            <button class="iv-btn w-full" type="submit">Iniciar sesión</button>
        </form>

        <div class="enlace-formulario"><a href="index.php?ruta=recuperar">Olvidé mi contraseña</a></div>

        <div class="enlace-formulario">
            ¿No tenés cuenta? <a href="index.php?ruta=registro">Creá tu cuenta</a>
        </div>
        <?php endif; ?>
    </main>
    <?php require dirname(__DIR__,3).'/includes/pie.php'; ?>
    <script src="public/js/java.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/java.js') ?>"></script>
</body>
</html>
