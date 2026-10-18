<?php
session_start();
require_once dirname(__DIR__,3).'/includes/config.php';
require_once dirname(__DIR__,3).'/includes/seguridad.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
$error=''; $email='';
$mensaje=$_SESSION['aviso_recuperar']??''; unset($_SESSION['aviso_recuperar']);
if (isset($_GET['token'])) {
    // Sacar el enlace de la barra antes de mostrar recursos o formularios.
    unset($_SESSION['restablecer']);
    try {
        limitarAcceso('restablecer:enlace:'.($_SERVER['REMOTE_ADDR']??'local'),60,900);
        $token=is_string($_GET['token']) ? $_GET['token'] : '';
        if (!preg_match('/^[a-f0-9]{64}$/D',$token)) { throw new RuntimeException('El enlace no es válido. Solicitá uno nuevo.'); }
        $hash=hash('sha256',$token); desafioRestablecimiento($hash);
        $_SESSION['restablecer']=$hash;
    } catch (RuntimeException $e) { $_SESSION['aviso_recuperar']='El enlace venció o no es válido. Solicitá uno nuevo.'; }
    header('Location: index.php?ruta=recuperar',true,303); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrfValido($_POST['csrf']??null)) {
        http_response_code(403); $error='La sesión cambió. Recargá el formulario e intentá nuevamente.';
    } else {
        try {
            $accion=datoFormulario('accion');
            if ($accion==='cancelar') {
                unset($_SESSION['restablecer']); header('Location: index.php?ruta=recuperar',true,303); exit;
            } elseif ($accion==='guardar') {
                $clave=is_string($_POST['password']??null)?$_POST['password']:'';
                $confirmar=is_string($_POST['confirm_password']??null)?$_POST['confirm_password']:'';
                $u=restablecerContrasena($_SESSION['restablecer']??'', $clave, $confirmar);
                // Cerrar también cualquier sesión de este navegador; el siguiente ingreso pide MFA.
                $_SESSION=[]; session_regenerate_id(true);
                $_SESSION['csrf']=bin2hex(random_bytes(32));
                $_SESSION['mensaje']='Tu contraseña quedó actualizada. Iniciá sesión con la nueva contraseña.';
                responderAntesDelCorreo('login');
                try { enviarMensajeCorreo($u['email'],'Tu contraseña de iVento cambió',
                    "Tu contraseña fue actualizada y cerramos las sesiones anteriores.\n".
                    "El ingreso en dos pasos conserva su configuración.\n\n".
                    "Si no hiciste este cambio, recuperá tu contraseña desde iVento y revisá la seguridad de tu correo.\n\niVento"); }
                catch (Throwable $e) { error_log('iVento: no se pudo entregar el aviso de cambio de contraseña.'); }
                exit;
            } else {
                $email=datoFormulario('email');
                if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>255) { throw new RuntimeException('Ingresá un correo válido.'); }
                $envio=prepararRestablecimiento($email);
                $_SESSION['aviso_recuperar']='Si ese correo está registrado, recibirás un enlace para elegir una nueva contraseña. Revisá también spam. Esperá 60 segundos antes de solicitar otro.';
                responderAntesDelCorreo('recuperar');
                if ($envio) { enviarRestablecimiento($envio); }
                exit;
            }
        } catch (PDOException $e) { $error='No pudimos completar la operación. Intentá de nuevo más tarde.'; }
        catch (RuntimeException $e) { $error=$e->getMessage(); }
        catch (Throwable $e) { $error='No pudimos completar la operación. Intentá de nuevo más tarde.'; }
    }
}
$cambiar=!empty($_SESSION['restablecer']);
if ($cambiar) {
    try { desafioRestablecimiento($_SESSION['restablecer']); }
    catch (RuntimeException $e) { unset($_SESSION['restablecer']); $cambiar=false; $error=$e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Recuperar contraseña | iVento</title>
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,3).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/tema.js') ?>"></script>
</head>
<body class="iv-app pagina-autenticacion">
    <?php require dirname(__DIR__,3).'/includes/navegacion.php'; ?>
    <div class="enlace-volver"><a href="index.php?ruta=login">← Volver al ingreso</a></div>
    <main class="iv-auth-card">
        <div class="iv-logo-tematico"><img class="iv-logo-claro" src="public/img/ivento-logo-horizontal.png" alt="iVento"><img class="iv-logo-oscuro" src="public/img/ivento-logo-horizontal-blanco.png" alt="iVento"></div>
        <h1 class="titulo-formulario"><?= $cambiar?'Elegí una nueva contraseña':'Recuperá tu cuenta' ?></h1>
        <p class="introduccion-formulario"><?= $cambiar?'Un último paso para volver a tus planes.':'Te enviamos un enlace para que puedas volver a entrar.' ?></p>
        <?php if ($mensaje): ?><div class="mensaje-exito" role="status" tabindex="-1" data-resultado-acceso><?= htmlspecialchars($mensaje,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error" role="alert" id="error-formulario" tabindex="-1"><?= htmlspecialchars($error,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
        <form class="formulario" method="post" action="index.php?ruta=recuperar" data-acceso>
            <?= campoCsrf() ?>
            <input type="hidden" name="accion" value="<?= $cambiar?'guardar':'solicitar' ?>">
            <?php if ($cambiar): ?>
                <div class="campo-formulario"><label for="nueva-clave">Nueva contraseña</label><input id="nueva-clave" type="password" name="password" autocomplete="new-password" minlength="6" required data-contrasena aria-describedby="ayuda-clave"></div>
                <p id="ayuda-clave" class="iv-muted text-sm">Al menos 6 caracteres, con mayúscula, minúscula y un número o símbolo (!@#$%^&*). Te recomendamos una frase larga que no uses en otras cuentas.</p>
                <div class="campo-formulario"><label for="repetir-clave">Repetí la nueva contraseña</label><input id="repetir-clave" type="password" name="confirm_password" autocomplete="new-password" minlength="6" required data-contrasena></div>
                <label class="opcion-contrasena" hidden><input type="checkbox" id="mostrarContrasenas"> Mostrar contraseñas</label>
                <p class="iv-muted text-sm">Se cerrarán las sesiones anteriores. Si activaste los dos pasos, seguirás usando tu código al ingresar.</p>
                <button type="submit" class="iv-btn w-full">Guardar nueva contraseña</button>
            <?php else: ?>
                <div class="campo-formulario"><label for="correo-recuperar">Correo electrónico</label><input id="correo-recuperar" type="email" name="email" autocomplete="email" autocapitalize="none" spellcheck="false" maxlength="255" placeholder="El correo de tu cuenta" required value="<?= htmlspecialchars($email,ENT_QUOTES,'UTF-8') ?>"></div>
                <p class="iv-muted text-sm">El enlace dura 15 minutos y funciona una sola vez. Tu contraseña no cambia hasta que elijas una nueva.</p>
                <button type="submit" class="iv-btn w-full">Enviar enlace de recuperación</button>
            <?php endif; ?>
        </form>
        <?php if ($cambiar): ?><form method="post" class="mt-4" data-acceso><?= campoCsrf() ?><button class="iv-btn-light w-full" name="accion" value="cancelar">Solicitar otro enlace</button></form><?php endif; ?>
        <div class="enlace-formulario"><a href="index.php?ruta=login">Ya puedo ingresar</a></div>
    </main>
    <?php require dirname(__DIR__,3).'/includes/pie.php'; ?>
    <script src="public/js/java.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/java.js') ?>"></script>
</body>
</html>
