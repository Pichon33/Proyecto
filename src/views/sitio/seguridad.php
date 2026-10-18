<?php
session_start();
require_once dirname(__DIR__,3).'/includes/config.php';
require_once dirname(__DIR__,3).'/includes/seguridad.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if (empty($_SESSION['user_id'])) { header('Location: index.php?ruta=login'); exit; }
$id=(int)$_SESSION['user_id'];
$error=''; $mensaje=''; $codigos=[];
$acciones=['verificar'=>'Verificar correo','activar'=>'Activar dos pasos',
    'desactivar'=>'Desactivar dos pasos','recuperacion'=>'Renovar códigos de recuperación'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        if (!csrfValido($_POST['csrf']??null)) {
            http_response_code(403); throw new RuntimeException('La sesión cambió. Recargá la página.');
        }
        $accion=datoFormulario('accion');
        if ($accion==='cancelar') {
            unset($_SESSION['auth_gestion']);
            header('Location: index.php?ruta=seguridad',true,303); exit;
        } elseif ($accion==='confirmar' || $accion==='reenviar') {
            if (empty($_SESSION['auth_gestion']) || $_SESSION['auth_gestion']['usuario']!==$id) {
                throw new RuntimeException('Primero elegí una acción y confirmá tu contraseña.');
            }
            if ($accion==='reenviar') {
                seguridadEnviar($_SESSION['auth_gestion']);
                $mensaje='Enviamos un código nuevo. El anterior dejó de ser válido.';
            } else {
                $proposito=$_SESSION['auth_gestion']['proposito'];
                $resultado=seguridadConfirmar($_SESSION['auth_gestion'],datoFormulario('codigo'));
                $codigos=$resultado['codigos']; completarAcceso($resultado['cuenta']);
                $mensajes=['verificar'=>'Tu correo quedó verificado.', 'activar'=>'El ingreso en dos pasos está activado.',
                    'desactivar'=>'El ingreso en dos pasos quedó desactivado.', 'recuperacion'=>'Tus códigos anteriores ya no sirven. Guardá los nuevos.'];
                $mensaje=$mensajes[$proposito];
            }
        } elseif (isset($acciones[$accion])) {
            $u=seguridadContrasena($id,is_string($_POST['password']??null)?$_POST['password']:'');
            if (($u['dos_pasos'] && in_array($accion,['activar','verificar'],true)) ||
                (!$u['dos_pasos'] && in_array($accion,['desactivar','recuperacion'],true))) {
                throw new RuntimeException('Esta acción no corresponde al estado actual de la cuenta.');
            }
            $enviado=seguridadIniciar($_SESSION['auth_gestion'],$u,$accion);
            $mensaje=$enviado ? 'Enviamos un código de seis números a tu correo. Vence en 5 minutos.' :
                'Ya hay un código enviado para este paso. Usá ese mismo código; no enviamos otro.';
        } else { throw new RuntimeException('Elegí una acción válida.'); }
    } catch (PDOException $e) { $error='No pudimos guardar el cambio. Intentá nuevamente más tarde.'; }
    catch (RuntimeException $e) { $error=$e->getMessage(); }
    catch (Throwable $e) { $error='No pudimos completar la operación. Intentá nuevamente más tarde.'; }
}
$cuenta=seguridadCuenta($id);
$activo=(bool)$cuenta['dos_pasos'];
$verificado=$cuenta['correo_verificado']!==null && $cuenta['correo_verificado']===$cuenta['email'];
$disponible=correoDisponible();
$pendiente=$_SESSION['auth_gestion']??null;
if ($pendiente && ($pendiente['vence']<time() || $pendiente['usuario']!==$id)) {
    unset($_SESSION['auth_gestion']); $pendiente=null;
    $error='La operación venció. Volvé a confirmar tu contraseña para empezar.';
}
$restantes=(int)seguridadConsulta('SELECT COUNT(*) FROM RecuperacionAcceso WHERE id_usuario=?',[$id])->fetchColumn();
$esperaEnvio=$pendiente ? seguridadEsperaEnvio($id) : 0;
?>
<!DOCTYPE html>
<html lang="es" class="motion-reduce:scroll-auto">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow"><title>Seguridad de tu cuenta | iVento</title>
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="stylesheet" href="public/css/tailwind.css?v=<?= filemtime(dirname(__DIR__,3).'/public/css/tailwind.css') ?>">
    <script src="public/js/tema.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/tema.js') ?>"></script>
</head>
<body class="iv-app iv-con-navegacion">
<?php require dirname(__DIR__,3).'/includes/navegacion.php'; ?>
<main class="mx-auto w-full max-w-2xl px-4 py-6 sm:px-6 sm:py-8">
    <a class="iv-back" href="index.php?ruta=perfil">← Mi perfil</a>
    <header class="mb-5 mt-5">
        <span class="iv-kicker">Tu cuenta, protegida</span>
        <h1 class="iv-title">Seguridad</h1>
        <p class="iv-muted mb-0">Un paso más para que solo vos puedas entrar.</p>
    </header>
    <?php if ($error!==''): ?><div id="error-formulario" tabindex="-1" class="iv-feedback iv-feedback-error mb-4 scroll-mt-4" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($mensaje!==''): ?><div data-resultado-acceso tabindex="-1" class="iv-feedback iv-feedback-ok mb-4 scroll-mt-4" role="status"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($codigos): ?>
    <section class="iv-panel mb-4" aria-labelledby="titulo-recuperacion">
        <h2 id="titulo-recuperacion" class="iv-section-title">Guardá estos códigos</h2>
        <p class="iv-muted text-sm">Cada uno permite entrar una sola vez si no podés recibir el correo. Guardalos en un lugar privado: solo se muestran ahora. La contraseña sigue siendo necesaria.</p>
        <ul class="m-0 grid list-none gap-2 p-0" aria-label="Códigos de recuperación">
            <?php foreach ($codigos as $codigo): ?><li class="rounded-xl bg-violet-50 px-2 py-3 text-center font-mono text-sm tracking-wide text-violet-900 select-all dark:bg-indigo-400/10 dark:text-violet-200"><?= htmlspecialchars($codigo) ?></li><?php endforeach; ?>
        </ul>
        <a href="index.php?ruta=seguridad" class="iv-btn mt-5 w-full">Ya guardé mis códigos</a>
    </section>
    <?php elseif ($pendiente): ?>
    <section class="iv-panel">
        <span class="iv-kicker">Último paso</span>
        <h2 class="iv-section-title mt-2"><?= htmlspecialchars($acciones[$pendiente['proposito']]) ?></h2>
        <p class="iv-muted break-words text-sm"><?= $pendiente['desafio']!==''?'Ingresá el código enviado a':'Solicitá un nuevo código para' ?> <strong><?= htmlspecialchars($cuenta['email']) ?></strong>.</p>
        <form method="post" class="space-y-4" data-acceso>
            <?= campoCsrf() ?><input type="hidden" name="accion" value="confirmar">
            <label class="iv-label" for="codigo">Código de seguridad</label>
            <input class="iv-input text-center font-mono tracking-widest" name="codigo" id="codigo" type="text" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" maxlength="29" required autofocus>
            <?php if ($activo): ?><p class="iv-muted text-sm">También podés pegar un código de recuperación.</p><?php endif; ?>
            <button type="submit" class="iv-btn w-full">Confirmar</button>
        </form>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <form method="post" data-acceso><?= campoCsrf() ?><button class="iv-btn-light w-full" name="accion" value="reenviar" data-reenviar data-espera="<?= $esperaEnvio ?>">Reenviar código</button></form>
            <form method="post" data-acceso><?= campoCsrf() ?><button class="iv-btn-light w-full" name="accion" value="cancelar">Cancelar</button></form>
        </div>
        <p class="iv-muted mb-0 mt-4 text-xs">El código vence en 5 minutos. Esperá 60 segundos entre envíos.</p>
    </section>
    <?php else: ?>
    <section class="iv-panel mb-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="iv-section-title m-0 text-base">Tu correo</h2>
            <span class="iv-chip text-xs"><?= $verificado?'Verificado':'Sin verificar' ?></span>
        </div>
        <p class="mb-0 break-words text-sm"><?= htmlspecialchars($cuenta['email']) ?></p>
        <a href="index.php?ruta=perfil/editar#correo-perfil" class="mt-3 inline-flex text-sm font-semibold text-violet-700 underline underline-offset-4 dark:text-violet-300">Editar mis datos</a>
    </section>
    <section class="iv-panel">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="iv-section-title m-0 text-lg">Ingreso en dos pasos</h2>
            <span class="iv-chip text-xs"><?= $activo?'Activado':'Desactivado' ?></span>
        </div>
        <p class="iv-muted text-sm"><?= $activo ? 'Después de tu contraseña, te pediremos un código enviado a tu correo.' : 'Sumá un código por correo a tu contraseña. Al activarlo también verificamos tu dirección.' ?></p>
        <?php if ($activo): ?><p class="iv-muted text-sm">Tenés <strong><?= $restantes ?></strong> códigos de recuperación disponibles. Al renovarlos, los anteriores dejan de funcionar.</p><?php endif; ?>
        <?php if (!$disponible): ?><p class="iv-feedback text-sm" role="status">El envío de códigos todavía no está disponible.<?= $activo?' Podés usar tus códigos de recuperación.':' Podrás activar esta opción cuando esté habilitado.' ?></p><?php endif; ?>
        <form method="post" class="mt-5 space-y-4" data-acceso>
            <?= campoCsrf() ?>
            <label class="iv-label" for="password">Confirmá tu contraseña actual
                <input class="iv-input" id="password" name="password" type="password" autocomplete="current-password" required <?= !$disponible&&!$activo?'disabled':'' ?>>
            </label>
            <div class="grid gap-2">
            <?php if ($activo): ?>
                <button class="iv-btn" name="accion" value="recuperacion">Renovar códigos de recuperación</button>
                <button class="iv-btn-light" name="accion" value="desactivar">Desactivar dos pasos</button>
            <?php else: ?>
                <button class="iv-btn" name="accion" value="activar" <?= !$disponible?'disabled':'' ?>>Activar dos pasos</button>
                <?php if (!$verificado): ?><button class="iv-btn-light" name="accion" value="verificar" <?= !$disponible?'disabled':'' ?>>Solo verificar mi correo</button><?php endif; ?>
            <?php endif; ?>
            </div>
        </form>
    </section>
    <?php endif; ?>
    <p class="iv-muted mt-5 text-center text-xs">iVento nunca te pedirá estos códigos por mensajes o llamadas.</p>
</main>
<?php $mostrarNavegacionInferior=true; $pagina='perfil'; require dirname(__DIR__,3).'/includes/pie.php'; ?>
<script src="public/js/java.js?v=<?= filemtime(dirname(__DIR__,3).'/public/js/java.js') ?>"></script>
</body>
</html>
