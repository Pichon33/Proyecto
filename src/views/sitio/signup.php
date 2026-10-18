<?php
session_start();
$urlBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$urlCanonica = $urlBase . '/index.php?ruta=registro';

require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/models/user.php';
require_once dirname(__DIR__, 3) . '/includes/seguridad.php';

$error = '';
$usuario = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $contrasena = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmar = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

    if (!csrfValido($_POST['csrf'] ?? null)) {
        http_response_code(403);
        $error = 'La sesión cambió. Recargá el formulario e intentá nuevamente.';
    } elseif ($usuario === '') {
        $error = 'Completá tu nombre de usuario.';
    } elseif (mb_strlen($usuario) > 255) {
        $error = 'El nombre de usuario puede tener hasta 255 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresá un correo válido.';
    } elseif ($contrasena !== $confirmar) {
        $error = 'Las contraseñas no coinciden. Escribí la misma en ambos campos.';
    } elseif (strlen($contrasena) > 72) {
        $error = 'La contraseña es demasiado larga. Usá una más corta (hasta 72 bytes).';
    } else if (!validarContrasena($contrasena)) {
        $error = 'La contraseña debe tener al menos 6 caracteres, una mayúscula, una minúscula y un número o uno de estos símbolos: !@#$%^&*.';
    } else {
        try {
            limitarFormularioAcceso('registro',$email);
            $usuarioObj = new User($pdo);
            $usuarioObj->register($usuario, $contrasena, $email);
            $_SESSION['mensaje'] = 'Registro exitoso. Ya podés iniciar sesión con tu correo y contraseña.';
            header('Location: index.php?ruta=login');
            exit;
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                $error = 'Ya existe una cuenta con ese correo. Iniciá sesión o usá otro correo.';
            } else {
                $error = 'No pudimos crear la cuenta. Intentá de nuevo en unos minutos.';
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (Exception $e) {
            $error = 'No pudimos crear la cuenta. Intentá de nuevo en unos minutos.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es" class="motion-reduce:scroll-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <meta name="description" content="Creá tu cuenta gratuita en iVento y empezá a descubrir y publicar eventos en Juan Lacaze y alrededores.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($urlCanonica); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Registrarse | iVento">
    <meta property="og:description" content="Sumate a iVento: creá tu cuenta gratis y descubrí eventos cerca tuyo.">
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
        <h1 class="titulo-formulario">Creá tu cuenta</h1>
        <p class="introduccion-formulario">Completá estos datos para sumarte a iVento.</p>



        <?php if (!empty($error)): ?>
            <div class="error" id="error-formulario" role="alert" tabindex="-1">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form class="formulario" method="POST" action="index.php?ruta=registro">
            <?= campoCsrf() ?>
            <div class="campo-formulario">
                <label for="usuario">Nombre de usuario</label>
                <input type="text" name="username" id="usuario" required maxlength="255"
                    autocomplete="nickname" placeholder="Ej.: Ana"
                    value="<?php echo htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="campo-formulario">
                <label for="email">Correo electrónico</label>
                <input type="email" name="email" id="email" required autocomplete="email"
                    autocapitalize="none" spellcheck="false" placeholder="Ej.: nombre@correo.com"
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="campo-formulario">
                <label for="contrasena">Contraseña</label>
                <input type="password" name="password" id="contrasena" required minlength="6"
                    autocomplete="new-password" aria-describedby="requisitos" data-contrasena
                    onchange="validarContrasenas()" oninput="validarContrasenas()">
            </div>

            <div class="validacion" id="requisitos">
                <p class="titulo-requisitos">Tu contraseña debe incluir:</p>
                <div class="validacion-item" id="largo">
                    ○ Mínimo 6 caracteres
                </div>
                <div class="validacion-item" id="mayuscula">
                    ○ Una letra mayúscula
                </div>
                <div class="validacion-item" id="minuscula">
                    ○ Una letra minúscula
                </div>
                <div class="validacion-item" id="numero">
                    ○ Un número o uno de estos símbolos: !@#$%^&amp;*
                </div>
            </div>

            <div class="campo-formulario">
                <label for="confirmar">Confirmar contraseña</label>
                <input type="password" name="confirm_password" id="confirmar" required
                    autocomplete="new-password" aria-describedby="coincide" data-contrasena
                    onchange="validarContrasenas()" oninput="validarContrasenas()">
                <p class="validacion-item ayuda-formulario" id="coincide" aria-live="polite">Repetí la misma contraseña.</p>
            </div>

            <label class="opcion-contrasena" hidden>
                <input type="checkbox" id="mostrarContrasenas"> Mostrar contraseñas
            </label>
            <button class="iv-btn w-full" type="submit" id="button">Registrarse</button>
        </form>

        <div class="enlace-formulario">
            ¿Ya tenés cuenta? <a href="index.php?ruta=login">Iniciá sesión</a>
        </div>
    </main>

    <script>
        function validarContrasenas() {
            let campoContrasena = document.getElementById('contrasena');
            let campoConfirmar = document.getElementById('confirmar');
            let contrasena = campoContrasena.value;
            let confirmar = campoConfirmar.value;

            let largo = contrasena.length >= 6;
            let mayuscula = /[A-Z]/.test(contrasena);
            let minuscula = /[a-z]/.test(contrasena);
            let numero = /[0-9]|[!@#$%^&*]/.test(contrasena);
            let coincide = contrasena === confirmar && confirmar.length > 0;

            actualizarRequisito('largo', largo, contrasena !== '');
            actualizarRequisito('mayuscula', mayuscula, contrasena !== '');
            actualizarRequisito('minuscula', minuscula, contrasena !== '');
            actualizarRequisito('numero', numero, contrasena !== '');

            let mensaje = '';
            if (!largo) mensaje = 'Usá al menos 6 caracteres.';
            else if (!mayuscula) mensaje = 'Agregá una letra mayúscula.';
            else if (!minuscula) mensaje = 'Agregá una letra minúscula.';
            else if (!numero) mensaje = 'Agregá un número o uno de estos símbolos: !@#$%^&*.';
            campoContrasena.setCustomValidity(contrasena === '' ? '' : mensaje);
            campoConfirmar.setCustomValidity(confirmar !== '' && !coincide ? 'Escribí la misma contraseña en ambos campos.' : '');

            let ayuda = document.getElementById('coincide');
            ayuda.textContent = confirmar === '' ? 'Repetí la misma contraseña.' : (coincide ? '✓ Las contraseñas coinciden.' : '✗ Las contraseñas no coinciden.');
            ayuda.className = 'validacion-item ayuda-formulario' + (confirmar === '' ? '' : (coincide ? ' ok' : ' no-ok'));
        }

        function actualizarRequisito(id, cumple, escrito) {
            let elemento = document.getElementById(id);
            elemento.className = 'validacion-item' + (escrito ? (cumple ? ' ok' : ' no-ok') : '');
            elemento.textContent = elemento.textContent.replace(/[○✓✗]/, escrito ? (cumple ? '✓' : '✗') : '○');
        }
    </script>
    <script src="public/js/java.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/java.js') ?>"></script>
    <?php require dirname(__DIR__,3).'/includes/pie.php'; ?>
</body>
</html>
